<?php 

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

#echo "<br>start";
# exit;

class rssfeed extends CI_Controller {

	function __construct() {	
		// https://github.com/Mitaka777/ci-feed
		parent::__construct();
		$this->load->library('feed');
		$this->load->helper('url');
	}

	public function index() {
		$output = "
			<!doctype html>
			<html lang=sv>
			<meta charset=utf-8>
			<title>RSS-flöded på TextTV.nu</title>
			<h1>RSS-flöden är en bra grej!</h1>
			
			<p>TextTV.nu har följande RSS-flöden:</p>
			
			<ul>
				<li>
					<p>
						Blogg/Nyheter från utvecklingsteamet
						<br>
						<a href='https://texttv.nu/feed/blogg'>https://texttv.nu/feed/blogg</a>
					</p>
				</li>
			</ul>
		";
		$this->output->set_output($output);
	}

	// https://texttv.nu/rss/blogg/atom?tmp=k
	public function visa($feedName = null, $format = 'atom') {
		// $this->output->set_output("visa feedName <b>$feedName</b> i format <b>$format</b>");
		switch ($feedName) {
			case 'blogg':
				$this->blogg($format);
				break;
			default:
				$this->index();
		}
	}

	public function blogg($format = 'atom') {

		// $this->load->view('404');
		$bloggEntries = $this->db->query("SELECT id, date_published, UNIX_TIMESTAMP(date_published) AS date_published_unix, permalink, title, content FROM texttv_blogg ORDER BY date_published DESC LIMIT 20");
		
		$firstEntry = $bloggEntries->first_row();

	    // create new instance
	    $feed = new Feed();
	
	    // set your feed's title, description, link, pubdate and language
	    $feed->title = 'Senaste blogginläggen från TextTV.nu';
	    $feed->description = 'Senaste blogginläggen från TextTV.nu';
	    $feed->link = 'https://texttv.nu/blogg';
	    $feed->lang = 'sv';
	    // date of your last update (in this example create date of your latest post)
	    $feed->pubdate = date('c', $firstEntry->date_published_unix);

		$cutOff = '';
			
		foreach ($bloggEntries->result() as $entry) {
			// print_r($entry);

			$contentExcerpt = shorten_text(strip_tags($entry->content), $maxLength = 280, $cutOff, $keepWord = true);
			// set item's title, author, url, pubdate and description
			$feed->add($entry->title, "TextTV.nu", "https://texttv.nu/blogg/" . $entry->permalink, date('c', $entry->date_published_unix), $contentExcerpt);
		}

		// show your feed (options: 'atom' (recommended) or 'rss')
		switch ($format) {
			case 'rss':
				$feed->render('rss');
				break;
			case 'atom':
			default:
				$feed->render('atom');
		}
		
		// $this->output->set_output('RSS');
	}

}
