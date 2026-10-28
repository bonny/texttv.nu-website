<?php
/**
 * Statussidan, data från controllers/status.php. Staplarna ritas med
 * blocktecken som på en riktig Text-TV-sida.
 */

$h = function ($s) {
	return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
};

// Stapel av blocktecken, högst $bredd tecken för största värdet.
$stapel = function ($varde, $max, $bredd = 20) {
	if ($max <= 0 || $varde <= 0) {
		return "";
	}
	return str_repeat("█", max(1, (int) round($varde / $max * $bredd)));
};

$sedan = function ($sekunder) {
	if ($sekunder === null) {
		return "aldrig i dag";
	}
	$sekunder = (int) $sekunder;
	if ($sekunder < 120) {
		return "$sekunder s sedan";
	}
	if ($sekunder < 7200) {
		return round($sekunder / 60) . " min sedan";
	}
	return round($sekunder / 3600, 1) . " h sedan";
};

$senaste_logg = isset($import["senaste_logg"]) ? $import["senaste_logg"] : null;
$import_ok = $senaste_logg !== null && (int) $senaste_logg <= $tyst_minuter * 60;

$typer = array(
	"VIEW" => "Visningar",
	"SHARE" => "Delningar",
	"COPYLINK" => "Kopierad länk",
	"COPYTEXT" => "Kopierad text",
	"OPENLINK" => "Öppnad i webbläsare",
);
?>
<div class="textcontent status">

	<h1>Status för TextTV.nu</h1>

	<p>Hur importen från SVT Text mår, hur arkivet växer och hur mycket som läses. Uppdateras varje minut, senast <?php echo $h(substr($beraknad, 11, 5)) ?>.</p>

	<h2>Importen</h2>

	<p class="status-lampa <?php echo $import_ok ? "status-ok" : "status-fel" ?>">
		<?php echo $import_ok ? "● Importen går" : "● Importen står still" ?>
	</p>

	<table class="status-tabell">
		<tr><th>Senaste kontroll av en sida</th><td><?php echo $h($sedan($senaste_logg)) ?></td></tr>
		<tr><th>Senaste nya version</th><td><?php echo $h($sedan(isset($import["senaste_ny"]) ? $import["senaste_ny"] : null)) ?></td></tr>
	</table>

	<h3>Kontroller per timme i dag</h3>

	<?php
	$max_timme = 0;
	foreach ($per_timme as $resultat) {
		$max_timme = max($max_timme, array_sum($resultat));
	}
	?>
	<table class="status-tabell status-staplar">
		<tr><th>Tim</th><th>Nya</th><th>Oförändrade</th><th>Ej sända</th><th>Fel</th><th></th></tr>
		<?php foreach ($per_timme as $timme => $resultat):
			$antal = function ($resultat_typ) use ($resultat) {
				return isset($resultat[$resultat_typ]) ? $resultat[$resultat_typ] : 0;
			};
			$nya = $antal("IMPORT_SUCCESS");
			$oforandrade = $antal("NOT_IMPORTED_PAGE_NOT_CHANGED");
			// Sidor som SVT inte sänder just nu. Inget fel.
			$ej_sanda = $antal("NOT_IMPORTED_REMOTE_NOT_BROADCASTED") + $antal("IMPORTED_AS_NOT_BROADCASTED");
			$fel = $antal("NOT_IMPORTED_EXCEPTION");
		?>
		<tr>
			<td><?php echo sprintf("%02d", $timme) ?></td>
			<td><?php echo $nya ?></td>
			<td><?php echo $oforandrade ?></td>
			<td><?php echo $ej_sanda ?></td>
			<td class="<?php echo $fel ? "status-fel" : "" ?>"><?php echo $fel ?></td>
			<td class="status-stapel"><span class="status-stapel-ny"><?php echo $stapel($nya, $max_timme) ?></span><?php echo $stapel($oforandrade, $max_timme) ?></td>
		</tr>
		<?php endforeach ?>
	</table>

	<h3>Mest ändrade sidor i dag</h3>

	<table class="status-tabell status-staplar">
		<?php
		$max_andrad = $mest_andrade ? (int) $mest_andrade[0]["antal"] : 0;
		foreach ($mest_andrade as $rad): ?>
		<tr>
			<td><a href="/<?php echo (int) $rad["page_num"] ?>"><?php echo (int) $rad["page_num"] ?></a></td>
			<td><?php echo (int) $rad["antal"] ?></td>
			<td class="status-stapel"><?php echo $stapel($rad["antal"], $max_andrad) ?></td>
		</tr>
		<?php endforeach ?>
	</table>

	<h2>Arkivet</h2>

	<table class="status-tabell">
		<tr><th>Sparade sidversioner</th><td><?php echo number_format((int) $arkiv["versioner"], 0, ",", " ") ?></td></tr>
		<tr><th>Olika sidnummer</th><td><?php echo (int) $arkiv["sidor"] ?></td></tr>
		<tr><th>Äldsta sparade version</th><td><?php echo $h(substr((string) $arkiv["aldsta"], 0, 10)) ?></td></tr>
	</table>

	<h3>Nya versioner per dag, 30 dagar</h3>

	<table class="status-tabell status-staplar">
		<?php
		$max_dag = 0;
		foreach ($nya_per_dag as $rad) {
			$max_dag = max($max_dag, (int) $rad["antal"]);
		}
		foreach ($nya_per_dag as $rad): ?>
		<tr>
			<td><?php echo $h(substr($rad["dag"], 5)) ?></td>
			<td><?php echo (int) $rad["antal"] ?></td>
			<td class="status-stapel"><?php echo $stapel($rad["antal"], $max_dag) ?></td>
		</tr>
		<?php endforeach ?>
	</table>

	<h2>Läsning</h2>

	<p>Från appen och webben, de senaste tio dagarna.</p>

	<?php
	$max_visningar = 0;
	$anvanda_typer = array();
	foreach ($lasning as $per_typ) {
		$max_visningar = max($max_visningar, isset($per_typ["VIEW"]) ? $per_typ["VIEW"] : 0);
		$anvanda_typer += $per_typ;
	}
	?>
	<table class="status-tabell status-staplar">
		<tr>
			<th>Dag</th>
			<?php foreach ($typer as $typ => $namn): if (!isset($anvanda_typer[$typ])) continue; ?>
			<th><?php echo $h($namn) ?></th>
			<?php endforeach ?>
			<th></th>
		</tr>
		<?php foreach ($lasning as $dag => $per_typ): ?>
		<tr>
			<td><?php echo $h(substr($dag, 5)) ?></td>
			<?php foreach ($typer as $typ => $namn): if (!isset($anvanda_typer[$typ])) continue; ?>
			<td><?php echo number_format(isset($per_typ[$typ]) ? $per_typ[$typ] : 0, 0, ",", " ") ?></td>
			<?php endforeach ?>
			<td class="status-stapel"><?php echo $stapel(isset($per_typ["VIEW"]) ? $per_typ["VIEW"] : 0, $max_visningar, 12) ?></td>
		</tr>
		<?php endforeach ?>
	</table>

	<p>Se även <a href="/sida/delat">mest delade sidor</a> och <a href="/sida/senast-uppdaterat">senast uppdaterat</a>. Maskinläsbar hälsa: <a href="/status/health">/status/health</a>.</p>

</div>
