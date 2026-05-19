# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

This repo contains **two independent PHP applications** that together power https://texttv.nu (a modern web UI for SVT's Text TV). They live side-by-side but are deployed separately:

- `texttv.nu/` — public-facing website. **CodeIgniter** (PHP), bundled in-tree under `texttv.nu/codeigniter/` (both `application/` and `system/`). Entry point is `texttv.nu/index.php`.
- `importer/` — backend that scrapes pages from `https://www.svt.se/text-tv/` and stores them in MySQL. **Laravel 8** app (PHP 7.4/8.x). The website reads from the same DB the importer writes to.
- `Bruno API files/` — API request collection for Bruno (https://www.usebruno.com/). Use it instead of curl/Postman for poking the API.

The two apps share a database but communicate **only through the database** — there is no in-process or HTTP coupling between them.

## Architecture

### Importer (`importer/`) — writes pages

A scheduler (`app/Console/Kernel.php`) runs `texttv:import {pageNumber}` on different intervals depending on how often a page range changes (news every 2 min, weather every 30 min, weekly for static pages, etc.). Single source of import logic:

- `app/Console/Commands/texttvimport.php` — `texttv:import` artisan command. Accepts ranges like `100`, `100-110`, or `110,120-130`.
- `app/Classes/Importer.php` — fetches HTML from `svt.se/text-tv/{n}`, extracts the `__NEXT_DATA__` JSON blob, then runs a fluent pipeline: `fromRemote()->cleanup()->colorize()->linkify()`.
- `app/Models/TextTV.php` — Eloquent model for the `texttv` table. Page contents are stored **gzcompressed + serialized** in `page_content` (see the `gzuncompress(substr(..., 4))` call in `routes/web.php` for the read shape, and `UNCOMPRESS()` in CodeIgniter's `texttv_page` model).
- Other commands: `texttv:cleanup-page-actions`, `texttv:cleanup-old-pages`, `import-status:remove-old`. All wired into the schedule.
- Routes (`routes/web.php`) expose debug views: `/live/{pageNum}` (re-fetches from SVT live), `/db/{pageNum}` (renders newest DB row), `/pagecolors/{pageNum}`, `/importstatus`.

### Website (`texttv.nu/`) — reads pages

CodeIgniter routes (`codeigniter/application/config/routes.php`) map URL patterns directly to controllers. The interesting bits:

- `(\d{3})` and `([0-9,\-]+)` → `sida/visa/$1` — **the core URL shape**. Single page (`/100`), comma list (`/100,300`), and ranges (`/101-103`) are all parsed by `Texttv_page::extract_pages_from_ranges()`.
- Archive routes use the format `/{pageRange}/{slug}-{db_id}` (the trailing numeric id is the DB row id, not the page number).
- `default_controller = sida` — root URL renders pages `100,300,401,101-105` as a "startpage" composite.
- `controllers/api.php` and `routes` `api/*` — JSON API consumed by the iOS/Android apps and `appembed` route.
- Service worker (`texttv.nu/service-worker.js`) + `manifest.json` make the site installable as a PWA.

### Database config has two environments in one file

`texttv.nu/codeigniter/application/config/database.php` branches on `$_SERVER['DB_USERNAME']` (live, set by nginx/php-fpm) vs `HTTP_HOST === 'texttv.nu.test'` (local Valet). There are **two DB connections**: `default` (page content) and `stats` (pageviews + share counts). When adding queries that need stats, use `$this->load->database('stats', TRUE)`.

## Local development

The whole project assumes **Laravel Valet** on macOS, with `texttv.nu/` served at `http://texttv.nu.test/` and the importer served at its own Valet hostname (or via `artisan serve`).

### Website (CodeIgniter)
1. Park `texttv.nu/` in Valet so `http://texttv.nu.test/` serves it.
2. Create local MySQL DBs `texttv_nu` and `texttv_stats` (root, no password — see `database.php`).
3. Populate `texttv_nu` by running the importer at least once (see below).

### Importer (Laravel)
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

### Tests (importer only — the CodeIgniter app has no test suite)
```bash
cd importer
vendor/bin/phpunit                                    # full suite
vendor/bin/phpunit --filter SomeTest                  # single class/method
vendor/bin/phpunit-watcher watch                      # rerun on file change
```

Test fixtures (saved SVT HTML) live in `importer/tests/TestPages/`.

## Deployment

Both apps deploy automatically via GitHub Actions on push to `main`, gated by **path filter**:
- `.github/workflows/deploy-website.yml` fires only on changes under `texttv.nu/**`.
- `.github/workflows/deploy-importer.yml` fires only on changes under `importer/**`.

Each workflow uses a **matrix** to deploy to two servers (`current-server` + `hetzner-server`) via SCP + SSH. The importer post-deploy step runs `composer install` and `php artisan optimize`. There is no staging environment — `main` is production. Branch out and merge deliberately.

## Conventions and gotchas

- **Comments and code are in Swedish.** Match the surrounding language when adding comments, commit messages, or user-facing strings. Variable names and identifiers are usually English.
- **Page numbers are always 3 digits** (100–999). The router's `(\d{3})` regex is the source of truth — anything else falls through to other routes (textsida, blogg, etc.).
- The importer's CodeIgniter sibling (`texttv.nu/codeigniter/application/models/texttv_page.php`) reads `page_content` via SQL `UNCOMPRESS(...)`. The Laravel side does the inverse with PHP's `gzuncompress(substr(..., 4))`. **Don't change the storage format on one side without the other.**
- The importer scheduler is the only thing that should write to `texttv` in production. Running `texttv:import` locally against a shared DB will pollute it.
- The PHP setup-action in CI pins **PHP 7.4**, but the importer's `composer.json` allows `^7.3|^8.0` and `readme.md` recommends 8.1 locally. Code that requires 8.x syntax will break the deploy build.
