Local domain using Valet:  
http://text-tv-importer.test/

Or using `$ php artisan serve` then access at http://127.0.0.1:8000.

## Att göra

-   [ ] Spara till DB
    - [ ] med compress
-   [ ] Avgöra om en uppdatering av sidan skett (diff)
-   [x] Model för texttv-sida
-   [ ] Skriv tester
    -   [x] Hämta ut JSON från en HTML
    -   [x] Dekorera
-   [x] Hämta en sida
-   [x] Använda https://github.com/Rct567/DomQuery för att från en sida hämta `<script id="__NEXT_DATA__" type="application/json">...` som innehåller texter som `.altText`
-   [x] Föräldra den rena texten till det nuvarande format som texttv.nu använder
    -   [x] Färgad rad överst
    -   [x] Färgad rad längst ner
    -   [x] Specifika ändringar per sida

## Hur

Köra tester och kör igen när en fil ändras:

    $ vendor/bin/phpunit-watcher watch
