<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://texttv.nu/min/?f=css/fonts.css,css/styles.css,css/texttvpage.css&amp;v=10">
    <title>SVT Text DB Laravel</title>
    <style>
        body {
            margin: 2rem;
        }

        .live-wrap {
            display: flex;
        }

        .live-nav a {
            display: block;
            padding: .25rem;
            white-space: nowrap;
        }

        .live-nav,
        .live-pages {
            margin: 2rem;
        }
    </style>
</head>

<body>
    <h1>SVT Text db-vy</h1>

    <p>Sidorna hämtas från databasen.</p>

    <p>Titel: {{$title}}.</p>
    <p>Date_added: {{$date_added}}.</p>

    <div class="live-wrap">
        <nav class="live-nav">
            <ul>
                <li><a href="/db/100">100 <span>Nyheter</span></a></li>
                <li><a href="/db/200">200 <span>Ekonomi</span></a></li>
                <li><a href="/db/300">300 <span>Sport</span></a></li>
                <li><a href="/db/330">330 <span>Resultatbörsen</span></a>
                </li>
                <li><a href="/db/377">377 <span>Målservice</span></a></li>
                <li><a href="/db/400">400 <span>Väder</span></a></li>
                <li><a href="/db/500">500 <span>Blandat</span></a></li>
                <li><a href="/db/600">600 <span>På TV</span></a></li>
                <li><a href="/db/700">700 <span>Innehåll</span></a></li>
                <li><a href="/db/800">800 <span>UR</span></a></li>
            </ul>
        </nav>

        <ul class="live-pages">
            <li data-sida=100 class='one-page TextTVPage'>
                <ul class='inpage-pages'>
                    @foreach ($pageContent as $subpage)
                        <li>
                            {!! $subpage !!}
                        </li>
                    @endforeach
                </ul>
            </li>
        </ul>
    </div>

</html>
