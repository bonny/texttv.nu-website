# Production server (hetzner-server)

Detaljer om hur prod-servern är uppsatt. Läs när du jobbar med deploy, cache-beteende, eller prod-debugging — annars sällan relevant.

## Stack

- **Hostname:** `TextTV-hetzner`. Enda prod-servern (`current-server` togs bort i 04ea660/795a4f2). **2 vCPU, 7,6 GB RAM, 0 swap.** Disk: 75 GB, 24 % använt 2026-08-09 (var 52 % 2026-05-19 — loggstädningen i logrotate-fixen tog ner den).
- **SSH:** `ssh texttv.nu` loggar in som `root` (alias i `~/.ssh/config`). För löpande hälsokoll finns skillen [`prod-health`](.claude/skills/prod-health/SKILL.md) — den har verifierade kommandon och tröskelvärden för load, minne, php-fpm och importer-färskhet.
- **PHP 8.2.31** runtime (NTS, med Zend OPcache 8.2.31).
  - OBS: `.github/workflows/deploy-website.yml` använder PHP 7.4 — det är **bara** för CI-build-stegen (composer/phpunit), inte runtime.
  - `composer.json` tillåter `^7.3|^8.0`; lokalt rekommenderas 8.1 (Valet).
- **MariaDB 10.11.18** (inte MySQL — service-alias `mysql` finns men det är MariaDB som körs). Verifierad version 2026-08-09.
- **nginx + php-fpm + MariaDB** (LEMP). Systemd-enheterna heter exakt `nginx`, `php8.2-fpm`, `mariadb`.
- **Server paths:**
  - Website: `/usr/share/nginx/texttv.nu/`
  - **Importer:** `/usr/share/nginx/l.texttv.nu/importer/` — annan path än website, körs som separat Laravel-app på samma server.
- **Ingen CDN framför** (inga `cf-cache-status` eller `via`-headers i responsen).
- **DB-namn i prod:** `texttv.nu` (med punkt!) för sid-innehåll, `texttv_stats` för pageviews/share-counts. Lokalt heter motsvarande DB:s `texttv_nu` resp. `texttv_stats` — namn-skillnaden är dolt under `application/config/database.php`-logiken.
- **nginx-vhost för website:** `/etc/nginx/sites-enabled/texttv.nu`. Servar **fyra hostnames från samma server-block:** `texttv.nu`, `api.texttv.nu`, `hetzner.texttv.nu`, `api-hetzner.texttv.nu`. Alla träffar samma codeigniter-app.
- **TLS hanteras av Certbot** (`# managed by Certbot`-kommentarer i vhost). `listen 443 ssl`, `ssl_certificate*` och redirect-blocket för :80 är auto-genererade — undvik manuell redigering av just de raderna utan att förstå Certbot-renewal-effekten. Cert-paths: `/etc/letsencrypt/live/texttv.nu/`.
- **`fastcgi_hide_header "X-Powered-By"`** är aktivt i vhost — PHP-versionen läcker inte via response-header (men finns i `errors-in-console` om något PHP-fel slungar ut paths).

## Cache-lager (tre stycken)

Ordning efter påverkan på post-deploy-synlighet:

### 1. nginx HTTP-cache (huvudlagret)

Synligt i response-headers:

```
X-Cache: MISS    # inget i cachen, full PHP-render kördes
X-Cache: STALE   # gammal version servad, fresh fetch i bakgrunden (stale-while-revalidate)
```

**Faktisk nginx-config (verifierad 2026-05-19):**

```nginx
fastcgi_cache cachezone;
fastcgi_cache_key $scheme$host$request_uri$request_method;
fastcgi_cache_valid 4s;
fastcgi_cache_use_stale updating timeout invalid_header http_500;
fastcgi_cache_background_update on;
fastcgi_cache_lock on;
fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=cachezone:10m max_size=100m;
```

- **TTL: 4 sekunder.** Mycket kort — cache invalideras effektivt direkt efter deploy.
- **Cache-key:** scheme + host + request_uri + method. `$request_uri` inkluderar query string → `?cb=$RANDOM` ger nytt cache-entry → fresh render.
- **Stale-while-revalidate:** vid `updating | timeout | invalid_header | http_500` servas STALE från cachen medan re-render händer i bakgrunden. Första request post-deploy får ofta STALE; andra+ får fresh.
- **`fastcgi_cache_lock on`** — single-flight: bara en request renderar åt gången per cache-key, övriga väntar/får stale.
- **Cache-storage:** `/var/cache/nginx/` (10 MB keys, max 100 MB content).
- **OBS:** `fastcgi_cache_path` (zonen som de andra direktiven refererar till) ligger **inte** i sites-enabled-vhost:en. Den måste deklareras globalt — leta i `/etc/nginx/nginx.conf` eller `/etc/nginx/conf.d/*.conf` om du behöver verifiera/ändra storlek.

### 2. Zend OPcache (PHP-bytecode)

Faktisk config (verifierad 2026-05-19):

