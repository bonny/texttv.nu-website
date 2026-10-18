<?php

// misc stuff
function d($str = "") {
	echo "<pre>";
	print_r($str);
	echo "</pre>";
}

/**
 * Som date() fast med svenska veckodags- och månadsnamn.
 *
 * date() är alltid engelsk oavsett locale. Sajten fick tidigare svenska datum
 * via strftime() + setlocale(LC_TIME, "sv_SE.utf8") i config/config.php, men
 * strftime() är deprecated sedan PHP 8.1 och ersattes rakt av med date() —
 * varpå alla utskrivna datum blev engelska.
 *
 * Här hanteras de fyra locale-beroende formattecknen (D, l, M, F) med egna
 * tabeller; resten skickas vidare till date(). Inga extratillägg krävs (intl
 * finns inte garanterat i prod), och namnen matchar glibc:s sv_SE, dvs samma
 * strängar som strftime %a/%A/%b/%B gav förut.
 *
 * Namnen returneras med gemener precis som strftime gjorde — anropare som vill
 * ha versal begynnelsebokstav kör ucfirst() själva.
 *
 * OBS: använd inte för maskinläsbara datum (RSS pubDate, HTTP Last-Modified,
 * URL-slugs) — de ska vara kvar på engelska/ISO.
 *
 * @param string   $format    Formatsträng enligt date().
 * @param int|null $timestamp Unixtime. Default: nu.
 * @return string
 */
function date_sv($format, $timestamp = null) {

	static $weekdays = array(
		"Monday"    => "måndag",
		"Tuesday"   => "tisdag",
		"Wednesday" => "onsdag",
		"Thursday"  => "torsdag",
		"Friday"    => "fredag",
		"Saturday"  => "lördag",
		"Sunday"    => "söndag",
	);

	static $weekdays_short = array(
		"Mon" => "mån",
		"Tue" => "tis",
		"Wed" => "ons",
		"Thu" => "tors",
		"Fri" => "fre",
		"Sat" => "lör",
		"Sun" => "sön",
	);

	static $months = array(
		"January"   => "januari",
		"February"  => "februari",
		"March"     => "mars",
		"April"     => "april",
		"May"       => "maj",
		"June"      => "juni",
		"July"      => "juli",
		"August"    => "augusti",
		"September" => "september",
		"October"   => "oktober",
		"November"  => "november",
		"December"  => "december",
	);

	static $months_short = array(
		"Jan" => "jan",
		"Feb" => "feb",
		"Mar" => "mar",
		"Apr" => "apr",
		"May" => "maj",
		"Jun" => "jun",
		"Jul" => "jul",
		"Aug" => "aug",
		"Sep" => "sep",
		"Oct" => "okt",
		"Nov" => "nov",
		"Dec" => "dec",
	);

	if ($timestamp === null) {
		$timestamp = time();
	}

	$output = "";
	$format_length = strlen($format);

	for ($i = 0; $i < $format_length; $i++) {

		$char = $format[$i];

		// Escapat tecken ("\l") — skriv ut nästa tecken orört.
		if ($char === "\\") {
			$output .= isset($format[$i + 1]) ? $format[$i + 1] : "";
			$i++;
			continue;
		}

		switch ($char) {
			case "D":
				$output .= $weekdays_short[ date("D", $timestamp) ];
				break;
			case "l":
				$output .= $weekdays[ date("l", $timestamp) ];
				break;
			case "M":
				$output .= $months_short[ date("M", $timestamp) ];
				break;
			case "F":
				$output .= $months[ date("F", $timestamp) ];
				break;
			default:
				// Allt annat (inkl. rena literaler som ":" och ",") till date().
				$output .= date($char, $timestamp);
				break;
		}

	}

	return $output;

}

/**
 * @param int $time_from Unixtime
 * @param int $time_to Unixtime
 * @param string $type
 */
function get_most_read_pages_for_period($time_from = null, $time_to = null, $type = 'news') {

	// Fallback to most read today if no times are set.
	if (empty($time_from) || empty($time_to)) {
		$dateUnix = time();
		$time_from = strtotime("today 00:00", $dateUnix);
		$time_to = strtotime("today 24:00", $dateUnix);
	}

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

	// 2026-08-10 (todo #14): Queryn är uppdelad i två steg.
	//
	// Tidigare låg UNCOMPRESS(tt.page_content) i SELECT-listan på den
	// grupperande queryn. Det innebar att sidinnehållet dekomprimerades för
	// VARJE joinad rad — även alla de som sedan kastades av LIMIT 50. På ett
	// dygn stod den här funktionen för 578 av 608 långsamma requests (p95
	// 1,76 s, max 6,4 s) och band php-fpm-workers så att poolen slog i taket.
	//
	// Nu gör den inre queryn bara aggregeringen (billig, inga blobbar), och
	// UNCOMPRESS körs i den yttre — alltså på exakt de 50 rader som blir kvar.
	//
	// OBS: ORDER BY använde tidigare `pa.created_at` som är en icke-aggregerad
	// kolumn i en GROUP BY-query; MariaDB plockade då ett godtyckligt värde ur
	// gruppen. Här används MIN(pa.created_at), vilket är deterministiskt och
	// matchar den uppenbara avsikten (äldsta action först vid lika antal).
	// Rader med samma count_page_ids kan därför byta inbördes ordning.
	$sql = "
		## Hämta actions från n senaste timmarna
		#EXPLAIN
		select
		  agg.count_page_ids,
		  agg.page_ids,
		  tt.id,
		  tt.page_num,
		  tt.title,
		  tt.date_updated,
		  UNCOMPRESS(tt.page_content) AS page_content,
		  DATE_FORMAT(tt.date_added, '%H:%i') AS date_added_formatted,
		  UNIX_TIMESTAMP(tt.date_added) AS date_added_unix,
		  tt.date_added

		FROM (
		  ## Steg 1: aggregera fram de 50 mest lästa. Ingen page_content här.
		  select
		    count(pa.page_ids) AS count_page_ids,
		    pa.page_ids,
		    MIN(pa.created_at) AS first_created_at

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
		    first_created_at ASC
		  LIMIT 50
		) AS agg

		## Steg 2: hämta innehållet för enbart de 50 vinnarna.
		INNER JOIN `texttv.nu`.texttv AS tt ON (tt.id = agg.page_ids)

		ORDER BY
		  agg.count_page_ids DESC,
		  agg.first_created_at ASC
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

/**
 * Sajtens 404-sida: status 404 med sidhuvud, meny och senaste nyheterna.
 *
 * Används av 404_override i routes.php (okända adresser, som annars fick
 * CodeIgniters engelska standardsida) och av controllers som inte hittar
 * det som efterfrågas. Anroparen ska göra return direkt efteråt.
 */
function show_texttv_404() {
	$ci =& get_instance();
	$ci->output->set_status_header(404);
	// fastcgi_cache_valid i vhosten gäller bara 200/301/302, men
	// X-Accel-Expires gör att nginx cachar även 404:an en kort stund. Annars
	// renderas hela sidan, med DB-fråga, för varje /wp-login.php från skannrar.
	$ci->output->set_header("X-Accel-Expires: 60");

	$data = [
		"custom_page_title" => "Sidan finns inte (felkod 404) - TextTV.nu",
	];

	$ci->load->view('header', $data);
	$ci->load->view('404', $data);
	$ci->load->view('pages-latest-updated', $data);
	$ci->load->view('controls', $data);
	$ci->load->view('footer', $data);
}

/**
 * Versioner av en sida som lades till under ett dygn, nyaste först.
 *
 * Dygnet räknas i svensk tid, eftersom importern sparar date_added i
 * Europe/Stockholm. Använder indexet (page_num, date_added).
 * Todo #16: dagsarkivet /377/igar och /377/2026-10-12.
 *
 * @param int    $page_num Sidnummer.
 * @param string $day_ymd  Datum, Y-m-d.
 * @param int    $limit    Högst så många (nyaste).
 * @return array Rader med id, title och date_added (Y-m-d H:i:s).
 */
function get_page_versions_for_day($page_num, $day_ymd, $limit = 500) {
	$tz = new DateTimeZone("Europe/Stockholm");
	$from = new DateTime($day_ymd . " 00:00:00", $tz);
	$to = clone $from;
	$to->modify("+1 day");

	$ci =& get_instance();
	$ci->load->database();

	$ci->db->select("id, title, date_added");
	$ci->db->from("texttv");
	$ci->db->where("page_num", (int) $page_num);
	$ci->db->where("date_added >=", $from->format("Y-m-d H:i:s"));
	$ci->db->where("date_added <", $to->format("Y-m-d H:i:s"));
	// Tom titel = "Sidan ej i sändning", som importern också sparar.
	$ci->db->where("title <>", "");
	$ci->db->order_by("date_added", "DESC");
	$ci->db->limit((int) $limit);

	return $ci->db->get()->result();
}

/**
 * Sidans version när ett dygn var slut: den senaste som lagts till före
 * midnatt, oavsett vilken dag. Importern sparar bara en ny rad när innehållet
 * ändras, så en dag utan ändringar (t.ex. 330 en söndag) har inga egna rader.
 *
 * Söker högst sju dygn bakåt, så att ett datum före arkivets början inte visar
 * en version från långt tidigare. Hoppar över "Sidan ej i sändning" (tom
 * titel). Todo #16.
 *
 * @param int    $page_num Sidnummer.
 * @param string $day_ymd  Datum, Y-m-d.
 * @return object|null Rad med id, title och date_added, eller null.
 */
function get_page_version_at_end_of_day($page_num, $day_ymd) {
	$tz = new DateTimeZone("Europe/Stockholm");
	$to = new DateTime($day_ymd . " 00:00:00", $tz);
	$to->modify("+1 day");
	$from = clone $to;
	$from->modify("-7 days");

	$ci =& get_instance();
	$ci->load->database();

	$ci->db->select("id, title, date_added");
	$ci->db->from("texttv");
	$ci->db->where("page_num", (int) $page_num);
	$ci->db->where("date_added >=", $from->format("Y-m-d H:i:s"));
	$ci->db->where("date_added <", $to->format("Y-m-d H:i:s"));
	$ci->db->where("title <>", "");
	$ci->db->order_by("date_added", "DESC");
	$ci->db->limit(1);

	$row = $ci->db->get()->row();

	return $row ? $row : null;
}

/**
 * Senaste versionen av en sida som hade innehåll, alltså inte "Sidan ej i
 * sändning" (tom titel). Högst $max_days dygn bakåt (null = obegränsat).
 * Används när sidan inte sänds, så att den levande adressen visar senast
 * sända version, eller länkar dit om sidan varit borta länge. Todo #16.
 *
 * @return object|null Rad med id, title och date_added, eller null.
 */
function get_latest_broadcast_version($page_num, $max_days = 60) {
	$ci =& get_instance();
	$ci->load->database();

	$ci->db->select("id, title, date_added");
	$ci->db->from("texttv");
	$ci->db->where("page_num", (int) $page_num);
	if ($max_days) {
		$from = new DateTime("now", new DateTimeZone("Europe/Stockholm"));
		$from->modify("-" . (int) $max_days . " days");
		$ci->db->where("date_added >=", $from->format("Y-m-d H:i:s"));
	}
	$ci->db->where("title <>", "");
	$ci->db->order_by("date_added", "DESC");
	$ci->db->limit(1);

	$row = $ci->db->get()->row();

	return $row ? $row : null;
}

/**
 * Veckodagar då en sida brukar sändas, räknat från de senaste åtta veckornas
 * versioner med innehåll. En veckodag räknas om sidan haft innehåll den
 * dagen under minst två olika veckor. Todo #16.
 *
 * @return int[] ISO-veckodagar, 1 = måndag … 7 = söndag, sorterade.
 */
function get_page_broadcast_weekdays($page_num) {
	$ci =& get_instance();
	$ci->load->database();

	$from = new DateTime("now", new DateTimeZone("Europe/Stockholm"));
	$from->modify("-56 days");

	$sql = "
		SELECT WEEKDAY(date_added) + 1 AS weekday, COUNT(DISTINCT YEARWEEK(date_added, 3)) AS weeks
		FROM texttv
		WHERE page_num = ? AND date_added >= ? AND title <> ''
		GROUP BY weekday
		HAVING weeks >= 2
		ORDER BY weekday
	";
	$rows = $ci->db->query($sql, [(int) $page_num, $from->format("Y-m-d H:i:s")])->result();

	return array_map(function ($row) {
		return (int) $row->weekday;
	}, $rows);
}

/**
 * "lördagar och söndagar", "måndag–fredag", "varje dag" o.s.v.
 *
 * @param int[] $weekdays ISO-veckodagar, sorterade.
 */
function format_broadcast_weekdays($weekdays) {
	$names = [1 => "måndagar", 2 => "tisdagar", 3 => "onsdagar", 4 => "torsdagar", 5 => "fredagar", 6 => "lördagar", 7 => "söndagar"];

	if (sizeof($weekdays) === 7) {
		return "varje dag";
	}
	if ($weekdays === [1, 2, 3, 4, 5]) {
		return "vardagar";
	}

	$words = array_map(function ($day) use ($names) {
		return $names[$day];
	}, $weekdays);

	if (sizeof($words) <= 1) {
		return implode("", $words);
	}
	$last = array_pop($words);
	return implode(", ", $words) . " och " . $last;
}

/**
 * Topp 50 mest lästa för en typ och ett datum, delat mellan alla
 * count-varianter av anropet.
 *
 * 2026-10-14 (todo #12): nginx cachar varje URL för sig, så count=5,
 * count=10, count=15 och brottsplatskartan-varianten regenererades var
 * för sig varje minut — samma tunga query fyra–fem gånger per minut, var
 * femte körning över 3 s. Queryn tar alltid fram topp 50 och count skär
 * bara av listan, så resultatet cachas här en gång per typ och datum och
 * delas av alla varianter. Svaret är byte-identiskt med tidigare.
 *
 * Bara de senaste 14 dagarna cachas, så att godtyckliga date-värden inte
 * kan fylla disken.
 *
 * Flyttad från Api-controllern 2026-10-14 (todo #16) så att dagslistorna
 * (Dagsnyheter) kan använda samma cache i stället för den tunga queryn.
 *
 * @return array [sidor, sekunder som nginx får cacha svaret]
 */
function most_read_pages_cached($type, $dateUnix)
{
	$date_ymd = date("Y-m-d", $dateUnix);
	$today_ymd = date("Y-m-d");
	$is_past_day = $date_ymd < $today_ymd;
	$ttl = $is_past_day ? 3600 : 60;
	$is_cacheable = $date_ymd <= $today_ymd && $date_ymd >= date("Y-m-d", strtotime("-14 days"));

	if (!$is_cacheable) {
		return [most_read_pages($type, $dateUnix), $ttl];
	}

	// En fil för en passerad dag gäller bara om den skrevs efter dagens
	// slut. Annars är det en ögonblicksbild från när dagen pågick, som
	// skulle serveras i en timme till trots att den saknar dagens sista
	// visningar.
	$valid_from = $is_past_day ? strtotime("tomorrow", $dateUnix) : 0;

	$dir = sys_get_temp_dir() . "/texttv-most-read";
	if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
		error_log("texttv most_read: kan inte skapa cachekatalogen $dir, kör utan cache");
	}
	$file = $dir . "/" . ($type ? $type : "all") . "-" . $date_ymd . ".json";

	$cached = most_read_read_cache($file, $ttl, $valid_from);
	if ($cached !== null) {
		return $cached;
	}

	// Lås så att bara en worker räknar fram resultatet. Övriga väntar inte
	// på den (då hade de hållit php-fpm-workers medan queryn kör) utan
	// serverar den föregående filen och låter nginx cacha det svaret bara
	// en kort stund. Finns ingen fil alls, första anropet för en dag,
	// väntar de på låset och läser sedan vad den första skrev.
	$lock = @fopen($file . ".lock", "c");
	if (!$lock) {
		error_log("texttv most_read: kan inte öppna låsfilen $file.lock, kör utan cache");
	} elseif (!flock($lock, LOCK_EX | LOCK_NB)) {
		$stale = most_read_read_cache($file, PHP_INT_MAX, 0);
		if ($stale !== null) {
			fclose($lock);
			return [$stale[0], 5];
		}
		flock($lock, LOCK_EX);
		$cached = most_read_read_cache($file, $ttl, $valid_from);
	}

	if ($cached === null) {
		$pages = most_read_pages($type, $dateUnix);
		$cached = [$pages, $ttl];
		$is_new_file = !file_exists($file);

		$json = json_encode($pages);
		$tmp = $file . "." . getmypid() . ".tmp";
		if ($json === false) {
			error_log("texttv most_read: json_encode misslyckades för $file: " . json_last_error_msg());
		} elseif (@file_put_contents($tmp, $json) !== false) {
			@rename($tmp, $file);
		}

		// Städa bort filer för dagar som inte längre cachas. Räcker att
		// göra när en ny dags fil skapas, inte vid varje omräkning.
		if ($is_new_file) {
			foreach (glob($dir . "/*") ?: [] as $old_file) {
				if (time() - @filemtime($old_file) > 15 * 86400) {
					@unlink($old_file);
				}
			}
		}
	}

	if ($lock) {
		flock($lock, LOCK_UN);
		fclose($lock);
	}

	return $cached;
}

