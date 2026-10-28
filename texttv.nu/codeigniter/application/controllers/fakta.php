<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * /text-tv-fakta och dess undersidor.
 *
 * Texterna låg i texttv_page_text och var mest skisser ("världen",
 * "Teknik..."). Sedan 2026-10-28 finns fakta och historik med källor på
 * /sida/vanliga-fragor, så varje gammal adress skickas dit, till det svar
 * som passar bäst. Raderna i databasen är orörda.
 */
class fakta extends CI_Controller {

	const MAL = [
		"start" => "",
		"text-tv-i-sverige" => "#nar-kom-text-tv-till-sverige",
		"text-tv-i-varlden" => "#vilket-%C3%A5r-kom-text-tv",
		"tekniken-bakom-text-tv" => "#vad-%C3%A4r-text-tv",
		"kandisar-som-gillar-text-tv" => "",
		"statistik" => "",
	];

	public function index() {
		$this->sida('start');
	}

	public function sida($slug) {
		if ( ! array_key_exists($slug, self::MAL) ) {
			show_texttv_404();
			return;
		}

		redirect("/sida/vanliga-fragor" . self::MAL[$slug], "location", 301);
	}

}
