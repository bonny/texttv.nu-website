Local domain using Valet:  
http://text-tv-importer.test/

Or using `$ php artisan serve` then access at http://127.0.0.1:8000.

## Att göra

-   [x] Model för texttv-sida
-   [ ] Skriv tester
    -   [ ] Hämta ut JSON från en HTML
    -   [ ] Dekorera
    -   [ ] Spara till DB
    -   [ ] Avgöra om en uppdatering av sidan skett (diff)
-   [ ] Hämta en sida
-   [ ] Använda https://github.com/Rct567/DomQuery för att från en sida hämta `<script id="__NEXT_DATA__" type="application/json">...` som innehåller texter som `.altText`
-   [ ] Föräldra den rena texten till det nuvarande format som texttv.nu använder
    -   [ ] Färgad rad överst
    -   [ ] Färgad rad längst ner
    -   [ ] Specifika ändringar per sida

## Hur

Köra tester och kör igen när en fil ändras:

    $ vendor/bin/phpunit-watcher watch
