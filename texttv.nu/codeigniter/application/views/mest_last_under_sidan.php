<?php

/*
	"Mest läst" under en nyhetssida och dess arkivsidor, efter annonsen. Den
	som läst klart får något att gå vidare till. Data från
	mest_last_under_sidan() i texttv_helper.php.

	Mindre än startsidans ruta: fem rader och inga flikar, så att blocket
	inte blir större än själva sidan (SEO- och AdSense-granskning
	2026-10-22). Vanliga länkar i HTML:en, ingen fetch.

	$mest_last:  ["rubrik", "items", "link", "dag"]
	$placement:  "sida" eller "arkiv", till GA4.
*/

?>
<nav class="tt-sidbredd day-archive-link mest-last-under" aria-label="Mest läst">
	<p class="tt-band"><?php echo html_escape($mest_last["rubrik"]) ?></p>
	<ol class="tt-list">
		<?php foreach ($mest_last["items"] as $i => $item) { ?>
			<li>
				<span class="tt-list__meta" aria-hidden="true"><?php echo $i + 1 ?></span>
				<a href="<?php echo html_escape($item["permalink"]) ?>"><?php echo html_escape($item["title"]) ?></a>
			</li>
		<?php } ?>
	</ol>
	<div class="tt-linkrow">
		<?php echo tt_pil_link($mest_last["link"][0], $mest_last["link"][1]) ?>
	</div>
</nav>
<script>
// Klick i listan till GA4, som startsidans ruta men med placement.
(function () {
	var ruta = document.currentScript.previousElementSibling;
	ruta.addEventListener("click", function (e) {
		var a = e.target.closest(".tt-list a");
		if (!a || typeof gtag !== "function") {
			return;
		}
		var rader = Array.prototype.slice.call(ruta.querySelectorAll(".tt-list a"));
		gtag("event", "select_content", {
			content_type: "most_read",
			tab: <?php echo json_encode($mest_last["dag"]) ?>,
			position: rader.indexOf(a) + 1,
			placement: <?php echo json_encode($placement) ?>
		});
	});
})();
</script>
