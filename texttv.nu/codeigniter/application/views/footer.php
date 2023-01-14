<?php 
// no footer when generating share screenshot
if ( ! $this->input->get("apiAppShare") ) { ?>
<footer class="site-footer">
	<p>TextTV.nu är en bättre text tv för din mobil, surfplatta och dator.</p>
	<p>Sajten är ett fristående projekt och är inte en officiell webbplats från SVT.</p>
</footer>
<?php } ?>

<?php

// footer when sharing
if ( $this->input->get("apiAppShare") ) {

	?>
	
	<style>
		body, html {
			margin: 0;
			padding: 0;
		}
		#pages {
			margin: 0;
		}
		.pageshare {
			display: none;
		}
		.ScreenshotFooter {
			/* 			background-color: rgb(0, 102, 181); */
			/* 			background: linear-gradient( transparent, rgb(0, 102, 181) ); */
			border-top: 2px solid rgba(255, 255, 255, 0.2);
			background-color: rgba(0, 102, 181, 0.4);
			text-align: center;
			/* 			text-align-last: justify; */
			padding: 20px;	
			color: #fff;
			margin-top: 40px;
			margin-bottom: 0;
		}
		.ScreenshotFooter__logoWrap,
		.ScreenshotFooter__store {
			display: inline-block;
			vertical-align: middle;
		}
		.ScreenshotFooter__logoWrap {
			white-space: nowrap;
			margin-right: 25px;
		}
		.ScreenshotFooter__logo {
			width: 45px;
			height: 45px;
			vertical-align: middle;
			display: inline-block;
		}
		.appShare body,
		.appShare pre, 
		.appShare span {
			font-family: 'Ubuntu Mono',Courier,monospace;	
			font-size: 32px;
			letter-spacing: -1px;
		}
		.ScreenshotFooter__sitename {
			font-family: sans-serif;
			vertical-align: middle;
			display: inline-block;
			xfont-size: 28px !important;
		}

		.ScreenshotFooter__store {
			display: none;
		}
		.ScreenshotFooter__store--google img {
			width: auto;
			height: 60px;
			display: block;
		}
		.ScreenshotFooter__store--apple img {
			width: auto;
			height: 39px;
			display: block;
		}		
	</style>
	<div class="ScreenshotFooter">
 		<div class="ScreenshotFooter__logoWrap">
			<svg class="ScreenshotFooter__logo" viewBox="0 0 350 350" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><rect fill="#008EFF" width="350" height="350" rx="40"/><path fill="#0049FC" d="M76 75h50v50H76z"/><path fill="#57C6EB" d="M151 75h50v50h-50z"/><path fill="#E5DB2B" d="M151 150h50v50h-50z"/><path fill="#F3A633" d="M151 225h50v50h-50z"/><path fill="#80F200" d="M226 75h50v50h-50z"/></g></svg>
			<span class="ScreenshotFooter__sitename">TextTV.nu</span>
		</div>
		
		<a class="ScreenshotFooter__store ScreenshotFooter__store--google" href='http://play.google.com/store/apps/details?id=com.mufflify.TextTVnu&hl=sv&utm_source=global_co&utm_medium=prtnr&utm_content=Mar2515&utm_campaign=PartBadge&pcampaignid=MKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1'><img alt='undefined' src='http://play.google.com/intl/en_us/badges/images/generic/sv_badge_web_generic.png'/></a>
		
		<a class="ScreenshotFooter__store ScreenshotFooter__store--apple" href="http://itunes.apple.com/se/app/texttv.nu/id607998045?mt=8
"><img src="/images/Download_on_the_App_Store_Badge_SE_135x40.svg" alt="Hämta i App Store"></a>
		
	</div>

	<?php

} // end if apiAppShare

?>

</div><?php // end wrap ?>
<?php

