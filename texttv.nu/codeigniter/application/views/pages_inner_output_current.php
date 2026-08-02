<?php

/*
	View = innehållet på vanliag sidor med texttv-innehåll
	(inte arkiv etc.)
*/

$arr_pages = $pages;
$out = "";


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
if (isset($pagedescription) && $pagedescription === 'startpage') {

	$headline = 'SVT Text TV - Nyheter och Sportresultat';

} else if (isset($pagenum) && $pagenum) {

	// Sidnummer => ämne. Bara sidor med stabil identitet enligt SVT:s numrering —
	// efemära event-/tomsidor lämnas medvetet till sr-only-fallbacken, samma
	// avgränsning som #04 Fas 1 gjorde för meta-taggarna.
	//
	// Nyckeln matchas med isset(), så sammansatta $pagenum ("101-103", "100,300")
	// faller igenom till fallbacken precis som med den tidigare if-kedjan.
	$arr_page_headlines = array(
		101 => "Inrikesnyheter",
		102 => "Inrikesnyheter",
		103 => "Inrikesnyheter",
		104 => "Utrikesnyheter",
		105 => "Utrikesnyheter",
		330 => "Resultatbörsen",
		376 => "Målserviceindex",
		377 => "Målservice och målresultat",
		378 => "Målservice utländska ligor",
		379 => "Matchfakta",
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
	$out .= sprintf(
		'<h1>%1$s</h1>',
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
foreach ($arr_pages as $one_page_obj) {
	$out .= $one_page_obj->get_output();
}

$out .= "</div>";
	
$out .= "</main>"; // #pages

// Arkivtext
$text_archive = "";

if ( true || $this->input->get("enable-share") ) {

	$text_archive .= "<div class='pageshare'>";
	$text_archive .= "<div class='pageshare__inner'>";
	
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
	

	$dateUpdatedHuman = date("H:i", $one_page_obj->date_added_unix);
	
	$text_archive .= sprintf('
		<p class="pageshare__col pageshare__col--1">Sidan uppdaterad %1$s</p>
		<p class="pageshare__col pageshare__col--2">
			<button class="pageshare__sharebutton"><i class="icon-share"></i> Dela</button>
		</p>
		', 
		$dateUpdatedHuman
	);
	

	$text_archive .= "</div>"; // inner
	$text_archive .= "</div>"; // outer
	
}



$out .= $text_archive;


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


echo $out;
