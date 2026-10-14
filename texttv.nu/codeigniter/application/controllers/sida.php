<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/*
Controller for most of the pages
http://texttv.nu/codeigniter/index.php/test
*/
class Sida extends CI_Controller {

	/**
	 * Sidor som har dagsarkiv (/NNN/igar, /NNN/YYYY-MM-DD). Bara sidor där
	 * en dag är en enhet, alltså resultatsidor som fylls på under dagen.
	 * Nyhetssidor byter innehåll under dagen och passar inte. Todo #16.
	 */
	const DAY_ARCHIVE_PAGES = [330, 377];

	/**
	 * Rubrik per sida i dagsarkivet. Samma ämnesord som H1:orna i
	 * pages_inner_output_current.php.
	 */
	const DAY_ARCHIVE_SUBJECTS = [
		330 => "Resultatbörsen",
		377 => "Målservice och resultat",
	];

	// Om ingen sida anges går vi till 100
	// Update 3 april 2015: vi visar över sporten, 300, pga. det är säkert bra
	public function index() {
		$this->visa("100,300,401,101-105", "startpage");
	}

	/**
	 * Visa en äldre version av en sida
	 * Aka TextTV-TimeMachine!
	 * Visa dock inte alla = får timeout, visa senaste dagarna typ bara
	 */
	function arkiv($page_num, $title = NULL, $db_id = NULL) {
		// Om bara page_num = visa översikt över alla arkiv som finns
		if (empty($db_id) && empty($title)) {
			// 28 juni (år?): inaktiverar denna, känns inte som bidrar till något
			show_texttv_404();
			return;
		} else { //if (is_numeric($db_id) && isset($title)) {
			// En eller flera sidor från arkivet ska visas
			$arr_db_ids = explode(",", $db_id);
			$arr_pages = array();
			foreach ($arr_db_ids as $one_db_id) {
				// om inte ett nummer: abort!
				if (!is_numeric($one_db_id)) {
					die("There was an error, ey!");
				}
				$page = new texttv_page();
				$page->id = $one_db_id;

				$load_ok = $page->load(TRUE);

				$arr_pages[] = $page;
			}

			if ($load_ok) {

				$data = [];
				$data["page"] = $page;
				$data["pages"] = $arr_pages;
				$data["pagenum"] = $page_num;
				$data["is_archive"] = TRUE;
				$data["page_permalink"] = $page->get_permalink();

				$this->load->view('header', $data);
				$this->load->view('pages_inner_output_archive', $data);
				$this->load->view('pages-latest-updated', $data);
				$this->load->view('controls', $data);
				$this->load->view('footer', $data);
			} else {

				show_texttv_404();
				return;
			}
		}
	}


	/**
	 * Dagsarkiv: sidans sista version en viss dag, plus dagens tidigare
	 * versioner. /377/igar och /377/2026-10-12. Todo #16.
	 *
	 * Fångar sökningar som "377 igår" och "resultat söndag", som SVT inte kan
	 * svara på eftersom deras sidor bara visar nuläget.
	 */
	function dag($page_num, $day = NULL) {
		$page_num = (int) $page_num;
		$tz = new DateTimeZone("Europe/Stockholm");
		$today = new DateTime("today", $tz);
		$is_yesterday_url = ($day === "igar");

		if (!in_array($page_num, self::DAY_ARCHIVE_PAGES, TRUE)) {
			show_texttv_404();
			return;
		}

		if ($is_yesterday_url) {
			$date = clone $today;
			$date->modify("-1 day");
		} else {
			$date = DateTime::createFromFormat("!Y-m-d", (string) $day, $tz);
			if (!$date || $date->format("Y-m-d") !== $day) {
				show_texttv_404();
				return;
			}
		}

		// Idag finns på den levande sidan. Framtiden finns inte.
		if ($date == $today) {
			redirect("/" . $page_num, "location", 302);
			return;
		}
		if ($date > $today) {
			show_texttv_404();
			return;
		}

		$versions = get_page_versions_for_day($page_num, $date->format("Y-m-d"));
		if (empty($versions)) {
			show_texttv_404();
			return;
		}

		$page = new texttv_page();
		$page->id = $versions[0]->id;
		$page->load(TRUE);

		// Lätta objekt för länkarna till dagens tidigare versioner. Permalänken
		// byggs av sidnummer, titel och id, så innehållet behöver inte laddas.
		$version_links = [];
		foreach ($versions as $one_version) {
			$version_page = new texttv_page();
			$version_page->id = $one_version->id;
			$version_page->num = $page_num;
			$version_page->title = $one_version->title;
			$version_links[] = [
				"time" => substr($one_version->date_added, 11, 5),
				"permalink" => $version_page->get_permalink(),
			];
		}

		$prev_date = clone $date;
		$prev_date->modify("-1 day");
		$next_date = clone $date;
		$next_date->modify("+1 day");

		// date_sv() går på PHP:s standardtidszon. Mitt på dagen ger samma
		// datum oavsett om den är UTC (lokal Docker) eller Europe/Stockholm.
		$human = function (DateTime $d, $format) {
			return date_sv($format, strtotime($d->format("Y-m-d") . " 12:00"));
		};

		$subject = self::DAY_ARCHIVE_SUBJECTS[$page_num];
		$date_human = $human($date, "l j F Y");

		if ($is_yesterday_url) {
			$canonical = "https://texttv.nu/{$page_num}/igar";
			$meta_title = "{$page_num} igår - SVT Text TV - {$subject}";
			$headline = "SVT Text TV {$page_num} igår - {$subject}";
		} else {
			$canonical = "https://texttv.nu/{$page_num}/" . $date->format("Y-m-d");
			$meta_title = "{$page_num} - {$subject} {$date_human} - SVT Text TV";
			$headline = "SVT Text TV {$page_num} - {$subject}";
		}

		$data = [];
		$data["page"] = $page;
		$data["pages"] = [$page];
		$data["pagenum"] = $page_num;
		$data["day_archive"] = [
			"canonical" => $canonical,
			"meta_title" => $meta_title,
			"meta_description" => sprintf(
				'%1$s från SVT Text TV %2$d %3$s, som sidan såg ut när dagen var slut. Med alla tidigare versioner under dagen.',
				$subject,
				$page_num,
				$date_human
			),
			"headline" => $headline,
			"date_human" => $date_human,
			"is_yesterday_url" => $is_yesterday_url,
			"version_links" => $version_links,
			"prev_url" => "/{$page_num}/" . $prev_date->format("Y-m-d"),
			"prev_human" => $human($prev_date, "l j F"),
			"next_url" => $next_date == $today ? "/{$page_num}" : "/{$page_num}/" . $next_date->format("Y-m-d"),
			"next_human" => $next_date == $today ? "idag" : $human($next_date, "l j F"),
		];

		// En passerad dag ändras inte, så nginx får cacha länge. /igar byter
		// innehåll vid midnatt och cachas kortare.
		$this->output->set_header("X-Accel-Expires: " . ($is_yesterday_url ? 600 : 3600));

		$this->load->view('header', $data);
		$this->load->view('pages_day_archive', $data);
		$this->load->view('pages-latest-updated', $data);
		$this->load->view('controls', $data);
		$this->load->view('footer', $data);
	}

