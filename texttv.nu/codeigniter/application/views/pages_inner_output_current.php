<?php

/*
	View = innehållet på vanliag sidor med texttv-innehåll
	(inte arkiv etc.)
*/

$arr_pages = $pages;
$out = "";
$is_startpage = isset($pagedescription) && $pagedescription === 'startpage';


// Lägg in output för sidinnehållet
$out .= "<main id='pages'>";

// Lägg till H1-rubriker ovanför sidorna.
//
// H1:n är inte bara en rubrik för läsaren. På högtrafikerade sidor skriver
// Google om <title> och hämtar då sin egen titel härifrån i stället — verifierat
// på 377 2026-08-02, där SERP:en visade "SVT Text TV 377 - Målservice" medan
// taggen sa "377 - SVT Text TV". Se todos/09. På de sidorna är alltså den här
// strängen den enda titel vi faktiskt styr över.
//
// TODO: header.php har en mycket större whitelist + blockbaserad fallback för
// meta-taggar. På sikt bör de två filerna källa samma metadata istället för att
// duplicera. Tills dess fyller sr-only-fallbacken (nedan) ut för de sidor som
// inte finns i tabellen.
$headline = null;

// isset-koll: api/get_html laddar den här vyn utan $pagedescription, vilket gav
// en PHP-varning rakt in i JSON-svaret till apparna. Se K3 i #08.
if ($is_startpage) {

	$headline = 'SVT Text TV - Nyheter och Sportresultat';

} else if (isset($pagenum) && $pagenum) {

	// Sidnummer => ämne. Bara sidor med stabil identitet enligt SVT:s numrering —
	// efemära event-/tomsidor lämnas medvetet till sr-only-fallbacken, samma
	// avgränsning som #04 Fas 1 gjorde för meta-taggarna.
	//
	// Nyckeln matchas med isset(), så sammansatta $pagenum ("101-103", "100,300")
	// faller igenom till fallbacken precis som med den tidigare if-kedjan.
	// 336-571 flyttades hit 2026-08-02 när <h1>-taggarna i texttv_page_text
	// sänktes till <h2> (de gav dubbla rubriker). Utan posterna hade sidorna
	// fallit tillbaka på den generiska sr-only-rubriken, dvs blivit sämre —
	// och H1:n är det Google skriver sina titlar från. Se todos/11.
	//
	// 200, 202 och 398 är medvetet utelämnade: 200 meddelar att text-tv:s
	// ekonomisidor lagts ner (fryst sedan 2024-06) och 202 hörde till samma
	// del, medan 398 bara är en hänvisningsrad. Där är den generiska
	// fallbacken ärligare än ett ämnesord.
	$arr_page_headlines = array(
		101 => "Inrikesnyheter",
		102 => "Inrikesnyheter",
		103 => "Inrikesnyheter",
		104 => "Utrikesnyheter",
		105 => "Utrikesnyheter",
		330 => "Resultatbörsen",
		336 => "Premier League",
		358 => "SHL tabell",
		360 => "Ishockey Allsvenskan",
		376 => "Målserviceindex",
		377 => "Målservice och målresultat",
		378 => "Målservice utländska ligor",
		379 => "Matchfakta",
		551 => "Stryktipset",
		571 => "V75-resultat",
	);

	if ( isset($arr_page_headlines[$pagenum]) ) {
		$headline = sprintf(
			"SVT Text TV %d - %s",
			(int) $pagenum,
			$arr_page_headlines[$pagenum]
		);
	}

}

if ($headline) {
	// Blått band i sidans bredd (tt-band, todo #18). Texten är oförändrad.
	$out .= sprintf(
		'<h1 class="tt-sidbredd tt-band pages-headline">%1$s</h1>',
		$headline
	);
} else if (isset($pagenum) && $pagenum) {
	// Fallback: sr-only h1 för crawlers + screenreaders på sidor utan visuell rubrik.
	$out .= sprintf(
		'<h1 class="sr-only">SVT Text TV %d</h1>',
		(int) $pagenum
	);
}

$out .= "<div>";

// Här skrivs själva sidorna ut, 
// en per li
$yesterday_links_html = "";
if (!empty($yesterday_links)) {
	$yesterday_links_html = $this->load->view('day_archive_link', [
		"yesterday_links" => $yesterday_links,
		"yesterday_prefix" => $yesterday_prefix,
		"yesterday_top" => isset($yesterday_top) ? $yesterday_top : [],
		"today_top" => isset($today_top) ? $today_top : [],
		"today_links" => isset($today_links) ? $today_links : [],
		"week_top" => isset($week_top) ? $week_top : [],
		"inline" => $is_startpage,
	], TRUE);
}

foreach ($arr_pages as $page_index => $one_page_obj) {
	$out .= $one_page_obj->get_output();
	// Startsidan visar sju sidor efter varandra. Gårdagens länkar direkt
	// efter den första (100), annars hamnar de fyra skärmar ner på mobil.
	if ($is_startpage && $page_index === 0 && $yesterday_links_html) {
		$out .= $yesterday_links_html;
	}
}

