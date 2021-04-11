<?php
/**
 * Textsnutt som är relevant för sidan, t.ex. om text tv på startsidan,
 * om 377 på 377 och om sport på landningsida för sport.
 * 
 * Tanken är att göra diverse landningssidor mer relevanta för Google
 */
//print_r($pages);
//print_r($pagenum);

// Array with page nums
// Array ( [0] => 100 [1] => 300 )
$arr_pages = texttv_page::extract_pages_from_ranges($pagenum);
$page_text = "";

// if ($this->input->get("show_page_text")) {
	
	/*
		# Tillgängliga pagedescriptions

		startpage = startsidan
		
	*/
	
	if ( empty($pagedescription) ) {
		$pagedescription = $pagenum;
	}
	
	$sql = sprintf('SELECT * FROM texttv_page_text WHERE pagedescription=%1$s LIMIT 1', $this->db->escape( $pagedescription ));
	#echo $sql;
	$res = $this->db->query($sql);
	$row = $res->row();
	if ( $row ) {
		$page_text = $row->text;
	}
	
// }

if ( $page_text ) {
?>
<aside class="page-text">
	<?php echo $page_text ?>
</aside>
<?php
}
