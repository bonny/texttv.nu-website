<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Controller for app embed.
 * URL is like: /appembed/100,300
 * http://texttv.nu/codeigniter/index.php/test
 */
class Appembed extends CI_Controller {
	public function index() {
		$this->visa("100,300", "startpage");
	}

	// Tar emot $pagenum som en eller flera sidor
	function visa($pagenum = NULL, $pagedescription = NULL) {
		$arr_pages = texttv_page::extract_pages_from_ranges($pagenum);

		// Om inga sidor är nåt knas, så då fortsätter vi inte.
		if (empty($arr_pages)) {
			$this->output->set_status_header('400');
			$this->output->set_header("Content-Type: text/html; charset=utf-8");
			$this->output->append_output(sprintf("<p>Fel: '%s' är inte en giltig sida.", htmlspecialchars($pagenum)));
			return;
		}

		// Add all pages to one array of texttv_page-objects.
		$arr_page_objs = array_map(function($one_page) {
			return new texttv_page($one_page);
		}, $arr_pages);

		// Skapa array med data att skicka med till vyerna
		$data = array();
		$data["page"] = $arr_page_objs[0];
		$data["pages"] = $arr_page_objs;
		$data["pagenum"] = $pagenum;
		$data["pagedescription"] = $pagedescription;

		// Ladda vyer för sidhuvud och kontroller
		// $this->load->view('header', $data);
		// $this->load->view("pages_updated_container", $data);
		// $this->load->view("breadcrumbs", $data);
		// $this->load->view("pages_current_page_top", $data);
		// $this->load->view("pages_inner_output_current", $data);
		$this->load->view("appembed/pagerange.php", $data);
		// $this->load->view('pages-latest-updated', $data);
		// $this->load->view('controls', $data);
		// $this->load->view('page_text', $data);
		// $this->load->view('footer', $data);
	}

}
