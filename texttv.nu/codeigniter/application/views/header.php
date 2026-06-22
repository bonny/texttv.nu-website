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
$ok_to_archive = true;
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
		// Is "/" for startpage.
		$url = base_url(uri_string());

		if ( $url == "/" ) {
			// startsidan, är flera pages men det är startsidan så den ska indexeras.
		} else {
			// 2022-12-26: Äh, indexera på
			// 2025-03-24: Äh, indexera inte
			$ok_to_archive = false;
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
$meta_description = "";
$meta_title = $page_title;
$generate_meta_title = true;
if (!isset($is_archive)) {

	if (isset($pages) && $pages[sizeof($pages) - 1]->num == 100) {
		// Direkt /100-request (single-page). Startpage / hanteras av $is_start-blocket längre ner.
		$meta_description = "SVT Text TV 100 – startsidan med dagens senaste rubriker från inrikes, utrikes, sport, väder och TV-tablåer. Uppdateras löpande.";
		$generate_meta_title = false;
	} else if (isset($pages) && sizeof($pages) == 1) {
		// 1 page and specific number
		$first_page_num = $pages[0]->num;

		if (376 == $first_page_num) {
			$meta_title = "376 - SVT Text TV";
			$meta_description = "Målservice från SVT Text TV 376";
		} else if (377 == $first_page_num) {
			$meta_title = "377 - SVT Text TV";
			$meta_description = "På SVT Text TV 377 finns dagens sportresultat & målservice. ⚽️️ 377 – sportnördens bästa vän!";
		} else if (330 == $first_page_num) {
			$meta_title = "330 - SVT Text TV";
			$meta_description = "Resultatbörsen på SVT Text TV 330";
		} else if (551 == $first_page_num) {
			$meta_title = "551 - SVT Text TV - Stryktipset";
			$meta_description = "Resultat stryktipset – sida 551 på SVT Text TV med senaste resultatet för stryktipset";
		} else if (552 == $first_page_num) {
			$meta_title = "552 - SVT Text TV - Stryktipset";
			$meta_description = "Resultat stryktipset – sida 552 på SVT Text TV med senaste resultatet för stryktipset";
		} else if (383 == $first_page_num) {
			$meta_title = "383 - SVT Text TV - Målservice fotboll";
			$meta_description = "Målservice för fotboll på SVT Text TV 383. ⚽️ Aktuella målsiffror och resultat från svenska och internationella matcher.";
		} else if (553 == $first_page_num) {
			//$meta_title = "553 - SVT Text TV - Europatips & Topptips";
			// en av de vanligaste sökningarna enligt google ads är "svt text 553"
			$meta_title = "SVT Text 553";
			$meta_description = "Europatipset på SVT Text TV 553 (resultat och utdelning för europatipset och topptipset)";
		} else if (560 == $first_page_num) {
			$meta_title = "560 - Oddset Bomben (sid 1 av 2) - SVT Text TV";
			$meta_description = "Resultat för Oddset Bomben hittar du här på sid 560 hos SVT Text TV";
		} else if (561 == $first_page_num) {
			$meta_title = "561 - Oddset Bomben (sid 2 av 2) - SVT Text TV";
			$meta_description = "Resultat för Oddset Bomben hittar du här på sid 561 hos SVT Text TV";
		} else if (571 == $first_page_num) {
			$meta_title = "Text TV 571 med V75-resultat";
			$meta_description = "Se dagens V75-resultat med vinnare, odds/proc och värde på SVT Text TV 571.";
		} else if (202 == $first_page_num) {
			$meta_title = "202 - SVT Text TV - Börsen";
			$meta_description = "Följ omsättningen för stockholmsbörsen varje dag på SVT Text TV sid 202. Omsättning large cap och sammanfattning OMX Stockholm.";
		} else if (101 == $first_page_num) {
			$meta_title = "101 - SVT Text TV - Inrikes";
			$meta_description = "Dagens inrikesnyheter i korthet på SVT Text TV 101. Rubrikerna från Sverige uppdateras löpande genom dagen.";
		} else if (104 == $first_page_num) {
			$meta_title = "104 - SVT Text TV - Utrikes";
			$meta_description = "Dagens utrikesnyheter i korthet på SVT Text TV 104. Vad som händer i världen just nu, uppdaterat löpande.";
		} else if (106 == $first_page_num) {
			$meta_title = "106 - SVT Text TV - Inrikes nyhet";
			$meta_description = "Dagens topp-inrikesnyhet i sin helhet på SVT Text TV 106. Uppdateras varje dag med senaste nytt från Sverige.";
		} else if (127 == $first_page_num) {
			$meta_title = "127 - SVT Text TV - Börsindex";
			$meta_description = "Dagens stängningskurser för OMX Stockholm, Dow Jones, Nasdaq, DAX, FTSE och Nikkei på SVT Text TV 127.";
		} else if (130 == $first_page_num) {
			$meta_title = "130 - SVT Text TV - Utrikes nyhet";
			$meta_description = "Dagens topp-utrikesnyhet i sin helhet på SVT Text TV 130. Uppdateras varje dag med det viktigaste från världen.";
		} else if (300 == $first_page_num) {
			$meta_title = "300 - SVT Text TV - Sport";
			$meta_description = "Sportens startsida på SVT Text TV 300. ⚽️ Dagens sportrubriker + snabb väg till resultat, tabeller och målservice.";
		} else if (336 == $first_page_num) {
			$meta_title = "336 - SVT Text TV - Premier League";
			$meta_description = "Premier League på SVT Text TV 336. ⚽️ Resultat från senaste omgången, kommande matcher och färsk tabell.";
		} else if (339 == $first_page_num) {
			$meta_title = "339 - SVT Text TV - La Liga";
			$meta_description = "La Liga på SVT Text TV 339. ⚽️ Resultat från Spaniens Primera Division och uppdaterad tabell.";
		} else if (343 == $first_page_num) {
			$meta_title = "343 - SVT Text TV - Allsvenskan tabell";
			$meta_description = "Allsvenskans tabell på SVT Text TV 343. ⚽️ Hela ställningen för alla 16 lag – matcherna hittar du på 344.";
		} else if (344 == $first_page_num) {
			$meta_title = "344 - SVT Text TV - Allsvenskan resultat";
			$meta_description = "Allsvenskans resultat och kommande matcher på SVT Text TV 344. ⚽️ Senaste omgången direkt – tabellen ligger på 343.";
		} else if (345 == $first_page_num) {
			$meta_title = "345 - SVT Text TV - Superettan";
			$meta_description = "Superettan på SVT Text TV 345. ⚽️ Resultat från senaste omgången och uppdaterad tabell.";
		} else if (349 == $first_page_num) {
			$meta_title = "349 - SVT Text TV - Damallsvenskan";
			$meta_description = "Damallsvenskan på SVT Text TV 349. ⚽️ Resultat från senaste omgången och uppdaterad tabell.";
		} else if (358 == $first_page_num) {
			$meta_title = "358 - SVT Text TV - SHL tabell";
			$meta_description = "SHL-tabellen på SVT Text TV 358. 🏒 Hela ställningen i Svenska Hockeyligan, uppdaterad efter varje omgång.";
		} else if (364 == $first_page_num) {
			$meta_title = "364 - SVT Text TV - Hockeyettan slutspel";
			$meta_description = "Hockeyettans slutspel på SVT Text TV 364. 🏒 Final, kvartsfinaler och åttondelar – resultat och matcher.";
		} else if (365 == $first_page_num) {
			$meta_title = "365 - SVT Text TV - SHL poängliga";
			$meta_description = "SHL:s poängliga på SVT Text TV 365. 🏒 Toppscorerlistan med mål, assist och poäng – uppdateras löpande.";
		} else if (374 == $first_page_num) {
			$meta_title = "374 - SVT Text TV - Beijer Hockey Games";
			$meta_description = "Beijer Hockey Games på SVT Text TV 374. 🏒 Resultat och tabell från landslagsturneringen med Sverige, Finland, Tjeckien och Schweiz.";
		} else if (399 == $first_page_num) {
			$meta_title = "399 - SVT Text TV - TV-tider sport";
			$meta_description = "Sport på SVT i dag – tider och sändningar samlade på SVT Text TV 399. Fotbollsstudion, friidrott, damallsvenskan med mera.";
		} else if (402 == $first_page_num) {
			$meta_title = "402 - SVT Text TV - Temperaturer Sverige";
			$meta_description = "Gårdagens temperaturer från orter i hela Sverige – Kiruna till Malmö – på SVT Text TV 402. Mätningen kl 14, uppdateras dagligen.";
		} else if (601 == $first_page_num) {
			$meta_title = "601 - SVT Text TV - TV-tablå SVT1";
			$meta_description = "Dagens TV-tablå för SVT1 på SVT Text TV 601. Alla program från morgon till sen kväll – uppdateras varje dag.";
		} else if (700 == $first_page_num) {
			$meta_title = "700 - SVT Text TV - Innehåll";
			$meta_description = "Innehållsförteckningen på SVT Text TV 700. Hitta alla sidor – nyheter, sport, väder, TV-tablåer och mer – med sidnummer.";

			// Datadriven whitelist-utvidgning #04 Fas 1 (2026-06-22): högimpressions-sidor
			// (GSC topp-sidor) som tidigare bara fick generisk block-fallback. Sidor med
			// stabil sid-identitet enligt SVT Text TV:s numrering. Efemära event-/tomsidor
			// (VM-grupper, tomma sidor, roterande lokalprognoser) lämnas medvetet till
			// block-fallbacken — se #04-todon.
		} else if (328 == $first_page_num) {
			$meta_title = "328 - SVT Text TV - Ishockey NHL";
			$meta_description = "Ishockey NHL på SVT Text TV 328. 🏒 Matchfakta, resultat och målskyttar från NHL.";
		} else if (337 == $first_page_num) {
			$meta_title = "337 - SVT Text TV - Engelska Championship";
			$meta_description = "Engelska Championship på SVT Text TV 337. ⚽️ Resultat, playoff och tabell från Englands näst högsta fotbollsdivision.";
		} else if (346 == $first_page_num) {
			$meta_title = "346 - SVT Text TV - Fotboll Ettan";
			$meta_description = "Ettan på SVT Text TV 346. ⚽️ Resultat från senaste omgången och uppdaterad tabell i herrfotbollens tredjedivision.";
		} else if (347 == $first_page_num) {
			$meta_title = "347 - SVT Text TV - Division 2 fotboll";
			$meta_description = "Division 2 herr på SVT Text TV 347. ⚽️ Resultat och tabeller från fotbollens fjärdedivision.";
		} else if (348 == $first_page_num) {
			$meta_title = "348 - SVT Text TV - Division 2 fotboll";
			$meta_description = "Division 2 herr på SVT Text TV 348. ⚽️ Resultat och tabeller från fotbollens fjärdedivision.";
		} else if (359 == $first_page_num) {
			$meta_title = "359 - SVT Text TV - SHL spelschema";
			$meta_description = "SHL på SVT Text TV 359. 🏒 Kommande matcher och resultat i Svenska Hockeyligan – tabellen ligger på 358.";
		} else if (375 == $first_page_num) {
			$meta_title = "375 - SVT Text TV - Ishockey VM";
			$meta_description = "Ishockey-VM på SVT Text TV 375. 🏒 Resultat och gruppspel från världsmästerskapet i ishockey.";
		} else if (378 == $first_page_num) {
			$meta_title = "378 - SVT Text TV - Målservice fotboll";
			$meta_description = "Målservice för fotboll på SVT Text TV 378. ⚽️ Live målsiffror medan matcherna spelas. Index på 376, resultat på 330.";
		} else if (379 == $first_page_num) {
			$meta_title = "379 - SVT Text TV - Målservice fotboll";
			$meta_description = "Målservice för fotboll på SVT Text TV 379. ⚽️ Live målsiffror medan matcherna spelas. Index på 376, resultat på 330.";
		} else if (380 == $first_page_num) {
			$meta_title = "380 - SVT Text TV - Målservice fotboll";
			$meta_description = "Målservice för fotboll på SVT Text TV 380. ⚽️ Live målsiffror medan matcherna spelas. Index på 376, resultat på 330.";
		} else if (381 == $first_page_num) {
			$meta_title = "381 - SVT Text TV - Målservice fotboll";
			$meta_description = "Målservice för fotboll på SVT Text TV 381. ⚽️ Live målsiffror medan matcherna spelas. Index på 376, resultat på 330.";
		} else if (400 == $first_page_num) {
			$meta_title = "400 - SVT Text TV - Väder";
			$meta_description = "Väderöversikten på SVT Text TV 400. Hitta vädersidorna: prognos (401), temperaturer (402), femdygnsprognoser och sjöväder.";
		} else if (404 == $first_page_num) {
			$meta_title = "404 - SVT Text TV - Varmast och kallast";
			$meta_description = "Varmast och kallast i Sverige på SVT Text TV 404. Dagens högsta och lägsta temperaturer ort för ort, mätt kl 14.";
		} else if (600 == $first_page_num) {
			$meta_title = "600 - SVT Text TV - TV-tablåer";
			$meta_description = "TV-tablåernas startsida på SVT Text TV 600. Hitta dagens program: SVT1 (601), SVT2 (604), SVT Barn, Kunskapskanalen, TV3, TV4 med flera.";
		} else if (602 == $first_page_num) {
			$meta_title = "602 - SVT Text TV - TV-tablå SVT1";
			$meta_description = "Dagens TV-tablå för SVT1 på SVT Text TV 602 (forts. från 601). Alla program med tider – uppdateras varje dag.";
		} else if (621 == $first_page_num) {
			$meta_title = "621 - SVT Text TV - TV-tablå TV4";
			$meta_description = "Dagens TV-tablå för TV4 på SVT Text TV 621. Alla program från morgon till kväll – uppdateras varje dag.";
		} else if (730 == $first_page_num) {
			$meta_title = "730 - SVT Text TV - Cykel";
			$meta_description = "Cykel på SVT Text TV 730. 🚴 Resultat och etappplaceringar från Giro d'Italia, Tour de France och andra stora lopp.";
		} else if (731 == $first_page_num) {
			$meta_title = "731 - SVT Text TV - Tennis ATP";
			$meta_description = "Tennis på SVT Text TV 731. 🎾 Resultat från ATP-touren – herrarnas turneringar, finaler och matcher.";
		} else if (732 == $first_page_num) {
			$meta_title = "732 - SVT Text TV - Tennis WTA";
			$meta_description = "Tennis på SVT Text TV 732. 🎾 Resultat från WTA-touren – damernas turneringar, finaler och matcher.";
		} else if (744 == $first_page_num) {
			$meta_title = "744 - SVT Text TV - Formel 1";
			$meta_description = "Formel 1 på SVT Text TV 744. 🏎️ Resultat och placeringar från F1-loppen – Grand Prix för Grand Prix.";
		}

		// Blockbaserad fallback för sidor utan specifik whitelist-entry.
		// Sidnummer-blocken följer SVT Text TV:s indelning (se även breadcrumbs.php
		// som har samma mappning). Ger keyword-rik description som är stabil per
		// sid-kategori istället för generiska "SVT Text sid NNN". Påverkar bara
		// sidor som inte redan fick description ovan.
		if (!$meta_description) {
			$n = (int) $first_page_num;
			if ($n >= 101 && $n <= 129) {
				$meta_title = "$n - SVT Text TV - Inrikes";
				$meta_description = "SVT Text TV $n – inrikesnyheter från Sverige. Senaste rubrikerna och nyhetsuppdateringarna direkt från SVT, uppdateras löpande.";
			} else if ($n >= 130 && $n <= 199) {
				$meta_title = "$n - SVT Text TV - Utrikes";
				$meta_description = "SVT Text TV $n – utrikesnyheter från världen. Vad som händer just nu, uppdateras löpande genom dagen.";
			} else if ($n >= 200 && $n <= 299) {
				$meta_title = "$n - SVT Text TV - Ekonomi";
				$meta_description = "SVT Text TV $n – ekonomi och börsen. Aktuella kurser, räntor och valutor från Stockholmsbörsen och världsmarknaderna.";
			} else if ($n >= 300 && $n <= 399) {
				$meta_title = "$n - SVT Text TV - Sport";
				$meta_description = "SVT Text TV $n – sport. Resultat, tabeller och senaste sportnyheterna från fotboll, hockey, längdskidor och mer.";
			} else if ($n >= 400 && $n <= 439) {
				$meta_title = "$n - SVT Text TV - Väder";
				$meta_description = "SVT Text TV $n – vädret i Sverige och omvärlden. Prognoser, temperaturer och varningar uppdaterade dagligen.";
			} else if ($n >= 440 && $n <= 499) {
				$meta_title = "$n - SVT Text TV - Sport / OS";
				$meta_description = "SVT Text TV $n – sport och OS-bevakning. Resultat, tabeller och scheman från större mästerskap.";
			} else if ($n >= 500 && $n <= 549) {
				$meta_title = "$n - SVT Text TV - Spel";
				$meta_description = "SVT Text TV $n – lotto och spel. Vinstrader, dragningar och resultat från Svenska Spel.";
			} else if ($n >= 550 && $n <= 569) {
				$meta_title = "$n - SVT Text TV - Tipset";
				$meta_description = "SVT Text TV $n – stryktipset, europatipset och topptipset. Senaste raderna, resultat och utdelningar.";
			} else if ($n >= 570 && $n <= 599) {
				$meta_title = "$n - SVT Text TV - Trav och Galopp";
				$meta_description = "SVT Text TV $n – trav och galopp. V75, V64, V86 med resultat, vinnare och utdelningar.";
			} else if ($n >= 600 && $n <= 669) {
				$meta_title = "$n - SVT Text TV - TV-tablåer";
				$meta_description = "SVT Text TV $n – TV-tablåer för dagens program. SVT1, SVT2, SVT24, Barnkanalen och Kunskapskanalen från morgon till natt.";
			} else if ($n >= 670 && $n <= 699) {
				$meta_title = "$n - SVT Text TV - Radio";
				$meta_description = "SVT Text TV $n – radio och poddinformation. Programtider och innehåll från SR.";
			} else if ($n >= 700 && $n <= 799) {
				$meta_title = "$n - SVT Text TV - Innehåll";
				$meta_description = "SVT Text TV $n – innehållsöversikter, programinformation och navigation till andra sidor på text-tv.";
			} else if ($n >= 800 && $n <= 999) {
				$meta_title = "$n - SVT Text TV";
				$meta_description = "SVT Text TV $n – sidan med text-tv-information. Innehåller aktuell information från SVT.";
			}
		}

		$generate_meta_title = false;
	}
}

// Unvik att arkivera några gamla sidor
// 1 Oct 2016 börjar med 571 som hade dåligt result i Google SERP
if (isset($is_archive) && $is_archive) {
	if (in_array($pages[0]->num, [571, 377])) {
		$ok_to_archive = false;
	}
}

// startpage gets its own (composite-rendering av 100,300,401,101-105 — se sida::index)
if ($is_start) {
	$meta_title = "SVT Text TV";
	$meta_description = "TextTV.nu – snabbare och mobilanpassad SVT Text TV. Dagens senaste rubriker från inrikes, utrikes, sport, väder och TV-tablåer. Uppdateras löpande.";
	$generate_meta_title = false;
}
?>
<!DOCTYPE html>
<html lang="sv" class="<?php echo join(" ", $arr_html_classes) ?>">

<head>
	<title><?php echo @htmlspecialchars($meta_title, ENT_QUOTES, "UTF-8"); ?></title>
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
		#$meta_title = "";
		#$meta_description = "";
		if ($generate_meta_title) {

			$meta_title = "";

			if (trim($pages[0]->title)) {
				$meta_title .= trim($pages[0]->title);
			} else {
				$meta_title .= "SVT Text TV sid ";
				foreach ($pages as $one_page) {
					$meta_title .= $one_page->num . ", ";
				}
				$meta_title = rtrim($meta_title, " ,");
			}
			$meta_title = mb_substr($meta_title, 0, 70);

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
			$meta_description .= @$pages[0]->arr_contents[0];
			$meta_description = strip_tags($meta_description);
			$meta_description = preg_replace('![ \n]+!', ' ', $meta_description);

			// Ta bort text om svt + dag
			// 130 SVT Text Söndag 31 jan 2016 UTRIKES PUBLICERAD 31 JANUARI Experter möter WHO om zikaviruset En grupp experter samlas i Geneve i morgon för att ge råd till WHO-chefen om hur världshälsoorganisatio
			$first_year_location = preg_match('/\d{4}/', $meta_description, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {

				#echo "\nFound match {$matches[0][0]} with start on offset {$matches[0][1]}";
				#echo "\nnew string:";
				$meta_description = mb_substr($meta_description, $matches[0][1] + 4);
				$meta_description = trim($meta_description);
			}


			$meta_description = mb_substr($meta_description, 0, 200);
			$meta_description = trim($meta_description);
		}
	?>
		<meta property="twitter:card" content="summary">
		<meta property="twitter:site" content="@texttv_nu">
		<meta property="twitter:title" content="<?php echo @htmlspecialchars($meta_title, ENT_QUOTES, "UTF-8"); ?>">
		<?php if ($meta_description) { ?>
			<meta property="twitter:description" content="<?php echo @htmlspecialchars($meta_description, ENT_QUOTES, "UTF-8"); ?>">
		<?php } ?>
		<meta property="twitter:app:name:iphone" content="TextTV.nu">
		<meta property="twitter:app:id:iphone" content="607998045">
		<meta property="twitter:app:id:ipad" content="607998045">
		<meta property="fb:admins" content="685381489" />
		<meta property="fb:admins" content="761320320" />
		<meta property="og:title" content="<?php echo @htmlspecialchars($meta_title, ENT_QUOTES, "UTF-8"); ?>">
		<?php if ($meta_description) { ?>
			<meta property="og:description" content="<?php echo @htmlspecialchars($meta_description, ENT_QUOTES, "UTF-8"); ?>">
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
		<?php if ($meta_description) { ?>
			<meta name="description" content="<?php echo @htmlspecialchars($meta_description, ENT_QUOTES, "UTF-8"); ?>">
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

	?>
	<link rel="stylesheet" href="/css/fonts.css">
	<link rel="stylesheet" href="/css/styles.css">
	<link rel="stylesheet" href="/css/texttvpage.css">
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
				"description": "<?php echo $meta_description ?>"
			}
		</script>
	<?php

	} // archive rich data

	// Article structured data för live (non-archive) single-page sidor.
	// Arkivsidor får NewsArticle ovan; multi-page-vyer och appShare hoppas över.
	// json_encode säkerställer att citat-tecken i $meta_title/$meta_description inte bryter JSON.
	if (!isset($is_archive) && !$this->input->get("apiAppShare") && isset($pages) && sizeof($pages) == 1) {
		$live_canonical = "https://texttv.nu/" . (int) $pages[0]->num;
		$article_data = [
			"@context" => "https://schema.org",
			"@type" => "Article",
			"mainEntityOfPage" => [
				"@type" => "WebPage",
				"@id" => $live_canonical
			],
			"headline" => $meta_title,
			"description" => $meta_description,
			"image" => "https://texttv.nu/images/texttv-nu-publisher-logo.png",
			"datePublished" => date("c", $pages[0]->date_added_unix),
			"dateModified" => date("c", $pages[0]->date_updated_unix),
			"author" => [
				"@type" => "Organization",
				"name" => "SVT Text TV",
				"url" => "https://www.svt.se/text-tv/"
			],
			"publisher" => [
				"@type" => "Organization",
				"name" => "TextTV.nu",
				"url" => "https://texttv.nu",
				"logo" => [
					"@type" => "ImageObject",
					"url" => "https://texttv.nu/images/texttv-nu-publisher-logo.png",
					"width" => 600,
					"height" => 66
				]
			]
		];
		echo "\n\t\t<script type=\"application/ld+json\">"
			. json_encode($article_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
			. "</script>\n";
	}

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

		<!-- Google tag (gtag.js) -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-J9BM4E3WHD"></script>
		<script>
			window.dataLayer = window.dataLayer || [];

			function gtag() {
				dataLayer.push(arguments);
			}
			gtag('js', new Date());
			gtag('config', 'G-J9BM4E3WHD');
		</script>
		<?php
	}

	// Title and stuff for search page
	if ($this->input->get("q")) {
	}
	?>
</head>

<body data-pages='<?php echo json_encode($bodyPagesDataAttr) ?>'>
	<div class="wrap <?php echo implode(" ", $wrapclasses) ?>" id="wrap">