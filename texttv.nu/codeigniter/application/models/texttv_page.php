<?php

class Texttv_page extends CI_Model {

	var
		// the id of this entry in db
		$id,

		// the number of the page
		$num, 

		// the contents of this page, including svt:s html for formatting
		// array since one page can have multiple pages, you know auto
		$arr_contents = array(),

		// the title of the page, if "intelligently" found on the page
		$title,

		// array with the pages that this page links to
		$links,

		// next and prev page, according to texttv
		$next_page,
		$prev_page,

		// when this page was updated from svt
		$date_updated_unix,
		
		// when page was added from svt (really!)
		$date_added_unix,

		// base url, where to look for remote pages
		$url_base = "http://www.svt.se/svttext/web/pages/"

		;

	function __construct($page_num = NULL)
	{
		// Call the Model constructor
		parent::__construct();
		
		if (!is_numeric($page_num) || strlen($page_num) != 3) {
			//exit("Ogiltigt sidnummer.");
			return FALSE;
		} else {
		
			$this->num = (int) $page_num;
			return $this->load();

		}

	}

	/**
	 * Make links local
	 */
	function fix_links() {
		// echo site_url(); // http://www.texttv.nu/codeigniter/index.php
		// echo current_url(); // http://www.texttv.nu/codeigniter/index.php/sida/visa/100
		// echo uri_string(); // sida/visa/100
		// echo index_page(); // echo index_page();
		if (is_array($this->links) && sizeof($this->links) > 0) {
			foreach ($this->links as $one_link) {
				$link_url = site_url("/$one_link");
				for ($i=0; $i <sizeof($this->arr_contents); $i++) {
					$this->arr_contents[$i] = str_replace("{$one_link}.html", $link_url, $this->arr_contents[$i]);
				}
			}
		}
	}

	function find_links() {
		// get all links in this page
		// <a href="130.html">130</a>
		$page_inner_contents = implode("\n", $this->arr_contents);
		if (preg_match_all("/([\d]+).html/", $page_inner_contents, $matches)) {
			$arr_page_numbers = $matches[1];
			$this->links = $arr_page_numbers;
		}
	}

	function load_links() {
		if (sizeof($this->links)) {
			foreach ($this->links as $one_link) {
				$one_sub_page = new Texttv_page((int)$one_link);
			}
		}
	}


	/**
	 * Load the page. From db or from web if outdated
	 * @param $by_id bool if we should load a page by id instead of page number
     */
    function load($from_archive = FALSE) {

		try {
			
			// first check if page exists in db and what age it has
			$do_update = FALSE;

			$this->db->select("id, page_num, UNCOMPRESS(page_content) AS page_content, date_updated, date_added, next_page, prev_page, title, is_shared");
			$this->db->from('texttv');

			if ($from_archive) {
				$this->db->where("id", $this->id);
			} else {
				$this->db->where("page_num", $this->num);
			}
			
			// get the most recent updated OR the one with the most recent id
			// used date_updated for a while
			$this->db->order_by("date_updated", "DESC");
			// changed back to order by id on 11 Apr 2017..and back to date again on 13 Apr 2017
			#$this->db->order_by("id", "DESC");
			
			$this->db->limit(1);
		
			$query = $this->db->get();

			if ($query->num_rows() == 1) {

				// exist, but how old?
				$result = $query->row();
				$this->id = $result->id;
				$this->num = $result->page_num;
				$this->next_page = $result->next_page;
				$this->prev_page = $result->prev_page;
				$this->is_shared = $result->is_shared;

				$this->arr_contents = @unserialize($result->page_content);
				if (FALSE === $this->arr_contents) {
					// @TODO: something went wrong during unserialize. fetch page again?
					// probably beacuse page was to long and did not fit BLOB
					$this->arr_contents = array();
				}
				$this->date_updated_unix = strtotime($result->date_updated);
				$this->date_added_unix = strtotime($result->date_added);
				$this->title = $result->title;
			
				// check age in minutes
				/*
				$max_age_minutes = 1;
				if ($this->date_updated_unix < (time()-(60*$max_age_minutes))) {
					//echo "to old, update!";
					$do_update = TRUE;
				} else {
					//echo "not to old, keep current page";
				}
				*/
				return true;
				
			} else {
				// not in db, update dammit!
				//$do_update = TRUE;
				
				// Not in db + archive = page that does not exist
				// (because never did, or because cleanup)
				return false;
				
			}

			/*if (isset($_GET["forceupdate"]) && $_GET["forceupdate"]) {
				//$do_update = TRUE; // debug
			}*/
						
			// Om arkiv = aldrig uppdatera
			/*
			if ($from_archive) {				
				$do_update = FALSE;
			}
			*/
			
			// Uppdatera
			// Samt fixa lite l??nkar och textfixar
			/*if ($do_update) {
				// $this->update_page();
				// don't update since 13 oktober 2013, update is done with cron instead
				// $this->update_page_manually();
			} // if do update
			*/
			
		} catch (Exception $e) {
			d($e);
		}
			
		
    } // load

