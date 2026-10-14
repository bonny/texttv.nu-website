<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Liten sitemap med bara det som är värt att indexera. Todo #16.
 *
 * Den gamla (avstängd 26 dec 2013, "för många anrop") listade varje rad i
 * texttv-tabellen, alltså varje version av varje sida, och laddade varje
 * sida med en egen query. Den här gör en enda indexerad fråga (senaste
 * versionen per sidnummer, för lastmod); dagssidorna räknas fram ur
 * datumet. Arkivets permalänkar är medvetet inte med (miljontals, mest
 * kortlivade nyheter).
 *
 * lastmod är den enda tidsuppgift Google använder (changefreq och priority
 * ignoreras), och bara om den stämmer. Därför: levande sidor får tiden för
 * senaste versionen, /igar-sidorna dagens midnatt (då de byter innehåll),
 * datumsidorna midnatt efter dagen (då de blev färdiga). De redaktionella
 * sidorna har ingen pålitlig ändringstid och får ingen lastmod.
 */
class Sitemap extends CI_Controller {

	/**
	 * De viktigaste levande sidorna. Urval efter sidfotens meny och de
	 * sidnummer som får mest söktrafik (GSC). Inte tipssidorna 550–571,
	 * som SVT slutat sända.
	 */
	const PAGES = [
		100, 101, 102, 103, 104, 105,
		300, 301, 302, 330, 336, 343, 344, 345, 346, 358, 359, 360,
		376, 377, 378, 379, 383,
		400, 401, 600, 700,
	];

	/** Redaktionella sidor. */
	const TEXT_PAGES = [
		"/sida/vanliga-fragor",
		"/sida/om-texttv-nu",
		"/sida/delat",
		"/sida/polisen",
		"/text-tv-fakta",
		"/svt-text-tv",
		"/blogg",
	];

	/** Hur många dagar bakåt datumsidorna listas. */
	const DAYS_BACK = 30;

	public function index()
	{
		$tz = new DateTimeZone("Europe/Stockholm");
		$today = new DateTime("today", $tz);

		// Senaste versionen per sidnummer. date_added sparas i svensk tid.
		$this->load->database();
		$latest = [];
		$rows = $this->db->query(
			"SELECT page_num, MAX(date_added) AS latest FROM texttv WHERE page_num IN (" . implode(",", self::PAGES) . ") GROUP BY page_num"
		)->result();
		foreach ($rows as $row) {
			$latest[(int) $row->page_num] = (new DateTime($row->latest, $tz))->format(DATE_W3C);
		}

		$urls = [];
		// Startsidan visar 100, 300, 401 och 101–105: senaste av dem.
		$start_parts = array_intersect_key($latest, array_flip([100, 101, 102, 103, 104, 105, 300, 401]));
		$urls[] = ["loc" => "/", "lastmod" => $start_parts ? max($start_parts) : null];

		foreach (self::PAGES as $num) {
			$urls[] = ["loc" => "/" . $num, "lastmod" => isset($latest[$num]) ? $latest[$num] : null];
		}

		$midnight = $today->format(DATE_W3C);
		$urls[] = ["loc" => "/nyheter/igar", "lastmod" => $midnight];
		$urls[] = ["loc" => "/sport/igar", "lastmod" => $midnight];
		$urls[] = ["loc" => "/377/igar", "lastmod" => $midnight];

		// Datumsidorna ändras inte när dagen är slut. Gårdagen finns redan
		// som /igar, så listan börjar i förrgår.
		$day = clone $today;
		$day->modify("-1 day");
		for ($i = 0; $i < self::DAYS_BACK; $i++) {
			$day->modify("-1 day");
			$ymd = $day->format("Y-m-d");
			$next = clone $day;
			$next->modify("+1 day");
			foreach (["nyheter", "sport", "377"] as $section) {
				$urls[] = ["loc" => "/{$section}/{$ymd}", "lastmod" => $next->format(DATE_W3C)];
			}
		}

		foreach (self::TEXT_PAGES as $path) {
			$urls[] = ["loc" => $path];
		}

		$out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ($urls as $url) {
			$out .= "\t<url><loc>https://texttv.nu" . htmlspecialchars($url["loc"], ENT_XML1) . "</loc>";
			if (!empty($url["lastmod"])) {
				$out .= "<lastmod>" . $url["lastmod"] . "</lastmod>";
			}
			$out .= "</url>\n";
		}
		$out .= "</urlset>\n";

		// lastmod för de levande sidorna ska inte bli mer än tio minuter gammal.
		$this->output->set_header("X-Accel-Expires: 600");
		$this->output->set_content_type("application/xml");
		$this->output->set_output($out);
	}
}
