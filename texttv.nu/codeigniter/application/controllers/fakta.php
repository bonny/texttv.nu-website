<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class fakta extends CI_Controller {

	public function index() {
		$this->sida('start');

	}
	
	public function sida($slug) {

		$sql = sprintf('SELECT title FROM texttv_page_text WHERE pagedescription=%1$s LIMIT 1', $this->db->escape( "fakta-{$slug}"));
		$res = $this->db->query($sql);
		$row = $res->row();
	
		// Get title and content from db		
		$data["custom_page_title"] = $row->title;
		$data["slug"] = $slug;
	
		$query = $this->db->query($sql);
			
		$this->load->view("header", $data);
		
		$this->load->view("fakta", $data);

		$this->load->view('controls', $data);
		$this->load->view("footer");
		
	}
	
}
