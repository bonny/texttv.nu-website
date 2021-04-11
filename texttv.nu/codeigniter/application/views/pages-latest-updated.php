<?php

if ( $this->input->get("apiAppShare") ) {
	return;
}

// Länkar till nyaste sidorna
$out_latest = "";

$result = get_latest_updated_pages(100, 200);

$tmpl_latest = '<li><small class="latest-pages-time">%2$s</small> <a class="latest-pages-title" href="/%1$d">%3$s</a></li>';

$arr_outputed_page_nums = array();
$out_latest .= "<div class='latest-pages'>";
$out_latest .= "<section class='latest-pages-list latest-pages-list--news'>";
$out_latest .= "<h2>Senaste nyheterna</h2>";
$out_latest .= "<ul>";
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

$result = get_latest_updated_pages(300, 400);

$out_latest .= "<section class='latest-pages-list latest-pages-list--sport'>";
$out_latest .= "<h2>Senaste sportnyheterna</h2>";
$out_latest .= "<ul>";
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
