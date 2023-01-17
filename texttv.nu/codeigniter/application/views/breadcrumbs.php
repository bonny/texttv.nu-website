<?php

// No breadcrumb for archive pages
if ( isset($is_archive_overview) ) {
	return;
}

// No breadcrum if taking screenshot for sharing
if ( $this->input->get("apiAppShare") ) {
	return;
}

// Do use breadcrumb if single or multiple page
if (isset($pages) && is_array($pages)) {

	// Only for single pages for now
	#if ( sizeof($pages) != 1 ) {
	#	return;
	#}
	
	$pagenum = (int) $pages[0]->num;
	
	// Skip breadcrumb on some pages, like overview pages
	$arr_pagenums_to_skip = array(
		100, 101, 102, 103, 104, 105	
	);

	if ( in_array($pagenum, $arr_pagenums_to_skip) ) {
		return;
	}
	
	// Inrikes = 101 - 130
	// Utrikes = 130 - 199
	// Ekonomi = 200 -
	$arr_parent = array();
	
	if ($pagenum > 101 && $pagenum < 130) {
		$arr_parent	= [
			"title" => "Nyheter Sverige",
			"url" => "https://texttv.nu/101,102,103"
		];
	}

	if ($pagenum >= 130 && $pagenum < 200) {
		$arr_parent	= [
			"title" => "Nyheter världen",
			"url" => "https://texttv.nu/104-105"
		];
	}

	if ($pagenum > 200 && $pagenum < 300) {
		$arr_parent	= [
			"title" => "Ekonomi",
			"url" => "https://texttv.nu/200-202"
		];
	}

	if ($pagenum > 300 && $pagenum < 400) {
		$arr_parent	= [
			"title" => "Sport",
			"url" => "https://texttv.nu/300-302"
		];
	}

	if ($pagenum >= 400 && $pagenum < 500) {
		$arr_parent	= [
			"title" => "Väder",
			"url" => "https://texttv.nu/400"
		];
	}

	if ($pagenum >= 440 && $pagenum < 500) {
		$arr_parent	= [
			"title" => "Sport › OS",
			"url" => "https://texttv.nu/440"
		];
	}

	if ($pagenum > 550 && $pagenum < 570) {
		$arr_parent	= [
			"title" => "Tipset",
			"url" => "https://texttv.nu/550"
		];
	}

	if ($pagenum > 570 && $pagenum < 580) {
		$arr_parent	= [
			"title" => "Trav och Galopp",
			"url" => "https://texttv.nu/570"
		];
	}

	if ($pagenum > 600 && $pagenum < 670) {
		$arr_parent	= [
			"title" => "TV",
			"url" => "https://texttv.nu/600"
		];
	}

	// If no parent then no breadcrumb
	if (!$arr_parent) {
		return;
	}
	
	$arr_extra_breadcrumb_html = "";
	
	// If page below målservice, ie 377 - 380
	if ($pagenum >= 377 && $pagenum <= 380) {
		
		$arr_extra_breadcrumb_html = sprintf(
			'
				<li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
					<a class="breadcrumbs__link" href="/%1$d" itemprop="item"><span itemprop="name">%2$s</span><meta itemprop="position" content="3" /></a> › 
				</li>
			', 
			376, // 1 link pagenum
			"Målservice" // 2 name of link
		);
		
	}
	
	$lastitem_position = 3;
	if ( $arr_extra_breadcrumb_html ) {
		$lastitem_position = 4;
	}
	
	
	printf('
			<ol class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">

				<li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<a class="breadcrumbs__link" href="http://texttv.nu/" itemprop="item"><span itemprop="name">Hem</span><meta itemprop="position" content="1" /></a> › 
				</li>

				<li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<a class="breadcrumbs__link" href="%3$s" itemprop="item"><span itemprop="name">%2$s</span><meta itemprop="position" content="2" /></a> › 
				</li>
				
				%4$s

				<li class="breadcrumbs__item breadcrumbs__item--current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<span itemprop="item"><span itemprop="name">%1$d<meta itemprop="url" content="/%1$d" /><meta itemprop="position" content="%5$d" /></span></span>
				</li>

			</ol>
		', 
		$pagenum, // 1
		$arr_parent["title"], // 2
		$arr_parent["url"], // 3
		$arr_extra_breadcrumb_html, // 4 extra breadcrumb after parent and before pagenum
		$lastitem_position // 5
	);
		
};

function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL)
{
    $encoding = $encoding === NULL ? mb_internal_encoding() : $encoding;
    $padBefore = $dir === STR_PAD_BOTH || $dir === STR_PAD_LEFT;
    $padAfter = $dir === STR_PAD_BOTH || $dir === STR_PAD_RIGHT;
    $pad_len -= mb_strlen($str, $encoding);
    $targetLen = $padBefore && $padAfter ? $pad_len / 2 : $pad_len;
    $strToRepeatLen = mb_strlen($pad_str, $encoding);
    $repeatTimes = ceil($targetLen / $strToRepeatLen);
    $repeatedString = str_repeat($pad_str, max(0, $repeatTimes)); // safe if used with valid unicode sequences (any charset)
    $before = $padBefore ? mb_substr($repeatedString, 0, floor($targetLen), $encoding) : '';
    $after = $padAfter ? mb_substr($repeatedString, 0, ceil($targetLen), $encoding) : '';
    return $before . $str . $after;
}
