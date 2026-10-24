<?php

// No breadcrumb for archive pages
if (isset($is_archive_overview)) {
	return;
}

// No breadcrum if taking screenshot for sharing
if ($this->input->get("apiAppShare")) {
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

	if (in_array($pagenum, $arr_pagenums_to_skip)) {
		return;
	}

	// Föräldern är en enskild sida: intervallen (/300-302 m.fl.) har noindex.
	// Inrikes = 101 - 130
	// Utrikes = 130 - 199
	// Ekonomi = 200 -
	$get_parent = function ($n) {
		$arr_parent = array();

		if ($n > 101 && $n < 130) {
			$arr_parent	= [
				"title" => "Nyheter Sverige",
				"url" => "https://texttv.nu/101"
			];
		}

		if ($n >= 130 && $n < 200) {
			$arr_parent	= [
				"title" => "Nyheter världen",
				"url" => "https://texttv.nu/104"
			];
		}

		if ($n > 200 && $n < 300) {
			$arr_parent	= [
				"title" => "Ekonomi",
				"url" => "https://texttv.nu/200"
			];
		}

		if ($n > 300 && $n < 400) {
			$arr_parent	= [
				"title" => "Sport",
				"url" => "https://texttv.nu/300"
			];
		}

		if ($n >= 400 && $n < 500) {
			$arr_parent	= [
				"title" => "Väder",
				"url" => "https://texttv.nu/400"
			];
		}

		if ($n >= 440 && $n < 500) {
			$arr_parent	= [
				"title" => "Sport › OS",
				"url" => "https://texttv.nu/440"
			];
		}

		if ($n > 550 && $n < 570) {
			$arr_parent	= [
				"title" => "Tipset",
				"url" => "https://texttv.nu/550"
			];
		}

		if ($n > 570 && $n < 580) {
			$arr_parent	= [
				"title" => "Trav och Galopp",
				"url" => "https://texttv.nu/570"
			];
		}

		if ($n > 600 && $n < 670) {
			$arr_parent	= [
				"title" => "TV",
				"url" => "https://texttv.nu/600"
			];
		}

		return $arr_parent;
	};

	// Flera sidor (/376,377): föräldern bara om alla sidor har samma. Annars
	// "Hem › 106, 132". Förut visades bara första sidan (granskning 2026-10-24).
	$is_multiple = sizeof($pages) > 1;
	$arr_parent = $get_parent($pagenum);
	foreach ($pages as $one_page) {
		if ($get_parent((int) $one_page->num) !== $arr_parent) {
			$arr_parent = array();
			break;
		}
	}

	// If no parent then no breadcrumb
	if (!$arr_parent && !$is_multiple) {
		return;
	}

	$arr_extra_breadcrumb_html = "";

	// If page below målservice, ie 377 - 380. Med flera sidor bara om alla är det.
	$all_below_malservice = true;
	foreach ($pages as $one_page) {
		if ($one_page->num < 377 || $one_page->num > 380) {
			$all_below_malservice = false;
		}
	}

	if ($all_below_malservice) {

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

	if ($is_multiple) {
		// Ingen BreadcrumbList: vyn har noindex, och sista ledet är inte en
		// enskild sida. Varje sidnummer länkar till sin sida, upp till sex
		// sidor. Fler (/100-199) står som text, "100–199".
		$current_html = html_escape(tt_sidnummer_text($pages));
		if (sizeof($pages) <= 6) {
			$arr_links = array();
			foreach ($pages as $one_page) {
				$arr_links[] = sprintf('<a class="breadcrumbs__link" href="/%1$d">%1$d</a>', $one_page->num);
			}
			$current_html = implode(", ", $arr_links);
		}

		$parent_html = "";
		if ($arr_parent) {
			$parent_html = sprintf(
				'<li class="breadcrumbs__item"><a class="breadcrumbs__link" href="%1$s">%2$s</a> › </li>',
				$arr_parent["url"],
				$arr_parent["title"]
			);
		}
		if ($arr_extra_breadcrumb_html) {
			$parent_html .= '<li class="breadcrumbs__item"><a class="breadcrumbs__link" href="/376">Målservice</a> › </li>';
		}

		printf(
			'
			<ol class="breadcrumbs">
				<li class="breadcrumbs__item"><a class="breadcrumbs__link" href="https://texttv.nu/">Hem</a> › </li>
				%1$s
				<li class="breadcrumbs__item breadcrumbs__item--current">%2$s</li>
			</ol>
			',
			$parent_html,
			$current_html
		);
		return;
	}

	$lastitem_position = 3;
	if ($arr_extra_breadcrumb_html) {
		$lastitem_position = 4;
	}

	printf(
		'
			<ol class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">

				<li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<a class="breadcrumbs__link" href="https://texttv.nu/" itemprop="item"><span itemprop="name">Hem</span><meta itemprop="position" content="1" /></a> › 
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

// Function exists in PHP 8.3.0.
if (!function_exists('mb_str_pad')) {
	function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL) {
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
}
