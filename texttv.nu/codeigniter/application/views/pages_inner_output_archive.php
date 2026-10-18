<?php

$arr_pages = $pages;
$text = "";
	
// Överst: ett band som säger att sidan är arkiverad och när, med länk till
// den levande sidan (todo #18). Delningsbilderna (apiAppShare) behåller sin
// gamla överdel, eftersom den syns i bilderna som delas.
$arkiv_band = function ($unix, $live_url, $live_label) {
	// Gult band "ÄLDRE VERSION" och tiden i klartext, så att ingen tror att
	// det är sidan som den ser ut nu.
	return sprintf(
		'<div class="tt-sidbredd tt-arkiv">
			<p class="tt-arkiv__band">ÄLDRE VERSION</p>
			<p class="tt-arkiv__text">Så här såg sidan ut <time datetime="%1$s">%2$s</time>.</p>
			<p class="tt-linkrow"><a href="%3$s">%4$s →</a></p>
		</div>',
		date("c", $unix),
		html_escape(date_sv('l j F Y', $unix) . ' kl ' . date('H:i', $unix)),
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

// Sidornas html byggs först, så att vi vet om den har en egen <h1>.
$pages_html = "";
foreach ($arr_pages as $one_page) {
	$pages_html .= $one_page->get_output();
}

// Nyhetsarkiven saknade <h1> (granskning 2026-10-18). Rubriken för
// skärmläsare och sökmotorer, samma grepp som på de levande sidorna.
if (stripos($pages_html, "<h1") === false && !$this->input->get("apiAppShare")) {
	$rubrik = trim((string) $arr_pages[0]->title);
	$text .= sprintf(
		'<h1 class="sr-only">%s</h1>',
		html_escape($rubrik !== "" && sizeof($arr_pages) == 1 ? $rubrik : sprintf("SVT Text TV %s", $pagenum))
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
$this->output->append_output($pages_html);
$this->output->append_output("</ul>");

// Polishändelser som Brottsplatskartan kopplat till nyheten (todo #21).
if (!empty($polis_handelser) && !$this->input->get("apiAppShare")) {
	$this->output->append_output($this->load->view('polis_handelser', ['polis_handelser' => $polis_handelser], TRUE));
}

	if ( ! $this->input->get("apiAppShare") ) {
		
		// Dela även härifrån: hit kommer den som fått en delad länk (todo
		// om fler delningar 2026-10-18). Samma knapp som på de levande sidorna,
		// js/scripts.js hämtar permalänken.
		$this->output->append_output(
			'<div class="pageshare tt-sidbredd"><button type="button" class="pageshare__sharebutton">Dela sidan</button></div>'
		);

		// Längst ner: tillbaka till den levande sidan.
		$this->output->append_output( sprintf(
			'<p class="tt-sidbredd tt-arkiv__slut">Du tittar på en arkiverad version. <a class="tt-link" href="%2$s">SVT Text TV %1$s just nu →</a></p>',
			html_escape( sizeof($arr_pages) == 1 ? (string) (int) $page->num : $pagenum ),
			html_escape( site_url( sizeof($arr_pages) == 1 ? $page->num : $pagenum ) )
		) );

	}


$this->output->append_output("</div>");

