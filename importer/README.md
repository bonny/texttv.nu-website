Nytt importskript skrivet i Laravel för att klara av ändringarna som SVT gjorde våren 2021.

Starta med `$ php artisan serve`.

## Att göra

https://github.com/bonny/texttv.nu-website/issues

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
