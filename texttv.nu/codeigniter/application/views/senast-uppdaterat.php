<?php

/*
	/sida/senast-uppdaterat: nyhets- och sportsidorna som ändrats senast, som
	"Senast uppdaterat" i appen. Samma urval som /api/last_updated (senaste
	åtta timmarna, utan nyhetsrullen och resultatsidorna). Raderna ser ut som
	på /sida/delat, med tiden först.
*/

if ( ! function_exists("output_latest_updated_pages")) {

	function output_latest_updated_pages($result, $tom_text) {

		$max = 25;
		$rader = [];

		// Senaste först. En sida som ändrats flera gånger visas en gång.
		foreach ($result->result() as $row) {
			if (count($rader) >= $max) {
				break;
			}
			if (isset($rader[$row->page_num])) {
				continue;
			}
			$rader[$row->page_num] = $row;
		}

		if ( ! $rader) {
			printf('<p class="delat-tom">%s</p>', html_escape($tom_text));
			return;
		}

		echo '<ol class="delat-lista delat-lista--tid">';
		foreach ($rader as $row) {
			printf(
				'<li><a href="/%1$d">'
				. '<span class="delat-lista__tid">%2$s</span>'
				. '<span class="delat-lista__num">%1$d</span>'
				. '<span class="delat-lista__titel">%3$s</span>'
				. '</a></li>',
				(int) $row->page_num,
				html_escape($row->date_added_formatted),
				html_escape(trim((string) $row->title))
			);
		}
		echo '</ol>';
	}
}

?>
<div class="textcontent">

	<div class="most-shared-section-wrap">

		<h1>Senast uppdaterat på SVT Text TV</h1>

		<p>Nyhets- och sportsidorna som ändrats de senaste åtta timmarna, senaste först.</p>

		<div class="most-shared-section">
			<h2>Nyheter</h2>
			<?php output_latest_updated_pages(get_latest_updated_pages(100, 200, 50), "Inga nyhetssidor har ändrats de senaste åtta timmarna."); ?>
		</div>

		<div class="most-shared-section">
			<h2>Sport</h2>
			<?php output_latest_updated_pages(get_latest_updated_pages(300, 329, 50), "Inga sportsidor har ändrats de senaste åtta timmarna."); ?>
		</div>

		<p class="tt-linkrow"><a href="/sida/delat">Mest delat</a></p>

	</div>

</div>
