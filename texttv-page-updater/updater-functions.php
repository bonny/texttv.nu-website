<?php

function find_next_page($page_contents)
{
    $prev_page = null;
    // Kolla efter nästa och föregående sida
    // var nextPage = "100.html";var previousPage = "101.html";
    preg_match('/nextPage = "(\d{3})/', $page_contents, $matches);
    if (isset($matches[1])) {
        $prev_page = $matches[1];
    }
    return $prev_page;
}

function find_prev_page($page_contents)
{

    $prev_page = null;
    preg_match('/previousPage = "(\d{3})/', $page_contents, $matches);

    if (isset($matches[1])) {
        $prev_page = $matches[1];
    }

    return $prev_page;
}

function find_and_fix_things($page_contents)
{

    $next_page = null;
    $prev_page = null;

    // Kolla efter nästa och föregående sida
    // var nextPage = "100.html";var previousPage = "101.html";
    #preg_match('/nextPage = "(\d{3})/', $page_contents, $matches);
    #if (isset($matches[1])) {
    #   $prev_page = $matches[1];
    #}
    $next_page = find_next_page($page_contents);

    $prev_page = find_prev_page($page_contents);

    if (! $page_contents) {
        return false;
    }

    // Leta ut innehållet
    $roots = explode('<pre class="root', $page_contents);
    array_shift($roots);

    // varje roots[n] är en sida
    // men inte med olika nummer, utan sub-sidor om det är en ff-sida
    for ($i=0; $i<sizeof($roots); $i++) {
        // se till att dom börjar med div root
        $roots[$i] = '<div class="root' . $roots[$i];

        // ta bort allt från sista </pre> och framåt
        // ligger lite skräp där
        $roots[$i] = preg_replace("/<\/pre>.*/mi", "</div>", $roots[$i]);

        // Replace links etc. in this inpage-subpage
        $roots[$i] = replace_root_stuff($roots[$i], $page_contents);
    }

    // From here on page_contents is an array
    $page_contents = $roots;

    $page_title = find_titles($page_contents);
    $page_links = find_links($page_contents);
    $page_contents = fix_links($page_links, $page_contents);

    for ($i=0; $i < sizeof($page_contents); $i++) {
        $page_contents[$i] = maybeChangeLineCount($page_contents[$i]);
    }

    /*
    // Before we save check if the contents of this one actually is different from the previous one
    if ($page_prev_version->arr_contents === $this->arr_contents) {

        // error_log("page contents for {$this->num} are the same, so updating existing");
        $this->save();
        return "saved_page_contents_not_changed";

    } else {

        #error_log("page contents for {$this->num} are not the same, so saving new");
        $this->save(true);
        return "saved";

    }
    */

    // please note that next page and prev page is the reverse because svt thinks prev is 101 when viewing 100..
    return array(
        "page_contents" => $page_contents,
        "page_title" => $page_title,
        "page_links" => $page_links,
        "prev_page" => $next_page,
        "next_page" => $prev_page
    );
}


// Leta upp alla .dh = rubriker
function find_titles($arr_contents)
{

    // Skapa title:s av dessa
    // numera som h1:or!
    $title = "";
    $page_contents =join(" ", $arr_contents);

    $pattern = '/<h1 class="[a-z ]*DH">([\w\dåäöÅÄÖüÜ :\',.()é\-"]+)/i';

    if (preg_match_all($pattern, $page_contents, $matches)) {
        $arr_titles = array();

        foreach ($matches[1] as $one_title) {

            if (trim($one_title)) {

                $arr_titles[] = trim($one_title);

            }

        }

        $title .= join(" | ", $arr_titles);

    }

    // Om ingen title, försök med metod två
    // dvs. ta title från första icke-tomma span:en efter .toprow
    if ( ! $title ) {

        # echo "<br>testing method two";

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $page_contents = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' . $page_contents;
        $doc->loadHTML($page_contents);

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query('//span');

        foreach ($nodes as $node) {

            $class = $node->getAttribute("class");

            // skip toprow
            if (strpos($class, "toprow") !== false) continue;

            $nodeValue = trim( $node->nodeValue );

            // skip empty rows
            if (empty($nodeValue)) continue;

            # echo "\n<br>" . $nodeValue;

            $title = $nodeValue;

            // we got a title - so we're done
            break;

        }
        #echo $doc->saveHTML();

    }

    // If title is all uppsercase we make only first char of each word uppercase instead. less ugly.
    if (isUpper($title)) {
        $title = mb_convert_case(strtolower($title), MB_CASE_TITLE);
    }

    return $title;

}

