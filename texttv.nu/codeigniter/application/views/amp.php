<?php

/**
 * Main view for AMP version of pages
 * /100/amp
 * /100,101/amp
 * /100-102,300
 * and so on
 */

$this->load->helper('file');

$gotoNextPageNum = null;
$gotoPrevPageNum = null;

if ( "/" == current_url() ) {
	$gotoNextPageNum = 101;
	$gotoPrevPageNum = 100;	
} else if ( isset($page) ) {
	$gotoNextPageNum = $page->next_page;
	$gotoPrevPageNum = $page->prev_page;		
}

$page_title = "";
$canonical = null;

if (isset($is_archive) && $is_archive) {

	$page_title .= $pages[0]->title;
	$canonical = $page_permalink;
		
} else {
	
	// Not archive
	$page_title .= trim($pagenum);
	
	$canonical = "/$pagenum";
	
}

$datePublished = date("c", $pages[0]->date_updated_unix);

$isMultiplePages = sizeof($pages) > 1;

$allowIndex = true;
if ($isMultiplePages || (isset($is_archive) && $is_archive)) {
	$allowIndex = false;
}

// 2023-06-07: Skickade vidare från /100/amp till /100 pga använder inte AMP aktivt längre.
// $canonical = /100-102,211 osv.
$this->output->set_header('Location: ' . $canonical);

