<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Sitemap extends CI_Controller {

	public function index()
	{
		
		// 26 dec 2013: inaktiverade denna, ge 404 istället
		// blev helt enkelt för många anrop
		$this->output->set_status_header('404');
		exit;


		// @todo: must be in a controller to work...
		$this->output->cache(10);
		
		header("Content-Type:text/xml");
		
		$rs = $this->db->select("id")->from("texttv")->order_by("date_updated", "DESC")->get();
		
		$out = '<?xml version="1.0" encoding="UTF-8"?>';
		$out .= "\n";
		$out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		//$out .= $rs->num_rows();
		foreach ($rs->result() as $row) {
			$page = new texttv_page();
			$page->id = $row->id;
			$page->load(TRUE);
			$out .= sprintf("\n<url>\n\t<loc>%s</loc>\n</url>", "http://texttv.nu".$page->get_permalink());
		}
		
		$out .= '</urlset>';
		/*
<?xml version="1.0" encoding="UTF-8"?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

   <url>

      <loc>http://www.example.com/</loc>

      <lastmod>2005-01-01</lastmod>

      <changefreq>monthly</changefreq>

      <priority>0.8</priority>

   </url>

</urlset> 
		*/
		
	$this->output->append_output($out);
	$this->output->append_output(sprintf("<!-- 
			Elapsed time: %s
			<br>Memory usage: %s 
			-->",
			$this->benchmark->elapsed_time(),
			$this->benchmark->memory_usage()
		));



	}
}


