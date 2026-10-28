<?php
// Prototyp: rita en arkiverad text-tv-sida (HTML från importern) med GD.
// php render.php <html-fil> <chars-dir> <font-dir> <ut.png>
[$_, $htmlFile, $charsDir, $fontDir, $out] = $argv;
$t0 = microtime(true);

const CW = 16;   // cellbredd (Ubuntu Mono 32px => 0.5em)
const CH = 32;   // cellhöjd
const COLS = 40;
const PAD = 16;

$font = "$fontDir/ubuntumono-r-webfont.ttf";
$fontSize = 24; // pt vid 96 dpi = 32px

$fg = ['W' => [252,254,252], 'Y' => [252,254,1], 'C' => [0,200,238], 'G' => [0,255,0],
       'R' => [255,0,0], 'M' => [252,3,253], 'B' => [17,34,204], 'bl' => [0,0,0]];
$bg = ['bgW' => [252,254,252], 'bgY' => [252,254,1], 'bgC' => [0,200,238], 'bgG' => [0,255,0],
       'bgR' => [252,3,4], 'bgM' => [252,3,253], 'bgB' => [0,0,255], 'bgBl' => [0,0,0]];

$html = file_get_contents($htmlFile);
$i = strpos($html, '<div class="root"');
$j = strpos($html, '</div>', $i);
$rootHtml = substr($html, $i, $j - $i + 6);

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<meta charset="utf-8">' . $rootHtml);
$xp = new DOMXPath($dom);
$root = $xp->query('//div[@class="root"]')->item(0);
// Generisk vandring: både importerns format (span.line) och det gamla (rader åtskilda av \n).
$rows = [['dh' => false, 'cells' => []]];
$walk = function (DOMNode $n, array $ctx) use (&$walk, &$rows) {
    if ($n instanceof DOMText) {
        foreach (mb_str_split($n->nodeValue) as $c) {
            if ($c === "\n") { $rows[] = ['dh' => false, 'cells' => []]; continue; }
            $rows[array_key_last($rows)]['cells'][] = ['c' => $c] + $ctx;
        }
        return;
    }
    if ($n instanceof DOMElement) {
        if ($n->tagName === 'a') $ctx['link'] = true;
        $cls = preg_split('/\s+/', $n->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($cls as $cl) {
            if (str_starts_with($cl, 'bg') && $cl !== 'bgImg') $ctx['bg'] = $cl;
            elseif (in_array($cl, ['W','Y','C','G','R','M','B','bl'], true)) $ctx['fg'] = $cl;
            elseif ($cl === 'DH') $rows[array_key_last($rows)]['dh'] = true;
        }
        if (preg_match('~chars/(\d+)\.gif~', $n->getAttribute('style'), $m)) $ctx['img'] = $m[1];
        foreach ($n->childNodes as $ch) $walk($ch, $ctx);
    }
};
$walk($root, ['fg' => 'W', 'bg' => 'bgBl', 'img' => null, 'link' => false]);
$rows = array_slice($rows, 0, 25);
$W = COLS * CW + 2 * PAD;
$footerH = 90;
$H = count($rows) * CH + 2 * PAD + $footerH;
$im = imagecreatetruecolor($W, $H);
imagefill($im, 0, 0, imagecolorallocate($im, 0, 0, 0));
$col = fn($img, $rgb) => imagecolorallocate($img, ...$rgb);

// baslinje
$bb = imagettfbbox($fontSize, 0, $font, 'Hgjåp');
$baseline = (int) round((CH - ($bb[1] - $bb[7])) / 2 - $bb[7]);

$gifCache = [];
$drawRow = function ($dst, array $row, int $y0) use (&$gifCache, $col, $fg, $bg, $font, $fontSize, $baseline, $charsDir) {
    foreach ($row['cells'] as $x => $cell) {
        if ($x >= COLS) break;
        $px = PAD + $x * CW;
        imagefilledrectangle($dst, $px, $y0, $px + CW - 1, $y0 + CH - 1, $col($dst, $bg[$cell['bg']]));
        if ($cell['img']) {
            $g = $gifCache[$cell['img']] ??= @imagecreatefromgif("$charsDir/{$cell['img']}.gif");
            if ($g) imagecopyresized($dst, $g, $px, $y0, 0, 0, CW, CH, imagesx($g), imagesy($g));
            continue;
        }
        $c = $col($dst, $fg[$cell['fg']]);
        if (trim($cell['c']) !== '') imagettftext($dst, $fontSize, 0, $px, $y0 + $baseline, $c, $font, $cell['c']);
        if ($cell['link']) imagefilledrectangle($dst, $px, $y0 + $baseline + 3, $px + CW - 1, $y0 + $baseline + 4, $c);
    }
};

// vanliga rader först, dubbelhöga sist (de täcker raden under, som scaleY(2) i CSS)
foreach ($rows as $r => $row) if (!$row['dh']) $drawRow($im, $row, PAD + $r * CH);
foreach ($rows as $r => $row) {
    if (!$row['dh']) continue;
    $tmp = imagecreatetruecolor($W, CH);
    $drawRow($tmp, $row, 0);
    imagecopyresampled($im, $tmp, 0, PAD + $r * CH, 0, 0, $W, 2 * CH, $W, CH);
}

// sidfot
$fy = $H - $footerH;
imagefilledrectangle($im, 0, $fy, $W, $H, $col($im, [0, 41, 72]));
imageline($im, 0, $fy, $W, $fy, $col($im, [60, 80, 100]));
$lx = (int) ($W / 2 - 90); $ly = $fy + 22; $s = 46 / 350;
imagefilledrectangle($im, $lx, $ly, $lx + 46, $ly + 46, $col($im, [0, 142, 255]));
foreach ([[76,75,[0,73,252]],[151,75,[87,198,235]],[151,150,[229,219,43]],[151,225,[243,166,51]],[226,75,[128,242,0]]] as [$rx,$ry,$rc])
    imagefilledrectangle($im, $lx + (int)($rx*$s), $ly + (int)($ry*$s), $lx + (int)(($rx+50)*$s), $ly + (int)(($ry+50)*$s), $col($im, $rc));
imagettftext($im, 22, 0, $lx + 62, $ly + 34, $col($im, [255,255,255]), $font, 'TextTV.nu');

$t1 = microtime(true);
// PNG: palett minskar storleken rejält
$pal = imagecreatetruecolor($W, $H); imagecopy($pal, $im, 0, 0, 0, 0, $W, $H);
imagetruecolortopalette($pal, false, 64);
imagepng($pal, $out, 9);
imagejpeg($im, preg_replace('/\.png$/', '.jpg', $out), 85);
imagepng($im, preg_replace('/\.png$/', '-tc.png', $out), 9);
fprintf(STDERR, "rows=%d size=%dx%d draw=%.1fms total=%.1fms peakmem=%.1fMB\n", count($rows), $W, $H,
    ($t1-$t0)*1000, (microtime(true)-$t0)*1000, memory_get_peak_usage(true)/1048576);