$out .= "</div>";
	
$out .= "</main>"; // #pages

// Arkivtext
$text_archive = "";

if ( true || $this->input->get("enable-share") ) {

	$text_archive .= "<div class='pageshare tt-sidbredd'>";
	
	// Om flera sidor, kunna länka till kombination
	if (sizeof($arr_pages)>1) {
		$arr_mutliple_archive_ids = array();
		foreach ($arr_pages as $one_page_obj) {
			//$text_archive .= $one_page_obj->get_permalink();
			$arr_mutliple_archive_ids[] = $one_page_obj->id;
		}
		
		$page_title_for_url = date("j M Y", $one_page_obj->date_updated_unix);

		$page_title_for_url = trim(strtolower($page_title_for_url));
		$page_title_for_url = url_title($page_title_for_url);	
		// Permalink för flera sidor
		$permalink = sprintf(
			'/%1$s/arkiv/%3$s/%2$s/',
			$pagenum, // 1 sidnummer
			implode(",", $arr_mutliple_archive_ids), // 2 id
			$page_title_for_url // 3 titel
		);
	
		#$text_archive .= sprintf('<p><strong>Länk för delning</strong>:<br><a href="%1$s">%1$s</a></p>', $permalink);
		#$data["page_permalink"] = $permalink;
	
	} else {
		
		#$text_archive .= sprintf('<p><strong>Länk för delning</strong>:<br><a href="%1$s">%1$s</a></p>', $arr_pages[0]->get_permalink());
		// $text_archive .= sprintf('<p><a href="/%1$d/arkiv">Arkiv med tidigare versioner av sida %1$d</a>.</p>', $page->num);
		#$data["page_permalink"] = $arr_pages[0]->get_permalink();
		
	}
	

	// Tid i cyan som metadata och "Dela sidan" som gul knapp, så att den
	// syns (todo #18, fler delningar 2026-10-18). Klassen
	// pageshare__sharebutton används av js/scripts.js.
	$text_archive .= sprintf('
		<p class="pageshare__updated">Uppdaterad <time datetime="%2$s">%1$s</time></p>
		<button type="button" class="pageshare__sharebutton">Dela sidan</button>
		',
		date("H:i", $one_page_obj->date_added_unix),
		date("c", $one_page_obj->date_added_unix)
	);

	$text_archive .= "</div>"; // .pageshare
	
}



// En sida som inte sänts på länge visar bara "ej i sändning"; tiden den
// "uppdaterades" säger då inget. Se not_broadcasted_notice.php.
if (!isset($not_broadcasted) || !$not_broadcasted || $not_broadcasted["page"]) {
	$out .= $text_archive;
}

// Dagens tidigare versioner av sportsidorna (todo #20). Sätts bara av
// Sida::visa(), inte av api/get_html som också laddar den här vyn.
if (!empty($today_versions)) {
	$num_label = (int) $arr_pages[0]->num;
	$items = array_map(function ($v) use ($num_label) {
		return sprintf(
			'<li><a href="%1$s" aria-label="%3$s">%2$s</a></li>',
			html_escape($v["permalink"]),
			html_escape($v["time"]),
			html_escape($num_label . " kl " . $v["time"])
		);
	}, $today_versions);
	$out .= '<nav class="tt-sidbredd tt-tidigare" aria-label="Tidigare versioner i dag">'
		. '<p class="tt-tidigare__rubrik">Tidigare i dag</p>'
		. '<ul class="tt-linkrow">' . implode("", $items) . '</ul>'
		. '</nav>';
}

// Länkar till gårdagen (todo #16), före annonsen. Sätts bara av Sida::visa(),
// inte av api/get_html som också laddar den här vyn.
if ($yesterday_links_html && !$is_startpage) {
	$out .= $yesterday_links_html;
}

// ad after text tv page, before "nyaste sidorna"
if ( $this->input->get("apiAppShare") ) {
	// no ads when generating sharing screenshot
} else {
	
	// AdSense-annons
	$out .= '
		<!-- texttv.nu efter sida -->
		<div class="ad ad--before-latest">
			<p class="ad-header">Fler nyheter efter annonsen</p>
			<ins class="adsbygoogle"
					style="display:block"
					data-ad-client="ca-pub-1689239266452655"
					data-ad-slot="8021374801"
					data-ad-format="horizontal"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
	';
	
}	
// end ad after text tv page

// Polishändelser som Brottsplatskartan kopplat till nyheten (todo #21).
// Efter annonsen, så att rutan med kartbild inte trycker ner den. Sätts
// bara av Sida::visa(), inte av api/get_html.
if (!empty($polis_handelser)) {
	$out .= $this->load->view('polis_handelser', ['polis_handelser' => $polis_handelser], TRUE);
}

// Mest läst efter annonsen och polisrutan (granskning 2026-10-22). Sätts
// bara av Sida::visa().
if (!empty($mest_last) && !$this->input->get("apiAppShare")) {
	$out .= $this->load->view('mest_last_under_sidan', ['mest_last' => $mest_last, 'placement' => 'sida'], TRUE);
}


echo $out;
