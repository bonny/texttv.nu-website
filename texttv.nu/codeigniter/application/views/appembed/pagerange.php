<?php

/**
 * View for app embed.
 * 
 * Parameters:
 * $page: The first page in the range.
 * $pages: An array of texttv_page-objects.
 * $pagenum: The page number(s) as a string.
 * $pagedescription: A description of the page(s).
 */
?>
<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<title>TextTV.nu</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<base href="/appembed/" />
	<link rel="stylesheet" href="/css/fonts.css">
	<link rel="stylesheet" href="/css/texttvpage.css">
	<!-- <link rel="stylesheet" href="/min/?f=css/fonts.css,css/styles.css,css/texttvpage.css&amp;v=16"> -->
	<style>
		.appembed {
			background-color: #111;
			font-family: "Ubuntu Mono", 'Courier New', Courier, monospace;
			display: flex;
			justify-content: center;
		}

		.appembed {
			font-size: clamp(.75rem, 4vw, 2rem);
		}

		.appembed .pages {
			margin: 0;
			padding: 0;
			list-style: none;
		}
	</style>
</head>

<body class="appembed">
	<section>
		<ul class="pages">
			<?php
			foreach ($pages as $one_page_obj) {
				$page_output = $one_page_obj->get_output();
				
				// Make absolute links relative so they can go
				// to this page instead of the root of the site.
				// E.g. convert link "<a href="/401">401</a>"
				// to <a href="401">401</a>.
				//
				// Example links to convert:
				// <a href="/401">401</a>
				// <a href="/403">403f</a>
				// <a href="/109-110">109-110</a>

				$page_output = preg_replace('/href="\/(\d{3})/', 'href="$1', $page_output);

				echo $page_output;
			}
			?>
		</ul>
	</section>
</body>

</html>