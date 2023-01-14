<?php

$arr_pages = $pages;
$text = "";
	
// Start format för det inre innehållet som har med valda sidor att göra
if (sizeof($arr_pages) == 1) {

	// Om en sida

	if ( ! $this->input->get("apiAppShare") ) {

		$text .= sprintf(
			'
			<div class="introtext alert">
				<p class="introtext__sender">
					<a href="%3$s">SVT Text TV %2$d</a>
					<br>%1$s
				</p>
				<!-- <p class="AppShareOnly sharedFrom">
					Delad från <em>www.TextTV.nu</em>
				</p> -->
			</div>
			',
			strftime("%a %e %b %Y, %H:%M", $page->date_updated_unix),
			(int) $page->num,
			site_url( $page->num )
		);

	}

} else {

	// Om flera sidor
	$text .= sprintf(
		'
		<div class="introtext alert">
			<p class="AppShareOnly">Delad från TextTV.NU</p>
			<p>TextTV sidorna %2$s<br>%1$s
			<p class="AppShareOnly sharedFrom">Delad från TextTV.NU
			<br><em>Mobilanpassad Text TV med smarta funktioner</em></p>
		</div>
		',
		// Fri 13 Jan 2023, 21:35
		date('D j M Y, H:i', $arr_pages[0]->date_updated_unix),
		$pagenum
	);

}

$this->output->append_output($text);
$this->output->append_output("<div id='pages'>");

$banner_output = <<<EOT
	<!-- TextTV - arkivsida -->
	<p>Annons:</p>
	<ins class="adsbygoogle"
	     xxstyle="display:inline-block;width:320px;height:100px"
	     style="display:inline-block;width:100%;height:100px"
	     data-ad-client="ca-pub-1689239266452655"
	     data-ad-slot="8439735600"></ins>
	<script>
	(adsbygoogle = window.adsbygoogle || []).push({});
	</script>
EOT;

if ( $this->input->get("apiAppShare") ) {
	// no ad on screenshots
} else {
	$this->output->append_output( $banner_output );
}

$this->output->append_output("<ul>");
foreach ($arr_pages as $one_page) {
	$this->output->append_output($one_page->get_output());
}
$this->output->append_output("</ul>");


	if ( ! $this->input->get("apiAppShare") ) {
		
		// Dela-knappar under arkivsida
		$this->output->append_output('
	<script>
  window.fbAsyncInit = function() {
    FB.init({
      xappId      : "your-app-id",
      xfbml      : true,
      version    : "v2.5"
    });
  };
  
  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, "script", "facebook-jssdk"));
   </script>
   
	   <div class="archive-share-buttons">
   
	   		<div class="archive-share-button archive-share-button-fb">
			    <div class="fb-like" 
			        data-layout="button" 
			        data-action="like" 
			        colorscheme="dark"
			        data-show-faces="true">
			    </div>
			</div>

	   		<div class="archive-share-button archive-share-button-fb archive-share-button-fb--send">
				<div class="fb-send" 
					data-layout="button_count">
				</div>
			</div>
	
			<div class="archive-share-button archive-share-button-twitter">
				<a href="https://twitter.com/share" class="twitter-share-button" data-via="texttv_nu" data-size="large" data-count="none" data-hashtags="texttv">Tweet</a>
			</div>
		</div>

		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document, "script", "twitter-wjs");</script>
		    
');

		// Info-text längst ner under arkivsida
		$this->output->append_output( sprintf(
			'
			<style>
				.intro-archive-info {
					display: block;
					margin: 20px auto;
					max-width: 20em;
					clear: both;
				}
				.archive-share-button {
					vertical-align: middle;
				}
				.archive-share-button {
					display: inline-block;
				}
				.archive-share-button-fb {
				    transform: scale(1.5);
				    position: relative;
				    top: -3px;
				    margin-right: 20px;
			    }
			    
			    /* slide over on ipad
			     * iPad Air or iPad Mini 
				 * (device-width: 768px) and (width: 320px)
				 * iPad Pro
				 * (device-width: 1024px) and (width: 320px)
				 */
				 @media (device-width: 768px) and (width: 320px) {
					 body, pre {
						 xfont-size: 14px;
					 }
				 }
				 @media (device-width: 1024px) and (width: 320px) {
					 body, pre {
						 xfont-size: 14px;
					 }
				 }
			</style>
			<div class="intro-archive-info">
				Du tittar på en arkiverad version av <a href="%2$s">SVT Text TV %1$d</a>.
			</div>
			',
			(int) $page->num,
			site_url( $page->num )
		) );

	}


$this->output->append_output("</div>");

