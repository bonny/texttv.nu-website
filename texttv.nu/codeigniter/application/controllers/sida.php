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
	 * (550–571) är inte med: SVT har slutat sända dem. 330 togs bort samma
	 * dag den lades till: den är en meny över resultatsidorna, inte
	 * resultat, så varje dag blev en nästan identisk sida. Todo #16.
	 *
	 * Per sida:
	 *  - subject: ämnet i /igar-titeln, "377 igår - SVT Text TV - <subject>"
	 *  - label:   ordet före datumet på datumsidorna, "<label> 13 okt 2026"
	 *  - suffix:  ev. ämnesord efter datumet i datumsidornas titel
	 */
	const DAY_ARCHIVE = [
		377 => ["subject" => "Målservice och resultat", "label" => "Resultat", "suffix" => "Målservice "],
	];

	/**
	 * Antal av gårdagens mest lästa nyheter i startsidans "Mest läst igår".
	 * Åtta i rutan bredvid 100 på bred skärm, där det finns plats. På smalare
	 * skärm visas fem (css/tt.css), så att sidorna under inte trycks ner för
	 * mycket. Todo #16, höjt från tre 2026-10-18.
	 */
	const STARTPAGE_YESTERDAY_COUNT = 8;

	/** Högsta antal sidor i en adress som /101-103,300 (granskning 2026-10-18). */
	const MAX_PAGES_PER_URL = 30;

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
					show_texttv_404();
					return;
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
				// "Polisen rapporterade" under arkiverade nyheter (todo #21).
				$data["polis_handelser"] = (sizeof($arr_pages) === 1 && (int) $page->num >= 100 && (int) $page->num <= 199)
					? bpk_handelser_for_titel($page->title, $page->date_added_unix)
					: [];

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

		// /330/igar och 330:s datumsidor fanns några timmar 2026-10-14.
		if ($page_num === 330) {
			redirect("/330", "location", 301);
			return;
		}

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

		$yesterday_date = clone $today;
		$yesterday_date->modify("-1 day");

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
			// Titel och H1 börjar inte med sidnumret, så att /377/igar inte
			// tävlar med /377 om "text tv 377" (Google skriver om titeln från H1).
			$canonical = "https://texttv.nu/{$page_num}/igar";
			$meta_title = sprintf(
				'%1$s igår (%2$s) – %3$sSVT Text TV %4$d',
				$archive_conf["label"],
				$human($date, "D j M"),
				$archive_conf["suffix"],
				$page_num
			);
			$headline = sprintf('%1$s igår, %2$s – SVT Text TV %3$d', $archive_conf["label"], $human($date, "l j F"), $page_num);
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
			"next_url" => $next_date == $today ? "/{$page_num}" : ($next_date == $yesterday_date ? "/{$page_num}/igar" : "/{$page_num}/" . $next_date->format("Y-m-d")),
			"next_human" => $next_date == $today ? "Idag på {$page_num}" : $human($next_date, "l j F"),
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

	/**
	 * Hur många av dagens tidigare versioner som länkas under sidan.
	 */
	const TODAY_VERSIONS_COUNT = 8;

	/**
	 * Dagens tidigare versioner av en sportsida (300–399), nyaste först,
	 * för raden "Tidigare i dag" under sidan. Svarar på "vad stod det i
	 * paus?" utan att man letar i arkivet. Versionen som visas räknas inte.
	 * Todo #20.
	 *
	 * @return array Med time ("H:i") och permalink, högst TODAY_VERSIONS_COUNT.
	 */
	private function _today_versions($arr_page_objs, $not_broadcasted) {
		if (sizeof($arr_page_objs) !== 1 || $not_broadcasted) {
			return [];
		}
		$current = $arr_page_objs[0];
		$num = (int) $current->num;
		if ($num < 300 || $num > 399 || empty($current->id)) {
			return [];
		}

		$today = new DateTime("today", new DateTimeZone("Europe/Stockholm"));
		// En extra, eftersom versionen som visas räknas bort nedan.
		$versions = get_page_versions_for_day($num, $today->format("Y-m-d"), self::TODAY_VERSIONS_COUNT + 1);

		// Ett lätt objekt räcker: permalänken byggs av nummer, titel och id.
		$version_page = new Texttv_page();
		$version_page->num = $num;
		$links = [];
		foreach ($versions as $one_version) {
			if ((int) $one_version->id === (int) $current->id) {
				continue;
			}
			$version_page->id = $one_version->id;
			$version_page->title = $one_version->title;
			$links[] = [
				"time" => substr($one_version->date_added, 11, 5),
				"permalink" => $version_page->get_permalink(),
			];
			if (sizeof($links) >= self::TODAY_VERSIONS_COUNT) {
				break;
			}
		}

		return $links;
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

		// Text TV har sidorna 100–999. /1000 och /099 gav förut 200 med en
		// tom sida; nu sajtens 404 (todo #19). Modellen lämnas orörd, den
		// används även av API:t.
		foreach ($arr_pages as $one_page) {
			if (!preg_match('/^\d{3}$/', (string) $one_page) || (int) $one_page < 100) {
				show_texttv_404();
				return;
			}
		}

		// Tak för hur många sidor en adress får visa. /100-999 gav 4 MB HTML
		// och 900 DB-frågor. Menyns största intervall är 376-395 (20 sidor).
		// Bara här: API:t har egna anropare och lämnas orört.
		if (sizeof($arr_pages) > self::MAX_PAGES_PER_URL) {
			show_texttv_404();
			return;
		}

		// Samla ihop alla sidor i en array med sid-object
		$arr_page_objs = array();
		foreach ($arr_pages as $one_page) {
			$page = new texttv_page($one_page);
			$arr_page_objs[] = $page;
		}

		// En sida som aldrig funnits i databasen (SVT har inte använt numret)
		// är också en 404, inte en tom sida.
		$finns = array_filter($arr_page_objs, function ($p) {
			return !empty($p->id);
		});
		if (!$finns) {
			show_texttv_404();
			return;
		}

		// Startsidan hoppar över sidor som inte sänds just nu ("103 Sidan ej
		// i sändning"). De tog annars en egen ruta och sköt rutnätet ur led
		// på desktop (todo #19).
		// 100 tas aldrig bort (titel och canonical för startsidan bygger på
		// den), och filtret används bara när minst två sidor blir kvar.
		if ($pagedescription === "startpage") {
			$sands = array_values(array_filter($arr_page_objs, function ($p) {
				return (int) $p->num === 100 || (!empty($p->id) && !$p->is_not_broadcasted());
			}));
			if (sizeof($sands) >= 2) {
				$arr_page_objs = $sands;
				$page = $sands[sizeof($sands) - 1];
			}
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
				$last_row = get_latest_broadcast_version($arr_page_objs[0]->num, null);
				$last_permalink = null;
				if ($last_row) {
					$last_page = new texttv_page();
					$last_page->id = $last_row->id;
					$last_page->num = (int) $arr_page_objs[0]->num;
					$last_page->title = $last_row->title;
					$last_permalink = $last_page->get_permalink();
				}
				$not_broadcasted = [
					"page" => null,
					"off_air_since_human" => date_sv("j F Y", $arr_page_objs[0]->date_added_unix),
					"last_permalink" => $last_permalink,
				];
			}
		}

		// Länkar till gårdagen (todo #16): startsidan får alla, en enskild sida
		// får sitt eget dagsarkiv och sin avdelnings dagslista.
		$yesterday_links = [];
		if ($pagedescription === "startpage") {
			// Rubriken ovanför säger redan "igår", så inget "Gårdagens" här.
			$yesterday_links = [
				["/nyheter/igar", "Nyheter"],
				["/sport/igar", "Sport"],
				["/377/igar", "Resultat 377"],
			];
		} elseif (sizeof($arr_page_objs) == 1) {
			$num = (int) $arr_page_objs[0]->num;
			// "← 377 igår" bara om det finns något att visa där, så länken
			// inte leder till 404.
			if (isset(self::DAY_ARCHIVE[$num])) {
				$yesterday = new DateTime("yesterday", new DateTimeZone("Europe/Stockholm"));
				if (get_page_version_at_end_of_day($num, $yesterday->format("Y-m-d"))) {
					$yesterday_links[] = ["/{$num}/igar", "Gårdagens resultat {$num}"];
				}
			}
			// Dagens lista först: "nyheten jag läste i morse" (granskning
			// 2026-10-18).
			if ($num >= 100 && $num <= 199) {
				$yesterday_links[] = ["/nyheter/idag", "Dagens nyheter"];
				$yesterday_links[] = ["/nyheter/igar", "Gårdagens nyheter"];
			} elseif ($num >= 300 && $num <= 399) {
				$yesterday_links[] = ["/sport/idag", "Dagens sportnyheter"];
				$yesterday_links[] = ["/sport/igar", "Gårdagens sportnyheter"];
			}
		}
		// Visas av pages_inner_output_current direkt under sidan, före
		// annonsen, så att de syns utan att man scrollar förbi den.
		// Startsidan visar dessutom gårdagens mest lästa nyheter, med länk
		// till varje nyhets arkivsida. Räknas fram en gång per dygn.
		$yesterday_top = $pagedescription === "startpage" ? yesterday_top_news(self::STARTPAGE_YESTERDAY_COUNT) : [];
		// Rubriken säger vad listan är; utan lista (t.ex. tidigt på dygnet
		// innan statistiken finns) gäller den bara länkarna.
		$yesterday_prefix = "";
		if ($pagedescription === "startpage") {
			$yesterday_prefix = $yesterday_top ? "Mest läst igår" : "Igår på Text TV";
		}
		// Flikarna "Idag" och "7 dagar" i samma ruta (todo #25), med länkraden till dagens
		// listor. Bara när gårdagens lista finns, annars rutan som förut.
		$today_top = $pagedescription === "startpage" && $yesterday_top ? today_top_news(self::STARTPAGE_YESTERDAY_COUNT) : [];
		$week_top = $pagedescription === "startpage" && $yesterday_top ? week_top_news(self::STARTPAGE_YESTERDAY_COUNT) : [];
		// Gårdagens 377 även här: Idag är förval efter kl 10, och utan den
		// länken föll /377/igar från 28–40 till 7 visningar per dag. /377
		// länkas redan överallt (todo #25).
		$today_links = [
			["/nyheter/idag", "Nyheter"],
			["/sport/idag", "Sport"],
			["/377/igar", "Resultat igår"],
		];
		// Skapa array med data att skicka med till vyerna
		$data = array();
		$data["not_broadcasted"] = $not_broadcasted;
		$data["page"] = $page;
		$data["pages"] = $arr_page_objs;
		$data["pagenum"] = $pagenum;
		$data["pagedescription"] = $pagedescription;
		$data["yesterday_links"] = $yesterday_links;
		$data["yesterday_prefix"] = $yesterday_prefix;
		$data["yesterday_top"] = $yesterday_top;
		$data["today_top"] = $today_top;
		$data["today_links"] = $today_links;
		$data["week_top"] = $week_top;
		$data["today_versions"] = $this->_today_versions($arr_page_objs, $not_broadcasted);
		// "Polisen rapporterade" under nyhetssidor (todo #21).
		$data["polis_handelser"] = (sizeof($arr_page_objs) === 1 && !$not_broadcasted && (int) $page->num >= 100 && (int) $page->num <= 199)
			? bpk_handelser_for_titel($page->title, $page->date_added_unix)
			: [];

		// Ladda vyer för sidhuvud och kontroller
		$this->load->view('header', $data);

		$this->load->view("pages_updated_container", $data);
		// Toppannonsen visas bara efter brödsmulorna (CSS-regeln
		// .breadcrumbs + .ad--beforeMainText). Utan dem skrivs den inte ut
		// alls: ett dolt <ins> fångade push() från blocket "innan fler
		// nyheter", som då aldrig fylldes (granskning 2026-10-18).
		$breadcrumbs_html = $this->load->view("breadcrumbs", $data, TRUE);
		$this->output->append_output($breadcrumbs_html);
		$data["has_breadcrumbs"] = strpos($breadcrumbs_html, 'class="breadcrumbs"') !== false;
		$this->load->view("pages_current_page_top", $data);
		if ($not_broadcasted) {
			$this->load->view("not_broadcasted_notice", $data);
		}
		$this->load->view("pages_inner_output_current", $data);
		$this->load->view('pages-latest-updated', $data);
		$this->load->view('controls', $data);
		$this->load->view('page_text', $data);
		$this->load->view('footer', $data);
	}

	// AMP-versionen är borttagen. Gamla /amp-adresser (Googlebot hämtar
	// fortfarande ett par hundra per dygn) skickas vidare med 301: sidor till
	// samma adress utan /amp, arkivsidor till sin permalänk som förut.
	function amp_redirect($page_num = NULL, $title = NULL, $db_id = NULL) {
		if ($db_id !== NULL) {
			$ids = explode(",", $db_id);
			foreach ($ids as $id) {
				if (!is_numeric($id)) {
					show_texttv_404();
					return;
				}
			}
			$page = new texttv_page();
			$page->id = end($ids);
			if (!$page->load(TRUE)) {
				show_texttv_404();
				return;
			}
			redirect($page->get_permalink(), 'location', 301);
			return;
		}

		// Utan /amp på slutet skulle sidan skicka till sig själv i en loop.
		$uri = $this->uri->uri_string();
		if (!preg_match('|^(.+)/amp$|', $uri, $match)) {
			show_texttv_404();
			return;
		}
		redirect($match[1], 'location', 301);
	}
}