?><!doctype html>
<html ⚡ lang="sv">
  <head>
    <meta charset="utf-8">
    <title><?php echo html_escape($page_title) ?> | SVT Text TV</title>
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <link rel="canonical" href="<?php echo html_escape( $canonical ) ?>">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16" />
	<link rel="apple-touch-icon-precomposed" href="/images/favicon-152.png">
	<link rel="manifest" href="/manifest-amp.json">
	<?php
	if (!$allowIndex) {
		?><meta name="robots" content="noindex"><?php
	}
	?>
    <script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "NewsArticle",
	"headline": "<?php echo html_escape( $page_title ) ?>",
	"datePublished": "<?php echo $datePublished ?>",
	"dateModified": "<?php echo $datePublished ?>",
	"author": {
		"@type": "Person",
		"name": "SVT Text TV"
	},
	"publisher": {
		"@type": "Organization",
		"name": "TextTV.nu",
		"logo": {
			"@type": "ImageObject",
			"url": "https://texttv.nu/images/texttv-nu-publisher-logo.png",
			"width": "600",
			"height": "600"
		}
	},
	"image": {
		"@type": "ImageObject",
		"url": "https://texttv.nu/images/texttv-nu-publisher-logo.png",
		"width": "600",
		"height": "600"
	}
}
    </script>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <style amp-custom>
        <?php
	    echo read_file("../css/amp-styles.css");
	    echo read_file("../css/texttvpage.css");
	    ?>
	    
    </style>
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>
    <script async custom-element="amp-ad" src="https://cdn.ampproject.org/v0/amp-ad-0.1.js"></script>
	<!-- <script async custom-element="amp-sticky-ad" src="https://cdn.ampproject.org/v0/amp-sticky-ad-1.0.js"></script> -->
    <!-- <script async custom-element="amp-carousel" src="https://cdn.ampproject.org/v0/amp-carousel-0.1.js"></script> -->
	<script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>
	<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>
	<script async custom-element="amp-live-list" src="https://cdn.ampproject.org/v0/amp-live-list-0.1.js"></script>
	<script async custom-template="amp-mustache" src="https://cdn.ampproject.org/v0/amp-mustache-0.2.js"></script>
	<script async custom-element="amp-list" src="https://cdn.ampproject.org/v0/amp-list-0.1.js"></script>
	<script async custom-element="amp-auto-ads" src="https://cdn.ampproject.org/v0/amp-auto-ads-0.1.js"></script>
	<link href='https://fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet'>
  </head>

  <body>

  	<amp-auto-ads type="adsense" data-ad-client="ca-pub-1689239266452655"></amp-auto-ads>

    <div class="main-body amp-border-box">
        
		<?php
		$this->view('amp-header', [
			"gotoPrevPageNum" => $gotoPrevPageNum,
			"gotoNextPageNum" => $gotoNextPageNum
		]);
		?>

        <amp-ad
            width="320"
            height="50"
            type="adsense"
            data-ad-client="ca-pub-1689239266452655"
            data-ad-slot="2317226401"
            layout="responsive"
            >
        </amp-ad>


        <main role="main">

            <article>
	            
	            <amp-live-list id="live-list-page" data-poll-interval="15000" data-max-items-per-page="5">
					<button update on="tap:live-list-page.update">You have updates!</button>
					<div items>
						<?php
							if ($this->input->get("test")) {
								print_r($pages);
							}
		
						$out = "";
						$maxDateUpdatedUnix = 0;
						$pagesOut = "";
						$rangeStr = "";
				
						foreach ($pages as $one_page_obj) {
							$rangeStr .= "-" . $one_page_obj->num;

							$pagesOut .= $one_page_obj->get_output();
							$maxDateUpdatedUnix = max($maxDateUpdatedUnix, $one_page_obj->date_updated_unix);
						}
						
						// $maxDateUpdatedUnix = $maxDateUpdatedUnix + 103;

						$out .= "<div id='live-list-page-texttvpage{$rangeStr}' data-sort-time='{$maxDateUpdatedUnix}' data-update-time='{$maxDateUpdatedUnix}'>";						
						$out .= "<ul class='pages-list'>";

						// Lägg till tidpunkt för senaste uppdaterade sidan inom range
						$isLastUpdatedToday = date("Y-m-d") == date("Y-m-d", $maxDateUpdatedUnix);
						if ($isLastUpdatedToday) {
							$strDate = date("H:i", $maxDateUpdatedUnix);
						} else {
							$strDate = date("Y-m-d H:i", $maxDateUpdatedUnix);
						}
						$out .= sprintf(
							'
								<li class="PageLastUpdated">
									<p>Uppdaterad %1$s</p>
								</li>
							', 
							$strDate
						);

						$out .= $pagesOut;	

						$out .= "</ul>";
						$out .= "</div>";
						

						// Links are like this
						// <a href="/104">
						// but we need them to be like
						// <a href="/104/amp">
						// funkar:      <a href="/111">111</a>
						// funkar inte: <a href="/109">109</a>
						$out = preg_replace('/<a href="\/([0-9\-,]+)">/', '<a href="/$1/amp">', $out);
						
						// for example "nästa sida" uses ' instead of " 
						$out = preg_replace('/<a href=\'\/([0-9\-,]+)\'>/', '<a href="/$1/amp">', $out);
												
						echo $out;
		
						?>
					</div>
				</amp-live-list>

            </article>

        </main>
        
		<?php
		// Kan inte använda denna pga fixed bottom app-like nav
		/*
		<amp-sticky-ad layout="nodisplay">
		  <amp-ad width="320"
		        height="100"
		        type="adsense"
		        data-ad-client="ca-pub-1689239266452655"
		        data-ad-slot="3109617609"
		        >
		  </amp-ad>
		</amp-sticky-ad>
		*/
		?>

		<?php

		/*
		$carousel_contents = "";
		$latest_pages = get_latest_updated_pages(104, 110, 10);

		foreach ( $latest_pages->result() as $row ) {
			
			$page = new texttv_page($row->page_num);
			$carousel_contents .= "<div><ul class='Carousel__LatestPages'>";
			$carousel_contents .= $page->get_output();
			$carousel_contents .= "</ul></div>";
			
		}
		*/

		?>
		<!--
		<h2>10 senaste nyheterna</div>

		<amp-carousel 
			width=330 
			height=420
			type=slides
			layout=responsive
			controls
			>
		  <?php // echo $carousel_contents ?>
		</amp-carousel>
		-->
		
		<div class="AmpList-RecentUpdated-wrap">
		
			<div class="AmpList-RecentUpdated AmpList-RecentUpdated--news">
				<h2>Senaste nyheterna</h2>
				<amp-list 
					src="https://texttv.nu/api/last_updated/news?count=10"
				    width=300 
				    height=60 
				    layout=responsive
				    items=pages
				    template=tmpl_last_updated
				    >
				</amp-list>
			</div>
			
			<div class="AmpList-RecentUpdated AmpList-RecentUpdated--sport">
				<h2>Senaste sportnyheterna</h2>
				<amp-list 
					src="https://texttv.nu/api/last_updated/sport?count=10"
				    width=300 
				    height=60 
				    layout=responsive
				    items=pages
				    template=tmpl_last_updated
				    >
				</amp-list>
			</div>

		</div>

	  <template type="amp-mustache" id="tmpl_last_updated">
	    <div class="AmpList-RecentUpdated-item"> 
		  	<a href="{{permalink}}/amp" class="AmpList-RecentUpdated-itemLink">
		      <span class="AmpList-RecentUpdated-itemPageNum">{{date_added_time}}</span>
		      <span class="AmpList-RecentUpdated-itemPageTitle">{{title}}</span>
		      <!-- - {{date_added}} -->
		       <!-- <amp-img class="AmpList-RecentUpdated-image" width=70 height=93 src="https://texttv.nu/api/screenshot/{{id}}.jpg"> -->
		    </a>
	    </div>
	  </template>

    </div><!-- // main body -->
    
	<?php
	$this->view('amp-bottom-nav', [
		"gotoPrevPageNum" => $gotoPrevPageNum,
		"gotoNextPageNum" => $gotoNextPageNum
	]);
	
	$this->view('amp-sidebar', [
	]);	
	?>


	<amp-analytics type="googleanalytics" id="analytics1">
		<script type="application/json">
			{
			  "vars": {
			    "account": "UA-181460-35"
			  },
			  "triggers": {
			    "trackPageview": {
			      "on": "visible",
			      "request": "pageview"
			    }
			  }
			}
		</script>
	</amp-analytics>

</html>
<!--

# Todo

- https://github.com/ampproject/amphtml/blob/master/examples/metadata-examples/article-json-ld.amp.html

- <amp-img src="welcome.jpg" alt="Welcome" height="400" width="800"></amp-img>



-->
