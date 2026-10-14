<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/*
Controller for most of the pages
http://texttv.nu/codeigniter/index.php/test
*/
class Sida extends CI_Controller {

	/**
	 * Sidor som har dagsarkiv (/NNN/igar, /NNN/YYYY-MM-DD). Bara sidor där
	 * en dag är en enhet, alltså resultatsidor som fylls på under dagen.
	 * Nyhetssidor byter innehåll under dagen och passar inte. Tipssidorna
	 * (550–571) är inte med: SVT har slutat sända dem. Todo #16.
	 *
	 * Per sida:
	 *  - subject: ämnet i /igar-titeln, "377 igår - SVT Text TV - <subject>"
	 *  - label:   ordet före datumet på datumsidorna, "<label> 13 okt 2026"
	 *  - suffix:  ev. ämnesord efter datumet i datumsidornas titel
	 */
	const DAY_ARCHIVE = [
		330 => ["subject" => "Resultatbörsen", "label" => "Resultatbörsen", "suffix" => ""],
		377 => ["subject" => "Målservice och resultat", "label" => "Resultat", "suffix" => "Målservice "],
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

		if (!isset(self::DAY_ARCHIVE[$page_num])) {
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

		// Sidan när dagen var slut. Kan vara från en tidigare dag om sidan inte
		// ändrades, eftersom importern bara sparar ändringar.
		$final_version = get_page_version_at_end_of_day($page_num, $date->format("Y-m-d"));
		if (!$final_version) {
			show_texttv_404();
			return;
		}

		$page = new texttv_page();
		$page->id = $final_version->id;
		if (!$page->load(TRUE) || empty($page->arr_contents)) {
			show_texttv_404();
			return;
		}

		// Länkar till dagens versioner. Ett och samma lätta objekt räcker:
		// permalänken byggs av sidnummer, titel och id, inte av innehållet.
		$versions = get_page_versions_for_day($page_num, $date->format("Y-m-d"));
		$version_page = new texttv_page();
		$version_page->num = $page_num;
		$version_links = [];
		foreach ($versions as $one_version) {
			$version_page->id = $one_version->id;
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

		// Länka bara bakåt om det finns något att visa där, så att arkivets
		// början inte blir en kedja av länkar till 404:or.
		$has_prev = (bool) get_page_version_at_end_of_day($page_num, $prev_date->format("Y-m-d"));

		// date_sv() går på PHP:s standardtidszon. Mitt på dagen ger samma
		// datum oavsett om den är UTC (lokal Docker) eller Europe/Stockholm.
		$human = function (DateTime $d, $format) {
			return date_sv($format, strtotime($d->format("Y-m-d") . " 12:00"));
		};

		$archive_conf = self::DAY_ARCHIVE[$page_num];
		$subject = $archive_conf["subject"];
		$date_human = $human($date, "l j F Y");

		// En dag utan egna versioner visar samma innehåll som dagen då
		// versionen las till (t.ex. tipssidorna mitt i veckan). Den dagens
		// datumsida är originalet, så att dubbletterna inte tävlar.
		$final_day = substr($final_version->date_added, 0, 10);

		if ($is_yesterday_url) {
			// /igar är alltid sitt eget original, även när innehållet är äldre.
			$canonical = "https://texttv.nu/{$page_num}/igar";
			$meta_title = "{$page_num} igår - SVT Text TV - {$subject}";
			$headline = "SVT Text TV {$page_num} igår - {$subject}";
		} else {
			$canonical = "https://texttv.nu/{$page_num}/" . (empty($versions) ? $final_day : $date->format("Y-m-d"));
			// Datumet först och i H1: Google skriver om titlarna på de här
			// sidorna från H1, och utan datum där ser alla datumsidor ut som
			// den levande sidan. Titeln hålls under ~60 tecken.
			$meta_title = sprintf(
				'%1$s %2$s – %3$sSVT Text TV %4$d',
				$archive_conf["label"],
				$human($date, "l j M Y"),
				$archive_conf["suffix"],
				$page_num
			);
			$headline = sprintf('SVT Text TV %1$d – %2$s %3$s', $page_num, $archive_conf["label"], $date_human);
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
			"final_date_added" => $final_version->date_added,
			"prev_url" => $has_prev ? "/{$page_num}/" . $prev_date->format("Y-m-d") : null,
			"prev_human" => $human($prev_date, "l j F"),
			"next_url" => $next_date == $today ? "/{$page_num}" : "/{$page_num}/" . $next_date->format("Y-m-d"),
			"next_human" => $next_date == $today ? "idag" : $human($next_date, "l j F"),
		];

		// En passerad dag ändras inte, så nginx får cacha länge. Men /igar byter
		// innehåll vid midnatt, och gårdagens datumsida länkar "idag →" till den
		// levande sidan. De får aldrig cachas förbi midnatt.
		$cache_seconds = $is_yesterday_url ? 600 : 3600;
		if ($is_yesterday_url || $next_date == $today) {
			$tomorrow = clone $today;
			$tomorrow->modify("+1 day");
			$cache_seconds = max(1, min($cache_seconds, $tomorrow->getTimestamp() - time()));
		}
		$this->output->set_header("X-Accel-Expires: " . $cache_seconds);

		$this->load->view('header', $data);
		$this->load->view('pages_day_archive', $data);
		$this->load->view('pages-latest-updated', $data);
		$this->load->view('controls', $data);
		$this->load->view('footer', $data);
	}

	/**
	 * Senast sända version av en sida som just nu är "ej i sändning", plus
	 * underlag för texten om när den brukar sändas. Null om ingen version med
	 * innehåll finns de senaste 60 dygnen.
	 */
	private function _latest_broadcast_fallback($current_page) {
		$row = get_latest_broadcast_version($current_page->num);
		if (!$row) {
			return null;
		}

		$page = new texttv_page();
		$page->id = $row->id;
		if (!$page->load(TRUE) || empty($page->arr_contents)) {
			return null;
		}
		// Pollningen ska jämföra mot "ej i sändning"-raden, inte mot den
		// äldre versionen som visas, annars lyser "ny version" hela tiden.
		$page->polling_added_unix = $current_page->date_added_unix;

		$weekdays = get_page_broadcast_weekdays($current_page->num);

		return [
			"page" => $page,
			"broadcast_human" => date_sv("l j F", $page->date_added_unix) . " kl " . date("H:i", $page->date_added_unix),
			"weekdays_human" => $weekdays ? format_broadcast_weekdays($weekdays) : "",
		];
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

		// Sidor som inte sänds just nu (t.ex. tipssidorna mitt i veckan) visar
		// annars bara "Sidan ej i sändning", och det är den tomma sidan Google
		// oftast hämtar. Visa senast sända versionen i stället, med en
		// förklaring. Bara för enskilda sidor. Todo #16.
		//
		// Har sidan inte sänts på länge (tipssidorna 550–571 togs bort av SVT
		// 2024–2026) finns ingen färsk version att visa. Då står det i stället
		// sedan när sidan inte sänts. "Ej i sändning"-raden läggs till när
		// sidan försvinner och ersätts inte förrän den kommer tillbaka, så
		// dess date_added är tidpunkten då den slutade sändas.
		$not_broadcasted = null;
		if (sizeof($arr_page_objs) == 1 && $arr_page_objs[0]->is_not_broadcasted()) {
			$not_broadcasted = $this->_latest_broadcast_fallback($arr_page_objs[0]);
			if ($not_broadcasted) {
				$arr_page_objs[0] = $not_broadcasted["page"];
				$page = $not_broadcasted["page"];
			} elseif ($arr_page_objs[0]->date_added_unix < strtotime("-14 days")) {
				$not_broadcasted = [
					"page" => null,
					"off_air_since_human" => date_sv("j F Y", $arr_page_objs[0]->date_added_unix),
				];
			}
		}

		// Skapa array med data att skicka med till vyerna
		$data = array();
		$data["not_broadcasted"] = $not_broadcasted;
		$data["page"] = $page;
		$data["pages"] = $arr_page_objs;
		$data["pagenum"] = $pagenum;
		$data["pagedescription"] = $pagedescription;

		// Ladda vyer för sidhuvud och kontroller
		$this->load->view('header', $data);

		$this->load->view("pages_updated_container", $data);
		$this->load->view("breadcrumbs", $data);
		$this->load->view("pages_current_page_top", $data);
		if ($not_broadcasted) {
			$this->load->view("not_broadcasted_notice", $data);
		}
		$this->load->view("pages_inner_output_current", $data);
		// Länkar till gårdagen (todo #16): startsidan får alla, en enskild sida
		// får sitt eget dagsarkiv och sin avdelnings dagslista.
		$yesterday_links = [];
		if ($pagedescription === "startpage") {
			$yesterday_links = [
				["/nyheter/igar", "Gårdagens nyheter"],
				["/sport/igar", "Gårdagens sportnyheter"],
				["/377/igar", "Resultat igår 377"],
				["/330/igar", "Resultatbörsen igår 330"],
			];
		} elseif (sizeof($arr_page_objs) == 1) {
			$num = (int) $arr_page_objs[0]->num;
			// "← 377 igår" bara om det finns något att visa där, så länken
			// inte leder till 404.
			if (isset(self::DAY_ARCHIVE[$num])) {
				$yesterday = new DateTime("yesterday", new DateTimeZone("Europe/Stockholm"));
				if (get_page_version_at_end_of_day($num, $yesterday->format("Y-m-d"))) {
					$yesterday_links[] = ["/{$num}/igar", "{$num} igår"];
				}
			}
			if ($num >= 100 && $num <= 199) {
				$yesterday_links[] = ["/nyheter/igar", "Gårdagens nyheter"];
			} elseif ($num >= 300 && $num <= 399) {
				$yesterday_links[] = ["/sport/igar", "Gårdagens sportnyheter"];
			}
		}
		if ($yesterday_links) {
			$data["yesterday_links"] = $yesterday_links;
			$data["yesterday_prefix"] = $pagedescription === "startpage" ? "Igår på Text TV:" : "←";
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