/**
 * @return array|null [sidor, sekunder kvar av filens livslängd], eller
 *                    null om filen saknas, är för gammal eller trasig.
 */
function most_read_read_cache($file, $ttl, $valid_from)
{
	clearstatcache(true, $file);
	$mtime = @filemtime($file);
	if (!$mtime || $mtime < $valid_from || time() - $mtime >= $ttl) {
		return null;
	}

	$pages = json_decode((string) @file_get_contents($file), true);
	if (!is_array($pages)) {
		return null;
	}

	return [$pages, max(5, min($ttl - (time() - $mtime), 3600))];
}

function most_read_pages($type, $dateUnix)
{
	$pages = [];
	$result = get_most_read_pages_for_period(strtotime("today 00:00", $dateUnix), strtotime("today 24:00", $dateUnix), $type);

	// row = en text tv-sida
	foreach ($result->result() as $row) {

		$page_content = unserialize($row->page_content);
		$page_content = strip_tags($page_content[0]);
		$page_content = trim($page_content);

		// permalink
		$archive_page = new Texttv_page();
		$archive_page->id = $row->id;
		$archive_page->load(true);
		$permalink = $archive_page->get_permalink(true);

		$pages[] = [
			"id" => $row->id,
			"title" => $row->title,
			"page_num" => $row->page_num,
			"date_added_formatted" => $row->date_added_formatted,
			"date_added_unix" => $row->date_added_unix,
			"date_added" => $row->date_added,
			"date_added_time" => date('H:i', $row->date_added_unix),
			"page_content" => $page_content,
			"permalink" => $permalink,
			"query_date" => date("Y-m-d", $dateUnix),
			"count_page_ids" => $row->count_page_ids,
		];
	}

	return $pages;
}

