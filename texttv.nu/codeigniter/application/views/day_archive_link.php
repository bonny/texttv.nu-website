<?php

/*
	Länk från den levande sidan till dagsarkivet. Laddas av Sida::visa() för
	sidor i Sida::DAY_ARCHIVE. Todo #16.
*/

$num = (int) $pages[0]->num;

?>
<p class="day-archive-link">
	<a href="/<?php echo $num ?>/igar">← <?php echo $num ?> igår</a>
</p>
