<?php

/*
	/sida/delat: de mest delade nyheterna idag och igår, eller en dag
	(?datum=2015-08-24). Datumet kontrolleras i controllers/textsida.php,
	som skickar $delat_datum (null utan datum) och för en dag $delat_rader.

	Raderna ser ut som en Text-TV-sida: sidnummer i cyan och rubriken.
	Antalet delningar visas inte, det är för få (ofta 1).
*/

if ( ! function_exists("output_shared_pages_date_form")) {

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
		// $delat_rader från controllers/textsida.php (avgör noindex).
		output_tt_lista(
			isset($delat_rader) ? $delat_rader : [],
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
			<?php output_tt_lista(get_shared_rader(strtotime("today"), time()), "Inga sidor har delats än idag."); ?>
		</div>

		<div class="most-shared-section most-shared-section--yesterday">
			<h2>Mest delat igår</h2>
			<?php output_tt_lista(get_shared_rader(strtotime("yesterday 00:00"), strtotime("today 00:00")), "Inga sidor delades igår."); ?>
			<p class="tt-linkrow">
				<a rel="prev" href="/sida/delat?datum=<?php echo date("Y-m-d", strtotime("-2 days")) ?>">← <?php echo ucfirst(date_sv("D j M", strtotime("-2 days"))) ?></a>
			</p>
		</div>

		<?php output_shared_pages_date_form(null); ?>

	<?php } ?>

		<p class="tt-linkrow"><a href="/sida/senast-uppdaterat">Senast uppdaterat</a></p>
	</div>

</div>
