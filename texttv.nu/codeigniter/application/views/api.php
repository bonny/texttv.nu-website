<?php

/**
 * Api
 */


switch ($api_call) {

	/**
	 * Update page(s)
	 * Called via CRON or by admin
	 * Update 24 jun 2016: this is not used anywhere any longer, right??
	 */
	case "update_page":	
		
		echo "\n# Updating";
		
		$this->output->set_header("Content-Type:text/plain; charset=UTF-8");

		// Check page with remote server after page is this num of seconds old
		// Randomize the ttl so all pages don't update at same time all the time 
		// (may look like tiny ddos attack on remote server or something)
		$page_ttl = rand(30, 50);
		echo "\n\nUsing random ttl of $page_ttl seconds.";

		// Group results so we can get stats about them last in output
		$arr_results = array();

		// Get page range
		$arr_pages = texttv_page::extract_pages_from_ranges($page_num);

		// Check each page in range
		foreach ($arr_pages as $one_page) {
			
			$do_update = false;
			$apc_key = "texttv_page_{$one_page}";
			
			echo "\n\n - Page '$one_page': ";

	    	if (false === $cached_page_info = apc_fetch($apc_key)) {
	    		// not in cache, so update based on last modified in db
	    		$do_update = true;
	    	} else {
	    		// cached value exist, but how old?
	    		$seconds_since_last_check = time() - $cached_page_info["local_last_checked"];
	    		echo "Checked $seconds_since_last_check seconds ago.";
	    		if ($seconds_since_last_check > $page_ttl) {
	    			$do_update = true;
	    			echo "\n   Update done more than {$page_ttl}s ago, so page will be updated.";
	    		}
	    	}
			
			if ($do_update) {
				$page = new texttv_page($one_page);
				$result = $page->update_page_manually();
				echo "\n   Update result: '$result'";
			} else {
				// echo "\nNot updating page '$one_page', not old enough in APC cache";
				$result = "apc_cache_not_old_enough";
			}

			if ( ! isset( $arr_results[ $result ] ) )
				$arr_results[ $result ] = 0;

			$arr_results[ $result ] ++;

			flush();


		}

		echo "\n\n# Stats\n";
		echo "\n - memory_usage: " . $this->benchmark->memory_usage();
		echo "\n - elapsed_time: " . $this->benchmark->elapsed_time() . " seconds";
		echo "\n - total_queries: " . $this->db->total_queries();
		echo "\n - result stats: " . print_r($arr_results, true);
		
		echo "\n\nAll done.\n\n";

		break;

	case "get":

		// Output as json
		$this->output->set_content_type("text/json");
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
				$arr_pages_json[] = array(
					"num" => $page->num,
					"title" => $page->title,
					"content" => $page->get_content_with_fixes(),
					"next_page" => $page->next_page,
					"prev_page" => $page->prev_page,
					"date_updated_unix" => $page->date_updated_unix,
					"permalink" => $page->get_permalink(TRUE),
					"id" => $page->id
				);
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