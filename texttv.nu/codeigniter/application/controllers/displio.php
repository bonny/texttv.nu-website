<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Displio extends CI_Controller {

	public function index() {

		//$this->visa("100,300,700");
		#$action = $this->input->get("action");
		$archive_page = new Texttv_page( 100 );
		
		$this->output->set_header("Content-type: text/html; charset=UTF-8");

		ob_start();
		?>
		<style>
			body {
				background: black;
			}
			body, h1, h2 {
				font-family: courier, monospace;
				font-size: 16px;
				line-height: 1;
			}
			
			body, h1, h2, a {
				color: white;
				font-weight: normal;
			}
			
			ul, li {
				list-style-type: none;
				margin: 0;
				padding: 0;
			}
			
			.root {
				white-space: pre-line;
			}
			
		</style>
		<?php
		$this->output->append_output( "<ul>" . ob_get_clean() . "</ul>" );
		
		$this->output->append_output( $archive_page->get_output() );
		
	}
	
}

