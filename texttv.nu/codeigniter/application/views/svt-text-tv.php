
<br><br><br><br>
<h1>SVT Text TV</h1>

<p>Nyheter och sport är bäst på text-tv!</p>

<hr>

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

	$teaser = empty( $vals["teaser"] ) ? "" : "<div class='teaser'>{$vals["teaser"]}</div>";

	printf('
		
		<section>
		
			<h1>%1$s</h1>
			
			%3$s
		
			<ul>%2$s</ul>
		
		</section>
		
		', $vals["page"]->get_page_title(),
		$vals["page"]->get_output(),
		$teaser // 3
	);

}	

