<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dev extends CI_Controller {

	public function index() {

		//$this->visa("100,300,700");
		#$action = $this->input->get("action");
		exit;

	}
	
	/**
	 * Test to make all pages equal number of lines
	 * pages like 100, 101, 300 and so on have a few more or less
	 * probably due to graphics usage
	 *
	 * Wikipedia says teletext has 24 row with 40 chars per row
	 * SVT seems to have 23 rows, so lets assume that to begin with
	 */
	function pageLineCountFix() {
		
		$page = new Texttv_page(100);
		header("content-type:text/html; charset=utf-8");
		
		?>
		<link rel="stylesheet" href="/css/texttvpage.css">
		
		<div class="TextTVPage">
		<?php
		

		foreach ( $page->arr_contents as $one_page ) {
			
			
			#echo "\nNum lines on page before change: $page_lines_count\n";

			//$one_page = $this->maybeChangeLineCount($one_page);

			#echo "\nNum lines on page after change: " . count($arr_page_lines) . "\n";
			#print_r($arr_page_lines);
			
			echo $one_page;
			
		}
		
		?>
		</div>
		<?php
		
		exit;
		
	}
	
	
	/**
	 * To test the title-extractor
	 */
	public function titleTest() {
		
		header('Content-Type: text/html; charset=utf-8');
				

		// Skapa title:s av dessa
		// numera som h1:or!
		$title = "";
		$page_contents = '<div id="pages"><ul><li data-sida=324 class="one-page"><ul class="inpage-pages  subpage-count-2 subpage-count-many"><li><div class="root"><span class="toprow"> 324 SVT Text         Lördag 04 jul 2015
 </span><span class="B bgB"> </span><span class="B bgB"> </span><span class="W bgB">SVERIGES BÄSTA IDROTTSSTAD   3 juli  </span>
 <span class="G">                                   1/2 </span>
 <span class="G">Sveriges bästa idrottsstad             </span>
 <span class="Y"> </span><span class="W"> </span><span class="W"> </span><span class="W"> </span><span class="W"> </span><span class="Y"> </span><span class="W">                                 </span>
 <span class="Y"> 1. </span><span class="W">Presenteras på söndag              </span>
 <span class="Y"> 2. </span><span class="W">                                   </span>
 <span class="Y"> 3. </span><span class="W">Karlstad                          </span><span class="Y"> </span>
 <span class="Y"> </span><span class="Y">4. </span><span class="W">Göteborg                          </span><span class="Y"> </span>
 <span class="Y"> </span><span class="Y">5. </span><span class="W">(4) Stockholm     217  </span><span class="Y">            </span>
  <span class="Y">6. </span><span class="W">(2) Solna         211              </span>
 <span class="Y"> </span><span class="W">   </span><span class="W">(6) Uppsala       211              </span>
 <span class="Y"> </span><span class="Y">8  </span><span class="W">(14) Linköping    196             </span><span class="Y"> </span>
 <span class="Y"> </span><span class="Y">9. </span><span class="W">(19) Mora         143            </span><span class="Y">  </span>
 <span class="Y">10. </span><span class="W">(8) Umeå 115                       </span>
 <span class="Y">                                       </span>
 <span class="Y">11. </span><span class="W">(22) Falun        108              </span>
 <span class="Y">12. </span><span class="W">(16) Växjö         74              </span>
 <span class="Y">13. </span><span class="W">(ny) Västerås      55              </span>
 <span class="Y">14. </span><span class="W">(12) Södertälje    48              </span>
 <span class="Y">15. </span><span class="W">(ny) Sundsvall     44              </span>
 <span class="Y">                                       </span>
                                        
 <span class="B bgB"> </span><span class="B bgB"> </span><span class="W bgB">      Läs mer: svt.se/sport          </span>
</div></li><li><div class="root sub"><span class="toprow"> 324 SVT Text         Lördag 04 jul 2015
 </span><span class="B bgB"> </span><span class="B bgB"> </span><span class="W bgB">SVERIGES BÄSTA IDROTTSSTAD   5 juli  </span>
 <span class="G">                                   2/2 </span>
 <span class="G">Sveriges bästa idrottsstad plats 16-25 </span>
 <span class="Y"> </span><span class="W"> </span><span class="W"> </span><span class="W"> </span><span class="W"> </span><span class="Y"> </span><span class="W">                                 </span>
 <span class="Y">16. </span><span class="W">(24) Kristianstad 38 poäng         </span>
 <span class="Y">17. </span><span class="W">(7)  Partille     32               </span>
 <span class="Y">18. </span><span class="W">(9)  Helsingborg  22               </span>
 <span class="Y">19. </span><span class="W">(ny) Östersund    19               </span>
 <span class="Y">    </span><span class="W">(16) Luleå        19               </span>
   <span class="Y">  </span><span class="W">(18) Piteå        19               </span>
 <span class="Y">22. </span><span class="W">(20) Gävle        16               </span>
 <span class="Y">23. </span><span class="W">(10) Leksand      15               </span>
 <span class="Y">24 </span><span class="W"> (15) Skellefteå   14               </span>
 <span class="Y">25. </span><span class="W">(ny) Borås         7               </span>
                                        
 <span class="Y">Utanför listan: </span><span class="W">Jönköping, Norrköping, </span>
 <span class="W">Ängelholm, Eskilstuna, Lidköping,      </span>
 <span class="W">Vetlanda, Nacka, Lund, Falkenberg,     </span>
 <span class="W">Nässjö, Skövde, Täby, Huddinge, Eslöv, </span>
 <span class="W">Halmstd.                               </span>
                                        
                                        
 <span class="B bgB"> </span><span class="B bgB"> </span><span class="W bgB">      Läs mer: svt.se/sport          </span>
</div></li></ul></li></ul></div>';
	
		if (preg_match_all('/<h1 class="[a-z ]*DH">([\w\dåäöÅÄÖ :.()é\-"]+)/i', $page_contents, $matches)) {
	
			$arr_titles = array();
	
			foreach ($matches[1] as $one_title) {
	
				if (trim($one_title)) {
	
					$arr_titles[] = trim($one_title);
	
				}
	
			}
	
			$title .= join(" | ", $arr_titles);
	
		}
	
		// Om ingen title, försök med metod två
		// dvs. ta title från första icke-tomma span:en efter .toprow		
		if ( ! $title ) {
			
			# echo "<br>testing method two";
			
			libxml_use_internal_errors(true);
			$doc = new DOMDocument();
			$page_contents = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' . $page_contents;
			$doc->loadHTML($page_contents);

			$xpath = new DOMXPath($doc);
			$nodes = $xpath->query('//span');
			foreach ($nodes as $node) {
				
				$class = $node->getAttribute("class");

				// skip toprow
				if (strpos($class, "toprow") !== false) continue;

				$nodeValue = trim( $node->nodeValue );
				
				// skip empty rows
				if (empty($nodeValue)) continue;
				
				# echo "\n<br>" . $nodeValue;
				
				$title = $nodeValue;
				
				// we got a title - so we're done
				break;
				
			}
			#echo $doc->saveHTML();
			
		}
		
		
		echo "<br>found title:<br>\n$title";
		
		exit;
		
	}
	
	// http://texttv.nu/dev/search
	public function search() {
		
		header('Content-Type: text/html; charset=utf-8');
		
		$s = $this->input->get("s");
		$pagenum = (int) $this->input->get("pagenum");
		if (!$pagenum) { $pagenum = ""; }
		
		?>

		<title>Sök SVT Text TV-sida</title>
		<meta content='width=device-width, initial-scale=1.0' id='viewport' name='viewport' />

		<h1>Sök text tv-sida</h1>
		
		<p>Söker efter sidor senaste två dagarna typ.</p>
		<p>Vissa sidor exkluderas, t.ex. 188 nyhetsrullen.</p>
		
		<form method="get">
			<p>
				<label>
					Sök efter ord
					<br>
					<input type="search" name="s" value="<?php echo htmlspecialchars($s) ?>">
				</label>
			</p>
			
			<p>
				<label>
					Endast på denna sida
					<br>
					(bara en sida stöds, lämna tom för att söka alla sidor)
					<br>
					<input type="text" name="pagenum" value="<?php echo htmlspecialchars($pagenum) ?>">
				</label>
			
			<p>
				<input type="submit" value="Sök">
			</p>
			
		</form>
		<?php
			
		if ($s) {
			
			printf('<p>Sökresultat för <b>%s</b></p>', htmlspecialchars($s));
			
			$sql_page_where = "";
			if ( $pagenum ) {
				$sql_page_where = sprintf(' AND page_num IN(%1$s)', $pagenum);
			}
					
			$sql = sprintf('
				SELECT 
					id, page_num, date_updated, date_added, title, UNCOMPRESS(page_content), is_shared 
				FROM texttv 
				WHERE 
					page_num NOT IN (188)
					AND date_added >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
					AND 
						(
							title LIKE "%%%1$s%%"
							OR
							UNCOMPRESS(page_content) LIKE "%%%1$s%%"
						)
					%2$s
				ORDER BY date_added DESC
				LIMIT 20
				', $this->db->escape_like_str($s), // 1
				$sql_page_where // 2
			);
			
			// echo $sql;
			
			$result = $this->db->query($sql);

			if ( $result->num_rows() == 0 ) {
				?><p>Nähä du, inga träffar alls på detta.</p><?php
			}

			foreach ($result->result() as $row) {

				$archive_page = new Texttv_page();
				$archive_page->id = $row->id;
				$archive_page->load(true);
				
				printf(
					'
						<li>
							Sida %1$s
							– %4$s
							– <a href="%2$s">
								%3$s
							</a>
						</li>
					',
					$row->page_num,
					$archive_page->get_permalink(),
					$row->title,
					$row->date_added // 4
				);
				
			}
			
		}
		
		exit;
		
	}	
	
	// http://texttv.nu/dev/updated
	public function updated() {
		
		header('Content-Type: text/html; charset=utf-8');
		
		$result = get_latest_updated_pages(100, 200, 50);
		$arr_outputed_page_nums = array();

		?>
		<title>Senast uppdaterade SVT Text TV-sidorna</title>
		<meta content='width=device-width, initial-scale=1.0' id='viewport' name='viewport' />
		<style>
			body {
				font-family: sans-serif;
			}
			
			.box {
				float: left;
				width: 33.333%;
			}
			
			.twitter-timeline-wrap {
				float: left;
				width: 50% !important;
			}
			
			@media (max-width: 667px) and (orientation: portrait) {

				.box {
					float: none;
					width: auto;
				}
				
				.twitter-timeline-wrap {
					float: none;
					width: auto !important;
				}
				
			}



		</style>
		<?php

		echo "<div class='box'>";
		echo "<h2>Senast uppdaterade nyhetssidorna</h2>";
		echo "<ul>";
		foreach ( $result->result() as $row ) {

			if ( in_array($row->page_num, $arr_outputed_page_nums)) {
				continue;
			}

			printf('
					<li>
						<a href="/%1$d">
							<small>%2$s:</small>
							%3$s
						</a>
					</li>
				',
				$row->page_num,
				$row->date_added_formatted,
				$row->title
			);
			
			$arr_outputed_page_nums[] = $row->page_num;

		}
		echo "</ul>";
		echo "</div>";

		$result = get_latest_updated_pages(300, 400, 50);
		$arr_outputed_page_nums = array();

		echo "<div class='box'>";
		echo "<h2>Senast uppdaterade sportsidorna</h2>";
		echo "<ul>";
		foreach ( $result->result() as $row ) {

			if ( in_array($row->page_num, $arr_outputed_page_nums)) {
				continue;
			}

			printf('
					<li>
						<a href="/%1$d">
							<small>%2$s:</small>
							%3$s
						</a>
					</li>
				',
				$row->page_num,
				$row->date_added_formatted,
				$row->title
			);
			
			$arr_outputed_page_nums[] = $row->page_num;

		}
		echo "</ul>";
		echo "</div>";
		
		$result = get_shared_pages(1, 20);

		echo "<div class='box'>";
		echo "<h2>Delade sidor idag (senaste 24 timmar)</h2>";
		echo "<ul>";
		foreach ( $result->result() as $row ) {
			
			$archive_page = new Texttv_page();
			$archive_page->id = $row->id;
			$archive_page->load(true);
			$permalink = $archive_page->get_permalink();

			printf('
					<li>
						<small>
							%4$s – hämtad %2$s – sida %1$s
						</small>
						<br>
						<a href="%5$s">
							%3$s
						</a>
					</li>
				',
				$row->page_num, // 1
				$row->date_added_formatted, // 2
				$row->title, // 3
				$row->num_shares > 1 ? $row->num_shares . " delningar" : "1 delning", // 4
				$permalink // 5
			);
			
			$arr_outputed_page_nums[] = $row->page_num;

		}
		echo "</ul>";
		echo "</div>";
		
		// https://twitter.com/settings/widgets/619611122426056704/edit
		?>
		
		<h2>Sparade twitter-sökningar</h2>
		
		<div class="twitter-timeline-wrap">
			<p>svt text</p>		
			<a class="twitter-timeline" href="https://twitter.com/search?q=svt%20text" data-widget-id="619611122426056704">Tweets about svt text</a>
		</div>

		<div class="twitter-timeline-wrap">
			<p>svttext</p>
            <a class="twitter-timeline"  href="https://twitter.com/search?q=svttext%20-from%3Atexttv.nu%20-from%3Atext_tv_nyheter%20-from%3Atext_tv_sport" data-widget-id="775297985454469120">Tweets about svttext -from:texttv.nu -from:text_tv_nyheter -from:text_tv_sport</a>
		</div>

		<div class="twitter-timeline-wrap">
			<p>"text tv"</p>
			<a class="twitter-timeline" href="https://twitter.com/search?q=%22text%20tv%22" data-widget-id="619611614459863040">Tweets about "text tv"</a>
		</div>

		<div class="twitter-timeline-wrap">
			<p>texttv</p>
            <a class="twitter-timeline"  href="https://twitter.com/search?q=texttv%20-from%3Atexttv.nu%20-from%3Atext_tv_nyheter%20-from%3Atext_tv_sport" data-widget-id="775298427039125505">Tweets about texttv -from:texttv.nu -from:text_tv_nyheter -from:text_tv_sport</a>
        </div>

		<div class="twitter-timeline-wrap">
			<p>texttv.nu</p>
			<a class="twitter-timeline" href="https://twitter.com/search?q=texttv.nu" data-widget-id="619611844769107968">Tweets about texttv.nu</a>
		</div>

		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

		<?php
		
		exit;
		
	}
	
	
	// http://texttv.nu/dev/updated
	public function stats() {
		
		header('Content-Type: text/html; charset=utf-8');
		
		?>
		<!DOCTYPE html>
		<head>
			<title>Stats</title>
			<script src="/js/js.cookie.js"></script>
		</head>
		<body>
			
			<h1>Statistik » dina mest besökta sidor</h1>
			
			<p>Sida: Antal besök</p>
			
			<div class="statsOutput">
			</div>
			
			<script>
				var statsOutputElm = document.querySelector(".statsOutput");
				var stats = Cookies.getJSON("stats");
				var statsHTML = "";
				
				if (stats) {
					for (var num in stats) {
						statsHTML += `<li>${num}: ${stats[num].count}</li>`;
					}
				}
				
				statsOutputElm.innerHTML = statsHTML;
			</script>
			
		</body>
		
		<?php
		
		exit;
		
	}

}

