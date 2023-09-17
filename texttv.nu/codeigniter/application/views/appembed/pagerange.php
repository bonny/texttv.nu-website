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
	<style>
		body {
			margin: 0;
		}

		.appembed {
			background-color: #111;
			font-family: "Ubuntu Mono", 'Courier New', Courier, monospace;
			display: flex;
			flex-direction: column;
			justify-content: center;
		}

		.appembed {
			font-size: clamp(1px, 4.2vw, 2rem);
		}

		.appembed .pages {
			margin: 0;
			padding: 0;
			list-style: none;
		}

		.one-page {
			margin-top: 4rem;
			margin-bottom: 4rem;
		}

		.TextTVPage .root,
		.TextTVPage .inpage-pages {
			text-align: center;
		}

		footer {
			color: #eee;
			border-top: 1px solid #6d6c80;
			padding-top: 1em;
			margin-top: 1.5em;
		}

		footer ul {
			margin: 0;
		}

		footer li {
			padding: .2rem 0;
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

	<footer>
		<ul>
			<?php
			/**
			 * Output last modified date and fetch date (to detect caches and old pages).
			 * 
			 * Example output:
			 * "Ändrad: 2023-09-17 12:23"
			 * "Hämtad: 2023-09-17 12:23"
			 */
			$now = new DateTime('now', new DateTimeZone('Europe/Stockholm'));
			echo "<li>Hämtad " . $now->format('Y-m-d H:i:s') . '</li>';

			// Get max value of date_updated_unix from all pages.
			$max_date_updated_unix = max(array_map(function ($page) {
				return $page->date_updated_unix;
			}, $pages));

			// Convert to DateTime object.
			if ($max_date_updated_unix) {
				$max_date_updated = new DateTime('@' . $max_date_updated_unix, new DateTimeZone('Europe/Stockholm'));
				echo "<li>Ändrad " . $max_date_updated->format('Y-m-d H:i:s') . '</li>';
			}
			?>
		</ul>
	</footer>

	<script>
		function addPostMessageLinkListener() {
			// Bail if ReactNativeWebView is not defined.
			// if (typeof window.ReactNativeWebView === 'undefined') {
			// 	return;
			// }

			// postMessage to React Native when a link is clicked.
			let links = document.querySelectorAll('a');
			links.forEach(link => {
				link.addEventListener('click', e => {
					e.preventDefault();

					const href = link.href;
					const pageRange = href.match(/\/(\d{3}(-\d{3})?)/)[1];

					const data = {
						href: href,
						text: link.innerText,
						pageRange: pageRange
					};

					if (typeof window.ReactNativeWebView !== 'undefined') {
						window.ReactNativeWebView.postMessage(JSON.stringify(data));
					} else {
						console.log('postMessage data', data);
					}
				});
			});
		}

		addPostMessageLinkListener();
	</script>

</body>

</html>