/**
 * Alla unika rubriker som sändes under ett dygn inom ett sidintervall, nyast
 * först. För dagslistorna /nyheter/igar och /sport/igar. Todo #16.
 *
 * En nyhet kan stå på två sidnummer samtidigt, eller flytta, så raderna
 * grupperas på rubrik. id och page_num är från rubrikens senaste version,
 * first_added från dess första. Dygnet räknas i svensk tid (date_added
 * sparas i Europe/Stockholm).
 *
 * @param int    $from_num Första sidnummer.
 * @param int    $to_num   Sista sidnummer.
 * @param int[]  $exclude  Sidnummer att hoppa över (notissidor, index o.d.).
 * @param string $day_ymd  Datum, Y-m-d.
 * @return array Rader med title, first_added, id, page_num.
 */
function get_headlines_for_day($from_num, $to_num, $exclude, $day_ymd) {
	$tz = new DateTimeZone("Europe/Stockholm");
	$from = new DateTime($day_ymd . " 00:00:00", $tz);
	$to = clone $from;
	$to->modify("+1 day");

	$exclude_sql = "";
	if ($exclude) {
		$exclude_sql = "AND page_num NOT IN (" . implode(",", array_map("intval", $exclude)) . ")";
	}

	$ci =& get_instance();
	$ci->load->database();

	$sql = "
		SELECT
			title,
			MIN(date_added) AS first_added,
			MAX(id) AS id,
			SUBSTRING_INDEX(GROUP_CONCAT(page_num ORDER BY id DESC), ',', 1) AS page_num
		FROM texttv
		WHERE date_added >= ? AND date_added < ?
			AND page_num BETWEEN ? AND ?
			AND title <> ''
			$exclude_sql
		GROUP BY title
		ORDER BY first_added DESC
		LIMIT 500
	";

	return $ci->db->query($sql, [
		$from->format("Y-m-d H:i:s"),
		$to->format("Y-m-d H:i:s"),
		(int) $from_num,
		(int) $to_num,
	])->result();
}

/**
 * Avdelningarna för dagslistorna (/nyheter/…, /sport/…) och startsidans
 * "Igår på Text TV". Flyttade från Dagsnyheter-controllern 2026-10-16
 * (todo #16) så att startsidan räknar på samma sidor.
 *
 * Per avdelning:
 *  - name:     ordet i titeln på datumsidorna
 *  - yesterday: titeln på /igar-sidan
 *  - live_url: dit dagens datum skickas
 *  - most_read: typ för most_read-cachen
 *  - groups:   [rubrik, första sida, sista sida], kontrollerat mot SVT:s
 *              indexsidor 101 och 104 2026-10-14
 *  - exclude:  notissidor (stympade rubriker), kulturindex, nyhetsrullen
 *  - results:  resultatsidor med dagsarkiv att länka till
 *  - other:    den andra avdelningen, för korslänkar
 */
function day_sections()
{
	return [
		"nyheter" => [
			"name" => "Nyheter",
			"yesterday" => "Gårdagens nyheter",
			"today" => "Dagens nyheter",
			"live_url" => "/",
			"live_label" => "Dagens nyheter",
			"now_label" => "Startsidan just nu",
			"most_read" => "news",
			"groups" => [
				["Inrikes", 106, 129],
				["Utrikes", 130, 149],
				// 190–199 är SVT:s servicesidor (bemanning, sidindex m.m.).
				["Kultur och övrigt", 150, 189],
			],
			"exclude" => [127, 128, 148, 150, 188],
			"results" => [],
			"other" => "sport",
		],
		"sport" => [
			"name" => "Sportnyheter",
			"yesterday" => "Gårdagens sportnyheter",
			"today" => "Dagens sportnyheter",
			"live_url" => "/300",
			"live_label" => "Dagens sport 300",
			"now_label" => "Sport 300 just nu",
			"most_read" => "sport",
			"groups" => [
				["Sport", 303, 329],
			],
			"exclude" => [],
			"results" => [
				377 => "Målservice",
			],
			"other" => "nyheter",
		],
	];
}

/**
 * Alla nyheter en dag för en avdelning ur day_sections(), som de visas på
 * dagslistorna. Flyttad från Dagsnyheter-controllern 2026-10-16 (todo #16)
 * så att startsidans "Igår på Text TV" visar samma nyheter som /nyheter/igar.
 *
 * Rådatan är en rad per exakt rubrik, men samma nyhet förekommer i flera
 * varianter (rättat stavfel, ändrad siffra, flyttad till annan sida), och
 * vid midnatt får varje sida en ny version eftersom datumraden ändras.
 * Därför:
 *  - brus bort (för korta rubriker, testsidor)
 *  - nyheter som sändes redan dagen innan räknas inte (kvarliggare)
 *  - snarlika rubriker slås ihop till en nyhet
 *
 * @param array  $conf     En avdelning ur day_sections().
 * @param string $day_ymd  Datum, Y-m-d.
 * @param bool   $is_sport Sportavdelningen (strängare brusfilter).
 * @return array [nyheter nyast först, samma nyheter per rubriknyckel].
 *               Varje nyhet har title, time, page_num och permalink.
 */
function get_day_stories($conf, $day_ymd, $is_sport)
{
	// Hämta hela avdelningens intervall en gång; grupperna sorteras av anroparen.
	$first_num = $conf["groups"][0][1];
	$last_num = $conf["groups"][sizeof($conf["groups"]) - 1][2];
	$rows = get_headlines_for_day($first_num, $last_num, $conf["exclude"], $day_ymd);

	if (empty($rows)) {
		return [[], []];
	}

	$prev_day = new DateTime($day_ymd, new DateTimeZone("Europe/Stockholm"));
	$prev_day->modify("-1 day");
	$prev_keys = [];
	foreach (get_headlines_for_day($first_num, $last_num, $conf["exclude"], $prev_day->format("Y-m-d")) as $row) {
		$prev_keys[day_story_key($row->title)] = true;
	}

	$stories = [];
	// Äldst först, så att den första sändningen blir nyhetens tid.
	foreach (array_reverse($rows) as $row) {
		$title = trim(preg_replace('/\s+/u', ' ', $row->title));
		if (day_story_is_noise($title, $is_sport)) {
			continue;
		}
		$key = day_story_key($title);
		// Exakt samma rubrik som dagen innan = kvarliggare. Inte ungefärlig
		// likhet här: "Sverige till final" dagen efter "… semifinal" är en
		// ny nyhet och får inte försvinna.
		if (isset($prev_keys[$key])) {
			continue;
		}
		$merged = false;
		foreach ($stories as &$story) {
			if (day_story_matches($key, (int) $row->page_num, $story)) {
				// Behåll första tiden men senaste rubrik och version.
				if ((int) $row->id > $story["id"]) {
					$story["title"] = $title;
					$story["id"] = (int) $row->id;
					$story["page_num"] = (int) $row->page_num;
				}
				$story["keys"][] = $key;
				$story["pages"][(int) $row->page_num] = true;
				$merged = true;
				break;
			}
		}
		unset($story);
		if (!$merged) {
			$stories[] = [
				"key" => $key,
				"keys" => [$key],
				"pages" => [(int) $row->page_num => true],
				"title" => $title,
				"id" => (int) $row->id,
				"page_num" => (int) $row->page_num,
				"time" => substr($row->first_added, 11, 5),
				// Hela tiden, för uppslaget mot Brottsplatskartan (todo #21).
				"first_added" => (string) $row->first_added,
			];
		}
	}

	// Ett återanvänt objekt räcker för permalänkarna.
	$link_page = new texttv_page();
	$items = [];
	$items_by_key = [];
	foreach (array_reverse($stories) as $story) {
		$link_page->id = $story["id"];
		$link_page->num = $story["page_num"];
		$link_page->title = $story["title"];
		$item = [
			"title" => $story["title"],
			"time" => $story["time"],
			"unix" => (int) strtotime($story["first_added"] . " Europe/Stockholm"),
			"page_num" => $story["page_num"],
			"permalink" => $link_page->get_permalink(),
		];
		foreach ($story["keys"] as $one_key) {
			$items_by_key[$one_key] = $item;
		}
		$items[] = $item;
	}

	return [$items, $items_by_key];
}

