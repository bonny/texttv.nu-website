<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class svttexttv extends CI_Controller {

	public function index() {

		$data = array(
			"wrapclasses" => array("svt-text-tv"),
			"disableSidebar" => true
		);

		$data["custom_page_title"] = "SVT Text TV";
		
		// $this->load->view("delade");

		$this->load->view("header", $data);
		$this->load->view("svt-text-tv", $data);
		// $this->load->view('controls', $data);
		$this->load->view("footer");	

	}
		
}

