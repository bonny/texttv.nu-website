<?php

// misc stuff
function d($str = "") {
	echo "<pre>";
	print_r($str);
	echo "</pre>";
}

/**
 * @param int $time_from Unixtime
 * @param int $time_to Unixtime
 * @param string $type
 */
function get_most_read_pages_for_period($time_from, $time_to, $type = 'news') {
	$time_from_ymd = date("Y-m-d H:i", $time_from);
	$time_to_ymd = date("Y-m-d H:i", $time_to);
		
	if ($type == 'news') {
		// type=news default, visa endast sidor 106 - 199
		$type_and = 'AND tt.page_num BETWEEN 106 AND 199';
	} elseif ($type == 'sport') {
		// type=news default, visa endast sidor 106 - 199
		$type_and = 'AND tt.page_num BETWEEN 303 AND 329';
	} else {
		// type=, visa allt förutom startsidor, översiktssidor osv.
		$type_and = '';
	}

	$ci =& get_instance();
	$ci->load->database();

	$sql = "
		## Hämta actions från n senaste timmarna
		#EXPLAIN
		select 
		  count(pa.page_ids) AS count_page_ids, 
		  pa.page_ids, 
		  tt.id, 
		  tt.page_num, 
		  tt.title, 
		  tt.date_updated,
		  UNCOMPRESS(tt.page_content) AS page_content,
		  DATE_FORMAT(tt.date_added, '%H:%i') AS date_added_formatted, 
		  UNIX_TIMESTAMP(tt.date_added) AS date_added_unix, 
		  tt.date_added
		
		FROM 
		  texttv_stats.page_actions AS pa 
		  INNER JOIN `texttv.nu`.texttv AS tt ON (tt.id = pa.page_ids)
		
		WHERE 
		  # pa.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
		  pa.created_at BETWEEN '$time_from_ymd' and '$time_to_ymd'
		  
		  AND pa.type in(
			'VIEW', 'SHARE', 'COPYLINK', 'COPYTEXT'
		  )
		  
		  # Exkludera startsidan och nyheterna
		  AND (
			tt.page_num NOT BETWEEN 100 AND 105
		  )
		  
		  # Exkludera börsen
		  AND (
			tt.page_num NOT BETWEEN 200 AND 299
		  )
		  
		  # Exkludera sportens nyheter och resultatstartsidorna
		  and (
			tt.page_num NOT BETWEEN 300 AND 303
		  ) 
		  AND tt.page_num not in (
			'377', '378', '379', '330', '331', '380', '344', '381'
		  )
		  
		  # Exkludera tv
		  AND (
			tt.page_num NOT BETWEEN 600 AND 699
		  )
		  
		  # Exkludera andra sidor
		  AND (tt.page_num NOT IN ('202'))

		  # Exkludera tomma sidor
		  AND (tt.title > '')
		  
		  # Inkludera ev. endast t.ex. nyheter eller sport
		  $type_and

		GROUP BY
		  pa.page_ids 
		ORDER BY
		  count_page_ids DESC, 
		  pa.created_at ASC 
		LIMIT 50
	";
	
	// echo $sql; exit;
	
	$result = $ci->db->query($sql);
	
	return $result;
} // end get most read pages for period

function get_shared_pages_for_period($time_from, $time_to) {
	$time_from_ymd = date("Y-m-d H:i", $time_from);
	$time_to_ymd = date("Y-m-d H:i", $time_to);

	$ci =& get_instance();
    $ci->load->database();

	$sql = "
		SELECT 
			id,
			DATE_FORMAT(date_added, '%H:%i') as date_added_formatted, 
			UNIX_TIMESTAMP(date_added) as date_added_unix, 
			date_added, 
			page_num, title, is_shared, 
			UNCOMPRESS(page_content) AS page_content,
			LENGTH( UNCOMPRESS(page_content) ) AS page_length,
			is_shared AS num_shares
		FROM texttv
		WHERE 
			date_added BETWEEN '$time_from_ymd' and '$time_to_ymd'
			AND is_shared <> 0
			AND page_num BETWEEN 106 AND 199
			# no nyhetsrullen
			AND page_num <> 188
		ORDER BY is_shared DESC, date_added DESC
		LIMIT 1000
	";
	
	$result = $ci->db->query($sql);
	
	return $result;
} // end get shared pages for period

function get_latest_updated_pages($from, $to, $maxcount = 20) {

	$from = (int) $from;
	$to = (int) $to;

	$ci =& get_instance();
    $ci->load->database();

	$sql = "
		SELECT 
			id, 
			UNIX_TIMESTAMP(date_added) as date_added_unix, 
			date_added, 
			DATE_FORMAT(date_added, '%H:%i') as date_added_formatted, 
			page_num, title, is_shared, 
			UNCOMPRESS(page_content) AS page_content,
			LENGTH( UNCOMPRESS(page_content) ) AS page_length
		FROM texttv
		# seems to only include pages with content
		# ie. excluding 'no content' pages..eh..
		WHERE 
			# only include pages updated the last n hours
			date_added >= DATE_SUB(NOW(), INTERVAL 8 HOUR)
			AND page_num BETWEEN $from and $to
			AND title <> ''
			# exclude some pages like nyhetsrullen
			AND page_num NOT IN (188, 100, 101, 102, 103, 104, 105, 300, 301, 302, 400)
			AND page_num NOT BETWEEN 350 and 399
		HAVING page_length > 1200
		ORDER BY date_added DESC
		LIMIT $maxcount
	";
	
	$result = $ci->db->query($sql);
	
	return $result;

}

function get_shared_pages($days = 1, $limit = 10) {

	$ci =& get_instance();
    $ci->load->database();

	$sql = "
		SELECT 
			id, date_added, DATE_FORMAT(date_added, '%H:%i') as date_added_formatted, page_num, title, is_shared, 
			UNCOMPRESS(page_content) AS page_content,
			LENGTH( UNCOMPRESS(page_content) ) AS page_length,
			is_shared AS num_shares
		FROM texttv
		WHERE 
			date_added >= DATE_SUB(NOW(), INTERVAL $days DAY)
			AND is_shared <> 0
			AND page_num NOT IN (188, 100, 101, 102, 103, 104, 105, 300, 301, 302, 400)
		ORDER BY is_shared DESC, date_added DESC
		LIMIT $limit
	";
	
	$result = $ci->db->query($sql);
	
	return $result;

}


#
#
# A PHP auto-linking library
#
# $Id: lib_autolink.php,v 1.4 2006/12/09 20:36:50 cal Exp $
#
# http://appliedthinking.org/autolinking/
#
# By Cal Henderson <cal@iamcal.com>
# This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
# http://creativecommons.org/licenses/by-sa/2.5/
#
#

####################################################################

function autolink($text, $limit=30, $tagfill=''){

	$text = autolink_do($text, 'https://',	$limit, $tagfill);
	$text = autolink_do($text, 'http://',	$limit, $tagfill);
	$text = autolink_do($text, 'ftp://',	$limit, $tagfill);
	$text = autolink_do($text, 'www.',	$limit, $tagfill);
	return $text;
}

####################################################################

