<?php
$is_single_blog_entry = $blogg_entries->num_rows === 1 ? true : false;

$classes = [
	'textcontent'
];

$classes[] = $is_single_blog_entry ? 'textcontent--single' : '';

if ($is_single_blog_entry) {
	$first_row = $blogg_entries->first_row();
}

?>
<div class="<?php echo implode(' ', $classes) ?>">
	<?php

	if ($is_single_blog_entry && $first_row) {

		printf(
			'
				<p class="textcontent-breadcrumb">
					<a href="/blogg/">TextTV utvecklingsblogg</a>
					»
					%1$s
				</p>
			',
			$first_row->title
		);
		
		// Output ld+json
		$date =  date('c', $first_row->date_published_unix)
		?>
		<script type="application/ld+json">
			{
				"@context": "http://schema.org",
				"@type": "BlogPosting",
				"author": "TextTV.nu",
				"name": "Text TV utvecklingsblogg",
				"datePublished": "<?php echo $date ?>",
				"dateModified": "<?php echo $date ?>",
				"mainEntityOfPage": true,
				"headline": <?php echo json_encode($first_row->title) ?>,
				"description": <?php echo json_encode(strip_tags($first_row->content)) ?>,
				"image": {
					"@type": "imageObject",
					"url": "https://texttv.nu/images/favicon-152.png",
					"height": "152",
					"width": "152"
				},
				"publisher": {
					"@type": "Organization",
					"name": "TextTV.nu",
					"logo": {
						"@type": "imageObject",
						"url": "https://texttv.nu/images/favicon-152.png"
					}
				}
			}
		</script>

		<?php

		
	} else {
		// Overview listing headline
		printf('
			<h1>TextTV utvecklingsblogg</h1>
			<p>På väg mot en bättre text-tv-upplevelse</p>
		');

	}


	foreach ($blogg_entries->result() as $row) {
	
		$printf_format = '';
		$cut_off = sprintf('<p class="textcontent-readmore"><a href="https://texttv.nu/blogg/%1$s">Läs hela inlägget</a></p>', $row->permalink);
		$content_excerpt = shorten_text(strip_tags($row->content), $max_length = 280, $cut_off, $keep_word = true);
	
		if ($is_single_blog_entry) {
			// Format for single blog page
			$printf_format = '
				<article>
					<h1>%1$s</h1>
					<p class="meta"><time datetime="%5$s">%2$s</time></p>
					%3$s
				</article>
			';
	
		} else {
			// Format for overview page
			$printf_format = '
				<article class="textcontent-overview-article">
					<h2><a href="/blogg/%4$s">%1$s</a></h2>
					<p class="meta"><time datetime="%5$s">%2$s</time></p>
					<div class="textcontent-blog-excerpt"><p>%6$s</p></div>
				</article>
			';			
	
		}
	
		printf(
			$printf_format, 
			$row->title, // 1
			ucfirst(date("j F Y", $row->date_published_unix)), // 2
			$row->content, // 3
			$row->permalink, // 4
			date('c', $row->date_published_unix), // 5
			$content_excerpt // 6
		);
		
	}
		
	?>
	
	<?php // added 28 aug 2016
	/*
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<!-- TextTV matchat innehåll -->
	<ins class="adsbygoogle"
	     style="display:block"
	     data-ad-client="ca-pub-1689239266452655"
	     data-ad-slot="9336879605"
	     data-ad-format="autorelaxed"></ins>
	<script>
	(adsbygoogle = window.adsbygoogle || []).push({});
	</script>
	*/
	?>

</div>