<?php

// Do use breadcrumb if single or multiple page
if (isset($pages) && is_array($pages)) {
	?>
	<!-- TextTV.nu – innan sidans huvudtext -->
	<!-- .ad--beforeMainText är display:none by default — visas bara via CSS-syskonregeln .breadcrumbs + .ad--beforeMainText. -->
	<!-- På sidor utan breadcrumbs (t.ex. /100, /101-/105) är <ins> dolt. Då tas hela blocket bort: ett obearbetat <ins> -->
	<!-- i DOM:en fångar nästa adsbygoogle.push(), så blocket "innan fler nyheter" fylldes aldrig (granskning 2026-10-18). -->
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
			} else if (ins) {
				ins.parentNode.parentNode.removeChild(ins.parentNode);
			}
		})();
		</script>
	</div>
	<?php
}
