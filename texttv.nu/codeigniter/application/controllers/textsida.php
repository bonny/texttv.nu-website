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
			"third_level" => $third_level
		);
		
		switch ($sidnamn) {
			
			case "om-texttv-nu":
				$data["custom_page_title"] = "Om TextTV.nu";
				$this->load->view("header", $data);
				$this->load->view("textsida-om-texttv-nu");
				break;

			case "vanliga-fragor":
				$data["custom_page_title"] = "Vanliga frågor om Text TV";
				$this->load->view("header", $data);
				$this->load->view("textsida-vanliga-fragor");
				break;

			case "vi-rekommenderar":
				$data["custom_page_title"] = "Sidor och sajter vi rekommenderar";
				$this->load->view("header", $data);
				$this->load->view("textsida-vi-rekommenderar");
				break;

			case "blogg":
				$this->load->view("header", $data);
				$this->load->view("textsida-blogg");
				break;

			case "delat":
				
				$data["custom_page_title"] = "Mest delade text-tv-sidorna";
				
				if ( $this->input->get("datum") ) {
					$data["custom_page_title"] = "Mest delat " . strftime("%A %e %B %G", strtotime($this->input->get("datum")));
				}
				
				$this->load->view("header", $data);
				$this->load->view("delade");
				break;

			case "polisen":

				$this->load->driver('cache');
				
				// Hämta ev. cachade händelser.
				$cache_key = 'polisen_media_events';
				$eventsInMedia = $this->cache->file->get($cache_key);
				
				if (!$eventsInMedia) {
					// Inga cachade händelser hittades, så hämta på nytt.
					$eventsInMediaURL = 'https://brottsplatskartan.se/api/eventsInMedia?media=texttv&limit=50&page=1';
					$eventsInMedia = file_get_contents($eventsInMediaURL);
					$eventsInMedia = json_decode($eventsInMedia);
					
					// Cache i n minuter
					$cache_ttl = 60 * 5;
					$this->cache->file->save($cache_key, $eventsInMedia, $cache_ttl);
				}
				
				$data['events_in_media'] = $eventsInMedia;
				$data["custom_page_title"] = "Polishändelser som det skrivs om på Text TV";
								
				$this->load->view("header", $data);
				$this->load->view("polisen");
				break;

			case "cookies":				
				$data["custom_page_title"] = "Om kakor";
				$this->load->view("header", $data);
				$this->load->view("textsida-cookies");
				break;

			case "integritetspolicy":
			case "integritet":
				$data["custom_page_title"] = "Integritetspolicy";
				$this->load->view("header", $data);
				$this->load->view("textsida-integritetspolicy");
				break;		
				
			default:
				$this->load->view("header", $data);
				$this->load->view("404");
				break;			
		}

		$this->load->view('controls', $data);
		$this->load->view("footer");
		
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */