**Status:** aktiv — plan granskad två varv 2026-10-28 (Linux ×2, DB, PHP/Laravel); A kan göras nu, sedan provkörning på klon
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
  Certbot, cron, logrotate, MariaDB-behörigheter)
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

Granskad 2026-10-28 i två varv: Linux-expert, sedan Linux-, DB- och
PHP/Laravel-experter parallellt (subagenter, läsande SSH, tester i
Docker). Fynden är inarbetade; se "Granskningar 2026-10-28" sist.

### A. Förberedelser på bookworm (var för sig, utan nertid)

1. ✅ 2026-10-28 (`ddda08e`, deployad och verifierad). **Importerns låsfil:** `composer install` går inte på PHP 8.4 med dagens
   lock (`nette/schema v1.2.3` och `nette/utils v3.2.8` kräver `<8.3`).
   `composer update nette/schema nette/utils` (→ 1.4.0 / 4.1.6, inget annat
   ändras), PHPUnit, deploya på 8.2. Lägg `set -e` först i importer-workflowens
   ssh-skript, så att ett composer-fel stoppar deployen i stället för att
   köra `artisan optimize` mot gammal `vendor/`.
2. **Delningsbilderna till headless Chromium.** wkhtmltox (projektets egen
   `.deb`) provades 2026-10-28: installerar på trixie men renderar fel
   (dubbelhög text och blockgrafiken i loggan saknas). Debians
   `chromium-headless-shell` 156 på trixie gav i stort sett samma bild som
   dagens wkhtmltoimage (2,8 s, 170 MB). Bookworm har också Chromium, så
   bytet görs före OS-bytet: kommandot i `api.php` (~rad 598), med fast
   fönsterbredd 650 och höjd efter antal sidor (en sida = 919 px;
   flersidiga delningar måste lösas, `--screenshot` tar bara fönstret).
   Jämför bilder före och efter.
3. **Kollationen låses** innan 11.8 byter standard tyst (trixies
   `50-server.cnf` saknar `character-set-server`/`collation-server`, och
   CodeIgniter sätter `utf8` utan kollation). I `70-texttv.cnf`:
   ```
   [mysqld]
   character-set-server = utf8mb4
   collation-server = utf8mb4_general_ci
   [mariadb-11.8]
   character-set-collations = utf8mb3=utf8mb3_general_ci,utf8mb4=utf8mb4_general_ci
   ```
   Gruppen `[mariadb-11.8]` läses bara av 11.8 (provat i Docker på båda). **Beslut 2026-10-28 (Pär):** vi byter inte till `uca1400` eller `uca1400_swedish_ai_ci`; bara `views/fakta.php` sorterar på text, och det kan få `COLLATE` i frågan om det behövs.
   Lägg även `tmpdir = /var/tmp` (annars hamnar MariaDB:s temporära
   tabeller i RAM när `/tmp` blir tmpfs) och, om #30 vill ha det,
   `userstat = 1`.
4. **Fatala PHP-fel ska loggas:** CodeIgniters `index.php` sätter
   `error_reporting(0)` i production, så ett 8.4-fel blir en tom 500 utan
   loggrad (och `fastcgi_cache_use_stale http_500` döljer det). Byt till
   `error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)`.
   Provat: 47/47 svar byte-identiska. Rätta också `CLAUDE.md`, som säger att
   opcache tystar URI.php-varningen.
5. **PHP-inställningar som drop-ins** att kopiera till 8.4:
   `fpm/conf.d/99-texttv.ini` och `cli/conf.d/99-texttv.ini` med
   `date.timezone`. Ta inte med `error_log = /var/log/php_errors.log`: filen
   har aldrig funnits (www-data kan inte skapa den), allt hamnar i nginx
   `error.log`. Kopiera aldrig 8.2:s `php.ini` till 8.4.
6. **Versionsoberoende sökvägar:** nginx `fastcgi_pass
unix:/run/php/php-fpm.sock` (Debians alternativlänk, finns redan),
   `status.php:291` med `PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION`.
7. **Plan-baslinje för MariaDB:** kör
   `todos/17-explain-baslinje.sql` på prod nu och spara
   utdata; samma på klonen efter 11.8. Kravet: sidvisningsfrågan
   (`texttv_page.php:133`) går på samma index utan ny filesort.
8. `mariadb-upgrade` på 10.11, så att systemtabellerna står på 10.11.19.
9. **Dev-test:** MariaDB `mariadb:11.8` med samma `70-texttv.cnf`, `make
test-api`, importerns PHPUnit; webben och importern på PHP 8.4 i
   prod-läge enligt PHP-granskningens procedur (byte-diff 8.2 mot 8.4).
   Byt inte dev-importern till 8.4 i main före prod (en `composer update`
   där kan låsa paket som kräver 8.3+). Laravel 9/10 stöder inte 8.4: kör
   #15 till L11/L12 på 8.2 före OS-bytet, eller hoppa 8→11 direkt efter.
10. Testa att nattdumpen går att läsa in i en `mariadb:10.11`-container, så
    att reserven är prövad.

### B. Städning före provkörningen

1. **GRUB på EFI** (annars kan servern sluta boota senare): prod bootar via
   fallback-sökvägen `EFI/BOOT/BOOTX64.EFI` (GRUB 2.06), och trixies postinst
   uppdaterar modulerna men inte den filen. Före `full-upgrade`:
   ```
   echo 'grub-efi-amd64 grub2/force_efi_extra_removable boolean true' | debconf-set-selections
   echo 'grub-efi-amd64 grub2/update_nvram boolean false' | debconf-set-selections
   ```
2. `apt install tmux`. Kontrollera att root-lösenordet för Hetzners
   webbkonsol finns.
3. `apt purge libapache2-mod-php8.2 apache2-bin`, sedan `apt purge '~c'`.
4. En enda `/etc/apt/sources.list.d/debian.sources` (deb822) skrivs för
   hand vid källbytet i D.2: Hetzners spegel för trixie och trixie-updates,
   `security.debian.org` för trixie-security, `Signed-By:
/usr/share/keyrings/debian-archive-keyring.gpg` på alla. Inte `apt
modernize-sources` (ger dubbletter och tom `Signed-By`).
5. `apt update && apt full-upgrade` på bookworm; omstart om kärnan
   uppdaterats.

### C. Provkörning på klon

1. Snapshot av prod: `HCLOUD_CONTEXT=texttv hcloud server create-image
--type snapshot --description "fore-klon $(date +%F)" 65074277`.
2. Klon: `hcloud server create … --image <id> --firewall texttv-web
--ssh-key … --user-data-from-file klon.yaml`, där `#cloud-config` i
   `bootcmd` stoppar cron, stänger av apt- och certbot-timrarna och lägger
   `127.0.0.1` och `::1` för `texttv.nu api.texttv.nu` i `/etc/hosts`.
   Webbkonsolen kräver `hcloud server reset-password`. Nya host-nycklar.
3. Kör D på klonen med klocka, utom dump, snapshot och `certbot renew
--dry-run` (bara prod). Torrkör först: `apt -s full-upgrade | grep -E
'^(Remv|Inst)'`, och anteckna borttagningar.
4. **Verifiera:**
   - `curl --resolve texttv.nu:443:<klon-ip>` mot startsidan, `/100`,
     `/arkiv`, `/status` (php_sedan inte null), en ny delningsbild; byte-diff
     av `tests/api/urls.txt` mot prod.
   - PHP: `php -v`, `php-fpm8.4 -i | grep -E
'^(opcache\.jit|display_errors|error_reporting|date\.timezone)'`,
     importerns PHPUnit på riktiga libbar, `texttv:import 100`, `grep "PHP
message" /var/log/nginx/error.log` (bara väntade deprecations).
   - MariaDB: `@@collation_server`, `@@character_set_collations`, och som
     `texttv_web` `@@collation_connection` = `utf8mb3_general_ci`;
     `explain.sql` diffad mot baslinjen; `ANALYZE FORMAT=JSON` på samma
     frågor; slow log (`long_query_time=0.5`, `log_slow_verbosity`), varje
     URL två gånger (snapshot-disken är kall).
   - System: `nginx -t`, `sshd -t`, `ss -ltn | grep 3306` (bara 127.0.0.1),
     `sysctl fs.protected_regular fs.protected_symlinks` (2 och 1),
     `cloud-init status --long`, `systemctl --failed`, fail2ban med en
     misslyckad inloggning från annan IP.
5. **Boot från färsk NVRAM:** snapshot av den uppgraderade klonen, ny server
   från den, kontrollera att den bootar (prövar GRUB-fallbacken på riktigt).
6. Radera klonerna och skriv in tidsåtgång och avvikelser här.

### D. Uppgraderingen (samma steg på klon och prod)

Prod: en tidig vardagsmorgon i lågtrafik, gärna 05:00. Räkna med 30–60 min.

