# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

This repo contains **two independent PHP applications** that together power https://texttv.nu (a modern web UI for SVT's Text TV). They live side-by-side but are deployed separately:

- `texttv.nu/` — public-facing website. **CodeIgniter** (PHP), bundled in-tree under `texttv.nu/codeigniter/` (both `application/` and `system/`). Entry point is `texttv.nu/index.php`.
- `importer/` — backend that scrapes pages from `https://www.svt.se/text-tv/` and stores them in MySQL. **Laravel 13** app (PHP 8.3+, prod runs 8.4). The website reads from the same DB the importer writes to.
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

#### SVT's data and how the importer builds a page

Each subpage in `__NEXT_DATA__` (`props.pageProps.subPages[]`) has three fields the importer uses. None of them is the page as it is shown:

- **`altText`**: the text, but without the block graphics and often with lines shorter than 40 characters (on 100, "▬▬▬ 130-133 ▬▬▬" becomes just `" 130-133  "`). `cleanup()` pads it to 24 lines × 40 characters. On 100, lines that only contain page numbers are placed using `imageMap`; other lines are centered or padded on the right. Hard-coded adjustments per page live in `alignLineTexts()`.
- **`gifAsBase64`**: SVT's rendered image, 520×400 px, where each character is **13×16 px** (40 columns, 25 rows, of which the importer uses 24). `colorize()` (via `TeletextCharsExtractor`) reads each cell's color and block graphics from the image and applies them as spans on the characters from `altText`. So the text must sit in exactly the right column. If a line is off by one column, digits end up in the graphics' spans and color changes land mid-word (that is how "1" became its own white span before "30-133", 2026-10-28).
- **`imageMap`**: `<AREA COORDS="x1,y1,x2,y2" HREF="130">` for every page number SVT links, in image pixels. The column is `x1/13` and the row `y1/16`. It is the ground truth for where page numbers sit (`CleanupTest`).

**Graphics are recognized by a hash that depends on libgd.** `TeletextCharsExtractor` identifies graphics characters and double-height headlines by the crc32 of the cell encoded as GIF (`imagegif()`), and the GIF bytes depend on the libgd version. Bookworm's libgd rounded the colors (04/fc/fe), trixie's keeps 00/ff, so every hash changed when prod moved to Debian 13 (2026-10-31). `8ef16f6` added `getCharImagesHashesLibgdTrixie()`/`getCharHeadlineHashesLibgdTrixie()` next to the old lists, and `f47a85b` removed three single-color cells from the trixie list (they matched every empty cell). **Do not remove the old lists:** the local Docker image (`serversideup/php:8.4`) runs PHP's bundled libgd ("bundled (2.1.0 compatible)"), not Debian's libgd3 like prod, and it produces the same hashes as bookworm (checked 2026-10-31). Locally you therefore get a few more graphics cells than on prod (single-color cells, e.g. 166 vs 162 on 100). If libgd changes again (Debian 14, another PHP image), graphics can stop being recognized without anything failing: compare the number of `bgImg` and `line DH` in imported HTML against prod (`curl -s http://localhost:8381/live/100 | grep -o bgImg | wc -l`).

**The page title** (`title()`, the `title` column) is the first line with text after rows 0–1, the headline on news pages. On results and table pages (330–376, the World Cup groups 441–452 and others) that line is a match, a table row or "omgång 2". The page header on row 1 is used instead: "FOTBOLL Italien, Serie A" becomes "Fotboll: Italien, Serie A" (tables get ", tabell"). Rows before 2026-11-01 have the first match as title. A two-line headline followed by an empty line is joined (327). Body text, tables and tables of contents are not joined.

In teletext a color change takes its own character cell, shown as a space (a "spacing attribute"). So at SVT a color change always sits in an empty cell between graphics and text, never in the middle of a number. If our spans split a number, the text is misplaced. `linkifySingleLine()` handles it anyway: the number moves to the span holding most of its characters and becomes a single link.

### Website (`texttv.nu/`) — reads pages

CodeIgniter routes (`codeigniter/application/config/routes.php`) map URL patterns directly to controllers. The interesting bits:

- `(\d{3})` and `([0-9,\-]+)` → `sida/visa/$1` — **the core URL shape**. Single page (`/100`), comma list (`/100,300`), and ranges (`/101-103`) are all parsed by `Texttv_page::extract_pages_from_ranges()`.
- Archive routes use the format `/{pageRange}/{slug}-{db_id}` (the trailing numeric id is the DB row id, not the page number).
- `default_controller = sida` — root URL renders pages `100,300,401,101-105` as a "startpage" composite.
- `controllers/api.php` and `routes` `api/*` — JSON API consumed by the **TextTV.nu iOS app** ([App Store id `607998045`](https://apps.apple.com/se/app/texttv-nu/id607998045)) and **Android app** ([Play Store `com.mufflify.TextTVnu2`](https://play.google.com/store/apps/details?id=com.mufflify.TextTVnu2)). The older Android listing `com.mufflify.TextTVnu` still runs a 2016 app (`?app=texttvnu.android1`, signing key lost, cannot be updated); `models/texttv_page.php` `get_content_with_fixes()` shows it a "Denna app slutar snart fungera" notice on pages 100, 200, 300 and 800, so keep that working. The same API also powers the `appembed` route (webview-friendly HTML wrapper used inside the apps for specific page-types). The apps are **separate codebases** in the sibling repo `../texttv-nu-smartare` (Ionic React + Capacitor; see `../CLAUDE.md` for the endpoint-to-app-file map) — and they're live in stores and a backend change that breaks the API will break shipped app versions in users' hands.
- Service worker (`texttv.nu/service-worker.js`) + `manifest.json` make the site installable as a PWA.
- `controllers/status.php` — `/status` (public, noindex status page: import health, hourly import results today, archive growth, page views; file cache 60 s in `/tmp/texttv-status.json`) and `/status/health` (JSON, 200, or 503 when `page_imports_logs` has no row in the last 10 min). Pulsetic monitors `/status/health`. Needs `SELECT` on `page_imports_logs` for `texttv_web` (granted 2026-10-28, see todo #30). Locally the import runs every 15 min, so the page shows "står still" (stalled) between runs.

### Database config has two environments in one file

`texttv.nu/codeigniter/application/config/database.php` branches on `$_SERVER['DB_USERNAME']` (live, set by nginx/php-fpm) vs `HTTP_HOST === 'texttv.nu.test'` (local Valet). There are **two DB connections**: `default` (page content) and `stats` (pageviews + share counts). When adding queries that need stats, use `$this->load->database('stats', TRUE)`.

## Local development

Two supported setups: **Docker Compose** (recommended: portable, prod parity) or **Laravel Valet** (macOS, the older flow).

### Docker Compose (recommended)

`git clone && make up` gives the whole stack: importer (Laravel 13) + scheduler + website (CodeIgniter) + MariaDB 11.8, all on `serversideup/php:8.4-fpm-nginx` (matches prod's PHP 8.4.27 and MariaDB 11.8.6, see `server.md`). A one-off `init` service migrates and seeds the start page's pages (`100,300,401,101-105`) live from SVT, so the site renders right away.

```bash
make up        # start everything (builds images automatically the first time; init seeds the DB)
make build     # rebuild images after a Dockerfile change (make rebuild = without cache)
# Site:        http://texttv.test:8380/100   ·  start page: http://texttv.test:8380/
# Importer:    http://localhost:8381  (debug routes /db/{n}, /live/{n}, /importstatus)
make import RANGE=100-110   # import more pages
make artisan CMD="schedule:list"
make mysql     # MariaDB shell (texttv_nu)
make logs      # follow logs (LOG_CHANNEL=stderr → everything in docker logs)
make down      # stop (keeps data)   ·   make destroy = + delete the volume
```

Files: `compose.yaml` (base) + `compose.override.yaml` (dev: ports, bind mounts, opcache tweaks), `deploy/{importer,website}/Dockerfile`, `deploy/mariadb/init/*.sql`, `Makefile`, `.env.example` (everything has defaults; `.env` is optional).

**The scheduler is opt-in.** `make up` does NOT start it (compose profile `scheduler`). `schedule:work` runs the whole prod schedule and scrapes svt.se every 2 minutes around the clock: needless SVT load from every dev machine. Start it only when testing the import flow: `make scheduler`.

**The local import is ON** (the `lokalimport` service, since 2026-10-19): 100–199, 300–399 and 400–401 every 15 minutes, 402–899 once an hour (`LOKALIMPORT_MINUTER` in `.env`). It gives today's and yesterday's news locally for the day lists and the start page's "Mest läst" without the prod schedule's load. Log: `docker compose logs lokalimport`. View statistics (`page_actions`) do not come from the import; `tests/api/setup.sql` creates them for today and yesterday.

**After changing importer dependencies:** `make build` is not enough for `init`/`lokalimport`/`scheduler`, which keep their old anonymous `vendor/` volume. Restart them with `docker compose up -d -V <service>`. If packages were removed, first empty `importer/bootstrap/cache/*.php` (bind-mounted from the host, generated, ignored by git), or artisan crashes on a service provider that no longer exists (happened with Ignition at Laravel 13, 2026-11-01).

**Docker-specific gotchas:**

- **The website's DB credentials are injected as `fastcgi_param`** (in `deploy/website/Dockerfile`), not as container env: serversideup runs PHP with `variables_order="GPCS"`, so env never reaches `$_SERVER`, and CI's `database.php` only reads `$_SERVER`. This enables CI's "live" branch against host `mariadb`. If you change DB credentials in `.env`, rebuild the website (`make rebuild`).
- **The stats DB (`texttv_stats`) is not reachable in Docker.** `database.php` hard-codes `hostname = 'localhost'` for the stats connection (line 73), which points at the website container, not `mariadb`. Only analytics writes (`page_actions`, `controllers/api.php`) are affected, never page rendering or the rest of the API. For stats locally: change the line to `$_SERVER['DB_HOSTNAME'] ?? 'localhost'` (safe for prod).
- **`/api/most_read` needs prepared data locally.** The query in `texttv_helper.php` hard-codes the prod names `` `texttv.nu`.texttv `` and `texttv_stats.page_actions` on the default connection, and `page_actions` is empty. `tests/api/setup.sql` creates the view and grants and seeds `page_actions` for today and yesterday (run automatically by `make test-api`, otherwise `docker compose exec -T mariadb mariadb -uroot -proot < tests/api/setup.sql`). The view cannot go in `deploy/mariadb/init/`, because the table is only created by the importer's migrations. The response is cached 60 s in `/tmp/texttv-most-read/` in the website container; empty it between manual tests.
- **The website's PHP and MariaDB run on Swedish time**, like prod: `PHP_DATE_TIMEZONE: Europe/Stockholm` for `website` (and the comparison container of `make test-api` in `tests/api/run.sh`), `TZ: Europe/Stockholm` for `mariadb` in `compose.yaml`. The images otherwise use UTC while `date_added` is stored in Swedish time; the day boundaries in the lists and the `NOW()` windows then end up two hours off. The website container's system clock (`date`) is still UTC.
- **MariaDB 11.8 runs with prod's collations** (`command:` in `compose.yaml`): `utf8mb4_general_ci` and `character-set-collations` for utf8mb3/utf8mb4, not 11.8's default `uca1400`. `MARIADB_AUTO_UPGRADE=1` upgrades the system tables in an existing volume (10.11 → 11.8 went that way on 2026-10-31, without a new volume).
- **OPcache is ON for the website** (`compose.override.yaml`), contrary to what you'd expect in dev. CI's bundled `core/URI.php` emits a PHP 8.2+ compile-time warning that, with opcache off, leaks into every `/api/*` response (invalid JSON for the iOS/Android apps). Prod silences it via `error_reporting` in `codeigniter/index.php` (only fatal errors in production); locally, where that branch is `E_ALL`, opcache does it. `validate_timestamps=1 + revalidate_freq=0` → code changes are still picked up immediately.
- **Website-owned helper tables** (`texttv_page_text`, `texttv_blogg`, `texttv_log`) are not in the importer's migrations (they only exist in prod). `deploy/mariadb/init/02-website-tables.sql` creates them empty so rendering does not crash on "table doesn't exist".
- **The block graphics (`storage/chars/*.gif`) are served by the importer, not the website.** `Importer.php` bakes the `asset()` URL into the page content at import, so `APP_URL` (compose default `http://localhost:8381`) must point at the importer's reachable host:port, otherwise the URL becomes `http://localhost/...` and the graphics 404. If you change the importer port: update `APP_URL` **and** re-import (`make seed`) so already stored pages get the right URL.

### Valet (alternative, macOS)

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

**PHPStan (`make phpstan`).** Static analysis of both apps, run in the project's own images (PHP 8.4 like prod). The tool lives in `tools/phpstan/`, outside the deployed folders, so it neither deploys nor triggers a deploy. The website runs at level 1 (`website.neon`: CodeIgniter's constants in `website-bootstrap.php`, `$this->load`/`->db` allowed via `universalObjectCratesClasses`, undefined variables ignored in views since they come from `extract()`), the importer at level 5 (`importer.neon`, with `importer/` mounted over the image and the image's `vendor/` kept). Old errors are in `website-baseline.neon` and `importer-baseline.neon`, so only new code is reported. Run it before pushing PHP changes. Regenerate a baseline (`make phpstan-baseline`) only when old errors have been fixed or deliberately accepted. The importer has no Larastan, so Eloquent static calls (`TextTV::where()`) are reported as undefined; they are in the baseline.

**Importer (PHPUnit):**

```bash
cd importer
vendor/bin/phpunit                                    # full suite
vendor/bin/phpunit --filter SomeTest                  # single class/method
vendor/bin/phpunit-watcher watch                      # rerun on file change
```

Test fixtures (saved SVT HTML) live in `importer/tests/TestPages/`.

In the Docker stack there is no `vendor/` on the host. Run inside the container: `docker compose exec -T importer php vendor/bin/phpunit`. Snapshots and expected results are rewritten with environment variables; then review `git diff`:

- `ImportTest::test_pipeline_snapshots`: the whole `cleanup()->colorize()->linkify()` chain for every test page, in `TestPages/snapshots/`. Rewritten with `-e UPDATE_SNAPSHOTS=1`. A new test page is fetched with `curl https://www.svt.se/text-tv/{n}` and added to the list.
- `LinkifyTest::test_corpus`: 3,259 real lines from 286 pages (2026-10-17 and 2026-10-28) with the links they should produce. Rewritten with `-e UPDATE_LINKIFY_CORPUS=1`. New lines can be taken from the local DB with the links removed (`UNCOMPRESS(page_content)`, unserialize, strip the `<a>` tags).
- `CleanupTest`: the page numbers on 100 sit in the same column as in SVT's `imageMap`.
- `TitleTest`: the titles of 20 sports pages (303–377) fetched 2026-11-01, in `TestPages/titles/` without GIFs (only `__NEXT_DATA__` with alt text and image map). Expected results in `TestPages/snapshots/titles.json`, rewritten with `-e UPDATE_SNAPSHOTS=1`.
- `ScheduleTest`: the schedule in `app/Console/Kernel.php` as JSON (`TestPages/snapshots/schedule.json`): cron expressions, jobs, locks, background and whether `between`/`unlessBetween` let 12:00 and 03:00 through. Rewritten with `-e UPDATE_SNAPSHOTS=1`. Replaces diffing `schedule:list`, whose format changes between Laravel versions.
- `CharsStorageTest`: every `storage/chars/{hash}.gif` the page points to exists on disk, and a write error throws. Flysystem 3 (Laravel 9+) only returns `false`, so the `local` disk needs `'throw' => true` for that.
- `FromRemoteTest`: the fetch from SVT. A network error gets one retry after 1 s, an error status from SVT none. `tests/TestCase.php` runs `Http::preventStrayRequests()`, so a test without `Http::fake()` throws instead of fetching from svt.se.
- `Feature/DebugRoutesTest`: `/live`, `/db` and `/importstatus` (reads the local DB). `/pagecolors/{n}` was removed 2026-10-28 (it returned 500 after the per-character image tags disappeared).

Write the tests before the change and let the old code write the expected results. The diff after the change then shows exactly what changed.

## Deployment

Both apps deploy automatically via GitHub Actions on push to `main`, gated by **path filter**:

- `.github/workflows/deploy-website.yml` fires only on changes under `texttv.nu/**`.
- `.github/workflows/deploy-importer.yml` fires only on changes under `importer/**`.

Each workflow uses a **matrix** to deploy via SCP + SSH. Currently only `hetzner-server` is active (`current-server` was removed in 04ea660/795a4f2). The importer's post-deploy step runs `composer install` and `php artisan optimize`. There is no staging: `main` is production. Branch and merge deliberately.

Production runtime details (PHP version, OPcache, nginx HTTP cache, deploy flow, cache gotchas when verifying after a deploy) are in [`server.md`](server.md). Read it when working on deploys, prod debugging or cache questions, not otherwise.

## Conventions and gotchas

- **Comments and code are in Swedish.** Match the surrounding language when adding comments, commit messages, or user-facing strings. Variable names and identifiers are usually English.
- **Dates in chat replies to Pär:** day and short Swedish month, "2 nov (idag)", "3 nov (imorgon)", "1 nov (igår)", "4 nov". Never "11-03". Files keep ISO `YYYY-MM-DD`.
- **Page numbers are always 3 digits** (100–999). The router's `(\d{3})` regex is the source of truth — anything else falls through to other routes (textsida, blogg, etc.).
- The importer's CodeIgniter sibling (`texttv.nu/codeigniter/application/models/texttv_page.php`) reads `page_content` via SQL `UNCOMPRESS(...)`. The Laravel side does the inverse with PHP's `gzuncompress(substr(..., 4))`. **Don't change the storage format on one side without the other.**
- The importer scheduler is the only thing that should write to `texttv` in production. Running `texttv:import` locally against a shared DB will pollute it.
- The importer requires **PHP 8.3+** (`composer.json`, Laravel 13 since todo #15). Composer blocks every stable Laravel 9–11 release over security advisories, so intermediate majors cannot be installed with the default audit settings. The importer's deploy workflow has no `setup-php` step: it copies files and runs `composer install` on the server (PHP 8.4). Lock-file changes therefore resolve against prod's PHP, not CI's. Update dependencies inside the importer container (`docker compose exec -u root importer composer ...`; as `www-data` it cannot write `vendor/`) and avoid `-W`, which upgrades every package.
- **App-impact check for server-side changes.** The iOS/Android apps consume `/api/*` JSON endpoints (see Architecture section). Any change that touches **nginx headers, CORS, Content-Type, response shape, or status codes** on those endpoints can silently break shipped app versions — and users can't auto-upgrade away from it. Before changing nginx config, response headers (esp. `Cache-Control`, `Content-Type`, CORS), or API responses, verify against a real device or at least curl against `https://api.texttv.nu/api/...` or `https://texttv.nu/api/...`. The Bruno collection (`Bruno API files/`) is the canonical source of expected API requests.
- **AdSense data via `adsense-mcp`: always filter on the domain.** The MCP server is registered user-wide and the AdSense account is shared with brottsplatskartan.se. Add `filters: ["DOMAIN_NAME==texttv.nu"]` to every report, otherwise the numbers mix both sites. Combining the domain filter with a `PLATFORM_TYPE_CODE` filter returns an empty result; use `PLATFORM_TYPE_NAME` as a dimension instead. Setup and review of the server: `Brottsplatskartan/brottsplatskartan-web/todos/done/116-adsense-mcp.md`.
- **The Chrome extension** "TextTV.nu - nyheter från SVT Text" (ID `pjldiaepaamifgbmhgkcgbkjphkkcjgn`) is in the [Chrome Web Store](https://chromewebstore.google.com/detail/texttvnu-nyheter-fr%C3%A5n-svt/pjldiaepaamifgbmhgkcgbkjphkkcjgn?hl=en). Installs, views and users are in the [Developer Dashboard](https://chrome.google.com/webstore/devconsole/2de76af6-2b31-488f-af92-226be2db700b/pjldiaepaamifgbmhgkcgbkjphkkcjgn/edit) under Analytics. Claude in Chrome cannot read the Web Store pages (Chrome blocks extensions there); store page visits and installs are instead in GA4 property `481079543` via `analytics-mcp`. It only sees installs made from the store page, not ones pushed by an admin. The source code is not in this repo or the app repo, and the site does not link to the extension.
