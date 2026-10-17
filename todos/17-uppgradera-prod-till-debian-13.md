**Status:** aktiv
**Senast uppdaterad:** 2026-10-17

# Todo #17 — Uppgradera prod till Debian 13 (trixie) och PHP 8.4

Utbruten ur #15 2026-10-17. Både Laravel 13 (kräver PHP 8.3) och bytet till
PHP 8.4 väntar på den här, så #15 är blockerad av den.

## Sammanfattning

Prod kör Debian 12 (bookworm) med Debians `php8.2`, MariaDB 10.11 och nginx
(se [`server.md`](../server.md)). Debian backporterar säkerhetsfixar till
bookworms PHP 8.2 till och med Debian LTS-slutet, **2028-06-30**. Det är den
verkliga deadlinen. Debian 13 har PHP 8.4 och MariaDB 11.8.

Förslag: **uppgradera befintlig server på plats** (`bookworm → trixie`), med
Hetzner-snapshot som ångra-knapp och en provkörning på en klon först. Ingen
brådska; gör det under 2027 och ta Laravel 13 i samma veva.

## Varför på plats och inte ny server

Först föreslogs en ny server bredvid (i #15). Omvärderat 2026-10-17:

- **Ångerbart ändå.** Servern ligger på Hetzner Cloud. En snapshot före
  uppgraderingen återställer exakt läget på minuter. Det som går förlorat är
  det som skrivits efter: importerade sidor (fylls på igen) och lite
  `page_actions`-statistik.
- **Mycket mindre jobb.** All konfig som inte finns i repot (vhost, nginx-cache,
  Certbot, cron, logrotate, MariaDB-behörigheter och vyn för `most_read`)
  stannar kvar. Ingen inventering, inget DNS-byte, ingen certflytt.
- **Debian stöder uppgraderingen officiellt**, och det här är en ren
  LEMP-maskin.
- Ny server hade gett test före byte och noll nertid. Testet får vi billigare
  med en klon från snapshoten. Nertiden (30–60 min i lågtrafik) är acceptabel.

## Plan

### 1. Provkörning på en klon

1. Ta en snapshot av prod i Hetzner-konsolen.
2. Skapa en ny server från snapshoten. **Stäng av cron direkt** (`systemctl
   stop cron`) så klonen inte skrapar SVT eller kör cleanup-jobb.
3. Kör uppgraderingen enligt steg 2 nedan på klonen, och anteckna allt som
   krånglar.
4. Verifiera med `make test-api`-urlerna mot klonens IP
   (`curl --resolve texttv.nu:443:<ip> ...`), plus en `texttv:import 100` för
   hand.
5. Radera klonen.

### 2. Uppgraderingen (samma steg på klon och prod)

Backup av `/etc/php/8.2/fpm/`, `/etc/nginx/`, `/etc/mysql/` och
`/etc/apt/` till `/root/config-backups-<datum>/` innan något ändras.

1. `apt update && apt full-upgrade` på bookworm så allt är aktuellt.
2. Byt `bookworm` → `trixie` i `/etc/apt/sources.list` och
   `sources.list.d/`. Ta bort MariaDB-blacklistningen i
   `/etc/apt/apt.conf.d/51unattended-upgrades-local` tillfälligt, annars hålls
   MariaDB kvar.
3. `apt update && apt upgrade --without-new-pkgs`, sedan `apt full-upgrade`.
4. **PHP 8.2 → 8.4:** poolkonfigen följer inte med. För över ändringarna från
   `/etc/php/8.2/fpm/pool.d/` (bl.a. `pm.max_children = 10` och slowlog från
   #12) och eventuella ini-ändringar (OPcache, `max_execution_time`) till
   `/etc/php/8.4/`. Byt socket i nginx-vhostens `fastcgi_pass`. Kontrollera
   att CLI-`php` är 8.4 (cron kör `php artisan`).
5. **MariaDB 10.11 → 11.8:** kör `mariadb-upgrade` efteråt om paketet inte
   gör det. Kontrollera `dpkg --audit` (tomt) — 10.11.18 → .19 hängde
   2026-10-16 mitt i postinst.
6. `composer install` i importern på nya PHP:n (lock-filen löses mot prod:s
   PHP, se `CLAUDE.md`).
7. Starta om och verifiera: `/api/get/100?cb=` svarar 200 med giltig JSON,
   startsidan renderar, `schedule:run` i cron-journalen, `prod-health`.
8. Lägg tillbaka MariaDB-blacklistningen, eller ta beslut om att släppa den.

### 3. Efteråt

- Uppdatera `server.md`, `prod-health`-skillen och Obsidian-noten
  `Server - texttv.nu Hetzner.md`: `php8.2-fpm` → `php8.4-fpm`, PHP- och
  MariaDB-versioner, sökvägar under `/etc/php/`.
- Byt dev-stacken till `serversideup/php:8.4` och MariaDB 11.8 för prod-paritet.
- Ta bort den gamla snapshoten när det gått en vecka utan problem.
- Avblockera #15 (Laravel 13).

## Risker

- **MariaDB-hoppet är den största risken.** Testas i provkörningen.
- **Appen har ingen staging** och shipped app-versioner kan inte uppdateras:
  `/api/*` måste svara identiskt. Både webben och importern är provade på
  PHP 8.4 i Docker (se [#15](15-uppgradera-eol-ramverken.md)), med bara
  deprecation-notiser i development-läget. Kontrollera att de inte läcker ut i
  API-svaren på prod (`display_errors` av).
- **Nertid 30–60 min.** Gör det i lågtrafik (tidig morgon).
- Rollback: återställ snapshoten. Minuter, inte timmar.

## Confidence

medel–hög — standarduppgradering med snapshot som rollback och provkörning
först. Allt arbete på prod kräver Pärs ok, ett steg i taget.
