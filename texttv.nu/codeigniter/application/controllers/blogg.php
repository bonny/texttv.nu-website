<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Blogg extends CI_Controller {

	public function index() {
		//$this->load->view('welcome_message');
		// Visa översikt över alla blogginlägg
		$result = $this->db->query("SELECT id, date_published, UNIX_TIMESTAMP(date_published) AS date_published_unix, permalink, title, content FROM texttv_blogg ORDER BY date_published DESC");

		$data = array(
			"wrapclasses" 	=> array("textsida"),
			"blogg_entries" => $result
		);

		$data["custom_page_title"] = "Utvecklingsblogg - TextTV nu";
		$data["custom_meta_description"] = "Utvecklingsbloggen för TextTV.nu: nya funktioner på sajten och i apparna, och hur tjänsten byggs.";
		$data["custom_canonical"] = "https://texttv.nu/blogg";

		$this->load->view("header", $data);
		$this->load->view("blogg_overview", $data);
		$this->load->view('controls', $data);
		$this->load->view("footer", $data);
	}

	public function visa($permalink) {

		// Leta upp inlägg med denna permalink
		//
		// $this->db->escape() i stället för mysqli_real_escape_string() direkt:
		// den senare tar conn_id och går sönder tyst om drivrutinen byts, och
		// kringgår CI:s egen escaping. escape() sätter citattecknen själv, därav
		// inga runt %1$s. Se L4 i todos/08-sakerhetsgranskning-2026-08-01.md.
		$query = sprintf(
			'
				SELECT id, date_published, UNIX_TIMESTAMP(date_published) AS date_published_unix, permalink, title, content
				FROM texttv_blogg
				WHERE permalink = %1$s',
			$this->db->escape($permalink)
		);
		$result = $this->db->query($query);

		if ($result->num_rows() == 0) {
			show_texttv_404();
			return;
		}

		// Hämta senaste bloggposterna.
		$latest_posts = $this->db->query(
			"
				SELECT id, date_published, UNIX_TIMESTAMP(date_published) AS date_published_unix, permalink, title, content 
				FROM texttv_blogg 
				ORDER BY date_published 
				DESC LIMIT 999
			");

		$data = array(
			"wrapclasses" => array("textsida"),
			"blogg_entries" => $result,
			"prev_blog_posts" => $latest_posts,
			"custom_page_title" => $result->row()->title . " | Text TV Utvecklingsblogg",
			// Början av inlägget, utan HTML (todo #19).
			"custom_meta_description" => blogg_utdrag($result->row()->content, 155, $result->row()->title),
			"custom_canonical" => "https://texttv.nu/blogg/" . rawurlencode($result->row()->permalink),
		);

		$this->load->view("header", $data);
		$this->load->view("blogg_overview", $data);
		$this->load->view("blogg_prev_posts_nav", $data);
		$this->load->view('controls', $data);
		$this->load->view("footer");
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */