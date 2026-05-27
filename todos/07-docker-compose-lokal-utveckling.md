**Status:** aktiv
**Senast uppdaterad:** 2026-05-27

# Todo #07 — Docker Compose för lokal utveckling

## Sammanfattning

**Huvudmål: portabel setup.** Lokal utveckling sker idag via Laravel Valet på macOS — fungerar bara på en Mac med Valet förinstallerat, och Pär upplever det jobbigt (2026-05-27). Med Docker Compose blir det `git clone && docker compose up` på vilken maskin som helst.

Bonuseffekt: prod-parity. PHP-versionen lokalt (8.1 via Valet) skiljer sig från prod (8.2.31 på Hetzner enligt `server.md`). Versions-driften är inte teoretisk — buggen #129 / facade/ignition som tog ner importer-schedulern 2026-05-27 berodde på PHP 8.2-deprecation som aldrig syntes i 8.1.

Mönstret kopieras från `~/Projects/Personal/brottsplatskartan/compose.yaml`, som använder `serversideup/php`-imagen och har körts smidigt i produktion länge.

## Bakgrund

- Två oberoende PHP-appar i repo:t (`importer/` Laravel 8, `texttv.nu/` CodeIgniter). Per CLAUDE.md kommunicerar de bara genom DB:n.
- Prod kör nginx + php-fpm + MariaDB 10.11 enligt `server.md`. DB-credentials sätts via nginx `fastcgi_param` — inte `.env`.
- CodeIgniter:s `application/config/database.php` switchar på *exakt* `HTTP_HOST === 'texttv.nu.test'` (lokalt) eller `$_SERVER['DB_USERNAME']` (prod). Den exakta hostname-strängen är hård att ändra utan att röra koden — accepteras som constraint.
- DB-namn skiljer prod/lokalt: prod = `texttv.nu` (med punkt), lokalt = `texttv_nu` (underscore).
- Brottsplatskartan-projektet använder `serversideup/php:8.4-fpm-nginx` + `install-php-extensions` (mlocati). Patternet med app+scheduler-services som delar samma byggda image är beprövat.

## Förslag

### Image-val

`serversideup/php:8.2-fpm-nginx` (bookworm-baserad, matchar prods PHP 8.2.31). Pinna minor (`8.2`), inte patch. `install-php-extensions` finns inbakad för enkel extension-hantering.

### Service-struktur (4 services, ingen separat nginx)

`serversideup/php:8.2-fpm-nginx` har nginx + php-fpm internt — vi får prod-likt beteende utan att handskriva nginx-config.

```yaml
services:
  importer:           # Laravel-appen (l.texttv.nu lokalt)
  scheduler:          # Samma image som importer, command: schedule:work
  website:            # CodeIgniter (texttv.nu.test)
  mariadb:            # mariadb:10.11 (matchar prod)
```

### Dockerfile

En `Dockerfile` (eller två om importer/website behöver olika extensions) som extender serversideup-imagen:

```dockerfile
FROM serversideup/php:8.2-fpm-nginx
USER root
RUN install-php-extensions intl bcmath gd zip exif
USER www-data
```

Extensions att installera (verifierat behov):
- `intl` — Laravel Carbon-lokalisering
- `bcmath` — Laravel default
- `gd` (med jpeg+freetype) — share-image-generering
- `zip` — composer
- `exif` — bildmetadata
- `pdo_mysql`, `mbstring`, `xml`, `dom`, `opcache` — ingår normalt i serversideup-basen, verifiera

Använd `mlocati/docker-php-extension-installer` (`install-php-extensions`) — den löser apt-deps åt sig själv.

### Hostname-fälla

`texttv.nu.test`-strängen är hårdkodad i `codeigniter/application/config/database.php`. Compose-nginx (inbakad i serversideup) måste ha `server_name texttv.nu.test;`, och användare måste lägga `/etc/hosts`-rad:

```
127.0.0.1 texttv.nu.test l.texttv.nu.test
```

Bryter mildt mot "git clone && up"-löftet, men alternativet är värre (dev-bekvämlighet i prod-kod).

### Volymer

- `./importer:/var/www/html` (importer-service) — bind-mount, men exkludera `vendor/` via anonymous volume så host-tomma `vendor/` inte skuggar imagen:
  ```yaml
  volumes:
    - ./importer:/var/www/html
    - /var/www/html/vendor
  ```
- `./texttv.nu:/var/www/html` (website-service) — samma pattern för composer-deps om någon.
- `mariadb-data` som **named volume**, inte bind-mount (`./.docker/mysql-data` är en macOS-fälla).

### Env-konfiguration

`.env.example` i repo:t med säkra defaults:

```env
DB_HOST=mariadb
DB_DATABASE=texttv_nu
DB_USERNAME=root
DB_PASSWORD=root
DB_DATABASE_STATS=texttv_stats
APP_ENV=local

# serversideup AUTORUN-flaggor för importer
AUTORUN_ENABLED=true
AUTORUN_LARAVEL_STORAGE_LINK=true
AUTORUN_LARAVEL_CONFIG_CACHE=false  # falska under dev, vi vill se config-ändringar direkt
SSL_MODE=off
LOG_OUTPUT_LEVEL=info
PHP_OPCACHE_ENABLE=0  # 0 lokalt, prod kör 1
```

