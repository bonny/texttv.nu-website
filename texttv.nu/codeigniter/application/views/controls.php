<?php

/**
 * Global navigation: home buttons, prev, next, etc.
 *
 * Text-TV-ramen (todo #18): svart, i sidans bredd. Rad 1 logga och "Sidor",
 * rad 2 föregående sida, "Gå till sida" och nästa sida.
 * JS-krokar i js/scripts.js: nav.controls, li.nav-menu a (sidomenyn) och
 * .controls-topnav-search-input/.controls-topnav-form (går till sidan
 * när tre siffror är skrivna). Förslagen under fältet: js/tt-sok.js.
 */

 // no controls on share pages
 if ( $this->input->get("apiAppShare") ) {
	 return;
 }

// Sidorna som förslagen under "Gå till sida" söker bland (js/tt-sok.js).
$sok_data = include __DIR__ . "/sidlista_data.php";
$sok_sidor = [];
foreach ($sok_data["lista"] as $item) {
	$sok_sidor[] = [$item[0], $item[1], $item[2]];
	foreach ($item[3] as $sub) {
		$sok_sidor[] = $sub;
	}
}
foreach ($sok_data["fler"] as $item) {
	$sok_sidor[] = $item;
}
$sok_json = [
	"sidor" => array_map(function ($s) {
		// num: första sidan, för sökning på siffror. visa: som i sidlistan.
		return ["url" => $s[0], "namn" => $s[1], "num" => $s[2] ? substr($s[2], 0, 3) : null, "visa" => $s[2]];
	}, $sok_sidor),
	"populara" => $sok_data["populara"],
];

$gotoNextPageNum = null;
$gotoPrevPageNum = null;
if ( "/" == current_url()) {
	$gotoNextPageNum = 101;
	$gotoPrevPageNum = 100;
} else if ( isset($pagedescription) && $pagedescription === "startpage" ) {
	// Startsidan visar flera sidor men pilarna går till 100 och 101.
	$gotoPrevPageNum = 100;
	$gotoNextPageNum = 101;
} else if ( isset($pages) && is_array($pages) && sizeof($pages) > 1 ) {
	// Flera sidor (101-103, 100,300): föregående till första sidan, nästa
	// efter sista. $page är bara den sist laddade (todo #19).
	$gotoPrevPageNum = $pages[0]->prev_page;
	$gotoNextPageNum = $pages[sizeof($pages) - 1]->next_page;
} else if ( isset($page) ) {
	$gotoNextPageNum = $page->next_page;
	$gotoPrevPageNum = $page->prev_page;
}
// En länk till en sida man redan står på (999 → 999, ← 100 på 100,300)
// visas inte.
if ( isset($pages) && is_array($pages) && ! ( isset($pagedescription) && $pagedescription === "startpage" ) ) {
	$visade = array_map(function ($p) {
		return (int) $p->num;
	}, $pages);
	if ( in_array((int) $gotoNextPageNum, $visade, true) ) {
		$gotoNextPageNum = null;
	}
	if ( in_array((int) $gotoPrevPageNum, $visade, true) ) {
		$gotoPrevPageNum = null;
	}
}
?>
<nav class="controls" aria-label="Sidnavigering">
	<div class="controls__inner tt-sidbredd">
		<script type="application/json" id="tt-sok-data"><?php echo json_encode($sok_json, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
		<div class="controls__row">
			<a href="/" class="controls__home">
				<svg viewBox="0 0 350 350" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><rect fill="#008EFF" width="350" height="350" rx="40"/><path fill="#0049FC" d="M76 75h50v50H76z"/><path fill="#57C6EB" d="M151 75h50v50h-50z"/><path fill="#E5DB2B" d="M151 150h50v50h-50z"/><path fill="#F3A633" d="M151 225h50v50h-50z"/><path fill="#80F200" d="M226 75h50v50h-50z"/></g></svg>
				TextTV.nu
			</a>
			<ul class="controls__menu">
				<li class="nav-menu"><a class="tt-link" href="/">Sidor ≡</a></li>
			</ul>
		</div>
		<div class="controls__row controls__row--pages">
			<?php if ( ! empty( $gotoPrevPageNum ) ) { ?>
				<a class="tt-link controls__prev" href="/<?php echo (int) $gotoPrevPageNum ?>" aria-label="Föregående sida, <?php echo (int) $gotoPrevPageNum ?>">← <?php echo (int) $gotoPrevPageNum ?></a>
			<?php } else { ?>
				<span></span>
			<?php } ?>
			<form action="/" method="get" class="controls-topnav-form">
				<label for="search-input" class="sr-only">Gå till sida</label>
				<input
					id="search-input"
					class="controls-topnav-search-input"
					type="text"
					inputmode="numeric"
					maxlength="30"
					autocomplete="off"
					role="combobox"
					aria-autocomplete="list"
					aria-expanded="false"
					aria-controls="tt-sok-forslag"
					name="number"
					value=""
					placeholder="Gå till sida"
					>
			</form>
			<div id="tt-sok-forslag" class="tt-sok" role="listbox" aria-label="Förslag" hidden></div>
			<?php if ( ! empty( $gotoNextPageNum ) ) { ?>
				<a class="tt-link controls__next" href="/<?php echo (int) $gotoNextPageNum ?>" aria-label="Nästa sida, <?php echo (int) $gotoNextPageNum ?>"><?php echo (int) $gotoNextPageNum ?> →</a>
			<?php } else { ?>
				<span></span>
			<?php } ?>
		</div>
	</div>
</nav>
