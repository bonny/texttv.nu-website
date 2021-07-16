<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>TextTV Laravel</title>

    </head>
    <body>
        <h1>TextTV laravel</h1>

        <p>Existing routes:</p>
        <ul>
            <li><a href="/live/100">/live/100</a></li>
            <li>
                <a href="/live/100?withdebug=1">/live/100?withdebug=1</a>
                <br/>Visa id för bilder + slå inte samman element, används för att lägga till/ta bort grafik-idn.
            </li>
            <li><a href="/db/100">/db/100</a></li>
            <li><a href="/pagecolors">/pagecolors</a></li>
        </ul>
    </body>
</html>
