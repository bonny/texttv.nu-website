<?php

/*
	View = innehållet på vanliga sidor med texttv-innehåll
	(inte arkiv etc.)
*/

$arr_pages = $pages;
$out = "";

// Lägg in output för sidinnehållet
$out .= "<section id='pages'>";

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

if (true || $this->input->get("enable-share")) {

	$text_archive .= "<div class='pageshare'>";
	$text_archive .= "<div class='pageshare__inner'>";

	$dateUpdatedHuman = date("H:i", $one_page_obj->date_added_unix);

	$text_archive .= sprintf(
		'
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
if ($this->input->get("apiAppShare")) {
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