function isUpper($item) {
    $item = preg_replace('/\W+/', '', $item);
    $item = preg_replace('/[0-9]+/', '', $item);
    return ctype_upper($item);
}

// get all links in this page
function find_links($arr_contents) {

    // <a href="130.html">130</a>
    $page_inner_contents = implode("\n", $arr_contents);
    if (preg_match_all("/([\d]+).html/", $page_inner_contents, $matches)) {
        $arr_page_numbers = $matches[1];
        return $arr_page_numbers;
    }

    return array();
}

function fix_links($links, $arr_contents) {
    // echo site_url(); // http://www.texttv.nu/codeigniter/index.php
    // echo current_url(); // http://www.texttv.nu/codeigniter/index.php/sida/visa/100
    // echo uri_string(); // sida/visa/100
    // echo index_page(); // echo index_page();
    if (is_array($links) && sizeof($links) > 0) {
        foreach ($links as $one_link) {
            #$link_url = site_url("/$one_link");
            $link_url = "/$one_link";
            for ($i=0; $i <sizeof($arr_contents); $i++) {
                $arr_contents[$i] = str_replace("{$one_link}.html", $link_url, $arr_contents[$i]);
            }
        }
    }

    return $arr_contents;
}

/**
 * Make modifications to the page contents
 * For example fix so ranges of links work
 * and texts, like "nästa sida" becomes a link
 */
