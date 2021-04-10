<?php

echo "\n---------- Begin ----------";

error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'updater-functions.php';

// This is 0 by default = script can run forever
// We set it to a value so no import scripts will hang forever
#echo ini_get('max_execution_time');
set_time_limit(60);

// Get number of already running updates
#$cmd = "ps auxwww | grep updater.php | grep -vc grep";
$cmd = "ps auxwww | grep updater.php | grep -v grep | grep -v bin/sh | wc -l";
$num_running = trim(exec($cmd));

if ($num_running > 5) {
    $msg = "Detected $num_running already running script, so no import, quitting.";
    $msg .= "\nargs:\n" . print_r($argv, true);
    echo "\n$msg\n";
    error_log("TextTV.nu $msg");
    mail("par.thernstrom@gmail.com", "TextTV.nu alert: import script going bananas", $msg);
    exit;
}

echo "\nNum running scripts: $num_running";

// Connect to db
if (isset($_ENV["LOGNAME"]) && $_ENV["LOGNAME"] == "bonny") {
    // running on local mac computer
    $dbServer = "localhost";
    $dbUser = "root";
    $dbPassword = "root";
} else {
    // live/digital ocean
    $dbServer = "localhost";
    $dbUser = "root";
    $dbPassword = "root";
}

$mysqli = new mysqli($dbServer, $dbUser, $dbPassword, "texttv.nu");
if (! $mysqli->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $mysqli->error);
    exit;
} else {
    // printf("Current character set: %s\n", $mysqli->character_set_name());
}

$time_start = microtime(true);

$stats = array(
    "stat_num_curl_requests" => 0,
    "stat_num_304" => 0,
    "stat_num_200" => 0,
    "stat_num_other" => 0,
    "num_pages_maybe_updated" => 0,
    "stat_num_pages_updated" => 0,
    "stat_num_sql_queries" => 0,
    "total_size_download" => 0
);

// Setup options passed on as args
// Like this:
// $ php updater.php --page 100 --forceUpdate --verbose
$arr_options = array(
    "page:", // requires value, the page num to update
    "forceUpdate", // does not accept value, use to force update, even if db is newer than remote
    "verbose", // switch
    "pageRange:" // range, i.e. "100-199"
);
$opts = getopt("", $arr_options);

$arg_page = isset($opts["page"]) ? $opts["page"] : false;
$arg_forceUpdate = isset($opts["forceUpdate"]);
$arg_verbose = isset($opts["verbose"]);
$arg_pageRange = isset($opts["pageRange"]) ? $opts["pageRange"] : null;

// make array containing all page nums in pagerange
if ($arg_pageRange) {
    $arg_pageRange = explode('-', $arg_pageRange);
    $arg_pageRange = range($arg_pageRange[0], $arg_pageRange[1]);
}

// Get all URLs to fetch
$urls = "texttv-urls-full.txt";
$arr_urls = explode("\n", file_get_contents($urls));
$arr_urls = array_filter($arr_urls);

// For test: Just keep a few pages to test with
//$arr_urls = array_splice($arr_urls, 0, 5);

// Create multi dimensional array with page data
$arr_urls = array_map(function ($url) {

    preg_match("#(\d{3})\.html#", $url, $matches);

    $page_num = null;
    if (sizeof($matches) == 2) {
        $page_num = $matches[1];
    }

    $arr = array(
        "url" => $url,
        "num" => $page_num,
        "do_update" => true,
        "db_last_updated" => null,
        "update_reason" => null,
        "no_update_reason" => null
    );

    return $arr;
}, $arr_urls);

// make array contain page numbers as keys = easy to get pages later on
$arr_pages_keys = array();
foreach ($arr_urls as $page_vals) {
    $arr_pages_keys[] = $page_vals["num"];
}
$arr_urls = array_combine($arr_pages_keys, $arr_urls);

// If argument is passed to check only one page, make array only include that page
if ($arg_page) {
    echo "\nWill only check page $arg_page";
    $arr_urls = array(
        $arg_page => $arr_urls[$arg_page]
    );
}

// If arg_pageRange is set then only keep those pages
if (isset($arg_pageRange)) {
    $arr_urls = array_intersect_key($arr_urls, array_flip($arg_pageRange));
}

// Curl handle that will be re-used during the script, to make requests use http keep-alives
$ch = curl_init();

// Get date updated for each page and add to page array
// Tip: don't add ID here or it will take forver to run this query!
$sql = "
      SELECT page_num, MAX(date_updated) as date_updated
      #, MAX(id) as max_id
      FROM texttv
      WHERE page_num IN(".implode(",", array_keys($arr_urls)).")
      GROUP BY page_num
      # ORDER BY MAX(date_updated) ASC
";

$res = $mysqli->query($sql);
$stats["stat_num_sql_queries"]++;

while ($row = $res->fetch_assoc()) {
    // 2017-04-10 21:13:24
    $arr_urls[$row["page_num"]]["db_last_updated"] = $row["date_updated"];

    // 1491851604
    $arr_urls[$row["page_num"]]["db_last_updated_unix"] = strtotime($row["date_updated"]);

    # $arr_urls[$row["page_num"]]["db_max_id"] = $row["max_id"];
}

// Get max id for each page and add to page array
// May wanna use that to check if latest date and max id is different = something is weird
/*$sqlMaxIdTmpl = '
    SELECT id, page_num FROM texttv
    WHERE page_num = %1$d
    ORDER BY id DESC
    LIMIT 1
';

foreach ($arr_urls as $oneUrl) {
    $pageNum = $oneUrl["num"];
    $sql = sprintf($sqlMaxIdTmpl, $pageNum);
    $res = $mysqli->query($sql);
    $stats["stat_num_sql_queries"]++;
    $row = $res->fetch_assoc();
    $arr_urls[$pageNum]["db_max_id"] = $row["id"];
}
*/

/*
When we get here format is like:

Array
(
    [377] => Array
        (
            [url] => http://www.svt.se/svttext/web/pages/377.html
            [num] => 377
            [do_update] => false
            [db_last_updated] => 2017-04-10 21:13:24
            [db_last_updated_unix] => 1491851604
            [update_reason] =>
        )

)
*/

// Prepare sql statement that we re-use for all inserts
$stmt_add_page = $mysqli->prepare(
    "INSERT INTO texttv(page_num, page_content, date_added, date_updated, next_page, prev_page, title)
    VALUES (?, COMPRESS(?), ?, ?, ?, ?, ?)"
);

if (! $stmt_add_page) {
    echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    return;
}

// Prepare sql statement to update last check time
$stmt_update_page_date_updated = $mysqli->prepare("UPDATE texttv SET date_updated = ? WHERE id = ?");

if (! $stmt_update_page_date_updated) {
    echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    return;
}

// If forceUpdate is set then mark all pages to be updated
if ($arg_forceUpdate) {
    echo "\nWill skip checks and force update from remote.";

    array_walk($arr_urls, function (& $pagedata) {
        $pagedata["do_update"] = true;
        $pagedata["update_reason"] = "argument forceUpdate was passed";
    });
}

// Get all existing page_contents from db for all the pages we want to check remote data on
// This is so we can compare current stored data with remote data, and actually only
// save a new version if remote has changed
$arr_page_nums_to_update = array();
$stmt_select_page_contents = $mysqli->prepare(
    "SELECT id, page_num, date_updated, UNCOMPRESS(page_content)
    FROM texttv WHERE page_num = ?
    ORDER BY date_updated
    DESC LIMIT 1"
);

// Get content for pages that we want to check for updates for
array_walk($arr_urls, function (& $pagedata) use ($mysqli, & $stats, & $arr_page_nums_to_update, $stmt_select_page_contents) {

    if ($pagedata["do_update"]) {
        if (! $stmt_select_page_contents->bind_param("s", $pagedata["num"])) {
            echo "bind_param failed";
            exit;
        }

        if (! $stmt_select_page_contents->execute()) {
            echo "Execute failed on line " . __LINE__;
            exit;
        };

        $stats["stat_num_sql_queries"]++;

        $stmt_select_page_contents->bind_result($id, $page_num, $date_updated, $page_content);
        $stmt_select_page_contents->fetch();

        $pagedata["prev_page_content"] = $page_content;
        $pagedata["prev_page_id"] = $id;
    }
});

// Must close statement to begin new statement later on
$stmt_select_page_contents->close();

/*
When we get here format is like:

Array
(
    [377] => Array
        (
            [url] => http://www.svt.se/svttext/web/pages/377.html
            [num] => 377
            [do_update] => false
            [db_last_updated] => 2017-04-10 21:13:24
            [update_reason] =>
            [db_last_updated_unix] => 1491851604,
            [prev_page_content] => "a:1:{i:0;s:1596:"<div class="root"><span class="toprow"> 377 SVT Text" ...
            [prev_page_id] => 14067559
        )

)
*/

#print_r($arr_urls);
#exit;

