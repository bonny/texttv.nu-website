<?php
/*
	Flikar mellan målservicesidorna, överst på dem. Folk går igenom 377, 378,
	379 ... i tur och ordning under matchkvällarna; det här går fortare än
	att bläddra en sida i taget. Sidorna står i Texttv_page::MALSERVICE_TABS.
*/
if (!isset($pages) || sizeof($pages) != 1) {
	return;
}
$num = (int) $pages[0]->num;
if (!isset(Texttv_page::MALSERVICE_TABS[$num])) {
	return;
}
?>
<nav class="tt-sidbredd tt-linkrow tt-flikar" aria-label="Målservice">
	<?php foreach (Texttv_page::MALSERVICE_TABS as $tab_num => $name) { ?>
		<a href="/<?php echo $tab_num ?>" aria-label="<?php echo html_escape("$tab_num $name") ?>"<?php echo $tab_num === $num ? ' aria-current="page"' : "" ?>><?php echo $tab_num ?></a>
	<?php } ?>
</nav>