1. **Paus och konsistent rollback-punkt:**
   - Kommentera bort importraden i root-crontab, `systemctl stop
apt-daily.timer apt-daily-upgrade.timer`.
   - Färsk dump till Mac minin (`launchctl kickstart … backup-texttv-db`),
     kontrollera `OK` i `backup.log`. Efter 11.8 går datakatalogen inte att
     nedgradera, och prod har ingen Hetzner-backup påslagen.
   - `tar czf /root/etc-$(date +%F).tgz /etc /var/spool/cron/crontabs
/root/texttv-tools`, `chmod 600`.
   - `mariadb -e "SET GLOBAL innodb_fast_shutdown=0"`, sedan `systemctl stop
nginx mariadb.socket mariadb.service` och kontrollera `journalctl -u
mariadb -n 20 | grep "Shutdown complete"` (11.8 vägrar starta på en
     datakatalog som behöver crash recovery).
   - **Snapshot:** `hcloud server create-image --type snapshot --description
"pre-trixie $(date +%F)" 65074277`; skriv upp ID:t.
   - `systemctl disable nginx`, annars startar postinst den mitt i
     `full-upgrade`.
2. Skriv `debian.sources` för trixie (B.4), ta bort gamla `.list`-filer.
3. I `tmux`, under `script -a /root/upgrade-trixie.log`: `apt update`, `apt
upgrade --without-new-pkgs`, `apt full-upgrade`. Conffile-frågor: behåll
   egna. PHP 8.4 och MariaDB 11.8 installeras och startas här; cron är
   pausad. Stoppa om torrkörningen eller utdata visar oväntade `Remv`.
4. **PHP 8.4:** kopiera `99-texttv.ini` och `99-php2shell.ini` till
   `/etc/php/8.4/{fpm,cli}/conf.d/`, poolinställningarna (`pm.*`, `slowlog`,
   `request_slowlog_timeout`) till 8.4:s `www.conf`, logrotate-regeln för
   slowloggen med nya sökvägar. `update-alternatives --auto php`,
   `php-fpm.sock` pekar på 8.4. Kvarvarande `php8.2*`: `apt purge`.
5. **MariaDB 11.8:** `dpkg --audit` tomt; `mariadb-upgrade
--check-if-upgrade-is-needed` ska ge exitkod 1, annars `mariadb-upgrade
--verbose`; `journalctl -u mariadb -b | grep -iE
"error|warn|deprecat|unknown"`; `ANALYZE TABLE` på `texttv`,
   `page_actions`, `page_imports_logs`. Rättigheter utan hashar: `SELECT
user,host,db,table_name,table_priv,column_priv FROM mysql.tables_priv` och
   `SELECT * FROM mysql.columns_priv` (facit: 10 rader + `texttv_web`
   UPDATE(`is_shared`)); aldrig `SHOW GRANTS` (skriver hashar till loggen).
   Kollationen enligt C.4, `ss -ltn | grep 3306`.
6. `apt install linux-sysctl-defaults` (Hetzner har `Install-Recommends
false`, och trixie flyttade `fs.protected_*` dit; utan det blir de 0).
   Starta om php-fpm efter MariaDB (CodeIgniter kör `pconnect`).
7. Importern: `composer install` som workflowen (med eller utan `--no-dev`,
   samma som deployen), `php artisan optimize` med `umask 022`, kontrollera
   att `bootstrap/cache/*.php` är läsbara för www-data.
8. **Före omstart:** `cmp /boot/grub/x86_64-efi/core.efi
/boot/efi/EFI/BOOT/BOOTX64.EFI`, `efibootmgr -v`, `ls
/boot/vmlinuz-6.12*`, `udevadm test-builtin net_setup_link
/sys/class/net/eth0`, webbkonsolen öppen. Sedan omstart (kärna 6.12,
   `/tmp` blir tmpfs).
9. `systemctl enable --now nginx`, importraden tillbaka, apt-timrarna på,
   `apt autoremove`. Slow log på en vecka (`slow_query_log=1`,
   `long_query_time=1` i `70-texttv.cnf`).
10. Verifiera som i C.4, plus `/status/health` 200, Pulsetic grönt, nästa
    import i `page_imports_logs`, `certbot renew --dry-run`, `/prod-health`.
11. **Beslut om MariaDB i unattended-upgrades:** `Package-Blacklist`
    påverkar bara automatiken; står den kvar får 11.8 aldrig sina
    säkerhetsfixar automatiskt. Förslag: ta bort den efter en vecka utan
    problem.

### E. Efteråt

