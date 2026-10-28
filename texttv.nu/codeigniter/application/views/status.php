<?php
/**
 * Statussidan, data från controllers/status.php. Varje kort är en egen
 * Text-TV-ruta på 40 tecken. Tidsserierna ritas som stående blockdiagram
 * (hela och halva celler) i en <pre>, med samma siffror som tabell för
 * skärmläsare och den som vill läsa exakt.
 */

$h = function ($s) {
	return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
};

$tal = function ($n) {
	return number_format((int) $n, 0, ",", " ");
};

$veckodagar = array("sö", "må", "ti", "on", "to", "fr", "lö");
$manader = array(1 => "jan", "feb", "mar", "apr", "maj", "jun", "jul", "aug", "sep", "okt", "nov", "dec");

// "ti 28", eller "ti 28 okt" med $med_manad.
$dag = function ($ymd, $med_manad = false) use ($veckodagar, $manader) {
	$ts = strtotime($ymd);
	return $veckodagar[(int) date("w", $ts)] . " " . date("j", $ts)
		. ($med_manad ? " " . $manader[(int) date("n", $ts)] : "");
};

$sedan = function ($tidpunkt) {
	if (!$tidpunkt) {
		return "inget i dag";
	}
	$sekunder = max(0, time() - strtotime($tidpunkt));
	if ($sekunder < 120) {
		$text = "$sekunder s sedan";
	} elseif ($sekunder < 7200) {
		$text = round($sekunder / 60) . " min sedan";
	} else {
		$text = round($sekunder / 3600, 1) . " h sedan";
	}
	return $text . " (" . substr($tidpunkt, 11, 5) . ")";
};

/**
 * En blockcell, $bredd tecken bred. Ritas med CSS och inte med █ ▄ ▁:
 * webbfonten saknar blocktecknen, och reservfontens tecken är bredare än
 * 1ch, så staplarna hamnade ur led. $form: hel, halv, linje eller tom.
 */
$cell = function ($form, $farg = "", $bredd = 1) {
	return '<i class="status-c status-c-' . $form . ($farg !== "" ? " status-graf-$farg" : "") . '"'
		. ($bredd !== 1 ? ' style="width: ' . $bredd . 'ch"' : "") . "></i>";
};

/**
 * Stående blockdiagram. $staplar: lista med array(varde, klass), där
 * klass är "" (vanlig), "nu" (pågår) eller "tom" (noll där det borde
 * finnas data). $bredd = tecken per stapel, $glapp = tecken mellan.
 */
$graf = function ($staplar, $hojd, $bredd = 1, $glapp = 0) use ($cell) {
	$max = 0;
	foreach ($staplar as $s) {
		$max = max($max, $s[0]);
	}
	$rader = array();
	for ($rad = $hojd; $rad >= 1; $rad--) {
		$celler = array();
		foreach ($staplar as $s) {
			// Höjd i halva celler; minst en halv för allt över noll.
			$halvor = $max > 0 ? (int) round($s[0] / $max * $hojd * 2) : 0;
			if ($s[0] > 0) {
				$halvor = max(1, $halvor);
			}
			if ($halvor >= $rad * 2) {
				$form = "hel";
			} elseif ($halvor === $rad * 2 - 1) {
				$form = "halv";
			} elseif ($rad === 1 && $s[1] === "tom") {
				$form = "linje";
			} else {
				$form = "tom";
			}
			$celler[] = $cell($form, $form === "tom" ? "" : $s[1], $bredd);
		}
		$rader[] = implode($glapp ? $cell("tom", "", $glapp) : "", $celler);
	}
	return implode("\n", $rader);
};

// Axelrad: etiketter placerade på teckenposition, array(position => text).
$axel = function ($bredd, $etiketter) use ($h) {
	$rad = str_repeat(" ", $bredd);
	foreach ($etiketter as $pos => $text) {
		$pos = min($pos, $bredd - mb_strlen($text));
		$rad = mb_substr($rad, 0, $pos) . $text . mb_substr($rad, $pos + mb_strlen($text));
	}
	return $h(rtrim($rad));
};

// Importen.
$senaste_logg = isset($import["senaste_logg"]) ? $import["senaste_logg"] : null;
$tyst_sekunder = $senaste_logg ? time() - strtotime($senaste_logg) : null;
$strax_efter_midnatt = date("H:i") < sprintf("00:%02d", $tyst_minuter);
$import_ok = $tyst_sekunder === null ? $strax_efter_midnatt : $tyst_sekunder <= $tyst_minuter * 60;

