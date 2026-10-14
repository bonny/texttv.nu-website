<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Liten sitemap med bara det som är värt att indexera. Todo #16.
 *
 * Den gamla (avstängd 26 dec 2013, "för många anrop") listade varje rad i
 * texttv-tabellen, alltså varje version av varje sida, och laddade varje
 * sida med en egen query. Den här gör ingen databasfråga alls: sidnumren är
 * fasta och dagssidorna räknas fram ur datumet. Arkivets permalänkar är
 * medvetet inte med (miljontals, mest kortlivade nyheter).
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
		$urls = [];
		$urls[] = ["loc" => "/"];

		foreach (self::PAGES as $num) {
			$urls[] = ["loc" => "/" . $num];
		}

		$urls[] = ["loc" => "/nyheter/igar"];
		$urls[] = ["loc" => "/sport/igar"];
		$urls[] = ["loc" => "/377/igar"];

		// Datumsidorna ändras inte när dagen är slut. Gårdagen finns redan
		// som /igar, så listan börjar i förrgår.
		$day = new DateTime("today", new DateTimeZone("Europe/Stockholm"));
		$day->modify("-1 day");
		for ($i = 0; $i < self::DAYS_BACK; $i++) {
			$day->modify("-1 day");
			$ymd = $day->format("Y-m-d");
			$next = clone $day;
			$next->modify("+1 day");
			foreach (["nyheter", "sport", "377"] as $section) {
				$urls[] = ["loc" => "/{$section}/{$ymd}", "lastmod" => $next->format("Y-m-d")];
			}
		}

		foreach (self::TEXT_PAGES as $path) {
			$urls[] = ["loc" => $path];
		}

		$out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ($urls as $url) {
			$out .= "\t<url><loc>https://texttv.nu" . htmlspecialchars($url["loc"], ENT_XML1) . "</loc>";
			if (isset($url["lastmod"])) {
				$out .= "<lastmod>" . $url["lastmod"] . "</lastmod>";
			}
			$out .= "</url>\n";
		}
		$out .= "</urlset>\n";

		// Innehållet byts en gång per dygn (datumlistan), så en timme räcker.
		$this->output->set_header("X-Accel-Expires: 3600");
		$this->output->set_content_type("application/xml");
		$this->output->set_output($out);
	}
}
