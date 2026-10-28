<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Statussida (/status): hur importen mår, hur arkivet växer och hur mycket
 * som läses. Publik men noindex, inget hemligt. /status/health är till för
 * uptime-övervakningen (Pulsetic) och svarar 503 när importen tystnat.
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

		$data["tyst_minuter"] = self::TYST_MINUTER;
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
			SELECT
				TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS sekunder,
				CURTIME() < ? AS strax_efter_midnatt
			FROM page_imports_logs
		", array(sprintf("00:%02d:00", self::TYST_MINUTER)))->row();

		$sekunder = $row && $row->sekunder !== null ? (int) $row->sekunder : null;

		// import-status:remove-old tömmer loggen vid midnatt. En tom logg
		// strax efter är därför väntad, inget stopp.
		$ok = $sekunder === null
			? $row && $row->strax_efter_midnatt
			: $sekunder <= self::TYST_MINUTER * 60;

		$this->output
			->set_status_header($ok ? 200 : 503)
			->set_header("Cache-Control: no-store")
			->set_content_type("application/json")
			->set_output(json_encode(array(
				"ok" => (bool) $ok,
				"senaste_import_sekunder_sedan" => $sekunder,
				"grans_sekunder" => self::TYST_MINUTER * 60,
			)));
	}

	private function hamta_data() {

		$cachefil = sys_get_temp_dir() . "/texttv-status-v2.json";
		if (is_file($cachefil) && filemtime($cachefil) > time() - self::CACHE_SEKUNDER) {
			$data = json_decode(file_get_contents($cachefil), true);
			if (is_array($data)) {
				return $data;
			}
		}

		$db = $this->db;

		// Klockslag, inte "sekunder sedan": sidan räknar ut hur länge sedan
		// när den visas, annars fryser filcachen värdet.
		$import = $db->query("
			SELECT
				MAX(created_at) AS senaste_logg,
				MAX(IF(import_result = 'IMPORT_SUCCESS', created_at, NULL)) AS senaste_ny
			FROM page_imports_logs
		")->row_array();

		// Resultat per timme i dag. Prod tömmer loggen vid midnatt, lokalt
		// inte. Timmar utan rader fylls i med noll, så att ett avbrott syns.
		$per_timme = array();
		for ($timme = 0; $timme <= (int) date("G"); $timme++) {
			$per_timme[$timme] = array("nya" => 0, "oforandrade" => 0, "ej_sanda" => 0, "fel" => 0);
		}
		$kategori = array(
			"IMPORT_SUCCESS" => "nya",
			"NOT_IMPORTED_PAGE_NOT_CHANGED" => "oforandrade",
			// Sidor som SVT inte sänder just nu. Inget fel.
			"NOT_IMPORTED_REMOTE_NOT_BROADCASTED" => "ej_sanda",
			"IMPORTED_AS_NOT_BROADCASTED" => "ej_sanda",
		);
		foreach ($db->query("
			SELECT HOUR(created_at) AS timme, import_result, COUNT(*) AS antal
			FROM page_imports_logs
			WHERE created_at >= CURDATE()
			GROUP BY timme, import_result
		")->result_array() as $rad) {
			$timme = (int) $rad["timme"];
			$nyckel = isset($kategori[$rad["import_result"]]) ? $kategori[$rad["import_result"]] : "fel";
			if (isset($per_timme[$timme])) {
				$per_timme[$timme][$nyckel] += (int) $rad["antal"];
			}
		}

		// Senaste rubriken per sida, så att listan säger vad som ändrats.
		$mest_andrade = $db->query("
			SELECT
				page_num,
				COUNT(*) AS antal,
				SUBSTRING_INDEX(GROUP_CONCAT(title ORDER BY id DESC SEPARATOR '\n'), '\n', 1) AS titel
			FROM texttv
			WHERE date_added >= CURDATE()
			GROUP BY page_num
			ORDER BY antal DESC, page_num
			LIMIT 10
		")->result_array();

		$nya_per_dag = $this->tomma_dagar(30);
		foreach ($db->query("
			SELECT DATE(date_added) AS dag, COUNT(*) AS antal
			FROM texttv
			WHERE date_added >= CURDATE() - INTERVAL 29 DAY
			GROUP BY dag
		")->result_array() as $rad) {
			if (isset($nya_per_dag[$rad["dag"]])) {
				$nya_per_dag[$rad["dag"]] = (int) $rad["antal"];
			}
		}

		// Några rader har nolldatum (1970). De är inte arkivets början.
		$arkiv = $db->query("
			SELECT
				COUNT(*) AS versioner,
				COUNT(DISTINCT page_num) AS sidor,
				MIN(IF(date_added >= '2000-01-01', date_added, NULL)) AS aldsta
			FROM texttv
		")->row_array();

		// Visningar och allt som är en delning, per dag. page_actions
		// sparas i 10 dagar.
		$lasning = array();
		foreach ($this->tomma_dagar(10) as $dag => $noll) {
			$lasning[$dag] = array("visningar" => 0, "delat" => 0);
		}
		$statsdb = $this->load->database('stats', TRUE);
		foreach ($statsdb->query("
			SELECT DATE(created_at) AS dag, type = 'VIEW' AS visning, COUNT(*) AS antal
			FROM page_actions
			WHERE created_at >= CURDATE() - INTERVAL 9 DAY
			GROUP BY dag, visning
		")->result_array() as $rad) {
			if (isset($lasning[$rad["dag"]])) {
				$lasning[$rad["dag"]][$rad["visning"] ? "visningar" : "delat"] += (int) $rad["antal"];
			}
		}

		$data = array(
			"beraknad" => date("Y-m-d H:i:s"),
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

	/**
	 * De senaste $antal dagarna till och med i dag, som "Y-m-d" => 0.
	 */
	private function tomma_dagar($antal) {
		$dagar = array();
		for ($i = $antal - 1; $i >= 0; $i--) {
			$dagar[date("Y-m-d", strtotime("-$i days"))] = 0;
		}
		return $dagar;
	}
}