// Now we have a list of urls and whether or not we should check for updates on them
// Now use curl and ifmodifiedsince to download them
array_walk($arr_urls, function ($pagedata) use ($ch, $mysqli, $stmt_add_page, $stmt_update_page_date_updated, & $stats, $arg_forceUpdate) {

    echo "\n\nChecking page {$pagedata["num"]}";

    if (false == $pagedata["do_update"]) {
        echo "\nwill not check remote page because " . $pagedata["no_update_reason"];
        return;
    }

    if ($pagedata["update_reason"]) {
        echo "\nwill check remote page because ". $pagedata["update_reason"];
    }

    // Setup curl
    #$verbose = fopen('php://temp', 'rw+');

    curl_reset($ch);
    $curl_options = array(
        CURLOPT_RETURNTRANSFER => true,
        // http://stackoverflow.com/questions/3757071/php-debugging-curl
        CURLOPT_VERBOSE => true,
        CURLOPT_FILETIME => true, // attempt to retrieve the modification date of the remote document
        CURLOPT_ENCODING => "", // set to empty to enable all supported encodings, like deflate, gzip
        CURLOPT_HEADER => true,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_TIMECONDITION => CURL_TIMECOND_IFMODSINCE,
        CURLOPT_URL => $pagedata["url"],
        #CURLOPT_STDERR, $verbose
    );

    // If we have the date of the last update use that to only fetch the page
    // if it has been modified since this date
    if (! empty($pagedata["db_last_updated_unix"])) {
        if ($arg_forceUpdate) {
            // don't check time value if forceUpdate
        } else {
            /*
            The time in seconds since January 1st, 1970.
            The time will be used by CURLOPT_TIMECONDITION. By default, CURL_TIMECOND_IFMODSINCE is used.
            */
            echo "\nUsing a CURLOPT_TIMEVALUE value of ";
            echo "{$pagedata["db_last_updated"]}";
            $curl_options[CURLOPT_TIMEVALUE] = $pagedata["db_last_updated_unix"];
        }
    }

    curl_setopt_array($ch, $curl_options);

    // Do the actual fetch of the page from the SVT servers
    $curl_result = curl_exec($ch);
    $stats["stat_num_curl_requests"]++;

    $arr_curlinfo = curl_getinfo($ch);

    /*
    $arr_curlinfo is like:

    Array
    (
        [url] => http://www.svt.se/svttext/web/pages/377.html
        [content_type] => text/html; charset=UTF-8
        [http_code] => 304
        [header_size] => 270
        [request_size] => 157
        [filetime] => 1491852763
        [ssl_verify_result] => 0
        [redirect_count] => 0
        [total_time] => 0.042118
        [namelookup_time] => 0.018494
        [connect_time] => 0.030611
        [pretransfer_time] => 0.031846
        [size_upload] => 0
        [size_download] => 0
        [speed_download] => 0
        [speed_upload] => 0
        [download_content_length] => 0
        [upload_content_length] => -1
        [starttransfer_time] => 0.042074
        [redirect_time] => 0
        [redirect_url] =>
        [primary_ip] => 95.101.97.5
        [certinfo] => Array
            (
            )

        [primary_port] => 80
        [local_ip] => 192.168.0.15
        [local_port] => 50182
        [request_header] => GET /svttext/web/pages/377.html HTTP/1.1
                            Host: www.svt.se
                            Accept-Encoding: deflate, gzip
                            If-Modified-Since: Mon, 10 Apr 2017 19:42:13 GMT
    )
    */

    $remote_http_code = $arr_curlinfo["http_code"];
    $remote_filetime = $arr_curlinfo["filetime"];
    $request_header = $arr_curlinfo["request_header"];
    $size_download = $arr_curlinfo["size_download"];

    $stats["total_size_download"] += $size_download;

    /*
    $curl_result is like:

    Content-Type: text/html; charset=UTF-8
    Last-Modified: Mon, 10 Apr 2017 19:32:43 GMT
    ETag: W/"1bcf-15b595a9f78"
    Cache-Control: public, max-age=6
    Date: Mon, 10 Apr 2017 19:55:18 GMT
    Connection: keep-alive
    Access-Control-Allow-Origin: *
    */
    $lastModified = null;
    $headers = explode("\n", $curl_result);
    foreach ($headers as $oneHeader) {
        if (strpos($oneHeader, "Last-Modified") !== false) {
            $lastModified = str_replace("Last-Modified: ", "", $oneHeader);
        }
    }
    $lastModifiedYMD = date("Y-m-d H:i:s", strtotime($lastModified));

    # echo "\nremote_filetime: $remote_filetime (" . date("Y-m-d H:i:s", $remote_filetime) . ")";
    echo "\nlast modified: $lastModified";
    if (isset($pagedata["db_last_updated_unix"])) {
        echo "\nmax db_date_updated: {$pagedata["db_last_updated_unix"]} (" . date("Y-m-d H:i:s", $pagedata["db_last_updated_unix"]) . ")";
    }
    #echo "\nremote url: " . $pagedata["url"];
    #echo "\nhttp code: " . $remote_http_code;

    // If answer is ok then check contents and save to db if different
    if ($arg_forceUpdate) {
        // skip code checks when forceUpdate is set
    } else {
        if (304 == $remote_http_code) {
            echo "\nremote code was 304 Not Modified, so page is not changed, so no save.";
            $stats["stat_num_304"]++;

            // Store modified date so we can use that as if-modified-since the next time
            // 15 Apr 2017: no, don't do that. keep the real date we got before when actually updating the page
            // echo "\nSetting date_updated in database to lastModified date: $lastModifiedYMD";
            // echo "\nUpdating old page with id: " . $pagedata["prev_page_id"];

            /*
            $res = $stmt_update_page_date_updated->bind_param("si", $lastModifiedYMD, $pagedata["prev_page_id"]);
            if (! $res) {
                echo "Binding parameters failed on line " . __LINE__;
                echo $stmt_update_page_date_updated->errno;
                echo $stmt_update_page_date_updated->error;
                return;
            }

            $res = $stmt_update_page_date_updated->execute();
            if (! $res) {
                echo "Execute failed on line " . __LINE__;
                echo $stmt_update_page_date_updated->errno;
                echo $stmt_update_page_date_updated->error;
                return;
            }
            */

            // no update shall be done, so break out of this array_walk
            return;
        } elseif (200 != $remote_http_code) {
            echo "\nRemote code was not 200, so no save";
            echo "\nremote_http_code: $remote_http_code";
            echo "\nremote_http_code: $remote_http_code";
            $stats["stat_num_other"]++;
            print_r($arr_curlinfo);

            // break out of this array_walk
            return;
        }
    }

    // We get here if page was 200 (i.e. not for example 304)
    echo "\nRemote code was 200, so continue to check if page should be saved";
    $stats["stat_num_200"]++;
    $stats["num_pages_maybe_updated"]++;

    // Make array of remote data and fix some things like links and so on
    $arr_page_info = find_and_fix_things($curl_result);

    // compare fetched page contents with prev_page_contents
    // $pev_page_content _unserialize = array of pages
    $prev_page_content_unserialized = unserialize($pagedata["prev_page_content"]);

    // If new array and old array with pages is same then don't save this page again
    if (! $arg_forceUpdate && ( $prev_page_content_unserialized == $arr_page_info["page_contents"] )) {
        // remove date of page is updated, but actual page contents have not changed
        // don't add new page, but update date_updated date so it's the correct one next time we check the page
        echo "\nPage contents are the same in new fetched and olded saved, so no save";

        // Update date_updated
        #$mysqli->query("UPDATE texttv SET date_updated = '{$lastModifiedYMD}' WHERE id = " . $pagedata["prev_page_id"]);
        #$stats["stat_num_sql_queries"]++;

        // break out of this array_walk
        return;
    } else {
        // contents of local and remote page is changed
        if ($prev_page_content_unserialized != $arr_page_info["page_contents"]) {
            echo "\nNew and old contents are different, so save";
        } elseif ($arg_forceUpdate) {
            echo "\nNew and old contents is the same, but force update is true, so save";
        }
    }

    // continue inside array_walk

    $stats["stat_num_pages_updated"]++;

    // Setup data to be saved
    $next_page = empty($arr_page_info["next_page"]) ? "" : $arr_page_info["next_page"];
    $prev_page = empty($arr_page_info["prev_page"]) ? "" : $arr_page_info["prev_page"];
    $title = empty($arr_page_info["page_title"]) ? "" : $arr_page_info["page_title"];
    $serialized_page_contents = serialize($arr_page_info["page_contents"]);

    $str_date_updated = date("Y-m-d H:i:s");

	$title = mb_substr($title, 0, 255);

    $res = $stmt_add_page->bind_param(
        "isssiis",
        $pagedata["num"],
        $serialized_page_contents,
        $lastModifiedYMD,
        $lastModifiedYMD,
        $next_page,
        $prev_page,
        $title
    );

    if (! $res) {
        echo "Binding parameters failed: (" . $stmt_add_page->errno . ") " . $stmt_add_page->error;
        return;
    }

    $res = $stmt_add_page->execute();

    if (! $res) {
        echo "\nExecute failed on line ".__LINE__.": (" . $stmt_add_page->errno . ") " . $stmt_add_page->error;
		echo "\ntitle: $title";
        return;
    }

    $stats["stat_num_sql_queries"]++;

    echo "\nDone with page";
});

$time_end = microtime(true);
$time_script = round($time_end - $time_start, 3);

// Send a summary to stdout + the system log
$size_downloaded_kb = round($stats["total_size_download"] / 1000, 2);

$script = basename(__FILE__);

$summary = "TextTV.nu script_time=$time_script curl_requests={$stats['stat_num_curl_requests']} stat_num_200={$stats['stat_num_200']} stat_num_304={$stats['stat_num_304']} stat_num_other={$stats['stat_num_other']} stat_num_pages_maybe_updated={$stats['num_pages_maybe_updated']} stat_num_pages_updated={$stats['stat_num_pages_updated']} stat_num_sql_queries={$stats['stat_num_sql_queries']} total_size_download_kb={$size_downloaded_kb}";

echo "\n\n" . $summary . "\n\n";

// Log errors to own file so we easier can watch it
ini_set("error_log", "/var/log/texttv-update.log");

error_log($summary);
