<?php

/**
 * Header for all pages
 * Incl. blogg and about-page
 */

// don't let api.textt.nu get indexed by google
if ("api.texttv.nu" == $this->input->server("HTTP_HOST")) {

	// some calls are ok
	if ($this->input->get("apiAppShare")) {
		// ok call
	} else {
		redirect('https://texttv.nu/');
		exit;
	}
}

// 16 jan 2015: redirect HTTP » HTTPS
if ("texttv.nu" == $this->input->server("HTTP_HOST") && "http" == $this->input->server("REQUEST_SCHEME")) {
	$redirect_to = "https://texttv.nu" . $this->input->server("REQUEST_URI");
	redirect($redirect_to, "location", 301);
	exit;
}

// If the go to page-form is submitted before JS is loaded then we need to catch that
// URL will be like http://texttv.nu/?number=300
if ($this->input->get("number")) {

	// this does not work?! gives corrupted content error or just displays the network down-page
	//redirect("https://textv.nu/" . $this->input->get("number"));
	//redirect("/" . $this->input->get("number"));

	// "tmp" fix
?>
	<script>
		document.location = "<?php echo "https://texttv.nu/" . (int) $this->input->get("number"); ?>";
	</script>
<?php
	exit;
}

$this->output->set_header('Content-Type: text/html; charset=utf-8');

// Skapa sidtitel
$page_title = "";
$ok_to_archive = TRUE;
$is_start = false;

if (isset($is_archive)) {
	$archive_permalink = $pages[0]->get_permalink();
}

if (isset($is_archive_overview)) {
	// Is overview over a page
	$page_title .= sprintf('Arkiv för SVT Text sida %1$d', $page->num);
} elseif (isset($pages) && is_array($pages)) {
	// date to use for last-modified
	// if multiple pages then we use the date from the most recent page
	$last_modified = null;
	foreach ($pages as $one_page) {
		$last_modified = max($last_modified, $one_page->date_updated_unix);
	}
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');

	if (isset($is_archive)) {
		// inget sidnummer först på arkiverade sidor
	} else {
		$page_title .= $pagenum . " ";
	}

	foreach ($pages as $one_page) {
		if (($page_name = $one_page->get_page_name())) {
			// Look for manually entered name
			$page_title .= trim($page_name);
		} elseif (trim($one_page->title)) {
			// Else see if we found a title when loading the page
			$page_title .= trim($one_page->title);
		} else {
			// hm.. not page name or title. let's append the page num at least
			//	$page_title .= "Sida " . $one_page->num;
			// $page_title .= "" . $one_page->num;
		}
		$page_title .= ", ";
	}
	$page_title = preg_replace('/, $/', "", $page_title);

	// Om mer än 1 sida, kolla om det är sidor som får hamna hos google
	/*if (sizeof($pages)>1) {
		foreach ($pages as $one_page) {
			if ($one_page->isOkToArchiveInRange() == FALSE) {
				$ok_to_archive = FALSE;
				break;
			}
		}
	}*/
	
	if (sizeof($pages) > 1) {
		// check that it's not the startpage
		if (sizeof($pages) == 7 && $pages[0]->num == 100 && $pages[1]->num == 300) {
			// typ startsida
		} else {
			// 2022-12-26: Äh, indexera på
			// $ok_to_archive = false;
		}
	}

	// Om permalink
	// if (isset($is_archive)) {
	// }

	// startpage has: 100,300,700
	// but has many pages so check for that so /100 is not considered start
	//if (isset($pages) && $pages[sizeof($pages)-1]->num == 100) {
	if (isset($pages) && $pages[0]->num == 100 && sizeof($pages) > 1) {
		$page_title = "SVT Text";
		$is_start = true;
		// 12 tkn använder ios för hemskärmsikonen

	} else if (isset($pages) && $pages[sizeof($pages) - 1]->num == 377) {
		$page_title = "SVT Text 377 - arkivsida"; // title som handlar på arkiv

	} else {

		$page_title .= " - SVT Text TV";
	}

	// Rensa lite skräp i formateringen för title
	$page_title = preg_replace("/(, )+/", ", ", $page_title);
}

if (isset($custom_page_title)) {
	$page_title .= $custom_page_title;
}

// Sätt klasser för html
$arr_html_classes = array();
if (isset($pages) && is_array($pages)) {
	if (sizeof($pages) == 1) {
		$arr_html_classes[] = "page-single";
		$arr_html_classes[] = "page-num-" . $pages[0]->num;
	} else {
		$arr_html_classes[] = "page-multiple";
	}
	//	print_r($pages);
}
if (isset($is_archive)) {
	$arr_html_classes[] = "page-is-archive";
}