/**
 * Dagens mest lästa, matchade mot get_day_stories(), så att tid, sidnummer
 * och länk är desamma i båda listorna. Mest lästa nyheter från dagen innan
 * (kvarliggare) finns inte bland nyheterna och hoppas därför över.
 *
 * @param array $most_read_pages Sidor ur most_read_pages_cached().
 * @param array $items_by_key    Andra värdet från get_day_stories().
 * @param int   $limit           Max antal.
 * @return array Nyheter i samma form som get_day_stories().
 */
function get_day_most_read($most_read_pages, $items_by_key, $limit)
{
	$most_read = [];
	$seen = [];
	foreach ($most_read_pages as $one) {
		$key = day_story_key($one["title"]);
		$item = isset($items_by_key[$key]) ? $items_by_key[$key] : null;
		if (!$item) {
			foreach ($items_by_key as $other_key => $other_item) {
				if (day_same_story($key, $other_key, (int) $one["page_num"] === $other_item["page_num"])) {
					$item = $other_item;
					break;
				}
			}
		}
		if (!$item || isset($seen[$item["permalink"]])) {
			continue;
		}
		$seen[$item["permalink"]] = true;
		$most_read[] = $item;
		if (sizeof($most_read) >= $limit) {
			break;
		}
	}
	return $most_read;
}

/**
 * Gårdagens mest lästa nyheter för startsidans "Igår på Text TV" (todo #16).
 *
 * Startsidan renderas om var fjärde sekund (nginx-cachens TTL), och
 * sammanställningen kör två rubrikqueries plus most_read. Gårdagen ändras
 * inte efter midnatt, så resultatet räknas fram en gång per dygn och sparas
 * bredvid most_read-cachen (som också städar bort gamla filer).
 *
 * Räknar en annan worker redan fram listan väntar vi inte på den, utan
 * startsidan visas utan rubriker. Det gäller bara de första sekunderna
 * efter midnatt.
 *
 * @param int $count Antal nyheter.
 * @return array Nyheter i samma form som get_day_stories(), eller tom.
 */
function yesterday_top_news($count)
{
	$yesterday = new DateTime("yesterday", new DateTimeZone("Europe/Stockholm"));
	$day_ymd = $yesterday->format("Y-m-d");

	$dir = sys_get_temp_dir() . "/texttv-most-read";
	$file = $dir . "/startpage-news-" . $day_ymd . ".json";

	$read = function () use ($file) {
		$json = @file_get_contents($file);
		$items = $json === false ? null : json_decode($json, true);
		return is_array($items) ? $items : null;
	};

	$items = $read();
	if ($items !== null) {
		return array_slice($items, 0, $count);
	}

	if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
		error_log("texttv igår på startsidan: kan inte skapa cachekatalogen $dir");
		return [];
	}
	$lock = @fopen($file . ".lock", "c");
	if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
		if ($lock) {
			fclose($lock);
		}
		return [];
	}

	// En annan worker kan ha skrivit filen mellan läsningen och låset.
	$items = $read();
	if ($items === null) {
		$conf = day_sections()["nyheter"];
		list($stories, $stories_by_key) = get_day_stories($conf, $day_ymd, false);
		list($most_read_pages, $most_read_ttl) = most_read_pages_cached($conf["most_read"], strtotime($day_ymd . " 12:00"));
		// Spara tio, så att antalet på startsidan kan ändras utan omräkning.
		$items = $stories ? get_day_most_read($most_read_pages, $stories_by_key, 10) : [];

		// most_read svarar med en kort ttl när en annan worker räknar om och
		// den bara har gårdagens ögonblicksbild från före midnatt. Spara inte
		// den, utan räkna igen vid nästa visning.
		if ($most_read_ttl > 5) {
			$tmp = $file . "." . getmypid() . ".tmp";
			if (@file_put_contents($tmp, json_encode($items)) !== false) {
				@rename($tmp, $file);
			}
		}
	}

	flock($lock, LOCK_UN);
	fclose($lock);

	return array_slice($items, 0, $count);
}

/**
 * Jämförelsenyckel för en rubrik: gemener, bara bokstäver och siffror.
 * "S: Saknas möjlighet" och "S:Saknas möjlighet" blir samma nyckel.
 */
function day_story_key($title)
{
	return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower((string) $title));
}

/**
 * Om två nycklar är samma nyhet.
 *
 * På samma sidnummer samma dag är en snarlik rubrik nästan alltid samma
 * nyhet som uppdaterats ("Två/Tre döda i …", rättade stavfel), så där
 * räcker 85 % likhet. Mellan olika sidor krävs 94 %: där finns olika
 * nyheter som skiljer sig med ett ord ("Riksbanken sänker/höjer räntan"
 * 85 %, "Tre/Två döda i rysk attack" 92 %), medan samma nyhet på två
 * sidor ligger högre ("Bergand vann - /och sedan …" 95 %). Testat på
 * riktiga och konstruerade par 2026-10-14.
 */