// Show sidebar
if ( ! isset( $disableSidebar ) && ! $this->input->get("apiAppShare") ) {
	?>
	<div class="sidebar">
		<ul>
			<li><a href="/">Hem <span class="num">100, 300</span></a></li>
			<li><a href="/101-102,103-105">Nyheter <span class="num">101-105</span></a></li>
			<li><a href="/101-103">Inrikes nyheter <span class="num">101-103</span></a></li>
			<li><a href="/104-105">Utrikes nyheter <span class="num">104-105</span></a></li>
			<li><a href="/300-302">Sport <span class="num">300-302</span></a></li>
			<li><a href="/330">Resultatbörsen <span class="num">330</span></a></li>
			<li><a href="/376">Målservice <span class="num">376</span></a></li>
			<li><a href="/377">Målservice, resultat <span class="num">377</span></a></li>
			<li><a href="/376-395">Målservice &amp; resultat <span class="num">376-395</span></a></li>
			<li><a href="/600,650-656">TV-tablåer <span class="num">600, 650-656</span></a></li>			
			<li><a href="/200-202">Ekonomi <span class="num">200-202</span></a></li>
			<li><a href="/700">Innehåll <span class="num">700</span></a></li>
			<?php
			/*
			<!--
				<li class="leifby">
					<a href="/376,351,327,330,551?utm_source=texttvnu&amp;utm_medium=nav&amp;utm_campaign=leifby&amp;utm_term=favoriter_rubrik&amp;utm_nooverride=1" title="Visa alla leifbys favoriter på en och samma sida">Leifbys text-tv-favoriter</a>
					<ul>
						<li><a href="/376?utm_source=texttvnu&amp;utm_medium=nav&amp;utm_campaign=leifby&amp;utm_term=favorit_1&amp;utm_nooverride=1">1) Målservice <span class="num">376</span></a></li>
						<li><a href="/351?utm_source=texttvnu&amp;utm_medium=nav&amp;utm_campaign=leifby&amp;utm_term=favorit_2&amp;utm_nooverride=1">2) Skytteligor (fotboll) <span class="num">351</span></a></li>
						<li><a href="/327?utm_source=texttvnu&amp;utm_medium=nav&amp;utm_campaign=leifby&amp;utm_term=favorit_3&amp;utm_nooverride=1">3) Tipset i sista stund <span class="num">327</span></a></li>
						<li><a href="/330?utm_source=texttvnu&amp;utm_medium=nav&amp;utm_campaign=leifby&amp;utm_term=favorit_4&amp;utm_nooverride=1">4) Resultat/Tabellbörsen <span class="num">330</span></a></li>
						<li><a href="/551?utm_source=texttvnu&amp;utm_medium=nav&amp;utm_campaign=leifby&amp;utm_term=favorit_5&amp;utm_nooverride=1">5) Tipset <span class="num">551</span></a></li>
					</ul>
				</li>
			-->
			*/
			?>
			
			<li>
				<a href="/sida/delat">Mest delat</a>
			</li>

			<li>
				<a href="/sida/polisen">Omnämnt av Polisen</a>
			</li>
			
			
			<?php
			/*
			<li>
				<form method="get" action="/sok" class="search-sidebar">
					<input type="search" name="q" value="<?php echo html_escape( $this->input->get("q") ); ?>">
					<input type="submit" value="Sök">
				</form>
			</li>
			*/
			?>
			
			<li class="sidebar-pages">
				<a href="/blogg"><span class="icon icon-file"></span>Blogg</a>
				<a href="/sida/vi-rekommenderar/"><span class="icon icon-file"></span>Länkar</a>
				<a href="/sida/om-texttv-nu"><span class="icon icon-file"></span>Om TextTV.nu</a>
			</li>
			<?php

			if ( $this->input->get("version") === "StorText" ) {
				?>
				<li>
					<a style="font-size: 1.5em; line-height: 1.2;" href="https://itunes.apple.com/se/app/texttv.nu/id607998045">Prova vår app för Iphone/Ipad</a>
				</li>
				<?php
			} else {
				?>
				<li><a href="https://texttv.nu/ios">
					Text-TV-app för Iphone
				</a></li>
				<li><a href="https://texttv.nu/android">
					Text-TV-app för Android
				</a></li>
				<?php
			}
			
			?>

			<li class="sidebar-fb-like">
				<div class="fb-like" data-href="https://www.facebook.com/texttv.nu/" data-layout="button" data-action="like" data-show-faces="false" data-share="true"></div>
			</li>
			
		</ul>
		
		<?php
		if ( $this->input->get("apiAppShare") ) {
			// no ads when generating sharing screenshot
		} else {
			/*
			// Bort med annonser 26 aug 2015, pga kändes så fånig att ha där den hade så dålig synlighet
			?>
			<div class="ad ad--sidebar">
				<p class="ad-header">Annons</p>		
				<!-- texttv responsive text only -->
				<ins class="adsbygoogle"
				     style="display:block"
				     data-ad-client="ca-pub-1689239266452655"
				     data-ad-slot="2007796805"
				     data-ad-format="auto"></ins>
				<script>
				(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
			</div>
			<?php 
			*/
		} ?>

	</div>
	<?php
}

if ( $this->input->get("skipScriptsFooter") || $this->input->get("apiAppShare") ) {
	// no load script yo
} else {
	?>
	<script src="/min/?b=js&amp;f=jquery.min.js,js.cookie.js,fastclick.js,mobile-detect.js,scripts.js&amp;15"></script>
	<?php
	// Kod för annonser på sidnivå
	?>
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<script>
	  (adsbygoogle = window.adsbygoogle || []).push({
	    google_ad_client: "ca-pub-1689239266452655",
	    enable_page_level_ads: true,
	    overlays: {bottom: true}
	  });
	</script>	
	<?php
}

#print_r($_GET);
#print_r($_POST);
#print_r($_REQUEST);

?>
</body>
</html>