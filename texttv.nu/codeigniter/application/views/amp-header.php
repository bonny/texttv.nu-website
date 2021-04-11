<?php
/**
 * Header with texttv.nu-logo
 */	
 
?>
<header class="Header">
  
  <div class="brand-logo">
      <a href="/100/amp" class="Header-homelink">
		  <svg class="Header-logo" viewBox="0 0 350 350" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><rect fill="#008EFF" width="350" height="350" rx="40"></rect><path fill="#0049FC" d="M76 75h50v50H76z"></path><path fill="#57C6EB" d="M151 75h50v50h-50z"></path><path fill="#E5DB2B" d="M151 150h50v50h-50z"></path><path fill="#F3A633" d="M151 225h50v50h-50z"></path><path fill="#80F200" d="M226 75h50v50h-50z"></path></g></svg>
	      <span class="Header-tagline">TextTV.nu</span>
	  </a>
  </div>
  
  <div class="Header-gotoPage">
	  <form method="get" id="headerGotoPageForm" class="Header-gotoPage-form" action="https://texttv.nu/api/amp_form_goto_page" action-xhr="https://texttv.nu/api/amp_form_goto_page" target="_top">
		  <input type="number" name="sida" class="Header-gotoPage-input" placeholder="Gå till sida …" min="100" max="999"
			  on="change:headerGotoPageForm.submit"
			  >
	  </form>
  </div>
  
  <!--   <button on="tap:sidebar.toggle" class="Header-showSidebar">☰</button> -->
  
  <?php
  /*
  <div class="navigation" data-z-depth="2">
    <div class="prev">
      <a href="/<?php echo $gotoPrevPageNum ?>/amp">
        <svg fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
		    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
		    <path d="M0 0h24v24H0z" fill="none"/>
		</svg>
          Föregående sida
      </a>
    </div>
    <div class="next">
    	<a href="/<?php echo $gotoNextPageNum ?>/amp">
        	Nästa sida
        	<svg fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
			    <path d="M0 0h24v24H0z" fill="none"/>
			</svg>
        </a>
    </div>
  </div>
  */
  ?>
</header>
