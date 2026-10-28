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
		$data["drift"] = $this->drift();
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

		$cachefil = sys_get_temp_dir() . "/texttv-status-v3.json";
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

		// Visningar per timme i dag.
		$lasning_per_timme = array();
		for ($timme = 0; $timme <= (int) date("G"); $timme++) {
			$lasning_per_timme[$timme] = 0;
		}
		foreach ($statsdb->query("
			SELECT HOUR(created_at) AS timme, COUNT(*) AS antal
			FROM page_actions
			WHERE created_at >= CURDATE() AND type = 'VIEW'
			GROUP BY timme
		")->result_array() as $rad) {
			if (isset($lasning_per_timme[(int) $rad["timme"]])) {
				$lasning_per_timme[(int) $rad["timme"]] = (int) $rad["antal"];
			}
		}

		// Mest lästa sidor i dag. page_ids är en kommalista med en vys sidor;
		// joinen jämför som tal och träffar därför den första, precis som
		// most_read i texttv_helper.php.
		$mest_lasta = $db->query("
			SELECT tt.page_num, COUNT(*) AS antal
			FROM texttv_stats.page_actions AS pa
			INNER JOIN `texttv.nu`.texttv AS tt ON (tt.id = pa.page_ids)
			WHERE pa.created_at >= CURDATE() AND pa.type = 'VIEW'
			GROUP BY tt.page_num
			ORDER BY antal DESC, tt.page_num
			LIMIT 10
		")->result_array();
		if ($mest_lasta) {
			$titlar = array();
			foreach ($db->query("
				SELECT page_num, title
				FROM texttv
				WHERE id IN (
					SELECT MAX(id) FROM texttv
					WHERE page_num IN (" . implode(",", array_map("intval", array_column($mest_lasta, "page_num"))) . ")
					GROUP BY page_num
				)
			")->result_array() as $rad) {
				$titlar[$rad["page_num"]] = $rad["title"];
			}
			foreach ($mest_lasta as $i => $rad) {
				$mest_lasta[$i]["titel"] = isset($titlar[$rad["page_num"]]) ? $titlar[$rad["page_num"]] : "";
			}
		}

		// Arkivet per år. Går igenom hela tabellen, så det cachas en timme.
		$arkiv_per_ar = $this->cachat("texttv-status-arkiv-per-ar", 3600, function () use ($db) {
			return $db->query("
				SELECT YEAR(date_added) AS ar, COUNT(*) AS antal
				FROM texttv
				WHERE date_added >= '2000-01-01'
				GROUP BY ar
				ORDER BY ar
			")->result_array();
		});

		$data = array(
			"beraknad" => date("Y-m-d H:i:s"),
			"import" => $import,
			"per_timme" => $per_timme,
			"mest_andrade" => $mest_andrade,
			"mest_lasta" => $mest_lasta,
			"nya_per_dag" => $nya_per_dag,
			"arkiv" => $arkiv,
			"arkiv_per_ar" => $arkiv_per_ar,
			"lasning" => $lasning,
			"lasning_per_timme" => $lasning_per_timme,
		);

		@file_put_contents($cachefil, json_encode($data), LOCK_EX);

		return $data;
	}

	/**
	 * Senaste deploy och hur länge servern och tjänsterna varit uppe. Billigt
	 * (några filer i /proc och en statusfråga), så ingen filcache.
	 */
	private function drift() {

		$deploy = null;
		$deployfil = APPPATH . "deploy.json";
		if (is_file($deployfil)) {
			$deploy = json_decode(file_get_contents($deployfil), true);
		}

		// Uppstart som Unix-tid. /proc/stat:s btime är när servern startade,
		// fält 22 i /proc/<pid>/stat när processen startade (i klockslag om
		// 1/100 s sedan uppstart).
		$btime = null;
		if (preg_match('/^btime (\d+)/m', (string) @file_get_contents("/proc/stat"), $m)) {
			$btime = (int) $m[1];
		}
		$process_start = function ($pidfil) use ($btime) {
			$pid = (int) @file_get_contents($pidfil);
			$stat = $pid > 0 ? @file_get_contents("/proc/$pid/stat") : false;
			if ($btime === null || !$stat) {
				return null;
			}
			// Processnamnet inom parentes kan innehålla mellanslag.
			$falt = explode(" ", substr($stat, strrpos($stat, ")") + 2));
			return isset($falt[19]) ? $btime + (int) round($falt[19] / 100) : null;
		};

		$mariadb_uptime = null;
		$rad = $this->db->query("SHOW GLOBAL STATUS LIKE 'Uptime'")->row_array();
		if ($rad && isset($rad["Value"])) {
			$mariadb_uptime = time() - (int) $rad["Value"];
		}

		$minne = array();
		if (preg_match_all('/^(MemTotal|MemAvailable):\s+(\d+)/m', (string) @file_get_contents("/proc/meminfo"), $m, PREG_SET_ORDER)) {
			foreach ($m as $traff) {
				$minne[$traff[1]] = (int) $traff[2] * 1024;
			}
		}

		$load = function_exists("sys_getloadavg") ? sys_getloadavg() : false;
		$karnor = preg_match_all('/^processor\s*:/m', (string) @file_get_contents("/proc/cpuinfo"));

		return array(
			"deploy" => is_array($deploy) ? $deploy : null,
			"server_sedan" => $btime,
			"nginx_sedan" => $process_start("/run/nginx.pid"),
			// Versionen från PHP självt, så att raden klarar bytet till 8.4 (#17 A.6).
			"php_sedan" => $process_start("/run/php/php" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "-fpm.pid"),
			"mariadb_sedan" => $mariadb_uptime,
			"load" => $load ? $load : null,
			"karnor" => $karnor ? $karnor : null,
			"minne_totalt" => isset($minne["MemTotal"]) ? $minne["MemTotal"] : null,
			"minne_ledigt" => isset($minne["MemAvailable"]) ? $minne["MemAvailable"] : null,
			"disk_totalt" => @disk_total_space("/"),
			"disk_ledigt" => @disk_free_space("/"),
		);
	}

	/**
	 * Värdet från $hamta, cachat i en fil i $sekunder.
	 */
	private function cachat($nyckel, $sekunder, $hamta) {
		$fil = sys_get_temp_dir() . "/$nyckel.json";
		if (is_file($fil) && filemtime($fil) > time() - $sekunder) {
			$varde = json_decode(file_get_contents($fil), true);
			if ($varde !== null) {
				return $varde;
			}
		}
		$varde = $hamta();
		@file_put_contents($fil, json_encode($varde), LOCK_EX);
		return $varde;
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
