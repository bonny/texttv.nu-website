<?php
/*
	/sida/polisen: polishändelser som Text TV har skrivit om, med nyheten och
	händelsen bredvid varandra. Data från Brottsplatskartan
	(/api/eventsInMedia?media=texttv), som matchar Text TV:s nyheter mot
	Polisens händelser. Todo #21.
*/
$events = isset($events_in_media->data) && is_array($events_in_media->data) ? $events_in_media->data : [];
?>
<div class="textcontent">
	<h1>Polishändelser i Text TV</h1>

	<p>Nyheter på Text TV som handlar om en händelse Polisen har rapporterat, med länk till händelsen på kartan hos <a href="https://brottsplatskartan.se/?utm_source=texttv.nu&amp;utm_medium=polisen">Brottsplatskartan</a>. Brottsplatskartan kopplar ihop nyheterna och händelserna.</p>

	<?php if (!$events) { ?>
		<p>Händelserna kunde inte hämtas just nu. Försök igen om en stund.</p>
	<?php } ?>

	<?php foreach ($events as $one_event) {
		$karta = bpk_lank(isset($one_event->permalink) ? $one_event->permalink : "", "polisen");
		$nyheter = [];
		foreach (isset($one_event->newsArticles) ? (array) $one_event->newsArticles : [] as $article) {
			$url = isset($article->url) ? (string) $article->url : "";
			if (preg_match('#^https://texttv\.nu/#', $url) && !empty($article->title)) {
				$nyheter[] = $article;
			}
		}
		$tid = !empty($one_event->pubdate_iso8601) ? strtotime($one_event->pubdate_iso8601) : 0;
		?>
		<section class="tt-polis-handelse">
			<h2>
				<?php if ($karta) { ?><a href="<?php echo html_escape($karta) ?>"><?php } ?>
				<?php echo html_escape(trim(($one_event->title_type ?? "") . ", " . ($one_event->location_string ?? $one_event->title_location ?? ""), ", ")) ?>
				<?php if ($karta) { ?></a><?php } ?>
			</h2>
			<?php if ($tid) { ?>
				<p class="tt-polis-handelse__tid"><time datetime="<?php echo html_escape(date("c", $tid)) ?>"><?php echo html_escape(ucfirst(date_sv("l j F Y", $tid)) . " kl " . date("H:i", $tid)) ?></time></p>
			<?php } ?>
			<?php if (!empty($one_event->content_teaser)) { ?>
				<p><?php echo html_escape($one_event->content_teaser) ?></p>
			<?php } ?>
			<?php if ($nyheter) { ?>
				<p class="tt-polis-handelse__nyheter">På Text TV:
					<?php echo implode(" · ", array_map(function ($a) {
						return sprintf('<a href="%1$s">%2$s</a>', html_escape($a->url), html_escape($a->title));
					}, $nyheter)) ?>
				</p>
			<?php } ?>
		</section>
	<?php } ?>
</div>
