<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*

Incoming webhook for facebook
https://texttv.nu/fb/webhook

Page Access Token = CAAEl9Tmk7IsBAAqOx5QhDPNDtTSm1q9CtJGX0fWOrC7syFZA26RUZBcAtWoIzFpEq0M15TM6pbuCJeYR9GwLmgQw0YXZBztjg3gJq2pWtsVVGlj8ZCtfRYV6ZCmPtJiXYuG0iTDwTSpZCGSmp0cEcQ4SZAbjpGiEPPfKzUmvx4hcUR8e2iMeHnTxzaYGNDUjxaYnFdZBTlEruQZDZD
*/

class Fb extends CI_Controller {

	var $page_access_token = "CAAEl9Tmk7IsBAAqOx5QhDPNDtTSm1q9CtJGX0fWOrC7syFZA26RUZBcAtWoIzFpEq0M15TM6pbuCJeYR9GwLmgQw0YXZBztjg3gJq2pWtsVVGlj8ZCtfRYV6ZCmPtJiXYuG0iTDwTSpZCGSmp0cEcQ4SZAbjpGiEPPfKzUmvx4hcUR8e2iMeHnTxzaYGNDUjxaYnFdZBTlEruQZDZD";

	// In order to send a message, make a POST request to 
	// https://graph.facebook.com/v2.6/me/messages?access_token=<PAGE_ACCESS_TOKEN>
	var $api_send_message = "https://graph.facebook.com/v2.6/me/messages?access_token=";

	public function index() {

		//$this->visa("100,300,700");
		#$action = $this->input->get("action");
		echo "yolo";
		exit;

	}
	
	/**
	 * webhook
	 */
	public function webhook() {
	
		/*$arr_json = [
			"test" => true,
			"page_access_token" => $this->page_access_token
		];
	
		$this->output->set_content_type("application/json");
		$this->output->append_output( json_encode($arr_json) );
		
		// Request from FB looks like this:
		// GET /fb/webhook?hub.mode=subscribe&hub.challenge=1226118981&hub.verify_token=yolo_verify

		
		*/

		$body = file_get_contents('php://input');
		$body_json = json_decode($body);
		
		$log_key = "fb messenger webhook";
		log2db($log_key, "-----");
		log2db($log_key, "body_json: " . print_r($body_json, true));
		log2db($log_key, "get: " . json_encode_pretty($_GET));
		log2db($log_key, "post: " . json_encode_pretty($_POST));
		log2db($log_key, "body: " . $body);
		
		if ( "GET" == $this->input->server('REQUEST_METHOD') && $this->input->get("hub_verify_token") ) {

			// If is a verify request from fb			
			$this->verify_token();

		} else if ( "POST" == $this->input->server('REQUEST_METHOD') && $body_json ) {

			// Posted data
			// Can be posted message or callback from clicked button
			$this->output->set_status_header('200');
			
			// All posted data have some common data 
			// https://developers.facebook.com/docs/messenger-platform/webhook-reference#common_format
			$callback_entries = $body_json->entry;
			foreach ( $callback_entries as $entry ) {

				$callback_time = $entry->time;
				$callback_messaging = $entry->messaging;
				
				$detected_post_callback_type = false;
				
				foreach ( $callback_messaging as $messaging ) {

					$sender_id = $messaging->sender->id;
					
					if ( ! empty( $messaging->message ) && ! empty( $messaging->message->text ) ) {
						
						// this is a callback for a text message
						$detected_post_callback_type = true;
						$message_text = $messaging->message->text;
						log2db($log_key, "Detected callback message text: $message_text");
						$this->act_on_text($entry, $messaging);
						
					} else if ( ! empty( $messaging->postback ) && ! empty( $messaging->postback->payload ) ) {

						// https://developers.facebook.com/docs/messenger-platform/webhook-reference#postback
						$postback_payload = $messaging->postback->payload;
						log2db($log_key, "Detected callback message payload: $postback_payload");
						$detected_post_callback_type = true;
						$this->act_on_callback($entry, $messaging, $postback_payload);

					}
					
					if ( ! $detected_post_callback_type ) {
						log2db($log_key, "warning: could not detect post callback type");
					}
					
				} // foreach message
				
			} // foreach callback entry
				
		} // if GET or POST

	}
	
	function verify_token() {

		$verify_token = "yolo_verify";
		if ( $this->input->get("hub_verify_token") == $verify_token ) {
			$this->output->append_output( $this->input->get("hub_challenge") );
			$this->output->set_status_header('200');
		}
		
	}

