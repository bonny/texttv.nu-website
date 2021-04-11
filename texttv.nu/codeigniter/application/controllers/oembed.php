<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Oembed callback for texttv.nu

Old format
http://api.texttv.nu/oembed/?maxwidth=620&maxheight=930&url=http%3A%2F%2Ftexttv.nu%2F149%2Farkiv%2F4-jun-2015-varlden-runt%2F7336730%2F

New format
http://api.texttv.nu/oembed/?maxwidth=620&maxheight=930&url=http://texttv.nu/129/tom-flygplats-plan-fick-vanda-8490933/&format=json
*/
class Oembed extends CI_Controller {

	public function index() {

		$url = $this->input->get("url");
		
		// just assume the last part of the url is the id
		$parts = explode("/", rtrim($url, "/"));
		
		$page_ids = end($parts);
		
		// What to return for this oembed:
		// - html with texttv content (not iframe because google)
		// - basic styles
			
		// En eller flera sidor från arkivet ska visas
		$arr_db_ids = explode(",", $page_ids);

		$arr_pages = array();
		$arr_db_real_ids = [];
		foreach ($arr_db_ids as $one_db_id) {
			
			// echo "one_db_id: $one_db_id";
			preg_match("!\d+!", $one_db_id, $matches);
		
			// om inte ett nummer: abort!
			if ( empty( $matches ) ) {
				die("There was an error, ey!");
			}
			#print_r($matches);
			
			$one_db_id = $matches[0];
			$arr_db_real_ids[] = $one_db_id;

			
			$page = new texttv_page();
			$page->id = $one_db_id;
			$page->load(TRUE);
			$arr_pages[] = $page;

			// Mark page as shared in db
			#$sql_set_shared = sprintf('UPDATE texttv SET is_shared = 1 WHERE id IN (%s)', $one_db_id);
			#$this->db->query($sql_set_shared);

		}

		mark_archive_ids_as_shared($arr_db_real_ids);
		
		// merge together html from all pages
		$html = "";
		
		$html .= '
			<style>
			
				.texttvnu-oembed-wrap {
					margin: 0;
					padding: 0;
				}
							
				.texttvnu-oembed,
				.texttvnu-oembed-subpages {
					list-style-type: none;
					margin: 0;
					padding: 0;
				}
				
				.texttvnu-oembed,
				.texttvnu-oembed h1,
				.texttvnu-oembed span {
					font-family: menlo, courier;
					font-size: 1em;
				}
				
				.texttvnu-oembed .root {
					white-space: pre;
				}
				
				.texttvnu-oembed h1 {
					display: inline;
				}
				
				.texttvnu-oembed-permalink {
					text-align: center;
				}
				
				.texttvnu-oembed pre {
					word-wrap: initial;
				}
				
			</style>
		';
		
		
		$html .= "<div class='texttvnu-oembed-wrap'>";
			
		$html .= "<ul class='texttvnu-oembed'>";
		foreach ($arr_pages as $page) {
			$page_subpages_html = "<ul class='texttvnu-oembed-subpages'>";
			foreach ($page->arr_contents as $one_subpage) {
				$page_subpages_html .= "<li><pre>{$one_subpage}</pre></li>";
			}
			$html .= sprintf('<li>%1$s</li>', $page_subpages_html);
			$page_subpages_html = "</ul>";
		}
		$html .= "</ul>";

		$html .= "</div>";
		
		// fix relative links so they point to texttv.nu
		// href="/104"
		$html = str_replace('<a href="/', '<a href="http://texttv.nu/', $html);
		
		$html .= sprintf('<p class="texttvnu-oembed-permalink"><a href="%1$s">Texten kommer från SVT Text via texttv.nu</a></p>', $url);
		
		$json = array(
			"type" => "rich",
			"version" => "1.0",
			"title" => $arr_pages[0]->title,
			"author_name" => "texttv.nu",
			"author_url" => "http://texttv.nu/",
			"provider_name" => "texttv.nu",
			"provider_url" => "http://texttv.nu/",
			"html" => $html,
			"width" => 100,
			"height" => 100
			// thumbnail_url
			// thumbnail_width
			// thumbnail_height
			
		);

		// $json["arr_pages"] = $arr_pages;
				
		header('Content-Type: application/json');
		echo json_encode( $json );
		
		/*$data = array();
		$data["page"] = $page;
		$data["pages"] = $arr_pages;
		$data["pagenum"] = $page_num;
		$data["is_archive"] = TRUE;
		$data["page_permalink"] = $page->get_permalink();*/
		
		#$this->load->view('header', $data);
		#$this->load->view('pages_inner_output_archive', $data);
		#$this->load->view('controls', $data);
		#$this->load->view('footer', $data);		
			

		exit;

	}
	
}

