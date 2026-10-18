<?php

/*
	/sida/delat: de mest delade nyheterna idag och igår, eller en dag
	(?datum=2015-08-24). Datumet kontrolleras i controllers/textsida.php,
	som skickar $delat_datum (null utan datum).

	Raderna ser ut som en Text-TV-sida: sidnummer i cyan och rubriken.
	Antalet delningar visas inte, det är för få (ofta 1).
*/

if ( ! function_exists("output_shared_pages")) {

	function output_shared_pages($result, $tom_text) {

		$max = 10;
		$rader = [];
		$titlar = [];

		foreach ($result->result() as $row) {
			if (count($rader) >= $max) {
				break;
			}

			$titel = trim((string) $row->title);

			// Under fyra tecken är något fel. "Inrikes 101 Utrikes 104
			// Innehåll 700" är sidfoten på en tom sida i 190-serien.
			if (mb_strlen($titel) < 4 || preg_match('/Innehåll\s+700/u', $titel)) {
				continue;
			}

			// Samma nyhet på två sidor (134 och 142) visas en gång, med den
			// som delats mest (raderna kommer sorterade så).
			if (isset($titlar[$titel])) {
				continue;
			}
			$titlar[$titel] = true;

			$archive_page = new Texttv_page();
			$archive_page->id = $row->id;
			$archive_page->load(true);

			$rader[] = [
				"url" => $archive_page->get_permalink(),
				"num" => (int) $row->page_num,
				"titel" => $titel,
				"tid" => $row->date_added_formatted,
			];
		}

		if ( ! $rader) {
			printf('<p class="delat-tom">%s</p>', html_escape($tom_text));
			return;
		}

		echo '<ol class="delat-lista">';
		foreach ($rader as $rad) {
			printf(
				'<li><a href="%1$s" title="Sida %2$d kl %4$s">'
				. '<span class="delat-lista__num">%2$d</span>'
				. '<span class="delat-lista__titel">%3$s</span>'
				. '</a></li>',
				html_escape($rad["url"]),
				$rad["num"],
				html_escape($rad["titel"]),
				html_escape($rad["tid"])
			);
		}
		echo '</ol>';
	}

	// Välj en dag. Telefonens egen kalender, från första mätdagen till idag.
	function output_shared_pages_date_form($datum) {
		?>
		<form class="delat-valj" method="get" action="/sida/delat">
			<label for="delat-datum">Välj en annan dag</label>
			<span class="delat-valj__falt">
				<input type="date" id="delat-datum" name="datum"
					min="<?php echo DELAT_FORSTA_DAG ?>"
					max="<?php echo date("Y-m-d") ?>"
					value="<?php echo html_escape($datum ?: date("Y-m-d", strtotime("-2 days"))) ?>"
					required>
				<button type="submit">Visa</button>
			</span>
		</form>
		<?php
	}
}

$idag = date("Y-m-d");

?>
<div class="textcontent">

	<div class="most-shared-section-wrap">
	<?php if ($delat_datum) {

		$dag_unix = strtotime($delat_datum);
		$forra = date("Y-m-d", strtotime("-1 day", $dag_unix));
		$nasta = date("Y-m-d", strtotime("+1 day", $dag_unix));
		$kort = function ($ymd) {
			return ucfirst(date_sv("D j M", strtotime($ymd)));
		};

		?>
		<h1>Mest delat <?php echo date_sv("l j F Y", $dag_unix) ?></h1>

		<?php // rel=prev/next: ← och → på tangentbordet går hit (js/scripts.js). ?>
		<nav class="tt-linkrow tt-dag__nav" aria-label="Bläddra mellan dagar">
			<?php if ($delat_datum > DELAT_FORSTA_DAG) { ?>
				<a rel="prev" href="/sida/delat?datum=<?php echo $forra ?>">← <?php echo $kort($forra) ?></a>
			<?php } else { ?>
				<span></span>
			<?php } ?>
			<?php if ($delat_datum < $idag) { ?>
				<a rel="next" href="/sida/delat?datum=<?php echo $nasta ?>"><?php echo $nasta === $idag ? "Idag" : $kort($nasta) ?> →</a>
			<?php } ?>
		</nav>

		<?php
		output_shared_pages(
			get_shared_pages_for_period(strtotime("today 00:00", $dag_unix), strtotime("today 24:00", $dag_unix)),
			$delat_datum === $idag ? "Inga sidor har delats än idag." : "Inga sidor delades den här dagen."
		);

		?>
		<p><a href="/sida/delat">Mest delat idag och igår</a></p>
		<?php

		output_shared_pages_date_form($delat_datum);

	} else { ?>

		<h1>Mest delade sidorna från SVT Text TV</h1>

		<p>Nyheterna som flest har delat med Dela-knappen här på texttv.nu och i appen.</p>

		<div class="most-shared-section most-shared-section--today">
			<h2>Mest delat idag</h2>
			<?php output_shared_pages(get_shared_pages_for_period(strtotime("today"), time()), "Inga sidor har delats än idag."); ?>
		</div>

		<div class="most-shared-section most-shared-section--yesterday">
			<h2>Mest delat igår</h2>
			<?php output_shared_pages(get_shared_pages_for_period(strtotime("yesterday 00:00"), strtotime("yesterday 24:00")), "Inga sidor delades igår."); ?>
			<p class="tt-linkrow">
				<a rel="prev" href="/sida/delat?datum=<?php echo date("Y-m-d", strtotime("-2 days")) ?>">← <?php echo ucfirst(date_sv("D j M", strtotime("-2 days"))) ?></a>
			</p>
		</div>

		<?php output_shared_pages_date_form(null); ?>

	<?php } ?>
	</div>

</div>
