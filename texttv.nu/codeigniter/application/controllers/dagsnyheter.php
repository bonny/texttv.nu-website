<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Dagslistor: alla nyheter respektive sportnyheter som sändes en dag, med
 * de mest lästa först. /nyheter/igar, /nyheter/2026-10-12, /sport/igar och
 * /sport/2026-10-12. Todo #16.
 *
 * Startsidan visar flera sidor (100, 300, 401, 101–105) men bara nuläget.
 * Här samlas gårdagens, eller en viss dags, nyheter på en sida med länkar
 * till varje nyhets arkivsida.
 */
class Dagsnyheter extends CI_Controller
{
	/**
	 * Per avdelning:
	 *  - name:     ordet i titeln på datumsidorna
	 *  - yesterday: titeln på /igar-sidan
	 *  - live_url: dit dagens datum skickas
	 *  - most_read: typ för most_read-cachen
	 *  - groups:   [rubrik, första sida, sista sida], kontrollerat mot SVT:s
	 *              indexsidor 101 och 104 2026-10-14
	 *  - exclude:  notissidor (stympade rubriker), kulturindex, nyhetsrullen
	 *  - results:  resultatsidor med dagsarkiv att länka till
	 *  - other:    den andra avdelningen, för korslänkar
	 */
	const SECTIONS = [
		"nyheter" => [
			"name" => "Nyheter",
			"yesterday" => "Gårdagens nyheter",
			"live_url" => "/",
			"most_read" => "news",
			"groups" => [
				["Inrikes", 106, 129],
				["Utrikes", 130, 149],
				["Kultur och övrigt", 150, 199],
			],
			"exclude" => [127, 128, 148, 150, 188],
			"results" => [],
			"other" => "sport",
		],
		"sport" => [
			"name" => "Sportnyheter",
			"yesterday" => "Gårdagens sportnyheter",
			"live_url" => "/300",
			"most_read" => "sport",
			"groups" => [
				["Sport", 303, 329],
			],
			"exclude" => [],
			"results" => [
				377 => "Målservice",
				330 => "Resultatbörsen",
			],
			"other" => "nyheter",
		],
	];

	/**
	 * Nyheternas arkivsidor raderas efter ett år om ingen delat dem
	 * (texttv:cleanup-old-pages), så äldre listor blir ofullständiga.
	 */
	const MAX_DAYS_BACK = 365;

	/**
	 * most_read-cachen täcker 14 dagar. Äldre datum skulle köra den tunga
	 * queryn för varje sidvisning, så där visas inga mest lästa.
	 */
	const MOST_READ_DAYS_BACK = 14;

