<?php

/*
	View = innehållet på vanliag sidor med texttv-innehåll
	(inte arkiv etc.)
*/

$arr_pages = $pages;
$out = "";


// Lägg in output för sidinnehållet
$out .= "<section id='pages'>";

// Lägg till H1-rubriker ovanför sidorna.
$headline = null;
// if (isset($_GET['apa'])) {
// 	#print_r($arr_pages);
// 	print_r($page);
// 	print_r($pages);
// 	print_r($pagenum);
// 	print_r($pagedescription);
// 	exit;
// }

if ($pagedescription === 'startpage') {
	$headline = 'SVT Text TV – Nyheter och Sportresultat';
} else if ($pagenum == 377) {
	$headline = 'SVT Text TV 377 – Målservice och målresultat';
} else if ($pagenum == 101 || $pagenum == 102 || $pagenum == 103) {
	$headline = "SVT Text TV $pagenum – Inrikesnyheter";
} else if ( in_array($pagenum, [104,105] )) {
	$headline = "SVT Text TV $pagenum – Utrikesnyheter";
}

if ($headline) {
	$out .= sprintf(
		'<h1>%1$s</h1>',
		$headline
	);	
}

$out .= "<ul>";

// Här skrivs själva sidorna ut, 
// en per li
foreach ($arr_pages as $one_page_obj) {
	$out .= $one_page_obj->get_output();
}

$out .= "</ul>";
	
$out .= "</section>"; // #pages

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
