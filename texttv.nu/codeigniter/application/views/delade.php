<?php

function output_shared_pages($result) {

	$num_max_to_show = 10;
	$num_outputted = 0;
	
	echo "<ol>";
	foreach ( $result->result() as $row ) {
				if ( $num_outputted > $num_max_to_show ) {
			break;
		}
		
		$archive_page = new Texttv_page();
		$archive_page->id = $row->id;
		$archive_page->load(true);
		$permalink = $archive_page->get_permalink();
		
		// Title must be longer than say 3 chars because otherwise clearly something is wrong
		if (mb_strlen($row->title) < 4) {
			continue;
		}

		printf('
				<li>
					<a href="%5$s">
						%3$s
					</a>
					<br>
					<small>sida %1$s från %2$s</small>
				</li>
			',
			$row->page_num, // 1
			$row->date_added_formatted, // 2
			$row->title, // 3
			$row->num_shares > 1 ? $row->num_shares . " delningar" : "1 delning", // 4
			$permalink // 5
		);
		
		$arr_outputed_page_nums[] = $row->page_num;
		
		$num_outputted++;

	}
	echo "</ol>";

}

function output_shared_pages_nav_form() {

	$CI =& get_instance();

	$date = $CI->input->get("datum");
	
	// Visa från och med när vi började mäta delade sidor (när nu det var?)
	$start_date = "2015-07-04";
	
	// ...tills idag
	$end_date = date("Y-m-d");

	?>
	
	<!-- <h2>Visa mest delade text-tv-sidorna för dag</h2> -->
	
	<p>
		<form method="get" action="/sida/delat/">
			<label for="most-shared-date">Datum</label>
			<select name="datum" id="most-shared-date">
				<?php
				$day = $end_date;
				$prev_month = null;
				$optgroup_needs_close = false;
				
				$datesLiHtml = '';
				
				while ( $day != $start_date ) {
			
					$this_month = date("F Y", strtotime($day));
					if ($this_month != $prev_month) {
						
						if ( $optgroup_needs_close ) {
							printf("</optgroup>");
						}
						
						printf('<optgroup label="%1$s">', ucfirst( date("F", strtotime($this_month))));
						$optgroup_needs_close = true;
					}
			
					$str_date = ucfirst( date("D j F", strtotime($day)) );
					if ( date("Y-m-d") == $day ) {
						$str_date = $str_date . " (idag)";
					} else if ( date("Y-m-d", strtotime("-1 day", strtotime(date("Y-m-d")))) == $day ) {
						$str_date = $str_date . " (igår)";
					}
			
					printf(
						"\n" . '<option %2$s value="%1$s">%3$s</option>',
						$day, // 1
						$day == $date ? " selected " : "", // 2
						$str_date
					);
					
					$datesLiHtml .= sprintf(
						"\n" . '<li><a href="%3$s">%2$s</a></li>',
						$day, // 1
						$str_date, // 2
						"?datum=$day" // 3
					);
					
					$day = date("Y-m-d", strtotime("-1 day", strtotime($day)));
					$prev_month = $this_month;
					
				}

				if ( $optgroup_needs_close ) {
					printf("</optgroup>");
				}
				
				?>
			</select>
			<input type="submit" value="Visa">
		</form>
	</p>
	
	<?php
		
	echo "<ul class='most-shared-dates-ul-list'>{$datesLiHtml}</ul>";
	
}

?>
<style>
	
	.most-shared-section-wrap {
		
	}
	
	.most-shared-section {
/* 		width: 50%; */
		float: left;
		margin-right: 50px;
	}
	
	.most-shared-dates-ul-list {
		height: 20em;
		overflow: hidden;
		overflow-y: auto;
	}
	
</style>

<div class="textcontent">
	
	<div class="most-shared-section-wrap clearfix">
	<?php
		
	// om datum är satt så ska bara händelser från den dagen visas
	$date = $third_level;
	
	// Om datum finns i querystring så använd det
	if ( $this->input->get("datum") ) {
		$date = $this->input->get("datum");
	}
	
	if ( $date ) {
	
		// Datum satt i följande URL-format:
		// http://texttv.nu/sida/delat/2015-08-24
		$date_unix = strtotime($date);
		printf('<h1>Text TV: mest delat %1$s</h1>', date("l j F Y", $date_unix));
		
		output_shared_pages_nav_form();
		
		$result = get_shared_pages_for_period( strtotime("today 00:00", $date_unix), strtotime("today 24:00", $date_unix) );
		output_shared_pages($result);

	} else {

		?>

		<h1>Mest delade sidorna från SVT Text TV</h1>
		
		<p>Här listas de mest delade nyheterna på texttv.nu.</p>

		<?php

		output_shared_pages_nav_form();

		// Inget datum, visa för idag + igår
		echo "<div class='most-shared-section most-shared-section--today'>";
		echo '<h2>Mest delat idag</h2>';
		$result = get_shared_pages_for_period(strtotime("today"), time());
		output_shared_pages($result);
		echo "</div>";

		echo "<div class='most-shared-section most-shared-section--yesterday'>";	
		echo '<h2>Mest delat igår</h2>';
		$result = get_shared_pages_for_period(strtotime("yesterday 00:00"), strtotime("yesterday 24:00"));
		output_shared_pages($result);
		echo "</div>";
	
		#echo '<h2>Mest delat en vecka tillbaka</h2>';
		#echo date( "Y-m-d", strtotime("-1 week") );
		#$result = get_shared_pages_for_period( strtotime("-1 week"), strtotime("yesterday 00:00") );
		#output_shared_pages($result);

	}
	
	?>
	</div>
		
</div>
