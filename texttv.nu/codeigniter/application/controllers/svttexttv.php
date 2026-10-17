<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class svttexttv extends CI_Controller {

	public function index() {

		// Ram och sidomeny som på resten av sajten (todo #18).
		$data = array(
			"wrapclasses" => array("svt-text-tv"),
		);

		$data["custom_page_title"] = "SVT Text TV";
		
		// $this->load->view("delade");

		$this->load->view("header", $data);
		$this->load->view("svt-text-tv", $data);
		$this->load->view('controls', $data);
		$this->load->view("footer");	

	}
		
}