    /**
	 * Get page from remote server, fix some things, then maybe save it to db
     */
    function update_page() {

		$page_prev_version = clone $this;

		$page_url = $this->url_base . $this->num . ".html";

		// if (isset($_GET["debug"])) {
		// 	echo "<pre>debug got here" . time() . "\n";
		// 	echo "do update from page_url<br>\n";
		// 	echo $page_url;
		// 	exit;
		// }

		$page_contents = file_get_contents($page_url);
		#if ($do_update) {
			#echo $page_contents;exit;
		#}
		
		// Kolla efter n??sta och f??reg??ende sida
		// var nextPage = "100.html";var previousPage = "101.html";
		preg_match('/nextPage = "(\d{3})/', $page_contents, $matches);
		if (isset($matches[1])) {
			$this->prev_page = $matches[1];
		}
		preg_match('/previousPage = "(\d{3})/', $page_contents, $matches);
		if (isset($matches[1])) {
			$this->next_page = $matches[1];
		}

		if ($page_contents !== FALSE) {
			
			// Leta ut inneh??llet
			// Tog bort utf-grejjen 7 oktober, efter att vi haft utf-problem ett tag.
			// Var det svt texttv som bytt encoding tro?
			//$page_contents = utf8_encode($page_contents);
			$roots = explode('<pre class="root', $page_contents);
			array_shift($roots);
			// varje roots[n] ??r en... sida va?
			// men inte med olika nummer, utan sub-sidor om det ??r en ff-sida
			for ($i=0; $i<sizeof($roots);$i++) {
				
				// se till att dom b??rjar med pre
				$roots[$i] = '<div class="root' . $roots[$i];
				// ta bort allt fr??n sista </pre> och fram??t
				$roots[$i] = preg_replace("/<\/pre>.*/mi", "</div>", $roots[$i]);

				// Replace links etc. in this inpage-subpage
				$roots[$i] = $this->replace_stuff($roots[$i]);
				
				// Don't let lines be completely empty
				//$roots[$i] = str_replace(str_repeat(" ", 20), str_repeat("&nbsp;", 20), $roots[$i]); // 40 mellanslag

			}
			
			$this->arr_contents = $roots;
			
			$this->title = $this->find_titles();
			$this->find_links();
			$this->fix_links();

			$this->date_updated_unix = time();
			
			// Kolla om den nya sidan vi har h??mtat skiljer sig fr??n den f??reg??ende tillr??ckligt mycket f??r att spara en ny version
			$prev_version_text = join(" ", $page_prev_version->arr_contents);
			$prev_version_text = strip_tags($prev_version_text);

			$current_version_text = join(" ", $this->arr_contents);
			$current_version_text = strip_tags($current_version_text);
		
			$similarity_in_percent = 0;
			similar_text($prev_version_text, $current_version_text, $similarity_in_percent);
			// echo "Percent similarity: $similarity_in_percent";
			
			// Om den h??r versionen ??r mindre ??n x% lika som f??reg??ende version s?? sparar vi ny
			// 99.598 % = "* = efter kl 6" blev till "* = efter kl 12"
			// S?? det m??ste vara lite l??gre f??r att inte bara f??nga upp skrivfel och mindre ??ndringar. Det ??r st??rre ??ndringar vi vill ha, right?
			// 97.8 n??r den rad ??ndrades, typ namnet p?? en nyhet p?? ??versiktssidorna
			
			// Texten m??ste ha mindre likhet ??n s??h??r f??r att sidan ska sparas som ny version
			// Om denna har v??rdet 70 betyder det allts?? att det m??ste vara mer ??n 30 % skillnad f??r att sidan ska sparas som ny
			$percent_threshold = 90;

			/*if ($this->input->get("debug_api")) {
				d("similarity_in_percent: $similarity_in_percent");
				$similarity_in_percent = 1;
			}*/

			if ($similarity_in_percent < $percent_threshold) {
				// Save new version
				$this->save(TRUE);
			} else {
				// Update existing
				$this->save();
			}
		
		} // if page contents

	
    }

