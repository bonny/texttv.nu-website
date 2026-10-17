<?php

$arr_pages = $pages;
$text = "";
	
// Överst: ett band som säger att sidan är arkiverad och när, med länk till
// den levande sidan (todo #18). Delningsbilderna (apiAppShare) behåller sin
// gamla överdel, eftersom den syns i bilderna som delas.
$arkiv_band = function ($unix, $live_url, $live_label) {
	return sprintf(
		'<div class="tt-sidbredd tt-arkiv">
			<p class="tt-band tt-arkiv__band"><span class="tt-arkiv__etikett">ARKIV</span> <time datetime="%1$s">%2$s</time></p>
			<p class="tt-linkrow"><a href="%3$s">%4$s →</a></p>
		</div>',
		date("c", $unix),
		html_escape(date_sv('D j M Y H:i', $unix)),
		html_escape($live_url),
		html_escape($live_label)
	);
};

if (sizeof($arr_pages) == 1) {

	// Om en sida
	if ( ! $this->input->get("apiAppShare") ) {
		$text .= $arkiv_band(
			$page->date_updated_unix,
			site_url( $page->num ),
			sprintf("SVT Text TV %d just nu", (int) $page->num)
		);
	}

} elseif ( $this->input->get("apiAppShare") ) {

	// Flera sidor i en delningsbild
	$text .= sprintf(
		'
		<div class="introtext alert">
			<p class="AppShareOnly">Delad från TextTV.NU</p>
			<p>TextTV sidorna %2$s<br>%1$s
			<p class="AppShareOnly sharedFrom">Delad från TextTV.NU
			<br><em>Mobilanpassad Text TV med smarta funktioner</em></p>
		</div>
		',
		// Fre 13 jan 2023, 21:35
		ucfirst( date_sv('D j M Y, H:i', $arr_pages[0]->date_updated_unix) ),
		$pagenum
	);

} else {

	// Flera sidor
	// Den senaste av sidornas tider, inte bara den första sidans.
	$text .= $arkiv_band(
		max(array_map(function ($p) { return (int) $p->date_updated_unix; }, $arr_pages)),
		site_url( $pagenum ),
		sprintf("SVT Text TV %s just nu", $pagenum)
	);

}

$this->output->append_output($text);
$this->output->append_output("<div id='pages'>");

$banner_output = <<<EOT
	<!-- TextTV - arkivsida -->
	<p class="ad-header">Annons</p>
	<ins class="adsbygoogle"
	     style="display:inline-block;width:100%;height:100px"
	     data-ad-client="ca-pub-1689239266452655"
	     data-ad-slot="8439735600"></ins>
	<script>
	(adsbygoogle = window.adsbygoogle || []).push({});
	</script>
EOT;

if ( $this->input->get("apiAppShare") ) {
	// no ad on screenshots
} else {
	$this->output->append_output( $banner_output );
}

$this->output->append_output("<ul>");
foreach ($arr_pages as $one_page) {
	$this->output->append_output($one_page->get_output());
}
$this->output->append_output("</ul>");

	if ( ! $this->input->get("apiAppShare") ) {
		
		// Längst ner: tillbaka till den levande sidan.
		$this->output->append_output( sprintf(
			'<p class="tt-sidbredd tt-arkiv__slut">Du tittar på en arkiverad version. <a class="tt-link" href="%2$s">SVT Text TV %1$s just nu →</a></p>',
			html_escape( sizeof($arr_pages) == 1 ? (string) (int) $page->num : $pagenum ),
			html_escape( site_url( sizeof($arr_pages) == 1 ? $page->num : $pagenum ) )
		) );

	}


$this->output->append_output("</div>");

