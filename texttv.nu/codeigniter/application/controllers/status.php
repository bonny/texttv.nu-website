<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Statussida (/status): hur importen mår, hur arkivet växer och hur mycket
 * som läses. Publik men noindex, inget hemligt. /status/health är till för
 * uptime-övervakningen och svarar 503 när importen tystnat.
 */
class Status extends CI_Controller {

	// Importen loggar varje kontroll i page_imports_logs, även oförändrade
	// sidor, så tystnad längre än så här betyder att den står still. Den
	// längsta luckan 2026-10-28 var 4 min (inklusive en avsiktlig paus).
	const TYST_MINUTER = 10;

	// Sidan cachas i en fil så att aggregeringarna inte körs vid varje anrop.
	const CACHE_SEKUNDER = 60;

	public function index() {

		$data = $this->hamta_data();

		$data["custom_page_title"] = "Status för TextTV.nu";
		$data["custom_canonical"] = "https://texttv.nu/status";
		$data["robots_noindex"] = true;
		$data["wrapclasses"] = array("textsida", "statussida");

		$this->load->view("header", $data);
		$this->load->view("status", $data);
		$this->load->view("controls", $data);
		$this->load->view("footer");
	}

	/**
	 * Hälsokontroll för uptime-verktyget: 200 om importen loggat något de
	 * senaste TYST_MINUTER minuterna, annars 503. Ingen filcache.
	 */
	public function health() {

		$row = $this->db->query("
			SELECT TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS sekunder
			FROM page_imports_logs
		")->row();

		$sekunder = $row && $row->sekunder !== null ? (int) $row->sekunder : null;
		$ok = $sekunder !== null && $sekunder <= self::TYST_MINUTER * 60;

		$this->output
			->set_status_header($ok ? 200 : 503)
			->set_header("Cache-Control: no-store")
			->set_content_type("application/json")
			->set_output(json_encode(array(
				"ok" => $ok,
				"senaste_import_sekunder_sedan" => $sekunder,
				"grans_sekunder" => self::TYST_MINUTER * 60,
			)));
	}

	private function hamta_data() {

		$cachefil = sys_get_temp_dir() . "/texttv-status.json";
		if (is_file($cachefil) && filemtime($cachefil) > time() - self::CACHE_SEKUNDER) {
			$data = json_decode(file_get_contents($cachefil), true);
			if (is_array($data)) {
				return $data;
			}
		}

		$db = $this->db;

		$import = $db->query("
			SELECT
				TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS senaste_logg,
				TIMESTAMPDIFF(SECOND, MAX(IF(import_result = 'IMPORT_SUCCESS', created_at, NULL)), NOW()) AS senaste_ny
			FROM page_imports_logs
		")->row_array();

		// Importresultat per timme i dag. Prod tömmer loggen vid midnatt, lokalt inte.
		$per_timme = array();
		foreach ($db->query("
			SELECT HOUR(created_at) AS timme, import_result, COUNT(*) AS antal
			FROM page_imports_logs
			WHERE created_at >= CURDATE()
			GROUP BY timme, import_result
		")->result_array() as $rad) {
			$per_timme[(int) $rad["timme"]][$rad["import_result"]] = (int) $rad["antal"];
		}

		$mest_andrade = $db->query("
			SELECT page_num, COUNT(*) AS antal
			FROM texttv
			WHERE date_added >= CURDATE()
			GROUP BY page_num
			ORDER BY antal DESC, page_num
			LIMIT 10
		")->result_array();

		$nya_per_dag = $db->query("
			SELECT DATE(date_added) AS dag, COUNT(*) AS antal
			FROM texttv
			WHERE date_added >= CURDATE() - INTERVAL 29 DAY
			GROUP BY dag
			ORDER BY dag
		")->result_array();

		$arkiv = $db->query("
			SELECT COUNT(*) AS versioner, MIN(date_added) AS aldsta, COUNT(DISTINCT page_num) AS sidor
			FROM texttv
		")->row_array();

		// Visningar och delningar per dag. page_actions sparas i 10 dagar.
		$statsdb = $this->load->database('stats', TRUE);
		$lasning = array();
		foreach ($statsdb->query("
			SELECT DATE(created_at) AS dag, type, COUNT(*) AS antal
			FROM page_actions
			WHERE created_at >= CURDATE() - INTERVAL 9 DAY
			GROUP BY dag, type
			ORDER BY dag
		")->result_array() as $rad) {
			$lasning[$rad["dag"]][$rad["type"]] = (int) $rad["antal"];
		}

		$data = array(
			"beraknad" => date("Y-m-d H:i:s"),
			"tyst_minuter" => self::TYST_MINUTER,
			"import" => $import,
			"per_timme" => $per_timme,
			"mest_andrade" => $mest_andrade,
			"nya_per_dag" => $nya_per_dag,
			"arkiv" => $arkiv,
			"lasning" => $lasning,
		);

		@file_put_contents($cachefil, json_encode($data), LOCK_EX);

		return $data;
	}
}