	// Tar emot $pagenum som en eller flera sidor
	function visa($pagenum = NULL, $pagedescription = NULL) {

		// Debug 
		// $this->output->enable_profiler(TRUE);

		$arr_pages = texttv_page::extract_pages_from_ranges($pagenum);

		// Om inga sidor är nåt knas, så då fortsätter vi inte.
		if (empty($arr_pages)) {

			$this->output->set_status_header('400');
			$this->output->set_header("Content-Type: text/html; charset=utf-8");
			$this->output->append_output(sprintf("<p>Fel: '%s' är inte en giltig sida.", htmlspecialchars($pagenum)));

			return;
		}

		// Samla ihop alla sidor i en array med sid-object
		$arr_page_objs = array();
		foreach ($arr_pages as $one_page) {
			$page = new texttv_page($one_page);
			$arr_page_objs[] = $page;
		}

		// Skapa array med data att skicka med till vyerna
		$data = array();
		$data["page"] = $page;
		$data["pages"] = $arr_page_objs;
		$data["pagenum"] = $pagenum;
		$data["pagedescription"] = $pagedescription;

		// Ladda vyer för sidhuvud och kontroller
		$this->load->view('header', $data);

		$this->load->view("pages_updated_container", $data);
		$this->load->view("breadcrumbs", $data);
		$this->load->view("pages_current_page_top", $data);
		$this->load->view("pages_inner_output_current", $data);
		if (sizeof($arr_page_objs) == 1 && in_array((int) $arr_page_objs[0]->num, self::DAY_ARCHIVE_PAGES, TRUE)) {
			$this->load->view('day_archive_link', $data);
		}
		$this->load->view('pages-thumbs', $data);
		$this->load->view('pages-latest-updated', $data);
		$this->load->view('controls', $data);
		$this->load->view('page_text', $data);
		$this->load->view('footer', $data);
	}

	function amp_arkiv($page_num, $title = NULL, $db_id = NULL) {

		// En eller flera sidor från arkivet ska visas
		$arr_db_ids = explode(",", $db_id);
		$arr_pages = array();
		foreach ($arr_db_ids as $one_db_id) {
			// om inte ett nummer: abort!
			if (!is_numeric($one_db_id)) {
				die("There was an error, ey!");
			}
			$page = new texttv_page();
			$page->id = $one_db_id;

			$load_ok = $page->load(TRUE);

			$arr_pages[] = $page;
		}

		if ($load_ok) {

			$data = [];
			$data["page"] = $page;
			$data["pages"] = $arr_pages;
			$data["pagenum"] = $page_num;
			$data["is_archive"] = TRUE;
			$data["page_permalink"] = $page->get_permalink();

			$this->load->view('amp', $data);
		} else {
			// page not loaded ok
			show_texttv_404();
		}
	}

	// Tar emot $pagenum som en eller flera sidor
	// Som visa fast för AMP
	function amp($pagenum = NULL, $pagedescription = NULL) {

		// Debug 
		// $this->output->enable_profiler(TRUE);

		$arr_pages = texttv_page::extract_pages_from_ranges($pagenum);

		// Om inga sidor är nåt knas, så då fortsätter vi inte.
		if (empty($arr_pages)) {

			$this->output->set_status_header('400');
			$this->output->set_header("Content-Type: text/html; charset=utf-8");
			$this->output->append_output(sprintf("<p>Fel: '%s' är inte en giltig sida.", htmlspecialchars($pagenum)));

			return;
		}

		// Samla ihop alla sidor i en array med sid-object
		$arr_page_objs = array();
		foreach ($arr_pages as $one_page) {
			$page = new texttv_page($one_page);
			$arr_page_objs[] = $page;
		}

		// Skapa array med data att skicka med till vyerna
		$data = array();
		$data["page"] = $page;
		$data["pages"] = $arr_page_objs;
		$data["pagenum"] = $pagenum;
		$data["pagedescription"] = $pagedescription;

		$this->load->view('amp', $data);
	}
}
