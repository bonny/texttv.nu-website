<?php
$time = time();
?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Dashboard - TextTV.nu</title>
    <!-- <link href='https://fonts.googleapis.com/css?family=Roboto+Slab:400,700,300' rel='stylesheet' type='text/css'> -->
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900' rel='stylesheet' type='text/css'>
	<link href='//fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css?time=<?= $time ?>">
    <link rel="stylesheet" href="/css/texttvpage.css?time=<?= $time ?>">
</head>

<body>

    <div class="wrap">

		<div class="col col-news">

			<!-- <div class="column-headline">
				<h2>TextTV.nu</h2>
			</div> -->
					
			<div class="box box-texttv">
				<div class="texttv-latest-news TextTVPage"></div>
			</div>

		</div>
	
		<div class="col col-webpage">

			<div class="column-headline">
				<h2>Webbsida</h2>
			</div>
	
	        <div class="box ga ga-visits-now">
	            <h2 class="ga-headline">Besökare just nu</h2>
	            <span class="ga-value"></span>
	        </div>
	
	        <div class="box ga ga-pageviews-today">
	            <h2 class="ga-headline">Sidvisningar idag</h2>
	            <span class="ga-value"></span>
	        </div>
	
	        <!-- <div class="box ga ga-pageviewsPerSession-today">
	            <h2 class="ga-headline">Sidvisningar / besök</h2>
	            <span class="ga-value"></span>
	        </div> -->

	        <div class="box ga ga-adsense">
	            <h2 class="ga-headline">AdSense</h2>
	            <span class="ga-value"></span>
	        </div>
	    
	    </div>

		<div class="col col-webpage">

			<div class="column-headline">
				<h2>App (Ios)</h2>
			</div>

	        <div class="box ga ga-visits-now-app">
	            <h2 class="ga-headline">Besökare just nu</h2>
	            <span class="ga-value"></span>
	        </div>
	
	        <div class="box ga ga-pageviews-today-app">
	            <h2 class="ga-headline">Sidvisningar idag</h2>
	            <span class="ga-value"></span>
	        </div>
	
	        <!-- <div class="box ga ga-pageviewsPerSession-today-app">
	            <h2 class="ga-headline">Sidvisningar / besök</h2>
	            <span class="ga-value"></span>
	        </div> -->
		</div>
	
        <div class="ga-date-updated">Uppdaterad: <span class="ga-date-updated-value"></span></div>

        <button id="auth-button" hidden>Authorize GA</button>

		<textarea class="api-error-output"></textarea>
		
		<textarea class="debug-output"></textarea>
		
		<a class="reload" href="https://texttv.nu/dashboard/"></button>
		<a class="close" href="#"></button>
    
    </div>
    
    <script src="jquery-2.2.1.min.js"></script>
    <script src="underscore.string.min.js"></script>
    <script src="script.js?time=<?= $time ?>"></script>
    <script src="https://apis.google.com/js/client.js?onload=authorize"></script>

</body>

</html>
