Nytt importskript skrivet i Laravel för att klara av ändringarna som SVT gjorde våren 2021.

## Kör lokalt

Starta med `$ php artisan serve`.

Testa lokalt på http://localhost:8000/ (inte 127.0.0.1 pga. misstolkar som sidnummer)

### Tester

Köra tester och kör igen när en fil ändras:

    $ vendor/bin/phpunit-watcher watch

## Att göra

-   [ ] Fixa länkar med callback pga då borde vi lättare kunna både ignorera + godkänna nummer på en rad.
-   [ ] Bättre titel på 401, typ "Väder i dag/i morgon"
-   https://github.com/bonny/texttv.nu-website/issues

## Diverse

Hämta meta-info från SVT:s JSON-data, t.ex.:

`$ http https://www.svt.se/text-tv/100 | pup '#__NEXT_DATA__ text{}' | jq .props.pageProps.meta.updated`

```

```