### Scheduler-container

Kopiera mönstret från brottsplatskartan:

```yaml
scheduler:
  image: texttv-importer:local  # samma som importer-servicen
  command: ["php", "/var/www/html/artisan", "schedule:work"]
  healthcheck:
    disable: true  # imagen:s default-check är för fpm/nginx, inte vår process
  depends_on:
    mariadb:
      condition: service_healthy
```

`schedule:work` finns i Laravel 8.x och hanterar `schedule:run` internt varje minut. Inget supercronic, ingen vanlig cron — single-process, signal-aware, loggar till stdout.

### Seed-strategi

- **Committa en mini-dump** i `seed/texttv-mini.sql.gz` (sidor 100, 300, 401, 101-105 — det som startsidans default-render använder). ~1 MB.
- `make seed` (eller motsvarande) auto-loadar dumpen vid första `up`.
- **Inte prod-dump** — `texttv_stats.page_actions` innehåller pageview-data som vi inte vill ha i utvecklingsklon.
- Alternativt: `init`-service som kör `artisan texttv:import 100-105` mot SVT live vid första start.

### Healthchecks och dependencies

```yaml
mariadb:
  healthcheck:
    test: ["CMD", "healthcheck.sh", "--su-mysql", "--connect", "--innodb_initialized"]
    interval: 10s
    timeout: 5s
    retries: 5

importer:
  depends_on:
    mariadb:
      condition: service_healthy
```

Annars croppar första migration när importer startar innan DB är redo.

### Loggning

`LOG_CHANNEL=stderr` i `.env` för importer-servicen → loggar via `docker compose logs`, inte till `storage/logs/laravel.log` på bind-mounten. Förhindrar att vi replikerar prods 8 GB-laravel.log-problem lokalt.

### .dockerignore

```
**/vendor/
**/node_modules/
.git/
**/storage/logs/*
.env
.idea/
.vscode/
```

### Onboarding

`Makefile` med targets:
- `make up` — `docker compose up -d`
- `make down` — `docker compose down`
- `make seed` — load mini-dump
- `make import <range>` — `docker compose exec importer php artisan texttv:import <range>`
- `make tinker` — `docker compose exec importer php artisan tinker`
- `make mysql` — `docker compose exec mariadb mysql -u root -proot texttv_nu`

`make` är universellt installerat — `just` skulle vara trevligare men ger en extra installations-step som motverkar portabilitets-målet.

### Steg-för-steg implementation

1. Skapa `compose.yaml` + `Dockerfile` baserat på mallar från brottsplatskartan.
2. `.env.example` med säkra defaults.
3. `.dockerignore`.
4. `Makefile` med onboarding-targets.
5. `seed/texttv-mini.sql.gz` (export från lokal Valet-DB innan migrering).
6. Verifiera att `make up && make seed && open http://texttv.nu.test/100` ger rendrad sida.
7. Köra `make import 100-105` och se nya rader i DB.
8. Uppdatera CLAUDE.md med Compose-instruktioner. Lämna Valet-instruktionerna kvar som alternativ tills setupen är beprövad.

## Risker

- **Prod-DB-katastrof**: Om någon av misstag pekar lokal scheduler mot prod-DB börjar den skriva varannan minut. Mitigering: hård guard i `Importer::importPage()` eller motsvarande som kollar `APP_ENV !== 'production'` om `DB_HOST` matchar prod-hostname. Egentligen borde prod-DB:n bara nås från Hetzner-IP via firewall — verifiera att den inte är öppen mot world.
- **Hostname-bindning**: `/etc/hosts`-steget bryter mildt mot "git clone && up"-löftet. Måste dokumenteras tydligt i CLAUDE.md / README.
- **macOS-volym-prestanda**: Sedan Docker Desktop bytte till VirtioFS (~2023) är `:delegated`/`:cached` deprecated/no-op och skippa dem. För `vendor/` använder vi ändå anonymous volume — slipper 20k småfiler korsa fs-bridgen.
- **Watchdog från prod (`*/30 * * * * pkill texttv:import`) finns inte lokalt.** Om en import-PHP hänger lokalt växer det. Lägg `->withoutOverlapping()` på schedule-jobben i `Kernel.php` som rätt fix (löser även prod-symptomet).
- **MariaDB version-mismatch**: brottsplatskartan kör `mariadb:11`, prod kör 10.11.14. Använd `mariadb:10.11` lokalt för att inte få oväntade SQL-syntaxskillnader.
- **DB-namn-fällan**: Lokalt heter DB:n `texttv_nu` (underscore), prod `texttv.nu` (punkt). Compose `MARIADB_DATABASE: texttv_nu` är rätt — switchen i CodeIgniter:s `database.php` hanterar resten.
- **Image-utgivning**: serversideup/php är beroende av att paketet underhålls. Aktivt projekt, men en risk att vara medveten om. Officiella `php:8.2-fpm-bookworm` är fallback.

## Confidence

Hög — patternet är beprövat (brottsplatskartan har kört det smidigt), image-valet är konkret och motiverat, och alla "ladiga" punkter (hostname, seed, prod-DB-guard) är identifierade istället för att lämnas till "vi får se".
