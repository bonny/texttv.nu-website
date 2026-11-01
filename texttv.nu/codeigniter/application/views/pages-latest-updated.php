<?php

if ( $this->input->get("apiAppShare") ) {
	return;
}

// Länkar till nyaste sidorna
$out_latest = "";

$result = get_latest_updated_pages(100, 200);

// Tid i cyan och rubrik som vit länk, som tt-list i css/tt.css (todo #18).
$tmpl_latest = '<li><span class="tt-list__meta">%2$s</span> <a href="/%1$d">%3$s</a></li>';

$arr_outputed_page_nums = array();
$out_latest .= "<div class='latest-pages'>";
$out_latest .= "<section class='latest-pages-list latest-pages-list--news tt-sidbredd'>";
$out_latest .= "<h2 class='tt-h2'><span>Senaste nyheterna</span></h2>";
$out_latest .= "<ul class='tt-list'>";

foreach ( $result->result() as $row ) {

	if ( in_array($row->page_num, $arr_outputed_page_nums)) {
		continue;
	}

	$out_latest .= sprintf($tmpl_latest,
		$row->page_num,
		$row->date_added_formatted,
		$row->title
	);
	
	$arr_outputed_page_nums[] = $row->page_num;

}
$out_latest .= "</ul>";
$out_latest .= "</section>"; // .latest-pages--news

// Bara nyhetssidorna 300–329, som /api/last_updated/sport. Resultatsidorna
// har en egen lista nedan.
$result = get_latest_updated_pages(300, 329);

$out_latest .= "<section class='latest-pages-list latest-pages-list--sport tt-sidbredd'>";
$out_latest .= "<h2 class='tt-h2'><span>Senaste sportnyheterna</span></h2>";
$out_latest .= "<ul class='tt-list'>";
foreach ( $result->result() as $row ) {

	if ( in_array($row->page_num, $arr_outputed_page_nums)) {
		continue;
	}

	$out_latest .= sprintf($tmpl_latest,
		$row->page_num,
		$row->date_added_formatted,
		$row->title
	);
	
	$arr_outputed_page_nums[] = $row->page_num;

}
$out_latest .= "</ul>";

// Resultat- och tabellsidor som uppdaterats, en rad per sida.
$result = get_latest_result_pages();
if ($result->num_rows()) {
	$out_latest .= "<h3 class='latest-pages-list__results-heading'>Nya resultat</h3>";
	$out_latest .= "<ul class='tt-list'>";
	foreach ( $result->result() as $row ) {
		$out_latest .= sprintf(
			'<li><span class="tt-list__meta">%2$s</span> <a href="/%1$d">%3$s <span class="tt-list__sidnr">%1$d</span></a></li>',
			$row->page_num,
			$row->date_added_formatted,
			htmlspecialchars(tt_resultatsida_namn($row), ENT_QUOTES, 'UTF-8')
		);
	}
	$out_latest .= "</ul>";
}
$out_latest .= "</section>"; // .latest-pages--sport
$out_latest .= "</div>"; // .latest-pages


// ad after "latest"-section
// removed 28 Jan 2017 because we added an ad at top instead
/*
if ( $this->input->get("apiAppShare") ) {
	// no ads when generating sharing screenshot
} else {

	$out_latest .= '
		<!-- texttv.nu mellan sidor -->
		<div class="ad ad--below">
			<p class="ad-header">Annons</p>
			<ins class="adsbygoogle"
			     style="display:block"
			     data-ad-client="ca-pub-1689239266452655"
			     data-ad-slot="4600784405"
			     data-ad-format="auto"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
	';
	
}
*/
// end ad after

echo $out_latest;
