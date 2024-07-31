<?php

/**
 * Global navigation: home buttons, prev, next, etc.
 */
 
 // no controls on share pages
 if ( $this->input->get("apiAppShare") ) {
	 return;
 }

$gotoDefaultPageNum = 100;
if ("/" == current_url()) {
	$gotoDefaultPageNum = 100;
} else if (isset($page->num)) {
	$gotoDefaultPageNum = $page->num;
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
<nav class="controls borderbox">
	
	<div class="controls-promo">
        <a class="controls-promo-item controls-promo-item--ios" href="https://itunes.apple.com/se/app/texttv.nu/id607998045?mt=8">Ladda hem Text TV-appen</a>
        <a class="controls-promo-item controls-promo-item--android" href="https://play.google.com/store/apps/details?id=com.mufflify.TextTVnu&hl=sv">Ladda hem Text TV-appen</a>
    </div>

	<div class="controls-topnav clearfix hidden">
		<a href="/" class="controls-topnav-logo">
			<svg viewBox="0 0 350 350" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><rect fill="#008EFF" width="350" height="350" rx="40"/><path fill="#0049FC" d="M76 75h50v50H76z"/><path fill="#57C6EB" d="M151 75h50v50h-50z"/><path fill="#E5DB2B" d="M151 150h50v50h-50z"/><path fill="#F3A633" d="M151 225h50v50h-50z"/><path fill="#80F200" d="M226 75h50v50h-50z"/></g></svg>
			<span>TextTV.nu</span>
		</a>
		<div class="controls-topnav-search">
			<form action="/" method="get" class="controls-topnav-form">
				<label for="search-input" class="sr-only">Gå till sida</label>
				<input 
					id="search-input"
					class="controls-topnav-search-input"
					type="number" 
					name="number" 
					value="" 
					placeholder="Gå till sida"
					max=999
					min=100
					>
				<button type="submit">Gå</button>
			</form>
		</div>
	</div>

	<ul>
		<li class="nav-menu">
			<a class="btn" href="/">
				<span class="icon icon-reorder"></span>
				Meny
			</a>
		</li>	
		<li class="nav-home">
			<a class="btn" href="/">
				<span class="icon icon-home"></span>
				100
			</a>
		</li>
		<li class="go-page">
			<form action="/" method="get" id="frmGoPage">
				<input name="number" type="number" value="<?php echo $gotoDefaultPageNum ?>" id="goPageNum"><input type="submit" value="Gå">
			</form>
		</li>

		<?php
		if ( ! empty( $gotoPrevPageNum ) ) {
			?>
			<li class="nav-prev">
				<a class="btn" href="/<?php echo $gotoPrevPageNum ?>">
					<span class="icon icon-arrow-left"></span>
					<?php echo $gotoPrevPageNum ?>
				</a>
			</li>
			<?php
		}
		?>
		<?php
		if ( ! empty( $gotoNextPageNum ) ) {
			?>
			<li class="nav-next">
				<a class="btn" href="/<?php echo $gotoNextPageNum ?>">
					<?php echo $gotoNextPageNum ?>
					<span class="icon icon-arrow-right"></span>
				</a>
			</li>
			<?php
		}
		?>
		
	</ul>
</nav>
