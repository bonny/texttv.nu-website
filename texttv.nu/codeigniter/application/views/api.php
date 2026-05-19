<?php

/**
 * Api
 */

switch ($api_call) {

	case "get":

		// Output as json
		$this->output->set_content_type("application/json");
		$out = "";
		$used_cached_values = false;

		// Generate unique cache key
		//$apc_key = "texttv_api_get_" . md5($page_num);
		// $cache_ttl = 10;

		// Check if api call exists in cache
		//if (false !== $arr_pages_json = apc_fetch($apc_key)) {

			// $out .= "\ncached value did exist";
			// Use cached value
			// $out = $cached_value;
			//echo "<!-- using cached value -->";
			//$used_cached_values = true;
			//error_log("api get: cached value");

		//} else {

			// error_log("api get: NOT cached value");
			// Cached API response did not exist

			// $out .= "\ncached value did not exist";

			// Create array with all the pages we want to get
			$arr_pages = texttv_page::extract_pages_from_ranges($page_num);

			// Samla ihop alla sidor i en array för json-output
			$arr_pages_json = array();
			foreach ($arr_pages as $one_page) {
				$page = new texttv_page($one_page);
				
				$content = $page->get_content_with_fixes();
				$page_content_plain = [];
				$include_content_plain = $this->input->get("includePlainTextContent");
				
				if ($include_content_plain) {
					foreach ( $content as $one_sub_page ) {
						$sub_page_content_plain = strip_tags($one_sub_page);
						$sub_page_content_plain = trim($sub_page_content_plain);
						$page_content_plain[] = $sub_page_content_plain;
					}
				}
				
				$return_arr = array(
					"num" => $page->num,
					"title" => $page->title,
					"content" => $content,
					"content_plain" => $page_content_plain,
					"next_page" => $page->next_page,
					"prev_page" => $page->prev_page,
					"date_updated_unix" => $page->date_updated_unix,
					"permalink" => $page->get_permalink(true),
					"id" => $page->id,
					"breadcrumbs" => $page->get_breadcrumbs()
				);
				
				if ($include_content_plain) {
					$return_arr["content_plain"] = $page_content_plain;
				}
				
				$arr_pages_json[] = $return_arr;
			}

			// Store in cache
			// apc_store($apc_key, $arr_pages_json, $cache_ttl);

		// } // end get cached stuff

		// add debug info to last one
		if ( $this->input->get("debug_api") ) {
			
			$arr_debug = array(
				"memory_usage" => $this->benchmark->memory_usage(),
				"elapsed_time" => $this->benchmark->elapsed_time() . " seconds",
				"total_queries" => $this->db->total_queries(),
				"used_cached_values" => $used_cached_values
			);
			
			$arr_pages_json[ sizeof($arr_pages_json)-1 ][ "debug" ] = $arr_debug;

		}

		if (isset($jsoncallback) && $jsoncallback) {
			$out .= $jsoncallback . "(";
		}

		// We can simulate slow fetching using get slow-answer=1
		if ( $this->input->get("slow_answer") ) {
			$rand = rand(1, 5);
			sleep( $rand );
			// $arr_pages_json["slow_answer"] = $rand;
		}


		$out .= json_encode($arr_pages_json);

		if (isset($jsoncallback) && $jsoncallback) {
			$out .= ");";
		}

		// Output
		echo $out;

		break;

	// ge html-formatet för de innre partierna
	case "get_html":

		// Create array with all the pages we want to get
		$arr_pages = texttv_page::extract_pages_from_ranges($page_num);

		// Samla ihop alla sidor i en array för json-output
		$arr_pages_json = array();
		$arr_pages_obj = array();
		foreach ($arr_pages as $one_page) {
			$page = new texttv_page($one_page);
			$arr_pages_obj[] = $page;
		}

		$data = array(
			"pages" => $arr_pages_obj,
			"pagenum" => $page_num
		);
		$arr_pages_json["pages_inner_output"] = $this->load->view("pages_inner_output_current", $data, true);

		if (isset($jsoncallback) && $jsoncallback) {
			echo $jsoncallback . "(";
		}
		echo json_encode($arr_pages_json);
		if (isset($jsoncallback) && $jsoncallback) {
			echo ");";
		}

		break;

}