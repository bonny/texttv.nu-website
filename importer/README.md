Nytt importskript skrivet i Laravel för att klara av ändringarna som SVT gjorde våren 2021.

Starta med `$ php artisan serve`.

## Att göra

-   [ ] Egen domän för import/laravel l.texttv.nu
-   [ ] Uppdatera logik för gissad titel
-   [x] Sätt varannan färg på 100
-   [x] Hitta titel för sida
-   [ ] Köra command för artisan
    -   [x] Skriva ut sida live från SVT - `php artisan texttv:db 100`
    -   [x] Skriva ut sida från DB - `php artisan texttv:livepage 100`
    -   [ ] Importera sida eller sidintervall
        -   [x] Avgöra om en uppdatering av sidan skett (diff)
        -   [x] Spara till DB
            -   [x] med compress
            -   [x] som serialiserad
-   [x] Kör som cron
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

## Importanteckningar

### Importsteg

1. Hämta in senaste texten för aktuella sidor
2. Hämta in sidorna från svt
3. Jämför

```sql

# Hämta datumen då sidor senast uppdatedes
SELECT page_num, MAX(date_updated) as date_updated
FROM texttv
WHERE page_num IN(100, 200)
GROUP BY page_num

# Jämföra med
.props.pageProps.meta.updated
?
Obs att meta.updated inte verkar innebära att sidan faktiskt uppdaterats.
Det kanske är senast next uppdatera sidan?

// Prepare sql statement that we re-use for all inserts
$stmt_add_page = $mysqli->prepare(
    "INSERT INTO texttv(page_num, page_content, date_added, date_updated, next_page, prev_page, title)
    VALUES (?, COMPRESS(?), ?, ?, ?, ?, ?)"
);

// Get all existing page_contents from db for all the pages we want to check remote data on
// This is so we can compare current stored data with remote data, and actually only
// save a new version if remote has changed
$arr_page_nums_to_update = array();
$stmt_select_page_contents = $mysqli->prepare(
    "SELECT id, page_num, date_updated, UNCOMPRESS(page_content)
    FROM texttv WHERE page_num = ?
    ORDER BY date_updated
    DESC LIMIT 1"
);

$ http https://www.svt.se/text-tv/100 | pup '#__NEXT_DATA__ text{}' | jq .props.pageProps.meta.updated

```
