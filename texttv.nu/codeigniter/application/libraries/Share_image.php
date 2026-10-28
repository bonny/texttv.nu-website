<?php

/**
 * Ritar delningsbilden för en eller flera arkivsidor med GD (todo #34).
 *
 * Ersätter wkhtmltoimage, som skärmdumpade arkivsidan över HTTP. En
 * text-tv-sida är ett rutnät med 40 tecken per rad och få färger, så bilden
 * ritas cell för cell direkt från sidans HTML i databasen.
 *
 * HTML:en finns i två format, och båda går igenom samma trädvandring:
 * - importerns sedan 2021: en `span.line` per rad, färgklasser per span,
 *   dubbelhöjd som `span.line.DH` och blockgrafik som `span.bgImg` med en
 *   GIF (13 × 16 px, klippt ur SVT:s sidbild) i `style`.
 * - det äldre: rader åtskilda av `\n` i texten, ingen blockgrafik, ibland
 *   `<h1>` i toppraden.
 *
 * Klassen beror inte på CodeIgniter, så den kan provköras från kommandoraden.
 */
class Share_image
{
	const COLS = 40;
	const ROWS = 25;
	const CELL_W = 16;
	const CELL_H = 32;
	const FONT_SIZE = 24; // punkter vid GD:s 96 dpi = 32 px, som sajtens CSS
	const PAD = 16;
	const FOOTER_H = 90;
	const PAGE_GAP = 16; // mellan undersidor och sidor

	// Importerns blockgrafik. Dev-stacken monterar importerns katalog på
	// samma sökväg i webbcontainern (compose.override.yaml).
	const CHARS_DIR = '/usr/share/nginx/l.texttv.nu/importer/storage/app/public/chars';

	const FG = [
		'W' => [252, 254, 252], 'Y' => [252, 254, 1], 'C' => [0, 200, 238],
		'G' => [0, 255, 0], 'R' => [255, 0, 0], 'M' => [252, 3, 253],
		'B' => [17, 34, 204], 'bl' => [0, 0, 0], 'top' => [221, 221, 221],
	];
	const BG = [
		'bgW' => [252, 254, 252], 'bgY' => [252, 254, 1], 'bgC' => [0, 200, 238],
		'bgG' => [0, 255, 0], 'bgR' => [252, 3, 4], 'bgM' => [252, 3, 253],
		'bgB' => [0, 0, 255], 'bgBl' => [0, 0, 0],
	];

	private $chars_dir;
	private $font;
	private $font_bold;
	private $baseline;
	private $gifs = [];
	private $colors = [];

	/**
	 * @param string $chars_dir Importerns `storage/app/public/chars`. Saknas en
	 *                          GIF ritas bara cellens bakgrund.
	 */
	public function __construct($chars_dir = self::CHARS_DIR)
	{
		$this->chars_dir = rtrim($chars_dir, '/');
		// Hela Ubuntu Mono (UFL), inte sajtens webfont: den täcker bara
		// Latin-1, och t.ex. ł, č och Š blev rutor.
		$this->font = __DIR__ . '/../third_party/ubuntu-mono/UbuntuMono-Regular.ttf';
		$this->font_bold = __DIR__ . '/../third_party/ubuntu-mono/UbuntuMono-Bold.ttf';

		$bb = imagettfbbox(self::FONT_SIZE, 0, $this->font, 'Hgjåp');
		$this->baseline = (int) round((self::CELL_H - ($bb[1] - $bb[7])) / 2 - $bb[7]);
	}

	/**
	 * @param string[] $subpages HTML för varje undersida (`arr_contents`),
	 *                           för alla sidor i delningen i ordning.
	 * @param string   $format   'png' eller 'jpg'.
	 * @return string Bildens bytes.
	 */
	public function render(array $subpages, $format)
	{
		$pages = [];
		foreach ($subpages as $html) {
			foreach ($this->parse($html) as $rows) {
				$pages[] = $rows;
			}
		}

		$w = self::COLS * self::CELL_W + 2 * self::PAD;
		$h = self::PAD + self::FOOTER_H;
		foreach ($pages as $rows) {
			$h += count($rows) * self::CELL_H + self::PAGE_GAP;
		}

		$im = imagecreatetruecolor($w, $h);
		imagefill($im, 0, 0, $this->color($im, [0, 0, 0]));

		$y = self::PAD;
		foreach ($pages as $rows) {
			// Dubbelhöga rader sist: de täcker raden under, som scaleY(2) i CSS.
			foreach ($rows as $r => $row) {
				if (!$row['dh']) {
					$this->draw_row($im, $row, self::PAD, $y + $r * self::CELL_H);
				}
			}
			foreach ($rows as $r => $row) {
				if ($row['dh']) {
					$tmp = imagecreatetruecolor($w, self::CELL_H);
					$this->draw_row($tmp, $row, self::PAD, 0);
					imagecopyresampled($im, $tmp, 0, $y + $r * self::CELL_H, 0, 0, $w, 2 * self::CELL_H, $w, self::CELL_H);
				}
			}
			$y += count($rows) * self::CELL_H + self::PAGE_GAP;
		}

		$this->draw_footer($im, $w, $h);

		ob_start();
		if ($format === 'png') {
			// En palett på 64 färger räcker för text-tv och kantutjämningen,
			// och ger en fil på runt 20 kB i stället för 45.
			imagetruecolortopalette($im, false, 64);
			imagepng($im, null, 9);
		} else {
			imagejpeg($im, null, 85);
		}
		return ob_get_clean();
	}

