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
			"live_label" => "Dagens nyheter",
			"most_read" => "news",
			"groups" => [
				["Inrikes", 106, 129],
				["Utrikes", 130, 149],
				// 190–199 är SVT:s servicesidor (bemanning, sidindex m.m.).
				["Kultur och övrigt", 150, 189],
			],
			"exclude" => [127, 128, 148, 150, 188],
			"results" => [],
			"other" => "sport",
		],
		"sport" => [
			"name" => "Sportnyheter",
			"yesterday" => "Gårdagens sportnyheter",
			"live_url" => "/300",
			"live_label" => "Dagens sport 300",
			"most_read" => "sport",
			"groups" => [
				["Sport", 303, 329],
			],
			"exclude" => [],
			"results" => [
				377 => "Målservice",
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

	/**
	 * /nyheter och /sport utan datum: användare kortar URL:en. Skicka till
	 * gårdagens lista i stället för en 404.
	 */
	public function avdelning($section = "")
	{
		if (!isset(self::SECTIONS[$section])) {
			show_texttv_404();
			return;
		}
		redirect("/{$section}/igar", "location", 302);
	}

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

		// Sätt ihop dagens nyheter. Rådatan är en rad per exakt rubrik, men
		// samma nyhet förekommer i flera varianter (rättat stavfel, ändrad
		// siffra, flyttad till annan sida), och vid midnatt får varje sida en
		// ny version eftersom datumraden ändras. Därför:
		//  - brus bort (för korta rubriker, testsidor)
		//  - nyheter som sändes redan dagen innan räknas inte (kvarliggare)
		//  - snarlika rubriker slås ihop till en nyhet
		$prev_day = clone $date;
		$prev_day->modify("-1 day");
		$prev_keys = [];
		foreach (get_headlines_for_day($first_num, $last_num, $conf["exclude"], $prev_day->format("Y-m-d")) as $row) {
			$prev_keys[$this->_story_key($row->title)] = true;
		}

		$stories = [];
		// Äldst först, så att den första sändningen blir nyhetens tid.
		foreach (array_reverse($rows) as $row) {
			$title = trim(preg_replace('/\s+/u', ' ', $row->title));
			if ($this->_is_noise($title)) {
				continue;
			}
			$key = $this->_story_key($title);
			// Exakt samma rubrik som dagen innan = kvarliggare. Inte ungefärlig
			// likhet här: "Sverige till final" dagen efter "… semifinal" är en
			// ny nyhet och får inte försvinna.
			if (isset($prev_keys[$key])) {
				continue;
			}
			$merged = false;
			foreach ($stories as &$story) {
				if ($this->_same_story($key, $story["key"], (int) $row->page_num === $story["page_num"])) {
					// Behåll första tiden men senaste rubrik och version.
					if ((int) $row->id > $story["id"]) {
						$story["title"] = $title;
						$story["id"] = (int) $row->id;
						$story["page_num"] = (int) $row->page_num;
					}
					$story["keys"][] = $key;
					$merged = true;
					break;
				}
			}
			unset($story);
			if (!$merged) {
				$stories[] = [
					"key" => $key,
					"keys" => [$key],
					"title" => $title,
					"id" => (int) $row->id,
					"page_num" => (int) $row->page_num,
					"time" => substr($row->first_added, 11, 5),
				];
			}
		}

		if (empty($stories)) {
			show_texttv_404();
			return;
		}

		// Ett återanvänt objekt räcker för permalänkarna.
		$link_page = new texttv_page();
		$groups = [];
		foreach ($conf["groups"] as $group) {
			$groups[$group[0]] = [];
		}
		$items_by_key = [];
		foreach (array_reverse($stories) as $story) {
			$link_page->id = $story["id"];
			$link_page->num = $story["page_num"];
			$link_page->title = $story["title"];
			$item = [
				"title" => $story["title"],
				"time" => $story["time"],
				"page_num" => $story["page_num"],
				"permalink" => $link_page->get_permalink(),
			];
			foreach ($story["keys"] as $one_key) {
				$items_by_key[$one_key] = $item;
			}
			foreach ($conf["groups"] as $group) {
				if ($item["page_num"] >= $group[1] && $item["page_num"] <= $group[2]) {
					$groups[$group[0]][] = $item;
					break;
				}
			}
		}
		$groups = array_filter($groups);
		$headline_count = sizeof($stories);

		// Mest lästa, bara där cachen finns (se MOST_READ_DAYS_BACK). Varje
		// post matchas mot dagens lista, så att tid, sidnummer och länk är
		// desamma i båda. Mest lästa nyheter från dagen innan (kvarliggare)
		// hoppas över, eftersom de hör till den dagens lista.
		$most_read = [];
		$most_read_oldest = clone $today;
		$most_read_oldest->modify("-" . self::MOST_READ_DAYS_BACK . " days");
		if ($date >= $most_read_oldest) {
			list($most_read_pages) = most_read_pages_cached($conf["most_read"], strtotime($day_ymd . " 12:00"));
			$seen = [];
			foreach ($most_read_pages as $one) {
				$key = $this->_story_key($one["title"]);
				$item = isset($items_by_key[$key]) ? $items_by_key[$key] : null;
				if (!$item) {
					foreach ($items_by_key as $other_key => $other_item) {
						if ($this->_same_story($key, $other_key, false)) {
							$item = $other_item;
							break;
						}
					}
				}
				if (!$item || isset($seen[$item["permalink"]])) {
					continue;
				}
				$seen[$item["permalink"]] = true;
				$most_read[] = $item;
				if (sizeof($most_read) >= 10) {
					break;
				}
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
		$yesterday = clone $today;
		$yesterday->modify("-1 day");

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
				"label" => $is_yesterday_url ? "Gårdagens resultat" : "Resultat",
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
			"next_url" => $next_date == $today ? $conf["live_url"] : ($next_date == $yesterday ? "/{$section}/igar" : "/{$section}/" . $next_date->format("Y-m-d")),
			"next_human" => $next_date == $today ? $conf["live_label"] : $human($next_date, "l j F"),
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

	/**
	 * Jämförelsenyckel för en rubrik: gemener, bara bokstäver och siffror.
	 * "S: Saknas möjlighet" och "S:Saknas möjlighet" blir samma nyckel.
	 */
	private function _story_key($title)
	{
		return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower((string) $title));
	}

	/**
	 * Om två nycklar är samma nyhet.
	 *
	 * På samma sidnummer samma dag är en snarlik rubrik nästan alltid samma
	 * nyhet som uppdaterats ("Två/Tre döda i …", rättade stavfel), så där
	 * räcker 85 % likhet. Mellan olika sidor krävs 94 %: där finns olika
	 * nyheter som skiljer sig med ett ord ("Riksbanken sänker/höjer räntan"
	 * 85 %, "Tre/Två döda i rysk attack" 92 %), medan samma nyhet på två
	 * sidor ligger högre ("Bergand vann - /och sedan …" 95 %). Testat på
	 * riktiga och konstruerade par 2026-10-14.
	 */
	private function _same_story($a, $b, $same_page)
	{
		if ($a === $b) {
			return true;
		}
		if (abs(strlen($a) - strlen($b)) > 12) {
			return false;
		}
		similar_text($a, $b, $percent);
		return $percent >= ($same_page ? 85 : 94);
	}

	/**
	 * Rubriker som inte är nyheter: SVT:s testsidor ("Test 12 okt 2026") och
	 * sidrubriker på resultatsidorna ("Ishockey NHL"). Korta riktiga rubriker
	 * som "Påven död" ska vara kvar, så bara korta rubriker som börjar med en
	 * sport räknas som sidrubrik.
	 */
	private function _is_noise($title)
	{
		if (preg_match('/^test\b/iu', $title)) {
			return true;
		}
		$words = preg_split('/\s+/u', trim($title));
		return sizeof($words) <= 2
			&& preg_match('/^(ishockey|fotboll|handboll|bandy|basket|innebandy|tennis|golf|motor|trav)$/iu', $words[0]);
	}
}