	public function visa($section = "", $day = "")
	{
		if (!isset(self::SECTIONS[$section])) {
			show_texttv_404();
			return;
		}
		$conf = self::SECTIONS[$section];

		$tz = new DateTimeZone("Europe/Stockholm");
		$today = new DateTime("today", $tz);
		$is_yesterday_url = ($day === "igar");

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

		if ($date == $today) {
			redirect($conf["live_url"], "location", 302);
			return;
		}

		$oldest = clone $today;
		$oldest->modify("-" . self::MAX_DAYS_BACK . " days");
		if ($date > $today || $date < $oldest) {
			show_texttv_404();
			return;
		}

		$day_ymd = $date->format("Y-m-d");

		// Hämta hela avdelningens intervall en gång och sortera in i grupper.
		$first_num = $conf["groups"][0][1];
		$last_num = $conf["groups"][sizeof($conf["groups"]) - 1][2];
		$rows = get_headlines_for_day($first_num, $last_num, $conf["exclude"], $day_ymd);

		if (empty($rows)) {
			show_texttv_404();
			return;
		}

		// Ett återanvänt objekt räcker för permalänkarna.
		$link_page = new texttv_page();
		$groups = [];
		foreach ($conf["groups"] as $group) {
			$groups[$group[0]] = [];
		}
		foreach ($rows as $row) {
			$link_page->id = $row->id;
			$link_page->num = (int) $row->page_num;
			$link_page->title = $row->title;
			$item = [
				"title" => trim($row->title),
				"time" => substr($row->first_added, 11, 5),
				"page_num" => (int) $row->page_num,
				"permalink" => $link_page->get_permalink(),
			];
			foreach ($conf["groups"] as $group) {
				if ($item["page_num"] >= $group[1] && $item["page_num"] <= $group[2]) {
					$groups[$group[0]][] = $item;
					break;
				}
			}
		}
		$groups = array_filter($groups);
		$headline_count = sizeof($rows);

		// Mest lästa, bara där cachen finns (se MOST_READ_DAYS_BACK).
		$most_read = [];
		$most_read_oldest = clone $today;
		$most_read_oldest->modify("-" . self::MOST_READ_DAYS_BACK . " days");
		if ($date >= $most_read_oldest) {
			list($most_read_pages) = most_read_pages_cached($conf["most_read"], strtotime($day_ymd . " 12:00"));
			// most_read räknar per version, så samma nyhet kan komma två
			// gånger. Behåll den mest lästa versionen av varje rubrik.
			$seen_titles = [];
			foreach ($most_read_pages as $one) {
				$title_key = mb_strtolower(trim($one["title"]));
				if (isset($seen_titles[$title_key])) {
					continue;
				}
				$seen_titles[$title_key] = true;
				if (sizeof($most_read) >= 10) {
					break;
				}
				$most_read[] = [
					"title" => trim($one["title"]),
					"time" => $one["date_added_time"],
					"page_num" => (int) $one["page_num"],
					// most_read ger absoluta länkar, sajten använder relativa.
					"permalink" => preg_replace('#^https://texttv\.nu#', '', $one["permalink"]),
				];
			}
		}

		// date_sv() går på PHP:s standardtidszon. Mitt på dagen ger samma
		// datum oavsett om den är UTC (lokal Docker) eller Europe/Stockholm.
		$human = function (DateTime $d, $format) {
			return date_sv($format, strtotime($d->format("Y-m-d") . " 12:00"));
		};
		$date_human = $human($date, "l j F Y");

		$prev_date = clone $date;
		$prev_date->modify("-1 day");
		$next_date = clone $date;
		$next_date->modify("+1 day");

		if ($is_yesterday_url) {
			$canonical = "https://texttv.nu/{$section}/igar";
			$meta_title = sprintf('%1$s, %2$s – SVT Text TV', $conf["yesterday"], $human($date, "l j M"));
			$headline = $conf["yesterday"] . " på SVT Text TV";
		} else {
			$canonical = "https://texttv.nu/{$section}/{$day_ymd}";
			$meta_title = sprintf('%1$s %2$s – SVT Text TV', $conf["name"], $human($date, "l j M Y"));
			$headline = sprintf('SVT Text TV – %1$s %2$s', $conf["name"], $date_human);
		}

		// Beskrivningen är unik per dag: antal och de mest lästa rubrikerna,
		// eller dagens första rubriker när mest lästa saknas.
		$all_items = array_merge(...array_values($groups));
		$top_titles = array_map(function ($item) {
			return $item["title"];
		}, array_slice($most_read ? $most_read : $all_items, 0, 3));
		$meta_description = sprintf(
			'Alla %1$d %2$s från SVT Text TV %3$s.',
			$headline_count,
			mb_strtolower($conf["name"]),
			$date_human
		);
		if ($top_titles) {
			$meta_description .= " " . ($most_read ? "Mest lästa: " : "Bland annat: ") . implode(", ", $top_titles) . ".";
		}

		// Resultatsidornas dagsarkiv samma dag.
		$results_links = [];
		foreach ($conf["results"] as $num => $label) {
			$results_links[] = [
				"url" => $is_yesterday_url ? "/{$num}/igar" : "/{$num}/{$day_ymd}",
				"label" => $is_yesterday_url ? "{$label} igår" : $label,
				"num" => $num,
			];
		}

		$other = $conf["other"];
		$data = [];
		$data["day_archive"] = [
			"canonical" => $canonical,
			"meta_title" => $meta_title,
			"meta_description" => $meta_description,
		];
		$data["day_headlines"] = [
			"section" => $section,
			"headline" => $headline,
			"date_human" => $date_human,
			"is_yesterday_url" => $is_yesterday_url,
			"groups" => $groups,
			"headline_count" => $headline_count,
			"most_read" => $most_read,
			"results_links" => $results_links,
			"other_url" => $is_yesterday_url ? "/{$other}/igar" : "/{$other}/{$day_ymd}",
			"other_label" => $is_yesterday_url ? self::SECTIONS[$other]["yesterday"] : self::SECTIONS[$other]["name"] . " samma dag",
			"canonical" => $canonical,
			"prev_url" => $prev_date >= $oldest ? "/{$section}/" . $prev_date->format("Y-m-d") : null,
			"prev_human" => $human($prev_date, "l j F"),
			"next_url" => $next_date == $today ? $conf["live_url"] : "/{$section}/" . $next_date->format("Y-m-d"),
			"next_human" => $next_date == $today ? "idag" : $human($next_date, "l j F"),
		];

		// En passerad dag ändras inte. /igar och gårdagens datumsida får aldrig
		// cachas förbi midnatt (innehåll respektive "idag →"-länk byts då).
		$cache_seconds = $is_yesterday_url ? 600 : 3600;
		if ($is_yesterday_url || $next_date == $today) {
			$tomorrow = clone $today;
			$tomorrow->modify("+1 day");
			$cache_seconds = max(1, min($cache_seconds, $tomorrow->getTimestamp() - time()));
		}
		$this->output->set_header("X-Accel-Expires: " . $cache_seconds);

		$this->load->view('header', $data);
		$this->load->view('day_headlines', $data);
		$this->load->view('controls', $data);
		$this->load->view('footer', $data);
	}
}