function replace_root_stuff($page_contents, $full_page_contents) {

    // Fixa så att text som kan vara en länk... blir en länk
    // $page_contents = str_replace("www.svt.se", "svt.se", $page_contents);
    // $page_contents = str_replace("svt.se", "www.svt.se", $page_contents);
    // $page_contents = autolink($page_contents, $limit=30);

    // Fixa så att "nästa sida" blir en länk tll nästa sida
    if (mb_strpos($page_contents, "nästa sida") !== FALSE) {
        // Mer på nästa sida
        #echo "\nfound 'nästa sida'";
        //$next_page = find_next_page($full_page_contents);
        $prev_page = find_prev_page($full_page_contents);
        #var_dump($next_page);
        $page_contents = str_replace("nästa sida", "<a href='/{$prev_page}'>nästa sida</a>", $page_contents);
    }

    // Ta bort "          Fortsättning följer >>>       "
    // &gt;&gt;&gt;
    if (mb_strpos($page_contents, "Fortsättning följer &gt;&gt;&gt;") !== FALSE) {
        // Mer på nästa sida
        $page_contents = str_replace("Fortsättning följer &gt;&gt;&gt;", "                       ", $page_contents);
    }

    // Första raden i varje root börjar alltid med
    //  398 SVT Text         Tisdag 27 dec 2011
    // så markera ut den med .toprow så vi kan stylea den
    $page_contents = preg_replace("/<span/", "</span><span", $page_contents, 1);
    $page_contents = preg_replace("/\">/", "\"><span class=\"toprow\">", $page_contents, 1);

    // länkar range
    // <span class="Y"> <a href="/130">130</a></span><span class="Y">-<a href="/131">131</a>
    $reg = '/<a href="\d{3}.html">(\d{3})<\/a><\/span><span class="Y">-<a href="\d{3}.html">(\d{3})<\/a>/msi';
    $replace = '<span class="Y"><a href="/\\1-\\2">\\1-\\2</a>';
    $page_contents = preg_replace($reg, $replace, $page_contents);

    // <span class="C">                <a href="/130">130</a>-<a href="/131">131</a>                </span>
    $reg = '/<a href="\d{3}.html">(\d{3})<\/a>-<a href="\d{3}.html">(\d{3})<\/a>/';
    $replace = '<a href="/\\1-\\2">\\1-\\2</a>';
    $page_contents = preg_replace($reg, $replace, $page_contents);

    //  <span class="Y"> <a href="/115">115</a></span><span class="Y">/<a href="/161">161</a>                </span>
    $reg = '/<a href="\d{3}.html">(\d{3})<\/a><\/span><span class="Y">\/<a href="\d{3}.html">(\d{3})<\/a>/';
    $replace = '<a href="/\\1,\\2">\\1/\\2</a>';
    $page_contents = preg_replace($reg, $replace, $page_contents);

    // länkar komma/slash
    // <a href="/121">121</a>/<a href="/165">165</a>
    $reg = '/<a href="\d{3}.html">(\d{3})<\/a>\/<a href="\d{3}.html">(\d{3})<\/a>/msi';
    $replace = '<a href="\\1,\\2">\\1/\\2</a>';
    $page_contents = preg_replace($reg, $replace, $page_contents);

    // Fixa så att TEXT......123
    // Blir länk av hela paketet
    #if (isset($_GET["test"])) {
        #$reg = '/>(.*?)([\.+]| )<a href="(\d{3}).html">\d{3}<\/a>/';
        //$replace = "><a href='\\3'>\\1\\2\\3</a>";
        #$replace = "><a class='link link-row' href='\\3'>\\1\\2\\3</a>";
        #$page_contents = preg_replace($reg, $replace, $page_contents);
    #}

    // Fixa så att "sida <nnn>"" blir länk (och inte bara <nnn>)
    // Fortsättning följer på sida <a href="/109">109</a>
    $page_contents = preg_replace("/sida <a href=\"([\d]{3}).html\">[\d]{3}<\/a>/", "<a href='/\\1'>sida \\1</a>", $page_contents);

    // fixa så att vi får h1-rubriker
    // <span class="Y DH">Kinesiskt bud lagt på Saab            </span>
    #if ($this->input->get("forceupdate")) {
    $page_contents = preg_replace('/<span class="Y DH">(.*?)<\/span>/', '<h1 class="Y DH">\\1</h1>', $page_contents);
    $page_contents = preg_replace('/<span class="Y bgB DH">(.*?)<\/span>/', '<h1 class="Y bgB DH">\\1</h1>', $page_contents);
    #}

    // slå ihop h1-or eller ta bort
    //  <h1 class="Y DH"> </h1><h1 class="Y DH">Ukraina: Proryssar tog statlig byggnad</h1>
    // <h1 class="Y DH"> </h1><h1
    $page_contents = str_replace('<h1 class="Y DH"> </h1><h1 class="Y DH">', '<h1 class="Y DH"> ', $page_contents);
    $page_contents = str_replace('<h1 class="Y bgB DH"> </h1><h1 class="Y bgB DH">', '<h1 class="Y bgB DH"> ', $page_contents);

    return $page_contents;

}

function maybeChangeLineCount( $page_contents ) {

    $expected_line_count = 25;

    $empty_line = str_repeat(" ", 39);
    $empty_line = " <span class='added-line'>" . $empty_line . "</span>";

    $arr_page_lines = explode("\n", $page_contents);
    $page_lines_count = count($arr_page_lines);

    if ( $page_lines_count < $expected_line_count ) {

        // Page has fewer lines than expected, so add lines from bottom
        // last line = </div>
        // last line - 1 = bottom nav
        // last line -2 = here we can add empty ones
        $lines_to_add = $expected_line_count - $page_lines_count;

        while ($lines_to_add--) {
            array_splice($arr_page_lines, -2, 0, $empty_line);
        }

        $page_contents = implode("\n", $arr_page_lines);

    }

    return $page_contents;

}