function autolink_do($text, $sub, $limit, $tagfill){

	$sub_len = strlen($sub);

	$text_l = StrToLower($text);
	$cursor = 0;
	$loop = 1;
	$buffer = '';

	while (($cursor < strlen($text)) && $loop){

		$ok = 1;
		$pos = strpos($text_l, $sub, $cursor);

		if ($pos === false){

			$loop = 0;
			$ok = 0;

		}else{

			$pre_hit = substr($text, $cursor, $pos-$cursor);
			$hit = substr($text, $pos, $sub_len);
			$pre = substr($text, 0, $pos);
			$post = substr($text, $pos + $sub_len);

			$fail_text = $pre_hit.$hit;
			$fail_len = strlen($fail_text);

			#
			# substring found - first check to see if we're inside a link tag already...
			#

			$bits = preg_split("!</a>!i", $pre);
			$last_bit = array_pop($bits);
			if (preg_match("!<a\s!i", $last_bit)){

				#echo "fail 1 at $cursor<br />\n";

				$ok = 0;
				$cursor += $fail_len;
				$buffer .= $fail_text;
			}
		}

		#
		# looks like a nice spot to autolink from - check the pre
		# to see if there was whitespace before this match
		#

		if ($ok){

			if ($pre){
				if (!preg_match('![\s\(\[\{]$!s', $pre)){

					#echo "fail 2 at $cursor ($pre)<br />\n";

					$ok = 0;
					$cursor += $fail_len;
					$buffer .= $fail_text;
				}
			}
		}

		#
		# we want to autolink here - find the extent of the url
		#

		if ($ok){
			if (preg_match('/^([a-z0-9\-\.\/\-_%~!?=,:;&+*#@\(\)\$]+)/i', $post, $matches)){

				$url = $hit.$matches[1];

				#
				# remove trailing punctuation from url
				#

				if (preg_match('|[.,!;:?]$|', $url)){
					$url = substr($url, 0, strlen($url)-1);
				}
				foreach (array('()', '[]', '{}') as $pair){
					$o = substr($pair, 0, 1);
					$c = substr($pair, 1, 1);
					if (preg_match("!^(\\$c|^)[^\\$o]+\\$c$!", $url)){
						$url = substr($url, 0, strlen($url)-1);
					}
				}

				#
				# commit
				#

				$cursor += strlen($url) + strlen($pre_hit);
				$buffer .= $pre_hit;

				#
				# nice-i-fy url here
				#

				$link_url = $url;
				$display_url = $url;

				if (preg_match('!^([a-z]+)://!i', $url, $m)){
					$display_url = substr($display_url, strlen($m[1])+3);
				}else{
					$link_url = "http://$link_url";
				}

				$display_url = autolink_label($display_url, $limit);

				#
				# add the url
				#

				$buffer .= "<a href=\"$link_url\"$tagfill>$display_url</a>";
			
			}else{
				#echo "fail 3 at $cursor<br />\n";

				$ok = 0;
				$cursor += $fail_len;
				$buffer .= $fail_text;
			}
		}

	}

	#
	# add everything from the cursor to the end onto the buffer.
	#

	$buffer .= substr($text, $cursor);

	return $buffer;
}

####################################################################

function autolink_label($text, $limit){

	if (!$limit){ return $text; }

	if (strlen($text) > $limit){
		return substr($text, 0, $limit-3).'...';
	}

	return $text;
}

####################################################################

function autolink_email($text, $tagfill=''){

	$atom = '[^()<>@,;:\\\\".\\[\\]\\x00-\\x20\\x7f]+'; # from RFC822

	#die($atom);

	$text_l = StrToLower($text);
	$cursor = 0;
	$loop = 1;
	$buffer = '';

	while(($cursor < strlen($text)) && $loop){

		#
		# find an '@' symbol
		#

		$ok = 1;
		$pos = strpos($text_l, '@', $cursor);

		if ($pos === false){

			$loop = 0;
			$ok = 0;

		}else{

			$pre = substr($text, $cursor, $pos-$cursor);
			$hit = substr($text, $pos, 1);
			$post = substr($text, $pos + 1);

			$fail_text = $pre.$hit;
			$fail_len = strlen($fail_text);

			#die("$pre::$hit::$post::$fail_text");

			#
			# substring found - first check to see if we're inside a link tag already...
			#

			$bits = preg_split("!</a>!i", $pre);
			$last_bit = array_pop($bits);
			if (preg_match("!<a\s!i", $last_bit)){

				#echo "fail 1 at $cursor<br />\n";

				$ok = 0;
				$cursor += $fail_len;
				$buffer .= $fail_text;
			}
		}

		#
		# check backwards
		#

		if ($ok){
			if (preg_match("!($atom(\.$atom)*)\$!", $pre, $matches)){

				# move matched part of address into $hit

				$len = strlen($matches[1]);
				$plen = strlen($pre);

				$hit = substr($pre, $plen-$len).$hit;
				$pre = substr($pre, 0, $plen-$len);

			}else{

				#echo "fail 2 at $cursor ($pre)<br />\n";

				$ok = 0;
				$cursor += $fail_len;
				$buffer .= $fail_text;
			}
		}

		#
		# check forwards
		#

		if ($ok){
			if (preg_match("!^($atom(\.$atom)*)!", $post, $matches)){

				# move matched part of address into $hit

				$len = strlen($matches[1]);

				$hit .= substr($post, 0, $len);
				$post = substr($post, $len);

			}else{
				#echo "fail 3 at $cursor ($post)<br />\n";

				$ok = 0;
				$cursor += $fail_len;
				$buffer .= $fail_text;
			}
		}

		#
		# commit
		#

		if ($ok) {

			$cursor += strlen($pre) + strlen($hit);
			$buffer .= $pre;
			$buffer .= "<a href=\"mailto:$hit\"$tagfill>$hit</a>";

		}

	}

	#
	# add everything from the cursor to the end onto the buffer.
	#

	$buffer .= substr($text, $cursor);

	return $buffer;
}

