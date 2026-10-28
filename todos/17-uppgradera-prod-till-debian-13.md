**Status:** aktiv — plan uppdaterad och granskad 2026-10-28; förberedelserna i A kan göras nu, sedan provkörning på klon
**Senast uppdaterad:** 2026-10-28

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

## Tillägg 2026-10-26 (granskning)

- **Fixa MariaDB:s root-inloggning först.** Hoppet till 11.8 kör
  `mariadb-upgrade`, och det misslyckas redan i dag: `root@localhost` är
  ställd på enbart lösenord, så Debians socket-inloggning nekas ("FATAL ERROR:
  Upgrade failed" vid varje start, systemtabellerna står på 10.11.11 fast
  servern kör 10.11.18). Åtgärden står i Obsidian-noten `Server-todo.md`
  (punkten om unix_socket) och väntar på att root-lösenordet flyttas till
  Proton Pass. Den blockerar alltså den här todon.
- **Klonen saknar brandvägg.** En server skapad från snapshot får ingen
  Hetzner Cloud Firewall automatiskt. Koppla `texttv-web` direkt
  (`HCLOUD_CONTEXT=texttv hcloud firewall apply-to-resource texttv-web
--type server --server <klon>`), och ge den delete-skydd av.
- **Kontrollera härdningen från 2026-10-25 efteråt.** Det mesta är drop-ins
  och bör överleva: `sshd_config.d/99-hardening.conf`, `fail2ban/jail.local`,
  `journald.conf.d/50-retention.conf`, apt-timrarnas `override.conf`,
  `needrestart/conf.d/50-auto.conf`, `52unattended-upgrades-updates` (använder
  `${distro_codename}`), swap och `sysctl.d/99-swappiness.conf`. Svara "behåll
  din version" på conffile-frågor och kör `sshd -t`, `fail2ban-client status`
  och `/prod-health updates`.
- **Deadline bekräftad:** PHP2Shell-fixen (8.2.35) kom via Debian LTS samma dag
  som releasen, 2026-10-21. LTS-spåret fungerar alltså, och 2028-06-30 står.
- **Kan delas upp.** Före själva OS-bytet: MariaDB-rootfixen (ovan), och
  Laravel 9–12 i #15 på nuvarande PHP 8.2. Sedan provkörning på klon en dag,
  prod-uppgraderingen en annan morgon, och Laravel 13 efter det. Dev-stacken
  kan gå över till `serversideup/php:8.4` redan nu som parallell kontroll
  (`make test-api` och `make phpstan` på 8.4), utan att prod ändras.
- Trixie föredrar deb822-formatet för apt-källor; `apt modernize-sources`
  efter uppgraderingen.

## Läget 2026-10-28 (inventerat på servern, bara läsande)

- Debian 12.15, kärna 6.1, PHP 8.2.35, MariaDB 10.11.19, nginx 1.22.1,
  nodejs 18 (Debians), 55 GB ledigt på `/`. Inga paket på hold, inga
  tredjepartsrepon. Apt-källorna står **dubbelt**: `deb.debian.org` och
  Hetzners spegel, båda för bookworm, bookworm-updates och bookworm-security.
