<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->view('404');
	}

	// https://texttv.nu/api/amp_form_goto_page
	public function amp_form_goto_page()
	{
	
	    $domain_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

		$this->output->set_content_type("application/json");
		$this->output->set_header("Access-Control-Allow-Credentials: true");
		$this->output->set_header("Access-Control-Allow-Origin: *.ampproject.org");
		$this->output->set_header("AMP-Access-Control-Allow-Source-Origin: ".$domain_url);
		
		$sida = (int) $this->input->get("sida");
		
		if ($sida >= 100 && $sida <= 999) {
			$redirectTo = "https://texttv.nu/$sida/amp";
			$this->output->set_header("AMP-Redirect-To: $redirectTo");
		}

		$arr_json = [
			"sida" => $sida
		];		

		$this->output->append_output( json_encode($arr_json) );

	}

	/**
	 * Hämta mest lästa sidorna
	 *
	 * Funkar nu: 
	 * https://texttv.nu/api/most_read/news
	 *
	 * Funkar kanske i framtiden:
	 * https://texttv.nu/api/most_read
	 * https://texttv.nu/api/most_read/sport
	 */
	public function most_read($type = "") {
		$arr_json = [
			"ok" => true,
			"pages" => []
		];
		
		// $date_unix = strtotime($date);
		// printf('<h1>Text TV: mest delat %1$s</h1>', strftime("%A %e %B %G", $date_unix));
		
		// output_shared_pages_nav_form();
		
		// $result = get_shared_pages_for_period( strtotime("today 00:00", $date_unix), strtotime("today 24:00", $date_unix) );
		$count = (int) $this->input->get("count");
		if (!$count) {
			$count = 50;
		}

		$date = $this->input->get("date");
		if (!$date) {
			$date = date('Y-m-d', strtotime("today"));
		}

		$dateUnix = strtotime($date);
			
		if ("news" == $type) {
			$result = get_shared_pages_for_period( strtotime("today 00:00", $dateUnix), strtotime("today 24:00", $dateUnix) );
		} else if ("sport" == $type) {
			die('Not yet');
		} else {
			die('Not yet');
		}
		
		// row = en text tv-sida
		foreach ( $result->result() as $row ) {

			$page_content = unserialize( $row->page_content );
			$page_content = strip_tags( $page_content[0] );
			$page_content = trim( $page_content );

			// preg_match('/\d{4}/', $page_content, $matches, PREG_OFFSET_CAPTURE);			
			// if ( $matches ) {
			//     $page_content = mb_substr( $page_content, $matches[0][1] + 4 );
			//     $page_content = trim( $page_content );
			// }
			
			// permalink
			$archive_page = new Texttv_page();
			$archive_page->id = $row->id;
			$archive_page->load(true);
			$permalink = $archive_page->get_permalink(true);
			
			$type_for_json = empty($type) ? $row->type : $type;

			$arr_json["pages"][] = [
				"id" => $row->id,
				"title" => $row->title,
				"page_num" => $row->page_num,
				"date_added_formatted" => $row->date_added_formatted,
				"date_added_unix" => $row->date_added_unix,
				"date_added" => $row->date_added,
				"date_added_time" => date('H:i', $row->date_added_unix),
				"type" => $type_for_json,
				"page_content" => $page_content,
				"permalink" => $permalink,
				"query_date" => date("Y-m-d", $dateUnix),
			];
			
			$arr_outputed_page_nums[] = $row->page_num;

		}
		
		$arr_json["pages"] = array_splice($arr_json["pages"], 0, $count);

		$this->output->set_content_type("application/json");
		$this->output->append_output( json_encode($arr_json) );			
	}
	
	// https://texttv.nu/api/last_updated
	public function last_updated($type = "") {
		
		$arr_json = [
			"ok" => true,
			"html" => "",
			"pages" => [],
		];
		
		/*
		$sql = sprintf('
				SELECT id, page_num, date_updated, title, date_added FROM texttv
				WHERE
				page_num IN (%1$s)
				AND date_added > FROM_UNIXTIME(%2$d)
				GROUP BY page_num
				ORDER BY date_added DESC
				LIMIT %3$d
			', 
			implode(",", $arr_page_nums), // 1
			$latest_date_updated, // 2
			$limit // 3
		);
		
		#$arr_json["sql"] = $sql;
		
		$res = $this->db->query($sql);			
		*/
		// function get_latest_updated_pages($from, $to, $maxcount = 20) {
		$count = $this->input->get("count");
		if (!$count) {
			$count = 50;
		}
			
		if ("news" == $type) {
		
			// Senast uppdaterade nyhetssidorna
			$result = get_latest_updated_pages(100, 200, 50);
			$result = $result->result_array();

		} else if ("sport" == $type) {

			// Senast uppdaterade sportsidorna
			$result = get_latest_updated_pages(300, 329, 50);
			$result = $result->result_array();
			
		} else {
			
			// get both news and sport, not working yet...
			$result_news = get_latest_updated_pages(100, 200, 50)->result_array();
			$result_sport = get_latest_updated_pages(300, 329, 50)->result_array();

			$result_news = array_map( function($val) {
				$val["type"] = "news";
				return $val;
			}, $result_news );
			
			$result_sport = array_map( function($val) {
				$val["type"] = "sport";
				return $val;
			}, $result_sport );

			$result = array_merge( $result_news, $result_sport );
			#print_r($result);
			#exit;
			
		}
		
		
		// Contains the numbers of all outputed pages
		// Used as a check so the same page is not outputed multiple times
		$arr_outputed_page_nums = array();

		foreach ( $result as $row ) {

			if ( in_array($row["page_num"], $arr_outputed_page_nums)) {
				continue;
			}

			$page_content = unserialize( $row["page_content"] );
			$page_content = strip_tags( $page_content[0] );
			$page_content = trim( $page_content );

			preg_match('/\d{4}/', $page_content, $matches, PREG_OFFSET_CAPTURE);
			
			if ( $matches ) {
			
			    $page_content = mb_substr( $page_content, $matches[0][1] + 4 );
			    $page_content = trim( $page_content );
			
			}
			
			// permalink
			$archive_page = new Texttv_page();
			$archive_page->id = $row["id"];
			$archive_page->load(true);
			$permalink = $archive_page->get_permalink(true);
			
			$type_for_json = empty($type) ? $row["type"] : $type;

			$arr_json["pages"][] = [
				"id" => $row["id"],
				"title" => $row["title"],
				"page_num" => $row["page_num"],
				"date_added_unix" => $row["date_added_unix"],
				"date_added" => $row["date_added"],
				"date_added_time" => date('H:i', $row["date_added_unix"]),
				//"date_added_formatted" => $row["date_added_formatted"],
				"type" => $type_for_json,
				"page_content" => $page_content,
				// "page_content_org" => $row->page_content
				//"debug" => $row
				"permalink" => $permalink
			];
			
			$arr_outputed_page_nums[] = $row["page_num"];

		}
		
		$arr_json["pages"] = array_splice($arr_json["pages"], 0, $count);

		$this->output->set_content_type("application/json");
		$this->output->append_output( json_encode($arr_json) );
			
	}
	
	/**
	 * check if any page among str_page_ids have a newer version available
	 * 
	 * Calls is like:
	 * http://texttv.nu/api/updated/100,300/1452244325?app=texttvnu.web
	 */
	public function updated($str_page_nums = "", $latest_date_updated = 0) {

		$arr_page_nums = texttv_page::extract_pages_from_ranges($str_page_nums);
		$latest_date_updated = (int) $latest_date_updated;

		$arr_json = array(
			"is_ok" => true,
			// "page_nums" => $arr_page_nums,
			// "latest_date_updated" => $latest_date_updated
		);
		
		// If API call is coming from a bot then "shortcut" it
		$limit = 10;
		
		$this->load->library('user_agent');
		if ( $this->agent->is_robot() ) {
			$limit = 0;
		}

		$sql = sprintf('
				SELECT id, page_num, date_updated, title, date_added FROM texttv
				WHERE
				page_num IN (%1$s)
				AND date_added > FROM_UNIXTIME(%2$d)
				GROUP BY page_num
				ORDER BY date_added DESC
				LIMIT %3$d
			', 
			implode(",", $arr_page_nums), // 1
			$latest_date_updated, // 2
			$limit // 3
		);
		
		#$arr_json["sql"] = $sql;
		
		$res = $this->db->query($sql);
		#$arr_json["num_rows"] = $res->num_rows;
		$arr_json["update_available"] = $res->num_rows ? true : false;
		$arr_json["res"] = $res->result();
		
		$this->output->set_content_type("application/json");

		$this->output->append_output( json_encode($arr_json) );
		
		// if number of pages are many, debug to see if we can find out who/what is causing it
		/*if ( sizeof( $arr_page_nums ) > 10 ) {

			$date_added = date("Y-m-d H:i:s");
			$key = "update_many";
			$text = json_encode( [ $_REQUEST, $_SERVER ], JSON_PRETTY_PRINT );

			$this->db->query(
				sprintf(
					'INSERT INTO texttv_log (date_added, log_key, log_text) VALUES (%1$s, %2$s, %3$s)',
					$this->db->escape($date_added),
					$this->db->escape($key),
					$this->db->escape($text)
				)
			);
			
		}*/
		
		#exit;

	}

	/*
	Creates screenshot for a page and returns a json with sharing info
	Used in iOS app when using the share-function
	Example call:
	https://api.texttv.nu/api/share/2664652,2664653
	*/
	public function share($str_page_ids = "") {
		
		$arr_json = array(
			"is_ok" => true
		);

		$arr_page_ids = texttv_page::extract_pages_from_ranges($str_page_ids);

		// Mark pages as shared in db		
		mark_archive_ids_as_shared($arr_page_ids);

		// Generate screenshot of selected pages using PhantomJS
		// Full URL to get screenshot will be like:
		// http://texttv.nu/100/arkiv/sida/2664652,2664653/?apiAppShare=1
		// screenshot end up here:
		// http://digital.texttv.nu/shares/1396185884588-20313710.jpg
		$permalinkURL = "https://www.texttv.nu/100/arkiv/sida/" . implode(",", $arr_page_ids);
		//$screenshotURL = "http://api.texttv.nu/100/arkiv/sida/" . implode(",", $arr_page_ids) . "/?apiAppShare=1";
		
		
		// $cmd = "/usr/bin/phantomjs /usr/share/nginx/share-screenshot/create-share-screenshot.js $screenshotURL";
		
		// phantomjs2
		//$permalinkURL = "https://www.texttv.nu/100/arkiv/sida/" . implode(",", $arr_page_ids);
		//$screenshotURL = "https://api.texttv.nu/100/arkiv/sida/" . implode(",", $arr_page_ids) . "/?apiAppShare=1";

		//$cmd = "/root/phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/share/nginx/share-screenshot/create-share-screenshot-2.js $screenshotURL";
		
		#$arr_json["debug"] = array();
		#$arr_json["debug"]["cmd"] = $cmd;
		
		#$output = null;
		#$return_val = null;

		#exec( $cmd, $output, $return_val );
		
		/*
		if ( $return_val != 0 ) {
			$err_msg = "TextTV: error when sharing page";
			$err_msg_details = "output: " . json_encode($output, JSON_PRETTY_PRINT) . ", return_val: $return_val";
			exec( "ulimit -a", $ulimit_out, $ulimit_return );
			error_log( $err_msg . " - " . $err_msg_details . "\n\nulimit output:" . json_encode($ulimit_out, JSON_PRETTY_PRINT) . "\n\nulimit return_var: " . $ulimit_return );
			mail( "par.thernstrom@gmail.com", "texttv.nu: share error", $err_msg . "\n\n" . $err_msg_details );
		}
		*/

		#$arr_json["debug"]["output"] = $output;
		#$arr_json["debug"]["return_val"] = $return_val;
				
		// output contains one value that begins with "saveFileName". Let's grab that one
		// [0] => saveFileName texttv-nu-1396184659250-4222389.jpg
		/*$saveFileName = null;
		foreach ($output as $key => $value) {
			if ( strpos( $value, "saveFileName" ) !== false) {
				#echo "\nvalue is: $value";
				#echo "\n";var_dump(strpos( $value, "saveFileName" ));
				$saveFileName = str_replace( "saveFileName", "", $value );
				$saveFileName = trim( $saveFileName );
				break;
			}
		}

		if ( ! $saveFileName ) {
			$arr_json["is_ok"] = false;
		}*/
		
		// Get title from first page
		$page = new Texttv_page();
		$page->id = (int) $arr_page_ids[0];
		$page->load(true);
		if (trim($page->title)) {
			$title = trim( $page->title );
		}
		
		$screenshotURL = "https://texttv.nu/api/screenshot/" . implode(",", $arr_page_ids);

		$arr_json["screenshot"] = $screenshotURL;
		$arr_json["permalink"] = $permalinkURL;
		$arr_json["title"] = $title;

		$this->output->set_content_type("text/json");
		echo json_encode($arr_json);
		
		exit;

	}

	/**
	 * Hämta en arkiv-sida
	 * 
	 * @param int $id Id för sida (inte sidnummer)
	 */
	public function getid($page_id = null) {

		$arr_json = [];

		// Bail of not ok page id.
		if (!is_numeric($page_id)) {
			$arr_json['error_code'] = 'ID_NOT_VALID';
			$arr_json['is_ok'] = false;
			$arr_json['is_error'] = true;

			$this->output->set_content_type("application/json");
			$this->output->append_output( json_encode($arr_json) );
		}

		$page_id = intval($page_id);
		
		$archive_page = new Texttv_page();
		$archive_page->id = $page_id;
		$archive_page->load(true);
		$permalink = $archive_page->get_permalink(true);

		// echo "<pre>";print_r($archive_page);exit;
		$page_content = $archive_page->arr_contents;
		
		$arr_json[] = [
			"num" => $archive_page->num,
			"title" => $archive_page->title,
			// "date_added" => $archive_page->date_added,
			// "date_added_time" => date('H:i', $archive_page->date_added_unix),
			"content" => $page_content,
			"date_updated_unix" => $archive_page->date_updated_unix,
			"permalink" => $permalink,
			"id" => $archive_page->id,
		];

		// print_r($arr_json);exit;
		
		// $arr_outputed_page_nums[] = $row["page_num"];
	
		// $arr_json["pages"] = array_splice($arr_json["pages"], 0, $count);

		$this->output->set_content_type("application/json");
		$this->output->append_output( json_encode($arr_json) );	
	}

	/**
	 * Hämta sida baserat på nummer
	 * 
	 * @param mixed $page_num
	 */
	public function get($page_num = "")
	{
		$data = array(
			"page_num" 		=> $page_num,
			"jsoncallback" 	=> (string) $this->input->get("jsoncallback"),
			"api_call"		=> "get"
		);
		$this->load->view('api', $data);
	}

	// Like get, but also force a reload from the svt-servers
	public function update_page($page_num = "")
	{
		$data = array(
			"page_num" 		=> $page_num,
			"api_call"		=> "update_page",
		);
		$this->load->view('api', $data);
	}

	public function get_html($page_num = "")
	{
		$data = array(
			"page_num" 		=> $page_num,
			"jsoncallback" 	=> (string) $this->input->get("jsoncallback"),
			"api_call"		=> "get_html"
		);
		$this->load->view('api', $data);
	}

	/**
	 * Returnerar permalänk för en eller flera page idn
	 * Exempel på anrop:
	 * http://texttv.nu/api/get_permalink/7576764,7576891,7308987?app=testapp
	 * 
	 * @param string $archive_ids Kommaseparerad lista på idn på sidorna
	 */
	public function get_permalink($archive_ids) {
		
		$arr_pagenums = array();
		$arr_archive_ids = explode(",", $archive_ids);
		$arr_archive_ids = array_map("intval", $arr_archive_ids);
		
		$arr_pages = array();
		
		foreach ($arr_archive_ids as $one_archive_id) {
			
			$archive_page = new Texttv_page();
			$archive_page->id = $one_archive_id;
			$archive_page->load(true);
			
			$arr_pagenums[] = $archive_page->num;
			
			# echo "<br>one_archive_id: $one_archive_id";
			
			$arr_pages[] = $archive_page;

		}
		
		if (sizeof($arr_pages) == 1) {
			$permalink = $arr_pages[0]->get_permalink();
		} else {
			$page_title_for_url = "";
			$page_title_for_url = strftime("%e-%b-%Y", $arr_pages[0]->date_updated_unix);
			$page_title_for_url = trim(strtolower($page_title_for_url));
			$page_title_for_url = url_title($page_title_for_url);	
	
			$permalink = sprintf(
				'/%1$s/arkiv/%3$s/%2$s/',
				implode(",", $arr_pagenums), // 1 sidnummer
				implode(",", $arr_archive_ids), // 2 id
				$page_title_for_url // 3 titel (datum när det är flera sidor)
			);
		}
	
		$screenshotURL = "https://texttv.nu/api/screenshot/" . implode(",", $arr_archive_ids);
	
		$arr_json = array(
			"permalink" => "https://texttv.nu" . $permalink,
			"pagenums" => $arr_pagenums,
			"ids" => $arr_archive_ids,
			"title" => $arr_pages[0]->get_page_title(),
			"screenshot" => $screenshotURL
		);

		$this->output->set_content_type("text/json");
		echo json_encode($arr_json);

		// Mark pages as shared in db
		mark_archive_ids_as_shared($arr_archive_ids);
		
		exit;
		
	}

	/**
	 * Generate a screenshot of the current page
	 */
	public function screenshotCurrent($pagenum = 100) {
		$page = new texttv_page($pagenum);
		
		if ( ! $page ) {
			return false;
		}
		
		header('x-debug-1: ' . $page->id);
		$screenshotRedirectURL = sprintf('https://texttv.nu/api/screenshot/%1$d', $page->id);
		header('x-debug-2: ' . $screenshotRedirectURL);
		$this->output->set_header("Location: $screenshotRedirectURL");
		
		// exit;
	}

	/**
	 * Generates and returns a screenshot image of the passed comma separated page ids
	 * 
	 * @param $str_page_ids comma separated list of page ids
	 */
	public function screenshot( $str_page_ids = null ) {
		
		$str_page_ids = str_replace(".jpg", "", $str_page_ids);

		$out = "";
		
		$arr_page_ids = texttv_page::extract_pages_from_ranges( $str_page_ids );
		
		if ( ! $arr_page_ids ) {
			$this->output->set_header("X-texttv-error: NO_PAGE_IDS");
			exit;
		}

		$permalinkURL = "https://www.texttv.nu/100/arkiv/sida/" . implode(",", $arr_page_ids);
		$screenshotURL = "https://api.texttv.nu/100/arkiv/sida/" . implode(",", $arr_page_ids) . "/?apiAppShare=1";

		$saveFileName = md5($screenshotURL) . ".jpg";
		$image_file = $_SERVER["DOCUMENT_ROOT"] . "/shares/{$saveFileName}";

		// check if screenshot already exists
		if ( file_exists( $image_file ) ) {
			
			$this->output->set_header("X-texttv-image: cached");
			
		} else {

			// no existing screenshot, create new
			#echo $saveFileName;exit;
	
			$cmd = "/root/phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/share/nginx/share-screenshot/create-share-screenshot-2.js $screenshotURL";
			
			// new since 27 aug 2016
			// 15 var saveFileName = md5(url) + ".jpg";
			$destImageName = "/usr/share/nginx/texttv.nu/shares/" . md5($screenshotURL) . ".jpg";
			
			#$cmd_wkhtml = "/root/wkhtmltopdf/wkhtmltox/bin/wkhtmltoimage --width 650 {$screenshotURL} {$destImageName}";
			$cmd_wkhtml = "/usr/bin/wkhtmltoimage --width 650 {$screenshotURL} {$destImageName}";

			$this->output->set_header("X-texttv-cmd_wkhtml: $cmd_wkhtml");
			$this->output->set_header("X-texttv-saveFileName: $saveFileName");
			
			$output = null;
			$return_val = null;
			$this->output->set_header("X-texttv-before_exec: 1");
			$this->output->set_header("X-texttv-before_exec_memory_get_usage: " . memory_get_usage());
			$this->output->set_header("X-texttv-before_exec_memory_get_peak_usage: " . memory_get_peak_usage());
			exec($cmd_wkhtml, $output, $return_val);
			$this->output->set_header("X-texttv-after_exec: 1");
			
			$this->output->set_header("X-texttv-debug_output: " . json_encode($output));
			$this->output->set_header("X-texttv-debug_return_val: $return_val");
			$this->output->set_header("X-texttv-dest-image-size: " . filesize($destImageName));
			$this->output->set_header("X-texttv-after_exec_memory_get_usage: " . memory_get_usage());
			$this->output->set_header("X-texttv-after_exec_memory_get_peak_usage: " . memory_get_peak_usage());
			$this->output->set_header("X-texttv-memory_memory_limit: " . ini_get('memory_limit'));
			
			
			// if $return_val = -1 then this did not work out...
					
			// output contains one value that begins with "saveFileName". Let's grab that one
			// [0] => saveFileName texttv-nu-1396184659250-4222389.jpg
			
			/*$saveFileName = null;
			foreach ($output as $key => $value) {
				if ( strpos( $value, "saveFileName" ) !== false) {
					#echo "\nvalue is: $value";
					#echo "\n";var_dump(strpos( $value, "saveFileName" ));
					$saveFileName = str_replace( "saveFileName", "", $value );
					$saveFileName = trim( $saveFileName );
					break;
				}
			}
			*/
			
			if ( ! $saveFileName ) {
				exit;
			}
			
			if ($return_val == -1) {
				$this->output->set_header("X-texttv-image: failed-to-create");
				error_log("texttv: api/screenshot: failed to create image");
				error_log( ob_get_contents() );
			} else {
				$this->output->set_header("X-texttv-image: created");
			}
			
			
		} // create new


		if (file_exists($image_file)) {

			// set content type and output image
			$this->output->set_content_type("image/jpeg");		
			$out = file_get_contents( $image_file );
			$this->output->append_output( $out );

		} else {

			// 503 Service Unavailable, because file should be there, but isn't.. :(
			$this->output->set_status_header(503);

		}
		
	}

	/**
	 * Åtgärdet för sida.
	 * Modernare/mer korrekt API-REST-URLigt.
	 * 
	 * Endpoints:
	 * - api.texttv.nu/page/<pageid>/view
	 * - api.texttv.nu/page/<pageid>/share
	 */
	public function page($page_ids = null, $type = null) {
		$arr_page_ids = texttv_page::extract_pages_from_ranges($page_ids);
		$page_ids = implode(",", $arr_page_ids);

		switch ($type) {
			case 'view':
				$type = 'VIEW';
				break;
			case 'share':
				$type = 'SHARE';
				break;
			case 'copyLinkToClipboard':
				$type = 'COPYLINK';
				break;
			case 'copyTextToClipboard':
				$type = 'COPYTEXT';
				break;
			case 'openLinkInBrowser':
				$type = 'OPENLINK';
				break;
			default:
				$type = null;
				break;
		}

		if (!$page_ids || !$type) {
			echo "Fel på datat. Fixa datat vettja.";
			exit;
		}

		$statsdb = $this->load->database('stats', TRUE);

		$created_at = date("Y-m-d H:i:s");

		$insert_data = [
			'page_ids' => $page_ids,
			'created_at' => $created_at,
			'type' => $type
		];

		$statsdb->insert('page_actions', $insert_data);
	
		$arr_json = [
			'success' => true,
			"request" => [
				"page_ids" => $page_ids,
				"type" => $type,
				'timestamp' => $created_at
			]
		];		

		$this->output->set_content_type("text/json");
		$this->output->append_output( json_encode($arr_json) );
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */