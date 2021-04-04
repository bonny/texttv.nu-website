<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://texttv.nu/min/?f=css/fonts.css,css/styles.css,css/texttvpage.css&amp;v=10">
    <title>SVT Text Live Laravel</title>
</head>

<body class="antialiased">
    <ul>
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

</html>
