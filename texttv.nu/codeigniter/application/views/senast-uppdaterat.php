<?php

/*
	/sida/senast-uppdaterat: nyhets- och sportsidorna som ändrats de senaste
	åtta timmarna, som "Senast uppdaterat" i appen. Samma sidor som
	/api/last_updated, men en rad per sida (get_latest_updated_page_per_num i
	texttv_helper.php). Raderna ser ut som på /sida/delat, med tiden först,
	och länkar till arkivversionen med just den rubriken.
*/

if ( ! function_exists("output_latest_updated_pages")) {

	// Åtta timmar kan gå över midnatt. Rader från igår får en egen lista
	// under "Igår", så att 23:10 inte ser ut att vara senare än 01:50.
	function output_latest_updated_pages($result, $tom_text) {
		$idag = date("Y-m-d");
		$dagar = ["idag" => [], "tidigare" => []];
		foreach (tt_lista_rader($result->result()) as $rad) {
			$dagar[$rad["rad"]->date_added_ymd === $idag ? "idag" : "tidigare"][] = $rad;
		}

		if ($dagar["idag"] || ! $dagar["tidigare"]) {
			output_tt_lista($dagar["idag"], $tom_text, true);
		}
		if ($dagar["tidigare"]) {
			echo '<p class="delat-dag">Igår</p>';
			output_tt_lista($dagar["tidigare"], $tom_text, true);
		}
	}
}

?>
<div class="textcontent">

	<div class="most-shared-section-wrap">

		<h1>Senast uppdaterat på SVT Text TV</h1>

		<p>Nyhets- och sportsidorna som ändrats de senaste åtta timmarna, senaste först.</p>

		<div class="most-shared-section">
			<h2>Nyheter</h2>
			<?php output_latest_updated_pages(get_latest_updated_page_per_num(100, 200), "Inga nyhetssidor har ändrats de senaste åtta timmarna."); ?>
		</div>

		<div class="most-shared-section">
			<h2>Sport</h2>
			<?php output_latest_updated_pages(get_latest_updated_page_per_num(300, 329), "Inga sportsidor har ändrats de senaste åtta timmarna."); ?>
		</div>

		<p class="tt-linkrow"><a href="/sida/delat">Mest delat</a></p>

	</div>

</div>
