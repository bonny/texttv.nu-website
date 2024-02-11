## Text TV importskript

Nytt importskript skrivet i Laravel för att klara av ändringarna som SVT gjorde våren 2021.

## Kör med PHP 8

`valet use` to use correct PHP version
`valet composer install`
`valet composer update`
`/opt/homebrew/opt/php@8.1/bin/php artisan serve --host=localhost`
`/opt/homebrew/opt/php@8.1/bin/php artisan schedule:run`
`/opt/homebrew/opt/php@8.1/bin/php artisan texttv:import 100`

## Kör lokalt

Starta med `$ valet php artisan serve --host=localhost`.

Testa lokalt på http://localhost:8000/ (inte 127.0.0.1 pga. misstolkar som sidnummer).

Kör import med `$ php artisan schedule:run` eller `$ valet php artisan texttv:import 100`.

### Tester

Köra tester och kör igen när en fil ändras:

    $ vendor/bin/phpunit-watcher watch

## Att göra

-   https://github.com/bonny/texttv.nu-website/issues

## Deploy

Deploy till live görs automatic via GitHub actions när man pushar till `main`.

## Diverse

Hämta meta-info från SVT:s JSON-data, t.ex.:

`$ http https://www.svt.se/text-tv/100 | pup '#__NEXT_DATA__ text{}' | jq .props.pageProps.meta.updated`
