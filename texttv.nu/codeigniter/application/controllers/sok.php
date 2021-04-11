<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Sok extends CI_Controller {

	public function index() {

		$data = array(
			"wrapclasses" => array("search"),
			"custom_page_title" => "Sök efter Text TV-sidor"
		);
		
		$query = trim( $this->input->get("q") );
		
		if ($query) {
			$data["custom_page_title"] = sprintf('Sökresultat för "%1$s"', html_escape($query));
		}

		$this->load->view("header", $data);
		
		
	
		$out = '
		<style>
			.wrap.search {
				margin-top: 52px;
			}
			#cse {
				text-align: left;	
			}
		</style>
		
		<h1>' . $data["custom_page_title"] . '</h1>
		
<div id="cse" style="width: 100%;">Loading</div>
<script src="https://www.google.com/jsapi" type="text/javascript"></script>
<script type="text/javascript"> 
  google.load("search", "1", {language : "sv", style : google.loader.themes.SHINY});
  google.setOnLoadCallback(function() {
    var customSearchOptions = {};
    var customSearchControl = new google.search.CustomSearchControl(
      "005986519605358956295:1kxcxeqyxga", customSearchOptions);
    customSearchControl.setResultSetSize(google.search.Search.FILTERED_CSE_RESULTSET);
    customSearchControl.draw("cse");
    customSearchControl.setLinkTarget("");
    customSearchControl.execute("' . html_escape( $query ) . '");
  }, true);
</script>
';
		$this->output->append_output($out);

		$this->load->view('controls', $data);
		$this->load->view("footer");

	}
}