// If page is retrieved with phantomjs, via the share function in the app
if ($this->input->get("apiAppShare")) {
	$arr_html_classes[] = "appShare";
	$arr_html_classes[] = "appShare--" . html_escape($this->input->get("apiAppShare"));
}

// New nav position
if ($this->input->get("navAtBottom")) {
	$arr_html_classes[] = "nav-at-bottom";
}

// Override description for 100 and 377 and some more pages, because so important
$twitter_description = "";
$twitter_title = $page_title;
$create_twitter_title = true;
if (!isset($is_archive)) {

	if (isset($pages) && $pages[sizeof($pages) - 1]->num == 100) {
		$twitter_description = "SVT Text sid 100";
		$create_twitter_title = false;
	} else if (isset($pages) && sizeof($pages) == 1) {
		// 1 page and specific number
		$first_page_num = $pages[0]->num;

		if (376 == $first_page_num) {
			$twitter_title = "376 - SVT Text TV";
			$twitter_description = "Målservice från SVT Text TV 376";
		} else if (377 == $first_page_num) {
			$twitter_title = "377 - SVT Text TV";
			$twitter_description = "På SVT Text TV 377 finns dagens sportresultat & målservice. ⚽️️ 377 – sportnördens bästa vän!";
		} else if (330 == $first_page_num) {
			$twitter_title = "330 - SVT Text TV";
			$twitter_description = "Resultatbörsen på SVT Text TV 330";
		} else if (551 == $first_page_num) {
			$twitter_title = "551 - SVT Text TV - Stryktipset";
			$twitter_description = "Resultat stryktipset – sida 551 på SVT Text TV med senaste resultatet för stryktipset";
		} else if (552 == $first_page_num) {
			$twitter_title = "552 - SVT Text TV - Stryktipset";
			$twitter_description = "Resultat stryktipset – sida 552 på SVT Text TV med senaste resultatet för stryktipset";
		} else if (383 == $first_page_num) {
			$twitter_title = "383 - Målservice från SVT Text TV";
			// $twitter_description = "";
		} else if (553 == $first_page_num) {
			//$twitter_title = "553 - SVT Text TV - Europatips & Topptips";
			// en av de vanligaste sökningarna enligt google ads är "svt text 553"
			$twitter_title = "SVT Text 553";
			$twitter_description = "Europatipset på SVT Text TV 553 (resultat och utdelning för europatipset och topptipset)";
		} else if (560 == $first_page_num) {
			$twitter_title = "560 - Oddset Bomben (sid 1 av 2) - SVT Text TV";
			$twitter_description = "Resultat för Oddset Bomben hittar du här på sid 560 hos SVT Text TV";
		} else if (561 == $first_page_num) {
			$twitter_title = "561 - Oddset Bomben (sid 2 av 2) - SVT Text TV";
			$twitter_description = "Resultat för Oddset Bomben hittar du här på sid 561 hos SVT Text TV";
		} else if (571 == $first_page_num) {
			$twitter_title = "Text TV 571 med V75-resultat";
			$twitter_description = "Se dagens V75-resultat med vinnare, odds/proc och värde på SVT Text TV 571.";
		} else if (202 == $first_page_num) {
			$twitter_title = "202 - SVT Text TV - Börsen";
			$twitter_description = "Följ omsättningen för stockholmsbörsen varje dag på SVT Text TV sid 202. Omsättning large cap och sammanfattning OMX Stockholm.";
		}

		$create_twitter_title = false;
	}
}

// Unvik att arkivera några gamla sidor
// 1 Oct 2016 börjar med 571 som hade dåligt result i Google SERP
if (isset($is_archive) && $is_archive) {
	if (in_array($pages[0]->num, [571, 377])) {
		$ok_to_archive = false;
	}
}

// startpage gets its own
if ($is_start) {
	// $twitter_title = "SVT Text TV från TextTV.nu";
	$twitter_title = "SVT Text TV";

	// $twitter_description = "På TextTV.nu hittar du SVT Text TV med fler och bättre funktioner. Vi gör allt för att ge dig bästa text-tv-upplevelsen!";
	$twitter_description = "
		Snabb, enkel, och mobilanpassad SVT Text TV. 
		Se 100, 300, 377 och dina andra Text TV-favoriter.
	";

	$create_twitter_title = false;
}
?>
<!DOCTYPE html>
<html lang="sv" class="<?php echo join(" ", $arr_html_classes) ?>">

