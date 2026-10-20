<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Textsida extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		//$this->load->view('welcome_message');
		//echo "hejsan";
	}
	
	public function visa($sidnamn, $third_level = "") {
			
		$data = array(
			"wrapclasses" => array("textsida"),
			"third_level" => $third_level,
		);
		// Canonical utan frågesträng, så att t.ex. ?utm_source= inte blir en
		// egen sida (todo #19). Inte för /sida/delat, där ?datum= ger olika
		// listor.
		$canonical = "https://texttv.nu/sida/";
		
		switch ($sidnamn) {
			
			case "om-texttv-nu":
				$data["custom_page_title"] = "Om TextTV.nu";
				$data["custom_canonical"] = $canonical . "om-texttv-nu";
				$data["custom_meta_description"] = "TextTV.nu är SVT Text TV i en snabbare och tydligare form för mobil, surfplatta och dator, med appar för iPhone och Android. Ett fristående projekt, inte från SVT.";
				$this->load->view("header", $data);
				$this->load->view("textsida-om-texttv-nu");
				break;

			case "vanliga-fragor":
				$data["custom_page_title"] = "Vanliga frågor om Text TV";
				$data["custom_canonical"] = $canonical . "vanliga-fragor";
				$data["custom_meta_description"] = "Svar på vanliga frågor om Text TV och TextTV.nu: vilka sidor som finns, var du hittar resultat och målservice, och hur appen fungerar.";
				$this->load->view("header", $data);
				$this->load->view("textsida-vanliga-fragor");
				break;

			case "blogg":
				$this->load->view("header", $data);
				$this->load->view("textsida-blogg");
				break;

			case "delat":

				// Formuläret skickade förut till /sida/delat/?datum=…, och gamla
				// länkar har /sida/delat/2015-08-24. Botar hämtar dem fortfarande
				// (cirka 1 200 om dagen i oktober 2026), så en adress per dag.
				if (parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) !== "/sida/delat") {
					$datum = $this->input->get("datum") ?: $third_level;
					redirect("/sida/delat" . ($datum ? "?datum=" . rawurlencode($datum) : ""), "location", 301);
					return;
				}

				// En dag: ?datum=2015-08-24 eller /sida/delat/2015-08-24. Annat än
				// ett riktigt datum mellan första mätdagen och idag ger 404; förut
				// blev det "torsdag 1 januari 1970" och en tom lista.
				$datum = $this->input->get("datum") ?: $third_level;
				if ($datum) {
					$d = DateTime::createFromFormat("!Y-m-d", (string) $datum);
					if (!$d || $d->format("Y-m-d") !== $datum || $datum < DELAT_FORSTA_DAG || $datum > date("Y-m-d")) {
						show_texttv_404();
						return;
					}
					$data["delat_datum"] = $datum;
					$data["custom_page_title"] = "Mest delat " . date_sv("l j F Y", strtotime($datum)) . " - SVT Text TV";
					$data["custom_meta_description"] = "De mest delade nyheterna från SVT Text TV " . date_sv("l j F Y", strtotime($datum)) . ", delade från texttv.nu och TextTV.nu-appen.";
					$data["custom_canonical"] = $canonical . "delat?datum=" . $datum;
				} else {
					$data["delat_datum"] = null;
					$data["custom_page_title"] = "Mest delade text-tv-sidorna";
					$data["custom_meta_description"] = "De mest delade nyheterna från SVT Text TV idag och igår, och för varje dag sedan 2015.";
					$data["custom_canonical"] = $canonical . "delat";
				}

				$this->load->view("header", $data);
				$this->load->view("delade");
				break;

			case "senast-uppdaterat":
				// Inga undersidor, som /sida/delat/inte-ett-datum.
				if ($third_level !== "") {
					show_texttv_404();
					return;
				}
				$data["custom_page_title"] = "Senast uppdaterat på SVT Text TV";
				$data["custom_meta_description"] = "Nyheterna och sportsidorna på SVT Text TV som ändrats senast, med tid och sidnummer.";
				$data["custom_canonical"] = $canonical . "senast-uppdaterat";
				$this->load->view("header", $data);
				$this->load->view("senast-uppdaterat");
				break;

			case "polisen":

				// Flödet innehåller sedan 2026-10-18 även Brottsplatskartans
				// AI-matchningar (todo #21). En halvtimmes cache: frågan är tung
				// hos Brottsplatskartan när dess cache är kall.
				$eventsInMedia = bpk_hamta_json('https://brottsplatskartan.se/api/eventsInMedia?media=texttv&limit=50&page=1', 'events-in-media', 1800, 120);
				// Vyn läser objekt (->data, ->permalink).
				$eventsInMedia = json_decode(json_encode($eventsInMedia ?: new stdClass()));

				$data['events_in_media'] = $eventsInMedia;
				$data["custom_page_title"] = "Polishändelser i Text TV";
				$data["custom_meta_description"] = "Nyheter på SVT Text TV om händelser som Polisen har rapporterat, med länk till händelsen på kartan hos Brottsplatskartan.";
				$data["custom_canonical"] = $canonical . "polisen";
								
				$this->load->view("header", $data);
				$this->load->view("polisen");
				break;

			case "cookies":				
				$data["custom_page_title"] = "Om kakor";
				$data["custom_canonical"] = $canonical . "cookies";
				$this->load->view("header", $data);
				$this->load->view("textsida-cookies");
				break;

			case "integritetspolicy":
			case "integritet":
				$data["custom_page_title"] = "Integritetspolicy";
				// /sida/integritet är samma sida.
				$data["custom_canonical"] = $canonical . "integritetspolicy";
				$this->load->view("header", $data);
				$this->load->view("textsida-integritetspolicy");
				break;		
				
			default:
				// Gav tidigare 404-texten med status 200.
				show_texttv_404();
				return;
		}

		$this->load->view('controls', $data);
		$this->load->view("footer");
		
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */