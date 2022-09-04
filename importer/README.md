Nytt importskript skrivet i Laravel för att klara av ändringarna som SVT gjorde våren 2021.

## Kör lokalt

Starta med `$ php artisan serve --host=localhost`.

Testa lokalt på http://localhost:8000/ (inte 127.0.0.1 pga. misstolkar som sidnummer).

### Tester

Köra tester och kör igen när en fil ändras:

    $ vendor/bin/phpunit-watcher watch

## Att göra

-   [ ] Uppdatering av 100, 377 och andra sidor går för långsamt, sidor står som "Inte i sändning" ofta.
    -   [ ] Använda https://github.com/spatie/laravel-short-schedule
    -   [ ] https://freek.dev/1743-how-to-schedule-commands-to-run-every-few-seconds-in-a-laravel-app
-   [ ] Lägg på klass om tecken har bakgrund
-   [ ] Fixa länkar med callback pga då borde vi lättare kunna både ignorera + godkänna nummer på en rad.
-   [ ] Då många små fyrkantiga rutorna på testsida 777 blir inte bra, nåt med background/cover/contain
-   https://github.com/bonny/texttv.nu-website/issues

## Deploy

Deploy till live görs automatic via GitHub actions när man pushar till `main`.

## Diverse

Hämta meta-info från SVT:s JSON-data, t.ex.:

`$ http https://www.svt.se/text-tv/100 | pup '#__NEXT_DATA__ text{}' | jq .props.pageProps.meta.updated`