    /**
	 * Get page from remote server, fix some things, then maybe save it to db
	 * This version uses CURL and uses if-modified-since to minimize bandwidth
	 * also uses last-modified-date of remote page to set new date_updated value in database
     */
    function update_page_manually() {

    	$apc_key = "texttv_page_{$this->num}";

		$page_prev_version = clone $this;

		$page_url = $this->url_base . $this->num . ".html";
		$prev_date_updated_unix = $this->date_updated_unix;

		// Remote last modified stopped working somehow...
		// Fetch last modified using another curl call..
		$ch2 = curl_init();
		curl_setopt($ch2, CURLOPT_URL, $page_url);
		curl_setopt($ch2, CURLOPT_HEADER, 1);
        curl_setopt($ch2, CURLOPT_NOBODY, true); // this seems to make remote server return last-modified
		curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 1 );
		curl_setopt($ch2, CURLOPT_TIMEOUT, 1 );
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch2, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
		curl_setopt($ch2, CURLOPT_TIMEVALUE, $this->date_updated_unix );

		$response2 = curl_exec($ch2);
		list($remote_header2, $page_contents2) = explode("\r\n\r\n", $response2, 2);
		preg_match('!Last-Modified:(.*)!', $remote_header2, $matches2);
		$remote_last_modified2 = trim($matches2[1]);
		$remote_status_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
		#echo "\nch2 remote_last_modified2: $remote_last_modified2";
		#echo "\nch2 remote_status_code: $remote_status_code";
		curl_close($ch2);
		// end get last modified

		$remote_last_modified_datetime = new DateTime($remote_last_modified2);
		$this->date_updated_unix = $remote_last_modified_datetime->format("U");
		// echo "<br>date updated: " . date("c", $this->date_updated_unix);
		// echo "\nFetch successfull.";

		// Store page info in APC cache
		if ( in_array( $remote_status_code, array(304, 200) ) ) {
			apc_store($apc_key, array(
				"remote_last_modified" => $remote_last_modified2,
				"remote_last_modified_unix" => $this->date_updated_unix,
				"local_last_checked" => time()
			));
		}

		
		// Only store page if it's modified
		// echo "\nremote_status_code: $remote_status_code";
		if (304 == $remote_status_code) {

			// Not modified. Remote is same as local saved.
			#error_log("texttv: page $this->num not modified");
			return "not_modified";

		} elseif (200 == $remote_status_code) {
			
			#error_log("texttv: page $this->num modified");
			// return "modified";

			// remote server says page is modified, but we can't quite trust it
			// so a bit further down we also check if actual page contents are different

			// Fetch pfull age with curl
			// echo "\nFetch from URL $page_url";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $page_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($ch, CURLOPT_HEADER, 1);
			//curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
			//curl_setopt($ch, CURLOPT_TIMEVALUE, $prev_date_updated_unix );
			
			// Set low timeout values so we don't hog up php
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1 );
			curl_setopt($ch, CURLOPT_TIMEOUT, 1 );

