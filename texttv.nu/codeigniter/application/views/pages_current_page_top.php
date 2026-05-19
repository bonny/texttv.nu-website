<?php

// Do use breadcrumb if single or multiple page
if (isset($pages) && is_array($pages)) {
	?>
	<!-- TextTV.nu – innan sidans huvudtext -->
	<!-- .ad--beforeMainText är display:none by default — visas bara via CSS-syskonregeln .breadcrumbs + .ad--beforeMainText. -->
	<!-- På sidor utan breadcrumbs (t.ex. /100, /101-/105) är <ins> dolt → availableWidth=0 → adsbygoogle.push() kraschar. -->
	<!-- Kolla layout innan push så vi slipper konsol-felet utan att förändra ad-beteendet på sidor som faktiskt visar annonsen. -->
	<!-- TODO: Lighthouse "errors-in-console" kvarstår delvis efter denna fix (verifierat 2026-05-19). -->
	<!-- Roten är förmodligen enable_page_level_ads i footer.php eller andra ad-block (pages_inner_output_current.php m.fl.). -->
	<!-- Återuppta om Best Practices-score blir viktigt. -->
	<div class="ad ad--beforeMainText">
		<p class="ad-header">Annons</p>
		<ins class="adsbygoogle"
		     style="display:block"
		     data-ad-client="ca-pub-1689239266452655"
		     data-ad-slot="5061315605"
		     data-ad-format="auto"></ins>
		<script>
		(function () {
			var ins = document.currentScript.previousElementSibling;
			if (ins && ins.offsetWidth > 0) {
				(adsbygoogle = window.adsbygoogle || []).push({});
			}
		})();
		</script>
	</div>
	<?php
}
