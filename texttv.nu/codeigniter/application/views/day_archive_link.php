<?php

/*
	Länkar från en levande sida till gårdagen: sidans dagsarkiv (/377/igar)
	och avdelningens dagslista (/nyheter/igar, /sport/igar). Startsidan får
	alla, och dessutom gårdagens mest lästa nyheter (yesterday_top_news()).
	Listan byggs av Sida::visa() och skrivs ut av
	pages_inner_output_current.php direkt under sidan. Todo #16.
*/

?>
<?php
// På startsidan står länkarna mellan sidorna, som är inline-block och står
// bredvid varandra på bred skärm. En vanlig blocknav skulle bryta raden, så
// där blir länkarna en egen ruta i raden, lika bred som en sida.
$inline = !empty($inline);
$yesterday_top = !empty($yesterday_top) ? $yesterday_top : [];
?>
<nav class="day-archive-link<?php echo $inline ? " day-archive-link--inline" : "" ?>" aria-label="Gårdagen">
	<?php if ($yesterday_prefix) { ?>
		<span class="day-archive-link__label"><?php echo html_escape($yesterday_prefix) ?></span>
	<?php } ?>
	<?php if ($yesterday_top) { ?>
		<ol class="day-archive-link__top">
			<?php foreach (array_values($yesterday_top) as $i => $item) { ?>
				<li>
					<span class="day-archive-link__rank" aria-hidden="true"><?php echo $i + 1 ?></span>
					<a href="<?php echo html_escape($item["permalink"]) ?>"><?php echo html_escape($item["title"]) ?></a>
				</li>
			<?php } ?>
		</ol>
	<?php } ?>
	<div class="day-archive-link__more">
		<?php foreach ($yesterday_links as $link) { ?>
			<a href="<?php echo html_escape($link[0]) ?>"><?php echo html_escape($link[1]) ?> →</a>
		<?php } ?>
	</div>
</nav>
<style>
	/* Text-TV:s formspråk: blått rubrikband, gula länkar, inga ramar. */
	.day-archive-link {
		margin: 1em auto;
		max-width: 60ch;
		text-align: left;
	}
	.day-archive-link__label {
		display: block;
		padding: .15em .5em;
		background: #00f;
		color: #fff;
	}
	/* Gårdagens mest lästa: rubriklänkar med placering i en egen kolumn
	   och hängande indrag. */
	.day-archive-link__top {
		margin: .5em 0 0;
		padding: 0;
		list-style: none;
	}
	.day-archive-link__top li {
		display: flex;
		gap: .75em;
		padding: .25em 0;
	}
	.day-archive-link__rank {
		flex: none;
		width: 1ch;
		color: #00ffff;
	}
	.day-archive-link__top a {
		color: #fff;
		text-decoration: none;
	}
	.day-archive-link__more {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		column-gap: 1.5em;
	}
	.day-archive-link__more a {
		display: inline-flex;
		align-items: center;
		min-height: 44px;
		color: #ffff00;
		text-decoration: underline;
		text-underline-offset: .2em;
	}
	.day-archive-link a:hover,
	.day-archive-link a:focus {
		color: #ffff00;
		text-decoration: underline;
		text-underline-offset: .2em;
	}
	.day-archive-link__more a:hover,
	.day-archive-link__more a:focus {
		background: #ffff00;
		color: #000;
	}
	/* Startsidan: en ruta i raden bredvid sida 100, lika bred som en sida. */
	.day-archive-link--inline {
		display: inline-block;
		vertical-align: top;
		width: 40ch;
		max-width: 100%;
		margin: 1em .5em;
	}
	.day-archive-link--inline .day-archive-link__more {
		justify-content: flex-start;
		margin-top: .25em;
	}
</style>