			// testar lite mera f??r att inte f?? den att dyka upp i slowlog
			curl_setopt($ch, CURLOPT_FILETIME, 1 );
			curl_setopt($ch, CURLOPT_NOSIGNAL, true );
			curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 2 );
			
			// error_log("Before curl_exec() for page " . $this->num);
			$page_contents = curl_exec($ch);
			// echo "\npage_contents: $page_contents";
			if (false === $page_contents) {
				#echo "\nError: curl_exec() returned false, so I could not get remote page.";
				#echo "\ncurl_error(): " . curl_error($ch);
				error_log("curl error from " . __FUNCTION__ . ": " . curl_error($ch));
				curl_close($ch);
				return "curl error";
			}
			// error_log("After curl_exec() for page " . $this->num);

			$remote_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			// end get page with curl


		} else {

			//echo "\nError: Got unknown remote code: '$remote_status_code'";
			error_log("texttv: error page $this->num got remote status code $remote_status_code");
			return "error";

		}

		// Check if page contains "SVT Text   Sidan ej i s??ndning"
		// if so then page is not broadcasted?
	
		// Kolla efter n??sta och f??reg??ende sida
		// var nextPage = "100.html";var previousPage = "101.html";
		preg_match('/nextPage = "(\d{3})/', $page_contents, $matches);
		if (isset($matches[1])) {
			$this->prev_page = $matches[1];
		}
		preg_match('/previousPage = "(\d{3})/', $page_contents, $matches);
		if (isset($matches[1])) {
			$this->next_page = $matches[1];
		}

		if ( ! $page_contents ) {
			return "no_page_contents";
		}
		
		// Leta ut inneh??llet
		// Tog bort utf-grejjen 7 oktober, efter att vi haft utf-problem ett tag.
		// Var det svt texttv som bytt encoding tro?
		//$page_contents = utf8_encode($page_contents);
		$roots = explode('<pre class="root', $page_contents);
		array_shift($roots);
		// varje roots[n] ??r en... sida va?
		// men inte med olika nummer, utan sub-sidor om det ??r en ff-sida
		for ($i=0; $i<sizeof($roots);$i++) {
			
			// se till att dom b??rjar med pre
			$roots[$i] = '<div class="root' . $roots[$i];
			// ta bort allt fr??n sista </pre> och fram??t
			$roots[$i] = preg_replace("/<\/pre>.*/mi", "</div>", $roots[$i]);

			// Replace links etc. in this inpage-subpage
			$roots[$i] = $this->replace_stuff($roots[$i]);
			
			// Don't let lines be completely empty
			//$roots[$i] = str_replace(str_repeat(" ", 20), str_repeat("&nbsp;", 20), $roots[$i]); // 40 mellanslag

		}
		
		$this->arr_contents = $roots;
		
		$this->title = $this->find_titles();
		$this->find_links();
		$this->fix_links();

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
				
    }


    // function find_and_set_title() {
	// Leta upp alla .dh = rubriker
    function find_titles() {
	
		// Skapa title:s av dessa
		// numera som h1:or!
		$title = "";
		$page_contents =join(" ", $this->arr_contents);
		if (preg_match_all('/<h1 class="[a-z ]*DH">([\w\d???????????? :\-"]+)/i', $page_contents, $matches)) {
			$arr_titles = array();
			foreach ($matches[1] as $one_title) {
				if (trim($one_title)) {
					$arr_titles[] = $one_title;
				}
			}
			$title .= join(" | ", $arr_titles);

		}
		return $title;
		
	}

    /**
     * Make modifications to the page contents
     * For example fix so ranges of links work
     * and texts, like "n??sta sida" becomes a link
     */
    function replace_stuff($page_contents) {
	    
		// Fixa s?? att text som kan vara en l??nk... blir en l??nk
		// $page_contents = str_replace("www.svt.se", "svt.se", $page_contents);
		// $page_contents = str_replace("svt.se", "www.svt.se", $page_contents);
		// $page_contents = autolink($page_contents, $limit=30);

		// Fixa s?? att "n??sta sida" blir en l??nk tll n??sta sida
		if (mb_strpos($page_contents, "n??sta sida") !== FALSE) {
			// Mer p?? n??sta sida
			$page_contents = str_replace("n??sta sida", "<a class='test-{$this->prev_page}' href='/{$this->next_page}'>n??sta sida</a>", $page_contents);
		}

		// Ta bort "          Forts??ttning f??ljer >>>       "
		// &gt;&gt;&gt;
		if (mb_strpos($page_contents, "Forts??ttning f??ljer &gt;&gt;&gt;") !== FALSE) {
			// Mer p?? n??sta sida
			$page_contents = str_replace("Forts??ttning f??ljer &gt;&gt;&gt;", "", $page_contents);
		}

		// F??rsta raden i varje root b??rjar alltid med
		//  398 SVT Text         Tisdag 27 dec 2011
		// s?? markera ut den med .toprow s?? vi kan stylea den
		$page_contents = preg_replace("/<span/", "</span><span", $page_contents, 1);
		$page_contents = preg_replace("/\">/", "\"><span class=\"toprow\">", $page_contents, 1);

		// l??nkar range
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
		
		// l??nkar komma/slash
		// <a href="/121">121</a>/<a href="/165">165</a> 
		$reg = '/<a href="\d{3}.html">(\d{3})<\/a>\/<a href="\d{3}.html">(\d{3})<\/a>/msi';
		$replace = '<a href="\\1,\\2">\\1/\\2</a>';
		$page_contents = preg_replace($reg, $replace, $page_contents);

		// Fixa s?? att TEXT......123
		// Blir l??nk av hela paketet
		#if (isset($_GET["test"])) {
			#$reg = '/>(.*?)([\.+]| )<a href="(\d{3}).html">\d{3}<\/a>/';
			//$replace = "><a href='\\3'>\\1\\2\\3</a>";
			#$replace = "><a class='link link-row' href='\\3'>\\1\\2\\3</a>";
			#$page_contents = preg_replace($reg, $replace, $page_contents);
		#}

		// Fixa s?? att "sida <nnn>"" blir l??nk (och inte bara <nnn>)
		// Forts??ttning f??ljer p?? sida <a href="/109">109</a>
		$page_contents = preg_replace("/sida <a href=\"([\d]{3}).html\">[\d]{3}<\/a>/", "<a href='/\\1'>sida \\1</a>", $page_contents);						
		
		// fixa s?? att vi f??r h1-rubriker
		// <span class="Y DH">Kinesiskt bud lagt p?? Saab            </span>
		#if ($this->input->get("forceupdate")) {
		$page_contents = preg_replace('/<span class="Y DH">(.*?)<\/span>/', '<h1 class="Y DH">\\1</h1>', $page_contents);
		$page_contents = preg_replace('/<span class="Y bgB DH">(.*?)<\/span>/', '<h1 class="Y bgB DH">\\1</h1>', $page_contents);
		#}
		
		return $page_contents;
	    
    }

    /**
     * Save this page to db
     * @param bool $save om sidan ska sparas som ny, dvs det skapas en arkiverad version av den tidigare
     */
     function save($save_as_new = FALSE) {

		try {

	    	// New or existing?
	    	if (!$save_as_new && isset($this->id) && is_numeric($this->id)) {
		    	
		    	// Existerande, uppdatera bara date_updated
				$data = array(
					"date_updated" 	=> date('Y-m-d H:i:s', $this->date_updated_unix),
				);
	    		$where = "id = $this->id";
				$str = $this->db->update_string('texttv', $data, $where);
				$this->db->query($str);

	    	} else {

				// Ny
				$data = array(
					"page_num" 		=> $this->num,
					"page_content" 	=> serialize($this->arr_contents),
					"date_updated" 	=> date('Y-m-d H:i:s', $this->date_updated_unix),
					"next_page" 	=> $this->next_page,
					"prev_page" 	=> $this->prev_page,
					"title" 		=> $this->title
				);
				$str = $this->db->insert_string('texttv', $data);
				$this->db->query($str);
				$this->id = $this->db->insert_id();

				// stupid way, but update column to be compressed
				$str = sprintf('UPDATE texttv SET page_content = COMPRESS(page_content) WHERE id = %1$d', $this->id);
				$this->db->query($str);

	    	}


    	} catch (Exception $e)  {
    		die(print_r($e, TRUE));
    	}

    }
	
	/**
	 * @Todo: en sida ??r ju "barn" av en huvudsida, t.ex. 303 ??r en sportnyhet och barn av 300.
	 * Anv??nds detta faktum f??r att skapa breadcrumbs
	 * 
	 */
	function get_main_pages() {
		// 106 -> inrikes
		// 130 -> utrikes
		
		$arr_main_pages = array(
			100 => "Nyheter",
			200 => "Ekonomi",
			300 => "Sport",
			330 => "Resultatb??rs",
			376 => "M??lservice",
			400 => "V??der",
			500 => "Blandat",
			550 => "Tips",
			580 => "Lotterier",
			600 => "P?? TV",
			800 => "Utbildningsradion"
		);
	}
	
	/**
	 * Visa arkiv p?? vissa sidor
	 */
	function show_archive_for_page() {
		// $this->num
		$arr = array(
			
		);
	}
	
	/**
	 * some pages have "names", like 100 = nyheter, 300 = sport
	 * let's use them for page titles!
	 */
	function get_page_name() {
		// http://svt.se/svttext/web/pages/700.html
    	$arr_names = array(
    		100 => "Start",
    		101 => "Inrikes nyheter",
    		104 => "Utrikes nyheter",
    		127 => "Ekonominotiser",
    		128 => "Inrikesnotiser",
    		148 => "Utrikesnotiser",
    		149 => "V??rlden runt",
    		150 => "Kultur & N??je",
    		//160 => "Bakgrunder",
    		188 => "Nyhetsrullen",
    		200 => "Ekonomi",
    		201 => "B??rsinneh??ll",
    		201 => "B??rssidor",
    		220 => "Aktier",
    		223 => "Optioner",
    		230 => "Valutakurser",
    		300 => "Resultatb??rs & sportnyheter",
    		330 => "Resultatb??rsen",
    		376 => "M??lservice",
    		377 => "M??lservice",
    		400 => "V??der",
    		420 => "Sn??rapporten",
    		430 => "V??glagsinfo fr??n Trafikverket",
    		431 => "Tillf??lliga trafikst??rningar",
    		440 => "OS i Rio",
    		500 => "Blandat",
    		520 => "Sveriges Radio (SR)",
    		550 => "Tipset i SVT text",
    		551 => "Stryktipset",
    		552 => "Stryktipset",
    		553 => "Europatipset",
    		570 => "v75/ATG (h??stsport)",
    		571 => "v75 resultat",
    		580 => "Lotterier m.m.",
    		590 => "Lotto",
    		600 => "P?? TV",
    		650 => "P?? TV just nu - SVT Texts programguide",
    		//670 => "H??star",
    		676 => "Radiohj??lpen",
    		700 => "Inneh??ll",
    		712 => "Info om SVT Text (Om Text TV)",
    		800 => "Utbildningsradion (UR)"
	    );
	    if (array_key_exists($this->num, $arr_names)) {
	    	return $arr_names[$this->num];
	    } else {
	    	return FALSE;
	    }
    }

    function get_content_with_fixes() {

    	$arr_contents = $this->arr_contents;

    	// each content = one page and sub-page 
    	foreach ($arr_contents as $key => $one_content) {
    		
    		/*
			Fix 1: hitta nummer i texten som inte ??r l??nkar men som borde vara det
			Exempel p?? text:
			 <span class="C">Under tiden 01.00 - 06.00 uppdaterar   </span>
			 <span class="C">nyhetsbyr????n TT sidorna 190 - 197.      </span>
			L??sning:
			Hitta nummer nnn som inte har > f??re sig eller </ efter sig. typ.
			*/

    		// print_r($one_content);

    		$arr_contents[$key] = $one_content;

    	}

    	return $arr_contents;

    }

    /**
      * ger li-output f??r en sida
      */
    function get_output() {

	    $out = "";

		// Output en sida
		$page_num_data_attr = sprintf("data-sida=%d", $this->num);
		
		$out .= "<li $page_num_data_attr class='one-page TextTVPage'>";
		
		//$out .= sprintf('<div style="display:none" class="page-swipe-wrap page-swipe-wrap-prev">F??reg??ende sida: %1$s:</div>', $this->prev_page);

		//$out .= "<div class='page-swipe-wrap'>";

		$num_of_subpages = sizeof($this->arr_contents);
		$subpages_class = "";
		if ($num_of_subpages == 1) {
			$subpages_class	.= " subpage-count-1";
		} else {
			$subpages_class	.= " subpage-count-$num_of_subpages subpage-count-many";
		}

		// Alla sidor f??r ett nummer visas i en ul
		// Denna kan inneh??lla bara en om sidan inte ??r en fler-sida
		$out .= "<ul class='inpage-pages $subpages_class'>";
		foreach ($this->arr_contents as $one_page) {

			$out .= "<li>";

			//$one_page = $this->maybeChangeLineCount($one_page);

			// T.ex. sid 377 saknar huvudrubrik, dvs 377:a
			// Pga SEO s?? l??gger vi till en h1 i toprow
			// <li><div class="root"><span class="toprow"> 377 SVT Text         S??ndag 18 dec 2016
			// blir
			// <li><div class="root"><span class="toprow"> <h1>377 SVT Text</h1>         S??ndag 18 dec 2016
			if (377 == $this->num) {
				$one_page = str_replace('377 SVT Text', '<h1>377 SVT Text</h1>', $one_page);
			}

			$out .= $one_page;

			$out .= "</li>";
			
		}
		$out .= "</ul>";

		//$out .= "</div>"; // div f??r swipe

		//$out .= sprintf('<div style="display:none" class="page-swipe-wrap page-swipe-wrap-next">N??sta sida: %1$s:</div>', $this->next_page);
		
		$out .= "</li>";

	    return $out;

    }

	/*function maybeChangeLineCount( $page_contents ) {

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
		
	}*/


    /**
     * Typ prefered page title
     */
    function get_page_title() {

		$page_title = "";
		if (($page_name = $this->get_page_name())) {
			// Look for manually entered name
			$page_title .= "" . trim($page_name);
		} elseif (trim($this->title)) {
			// Else see if we found a title when loading the page
			$page_title .= "" . trim($this->title);
		}
		
		return $page_title;

    }

    /**
     * Permalink for archive
     */
    function get_permalink($include_domain = FALSE) {

		$page_title_for_url = $this->get_page_title();
		# $page_title_for_url = strftime("%e %b %Y", $this->date_updated_unix) . "-$page_title_for_url";
		$page_title_for_url = mb_strtolower($page_title_for_url);
		$page_title_for_url = str_replace("??", "a", $page_title_for_url);
		$page_title_for_url = str_replace("??", "a", $page_title_for_url);
		$page_title_for_url = str_replace("??", "o", $page_title_for_url);
		$page_title_for_url = trim($page_title_for_url);
		$page_title_for_url = preg_replace('/-$/', "", $page_title_for_url); // bort med ev ??verblivet kommatecken
		$page_title_for_url = url_title($page_title_for_url);

		/*
		$permalink = sprintf(
			'/%1$d/arkiv/%3$s/%2$d/',
			$this->num, // 1 sidnummer
			$this->id, // 2 id
			$page_title_for_url // 3 titel
		);*/

		// Ny permlinkstruktur sept 2015
		$permalink = sprintf(
			'/%1$d/%3$s-%2$d',
			$this->num, // 1 sidnummer
			$this->id, // 2 id
			$page_title_for_url // 3 titel
		);
		
		if ($include_domain) {
			$permalink = "https://texttv.nu" . $permalink;
		}
		
		return $permalink;
	    
    }

    function isOkToArchiveInRange() {
	    $ok_ids = "100,101,102,103,104,105,300,301,302,700";
	    $arr_ids = explode(",",$ok_ids);
	    return in_array($this->num, $arr_ids);
    }


	// Skapar en array med sidnummer utifr??n en string i format
	// T.ex. "100", "100-104" "100,104,300-310"
	static function extract_pages_from_ranges($pagenum) {
		
		// en sida, typ 100 eller range & flera 100-106,300-301
		$arr_pages = array();
		$arr_page_groups = explode(",", $pagenum);
		foreach ($arr_page_groups as $one_page_group) {
			$one_page_group = trim($one_page_group);	
			if (is_numeric($one_page_group)) {
				// Just a single page
				$arr_pages[] = $one_page_group;
			} else {
				// Range perhaps
				preg_match("/(\d{3})\-(\d{3})/", $one_page_group, $matches);
				if ($matches && isset($matches[1]) && isset($matches[2])) {
					// Yep, was a range
					for ($i = $matches[1]; $i <= $matches[2]; $i++) {
						$arr_pages[] = $i;
					}

				}
			}
		}
		
		return $arr_pages;
		
	}


}
