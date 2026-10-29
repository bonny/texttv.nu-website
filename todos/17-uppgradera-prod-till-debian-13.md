**Status:** aktiv — A klar 2026-10-28 (A.8 struket), B klar 2026-10-28; nästa C (provkörning på klon), sedan D på prod en tidig morgon. Granskning varv 3 (2026-10-29) införd i C, D, E och Rollback.
**Senast uppdaterad:** 2026-10-29

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
2. ✅ 2026-10-28 (`11646b9`, deployad, delning testad av Pär). **Delningsbilderna ritas i PHP** ([#34](34-delningsbilder-ritade-i-php.md)), beslut 2026-10-28. `wkhtmltopdf` finns inte i trixie; wkhtmltox renderar fel och Chromium är tyngre (~2,6 s och 250–400 MB per bild). Måste vara klart före OS-bytet.
3. ✅ 2026-10-28 (i `70-texttv.cnf` med `tmpdir`, backup `/root/70-texttv.cnf.bak-20261028-a3`, gäller från nästa omstart). **Kollationen låses** innan 11.8 byter standard tyst (trixies
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
4. ✅ 2026-10-28. **Fatala PHP-fel ska loggas:** CodeIgniters `index.php` sätter
   `error_reporting(0)` i production, så ett 8.4-fel blir en tom 500 utan
   loggrad (och `fastcgi_cache_use_stale http_500` döljer det). Byt till
   `error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)`.
   Provat: 47/47 svar byte-identiska. Rätta också `CLAUDE.md`, som säger att
   opcache tystar URI.php-varningen.
5. ✅ 2026-10-28 (`99-texttv.ini` i `fpm/conf.d` och `cli/conf.d`, bara `date.timezone`; ingen reload behövdes, samma värde). **PHP-inställningar som drop-ins** att kopiera till 8.4:
   `fpm/conf.d/99-texttv.ini` och `cli/conf.d/99-texttv.ini` med
   `date.timezone`. Ta inte med `error_log = /var/log/php_errors.log`: filen
   har aldrig funnits (www-data kan inte skapa den), allt hamnar i nginx
   `error.log`. Kopiera aldrig 8.2:s `php.ini` till 8.4.
6. ✅ 2026-10-28 (nginx för texttv.nu och l.texttv.nu, reload utan avbrott, backuper `/root/nginx-*.bak-20261028-a6`; `status.php` läser versionen från PHP). **Versionsoberoende sökvägar:** nginx `fastcgi_pass
unix:/run/php/php-fpm.sock` (Debians alternativlänk, finns redan),
   `status.php:291` med `PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION`.
7. ✅ 2026-10-28 (10.11 sparad i `17-explain-baslinje-10.11.txt`). **Plan-baslinje för MariaDB:** kör
   `todos/17-explain-baslinje.sql` på prod nu och spara
   utdata; samma på klonen efter 11.8. Kravet: sidvisningsfrågan
   (`texttv_page.php:133`) går på samma index utan ny filesort.
8. ~~`mariadb-upgrade` på 10.11, så att systemtabellerna står på 10.11.19.~~ Struket 2026-10-28: `mariadb-upgrade --check-if-upgrade-is-needed` svarar att 10.11.11 → 10.11.19 inte behöver det (samma huvudversion, systemtabellerna ändras inte). Behövs efter 11.8 (C/D), där Debians paket kör det och vi kontrollerar `mariadb_upgrade_info`.
9. ✅ 2026-10-28 (se resultat nedan). **Dev-test:** MariaDB `mariadb:11.8` med samma `70-texttv.cnf`, `make
test-api`, importerns PHPUnit; webben och importern på PHP 8.4 i
   prod-läge enligt PHP-granskningens procedur (byte-diff 8.2 mot 8.4).
   Byt inte dev-importern till 8.4 i main före prod (en `composer update`
   där kan låsa paket som kräver 8.3+). Laravel 9/10 stöder inte 8.4: kör
   #15 till L12 på 8.2 före OS-bytet, eller ta OS-bytet först: Laravel 8 på PHP 8.4 fungerade i A.9 nedan (tester, import), bara deprecations. Pär väljer ordning; L9–L11 deployas inte (se #15).

   ✅ **Kört 2026-10-28** med prods nattdump (2026-10-28 12:28) i
   `mariadb:10.11` och `mariadb:11.8.9`, båda med kopia av `70-texttv.cnf`,
   och webben i prod-läge (utan `APP_ENV`) på `serversideup/php` 8.2 och
   8.4.27 (trixie):

   - Dumpen läses in i 11.8 på 59 s; alla tabeller behåller
     `utf8mb3/utf8mb4_general_ci`. `SET NAMES utf8` ger
     `utf8mb3_general_ci`, en ny tabell `utf8mb4_general_ci`, så låset i
     A.3 fungerar.
   - Frågeplanerna i `17-explain-baslinje.sql` är identiska (typ, index,
     Extra) i 10.11 och 11.8: ingen ny filesort. Utdata i
     [`17-explain-baslinje-11.8-lokalt.txt`](17-explain-baslinje-11.8-lokalt.txt).
   - 63 adresser (API, sidor, arkiv, gammalt format, delningsbilder png/jpg,
     flersidig delning, 404): PHP 8.4 mot 8.2 på samma DB **byte-identiska
     63/63**, även bilderna. 8.4 + 11.8 mot 8.2 + 10.11: fem skillnader,
     fyra av dem tid/egna testanrop (`/status`, health, sitemap, share-count).
   - **Enda verkliga skillnaden:** `FROM_UNIXTIME(0)` är `NULL` i 11.8
     (1970-01-01 i 10.11), så `/api/updated/<sida>/0` svarar "ingen
     uppdatering" i stället för en godtycklig rad från 2015. Appen skickar
     aktuell tid; 1 av 716 712 `api/updated`-anrop i dagens nginx-logg hade
     `0`. Ingen åtgärd.
   - Dev-läge (`E_ALL`): samma varningar i 8.2 och 8.4 (CI 2:s
     `AllowDynamicProperties`-varningar och `log_message`), inga nya. Prod-läge
     8.4: inget i loggen.
   - Importern på 8.4: `composer install` från låsfilen OK (A.1), PHPUnit 98
     tester OK (1 risky, utan assertions). Deprecations från Laravel 8 och
     Monolog (implicit nullable) går till Laravels deprecations-kanal, inte
     undantag. Riktig import av 100, 101, 377, 401 mot 11.8: sparade,
     `IMPORT_SUCCESS`, och webben 8.4 visar dem och ritar delningsbilden.
   - Docker-VM:en (7,7 GB) OOM-dödade 10.11-containern en gång med två
     buffer pools på 1 GB parallellt med övriga stackar; ingen produktfråga.

10. ✅ 2026-10-28: dumpen 2026-10-28 (xz 1 GB) inläst i `mariadb:10.11` på 50 s utan fel; `texttv` 1 575 935 rader, samma som prod före dumptiden. Testa att nattdumpen går att läsa in i en `mariadb:10.11`-container, så
    att reserven är prövad.

### B. Städning före provkörningen

1. ✅ 2026-10-28 (debconf satt, `BOOTX64.EFI` oförändrad). **GRUB på EFI** (annars kan servern sluta boota senare): prod bootar via
   fallback-sökvägen `EFI/BOOT/BOOTX64.EFI` (GRUB 2.06, identisk med
   `core.efi`, ingen `debian`-post i NVRAM, ingen Secure Boot). Bookworms
   postinst kör bara `grub-install` om `EFI/debian` finns (den saknas), så
   inställningarna nedan gör ingenting på bookworm. Trixies postinst kör
   `grub-install` eftersom `/boot/grub/x86_64-efi/core.efi` finns; utan
   `force_efi_extra_removable` blir `BOOTX64.EFI` kvar på 2.06 medan modulerna
   blir 2.12, och servern kan sluta boota. `update_nvram=false` håller boot
   via fallback-posten, lika på klon och prod. Före `full-upgrade`:
   ```
   echo 'grub-efi-amd64 grub2/force_efi_extra_removable boolean true' | debconf-set-selections
   echo 'grub-efi-amd64 grub2/update_nvram boolean false' | debconf-set-selections
   ```
2. ✅ 2026-10-28 (tmux 3.3a; root-lösenordet satt, Pär bekräftar att han har det). `apt install tmux`. Kontrollera att root-lösenordet för Hetzners
   webbkonsol finns.
3. ✅ 2026-10-28. `apt purge libapache2-mod-php8.2 apache2-bin`, sedan `apt purge '~c'`
   (granskat 2026-10-28: bara de två paketen; `~c` är 16 gamla
   `linux-image-6.1.0-*` utan conffiles). Görs gärna före B.1. Frivilligt:
   shim-paketen används inte (ingen Secure Boot) och kan tas bort, prova på
   klonen först. **Varv 3:** purga shim-paketen (`shim-signed`,
   `shim-helpers-amd64-signed`, `shim-unsigned`, `shim-signed-common`) på
   klonen i C och på prod före D, så att bara en bootväg finns kvar.
4. En enda `/etc/apt/sources.list.d/debian.sources` (deb822) skrivs för
   hand vid källbytet i D.2: Hetzners spegel för trixie och trixie-updates,
   `security.debian.org` för trixie-security, `Signed-By:
/usr/share/keyrings/debian-archive-keyring.gpg` på alla. Inte `apt
modernize-sources` (ger dubbletter och tom `Signed-By`).
5. ✅ 2026-10-28 (0 väntande, kör 6.1.0-54). `apt update && apt full-upgrade` på bookworm; omstart om kärnan
   uppdaterats.

### C. Provkörning på klon

Från C till och med D.10: **inga deployer** (ingen push till `main`, eller
`gh workflow disable` för båda workflows). Deployas Laravel 12 (#15) mellan
C och D gäller inte klontestet för importern; gör då om importerdelen av
C.4. Ta aldrig L12/L13 samma morgon som D.

1. Källa: senaste dagliga backup-imagen räcker (prod har Hetzner-backup,
   fönster 18–22), annars snapshot: `HCLOUD_CONTEXT=texttv hcloud server
create-image --type snapshot --description "fore-klon $(date +%F)"
65074277`.
2. Klon: `hcloud server create … --image <id> --firewall texttv-web
--ssh-key … --user-data-from-file klon.yaml`, där `#cloud-config` i
   `bootcmd` kör `systemctl mask --now cron.service certbot.timer
apt-daily.timer apt-daily-upgrade.timer` (`stop` är för tidigt i bootcmd
   och gör ingenting) och lägger `127.0.0.1` och `::1` för `texttv.nu
api.texttv.nu` i `/etc/hosts`. Efter första boot:
   - `systemctl is-active cron apt-daily-upgrade.timer` ska ge `inactive`.
   - **Webbkonsolen med prods eget root-lösenord** (klonen ärver
     `/etc/shadow`) innan något reset; noVNC har amerikansk
     tangentbordslayout. Bara om det misslyckas: `hcloud server
reset-password`, och lös lösenordsfrågan för prod före D.
   - `debconf-show grub-efi-amd64`: `force_efi_extra_removable: true` och
     `update_nvram: false` ska finnas kvar (cloud-inits `grub-dpkg` körs per
     ny instans).
   - MariaDB 10.11 har startats med A.3-konfigen för första gången:
     `@@tmpdir` = `/var/tmp` innan uppgraderingen, så att ett konfigfel inte
     blandas ihop med 11.8.
   - Purga shim-paketen (B.3).
3. Kör D på klonen med klocka, utom dump, snapshot och `certbot renew
--dry-run` (bara prod). Torrkör först: `apt -s full-upgrade | grep -E
'^(Remv|Inst)'`, och anteckna borttagningar. `apt -s full-upgrade | grep -E
'apache2|libapache2|grub-efi-amd64-signed'` ska vara tomt (trixies `php8.4`
   har `Depends: libapache2-mod-php8.4 | php8.4-fpm | …`).
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
     misslyckad inloggning från annan IP (räknas den inte: `fail2ban-regex
systemd-journal sshd`, troligen filtrets prefix `sshd-session`).
   - Identitet efter omstarten (cloud-init 22.4 → 25.1, `ssh_deletekeys`):
     `ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub` och `cat
/var/lib/cloud/data/instance-id` oförändrade mot före uppgraderingen,
     `/etc/udev/rules.d/70-persistent-net.rules` kvar, `ip -br a` visar
     `eth0` med IPv4 och IPv6.
   - `command -v mysql mysqldump` (kan ligga i `mariadb-client-compat`);
     saknas de, byt till `mariadb` i prod-health-skillen (E).
5. **Boot från färsk NVRAM:** snapshot av den uppgraderade klonen, ny server
   från den, kontrollera att den bootar (prövar GRUB-fallbacken på riktigt).
6. **Prova rollbacken:** `hcloud server rebuild --image <källimage>` på
   klonen, med klocka. Kontrollera att den bootar bookworm, att host-nyckelns
   fingeravtryck är oförändrat och att webbkonsolen fungerar.
7. Radera klonerna och skriv in tidsåtgång och avvikelser här.

### D. Uppgraderingen (samma steg på klon och prod)

Prod: en tidig vardagsmorgon i lågtrafik, gärna 05:00. Räkna med 30–60 min.

1. **Paus och konsistent rollback-punkt:**
   - `cd …/importer && php artisan down`, sedan samma `pgrep -af 'artisan
(schedule:|texttv:|import-status)'`-väntan som `deploy-importer.yml`
     (en import som redan kör stoppas inte av crontab-ändringen).
   - Kommentera bort importraden i root-crontab, `systemctl disable --now
apt-daily.timer apt-daily-upgrade.timer` (`stop` räcker inte, omstarten i
     D.8 slår på dem igen).
   - `apt-mark manual linux-image-6.1.0-54-amd64`, så att 6.1 finns kvar att
     välja i GRUB-menyn (`GRUB_TIMEOUT=5`) via webbkonsolen.
   - Färsk dump till Mac minin (`launchctl kickstart … backup-texttv-db`),
     kontrollera `OK` i `backup.log`. Efter 11.8 går datakatalogen inte att
     nedgradera. Hetzner-backupen (dagliga images, fönster 18–22) är äldre
     än morgonens importer och räcker inte som rollback-punkt.
   - `tar czf /root/etc-$(date +%F).tgz /etc /var/spool/cron/crontabs
/root/texttv-tools`, `chmod 600`.
   - `mariadb -e "SET GLOBAL innodb_fast_shutdown=0"`, sedan `systemctl stop
nginx mariadb.socket mariadb.service` och kontrollera `journalctl -u
mariadb -n 20 | grep "Shutdown complete"` (11.8 vägrar starta på en
     datakatalog som behöver crash recovery).
   - **Snapshot:** `hcloud server create-image --type snapshot --description
"pre-trixie $(date +%F)" 65074277`; skriv upp ID:t och skydda den:
     `hcloud image enable-protection <id> delete`.
   - `systemctl disable nginx`, annars startar postinst den mitt i
     `full-upgrade`.
2. Skriv `debian.sources` för trixie (B.4), ta bort gamla `.list`-filer.
3. I `tmux`, under `script -a /root/upgrade-trixie.log`: `apt update`, `apt
upgrade --without-new-pkgs`, `apt full-upgrade`. Conffile-frågor: behåll
   egna. PHP 8.4 och MariaDB 11.8 installeras och startas här; cron är
   pausad. Stoppa om torrkörningen eller utdata visar oväntade `Remv`.
   Efteråt: `grep -E "Installing for|core image|shim|Failed: grub-install"
/root/upgrade-trixie.log` (väntat: "No Secure Boot: installing core
   image"). tmux uppgraderas 3.3a → 3.5 under körningen; ger `tmux attach`
   "protocol version mismatch": `/proc/$(pgrep -o tmux)/exe attach`.
4. **PHP 8.4:** kopiera `99-texttv.ini` och `99-php2shell.ini` till
   `/etc/php/8.4/{fpm,cli}/conf.d/`, poolinställningarna (`pm.*`, `slowlog`,
   `request_slowlog_timeout`) till 8.4:s `www.conf`, logrotate-regeln för
   slowloggen med nya sökvägar. `update-alternatives --auto php`,
   `php-fpm.sock` pekar på 8.4. Kvarvarande 8.2: `apt purge '~i~n^php8\.2'`,
   läs listan innan du svarar ja. Ta bort
   `/etc/logrotate.d/php8.2-fpm-slowlog` när 8.4-varianten finns.
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
7. Importern: efter omstarten i D.8 och nginx i D.9, kör `gh workflow run
deploy-importer.yml` (på klonen: samma skriptsteg för hand). Den gör
   `composer check-platform-reqs`, `composer install --no-dev`, `optimize`
   med `umask 002`, rättigheterna från #33 (`root:www-data`, 2775/664),
   röktest och `artisan up`. Inte `umask 022` för hand: då kan
   `/importstatus` (www-data) sluta kunna skriva loggen.
8. **Före omstart:**
   - `cmp /boot/grub/x86_64-efi/core.efi /boot/efi/EFI/BOOT/BOOTX64.EFI`
     (väntat: `EFI/BOOT/BOOTX64.EFI` och `EFI/debian/grubx64.efi` finns).
     Skiljer de: `grub-install --target=x86_64-efi --efi-directory=/boot/efi
--force-extra-removable --no-nvram` och jämför igen. **Ingen omstart
     förrän filerna är lika.**
   - `efibootmgr -v` (fortfarande ingen `debian`-post), `ls
/boot/vmlinuz-6.12*`, `ls /boot/vmlinuz-6.1.0-54*` (fallbacken kvar).
   - Notera host-nyckelns fingeravtryck och `instance-id` (jämförs i D.10).
   - `sshd -t`, sedan en **ny** `ssh texttv.nu true` från Mac minin innan
     den gamla sessionen stängs (OpenSSH 10).
   - Webbkonsolen öppen. Sedan omstart (kärna 6.12, `/tmp` blir tmpfs).
9. `systemctl enable --now nginx`, importraden tillbaka, `systemctl enable
--now apt-daily.timer apt-daily-upgrade.timer`, sedan D.7 (som kör `artisan
up`). Ingen `apt autoremove` än (E). Slow log på en vecka
   (`slow_query_log=1`, `long_query_time=1` i `70-texttv.cnf`).
10. Verifiera som i C.4 (inklusive identiteten efter omstarten), plus `/status/health` 200, Pulsetic grönt, nästa
    import i `page_imports_logs`, `certbot renew --dry-run`, `/prod-health`.
11. **Beslut om MariaDB i unattended-upgrades:** `Package-Blacklist`
    påverkar bara automatiken; står den kvar får 11.8 aldrig sina
    säkerhetsfixar automatiskt. Förslag: ta bort den efter en vecka utan
    problem.

### E. Efteråt

- Uppdatera `server.md`, `CLAUDE.md`, `prod-health`-skillen och
  Obsidian-noten (append): 8.2 → 8.4, versioner, sökvägar, PHP-ritade delningsbilder (#34) för
  delningsbilderna; `last` finns inte i trixie, byt till `journalctl
--list-boots | tail -2`.
- fail2ban: om räknaren inte gick upp på klonen (OpenSSH 10), rätta enligt
  `fail2ban-regex` i C.4. `journalmatch` har redan
  `_SYSTEMD_UNIT=ssh.service`, så felet ligger troligen i filtrets prefix.
- PHPStan till 8.4 (`phpVersion: 80400`, workflowens `php-version`,
  `platform.php`), dev-stacken till 8.4 och MariaDB 11.8 i main.
- Kontrollera nästa morgons backup, radera snapshotarna efter en vecka
  (ta bort skyddet först).
- `apt autoremove` efter en vecka utan problem (tar bort 6.1-kärnan;
  `apt-mark auto` på den först).
- `mysql` → `mariadb` i prod-health-skillen om `mysql` saknas (C.4).
- Frivilligt: ta bort de oanvända `/etc/ssh/ssh_host_dsa_key*`.
- Flytta `character-set-collations` från `[mariadb-11.8]` till `[mysqld]` i `70-texttv.cnf`, så att den gäller även för framtida versioner (gruppen med versionsnummer läses bara av exakt 11.8).
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
mariadb.socket mariadb nginx`, `systemctl enable --now` på apt-timrarna,
importraden tillbaka, `php artisan up`. Det som går förlorat är importerade
sidor och `page_actions` sedan snapshoten. Tidsåtgången mäts i C.6.

**Radera aldrig servern och skapa den på nytt:** båda primary IP:na har
`auto_delete=yes`, så IPv4 och IPv6 skulle försvinna. Använd alltid
`rebuild`.

**Bootar servern inte** men disken är hel: rescue i stället för rebuild.
`hcloud server enable-rescue 65074277 && hcloud server reset 65074277`,
sedan i rescue: montera roten och `/boot/efi`, bind-montera `/dev /proc
/sys`, `chroot` och `grub-install --target=x86_64-efi
--efi-directory=/boot/efi --removable --no-nvram`. Alternativt välj 6.1 i
GRUB-menyn via webbkonsolen.

## Risker

- **MariaDB 10.11 → 11.8:** ny kostnadsmodell (kan inte stängas av; enda
  vägen tillbaka för en fråga är `FORCE INDEX`), kollation (låst i A.3),
  `innodb_snapshot_isolation` på (autocommit, låg risk; bevaka loggarna en
  vecka). Provkörningen ska visa planerna.
- **GRUB på EFI:** utan B.1 kan servern sluta boota vid nästa kallstart
  eller värdflytt.
- **PHP 8.4:** Laravel 8 fungerar med nette-fixen (provat), Laravel 9–10
  stöds inte på 8.4; CodeIgniter 2.2.6 gav byte-identiska svar.
- **Delningsbilderna:** ritas i PHP före bytet (#34, A.2).
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

**Varv 3 (Linux, 2026-10-29, C och D):** inga blockerare för C.
Hetzner-backup är påslagen (planen sa fel); rollbacken prövas aldrig
(C.6); webbkonsolen med prods eget lösenord (C.2); `bootcmd` stoppar inte
cron och timrarna (`mask`); shim kan byta `BOOTX64.EFI` och `cmp` saknade
åtgärd; D.7 krockade med workflowen och #33; D.1 väntade inte in pågående
importjobb; `autoremove` tog fallback-kärnan; cloud-init 25.1 kan byta
host-nycklar och nätregler; deployfrysning mot #15; ny SSH-inloggning före
omstart; rescue-väg; radera aldrig servern (IP:na följer med).

Ett eget test 2026-10-28: wkhtmltox renderar delningsbilderna fel, Chromium
rätt men tungt; Pär valde att rita bilderna i PHP (#34, A.2).

## Confidence

medel–hög — alla blockerare har konkreta åtgärder som görs före bytet, och
det som återstår som okänt (frågeplaner i 11.8, GRUB-fallbacken) prövas på
klonen.