<head>
	<title><?php echo @htmlspecialchars($twitter_title, ENT_QUOTES, "UTF-8"); ?></title>
	<meta content='width=device-width, initial-scale=1.0, maximum-scale=5.0' id='viewport' name='viewport' />
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16" />
	<link rel="apple-touch-icon" href="/images/favicon-152.png">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="theme-color" content="#0066B5">
	<link rel="dns-prefetch" href="//google-analytics.com">
	<link rel="dns-prefetch" href="//www.google-analytics.com">
	<link rel="dns-prefetch" href="//ssl.google-analytics.com">
	<link rel="preload" href="https://fonts.gstatic.com/s/ubuntumono/v10/KFOjCneDtsqEr0keqCMhbCc6CsE.ttf" as="font" crossorigin>
	<link rel="preload" href="https://fonts.gstatic.com/s/ubuntumono/v10/KFO-CneDtsqEr0keqCMhbC-BL9H1tYg.ttf" as="font" crossorigin>
	<link rel="alternate" type="application/atom+xml" href="https://texttv.nu/feed/blogg" />
	<?php
	// Inte indexera range-sidor. Känns så meningslöst och förvirrande just nu.
	// eller jo, vi kör på mer indexerat = bättre
	// 17 feb 2012: nej förresten. känns som mkt duplicate?
	// site:texttv.nu innan: ca. 113000 
	if ($ok_to_archive == FALSE) {
		// ...fast vissa range vill vi tillåta, t.ex. nyhetsöversikter etc.
		// inaktiverat 23 april pga jag fuckade upp och gjorde noindex på start / och jag fattade inte varför?!
		// aktiverar igen 12 juli 2015 pga t.ex. texttv.nu/106,132 hamnade i index
		// inaktiverar 23 maj 2022 pga satsar på long tail
		
		?>
		<meta name="robots" content="noindex, follow">
		<?php		
	}

	/**
	 * 8 Jan 2018:  Sätt noindex på lite sidor pga 
	 * 11 May 2018: Noindex på fler sidor pga tänker kan komma högre upp i resultat pga färre sidor i index
	 * 23 maj 2022: Äh, indexera all, satsa på lite long tail. 
	 */
	$shareCount = 0;

	if (isset($pages[0]->is_shared) && is_numeric($pages[0]->is_shared)) {
		$shareCount = intval($pages[0]->is_shared);
	}

	if (false && isset($is_archive) && ($shareCount < 15)) {
		?>
		<meta name="robots" content="noindex, follow" data-share-count="<?php echo $shareCount ?>">
		<?php
	} else {
	?>
		<meta name="x-share-count" content="<?php echo $shareCount ?>">
	<?php
	}

	// Meta-stuff för twitter på text-tv-sidorna
	if (isset($pages) && is_array($pages)) {
		#$twitter_title = "";
		#$twitter_description = "";
		if ($create_twitter_title) {

			$twitter_title = "";

			if (trim($pages[0]->title)) {
				$twitter_title .= trim($pages[0]->title);
			} else {
				$twitter_title .= "SVT Text TV sid ";
				foreach ($pages as $one_page) {
					$twitter_title .= $one_page->num . ", ";
				}
				$twitter_title = rtrim($twitter_title, " ,");
			}
			$twitter_title = mb_substr($twitter_title, 0, 70);

			$show_twitter_card_meta = false;

			if ($this->input->get("show_twitter_meta") || strpos($_SERVER["HTTP_USER_AGENT"], "Twitterbot") !== false) {
				$show_twitter_card_meta = true;
			}

			$show_og_tags = false;

			if ($this->input->get("show_og_tags") || strpos($_SERVER["HTTP_USER_AGENT"], "facebookexternalhit") !== false) {
				$show_og_tags = true;
			}

			// Start twitter/og-tags
			/*
				Looks like this (31 jan 2016):
				130 SVT Text Söndag 31 jan 2016 UTRIKES PUBLICERAD 31 JANUARI Experter möter WHO om zikaviruset En grupp experter samlas i Geneve i morgon för att ge råd till WHO-chefen om hur världshälsoorganisatio
				190 SVT Text Söndag 31 jan 2016 Nyheter från TT för SVT Text kl 01-06 M föreslår slopat detaljplanekrav...191 Experter möter WHO om zikaviruset...192 SVT Text:s nyhetsredaktion är bemannad klockan 06
				106 SVT Text Söndag 31 jan 2016 INRIKES PUBLICERAD 31 JANUARI M vill förenkla boostadsbyggandet Moderaterna vill förenkla bostads- byggandet i Sverige, bland annat genom att på sikt avskaffa detaljpl
				106 SVT Text Lördag 30 jan 2016 INRIKES PUBLICERAD 30 JANUARI Medborgarskap försvårar USA-resor Sedan 21 januari kan svenskar som också har medborgarskap i eller har besökt Iran, Irak, Sudan och Syri
				
			*/
			$twitter_description .= @$pages[0]->arr_contents[0];
			$twitter_description = strip_tags($twitter_description);
			$twitter_description = preg_replace('![ \n]+!', ' ', $twitter_description);

			// Ta bort text om svt + dag
			// 130 SVT Text Söndag 31 jan 2016 UTRIKES PUBLICERAD 31 JANUARI Experter möter WHO om zikaviruset En grupp experter samlas i Geneve i morgon för att ge råd till WHO-chefen om hur världshälsoorganisatio
			$first_year_location = preg_match('/\d{4}/', $twitter_description, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {

				#echo "\nFound match {$matches[0][0]} with start on offset {$matches[0][1]}";
				#echo "\nnew string:";
				$twitter_description = mb_substr($twitter_description, $matches[0][1] + 4);
				$twitter_description = trim($twitter_description);
			}


			$twitter_description = mb_substr($twitter_description, 0, 200);
			$twitter_description = trim($twitter_description);
		}
	?>
		<meta property="twitter:card" content="summary">
		<meta property="twitter:site" content="@texttv_nu">
		<meta property="twitter:title" content="<?php echo @htmlspecialchars($twitter_title, ENT_QUOTES, "UTF-8"); ?>">
		<?php if ($twitter_description) { ?>
			<meta property="twitter:description" content="<?php echo @htmlspecialchars($twitter_description, ENT_QUOTES, "UTF-8"); ?>">
		<?php } ?>
		<meta property="twitter:app:name:iphone" content="TextTV.nu">
		<meta property="twitter:app:id:iphone" content="607998045">
		<meta property="twitter:app:id:ipad" content="607998045">
		<meta property="fb:admins" content="685381489" />
		<meta property="fb:admins" content="761320320" />
		<meta property="og:title" content="<?php echo @htmlspecialchars($twitter_title, ENT_QUOTES, "UTF-8"); ?>">
		<?php if ($twitter_description) { ?>
			<meta property="og:description" content="<?php echo @htmlspecialchars($twitter_description, ENT_QUOTES, "UTF-8"); ?>">
		<?php } ?>
		<meta property="og:type" content="article" />
		<?php
		$screenshot_url = "https://texttv.nu/images/texttv-nu-publisher-logo.png";
		if (isset($is_archive) && isset($pages[0])) {
			// skapa skärmdump av första sidan
			$screenshot_url = sprintf('https://texttv.nu/api/screenshot/%d.jpg', $pages[0]->id);
		}
		?>
		<meta property="og:image" content="<?php echo $screenshot_url ?>" />
		<?php if ($twitter_description) { ?>
			<meta name="description" content="<?php echo @htmlspecialchars($twitter_description, ENT_QUOTES, "UTF-8"); ?>">
		<?php } ?>
	<?php
		/*
		@TODO: add images
		<meta property="og:image" content="http://ia.media-imdb.com/images/rock.jpg" />
		
		twitter:image
		URL to a unique image representing the content of the page. 
		Do not use a generic image such as your website logo, author photo, 
		or other image that spans multiple pages. The image must be a minimum size of 120x120px. 
		Images larger than 120x120px will be resized and cropped square based on its longest dimension. 
		Images must be less than 1MB in size.

		*/
		// end og/twitter tags

	} // if pages

	/*
	27 jun 2015: flyttade denna css till styles.css istället, så har vi bara en enda style = 2 st färre anrop
	             aktiverade google fonts från egen fil igen, safari desktop + mobile laddade inte fonterna om dom låg i styles.css
	<link href='//fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet'>
	<link href="//netdna.bootstrapcdn.com/font-awesome/3.0/css/font-awesome.css" rel="stylesheet">
	*/
	?>
	<!-- <link rel='stylesheet' href='//fonts.googleapis.com/css?family=Ubuntu+Mono:400,700'> -->
	<link rel="stylesheet" href="/min/?f=css/fonts.css,css/styles.css,css/texttvpage.css&amp;v=17">
	<?php if ($this->input->get("skipScriptsHeader")) {
		// no scripts
	} else {
		/* ?><script src="/min/?b=js&amp;f=head.js&amp;20131024-4"></script><?php */
	}
	?>
	<?php // manifest for webb app install banner on android 
	?>
	<link rel="manifest" href="/manifest.json">
	<?php
	// Moved to body data attr 27 july 2016
	/*
	<script>var pages = [];<?php
		if (isset($pages)) {
			foreach ($pages as $one_page) {
				printf("pages.push({num:%d,id:%d,added:%d});", $one_page->num, $one_page->id, $one_page->date_added_unix);
			}
		}?></script>
	*/

	// Add current pages to body data attr
	$bodyPagesDataAttr = [];
	if (isset($pages)) {
		foreach ($pages as $one_page) {
			//printf("pages.push({num:%d,id:%d,added:%d});", $one_page->num, $one_page->id, $one_page->date_added_unix);
			$bodyPagesDataAttr[] = [
				"num" => (int) $one_page->num,
				"id" => (int) $one_page->id,
				"added" => (int) $one_page->date_added_unix
			];
		}
	}
	?>
	<meta name="apple-itunes-app" content="app-id=607998045">
	<?php

	$wrapclasses = isset($wrapclasses) ? $wrapclasses : array();
	$wrapclasses[] = "clearfix";
	/*
	Add canonical to pages that have multiple indexed pages in google
	
	Examples of bad indexed pages:
	http://texttv.nu/330?utm_source=texttvnu&utm_medium=nav&utm_campaign=leifby&utm_term=favorit_4&utm_nooveride=1
	http://texttv.nu/330?utm_source=texttvnu&utm_medium=nav&utm_campaign=leifby&utm_term=favorit_4&utm_nooverride=1
	http://texttv.nu/377?utm_expid=54301347-3
	*/
	if (!isset($is_archive) && isset($pages) && sizeof($pages) == 1) {
	?>
		<link rel="canonical" href="https://texttv.nu/<?php echo $pages[0]->num  ?>" />
	<?php
	}

	/* // AMP disabled on 2021-10-17
	if (!empty($pagenum) && (!isset($is_archive) || false == $is_archive)) {
		// AMP link for non-archived pages
	?>
		<link rel="amphtml" href="/<?php echo html_escape($pagenum) ?>/amp">
	<?php
	} else if (isset($is_archive) && $is_archive) {
		// AMP link for archive version
	?>
		<link rel="amphtml" href="<?php echo html_escape($archive_permalink) ?>/amp">
	<?php
	}
	*/
	
	/*
	Google Rich Snippet
	https://developers.google.com/structured-data/rich-snippets/articles
	https://developers.google.com/structured-data/testing-tool/	
	*/

	if (isset($is_archive) && !$this->input->get("apiAppShare")) {
		$archive_date = date("c", $pages[0]->date_updated_unix);
		
		?>
		<script type="application/ld+json">
			{
				"@context": "https://schema.org",
				"@type": "NewsArticle",
				"mainEntityOfPage": {
					"@type": "WebPage",
					"@id": "https://texttv.nu<?php echo $archive_permalink ?>"
				},
				"headline": "<?php echo $page_title ?>",
				"image": {
					"@type": "ImageObject",
					"url": "<?php echo $screenshot_url ?>",
					"height": 800,
					"width": 800
				},
				"datePublished": "<?php echo $archive_date ?>",
				"dateModified": "<?php echo $archive_date ?>",
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
						"width": 600,
						"height": 66
					}
				},
				"description": "<?php echo $twitter_description ?>"
			}
		</script>
	<?php

	} // archive rich data

	if (!$this->input->get("apiAppShare")) {
		?>
		<script type="application/ld+json">
			{
				"@context": "https://schema.org",
				"@type": "WebSite",
				"name": "TextTV.nu",
				"alternateName": "TextTV.nu",
				"url": "https://texttv.nu"
			}
		</script>
		<script>
			(function(i, s, o, g, r, a, m) {
				i['GoogleAnalyticsObject'] = r;
				i[r] = i[r] || function() {
					(i[r].q = i[r].q || []).push(arguments)
				}, i[r].l = 1 * new Date();
				a = s.createElement(o),
					m = s.getElementsByTagName(o)[0];
				a.async = 1;
				a.src = g;
				m.parentNode.insertBefore(a, m)
			})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
			ga('create', 'UA-181460-18', 'auto');
			ga('require', 'linkid', 'linkid.js');
			ga('set', 'anonymizeIp', true);
			ga('send', 'pageview');
		</script>
	<?php

	}

	// Title and stuff for search page
	if ($this->input->get("q")) {
	}
	?>
</head>

<body data-pages='<?php echo json_encode($bodyPagesDataAttr) ?>'>
	<?php
	// TODO: only load this on desktop
	?>
	<div class="wrap <?php echo implode(" ", $wrapclasses) ?>" id="wrap">