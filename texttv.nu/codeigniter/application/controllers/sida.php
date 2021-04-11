<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Controller for most of the pages
http://texttv.nu/codeigniter/index.php/test
*/
class Sida extends CI_Controller {

	// Om ingen sida anges går vi till 100
	// Update 3 april 2015: vi visar över sporten, 300, pga. det är säkert bra
	public function index() {

		$this->visa("100,300", "startpage");

	}

	/**
	 * Visa en äldre version av en sida
	 * Aka TextTV-TimeMachine!
	 * Visa dock inte alla = får timeout, visa senaste dagarna typ bara
	 */
	function arkiv($page_num, $title = NULL, $db_id = NULL) {

		// Om bara page_num = visa översikt över alla arkiv som finns
		if (empty($db_id) && empty($title)) {
			
			// 28 juni: inaktiverar denna, känns inte som bidrar till något
			show_404();
			
			$out = "<div class='archive'>";

			$sql = sprintf('SELECT id FROM texttv WHERE page_num = %1$d ORDER BY date_updated DESC LIMIT 10', $page_num);
			$res = $this->db->query($sql);
			if (!$res->num_rows) {
				
				$out .= sprintf("<p>Sidan %d har inga arkiverade sidor.", $page_num);
				
			} else {

				$out .= sprintf('<p><a href="%3$s">Text-TV %2$d</a> har %1$d arkiverade sidor:', $res->num_rows, $page_num, site_url("" . $page_num));
				$out .= "<ul>";
				$firstpage = NULL;
				$loopNum = 0;
				foreach ($res->result() as $row) {

					$page = new texttv_page();
					$page->id = $row->id;
					$page->load(TRUE);
					
					if ($loopNum == 0) {
						$firstpage = $page;
					}
					
					$page_title = $page->get_page_title();
					
					$out .= sprintf('
						<li>
							<a href="%3$s">%1$s: %2$s</a></li>
						',
						strftime("%H:%M %a %e %b %Y", $page->date_updated_unix), 
						$page_title,
						$page->get_permalink()
					);
					
					$loopNum++;
					
				}
				$out .= "</ul>";
				
			}
			
			$out .= "</div>";
			
			$data = array(
				"page" 					=> $firstpage,
				"is_archive_overview" 	=> TRUE
			);
			$this->load->view('header', $data);
			$this->output->append_output($out);
			$this->load->view('controls', $data);
			$this->load->view('footer', $data);

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

			if ( $load_ok ) {

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
				
				// not load ok
				
				$this->output->set_status_header('404');
				
				$data = [];
				$data["custom_page_title"] = "Sidan hittades inte (felkod 404)";
				
				$this->load->view('header', $data);
				$this->load->view('404', $data);
				$this->load->view('pages-latest-updated', $data);
				$this->load->view('controls', $data);
				$this->load->view('footer', $data);
				
			}
			
		}

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
			$this->output->append_output( sprintf("<p>Fel: '%s' är inte en giltig sida.", htmlspecialchars($pagenum)) );
			
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
		
		if ( $load_ok ) {

			$data = [];
			$data["page"] = $page;
			$data["pages"] = $arr_pages;
			$data["pagenum"] = $page_num;
			$data["is_archive"] = TRUE;
			$data["page_permalink"] = $page->get_permalink();
			
			$this->load->view('amp', $data);
			/*
			$this->load->view('header', $data);
			$this->load->view('pages_inner_output_archive', $data);
			$this->load->view('pages-latest-updated', $data);
			$this->load->view('controls', $data);
			$this->load->view('footer', $data);
			*/

		} else {				
			// page not loaded ok
			$this->output->set_status_header('404');
			$data = [];
			$data["custom_page_title"] = "Sidan hittades inte (felkod 404)";
			
			$this->load->view('header', $data);
			$this->load->view('404', $data);
			$this->load->view('pages-latest-updated', $data);
			$this->load->view('controls', $data);
			$this->load->view('footer', $data);

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
			$this->output->append_output( sprintf("<p>Fel: '%s' är inte en giltig sida.", htmlspecialchars($pagenum)) );
			
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

