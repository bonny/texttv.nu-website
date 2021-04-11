<?php
/**
 * Bottom nav, fixed, ala app style
 */	
 
 
$showFavs = $this->input->get("showBottomFav");

?>
<nav class="MainNav">

	<div class="MainNav-item MainNav-item--start">
		<a href="/100/amp" class="MainNav-itemLink">
			<svg class="MainNav-itemIcon" fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
			    <path d="M0 0h24v24H0z" fill="none"/>
			</svg>
        	<span class="MainNav-itemText">Start</span>
		</a>
	</div>
	
	<?php 
	if ($showFavs) {
		?>
		<div class="MainNav-item MainNav-item--favs">
			<a href="/favoriter/amp" class="MainNav-itemLink">
				<svg class="MainNav-itemIcon" fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
				    <path d="M0 0h24v24H0z" fill="none"/>
				    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
				    <path d="M0 0h24v24H0z" fill="none"/>
				</svg>
	        	<span class="MainNav-itemText">Favoriter</span>
			</a>
		</div>
		<?php
	}
	?>

    <div class="MainNav-item MainNav--prev">
      <a href="/<?php echo $gotoPrevPageNum ?>/amp" class="MainNav-itemLink">
        <svg class="MainNav-itemIcon" fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
		    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
		    <path d="M0 0h24v24H0z" fill="none"/>
		</svg>
        <span class="MainNav-itemText">Föregående</span>
      </a>
    </div>

    <div class="MainNav-item MainNav-item--next">
    	<a href="/<?php echo $gotoNextPageNum ?>/amp" class="MainNav-itemLink">
        	<svg class="MainNav-itemIcon" fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
			    <path d="M0 0h24v24H0z" fill="none"/>
			</svg>
        	<span class="MainNav-itemText">Nästa</span>
        </a>
    </div>

    <div class="MainNav-item MainNav-item--menu">
    	<button class="MainNav-itemLink" on="tap:sidebar.toggle">
			<svg class="MainNav-itemIcon" fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			    <path d="M0 0h24v24H0z" fill="none"/>
			    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
			</svg>
        	<span class="MainNav-itemText">Meny</span>
        </button>
    </div>

</nav>
