<?php

/**
 * Global navigation: home buttons, prev, next, etc.
 *
 * Text-TV-ramen (todo #18): svart, i sidans bredd. Rad 1 logga och "Sidor",
 * rad 2 föregående sida, "Gå till sida" och nästa sida.
 * JS-krokar i js/scripts.js: nav.controls, li.nav-menu a (sidomenyn) och
 * .controls-topnav-search-input/.controls-topnav-form (går till sidan
 * när tre siffror är skrivna).
 */

 // no controls on share pages
 if ( $this->input->get("apiAppShare") ) {
	 return;
 }

$gotoNextPageNum = null;
$gotoPrevPageNum = null;
if ( "/" == current_url()) {
	$gotoNextPageNum = 101;
	$gotoPrevPageNum = 100;
} else if ( isset($page) ) {
	$gotoNextPageNum = $page->next_page;
	$gotoPrevPageNum = $page->prev_page;
}
?>
<nav class="controls" aria-label="Sidnavigering">
	<div class="controls__inner tt-sidbredd">
		<div class="controls__row">
			<a href="/" class="controls__home">
				<svg viewBox="0 0 350 350" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path fill="#00f" d="M76 75h50v50H76z"/><path fill="#00c8ee" d="M151 75h50v50h-50z"/><path fill="#fcfe01" d="M151 150h50v50h-50z"/><path fill="#fc0304" d="M151 225h50v50h-50z"/><path fill="#0f0" d="M226 75h50v50h-50z"/></g></svg>
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
					pattern="[0-9]*"
					maxlength="3"
					autocomplete="off"
					name="number"
					value=""
					placeholder="Gå till sida"
					>
			</form>
			<?php if ( ! empty( $gotoNextPageNum ) ) { ?>
				<a class="tt-link controls__next" href="/<?php echo (int) $gotoNextPageNum ?>" aria-label="Nästa sida, <?php echo (int) $gotoNextPageNum ?>"><?php echo (int) $gotoNextPageNum ?> →</a>
			<?php } else { ?>
				<span></span>
			<?php } ?>
		</div>
	</div>
</nav>