function day_same_story($a, $b, $same_page)
{
	if ($a === $b) {
		return true;
	}
	if (abs(strlen($a) - strlen($b)) > 12) {
		return false;
	}
	similar_text($a, $b, $percent);
	return $percent >= ($same_page ? 85 : 94);
}

/**
 * Om en rad hör till en nyhet som redan finns i listan. Jämför mot alla
 * rubrikvarianter nyheten haft, och räknar som "samma sida" om raden står
 * på något av de sidnummer nyheten stått på. Annars delas en nyhet som
 * uppdateras i flera steg, eller flyttar och sedan uppdateras, i flera.
 */
function day_story_matches($key, $page_num, $story)
{
	$same_page = isset($story["pages"][$page_num]);
	foreach ($story["keys"] as $story_key) {
		if (day_same_story($key, $story_key, $same_page)) {
			return true;
		}
	}
	return false;
}

/**
 * Rubriker som inte är nyheter: tomma, SVT:s testsidor ("Test 12 okt
 * 2026"), och på sportsidorna sidrubriker som "Ishockey NHL" eller
 * "Premier League" (högst två ord). På nyhetssidorna får korta rubriker
 * som "Påven död" stå kvar.
 */
function day_story_is_noise($title, $is_sport)
{
	$title = trim($title);
	if ($title === "" || preg_match('/^test\b/iu', $title)) {
		return true;
	}
	return $is_sport && sizeof(preg_split('/\s+/u', $title)) <= 2;
}

function get_latest_updated_pages($from, $to, $maxcount = 20) {
	$from = (int) $from;
	$to = (int) $to;
	// Interpoleras i LIMIT nedan — cast:as som $from/$to. Alla anropare skickar
	// literaler idag, men parametern ska inte vara ett hål om någon kopplar den
	// till input. Se L1 i todos/08-sakerhetsgranskning-2026-08-01.md.
	$maxcount = (int) $maxcount;

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

	// Interpoleras i INTERVAL respektive LIMIT nedan. Se L1 i #08.
	$days = (int) $days;
	$limit = (int) $limit;

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

// Här låg log2db(), json_encode_pretty() och removeWhiteSpace(). De hade bara
// fb.php som anropare och blev döda när Facebook Messenger-webhooken togs bort
// 2026-08-01 (K4). log2db() var skrivvägen in i texttv_log — tabellen finns
// kvar i databasen med sin historik, det är bara koden som är borta.
// Se L8 i todos/08-sakerhetsgranskning-2026-08-01.md.

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
/**
 * Utdrag ur ett blogginlägg: utan HTML, med blanktecken ihopslagna och
 * avkortat vid ett ord med "…". Börjar texten med rubriken tas den bort,
 * annars står rubriken två gånger i bloggöversikten (todo #19).
 */
function blogg_utdrag($html, $max_length = 155, $title = '')
{
	// Stycken och radbrytningar blir mellanslag, annars blir
	// "<p>Hej</p><p>Då</p>" "HejDå".
	$html = preg_replace('#<(br|/p|/div|/li|/h[1-6])\b[^>]*>#i', ' ', (string) $html);
	$text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
	$text = trim(preg_replace('/\s+/u', ' ', $text));

	// Rubriken bara som hela ord: "Nytt" ska inte klippa "Nyttig".
	$title = trim((string) $title);
	if ($title !== '' && preg_match('/^' . preg_quote($title, '/') . '(?!\p{L})/iu', $text)) {
		$text = ltrim(mb_substr($text, mb_strlen($title)), " .:–-");
	}

	if (mb_strlen($text) <= $max_length) {
		return $text;
	}

	$text = mb_substr($text, 0, $max_length + 1);
	$last_space = mb_strrpos($text, ' ');
	if ($last_space) {
		$text = mb_substr($text, 0, $last_space);
	}

	return rtrim($text, " ,.:;–-") . '…';
}

/**
 * Normaliserad nyhetsrubrik, samma som Brottsplatskartans
 * NewsArticle::normalizeTitle(): gemener, ihopslagna blanktecken och utan
 * avslutande "..191" (Text TV sätter ibland sidnumret efter prickar).
 */
function bpk_titel_nyckel($title)
{
	$t = mb_strtolower(trim((string) $title));
	$t = preg_replace('/\.{2,}[\d\s]*$/u', '', $t);
	return trim(preg_replace('/\s+/u', ' ', $t));
}

/**
 * Hämtar JSON från Brottsplatskartans API, cachat i en fil i tempkatalogen
 * (som yesterday_top_news()). Todo #21.
 *
 * - $ttl sekunder vid lyckad hämtning, $ttl_fel efter ett fel. Vid fel
 *   behålls den senaste goda datan, bara giltighetstiden förlängs.
 * - Bara en worker hämtar åt gången (flock utan väntan). De andra får den
 *   gamla datan direkt, eller null om ingen finns, och väntar inte. Annars
 *   kunde alla php-fpm-workers (max 10) hänga på ett långsamt
 *   Brottsplatskartan när cachen gick ut, och hela sajten med dem.
 * - curl med 1 s anslutning och 2 s totalt.
 *
 * CI:s cachedrivrutin ger deprecation-varningar på PHP 8.2, därav en egen.
 *
 * @return mixed Avkodad JSON (assoc), eller null.
 */
function bpk_hamta_json($url, $namn, $ttl, $ttl_fel)
{
	$dir = sys_get_temp_dir() . "/texttv-bpk";
	$file = $dir . "/" . $namn . ".json";

	$las_cache = function () use ($file) {
		$cached = @file_get_contents($file);
		$wrap = $cached === false ? null : json_decode($cached, true);
		return is_array($wrap) && array_key_exists("data", $wrap) ? $wrap : null;
	};

	$wrap = $las_cache();
	if ($wrap && isset($wrap["giltig_till"]) && $wrap["giltig_till"] > time()) {
		return $wrap["data"];
	}
	$gammal = $wrap ? $wrap["data"] : null;

	if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
		return $gammal;
	}
	$lock = @fopen($file . ".lock", "c");
	if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
		// En annan worker hämtar redan.
		if ($lock) {
			fclose($lock);
		}
		return $gammal;
	}

	// En annan worker kan ha hunnit skriva filen mellan läsningen och låset.
	$wrap = $las_cache();
	if ($wrap && isset($wrap["giltig_till"]) && $wrap["giltig_till"] > time()) {
		flock($lock, LOCK_UN);
		fclose($lock);
		return $wrap["data"];
	}

	$json = false;
	if (function_exists("curl_init")) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 1,
			CURLOPT_TIMEOUT => 2,
			CURLOPT_USERAGENT => "texttv.nu",
		]);
		$json = curl_exec($ch);
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
			$json = false;
		}
	}
	$data = is_string($json) ? json_decode($json, true) : null;

	if ($data === null) {
		error_log("texttv: kunde inte hämta $url");
		$data = $gammal;
		$giltig_till = time() + $ttl_fel;
	} else {
		$giltig_till = time() + $ttl;
	}

	$tmp = $file . "." . getmypid() . ".tmp";
	if (@file_put_contents($tmp, json_encode(["giltig_till" => $giltig_till, "data" => $data])) !== false) {
		@rename($tmp, $file);
	}

	flock($lock, LOCK_UN);
	fclose($lock);

	return $data;
}

