<?php

// Do use breadcrumb if single or multiple page
if (isset($pages) && is_array($pages) && !empty($has_breadcrumbs)) {
	?>
	<!-- TextTV.nu – innan sidans huvudtext -->
	<!-- .ad--beforeMainText visas bara efter brödsmulorna (CSS .breadcrumbs + .ad--beforeMainText). -->
	<!-- Sida::visa() skriver bara ut blocket när brödsmulor finns: ett dolt <ins> fångade nästa -->
	<!-- adsbygoogle.push(), så blocket "innan fler nyheter" fylldes aldrig (granskning 2026-10-18). -->
	<!-- Skriptet nedan är en extra säkring om blocket ändå är dolt. -->
	<!-- På mobil tas data-ad-format bort, så att AdSense fyller rutans fasta höjd (280 px i -->
	<!-- styles.css) i stället för en kvadrat i sidans bredd. Då syns sidans första rader (todo #22). -->
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
			if (ins && window.matchMedia('(max-width: 699px)').matches) {
				ins.removeAttribute('data-ad-format');
			}
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
