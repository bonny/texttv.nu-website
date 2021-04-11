<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
http://texttv.nu/codeigniter/index.php/test
*/
class Test extends CI_Controller {

	public function index()
	{
		//$this->load->library('textpage');
		echo "<br>start";
		$page = new texttv_page(100);
		
		foreach ($page->arr_contents as $one_page) {
			echo $one_page;
		}

	}


}