$i_dag = array("nya" => 0, "oforandrade" => 0, "ej_sanda" => 0, "fel" => 0);
foreach ($per_timme as $timme => $resultat) {
	foreach ($resultat as $nyckel => $antal) {
		$i_dag[$nyckel] += $antal;
	}
}
$nu_timme = (int) date("G");
$lasning_i_dag = end($lasning);
?>
<div class="textcontent status">

	<p class="status-topp">
		<span>STATUS</span>
		<span>TextTV.nu</span>
		<span class="status-topp-tid"><?php echo $h($dag(substr($beraknad, 0, 10), true) . " " . substr($beraknad, 11, 5)) ?></span>
	</p>

	<h1>Status för TextTV.nu</h1>

	<p class="status-lampa <?php echo $import_ok ? "status-lampa-ok" : "status-lampa-fel" ?>">
		<span aria-hidden="true"><?php echo $import_ok ? "●" : "✕" ?></span>
		<?php echo $import_ok ? "Importen går" : "Importen står still" ?>
	</p>

	<p class="status-sammanfattning">
		I dag: <?php echo $tal($i_dag["nya"]) ?> nya versioner ·
		<?php echo $tal($lasning_i_dag["visningar"]) ?> visningar ·
		<?php echo $tal($i_dag["fel"]) ?> fel
	</p>

	<div class="status-kort-lista">

		<section class="status-kort" aria-labelledby="status-importen">
			<h2 id="status-importen">Importen</h2>

			<table class="status-nyckeltal">
				<tr><th scope="row">Senaste kontroll</th><td><?php echo $h($sedan($senaste_logg)) ?></td></tr>
				<tr><th scope="row">Senaste nya version</th><td><?php echo $h($sedan(isset($import["senaste_ny"]) ? $import["senaste_ny"] : null)) ?></td></tr>
				<tr><th scope="row">Kontroller i dag</th><td><?php echo $tal(array_sum($i_dag)) ?></td></tr>
				<tr><th scope="row">Nya versioner i dag</th><td><?php echo $tal($i_dag["nya"]) ?></td></tr>
				<tr><th scope="row">Ej sända i dag</th><td><?php echo $tal($i_dag["ej_sanda"]) ?></td></tr>
				<tr><th scope="row">Fel i dag</th><td class="<?php echo $i_dag["fel"] ? "status-fel" : "" ?>"><?php echo $tal($i_dag["fel"]) ?></td></tr>
			</table>

			<p class="status-forklaring">Importen kollar hela tiden varje sida hos SVT. Ändrade sidor sparas som en ny version. Ej sända är sidor som SVT inte sänder just nu, inget fel.</p>
		</section>

		<section class="status-kort" aria-labelledby="status-timmar">
			<h2 id="status-timmar">Nya versioner per timme</h2>
			<?php
			$staplar = array();
			$max_nya = 0;
			foreach ($per_timme as $timme => $resultat) {
				$max_nya = max($max_nya, $resultat["nya"]);
				$tom = array_sum($resultat) === 0 && $timme < $nu_timme;
				$staplar[] = array($resultat["nya"], $timme === $nu_timme ? "nu" : ($tom ? "tom" : ""));
			}
			// Hela dygnet på axeln, även timmar som inte kommit än.
			for ($timme = $nu_timme + 1; $timme < 24; $timme++) {
				$staplar[] = array(0, "");
			}
			?>
			<p class="status-graf-max">max <?php echo $tal($max_nya) ?>/timme</p>
			<pre class="status-graf" aria-hidden="true"><?php echo $graf($staplar, 6) . "\n" . '<span class="status-graf-axel">' . $axel(24, array(0 => "00", 6 => "06", 12 => "12", 18 => "18", 22 => "23")) . "</span>" ?></pre>
			<p class="status-forklaring"><?php echo $cell("hel", "nu") ?> pågående timme<?php if (in_array("tom", array_column($staplar, 1), true)): ?> · <?php echo $cell("linje", "tom") ?> ingen kontroll alls<?php endif ?></p>

			<details>
				<summary>Visa som tabell</summary>
				<table class="status-tabell">
					<thead><tr><th scope="col">Kl</th><th scope="col">Nya</th><th scope="col">Samma</th><th scope="col">Ej sänd</th><th scope="col">Fel</th></tr></thead>
					<tbody>
					<?php foreach (array_reverse($per_timme, true) as $timme => $resultat): ?>
						<tr>
							<th scope="row"><?php echo sprintf("%02d", $timme) ?></th>
							<td><?php echo $tal($resultat["nya"]) ?></td>
							<td><?php echo $tal($resultat["oforandrade"]) ?></td>
							<td><?php echo $tal($resultat["ej_sanda"]) ?></td>
							<td class="<?php echo $resultat["fel"] ? "status-fel" : "" ?>"><?php echo $tal($resultat["fel"]) ?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</details>
		</section>

		<?php
		// Topplista med sidnummer, senaste rubrik, antal och stapel.
		$topplista = function ($rader, $kolumn) use ($h, $tal, $cell) {
			$max = (int) $rader[0]["antal"];
			ob_start();
			?>
			<table class="status-tabell status-topplista">
				<thead><tr><th scope="col">Sida</th><th scope="col">Rubrik</th><th scope="col"><?php echo $h($kolumn) ?></th><th scope="col"><span class="sr-only">Stapel</span></th></tr></thead>
				<tbody>
				<?php foreach ($rader as $rad):
					$titel = trim((string) $rad["titel"]);
					$kort_titel = mb_strlen($titel) > 14 ? rtrim(mb_substr($titel, 0, 13)) . "…" : $titel;
				?>
					<tr>
						<th scope="row"><a href="/<?php echo (int) $rad["page_num"] ?>"><?php echo (int) $rad["page_num"] ?></a></th>
						<td class="status-titel" title="<?php echo $h($titel) ?>"><?php echo $h($kort_titel) ?></td>
						<td><?php echo $tal($rad["antal"]) ?></td>
						<td class="status-stapel" aria-hidden="true"><?php echo $cell("hel", "", max(1, (int) round($rad["antal"] / $max * 8))) ?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
			<?php
			return ob_get_clean();
		};
		$i_dag_ymd = date("Y-m-d");
		?>

		<section class="status-kort" aria-labelledby="status-mest-andrade">
			<h2 id="status-mest-andrade">Mest ändrade sidor i dag</h2>
			<?php if (!$mest_andrade): ?>
				<p>Inga sidor har ändrats ännu i dag.</p>
			<?php else: ?>
				<?php echo $topplista($mest_andrade, "Nya") ?>
			<?php endif ?>
		</section>

		<section class="status-kort" aria-labelledby="status-mest-lasta">
			<h2 id="status-mest-lasta">Mest lästa sidor i dag</h2>
			<?php if (!$mest_lasta): ?>
				<p>Inga visningar ännu i dag.</p>
			<?php else: ?>
				<?php echo $topplista($mest_lasta, "Visn.") ?>
				<p class="status-forklaring">I appen. Visar appen flera sidor samtidigt räknas den första.</p>
			<?php endif ?>
		</section>

		<section class="status-kort" aria-labelledby="status-lasning">
			<h2 id="status-lasning">Läsning</h2>
			<p>Sidvisningar i TextTV.nu-appen för <a href="/ios">iPhone</a> och <a href="/android">Android</a>. Webben räknas inte här.</p>

			<h3>Per timme i dag</h3>
			<?php
			$staplar = array();
			$max_tim = 0;
			foreach ($lasning_per_timme as $timme => $antal) {
				$max_tim = max($max_tim, $antal);
				$staplar[] = array($antal, $timme === $nu_timme ? "nu" : ($antal === 0 ? "tom" : ""));
			}
			for ($timme = $nu_timme + 1; $timme < 24; $timme++) {
				$staplar[] = array(0, "");
			}
			?>
			<p class="status-graf-max">max <?php echo $tal($max_tim) ?>/timme</p>
			<pre class="status-graf" aria-hidden="true"><?php echo $graf($staplar, 6) . "\n" . '<span class="status-graf-axel">' . $axel(24, array(0 => "00", 6 => "06", 12 => "12", 18 => "18", 22 => "23")) . "</span>" ?></pre>
			<p class="status-forklaring"><?php echo $cell("hel", "nu") ?> pågående timme</p>

			<details>
				<summary>Visa som tabell</summary>
				<table class="status-tabell">
					<thead><tr><th scope="col">Kl</th><th scope="col">Visningar</th></tr></thead>
					<tbody>
					<?php foreach (array_reverse($lasning_per_timme, true) as $timme => $antal): ?>
						<tr><th scope="row"><?php echo sprintf("%02d", $timme) ?></th><td><?php echo $tal($antal) ?></td></tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</details>

			<h3>Per dag</h3>
			<?php
			$staplar = array();
			$max_vis = 0;
			foreach ($lasning as $ymd => $rad) {
				$max_vis = max($max_vis, $rad["visningar"]);
				$staplar[] = array($rad["visningar"], $ymd === $i_dag_ymd ? "nu" : ($rad["visningar"] === 0 ? "tom" : ""));
			}
			$etiketter = array();
			$pos = 0;
			foreach (array_keys($lasning) as $ymd) {
				$etiketter[$pos] = $veckodagar[(int) date("w", strtotime($ymd))];
				$pos += 3;
			}
			?>
			<p class="status-graf-max">max <?php echo $tal($max_vis) ?>/dag</p>
			<pre class="status-graf" aria-hidden="true"><?php echo $graf($staplar, 6, 2, 1) . "\n" . '<span class="status-graf-axel">' . $axel(count($staplar) * 3, $etiketter) . "</span>" ?></pre>
			<p class="status-forklaring"><?php echo $cell("hel", "nu") ?> i dag, hittills</p>

			<table class="status-tabell">
				<thead><tr><th scope="col">Dag</th><th scope="col">Visningar</th><th scope="col">Delat</th></tr></thead>
				<tbody>
				<?php foreach (array_reverse($lasning, true) as $ymd => $rad): ?>
					<tr><th scope="row"><?php echo $h($dag($ymd)) ?></th><td><?php echo $tal($rad["visningar"]) ?></td><td><?php echo $tal($rad["delat"]) ?></td></tr>
				<?php endforeach ?>
				</tbody>
			</table>
			<p class="status-forklaring">Delat = delningar, kopierade länkar och texter och sidor öppnade i webbläsaren.</p>
			<p>Har du inte appen? Den är gratis: <a href="/ios">hämta för iPhone</a> eller <a href="/android">för Android</a>.</p>
		</section>

		<section class="status-kort" aria-labelledby="status-arkivet">
			<h2 id="status-arkivet">Arkivet</h2>

			<table class="status-nyckeltal">
				<tr><th scope="row">Sparade versioner</th><td><?php echo $tal($arkiv["versioner"]) ?></td></tr>
				<tr><th scope="row">Olika sidnummer</th><td><?php echo $tal($arkiv["sidor"]) ?></td></tr>
				<tr><th scope="row">Sparar sedan</th><td><?php echo $arkiv["aldsta"] ? $h(substr($arkiv["aldsta"], 0, 4)) : "–" ?></td></tr>
			</table>

			<h3>Nya versioner per dag</h3>
			<?php
			$staplar = array();
			$max_dag = 0;
			foreach ($nya_per_dag as $ymd => $antal) {
				$max_dag = max($max_dag, $antal);
				$staplar[] = array($antal, $ymd === $i_dag_ymd ? "nu" : ($antal === 0 ? "tom" : ""));
			}
			$dagar = array_keys($nya_per_dag);
			?>
			<p class="status-graf-max">max <?php echo $tal($max_dag) ?>/dag</p>
			<pre class="status-graf" aria-hidden="true"><?php echo $graf($staplar, 6) . "\n" . '<span class="status-graf-axel">' . $axel(count($staplar), array(0 => $dag($dagar[0], true), count($staplar) - 6 => "i dag")) . "</span>" ?></pre>
			<p class="status-forklaring"><?php echo $cell("hel", "nu") ?> i dag, hittills</p>

			<details>
				<summary>Visa som tabell</summary>
				<table class="status-tabell">
					<thead><tr><th scope="col">Dag</th><th scope="col">Nya</th></tr></thead>
					<tbody>
					<?php foreach (array_reverse($nya_per_dag, true) as $ymd => $antal): ?>
						<tr><th scope="row"><?php echo $h($dag($ymd, true)) ?></th><td><?php echo $tal($antal) ?></td></tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</details>

			<?php if ($arkiv_per_ar): ?>
				<h3>Sparade versioner per år</h3>
				<?php
				$max_ar = 0;
				foreach ($arkiv_per_ar as $rad) {
					$max_ar = max($max_ar, (int) $rad["antal"]);
				}
				?>
				<table class="status-tabell">
					<thead><tr><th scope="col">År</th><th scope="col">Versioner</th><th scope="col"><span class="sr-only">Stapel</span></th></tr></thead>
					<tbody>
					<?php foreach ($arkiv_per_ar as $rad): ?>
						<tr>
							<th scope="row"><?php echo (int) $rad["ar"] ?></th>
							<td><?php echo $tal($rad["antal"]) ?></td>
							<td class="status-stapel" aria-hidden="true"><?php echo $cell("hel", (int) $rad["ar"] === (int) date("Y") ? "nu" : "", max(1, (int) round($rad["antal"] / $max_ar * 20))) ?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
				<p class="status-forklaring">Versioner äldre än ett år gallras, utom delade sidor och sidorna 100 och 377. Därför är de äldre åren mindre.</p>
			<?php endif ?>
		</section>

	</div>

	<p class="status-fot">Svensk tid. Siffrorna räknas om varje minut och sidan laddas om av sig själv. Maskinläsbart: <a href="/status/health">/status/health</a>.</p>

	<script>setTimeout(function () { location.reload(); }, 60000);</script>
</div>
