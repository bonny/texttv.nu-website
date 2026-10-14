<?php

/*
	404-sidan, ritad som en Text-TV-sida med samma CSS-klasser som riktiga
	sidor (.root, .line, färgklasserna). Laddas av show_texttv_404() i
	helpers/texttv_helper.php.
*/

// En Text-TV-rad är 40 tecken. Fyller ut med mellanslag utifrån den synliga
// texten, så att länkarna inte räknas med. (mb_str_pad finns först i PHP 8.3.)
$line = function ($html, $classes = "bgBl W") {
	$visible = mb_strlen(html_entity_decode(strip_tags($html), ENT_QUOTES, "UTF-8"));
	$padding = str_repeat(" ", max(0, 40 - $visible));
	return sprintf(
		'<span class="line"><span class="%1$s">%2$s%3$s</span></span>' . "\n",
		$classes,
		$html,
		$padding
	);
};

// Sidnummer, och ev. sidnamn, som en länk.
$link = function ($num, $name = "") {
	return sprintf(
		'<a href="/%1$d">%1$d%2$s</a>',
		(int) $num,
		$name === "" ? "" : " " . htmlspecialchars($name, ENT_QUOTES, "UTF-8")
	);
};

$date = date_sv("l d M Y");
$toprow = sprintf(
	'<span class="line toprow"><span class="bgBl">   </span><span class="bgBl W">404 </span><span class="bgBl Y">SVT Text </span><span class="bgBl W">%s</span></span>' . "\n",
	// 3 + 4 + 9 tecken före datumet, så datumet fylls till 24. Räknas med
	// mb_strlen: str_pad räknar byte, och "lördag" och "söndag" har ett ö.
	$date . str_repeat(" ", max(0, 24 - mb_strlen($date)))
);

$rows = [
	$toprow,
	$line("            SIDAN FINNS INTE", "bgB W"),
	$line(""),
	$line(" Sidan finns inte, eller så har", "bgBl W"),
	$line(" adressen blivit fel.", "bgBl W"),
	$line(""),
	$line(" Prova någon av de här:", "bgBl Y"),
	$line(""),
	$line(" " . $link(100, "Nyheter") . "          " . $link(300, "Sport"), "bgBl G"),
	$line(" " . $link(377, "Målservice") . "       " . $link(330, "Resultat"), "bgBl G"),
	$line(" " . $link(401, "Väder") . "            " . $link(600, "På TV"), "bgBl G"),
	$line(" " . $link(101, "Inrikes") . "          " . $link(104, "Utrikes"), "bgBl G"),
	$line(""),
	$line(" " . '<a href="/nyheter/igar">Gårdagens nyheter</a>', "bgBl G"),
	$line(""),
	$line(" Eller skriv ett sidnummer i rutan", "bgBl W"),
	$line(" Gå till sida högst upp.", "bgBl W"),
	$line(""),
	$line(" " . '<a href="/">Startsidan</a>' . " * Sport " . $link(300) . " * Väder " . $link(401), "bgB W"),
];

?>
<main id="pages" class="page-404">
	<h1 class="sr-only">Sidan finns inte (felkod 404)</h1>
	<div>
		<div class="one-page TextTVPage li">
			<div class="inpage-pages ul subpage-count-1">
				<div class="li">
					<div class="root"><?php echo implode("", $rows) ?></div>
				</div>
			</div>
		</div>
	</div>
</main>