```ini
opcache.validate_timestamps = On
opcache.revalidate_freq = 2        ; sek mellan timestamp-checks
opcache.memory_consumption = 128   ; MB
opcache.max_accelerated_files = 10000
opcache.revalidate_path = Off
```

- **Innebär:** efter SCP picks OPcache upp nya filer inom ~2 sek. Inget manuellt `systemctl reload php8.2-fpm` behövs.
- Kombinerat med nginx 4s TTL → **total max-delay deploy → live: ~6 sekunder.** Det stämmer med empirisk observation från SEO-fixen 2026-05-19.

### 3. CodeIgniter output-cache

- `config.php`: `cache_path = ''` (default `system/cache/`).
- Endast **en** controller använder det: `sitemap.php` rad 14 — `$this->output->cache(10)` (10 min).
- Cache-mapp på server: `/usr/share/nginx/texttv.nu/codeigniter/application/cache/` (chmod 777 vid deploy).
- För sid-rendering: **inget** application-cache. Sidan renderas från DB varje gång nginx släpper igenom.

## php-fpm-poolen

Faktisk config i `/etc/php/8.2/fpm/pool.d/www.conf` (verifierad 2026-08-09):

```ini
user = www-data
listen = /run/php/php8.2-fpm.sock   ; unix-socket, backlog 4096
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

- **`pm.max_children = 5` är taket för samtidiga PHP-requests** — och poolen slår
  i det regelbundet i normaldrift (~var tionde minut 2026-08-09). Varje träff
  betyder att requests köade. Se todo **#12**.
- **Ingen status-sida — och den går inte att sätta upp.** `pm.status_path`
  provades 2026-08-10 och gav `File not found.` även med korrekt inläst config.
  Enda kända lösningen är att tömma `security.limit_extensions` (idag
  `.php .phar`), vilket inte är acceptabelt på en pool som servar publik PHP.
  Återställt. Mättnad syns istället som
  `WARNING: [pool www] server reached pm.max_children setting (5)` i
  `/var/log/php8.2-fpm.log` — tidsstämplat, vilket är mer användbart än
  statussidans kumulativa räknare.
- **Slowlog aktiv sedan 2026-08-10:** `/var/log/php8.2-fpm.slow.log`, tröskel
  `request_slowlog_timeout = 3s`. Ger PHP-stacktrace per långsam request.
  Roteras via `/etc/logrotate.d/php8.2-fpm-slowlog` (egen regel — den
  befintliga php8.2-fpm-regeln matchar bara literal sökväg, ingen glob).
- **Importern och webbplatsen delar pool.** Båda går mot
  `/run/php/php8.2-fpm.sock`, så en långsam `/live/{n}`-request på importern
  tar en av webbens fem workers. Se todo #12.

> ⚠️ **Lägg aldrig backupfiler i `/etc/nginx/sites-enabled/`.** Den inkluderas
> som `include /etc/nginx/sites-enabled/*;` — **utan** filändelse-filter, till
> skillnad från `conf.d/*.conf` och `pool.d/*.conf`. En `.bak`-fil där laddas
> som en extra vhost och ger dubbla server-block. Lägg backuper utanför
> `/etc/nginx/`, t.ex. `/root/config-backups-YYYY-MM-DD/`.
- Workers drar ~28 MB RES i snitt (max 37 MB), så minnet är inte den
  begränsande faktorn — 5 workers = ~110 MB av 7,6 GB.

## Deploy-flöde

`.github/workflows/deploy-website.yml` triggas av push till `main` med path-filter `texttv.nu/**`:

1. Checkout + PHP 7.4 setup (för composer-tool, ej runtime).
2. `scp` hela `texttv.nu/` → `/usr/share/nginx/` på hetzner-server.
3. Post-SSH:
    - `sudo chown -R root:root .`
    - `chmod 777 -R /shares`
    - `chmod 777 /codeigniter/application/cache/`
4. Inget explicit cache-flush eller php-fpm reload — OPcache picks up via timestamp-validering, nginx-cachen får MISS när TTL går ut.

## Cron / scheduler

`crontab -l` för root (user-cron och root-cron är identiska):

| Schema           | Kommando                                                                                         | Syfte                                     |
| ---------------- | ------------------------------------------------------------------------------------------------ | ----------------------------------------- |
| `* * * * *`      | `cd /usr/share/nginx/l.texttv.nu/importer && php artisan schedule:run` (max_execution_time=600)  | **Laravel-schemaläggaren** för importern  |
| `*/30 * * * *`   | `ps aux \| grep 'texttv:import' \| xargs -r kill -9`                                             | **Watchdog** — kill:a hängda import-PHPs (lades till 2024-08-11) |
| `45 01 * * *`    | `/root/texttv-tools/remove-old-share-images.js`                                                  | Nattlig städning av share-bilder          |

Inaktiverade rader (kommenterade ut): Twitter-poster (2023-08-25, Twitter dog), letsencrypt-renew (har egen i `/etc/cron.d/certbot`), mysql DELETE-jobs för icke-delade rader.

**Implikation:** importerns hela funktion bygger på `* * * * *`-raden. När en sida inte uppdateras, kolla först om schedulern faktiskt körs:

```bash
ssh texttv.nu 'journalctl -u cron --since "-10 min" --no-pager | grep schedule:run'
```

> ⚠️ Tidigare stod här `tail /var/log/syslog | grep CRON`. **Det fungerar inte — `/var/log/syslog` finns inte på servern** (rsyslog är inte installerat, allt går till journald). Använd `journalctl` enligt ovan.

## Loggar

| Path                                | Innehåll                       | Storlek (2026-08-09)               |
| ----------------------------------- | ------------------------------ | ---------------------------------- |
| `/var/log/nginx/access.log`         | HTTP access (alla requests)    | **1,3 GB** (hela `/var/log/nginx` 3,3 GB inkl. roterade) |
| `/var/log/nginx/error.log`          | nginx-fel                      | 287 KB                             |
| `/var/log/php8.2-fpm.log`           | php-fpm-fel + PHP `error_log`  | 6,6 KB (rotateras veckovis)        |
| `/var/log/php8.2-fpm.log.N.gz`      | Roterade php-fpm-loggar        | ~3-12 KB / vecka                   |
| MariaDB error log                   | _Ej i `/var/log/mysql/`_ — troligen via `journalctl -u mariadb` |                                    |
| Laravel app-log (importer)          | `/usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log` | 13 MB — **logrotate på plats** (`/etc/logrotate.d/laravel-texttv` + `texttv-importer`) |
| CodeIgniter app-log (website)       | `/usr/share/nginx/texttv.nu/codeigniter/application/logs/` | Tomt (bara `index.html`) — CI-logging avstängd eller inte konfigurerad |

> ✅ **8 GB-problemet med `laravel.log` är löst.** 2026-05-19 låg filen på 8,0 GB utan logrotate; 2026-08-09 är den 13 MB och roteras. Ingen åtgärd kvar.

**`access.log` har responstid sedan 2026-08-10.** `log_format anonymized` i `/etc/nginx/conf.d/general.conf` avslutas sedan dess med `rt=$request_time urt=$upstream_response_time cache=$upstream_cache_status`. Innan dess loggades ingen tid alls, så historik före 2026-08-10 saknas.

Första baslinjen (2026-08-10 kl 09, 6 484 requests): **p50 0,002 s · p95 0,006 s · p99 0,037 s · max 1,93 s.** `urt=-` betyder att svaret kom ur nginx-cachen utan att php-fpm rördes.

> ✅ **Dubbelloggningen är åtgärdad 2026-08-10.** Fram till dess fanns två `access_log`-direktiv mot samma fil — `nginx.conf:40` (default `combined`-format, **oanonymiserad ip**) och `general.conf:33` (`anonymized`). Båda låg på http-nivå, så nginx skrev en rad per direktiv och ip-maskeringen var verkningslös. `nginx.conf:40` är utkommenterad; loggning sker nu enbart via `general.conf`. Verifierat: 300/300 rader anonymiserade, 0 dubbletter. Detaljer i todo **#13**.
>
> **Loggar skrivna före 2026-08-10 innehåller råa ip:n.** De roteras ut automatiskt (`daily`, `rotate 14`) senast 2026-08-24.

Volym för referens: ~55 req/s (2026-08-09 kl 21). Före fixen gav det dubbelt så många loggrader.

## Cache-gotcha vid post-deploy-verifiering

Använd:

```bash
curl https://texttv.nu/<sida>?cb=$RANDOM | grep '<title>'
```

Utan cache-buster kan du få `X-Cache: STALE` (gammal HTML). Givet TTL=4s håller fönstret bara ett par sekunder per request — i praktiken syns nya versionen i löpande tester inom ~10s efter deploy. För Googlebot är detta irrelevant — botar träffar `MISS` när TTL går ut, så nya titlar/meta-tags blir indexerade inom timmar för högvolym-sidor.

## Vad som **inte** ligger i repot

- Full nginx site-config (vi har dock cache-snippet ovan)
- MariaDB `my.cnf` + tabellstorlek + index-info
- `.env` / DB-credentials. Sätts via `$_SERVER['DB_*']` av nginx `fastcgi_param`-direktiv i `/etc/nginx/sites-enabled/texttv.nu` (inom `location ~ \.php$`-blocket). Det är **den enda platsen** de bor — inte i `.env`, inte i någon fil i repot. Värt att veta: variabel-namn som sätts där är `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE_STATS`, `VIEW_PHPINFO_SECRET`.
- OPcache-tuning (memory_consumption, max_accelerated_files, revalidate_freq)
- Backup-strategi (om sådan finns)
- Error tracking / monitoring (om sådant finns)

Den konfigen lever bara på servern. Om du behöver granska den, SSH:a in och kolla `/etc/nginx/`, `/etc/php/8.2/fpm/`, `/etc/mysql/mariadb.conf.d/`.
