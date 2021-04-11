<div class="textcontent">

	<?php

	$sql = sprintf('
		SELECT * FROM texttv_page_text 
		WHERE 
			pagedescription <> "fakta-start"
			AND pagedescription LIKE "fakta%%"
		ORDER BY title ASC
	');

	$query = $this->db->query($sql);
	echo "<ul>";

	printf(
		'<li><a href="%1$s">%2$s</a></li>',
		"http://texttv.nu/text-tv-fakta/", // 1
		"Text TV: Fakta och historik" // 2
	);

	foreach ( $query->result() as $row ) {
		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			"http://texttv.nu/text-tv-fakta/" . preg_replace('/^fakta-/', '', $row->pagedescription), // 1
			$row->title // 2
		);
	}
	echo "</ul>";

	$sql = sprintf('SELECT * FROM texttv_page_text WHERE pagedescription=%1$s LIMIT 1', $this->db->escape( "fakta-{$slug}"));
	$res = $this->db->query($sql);
	$row = $res->row();

	if ( $row ) {

		printf('<h1>%1$s</h1>', $row->title);
		printf('%1$s', $row->text);
		
	}	
	?>

</div>