/**
 * Polishändelser som Brottsplatskartan kopplat till nyheter på Text TV
 * (AI-matchning där, /api/texttvHandelser). Nyckel: bpk_titel_nyckel() av
 * nyhetens rubrik, värde: högst tre händelser (typ, plats, tid_iso8601, url).
 * Tio minuters cache. Todo #21.
 *
 * @return array<string, array>
 */
function bpk_texttv_handelser()
{
	static $map = null;
	if ($map !== null) {
		return $map;
	}

	$data = bpk_hamta_json("https://brottsplatskartan.se/api/texttvHandelser?dagar=14", "texttv-handelser-v3", 600, 120);

	$map = [];
	if (is_array($data) && isset($data["data"]) && is_array($data["data"])) {
		foreach ($data["data"] as $row) {
			$h = isset($row["handelse"]) && is_array($row["handelse"]) ? $row["handelse"] : [];
			if (empty($row["texttv_titel_nyckel"]) || empty($h["url"]) || empty($h["id"])) {
				continue;
			}
			$key = (string) $row["texttv_titel_nyckel"];
			$id = (int) $h["id"];
			if (isset($map[$key][$id]) || (isset($map[$key]) && sizeof($map[$key]) >= 3)) {
				continue;
			}
			$map[$key][$id] = [
				"typ" => (string) ($h["typ"] ?? ""),
				"plats" => (string) ($h["plats"] ?? ""),
				"tid_iso8601" => (string) ($h["tid_iso8601"] ?? ""),
				"url" => (string) $h["url"],
				"bild" => (string) ($h["bild"] ?? ""),
				// När nyheten låg på Text TV, för att inte koppla en äldre
				// nyhet med samma rubrik till händelsen.
				"texttv_unix" => !empty($row["texttv_tid"]) ? (int) strtotime($row["texttv_tid"] . " Europe/Stockholm") : 0,
			];
		}
	}

	return $map;
}

/**
 * Brottsplatskartans händelser för en nyhetsrubrik som låg på Text TV vid
 * $unix, eller tom array. Tidsvillkoret behövs eftersom rubriker återkommer:
 * "Man skjuten i Malmö" på en arkivsida från 2019 ska inte få en händelse
 * från i dag. Brottsplatskartan ger tiden för nyhetens första version; en
 * nyhet ligger kvar några dygn, därför ett dygn före till tre efter.
 */
function bpk_handelser_for_titel($title, $unix)
{
	$key = bpk_titel_nyckel($title);
	if ($key === '' || !$unix) {
		return [];
	}
	$map = bpk_texttv_handelser();
	if (!isset($map[$key])) {
		return [];
	}
	return array_values(array_filter($map[$key], function ($h) use ($unix) {
		$diff = (int) $unix - $h["texttv_unix"];
		return $h["texttv_unix"] && $diff >= -86400 && $diff <= 3 * 86400;
	}));
}

/**
 * Kartbild från Brottsplatskartans tileserver, eller '' om adressen inte
 * pekar dit (data från ett externt API skrivs ut i src).
 */
function bpk_kartbild($url)
{
	$url = (string) $url;
	return preg_match('#^https://kartbilder\.brottsplatskartan\.se/#', $url) ? $url : '';
}

/**
 * Länk till en händelse på Brottsplatskartan, märkt så att trafiken från
 * texttv.nu syns där.
 */
function bpk_lank($url, $medium)
{
	$url = (string) $url;
	if (!preg_match('#^https://brottsplatskartan\.se/#', $url)) {
		return '';
	}
	return $url . (strpos($url, '?') === false ? '?' : '&') . 'utm_source=texttv.nu&utm_medium=' . rawurlencode($medium);
}