- Uppdatera `server.md`, `CLAUDE.md`, `prod-health`-skillen och
  Obsidian-noten (append): 8.2 → 8.4, versioner, sökvägar, Chromium för
  delningsbilderna; `last` finns inte i trixie, byt till `journalctl
--list-boots | tail -2`.
- fail2ban: lägg till `+ _COMM=sshd-session` i `journalmatch` i
  `jail.local` om räknaren inte gick upp på klonen (OpenSSH 10).
- PHPStan till 8.4 (`phpVersion: 80400`, workflowens `php-version`,
  `platform.php`), dev-stacken till 8.4 och MariaDB 11.8 i main.
- Kontrollera nästa morgons backup, radera snapshotarna efter en vecka.
- Avblockera #15 (Laravel 13). `buffer_pool` till 3G kan övervägas separat.
- Logga i `Logg - texttv.nu.md` samma dag.

## Rollback

Före D.3 (inget installerat): återställ källfilerna, `systemctl enable
--now nginx`, `systemctl start mariadb.socket mariadb`, apt-timrarna på,
importraden tillbaka. Efter D.3: återställ snapshoten från D.1. Prod har
delete- och rebuild-skydd, så:

```
HCLOUD_CONTEXT=texttv hcloud server disable-protection 65074277 delete rebuild
hcloud server rebuild --image <snapshot-id> 65074277
hcloud server enable-protection 65074277 delete rebuild
```

Snapshoten är tagen i pausläget: efter återställningen `systemctl start
mariadb.socket mariadb nginx`, apt-timrarna på, importraden tillbaka. Det
som går förlorat är importerade sidor och `page_actions` sedan snapshoten.

## Risker

- **MariaDB 10.11 → 11.8:** ny kostnadsmodell (kan inte stängas av; enda
  vägen tillbaka för en fråga är `FORCE INDEX`), kollation (låst i A.3),
  `innodb_snapshot_isolation` på (autocommit, låg risk; bevaka loggarna en
  vecka). Provkörningen ska visa planerna.
- **GRUB på EFI:** utan B.1 kan servern sluta boota vid nästa kallstart
  eller värdflytt.
- **PHP 8.4:** Laravel 8 fungerar med nette-fixen (provat), Laravel 9–10
  stöds inte på 8.4; CodeIgniter 2.2.6 gav byte-identiska svar.
- **Delningsbilderna:** Chromium ersätter wkhtmltoimage före bytet (A.2).
- **Ingen staging** och appversioner i fält: `/api/*` måste svara som förut
  (byte-diff i C.4).
- **Nertid 30–60 min.** Pulsetic larmar; det är väntat.

## Granskningar 2026-10-28

**Varv 1 (Linux):** MariaDB stoppades inte rent och `mariadb.socket` kunde
starta den igen; wkhtmltox i `/usr/local/bin`; ingen tmux; ingen färsk
dump; blacklist-steget verkningslöst; PHP 8.4 kommer med `full-upgrade`;
Apache-modulen; klonen hämtade bilder från prod; konfigbackupen
ofullständig; rollbacken tappade pausläget.

**Varv 2:**

- _Linux:_ GRUB-fallbacken på EFI (blockerare), rollback nekas av
  skyddsflaggorna och `create-image` saknade servern (blockerare), nginx
  startas av postinst, `fs.protected_*` försvinner utan
  `linux-sysctl-defaults`, kontroller före omstart, cloud-init-user-data,
  deb822 för hand, cloud-init 25.1-moduler, fail2ban och `sshd-session`,
  `last` borta, MariaDB:s `tmpdir` på tmpfs.
- _DB:_ kollationen byter tyst (blockerare), `SHOW GRANTS` skriver hashar,
  vyn för `most_read` finns inte på prod (bara i dev), plan-baslinje,
  slow log och kall disk på klonen, `innodb_fast_shutdown=0`, skarpare
  kontroller efter start, 11.8 i dev först. `buffer_pool` 2G räcker
  (träffgrad 99,997 %).
- _PHP/Laravel:_ nette-låsningen stoppar `composer install` på 8.4
  (blockerare), fatala fel loggas inte i production, `error_log` pekar på
  en fil som aldrig funnits, versionsoberoende sökvägar, Laravel 9/10 utan
  8.4-stöd, `set -e` i importer-workflowen, `artisan optimize`.

Ett eget test samma dag: wkhtmltox renderar delningsbilderna fel, Chromium
rätt (A.2).

## Confidence

medel–hög — alla blockerare har konkreta åtgärder som görs före bytet, och
det som återstår som okänt (frågeplaner i 11.8, GRUB-fallbacken) prövas på
klonen.