	/**
	 * Called when we get a text message
	 * Detect type of text message and then return response based on that
	 */	
	function act_on_text( $entry, $messaging ) {

		$log_key = "fb messenger webhook";
		log2db($log_key, "act_on_text()");
		
		$sender_id = $messaging->sender->id;
		$message_text = $messaging->message->text;
		
		$found_nums = preg_match_all('/(\d{3})/', $message_text, $matches);
		
		if ( $found_nums ) {
			
			// Found numbers, so show them
			/*
			$matches:
			Array
			(
			    [0] => Array
			        (
			            [0] => 300
			            [1] => 377
			        )
			
			    [1] => Array
			        (
			            [0] => 300
			            [1] => 377
			        )
			
			)				
			*/
			foreach ( $matches[0] as $matched_pagenum ) {

				$this->send_pagenum($matched_pagenum, $entry, $messaging);
					
			}
			
		} else {
			
			// not found number, show help/general message
		
			// Detect message type
			// @todo: detect if users want to get number
			// at the moment we simple sent back a message with some buttons
			$curl_payload = [
				"recipient" => [
					"id" => $sender_id
				],
				"message" => [
					"attachment" => [
						"type" => "template",
						"payload" => [
							"template_type" => "button",
							"text" => "Okej! Vilken text-tv-sida vill du läsa? Välj nedan eller skriv numret och skicka.",
					        "buttons" => [
								[
									"type" => "postback",
									"title" => "100",
									"payload" => "SHOW_PAGE_100"
								],
								[
									"type" => "postback",
									"title" => "377",
									"payload" => "SHOW_PAGE_377"
								],
								[
									"type" => "web_url",
									"url" => "https://texttv.nu/",
									"title" => "Gå till texttv.nu"
								],
							]
						]
					]
				]
			];
	
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $this->api_send_message . $this->page_access_token);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json'
			]);
			
			$result = curl_exec($ch);
			log2db($log_key, "sent curl request to api with result: " . json_encode_pretty($result));
			
			// curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
			// FB keeps sending text to this webhook, try to answer something and see if that helps..
			//$this->output->append_output( "ok" );

		} // if num(s) found
			
	} // end func
	
	/**
	 * User has clicked button in message
	 */
	function act_on_callback( $entry, $messaging, $postback_payload ) {

		$log_key = "fb messenger webhook";
		log2db($log_key, "act_on_callback()");

		$sender_id = $messaging->sender->id;
		
		// $postback_payload = SHOW_PAGE_123	
		if ( strpos($postback_payload, "SHOW_PAGE_") !== false ) {
			
			$page_num_to_show = (int) str_replace("SHOW_PAGE_", "", $postback_payload);
			
			$this->send_pagenum($page_num_to_show, $entry, $messaging);			
			//$this->output->append_output( "ok" );

		}

	} // func
	
	function send_pagenum($pagenum, $entry, $messaging) {

		$log_key = "fb messenger webhook";
		log2db($log_key, "send_pagenum()");
		
		$pagenum = (int) $pagenum;
		if ( ! $pagenum ) {
			return false;
		}
		
		$page = new texttv_page($pagenum);
		
		if ( ! $page ) {
			return false;
		}
		
		$element_title = $page->title;

		$element_subtitle = implode($page->arr_contents);
		$element_subtitle = strip_tags($element_subtitle);
		
		log2db($log_key, json_encode_pretty($page));
		
		$sender_id = $messaging->sender->id;

		// Works, but not all text fits
		$curl_payload_generic = [
			"recipient" => [
				"id" => $sender_id
			],
			"message" => [
				"attachment" => [
					"type" => "template",
					"payload" => [
						"template_type" => "generic",
						"elements" => [
							/*[
								"title" => $element_title,
								"subtitle" => $element_subtitle,
								"image_url" => "https://texttv.nu/images/fb-page-full-2.png",
								"item_url" => "https://texttv.nu/$pagenum"
							],
							[
								"title" => $element_title,
								"subtitle" => $element_subtitle,
								"image_url" => "https://texttv.nu/images/fb-page-full.png",
								"item_url" => "https://texttv.nu/$pagenum"
							],*/
							[
								"title" => "1/2 $element_title",
								//"subtitle" => $element_subtitle,
								"image_url" => "https://texttv.nu/images/fb-page-top.png",
								"item_url" => "https://texttv.nu/$pagenum"
							],
							[
								"title" => "2/2 $element_title",
								//"subtitle" => $element_subtitle,
								"image_url" => "https://texttv.nu/images/fb-page-bottom.png",
								"item_url" => "https://texttv.nu/$pagenum"
							],
							/*[
								"title" => "Mer Text TV $page_num_to_show",
								"subtitle" => "Mer utdrag från $page_num_to_show kanske",
								"item_url" => "https://texttv.nu/$page_num_to_show"
							],*/
						]
					]
				]
			]
		];
		
		// Only text
		// text must be less than 320!
		$text_max_length = 320;
		$message_text = $element_subtitle;
		// remove whitespace
		$message_text = removeWhiteSpace($message_text);
		$message_text = substr($message_text, 0, $text_max_length-1);

		$curl_payload_text = [
			"recipient" => [
				"id" => $sender_id
			],
			"message" => [
				"text" => $message_text
			]
		];

		
		$image_url = "http://texttv.nu/images/fb-page-full.png";
		$image_url = "https://texttv.nu/shares/" . $this->generate_screenshot($pagenum) . ".png";

		$curl_payload_image = [
			"recipient" => [
				"id" => $sender_id
			],
			"message" => [
				"attachment" => [
					"type" => "image",
					"payload" => [
						"url" => $image_url
					]
				]
			]
		];
	
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $this->api_send_message . $this->page_access_token);
		curl_setopt($ch, CURLOPT_POST, true);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_payload_text));
		//curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_payload_generic));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_payload_image));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json'
		]);
	
		$result = curl_exec($ch);
		log2db($log_key, "sent curl with CURLOPT_POSTFIELDS: " . json_encode_pretty($curl_payload_image));
		log2db($log_key, "sent curl request to api with result: " . json_encode_pretty($result));

	}
	
	/**
	 * Generate a screenshot of a page using phantomjs
	 * @param int $pagenum page num to generate screenshot for
	 * @return name of output file, with no extension
	 */
	function generate_screenshot($pagenum) {
	
		$pagenum = (int) $pagenum;
		
		$uniqid = uniqid();
				
		$cmd = "/root/fb-messenger-screenshot/phantomjs /root/fb-messenger-screenshot/rasterize.js 'http://api.texttv.nu/{$pagenum}?apiAppShare=fbmessenger' /usr/share/nginx/texttv.nu/shares/{$uniqid}.png";
		
		$last_line = system($cmd, $return_val);
		
		return $uniqid;	
			
	}
	
}

