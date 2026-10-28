# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

This repo contains **two independent PHP applications** that together power https://texttv.nu (a modern web UI for SVT's Text TV). They live side-by-side but are deployed separately:

- `texttv.nu/` — public-facing website. **CodeIgniter** (PHP), bundled in-tree under `texttv.nu/codeigniter/` (both `application/` and `system/`). Entry point is `texttv.nu/index.php`.
- `importer/` — backend that scrapes pages from `https://www.svt.se/text-tv/` and stores them in MySQL. **Laravel 8** app (PHP 8.1+, prod runs 8.2). The website reads from the same DB the importer writes to.
- `Bruno API files/` — API request collection for Bruno (https://www.usebruno.com/). Use it instead of curl/Postman for poking the API.

The two apps share a database but communicate **only through the database** — there is no in-process or HTTP coupling between them.

## Architecture

### Importer (`importer/`) — writes pages

A scheduler (`app/Console/Kernel.php`) runs `texttv:import {pageNumber}` on different intervals depending on how often a page range changes (news every 2 min, weather every 30 min, weekly for static pages, etc.). Single source of import logic:

- `app/Console/Commands/texttvimport.php` — `texttv:import` artisan command. Accepts ranges like `100`, `100-110`, or `110,120-130`.
- `app/Classes/Importer.php` — fetches HTML from `svt.se/text-tv/{n}`, extracts the `__NEXT_DATA__` JSON blob, then runs a fluent pipeline: `fromRemote()->cleanup()->colorize()->linkify()`.
- `app/Models/TextTV.php` — Eloquent model for the `texttv` table. Page contents are stored **gzcompressed + serialized** in `page_content` (see the `gzuncompress(substr(..., 4))` call in `routes/web.php` for the read shape, and `UNCOMPRESS()` in CodeIgniter's `texttv_page` model).
- Other commands: `texttv:cleanup-page-actions`, `texttv:cleanup-old-pages`, `import-status:remove-old`. All wired into the schedule.
- Routes (`routes/web.php`) expose debug views: `/live/{pageNum}` (re-fetches from SVT live), `/db/{pageNum}` (renders newest DB row), `/importstatus`.

#### SVT:s data och hur importern bygger sidan

Varje undersida i `__NEXT_DATA__` (`props.pageProps.subPages[]`) har tre fält som importern använder. Inget av dem är sidan som den visas:

- **`altText`**: texten, men utan blockgrafiken och ofta med kortare rader än 40 tecken (på 100 blir "▬▬▬ 130-133 ▬▬▬" bara `" 130-133  "`). `cleanup()` fyller ut till 24 rader × 40 tecken. På 100 placeras rader som bara innehåller sidnummer med hjälp av `imageMap`, övriga rader centreras eller fylls ut till höger. Hårdkodade justeringar per sida finns i `alignLineTexts()`.
- **`gifAsBase64`**: SVT:s renderade bild, 520×400 px, där varje tecken är **13×16 px** (40 kolumner, 25 rader varav importern använder 24). `colorize()` (via `TeletextCharsExtractor`) läser färgen och blockgrafiken för varje cell ur bilden och lägger dem som spans på tecknen från `altText`. Därför måste texten stå i exakt rätt kolumn. Ligger en rad en kolumn fel hamnar siffror i grafikens spans och färgbytena mitt i ord (så blev "1" en egen vit span före "30-133", 2026-10-28).
- **`imageMap`**: `<AREA COORDS="x1,y1,x2,y2" HREF="130">` för varje sidnummer SVT länkar, i bildens pixlar. Kolumnen är `x1/13` och raden `y1/16`. Det är facit för var sidnumren står (`CleanupTest`).

I text-tv tar en färgändring en egen teckencell, som visas som mellanslag (en "spacing attribute"). Ett färgbyte ligger därför alltid i en tom cell mellan grafiken och texten, aldrig mitt i ett tal hos SVT. Delar våra spans ett tal är texten felplacerad. `linkifySingleLine()` klarar det ändå: talet flyttas till den span som har flest av dess tecken och blir en enda länk.

### Website (`texttv.nu/`) — reads pages

CodeIgniter routes (`codeigniter/application/config/routes.php`) map URL patterns directly to controllers. The interesting bits:

- `(\d{3})` and `([0-9,\-]+)` → `sida/visa/$1` — **the core URL shape**. Single page (`/100`), comma list (`/100,300`), and ranges (`/101-103`) are all parsed by `Texttv_page::extract_pages_from_ranges()`.
- Archive routes use the format `/{pageRange}/{slug}-{db_id}` (the trailing numeric id is the DB row id, not the page number).
- `default_controller = sida` — root URL renders pages `100,300,401,101-105` as a "startpage" composite.
- `controllers/api.php` and `routes` `api/*` — JSON API consumed by the **TextTV.nu iOS app** ([App Store id `607998045`](https://apps.apple.com/se/app/texttv-nu/id607998045)) and **Android app** ([Play Store `com.mufflify.TextTVnu`](https://play.google.com/store/apps/details?id=com.mufflify.TextTVnu)). The same API also powers the `appembed` route (webview-friendly HTML wrapper used inside the apps for specific page-types). The apps are **separate codebases** in the sibling repo `../texttv.nu (app)` (Ionic React + Capacitor; see `../CLAUDE.md` for the endpoint-to-app-file map) — and they're live in stores and a backend change that breaks the API will break shipped app versions in users' hands.
- Service worker (`texttv.nu/service-worker.js`) + `manifest.json` make the site installable as a PWA.
- `controllers/status.php` — `/status` (public, noindex status page: import health, hourly import results today, archive growth, page views; file cache 60 s in `/tmp/texttv-status.json`) and `/status/health` (JSON, 200, or 503 when `page_imports_logs` has no row in the last 10 min). Pulsetic monitors `/status/health`. Needs `SELECT` on `page_imports_logs` for `texttv_web` (granted 2026-10-28, see todo #30). Locally the import runs every 15 min, so the page shows "står still" between runs.

### Database config has two environments in one file

`texttv.nu/codeigniter/application/config/database.php` branches on `$_SERVER['DB_USERNAME']` (live, set by nginx/php-fpm) vs `HTTP_HOST === 'texttv.nu.test'` (local Valet). There are **two DB connections**: `default` (page content) and `stats` (pageviews + share counts). When adding queries that need stats, use `$this->load->database('stats', TRUE)`.

## Local development

Two supported setups: **Docker Compose** (rekommenderas — portabelt, prod-parity) eller **Laravel Valet** (macOS, äldre flödet).

### Docker Compose (rekommenderas)

`git clone && make up` ger hela stacken: importer (Laravel 8) + scheduler + website (CodeIgniter) + MariaDB 10.11, allt på `serversideup/php:8.2-fpm-nginx` (matchar prods PHP 8.2.31, se `server.md`). En engångs-`init`-service migrerar och seedar startsidans sidor (`100,300,401,101-105`) live från SVT, så sajten renderar direkt.

```bash
make up        # starta allt (bygger images auto första gången; init seedar DB:n)
make build     # bygg om images efter ändrad Dockerfile (make rebuild = utan cache)
# Sajt:        http://texttv.test:8380/100   ·  startsida: http://texttv.test:8380/
# Importer:    http://localhost:8381  (debug-rutter /db/{n}, /live/{n}, /importstatus)
make import RANGE=100-110   # importera fler sidor
make artisan CMD="schedule:list"
make mysql     # MariaDB-shell (texttv_nu)
make logs      # följ loggar (LOG_CHANNEL=stderr → allt i docker logs)
make down      # stoppa (behåller data)   ·   make destroy = + radera volym
```

Filer: `compose.yaml` (bas) + `compose.override.yaml` (dev: portar, bind-mounts, opcache-tweaks), `deploy/{importer,website}/Dockerfile`, `deploy/mariadb/init/*.sql`, `Makefile`, `.env.example` (allt har defaults — `.env` är valfritt).

**Schedulern är opt-in.** `make up` startar den INTE (compose-profil `scheduler`). `schedule:work` kör hela prod-schemat och skrapar svt.se var 2:e minut dygnet runt — onödig SVT-last från varje dev-maskin. Starta den bara när du testar import-flödet: `make scheduler`.

**Lokalimporten är PÅ** (tjänsten `lokalimport`, sedan 2026-10-19): 100–199, 300–399 och 400–401 var 15:e minut, 402–899 en gång i timmen (`LOKALIMPORT_MINUTER` i `.env`). Den ger lokalt dagens och gårdagens nyheter till dagslistorna och startsidans "Mest läst" utan prod-schemats last. Logg: `docker compose logs lokalimport`. Visningsstatistiken (`page_actions`) kommer inte från importen; `tests/api/setup.sql` skapar den för idag och igår.

**Efter ändrade importer-beroenden:** `make build` räcker inte för `init`/`lokalimport`/`scheduler`, som behåller sin gamla anonyma `vendor/`-volym. Starta om dem med `docker compose up -d -V <tjänst>`.

**Docker-specifika gotchas:**
- **Webbens DB-creds injiceras som `fastcgi_param`** (i `deploy/website/Dockerfile`), inte som container-env — serversideup kör php med `variables_order="GPCS"` så env hamnar aldrig i `$_SERVER`, och CI:s `database.php` läser bara `$_SERVER`. Detta aktiverar CI:s "live"-gren mot host `mariadb`. Ändrar du DB-creds i `.env` måste webben byggas om (`make rebuild`).
- **Stats-DB:n (`texttv_stats`) nås inte i Docker.** `database.php` hårdkodar `hostname = 'localhost'` för stats-anslutningen (rad 73), vilket pekar på website-containern, inte `mariadb`. Endast analytics-skrivning (`page_actions`, `controllers/api.php`) påverkas — aldrig sidrendering eller övriga API:t. Vill du ha stats lokalt: ändra raden till `$_SERVER['DB_HOSTNAME'] ?? 'localhost'` (prod-säkert).
- **`/api/most_read` behöver förberedd data lokalt.** Queryn i `texttv_helper.php` hårdkodar prod-namnen `` `texttv.nu`.texttv `` och `texttv_stats.page_actions` på default-anslutningen, och `page_actions` är tom. `tests/api/setup.sql` skapar vyn och behörigheterna och seedar `page_actions` för idag och igår (körs automatiskt av `make test-api`, annars `docker compose exec -T mariadb mariadb -uroot -proot < tests/api/setup.sql`). Vyn kan inte läggas i `deploy/mariadb/init/`, eftersom tabellen skapas först av importerns migrationer. Svaret cachas 60 s i `/tmp/texttv-most-read/` i website-containern; töm den mellan manuella tester.
- **Webbens PHP och MariaDB kör svensk tid**, som prod: `PHP_DATE_TIMEZONE: Europe/Stockholm` för `website` (och `make test-api`:s jämförelsecontainer i `tests/api/run.sh`), `TZ: Europe/Stockholm` för `mariadb` i `compose.yaml`. Imagerna har annars UTC, medan `date_added` sparas i svensk tid; då hamnar dygnsgränserna i listorna och `NOW()`-fönstren två timmar fel. Webbcontainerns systemklocka (`date`) är fortfarande UTC.
- **OPcache är PÅ för webben** (`compose.override.yaml`), tvärtemot vad man väntar i dev. CI:s bundlade `core/URI.php` avger en PHP 8.2 compile-time-warning som med opcache av läcker in i varje `/api/*`-svar (ogiltig JSON för iOS/Android-apparna). Prod tystar den via `error_reporting` i `codeigniter/index.php` (bara fatala fel i production); lokalt, där den grenen är `E_ALL`, gör opcache det. `validate_timestamps=1 + revalidate_freq=0` → kodändringar läses ändå om direkt.
- **Webb-ägda hjälptabeller** (`texttv_page_text`, `texttv_blogg`, `texttv_log`) finns inte i importerns migrationer (de bor bara i prod). De skapas tomma av `deploy/mariadb/init/02-website-tables.sql` så rendering inte kraschar på "table doesn't exist".
- **Block-grafiken (`storage/chars/*.gif`) serveras av importern, inte webben.** `Importer.php` bakar in `asset()`-URL:en i sidinnehållet vid import, så `APP_URL` (compose-default `http://localhost:8381`) måste peka på importerns nåbara host:port — annars blir URL:en `http://localhost/...` och grafiken 404:ar. Ändrar du importer-porten: uppdatera `APP_URL` **och** re-importera (`make seed`) så redan lagrade sidor får rätt URL.

### Valet (alternativ, macOS)

The Valet flow assumes **Laravel Valet** on macOS, with `texttv.nu/` served at `http://texttv.nu.test/` and the importer served at its own Valet hostname (or via `artisan serve`).

#### Website (CodeIgniter)
1. Park `texttv.nu/` in Valet so `http://texttv.nu.test/` serves it.
2. Create local MySQL DBs `texttv_nu` and `texttv_stats` (root, no password — see `database.php`). **Note:** prod uses `texttv.nu` (with a literal dot) for the page-content DB, not `texttv_nu` — the local underscore-name is a Valet-friendly alias. See `server.md` for the actual prod DB-name location.
3. Populate `texttv_nu` by running the importer at least once (see below).

#### Importer (Laravel)
```bash
cd importer
valet use 8.1                                  # or another supported PHP
valet composer install
valet php artisan serve --host=localhost       # use localhost, NOT 127.0.0.1 — Valet routes 127.0.0.1/100 as if 100 were a page

# One-off import:
valet php artisan texttv:import 100
valet php artisan texttv:import 100-110

# Run the full schedule once (mimics cron):
valet php artisan schedule:run
```

### Tests

**Website API (`make test-api`).** The CodeIgniter app has no unit tests. Instead `tests/api/run.sh` runs every request in `tests/api/urls.txt` against two versions of the code at once, the working tree on :8380 and a git ref (default `origin/main`, i.e. prod; `make test-api BASE=HEAD~1`) in a throwaway container on :8382, both against the same local DB. It fails if status, `Content-Type`, CORS/cache headers or the body differ by a single byte. Each request is sent twice to the working tree, so file caches are tested both cold and warm. Run it before pushing any change under `texttv.nu/` that touches `api.php`, its helpers or models; if a change to the response is intended, the diff it prints is the review of that change. Add a line to `urls.txt` when the app starts calling something new. `/api/page` (stats write) and `/api/share`/`screenshot` are not covered. Three requests are flagged as invalid JSON in both versions: JSONP (expected), and PHP deprecation notices that only print in the local development mode.

**PHPStan (`make phpstan`).** Static analysis of both apps, run in the project's own images (PHP 8.2 like prod). The tool lives in `tools/phpstan/`, outside the deployed folders, so it neither deploys nor triggers a deploy. The website runs at level 1 (`website.neon`: CodeIgniter's constants in `website-bootstrap.php`, `$this->load`/`->db` allowed via `universalObjectCratesClasses`, undefined variables ignored in views since they come from `extract()`), the importer at level 5 (`importer.neon`, with `importer/` mounted over the image and the image's `vendor/` kept). Old errors are in `website-baseline.neon` and `importer-baseline.neon`, so only new code is reported. Run it before pushing PHP changes. Regenerate a baseline (`make phpstan-baseline`) only when old errors have been fixed or deliberately accepted. The importer has no Larastan, so Eloquent static calls (`TextTV::where()`) are reported as undefined; they are in the baseline.

**Importer (PHPUnit):**
```bash
cd importer
vendor/bin/phpunit                                    # full suite
vendor/bin/phpunit --filter SomeTest                  # single class/method
vendor/bin/phpunit-watcher watch                      # rerun on file change
```

Test fixtures (saved SVT HTML) live in `importer/tests/TestPages/`.

I Docker-stacken finns ingen `vendor/` på värden. Kör i containern: `docker compose exec -T importer php vendor/bin/phpunit`. Ögonblicksbilder och facit skrivs om med miljövariabler, granska sedan `git diff`:

- `ImportTest::test_pipeline_snapshots`: hela kedjan `cleanup()->colorize()->linkify()` för varje testsida, i `TestPages/snapshots/`. Skrivs om med `-e UPDATE_SNAPSHOTS=1`. En ny testsida hämtas med `curl https://www.svt.se/text-tv/{n}` och läggs till i listan.
- `LinkifyTest::test_corpus`: 3 259 riktiga rader från 286 sidor (2026-10-17 och 2026-10-28) med de länkar de ska ge. Skrivs om med `-e UPDATE_LINKIFY_CORPUS=1`. Nya rader kan tas ur lokala DB:n med länkarna borttagna (`UNCOMPRESS(page_content)`, unserialize, ta bort `<a>`-taggarna).
- `CleanupTest`: sidnumren på 100 står i samma kolumn som i SVT:s `imageMap`.
- `ScheduleTest`: schemat i `app/Console/Kernel.php` som JSON (`TestPages/snapshots/schedule.json`): cron-uttryck, jobb, lås, bakgrund och om `between`/`unlessBetween` släpper igenom klockan 12 och 03. Skrivs om med `-e UPDATE_SNAPSHOTS=1`. Ersätter diff av `schedule:list`, vars format ändras mellan Laravel-versionerna.
- `CharsStorageTest`: varje `storage/chars/{hash}.gif` som sidan pekar på finns på disk, och ett skrivfel kastar. Flysystem 3 (Laravel 9+) returnerar bara `false`, så `local`-disken behöver `'throw' => true` då.
- `Feature/DebugRoutesTest`: `/live`, `/db` och `/importstatus` (läser lokala DB:n). `/pagecolors/{n}` togs bort 2026-10-28 (gav 500 sedan bildtaggarna per tecken försvann).

Skriv testerna före ändringen och låt den gamla koden skriva facit. Då visar diffen efter ändringen exakt vad som ändrats.

## Deployment

Both apps deploy automatically via GitHub Actions on push to `main`, gated by **path filter**:
- `.github/workflows/deploy-website.yml` fires only on changes under `texttv.nu/**`.
- `.github/workflows/deploy-importer.yml` fires only on changes under `importer/**`.

Each workflow uses a **matrix** to deploy via SCP + SSH. Currently only `hetzner-server` är aktiv (`current-server` togs bort i 04ea660/795a4f2). Importerns post-deploy-steg kör `composer install` och `php artisan optimize`. Det finns inget staging — `main` är produktion. Branch:a och merge:a medvetet.

Production runtime details (PHP version, OPcache, nginx HTTP-cache, deploy-flöde, cache-gotchas vid post-deploy-verifiering) finns i [`server.md`](server.md). Läs den när du jobbar med deploy, prod-debugging eller cache-frågor — inte annars.

## Conventions and gotchas

- **Comments and code are in Swedish.** Match the surrounding language when adding comments, commit messages, or user-facing strings. Variable names and identifiers are usually English.
- **Page numbers are always 3 digits** (100–999). The router's `(\d{3})` regex is the source of truth — anything else falls through to other routes (textsida, blogg, etc.).
- The importer's CodeIgniter sibling (`texttv.nu/codeigniter/application/models/texttv_page.php`) reads `page_content` via SQL `UNCOMPRESS(...)`. The Laravel side does the inverse with PHP's `gzuncompress(substr(..., 4))`. **Don't change the storage format on one side without the other.**
- The importer scheduler is the only thing that should write to `texttv` in production. Running `texttv:import` locally against a shared DB will pollute it.
- The importer requires **PHP 8.1+** (`composer.json`, since 2026-10-14; `spatie/laravel-ignition` and its deps need it). The deploy workflow's `setup-php` step pins 7.4, but nothing in the workflow runs PHP: it copies files and runs `composer install` on the server (PHP 8.2). Lock-file changes therefore resolve against prod's PHP, not CI's. Update dependencies inside the importer container (`docker compose exec -u root importer composer ...`; as `www-data` it cannot write `vendor/`) and avoid `-W`, which upgrades every package.
- **App-impact check for server-side changes.** The iOS/Android apps consume `/api/*` JSON endpoints (see Architecture section). Any change that touches **nginx headers, CORS, Content-Type, response shape, or status codes** on those endpoints can silently break shipped app versions — and users can't auto-upgrade away from it. Before changing nginx config, response headers (esp. `Cache-Control`, `Content-Type`, CORS), or API responses, verify against a real device or at least curl against `https://api.texttv.nu/api/...` or `https://texttv.nu/api/...`. The Bruno collection (`Bruno API files/`) is the canonical source of expected API requests.
- **AdSense data via `adsense-mcp`: always filter on the domain.** The MCP server is registered user-wide and the AdSense account is shared with brottsplatskartan.se. Add `filters: ["DOMAIN_NAME==texttv.nu"]` to every report, otherwise the numbers mix both sites. Combining the domain filter with a `PLATFORM_TYPE_CODE` filter returns an empty result; use `PLATFORM_TYPE_NAME` as a dimension instead. Setup and review of the server: `Brottsplatskartan/brottsplatskartan-web/todos/done/116-adsense-mcp.md`.