	/**
	 * Delar upp HTML:en i undersidor (`div.root`) och dem i rader av celler.
	 *
	 * @return array[] En lista rader per undersida.
	 */
	private function parse($html)
	{
		$dom = new DOMDocument();
		$prev = libxml_use_internal_errors(true);
		$dom->loadHTML('<meta charset="utf-8">' . $html);
		libxml_clear_errors();
		libxml_use_internal_errors($prev);

		$out = [];
		$roots = (new DOMXPath($dom))->query('//div[contains(concat(" ", @class, " "), " root ")]');
		foreach ($roots as $root) {
			$rows = [['dh' => false, 'cells' => []]];
			$this->walk($root, ['fg' => 'W', 'bg' => 'bgBl', 'img' => null, 'link' => false, 'bold' => false], $rows);

			// Sista radbrytningen ger en tom rad, den ska inte ritas.
			while (count($rows) > 1 && !end($rows)['cells']) {
				array_pop($rows);
			}
			$out[] = array_slice($rows, 0, self::ROWS);
		}
		return $out;
	}

	private function walk(DOMNode $node, array $ctx, array &$rows)
	{
		if ($node instanceof DOMText) {
			foreach (mb_str_split($node->nodeValue) as $c) {
				if ($c === "\n") {
					$rows[] = ['dh' => false, 'cells' => []];
				} else {
					$rows[count($rows) - 1]['cells'][] = ['c' => $c] + $ctx;
				}
			}
			return;
		}
		if (!$node instanceof DOMElement) {
			return;
		}

		if ($node->tagName === 'a') {
			$ctx['link'] = true;
		} elseif ($node->tagName === 'h1' || $node->tagName === 'h2') {
			$ctx['bold'] = true;
		}
		foreach (preg_split('/\s+/', $node->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) as $class) {
			if (isset(self::BG[$class])) {
				$ctx['bg'] = $class;
			} elseif (isset(self::FG[$class])) {
				$ctx['fg'] = $class;
			} elseif ($class === 'toprow') {
				$ctx['fg'] = 'top';
			} elseif ($class === 'DH') {
				$rows[count($rows) - 1]['dh'] = true;
			}
		}
		if (preg_match('~chars/(\d+)\.gif~', $node->getAttribute('style'), $m)) {
			$ctx['img'] = $m[1];
		}

		foreach ($node->childNodes as $child) {
			$this->walk($child, $ctx, $rows);
		}
	}

	private function draw_row($im, array $row, $x0, $y0)
	{
		foreach ($row['cells'] as $col => $cell) {
			if ($col >= self::COLS) {
				break;
			}
			$x = $x0 + $col * self::CELL_W;
			imagefilledrectangle($im, $x, $y0, $x + self::CELL_W - 1, $y0 + self::CELL_H - 1, $this->color($im, self::BG[$cell['bg']]));

			if ($cell['img']) {
				$gif = $this->gif($cell['img']);
				if ($gif) {
					imagecopyresized($im, $gif, $x, $y0, 0, 0, self::CELL_W, self::CELL_H, imagesx($gif), imagesy($gif));
				}
				continue;
			}

			$fg = $this->color($im, self::FG[$cell['fg']]);
			if (trim($cell['c']) !== '') {
				imagettftext($im, self::FONT_SIZE, 0, $x, $y0 + $this->baseline, $fg, $cell['bold'] ? $this->font_bold : $this->font, $cell['c']);
			}
			if ($cell['link']) {
				imagefilledrectangle($im, $x, $y0 + $this->baseline + 3, $x + self::CELL_W - 1, $y0 + $this->baseline + 4, $fg);
			}
		}
	}

	/**
	 * Mörkblå list med loggan, som sajtens sidfot.
	 */
	private function draw_footer($im, $w, $h)
	{
		$y = $h - self::FOOTER_H;
		imagefilledrectangle($im, 0, $y, $w, $h, $this->color($im, [0, 41, 72]));
		imageline($im, 0, $y, $w, $y, $this->color($im, [60, 80, 100]));

		// Loggans fyrkanter, från images/text-tv-nu-logotyp.svg (350 × 350).
		$lx = (int) ($w / 2 - 90);
		$ly = $y + 22;
		$s = 46 / 350;
		imagefilledrectangle($im, $lx, $ly, $lx + 46, $ly + 46, $this->color($im, [0, 142, 255]));
		$squares = [[76, 75, [0, 73, 252]], [151, 75, [87, 198, 235]], [151, 150, [229, 219, 43]], [151, 225, [243, 166, 51]], [226, 75, [128, 242, 0]]];
		foreach ($squares as [$sx, $sy, $rgb]) {
			imagefilledrectangle($im, $lx + (int) ($sx * $s), $ly + (int) ($sy * $s), $lx + (int) (($sx + 50) * $s), $ly + (int) (($sy + 50) * $s), $this->color($im, $rgb));
		}
		imagettftext($im, 22, 0, $lx + 62, $ly + 34, $this->color($im, [255, 255, 255]), $this->font, 'TextTV.nu');
	}

	private function gif($crc)
	{
		if (!array_key_exists($crc, $this->gifs)) {
			$file = "{$this->chars_dir}/{$crc}.gif";
			$this->gifs[$crc] = is_file($file) ? @imagecreatefromgif($file) : false;
		}
		return $this->gifs[$crc];
	}

	private function color($im, array $rgb)
	{
		// Truecolor-bilder: färgen är bara ett heltal, oberoende av bilden.
		$key = implode(',', $rgb);
		return $this->colors[$key] ??= imagecolorallocate($im, ...$rgb);
	}
}
