
<?php
// Landningssida med de viktigaste sidorna efter varandra. En h1 som band,
// en h2 per sida (todo #18; förut var varje sidrubrik en h1).
?>
<main id="pages" class="svt-text-tv-sidor">
<h1 class="tt-sidbredd tt-band pages-headline">SVT Text TV</h1>

<p class="tt-sidbredd tt-dag__intro">Nyheter och sport är bäst på Text TV.</p>

<?php

$arr_pages = [
	[
		"page" => new Texttv_page(100),
		"teaser" => "De senaste och de viktigaste nyheterna hittar du alltid på sidan 100."
	],
	["page" => new Texttv_page(101)],
	["page" => new Texttv_page(102)],
	["page" => new Texttv_page(103)],
	["page" => new Texttv_page(104)],
	[
		"page" => new Texttv_page(300),
		"teaser" => "Sportnyheter och sportresultat hittar du alltid på sidan 300."
	],
	[
		"page" => new Texttv_page(377),
		"teaser" => "Klassiska sidan 377 innehåller alltid dom senaste sportresultaten!"
	],
];

foreach ( $arr_pages as $vals ) {

	$teaser = empty( $vals["teaser"] ) ? "" : "<p class='tt-sidbredd teaser'>{$vals["teaser"]}</p>";

	printf('
		<section class="svt-text-tv-sida">
			<h2 class="tt-sidbredd tt-band">%4$d %1$s</h2>
			%3$s
			<div>%2$s</div>
		</section>
		', html_escape($vals["page"]->get_page_title()),
		$vals["page"]->get_output(),
		$teaser, // 3
		// Sidnumret först: 101–103 heter alla "Inrikes" (todo #19).
		(int) $vals["page"]->num // 4
	);

}
?>
</main>
