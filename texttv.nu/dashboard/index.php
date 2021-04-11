<?php
$time = time();

$pageNum = (int)$_GET['page'];
$fontSize = (int)$_GET['fontsize'];

if (!$pageNum) {
	$pageNum = 100;
}

if (!$fontSize) {
	$fontSize = 17;
}

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<title>Dashboard - TextTV.nu</title>
	<!-- <link href='https://fonts.googleapis.com/css?family=Roboto+Slab:400,700,300' rel='stylesheet' type='text/css'> -->
	<link href='https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900' rel='stylesheet' type='text/css'>
	<link href='//fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet'>
	<style>
		*,
		*:before,
		*:after {
			-moz-box-sizing: border-box;
			-webkit-box-sizing: border-box;
			box-sizing: border-box;
		}

		html,
		body {
			margin: 0;
			background-color: rgb(220, 220, 220);
			color: rgba(0, 0, 0, 0.87);
		}

		.box-texttv {
			background-color: rgb(17, 30, 63);
		}

		.texttv-latest-news {
			font-family: "ubuntu mono", monospace;
			font-size: <?= $fontSize ?>px;
			/* 	letter-spacing: -.1ex; */
			white-space: pre;
			background: #111E3F;
			padding-top: .5em;
			text-align: center;
		}

		.texttv-latest-news .root {
			margin-left: -0.7em;
		}
	</style>
	<link rel="stylesheet" href="/css/texttvpage.css?time=<?= $time ?>">
</head>

<body>

	<div class="wrap">

		<div class="texttv-latest-news TextTVPage"></div>

	</div>

	<script src="jquery-2.2.1.min.js"></script>
	<script>
		function initTexttv() {
			getLatestTextTvNews();

			// setInterval(getLatestTextTvNews, 10);
		}

		function getLatestTextTvNews() {
			$.getJSON("https://api.texttv.nu/api/get/<?= $pageNum ?>?app=dashboard.rasptouch").then(
				function(data) {
					if (data && data[0] && data[0].content) {
						// on page 100 some lines are not content (text tv logo)
						var content = data[0].content.join();
						var lines = content.split("\n");

						lines = lines.slice(0, 1).concat(lines.slice(5, 20));

						$(".texttv-latest-news").html(lines.join("\n"));
					}
				}
			);
		}

		function d(str) {
			var debugElm = $(".debug-output");
			var html = debugElm.html() + "\n\n" + str;
			debugElm.html(html);
		}

		initTexttv();
	</script>

</body>

</html>