<?php

/*
	Länkar från en levande sida till gårdagen: sidans dagsarkiv (/377/igar)
	och avdelningens dagslista (/nyheter/igar, /sport/igar). Startsidan får
	alla. Listan byggs av Sida::visa(). Todo #16.
*/

$links = array_map(function ($link) {
	return sprintf('<a href="%1$s">%2$s</a>', html_escape($link[0]), html_escape($link[1]));
}, $yesterday_links);

?>
<p class="day-archive-link">
	<?php echo html_escape($yesterday_prefix) ?> <?php echo implode(" · ", $links) ?>
</p>
