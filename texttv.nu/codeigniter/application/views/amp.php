<?php

/**
 * Main view for AMP version of pages
 * /100/amp
 * /100,101/amp
 * /100-102,300
 * and so on
 */
$canonical = null;

if (isset($is_archive) && $is_archive) {
	$canonical = $page_permalink;
} else {
	$canonical = "/$pagenum";
}

redirect($canonical, 'location', 301);
