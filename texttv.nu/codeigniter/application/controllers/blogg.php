<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blogg extends CI_Controller {

	public function index()
	{
		//$this->load->view('welcome_message');
		// Visa översikt över alla blogginlägg
		$result = $this->db->query("SELECT id, date_published, UNIX_TIMESTAMP(date_published) AS date_published_unix, permalink, title, content FROM texttv_blogg ORDER BY date_published DESC");

		$data = array(
			"wrapclasses" 	=> array("textsida"),
			"blogg_entries" => $result
		);
		
		$data["custom_page_title"] = "Utvecklingsblogg - TextTV nu";

		$this->load->view("header", $data);
		$this->load->view("blogg_overview", $data);
		$this->load->view('controls', $data);
		$this->load->view("footer", $data);

	}
	
	public function visa($permalink) {
		
		// Leta upp inlägg med denna permalink
		$query = sprintf('
				SELECT id, date_published, UNIX_TIMESTAMP(date_published) AS date_published_unix, permalink, title, content 
				FROM texttv_blogg
				WHERE permalink = "%1$s"',
				mysqli_real_escape_string($this->db->conn_id, $permalink)
			);
		$result = $this->db->query($query);
		//var_dump($this->db->conn_id);
		// mysqli_real_escape_string
		if ($result->num_rows() == 0) {
			die("Doh! Kunde inte hitta något blogginlägg med denna adress.");
		}
	
		$data = array(
			"wrapclasses" => array("textsida"),
			"blogg_entries" => $result,
			"custom_page_title" => $result->row()->title . " | Text TV Utvecklingsblogg"
		);
		
		$this->load->view("header", $data);
		$this->load->view("blogg_overview", $data);
		$this->load->view('controls', $data);
		$this->load->view("footer");
		
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */