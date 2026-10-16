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
// På startsidan står knapparna mellan sidorna, som är inline-block och står
// bredvid varandra på bred skärm. En vanlig blocknav skulle bryta raden, så
// där blir knapparna en egen ruta i raden, lika bred som en sida.
$inline = !empty($inline);
$yesterday_top = !empty($yesterday_top) ? $yesterday_top : [];
?>
<nav class="day-archive-link<?php echo $inline ? " day-archive-link--inline" : "" ?>" aria-label="Gårdagen">
	<?php if ($yesterday_prefix) { ?>
		<span class="day-archive-link__label"><?php echo html_escape($yesterday_prefix) ?></span>
	<?php } ?>
	<?php if ($yesterday_top) { ?>
		<ol class="day-archive-link__top" aria-label="Mest lästa nyheterna igår">
			<?php foreach ($yesterday_top as $item) { ?>
				<li>
					<span class="day-archive-link__time"><?php echo html_escape($item["time"]) ?></span>
					<a href="<?php echo html_escape($item["permalink"]) ?>"><?php echo html_escape($item["title"]) ?></a>
				</li>
			<?php } ?>
		</ol>
	<?php } ?>
	<?php foreach ($yesterday_links as $link) { ?>
		<a href="<?php echo html_escape($link[0]) ?>"><?php echo html_escape($link[1]) ?> →</a>
	<?php } ?>
</nav>
<style>
	.day-archive-link {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		align-items: center;
		gap: .5em;
		margin: 1em auto;
		max-width: 60ch;
	}
	.day-archive-link a {
		display: inline-flex;
		align-items: center;
		min-height: 44px;
		box-sizing: border-box;
		padding: .5em 1em;
		border: 1px solid #ffff00;
		color: #ffff00;
		text-decoration: none;
	}
	.day-archive-link a:hover,
	.day-archive-link a:focus {
		background: #ffff00;
		color: #000;
	}
	.day-archive-link__label {
		width: 100%;
		text-align: center;
	}
	/* Gårdagens mest lästa: rubriklänkar, inte knappar. Tid i en egen
	   kolumn med hängande indrag, som på dagslistorna. */
	.day-archive-link__top {
		width: 100%;
		margin: 0;
		padding: 0;
		list-style: none;
		text-align: left;
	}
	.day-archive-link__top li {
		display: flex;
		gap: .75em;
		padding: .35em 0;
	}
	.day-archive-link .day-archive-link__top a {
		display: inline;
		min-height: 0;
		padding: 0;
		border: 0;
		color: #fff;
		text-decoration: underline;
		text-decoration-color: rgba(255, 255, 255, .4);
		text-underline-offset: .2em;
	}
	.day-archive-link .day-archive-link__top a:hover,
	.day-archive-link .day-archive-link__top a:focus {
		background: none;
		color: #ffff00;
		text-decoration-color: #ffff00;
	}
	.day-archive-link__time {
		flex: none;
		color: #00ffff;
		font-variant-numeric: tabular-nums;
	}
	.day-archive-link--inline {
		display: inline-flex;
		flex-direction: column;
		vertical-align: top;
		width: 40ch;
		max-width: 100%;
		margin: 1em .5em;
	}
</style>