####################################################################

// Skapa permalänk för 1 eller flera sidor
function get_permalink_from_pages($arr_pages, $page, $pagenum) {
	
	$permalink = "";

	if ( sizeof( $arr_pages) > 1 ) {
	
		$arr_mutliple_archive_ids = array();
		foreach ($arr_pages as $one_page_obj) {
			$arr_mutliple_archive_ids[] = $one_page_obj->id;
		}

		$page_title_for_url = date("j M Y", $one_page_obj->date_updated_unix);
		$page_title_for_url = trim(strtolower($page_title_for_url));
		$page_title_for_url = url_title($page_title_for_url);	

		// Permalink för flera sidor
		$permalink = sprintf(
			'/%1$s/arkiv/%3$s/%2$s/',
			$pagenum, // 1 sidnummer
			implode(",", $arr_mutliple_archive_ids), // 2 id
			$page_title_for_url // 3 titel
		);

	} else if ( isset( $page ) ) {
	
		$permalink = $arr_pages[0]->get_permalink();
		
	}

	return $permalink;

}

function mark_archive_ids_as_shared($arr_page_ids) {
	
	if (empty($arr_page_ids)) {
		return;
	}
	
	$ci =& get_instance();
    $ci->load->database();

	$sql_set_shared = sprintf('UPDATE texttv SET is_shared = is_shared + 1 WHERE id IN (%s)', implode(",", $arr_page_ids));
	$ci->db->query($sql_set_shared);	
}

function log2db( $key = "", $text = "" ) {

	$ci =& get_instance();
    $ci->load->database();

	$date_added = date("Y-m-d H:i:s");

	//$text = json_encode( [ $_REQUEST, $_SERVER ], JSON_PRETTY_PRINT );
	
	$ci->db->query(
		sprintf(
			'INSERT INTO texttv_log (date_added, log_key, log_text) VALUES (%1$s, %2$s, %3$s)',
			$ci->db->escape($date_added),
			$ci->db->escape($key),
			$ci->db->escape($text)
		)
	);
	
}

function json_encode_pretty($data) {
	return json_encode($data, JSON_PRETTY_PRINT);
}

function removeWhiteSpace($text) {
    $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
    $text = preg_replace('/([\s])\1+/', ' ', $text);
    $text = trim($text);
    return $text;
}

// https://gist.github.com/stankusl/579e436892ef1cdb5d4a
function shorten_text($text, $max_length = 140, $cut_off = '...', $keep_word = false)
{
    if(strlen($text) <= $max_length) {
        return $text;
    }

    if(strlen($text) > $max_length) {
        if($keep_word) {
            $text = substr($text, 0, $max_length + 1);

            if($last_space = strrpos($text, ' ')) {
                $text = substr($text, 0, $last_space);
                $text = rtrim($text);
                $text .=  $cut_off;
            }
        } else {
            $text = substr($text, 0, $max_length);
            $text = rtrim($text);
            $text .=  $cut_off;
        }
    }

    return $text;
}