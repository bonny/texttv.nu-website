<?php

/*
	Länkar från en levande sida till gårdagen: sidans dagsarkiv (/377/igar)
	och avdelningens dagslista (/nyheter/igar, /sport/igar). Startsidan får
	alla, och dessutom gårdagens mest lästa nyheter (yesterday_top_news()).
	Listan byggs av Sida::visa() och skrivs ut av
	pages_inner_output_current.php direkt under sidan. Todo #16.

	Byggd av komponenterna i css/tt.css (todo #18): tt-sidbredd, tt-band,
	tt-list och tt-linkrow.
*/

?>
<?php
// På startsidan står länkarna mellan sidorna, som är inline-block och står
// bredvid varandra på bred skärm. En vanlig blocknav skulle bryta raden, så
// där blir länkarna en egen ruta i raden, lika bred som en sida.
$inline = !empty($inline);
$yesterday_top = !empty($yesterday_top) ? $yesterday_top : [];
?>
<nav class="tt-sidbredd day-archive-link<?php echo $inline ? " day-archive-link--inline" : "" ?>" aria-label="Gårdagen">
	<?php if ($yesterday_prefix) { ?>
		<span class="tt-band"><?php echo html_escape($yesterday_prefix) ?></span>
	<?php } ?>
	<?php if ($yesterday_top) { ?>
		<ol class="tt-list">
			<?php foreach (array_values($yesterday_top) as $i => $item) { ?>
				<li>
					<span class="tt-list__meta" aria-hidden="true"><?php echo $i + 1 ?></span>
					<a href="<?php echo html_escape($item["permalink"]) ?>"><?php echo html_escape($item["title"]) ?></a>
				</li>
			<?php } ?>
		</ol>
	<?php } ?>
	<div class="tt-linkrow">
		<?php foreach ($yesterday_links as $link) { ?>
			<a href="<?php echo html_escape($link[0]) ?>"><?php echo html_escape($link[1]) ?> →</a>
		<?php } ?>
	</div>
</nav>
