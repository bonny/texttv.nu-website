<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://texttv.nu/min/?f=css/fonts.css,css/styles.css,css/texttvpage.css&amp;v=10">
    <title>SVT Text Live Laravel</title>
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
    <h1>SVT Text live-vy</h1>

    <p>Sidorna hämtas direkt från <a href="https://www.svt.se/text-tv/100">svt.se/text-tv</a>.</p>

    <p>Gissad titel: {{$title}}</p>

    <div class="live-wrap">
        <nav class="live-nav">
            <ul>
                <li><a href="/live/100">100 <span>Nyheter</span></a></li>
                <li><a href="/live/200">200 <span>Ekonomi</span></a></li>
                <li><a href="/live/300">300 <span>Sport</span></a></li>
                <li><a href="/live/330">330 <span>Resultatbörsen</span></a>
                </li>
                <li><a href="/live/377">377 <span>Målservice</span></a></li>
                <li><a href="/live/400">400 <span>Väder</span></a></li>
                <li><a href="/live/500">500 <span>Blandat</span></a></li>
                <li><a href="/live/600">600 <span>På TV</span></a></li>
                <li><a href="/live/700">700 <span>Innehåll</span></a></li>
                <li><a href="/live/800">800 <span>UR</span></a></li>
            </ul>
        </nav>

        <ul class="live-pages">
            <li data-sida=100 class='one-page TextTVPage'>
                <ul class='inpage-pages'>
                    @foreach ($importer->subpages() as $subpage)
                        <li>
                            {!! $subpage['text'] !!}
                        </li>
                    @endforeach
                </ul>
            </li>
        </ul>
    </div>

</html>
