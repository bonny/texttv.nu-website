<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller
{

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

	/**
	 * Hämta mest lästa sidorna.
	 * Nya funktionen som aktiverades 2021-09-11.
	 *
	 * Funkar nu: 
	 * https://texttv.nu/api/most_read/news
	 *
	 * Funkar kanske i framtiden:
	 * https://texttv.nu/api/most_read
	 * https://texttv.nu/api/most_read/sport
	 */
	public function most_read($type = "")
	{
		$arr_json = [
			"ok" => true,
			"pages" => []
		];

		$count = (int) $this->input->get("count");
		if (!$count) {
			$count = 50;
		}

		$date = $this->input->get("date");
		if (!$date) {
			$date = date('Y-m-d', strtotime("today"));
		}

		$dateUnix = strtotime($date);

		if (!in_array($type, ['news', 'sport'])) {
			$type = '';
		}

		list($arr_json["pages"], $cache_seconds) = most_read_pages_cached($type, $dateUnix);

		$arr_json["pages"] = array_splice($arr_json["pages"], 0, $count);

		// 2026-08-10: Låt nginx cacha det här svaret i 60 s istället för
		// vhostens fastcgi_cache_valid 4s. Se todo #14: endpointen stod för
		// 578 av 608 långsamma requests under ett dygn — queryn är tung och
		// regenererades i praktiken oavbrutet med 4s TTL, vilket band en
		// php-fpm-worker i upp till 6 s per regenerering (poolen har 5).
		// "Mest lästa idag" behöver inte fyra sekunders färskhet.
		//
		// X-Accel-Expires används med flit i stället för Cache-Control:
		// nginx konsumerar och STRIPPAR headern, så svaret till iOS- och
		// Android-apparna blir byte-identiskt. Cache-Control hade synts för
		// klienten och kunnat ändra beteendet i redan utgivna appversioner.
		//
		// 2026-10-14 (todo #12): En dag som redan är slut ändras inte längre,
		// så den cachas en timme i stället för en minut. Tiden kommer från
		// filcachen nedan och är det som återstår av filens livslängd, så att
		// nginx-cachen och filcachen inte staplas på varandra.
		$this->output->set_header("X-Accel-Expires: " . $cache_seconds);

		$this->output->set_content_type("application/json");
		$this->output->append_output(json_encode($arr_json));
	}

	/**
	 * Hämta mest lästa sidorna.
	 * Gamla funktionen som inaktiverades 2021-09-11.
	 *
	 * Funkar nu: 
	 * https://texttv.nu/api/most_read/news
	 *
	 * Funkar kanske i framtiden:
	 * https://texttv.nu/api/most_read
	 * https://texttv.nu/api/most_read/sport
	 */

	// https://texttv.nu/api/last_updated
	public function last_updated($type = "")
	{
		$arr_json = [
			"ok" => true,
			"html" => "",
			"pages" => [],
		];

		// function get_latest_updated_pages($from, $to, $maxcount = 20) {
		$count = (int) $this->input->get("count");
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
			// get both news and sport.
			$result_news = get_latest_updated_pages(100, 200, 50)->result_array();
			$result_sport = get_latest_updated_pages(300, 329, 50)->result_array();
			$result_weather = get_latest_updated_pages(401, 401, 1)->result_array();

			$result_news = array_map(function ($val) {
				$val["type"] = "news";
				return $val;
			}, $result_news);

			$result_sport = array_map(function ($val) {
				$val["type"] = "sport";
				return $val;
			}, $result_sport);

			$result_weather = array_map(function ($val) {
				$val["type"] = "weather";
				return $val;
			}, $result_weather);

			$result = array_merge($result_news, $result_sport, $result_weather);
		}


		// Contains the numbers of all outputed pages
		// Used as a check so the same page is not outputed multiple times
		$arr_outputed_page_nums = array();

		foreach ($result as $row) {

			if (in_array($row["page_num"], $arr_outputed_page_nums)) {
				continue;
			}

			$page_content = unserialize($row["page_content"]);
			$page_content = strip_tags($page_content[0]);
			$page_content = trim($page_content);

			preg_match('/\d{4}/', $page_content, $matches, PREG_OFFSET_CAPTURE);

			if ($matches) {
				$page_content = mb_substr($page_content, $matches[0][1] + 4);
				$page_content = trim($page_content);
			}

			// permalink
			$archive_page = new Texttv_page();
			$archive_page->id = $row["id"];
			$archive_page->load(true);
			$permalink = $archive_page->get_permalink(true);
			$page_title = $archive_page->get_page_title();

			$type_for_json = empty($type) ? $row["type"] : $type;

			$arr_json["pages"][] = [
				"id" => $row["id"],
				"title" => $page_title,
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
		$this->output->append_output(json_encode($arr_json));
	}

	/**
	 * check if any page among str_page_ids have a newer version available
	 * 
	 * Calls is like:
	 * http://texttv.nu/api/updated/100,300/1452244325?app=texttvnu.web
	 */
	public function updated($str_page_nums = "", $latest_date_updated = 0)
	{

		$arr_page_nums = texttv_page::extract_pages_from_ranges($str_page_nums);
		$latest_date_updated = (int) $latest_date_updated;

		$arr_json = array(
			"is_ok" => true,
		);

		// Utan sidnummer blir det "page_num IN ()" = SQL-syntaxfel. Sedan PHP 8.1
		// kastar mysqli undantag i stället för att returnera false, så CI:s egen
		// db_debug-hantering nås aldrig — felet blir en okontrollerad 500. Bomma
		// ut här i stället, samma vakt som sida.php redan har.
		if (empty($arr_page_nums)) {
			$arr_json["is_ok"] = false;
			$arr_json["update_available"] = false;
			$arr_json["res"] = array();

			$this->output->set_content_type("application/json");
			$this->output->append_output(json_encode($arr_json));

			return;
		}

		// If API call is coming from a bot then "shortcut" it
		$limit = 10;

		$this->load->library('user_agent');
		if ($this->agent->is_robot()) {
			$limit = 0;
		}

		$sql = sprintf(
			'
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


		$res = $this->db->query($sql);
		$arr_json["update_available"] = $res->num_rows ? true : false;
		$arr_json["res"] = $res->result();

		$this->output->set_content_type("application/json");

		$this->output->append_output(json_encode($arr_json));
	}

	/*
	Creates screenshot for a page and returns a json with sharing info
	Used in iOS app when using the share-function
	Example call:
	https://api.texttv.nu/api/share/2664652,2664653
	*/
	public function share($str_page_ids = "")
	{

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

		// Default title/fallback.
		$title = 'Text-TV';

		// Get title from first page.
		if (isset($arr_page_ids[0])) {
			$page = new Texttv_page();
			$page->id = (int) $arr_page_ids[0];
			$page->load(true);
			if (trim($page->title)) {
				$title = trim($page->title);
			}
		}

		$screenshotURL = "https://texttv.nu/api/screenshot/" . implode(",", $arr_page_ids);

		$arr_json["screenshot"] = $screenshotURL;
		$arr_json["permalink"] = $permalinkURL;
		$arr_json["title"] = $title;

		$this->output->set_content_type("application/json");
		echo json_encode($arr_json);

		exit;
	}

	/**
	 * Hämta en arkiv-sida
	 * 
	 * @param int $id Id för sida (inte sidnummer)
	 */
	public function getid($page_id = null)
	{

		$arr_json = [];

		// Bail of not ok page id.
		if (!is_numeric($page_id)) {
			$arr_json['error_code'] = 'ID_NOT_VALID';
			$arr_json['is_ok'] = false;
			$arr_json['is_error'] = true;

			$this->output->set_content_type("application/json");
			$this->output->append_output(json_encode($arr_json));
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
		$this->output->append_output(json_encode($arr_json));
	}

	/**
	 * Hämta sida baserat på nummer
	 * 
	 * @param mixed $page_num
	 */
	/**
	 * Hämta och validera JSONP-callbacken från query-strängen.
	 *
	 * Callbacken echoas rått före JSON-svaret i views/api.php, och get_html-grenen
	 * går ut som text/html — utan den här vitlistan blir vilken sträng som helst
	 * reflekterad XSS på texttv.nu. Se K1 i todos/08-sakerhetsgranskning-2026-08-01.md.
	 *
	 * Tillåter bara det som faktiskt kan vara ett JS-funktionsnamn (jQuerys
	 * "?jsoncallback=?" blir t.ex. jQuery112409… — punkt tillåts för namespace).
	 *
	 * @return string Callbacken, eller tom sträng om den inte är giltig.
	 */
	private function get_valid_jsoncallback()
	{
		$jsoncallback = (string) $this->input->get("jsoncallback");

		if (!preg_match('/^[A-Za-z0-9_.]{1,64}$/', $jsoncallback)) {
			return "";
		}

		return $jsoncallback;
	}

	public function get($page_num = "")
	{
		$data = array(
			"page_num" 		=> $page_num,
			"jsoncallback" 	=> $this->get_valid_jsoncallback(),
			"api_call"		=> "get"
		);
		$this->load->view('api', $data);
	}

	public function get_html($page_num = "")
	{
		$data = array(
			"page_num" 		=> $page_num,
			"jsoncallback" 	=> $this->get_valid_jsoncallback(),
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
	public function get_permalink($archive_ids)
	{

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
			$page_title_for_url = date('j-M-Y', $arr_pages[0]->date_updated_unix);
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
			// "Allsvenskan tabell, 20 okt 2026" i stället för tabellens första
			// rad. Bara webbens delningsknapp (js/scripts.js) anropar den här.
			"title" => $arr_pages[0]->get_archive_title(),
			"screenshot" => $screenshotURL
		);

		$this->output->set_content_type("application/json");
		echo json_encode($arr_json);

		// Mark pages as shared in db
		mark_archive_ids_as_shared($arr_archive_ids);

		exit;
	}

	/**
	 * Generate a screenshot of the current page
	 */
	public function screenshotCurrent($pagenum = 100)
	{
		$page = new texttv_page($pagenum);

		if (!$page) {
			return false;
		}

		header('x-debug-1: ' . $page->id);
		$screenshotRedirectURL = sprintf('https://texttv.nu/api/screenshot/%1$d', $page->id);
		header('x-debug-2: ' . $screenshotRedirectURL);
		$this->output->set_header("Location: $screenshotRedirectURL");

		// exit;
	}

	/**
	 * Delningsbilden för en eller flera arkivsidor, t.ex.
	 * /api/screenshot/37442923.png eller /api/screenshot/37442923,37442924.jpg.
	 *
	 * Sajtens og:image, så den hämtas mest av botar. `.png` ger PNG, `.jpg`
	 * och adresser utan ändelse (api/share och screenshotCurrent i gamla
	 * appar) ger JPEG. Bilden ritas i PHP av Share_image (todo #34; tidigare
	 * wkhtmltoimage, som inte finns i Debian 13) och sparas i shares/v2/. Ett
	 * arkiv-id ändras aldrig, så bilden kan cachas för alltid.
	 *
	 * @param string $str_page_ids kommaseparerade id:n, med eller utan ändelse
	 */
	public function screenshot($str_page_ids = null)
	{
		if (!preg_match('/^(\d+(?:,\d+)*)(?:\.(png|jpg))?$/', (string) $str_page_ids, $m)) {
			$this->_screenshot_not_found();
			return;
		}

		// Taket skyddar mot dyra anrop med påhittade listor.
		$arr_page_ids = array_map('intval', explode(',', $m[1]));
		if (count($arr_page_ids) > 6) {
			$this->_screenshot_not_found();
			return;
		}

		$format = ($m[2] ?? '') === 'png' ? 'png' : 'jpg';
		$name = count($arr_page_ids) === 1 ? $arr_page_ids[0] : md5(implode(',', $arr_page_ids));
		// v2: byt version för att rita om alla bilder. shares/ utan version
		// är wkhtmltoimage-bilderna.
		$dir = $_SERVER['DOCUMENT_ROOT'] . '/shares/v2';
		$image_file = "{$dir}/{$name}.{$format}";

		if (!is_file($image_file)) {
			$subpages = [];
			foreach ($arr_page_ids as $page_id) {
				$page = new Texttv_page();
				$page->id = $page_id;
				// Saknas en sida sparas ingen bild; i dag sparades 404-sidan.
				if (!$page->load(true) || !$page->arr_contents) {
					$this->_screenshot_not_found();
					return;
				}
				$subpages = array_merge($subpages, $page->arr_contents);
			}
			// Högst 12 undersidor, annars blir bilden orimligt lång.
			$subpages = array_slice($subpages, 0, 12);

			require_once APPPATH . 'libraries/Share_image.php';
			$renderer = new Share_image();
			$image = $renderer->render($subpages, $format);

			// Temporär fil + rename: en samtidig request ser aldrig en halv bild,
			// och två som ritar samma bild skriver bara över varandra.
			if (!is_dir($dir)) {
				@mkdir($dir, 0775, true);
			}
			$tmp = "{$image_file}." . getmypid() . '.tmp';
			if (@file_put_contents($tmp, $image) === false || !@rename($tmp, $image_file)) {
				@unlink($tmp);
				error_log("texttv: api/screenshot: kunde inte spara {$image_file}");
			}
		} else {
			$image = file_get_contents($image_file);
		}

		$mtime = is_file($image_file) ? filemtime($image_file) : time();
		$this->output
			->set_content_type($format === 'png' ? 'image/png' : 'image/jpeg')
			->set_header('Cache-Control: public, max-age=31536000, immutable')
			->set_header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
			// nginx fastcgi_cache följer annars Cache-Control och fyller
			// cachezonen med bilder; filen i shares/v2 är cachen.
			->set_header('X-Accel-Expires: 60')
			->set_output($image);
	}

	private function _screenshot_not_found()
	{
		$this->output
			->set_status_header(404)
			->set_content_type('text/plain')
			->set_header('Cache-Control: public, max-age=600')
			->set_output("Sidan finns inte.\n");
	}

	/**
	 * Åtgärded för sida.
	 * Modernare/mer korrekt API-REST-URLigt.
	 * 
	 * Endpoints:
	 * - api.texttv.nu/api/page/<pageid>/view
	 * - api.texttv.nu/api/page/<pageid>/share
	 */
	public function page($page_ids = null, $type = null)
	{
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

		$created_at = date("Y-m-d H:i:s");

		$arr_json_response = [
			'success' => true,
			"request" => [
				"page_ids" => $page_ids,
				"type" => $type,
				'timestamp' => $created_at
			]
		];

		$this->output->set_content_type("application/json");

		// VIEW har stöd för max 100 tecken i page_ids.
		$view_page_ids_max_length = 100;
		if ($type === 'VIEW' && mb_strlen($page_ids) > $view_page_ids_max_length) {
			$arr_json_response['success'] = false;
			$arr_json_response['error_message'] = 'page_ids more than 100 chars';
			$this->output->append_output(json_encode($arr_json_response));
			return;
		}

		$statsdb = $this->load->database('stats', TRUE);

		$insert_data = [
			'page_ids' => $page_ids,
			'created_at' => $created_at,
			'type' => $type
		];

		$statsdb->insert('page_actions', $insert_data);

		// Appen delar sedan 2019 utan /api/share, så delningar därifrån räknades
		// aldrig i texttv.is_shared: /sida/delat visade bara webben, och
		// städningen raderade de delade arkivsidorna efter ett år. Högst tio
		// sidor per delning, så att ett anrop inte kan räkna upp hela tabellen.
		// "Öppna i webbläsaren" (OPENLINK) är ingen delning: den gör att sidor
		// som 700 och 401 toppar listan.
		if (in_array($type, ['SHARE', 'COPYLINK', 'COPYTEXT'], true)) {
			$share_ids = array_slice(array_unique(array_filter(array_map("intval", $arr_page_ids))), 0, 10);
			mark_archive_ids_as_shared($share_ids);
		}

		$this->output->append_output(json_encode($arr_json_response));
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */