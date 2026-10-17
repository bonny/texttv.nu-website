<?php
//17 mars: tog bort dela bara om permalink. underligt!
//if (isset($page_permalink)) {
//$page_permalink = $page->get_permalink();
/*if (isset($page_permalink)) {
		?>
		<div class="clearfix"></div>
		<div class="alert shares">
		Dela på: 
			<a href="https://twitter.com/intent/tweet?original_referer=&text=texttv.nu&url=<?php echo rawurlencode("http://texttv.nu".$page_permalink) ?>">Twitter</a>
			<!-- http://a4.twimg.com/images/favicon.ico -->
			<a href="https://www.facebook.com/sharer.php?u=<?php echo rawurlencode("http://texttv.nu".$page_permalink) ?>">Facebook</a>
			<!-- &t=<title of content> -->
		<?php
	}*/
//}
/*
printf("<!-- 
		Elapsed time: %s
		<br>Memory usage: %s 
		-->",
		$this->benchmark->elapsed_time(),
		$this->benchmark->memory_usage()
	);
*/

// Skapa permalänk, 1 eller flera sidor
$permalink = "";
if (isset($pages) && is_array($pages) && function_exists("get_permalink_from_pages")) {
	$permalink = get_permalink_from_pages($pages, $page, $pagenum);
	$permalink_headline = $pages[0]->get_page_title();
}
?>

<?php
// no footer when generating share screenshot
if (!$this->input->get("apiAppShare")) { ?>
	<footer class="site-footer">
		<div class="site-footer__inner tt-sidbredd">
			<nav class="site-footer__sidor" aria-label="Sidor">
				<h2 class="tt-band">Sidor</h2>
				<?php $this->load->view('sidlista') ?>
			</nav>

			<nav class="tt-linkrow site-footer__mer" aria-label="Mer på TextTV.nu">
				<a href="/sida/delat">Mest delat</a>
				<a href="/sida/polisen">Omnämnt av Polisen</a>
				<a href="/sida/vanliga-fragor">Vanliga frågor</a>
				<a href="/blogg">Blogg</a>
				<a href="/sida/om-texttv-nu">Om TextTV.nu</a>
				<a href="/ios">TextTV.nu för iPhone</a>
				<a href="/android">TextTV.nu för Android</a>
			</nav>

			<p class="site-footer__om">TextTV.nu är en bättre Text TV för mobil, surfplatta och dator. Sajten är ett fristående projekt och inte en officiell webbplats från SVT.</p>

			<nav class="tt-linkrow site-footer__meta" aria-label="Om webbplatsen">
				<a href="/sida/integritetspolicy/">Integritetspolicy</a>
				<a href="/sida/cookies/">Om cookies</a>
				<!-- href + return false: cookieinställningar är en action, inte navigation, men Lighthouse vill ha href på <a> (crawlable-anchors). Fragment som inte finns på sidan → ingen scroll. -->
				<a href="#cookieinstallningar" onclick="googlefc.showRevocationMessage(); return false;">Cookieinställningar</a>
			</nav>
		</div>
	</footer>

<?php } ?>

<?php

// footer when sharing
if ($this->input->get("apiAppShare")) {
?>
	<div class="ScreenshotFooter">
		<div class="ScreenshotFooter__logoWrap">
			<svg class="ScreenshotFooter__logo" viewBox="0 0 350 350" xmlns="http://www.w3.org/2000/svg">
				<g fill="none" fill-rule="evenodd">
					<rect fill="#008EFF" width="350" height="350" rx="40" />
					<path fill="#0049FC" d="M76 75h50v50H76z" />
					<path fill="#57C6EB" d="M151 75h50v50h-50z" />
					<path fill="#E5DB2B" d="M151 150h50v50h-50z" />
					<path fill="#F3A633" d="M151 225h50v50h-50z" />
					<path fill="#80F200" d="M226 75h50v50h-50z" />
				</g>
			</svg>
			<span class="ScreenshotFooter__sitename">TextTV.nu</span>
		</div>

		<a class="ScreenshotFooter__store ScreenshotFooter__store--google" href='http://play.google.com/store/apps/details?id=com.mufflify.TextTVnu&hl=sv&utm_source=global_co&utm_medium=prtnr&utm_content=Mar2515&utm_campaign=PartBadge&pcampaignid=MKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1'><img alt='undefined' src='http://play.google.com/intl/en_us/badges/images/generic/sv_badge_web_generic.png' /></a>

		<a class="ScreenshotFooter__store ScreenshotFooter__store--apple" href="http://itunes.apple.com/se/app/texttv.nu/id607998045?mt=8
"><img src="/images/Download_on_the_App_Store_Badge_SE_135x40.svg" alt="Hämta i App Store"></a>

	</div>
<?php
} // end if apiAppShare

?>
</div>
<?php // end wrap 
?>
<?php

// Show sidebar
if (!isset($disableSidebar) && !$this->input->get("apiAppShare")) {
?>
	<nav class="sidebar" id="sidomeny" aria-label="Sidor">
		<?php $this->load->view('sidlista') ?>
	</nav>
<?php
}

if ($this->input->get("skipScriptsFooter") || $this->input->get("apiAppShare")) {
	// no load script yo
} else {
?>
	<script src="/js/scripts.js?v=<?php echo @filemtime(dirname(__DIR__, 3) . '/js/scripts.js') ?>"></script>
	<script src="/js/tt-sok.js?v=<?php echo @filemtime(dirname(__DIR__, 3) . '/js/tt-sok.js') ?>"></script>
	<?php
	// Kod för annonser på sidnivå
	?>
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<script>
		(adsbygoogle = window.adsbygoogle || []).push({
			google_ad_client: "ca-pub-1689239266452655",
			enable_page_level_ads: true,
			overlays: {
				bottom: true
			}
		});
	</script>
<?php
}

?>
</body>

</html>