- **Blockeraren från 2026-10-26 är löst:** `root@localhost` har nu
  `unix_socket` som första metod (lösenordet finns kvar som andra till
  #30 steg 6, 2026-11-04). `mariadb-upgrade --check-if-upgrade-is-needed`
  fungerar. Systemtabellerna står på 10.11.11 (`mysql_upgrade_info` från
  2025-06); det räcker inom 10.11 men visar att uppgraderingen inte gått
  sedan dess.
- **Nytt hinder: `wkhtmltopdf` finns inte i Debian 13.** Delningsbilderna
  (`api.php` rad ~598, `/usr/bin/wkhtmltoimage --width 650`) bygger på
  bookworms `wkhtmltopdf 0.12.6-2+b1`. Paketet togs bort ur trixie (QtWebKit
  är borta), och t64-övergången gör att `full-upgrade` sannolikt avinstallerar
  det. Måste lösas före prod, se förberedelse B.
- **PHP-konfig som ska följa med till 8.4** (allt annat är paketets default):
  - `fpm/pool.d/www.conf`: `pm = dynamic`, `max_children 10`, `start 2`,
    `min_spare 1`, `max_spare 3`, `slowlog` + `request_slowlog_timeout 3s`.
  - `fpm/php.ini`: `error_log = /var/log/php_errors.log`,
    `date.timezone = "Europe/Stockholm"`. `cli/php.ini`: `date.timezone`.
  - `fpm/conf.d/99-php2shell.ini`: `session.upload_progress.enabled = Off`.
  - `/etc/logrotate.d/php8.2-fpm-slowlog` (egen regel).
- **MariaDB-konfig:** bara `mariadb.conf.d/70-texttv.cnf`
  (`innodb_buffer_pool_size = 2G`, `local_infile = 0`); paketfilerna är
  orörda. `userstat=1` är satt dynamiskt och försvinner vid omstart (#30).
- **nginx:** `nginx.conf` är ändrad mot paketet (svara "behåll din" på
  frågan). Två `fastcgi_pass unix:/var/run/php/php8.2-fpm.sock` i
  `sites-enabled`. En vhost har `listen 443 ssl http2`, som ger en
  deprecation-varning i nginx 1.26 (bara varning).
- **Kod och dokumentation med 8.2 i sig:** `controllers/status.php:291`
  (`/run/php/php8.2-fpm.pid`), `server.md`, `CLAUDE.md`,
  `.claude/skills/prod-health/SKILL.md` (många sökvägar), dev-stackens
  Dockerfiles.
- Jobb som påverkas av ett servicefönster: importen (cron varje minut), den
  nattliga backupen till Mac minin 07:45, `remove-old-share-images.js` 01:45
  (node), unattended-upgrades med automatisk omstart 03:30, Pulsetic-larmet
  på `/status/health`.

## Plan

Granskad 2026-10-28 av en Linux-expert (subagent, läsande SSH och test i en
`debian:trixie`-container). Fynden är inarbetade nedan; se "Granskning
2026-10-28" sist för det som ändrades och varför.

### A. Förberedelser på bookworm (små, var för sig, kan göras nu)

1. **Delningsbilderna till wkhtmltox 0.12.6.1-3** (projektets egen `.deb`,
   byggd för bookworm). Testat i trixie-container: installerar och renderar,
   eftersom trixies t64-bibliotek har `Provides: libssl3` och
   `libpng16-16`. Paketet ersätter Debians `wkhtmltopdf`
   (`Conflicts/Replaces/Provides`), men lägger binären i
   `/usr/local/bin/wkhtmltoimage`: ändra sökvägen i `api.php` (rad ~598).
   Det drar in `xfonts-base`/`xfonts-75dpi`, så jämför en bild före och
   efter. Gjort här berörs delningsbilderna inte av OS-bytet. Reserv på sikt
   (projektet är arkiverat): `chromium-headless-shell`, men `--screenshot`
   tar bara viewporten, så det kräver fast höjd eller puppeteer.
2. `status.php`: läs php-fpm:s pid-fil för den version nginx använder (eller
   den nyaste av `/run/php/php*-fpm.pid`), så att `/status` fungerar under
   och efter bytet.
3. **Egna PHP-inställningar som drop-ins**, så att de bara behöver kopieras:
   `/etc/php/8.2/fpm/conf.d/99-texttv.ini` (`date.timezone`, `error_log`) och
   `/etc/php/8.2/cli/conf.d/99-texttv.ini` (`date.timezone`); återställ
   `php.ini` till paketets. `99-php2shell.ini` finns redan som drop-in.
4. `mariadb-upgrade` på 10.11, så att systemtabellerna står på 10.11.19 före
   hoppet.
5. nginx: `listen 443 ssl http2;` → `listen 443 ssl;` + `http2 on;` görs
   efter bytet (1.22 har inte `http2 on`); notera bara här.
6. **Importern på PHP 8.4 i dev-stacken:** `make test-api`, `make phpstan`,
   importerns `phpunit` och en riktig `texttv:import 100` i en
   `serversideup/php:8.4`-container med `composer install` mot nuvarande
   lock. Laravel 8 på 8.4 är annars otestat. Hellre: gör Laravel ≥ 10 i #15
   klart före OS-bytet.

### B. Städning före provkörningen

1. `apt install tmux`. Kontrollera att root-lösenordet för Hetzners
   webbkonsol finns (reserv om SSH tappas mitt i uppgraderingen).
2. `apt purge libapache2-mod-php8.2 apache2-bin` (ingen apache-tjänst; trixies
   `php8.4` drar annars in `libapache2-mod-php8.4`), sedan `apt purge '~c'`
   (16 paket i rc-läge).
3. Behåll en spegel i apt-källorna (de står dubbelt).
4. `apt update && apt full-upgrade` på bookworm; omstart om kärnan
   uppdaterats.

### C. Provkörning på klon

1. Snapshot av prod (`HCLOUD_CONTEXT=texttv hcloud server create-image
--type snapshot`), ny server från den med `--user-data-from-file` där
   cloud-init kör `systemctl stop cron` vid boot. Koppla brandväggen
   `texttv-web`. Klonen får nya host-nycklar (varning i known_hosts).
2. På klonen: `systemctl disable --now apt-daily.timer
apt-daily-upgrade.timer certbot.timer`, och `127.0.0.1 texttv.nu
api.texttv.nu` i `/etc/hosts`, annars hämtar `api.php` delningsbildens
   sida från prod.
3. Kör steg D på klonen med klocka. Torrkör först efter källbytet:
   `apt -s full-upgrade | grep -E '^(Remv|Inst)'` och anteckna vad som tas
   bort.
4. Verifiera:
   - `curl --resolve texttv.nu:443:<klon-ip>` mot startsidan, `/100`,
     `/arkiv`, `/status`, en delningsbild; diffa API-svaren byte för byte mot
     prod med `tests/api/urls.txt`.
   - `php artisan texttv:import 100` för hand; `nginx -t`, `sshd -t`,
     `fail2ban-client status`; `ss -ltn | grep 3306` (bara 127.0.0.1).
   - MariaDB 11.8: `ANALYZE TABLE` på de stora tabellerna, tidmät
     `/status`, `/api/most_read/news`, `/arkiv` och dagslistorna mot prod
     (ny kostnadsmodell i optimeraren), slow log en stund.
5. Radera klonen och skriv in tidsåtgång och avvikelser här.

### D. Uppgraderingen (samma steg på klon och prod)

Prod: en tidig vardagsmorgon i lågtrafik, gärna 05:00. Räkna med 30–60 min.

1. **Paus och konsistent rollback-punkt:**
   - Kommentera bort importraden i root-crontab (som vid InnoDB-bytet), och
     `systemctl stop apt-daily.timer apt-daily-upgrade.timer`.
   - Färsk dump till Mac minin (`launchctl kickstart … backup-texttv-db`),
     kontrollera `OK` i `backup.log`. Efter 11.8 går datakatalogen inte att
     nedgradera.
   - `tar czf /root/etc-$(date +%F).tgz /etc /var/spool/cron/crontabs
/root/texttv-tools` och `chmod 600`.
   - `systemctl stop nginx mariadb.socket mariadb.service` (socketen startar
     annars databasen igen vid nästa anrop) och kontrollera `journalctl -u
mariadb -n 20 | grep "Shutdown complete"`.
   - **Snapshot nu.** Den är rollback-punkten.
2. Byt `bookworm` → `trixie` i källorna (`sed -i 's/bookworm/trixie/g'
/etc/apt/sources.list /etc/apt/sources.list.d/*.list`, även security).
3. I `tmux`, under `script -a /root/upgrade-trixie.log`: `apt update`, `apt
upgrade --without-new-pkgs`, sedan `apt full-upgrade`. Conffile-frågor:
   behåll egna (`nginx.conf` m.fl.). PHP 8.4 installeras här via
   metapaketen, och `/usr/bin/php` byter till 8.4 direkt; cron är fortfarande
   pausad.
4. **PHP 8.4:** kopiera `99-texttv.ini` och `99-php2shell.ini` till
   `/etc/php/8.4/{fpm,cli}/conf.d/`, för över poolinställningarna till
   `/etc/php/8.4/fpm/pool.d/www.conf` och logrotate-regeln för slowloggen
   (sökvägar `php8.4-fpm.*`). Byt båda `fastcgi_pass` till
   `php8.4-fpm.sock`, `listen 443 ssl;` + `http2 on;`, `nginx -t`.
   `update-alternatives --auto php`. När 8.4 svarar: `systemctl disable
--now php8.2-fpm`, `apt purge 'php8.2*'`.
5. **MariaDB 11.8:** `systemctl start mariadb.socket mariadb`, `dpkg --audit`
   tomt, `mariadb-upgrade` gått (annars kör den), `SHOW GRANTS` för
   `texttv_web`, `texttv_importer`, `texttv_admin`, `innodb_buffer_pool_size`
   och `local_infile`, `ss -ltn | grep 3306`. Kontrollera att
   `/etc/systemd/system/mariadb.socket.d/local-only.conf` fortfarande gäller.
6. `composer install --no-dev` i importern på 8.4, `php artisan config:cache`
   med `umask 022`. Starta nginx.
7. Omstart (kärna 6.12; `/tmp` blir tmpfs, cachefilerna där är ofarliga att
   tappa). Sedan importraden tillbaka, apt-timrarna på, `apt
modernize-sources`, `apt autoremove`.
8. **Beslut om MariaDB i unattended-upgrades:** `Package-Blacklist`
   (`mariadb-`, `libmariadb`) påverkar bara unattended-upgrades, inte en
   manuell uppgradering. Står den kvar kommer 11.8:s säkerhetsfixar aldrig
   in automatiskt. Förslag: ta bort den efter en vecka utan problem.
9. Verifiera som i C.4, plus `/status/health` 200, Pulsetic grönt, nästa
   import i `page_imports_logs`, `certbot renew --dry-run`, `/prod-health`.

### E. Efteråt

- Uppdatera `server.md`, `CLAUDE.md`, `prod-health`-skillen och
  Obsidian-noten (append, läses inte): 8.2 → 8.4, versioner, sökvägar, att
  delningsbilderna kommer från wkhtmltox i `/usr/local/bin`.
- Dev-stacken till PHP 8.4 och MariaDB 11.8 in i main.
- Kontrollera nästa morgons backup i `backup.log`.
- Radera snapshotarna efter en vecka utan problem.
- Avblockera #15 (Laravel 13).
- Logga i `Logg - texttv.nu.md` samma dag.

## Rollback

Före D.3 (inget installerat): ångra källändringen och starta MariaDB, nginx
och pausade jobb igen. Efter: återställ snapshoten från D.1
(`hcloud server rebuild --image <snapshot>`, några minuter). Snapshoten är
tagen i pausläget, så efter återställningen: `systemctl start mariadb.socket
mariadb nginx`, apt-timrarna på och importraden tillbaka i crontab. Det som
går förlorat är importerade sidor och `page_actions` sedan snapshoten;
importen fyller på sidorna igen.

## Risker

- **MariaDB 10.11 → 11.8** är det största hoppet: systemtabeller, ny
  kostnadsmodell i optimeraren, standardkollationen (`uca1400`) för nya
  tabeller. Provkörningen ska visa det.
- **PHP 8.4 och Laravel 8 / CodeIgniter 2.2.6:** CodeIgniter provat i Docker
  med bara deprecation-notiser; Laravel 8 provas i A.6. `display_errors`
  måste vara av.
- **Delningsbilderna** hänger på ett arkiverat projekt (wkhtmltox), men det
  är löst före bytet i A.1 och fungerar på trixie.
- **Ingen staging** och appversioner i fält: `/api/*` måste svara som förut
  (byte-diff i C.4).
- **Nertid 30–60 min.** Pulsetic larmar; det är väntat.

## Granskning 2026-10-28 (Linux-expert)

Inarbetat ovan:

- **Blockerare:** MariaDB stoppades inte rent före hoppet, och
  `mariadb.socket` kunde starta den igen mitt i paketbytet (nu D.1, snapshot
  efter stoppet). wkhtmltox lägger binären i `/usr/local/bin` (nu A.1, gjort
  på bookworm). Ingen tmux och inget sparat utdata (B.1, D.3). Ingen färsk
  dump före prod, eftersom backupen går 07:45 (D.1).
- **Bör fixas:** blacklist-steget gjorde inget vid manuell uppgradering
  (struket, beslut i D.8). PHP 8.4 kommer redan med `full-upgrade`, och
  `/usr/bin/php` byter direkt (D.3, drop-ins i A.3). Apache-modulen och
  rc-paket rensas (B.2). Klonen hämtade delningsbilden från prod
  (`/etc/hosts`, C.2). Laravel 8 på 8.4 var otestat (A.6). Frågeplaner med
  11.8 (C.4). Konfigbackupen var ofullständig (hela `/etc`, D.1). Rollbacken
  tappade pausläget (Rollback).
- **Redan i ordning enligt granskningen:** `openssh-server` deb12u10, usrmerge
  klart, `dpkg --audit` tomt, inga holds, ed25519-nycklar, interfacet `eth0`,
  Hetzners trixie-speglar svarar, backupskriptet använder `mariadb-dump`.

## Confidence

medel–hög — standarduppgradering med konsistent snapshot och provkörning.
Delningsbilderna löses före bytet; kvar som okänt är MariaDB-hoppet, och det
är det klonen ska visa.
