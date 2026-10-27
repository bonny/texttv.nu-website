**Status:** aktiv — steg 1–4 klara 2026-10-27 (importern kör som `texttv_importer`); följ upp nattjobben 2026-10-28, sedan steg 5 (webben)
**Senast uppdaterad:** 2026-10-27

# Todo #30 — Egna databasanvändare för webben och importern

## Sammanfattning

Både webben (CodeIgniter) och importern (Laravel) loggar in i MariaDB som
`root`. En SQL-injektion i CI 2-koden räcker därför för full kontroll: alla
databaser, `GRANT`, och `FILE`, som läser och skriver filer på servern.
`secure_file_priv` är inte satt, så `LOAD_FILE()` når t.ex. importerns
`.env` (verifierat 2026-10-27, bara längden lästes).

Förslag: två användare med rättigheter per tabell, `texttv_web` och
`texttv_importer`, en tredje för bloggskrivning, och till sist `root` bara
via unix_socket.

## Bakgrund

### Anslutningar

- **Webben:** nginx sätter `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` och
  `DB_DATABASE_STATS` som `fastcgi_param` i ett enda `location ~ \.php$`-block
  i `/etc/nginx/sites-enabled/texttv.nu`. `database.php` använder samma
  användare för `default` (`texttv.nu`) och `stats` (`texttv_stats`). Host
  `localhost`, alltså unix-socket. `pconnect` gör inget i CI 2
  (`mysqli_driver.php:90–93`).
- **Importern:** **två** anslutningar i `.env`, båda root:
  `DB_USERNAME`/`DB_PASSWORD` (`mysql`) och `DB_STATS_USERNAME`/`DB_STATS_PASSWORD`
  (`mysql_stats_db`, `config/database.php:66–82`). Host `localhost`. Prod
  kör med cachad config (`bootstrap/cache/config.php`). Ingen queue worker
  (`QUEUE_CONNECTION=sync`), cache och sessioner är `file`.
- `@localhost` matchar både socket och TCP mot 127.0.0.1
  (`skip_name_resolve=0`). Skapa ingen `@127.0.0.1`.

### Kartläggning (granskad i koden 2026-10-27)

| Användare | Tabell | Rättigheter | Var i koden |
| --- | --- | --- | --- |
| webben | `texttv.nu`.`texttv` | SELECT, UPDATE (`is_shared`) | `texttv_helper.php:1828` |
| webben | `texttv.nu`.`texttv_blogg` | SELECT | `blogg.php`, `rssfeed.php` |
| webben | `texttv.nu`.`texttv_page_text` | SELECT | |
| webben | `texttv_stats`.`page_actions` | SELECT, INSERT | `api.php:744`, korsdatabas-query `texttv_helper.php:196` |
| importern | `texttv.nu`.`texttv` | SELECT, INSERT, UPDATE, DELETE | `texttvimport.php:189`, `CleanupOldPages.php:54` |
| importern | `texttv.nu`.`page_imports_logs` | SELECT, INSERT, DELETE | `ImportstatusController.php` |
| importern | `texttv_stats`.`page_actions` | SELECT, DELETE | `CleanupPageActions.php:42` |

- `Texttv_page::save()` (`texttv_page.php:602–634`) är död kod: anropas bara
  från bortkommenterade `update_page*`.
- Inga `TRUNCATE`, `CREATE`, `LOCK TABLES`, `REPLACE` eller
  information_schema-frågor. Inga vyer, rutiner, triggers eller events i prod
  (bara `sys`/`mysql`; `event_scheduler=OFF`). Vyn i `tests/api/setup.sql` finns
  bara lokalt.
- `texttv` och `texttv_blogg` är **MyISAM** (resten InnoDB), så transaktioner
  och ROLLBACK fungerar inte på dem.
- `texttv_blogg`, `texttv_page_text` och `texttv_log` är tabeller i
  `texttv.nu`, inte egna databaser.

### Övrigt

- Migrationer körs inte av deployen (bara `composer install` +
  `php artisan optimize`).
- **Bloggen:** Pär skriver inlägg direkt i `texttv_blogg` med TablePlus eller
  Sequel Ace via SSH-tunnel som root (Obsidian-noten
  `Hur skriva nytt blogginlägg på TextTV.nu`). Det slutar fungera i steg 6.
- **Fel syns inte i prod:** CI har `db_debug` av och `log_threshold = 0`, så en
  nekad fråga ger bara tomt resultat. `/api/page` kontrollerar inte sin
  `insert`. nginx `fastcgi_cache_use_stale … http_500` kan dölja 500-fel bakom
  gammal cache.
- 2026-10-27: `root@localhost` är `unix_socket OR mysql_native_password`
  (samma hash som förut). Root-lösenordet läckte i två granskares
  transkript samma dag; Pär valde att inte byta det, eftersom steg 6 gör det
  ofarligt.

## Förslag

1. **Förberedelser.**
   - `SET GLOBAL userstat=1` (dynamisk, idag 0). Ger
     `information_schema.USER_STATISTICS` per användare: `TOTAL_CONNECTIONS`,
     `ROWS_UPDATED`, `ACCESS_DENIED`, `DENIED_CONNECTIONS`. Det är så vi ser att
     inget längre använder root och att ingen rättighet saknas.
   - `SET GLOBAL local_infile=0` (dynamisk). Lägg samtidigt in den i
     konfigurationsfilen från steg 7 så den gäller efter omstart.
2. **Skapa användarna** som root via socket. Lösenord genereras på servern med
   `openssl rand -hex 32` (inte base64: `$` och `;` ställer till det i nginx,
   `#` och mellanslag i `.env`). SQL skickas med heredoc på stdin, aldrig som
   `-p…` i argv. Lösenorden skrivs aldrig ut.
   ```sql
   CREATE USER texttv_web@localhost IDENTIFIED BY '…';
   GRANT SELECT, UPDATE (is_shared) ON `texttv.nu`.texttv TO texttv_web@localhost;
   GRANT SELECT ON `texttv.nu`.texttv_blogg TO texttv_web@localhost;
   GRANT SELECT ON `texttv.nu`.texttv_page_text TO texttv_web@localhost;
   GRANT SELECT, INSERT ON texttv_stats.page_actions TO texttv_web@localhost;

   CREATE USER texttv_importer@localhost IDENTIFIED BY '…';
   GRANT SELECT, INSERT, UPDATE, DELETE ON `texttv.nu`.texttv TO texttv_importer@localhost;
   GRANT SELECT, INSERT, DELETE ON `texttv.nu`.page_imports_logs TO texttv_importer@localhost;
   GRANT SELECT, DELETE ON texttv_stats.page_actions TO texttv_importer@localhost;
   ```
   Inga globala rättigheter, ingen DDL, ingen `FILE`. `FLUSH PRIVILEGES`
   behövs inte. Valfritt: `WITH MAX_USER_CONNECTIONS 30` (poolen har 10 barn,
   högst 2 anslutningar per request).
3. **Provkör** som `www-data` med PHP mot de nya användarna, innan något byts.
   `SHOW GRANTS FOR …`, en läsning per tabell, och skrivrättigheter med satser
   som inte träffar några rader (rättigheten kontrolleras ändå, och MyISAM gör
   ROLLBACK verkningslös):
   - `UPDATE texttv SET is_shared=is_shared WHERE id=0`
   - `DELETE FROM texttv WHERE 1=0`
   - `INSERT INTO page_actions (page_ids,type,created_at) SELECT 1,'VIEW',NOW() FROM DUAL WHERE 0`
   - och en sats per rättighet som **inte** ska finnas (t.ex. `DELETE` som
     webben), som ska nekas.
4. **Byt importern först.**
   - Backup av `.env` till `/root`, `chmod 600`.
   - Byt `DB_USERNAME`, `DB_PASSWORD`, `DB_STATS_USERNAME` och
     `DB_STATS_PASSWORD` till `texttv_importer`, sedan `php artisan config:cache`
     med normal umask (filen måste vara 644, www-data läser den).
   - Kontrollera båda anslutningarna:
     `php artisan tinker --execute='dump(DB::select("select current_user() u"), DB::connection("mysql_stats_db")->select("select current_user() u"));'`
   - Följ nästa import, `/importstatus`, `laravel.log` och `USER_STATISTICS`
     (`ACCESS_DENIED` ska vara 0). `cleanup-page-actions` och
     `cleanup-old-pages` körs sällan; kör dem för hand eller vänta in dem.
   - Tillbaka: återställ `.env` och `config:cache`.
5. **Byt webben.**
   - Backup av nginx-confen till `/root` (inte under `/etc/nginx/`, se
     `server.md`), `chmod 600`.
   - Byt `fastcgi_param DB_USERNAME/DB_PASSWORD`, `nginx -t`,
     `systemctl reload nginx`. php-fpm behöver ingen reload.
   - Kontrollera med cache-buster och kräv `X-Cache: MISS` och rätt innehåll,
     inte bara 200: `/`, `/100`, `/api/get/100`, `/api/most_read/news`, arkiv,
     RSS, blogg, och `/api/page/...` (antalet rader i `page_actions` ska öka).
     Kör `tests/api/urls.txt` mot prod. Följ `USER_STATISTICS`.
   - Tillbaka: återställ raderna och reload.
   **5b. Bloggskrivning:** `texttv_admin@localhost` med
   `SELECT, INSERT, UPDATE, DELETE ON` `` `texttv.nu` ``.`texttv_blogg`, eget
   lösenord i Proton Pass, för TablePlus/Sequel Ace via tunnel. Fråga Pär om
   andra GUI-anslutningar som root.
6. **Efter en lugn vecka:** när `USER_STATISTICS` visar att root bara används
   via socket.
   - `ALTER USER root@localhost IDENTIFIED VIA unix_socket` (utan lösenord).
     debian-start påverkas inte (`debian.cnf` saknar lösenord). Ingen cron
     eller backup använder root-lösenordet.
   - Ta bort root-lösenordet ur Obsidian-noten `Server - texttv.nu Hetzner`
     (där står även ett rescue-lösenord i klartext) och ur Proton Pass.
   - Rensa backupfilerna som innehåller det: `/root/nginx-texttv.nu.bak-20261026-php2shell`,
     `/root/config-backups-2026-10-15/texttv.nu`, och backuperna från steg 4–5.
   - `SET GLOBAL userstat=0`.
   - **Migrationer därefter:** prod kör med cachad config, så env-variabler på
     kommandoraden ignoreras. Rutin: ge `texttv_importer` tillfälliga
     DDL-rättigheter plus rätt på `migrations`, kör `php artisan migrate`,
     återkalla. (Alternativet, att köra som OS-root mot en tillfällig config med
     root via socket, är inte testat med mysqlnd.)
7. **Vid nästa planerade MariaDB-omstart** (#17 är ett bra tillfälle):
   `secure_file_priv` mot en tom root-ägd katalog, i en egen fil under
   `/etc/mysql/mariadb.conf.d/`. Katalogen måste finnas **före** omstarten,
   annars startar inte mariadbd. I MariaDB betyder tomt eller NULL "ingen
   begränsning", så det är så man stänger av filfunktionerna. Litet värde när
   ingen app-användare har `FILE`, men stänger dem även för root.

Logga varje prod-steg i Obsidian-noten `Logg - texttv.nu` samma dag.

## Logg

- 2026-10-27: steg 1 (`userstat=1`, `local_infile=0`, båda dynamiska och tillbaka vid omstart tills steg 7). Steg 2–3 för `texttv_importer`: skapad, lösenordet i `/root/texttv_importer.pw` (600, raderas efter steg 4). Provkörd som www-data med PDO: 10 tillåtna satser OK, 7 otillåtna nekade. Importern gör bara `create`/`where`/`delete` på `page_imports_logs`, så UPDATE behövs inte där.

- 2026-10-27 20:06: steg 4. `.env` (båda anslutningarna) → `texttv_importer`, `config:cache`, `current_user()` = `texttv_importer@localhost` på båda. **Lärdom:** kör aldrig `config:cache` med `umask 077` — `bootstrap/cache/config.php` blev 600, importerns webbrutter gav 500 i ~3 min (`chmod 644`). Samtidigt 27 nekade inloggningar och 6 nekade frågor för `texttv_importer`, inga efter 20:10; troligen processer som läste `.env`/cachen mitt i bytet (båda skrevs icke-atomärt), inte bevisat. Importen skriver (970 rader in första 8 min). `/root/texttv_importer.pw` raderad; rollback-kopia `/root/importer.env.bak-20261027` (600, innehåller root-lösenordet → rensas i steg 6).

## Risker

- **En missad rättighet** syns först när just den koden körs, och felen syns
  inte i CI. Därför `USER_STATISTICS`, importern och webben i olika steg, och
  koll efter ett dygn och efter att de sällan körda cleanup-jobben gått.
- **Cachad config:** ändras `.env` utan `config:cache` används fortfarande root.
  Verifiera med `current_user()` på båda anslutningarna. `config:cache` skriver
  inte filen atomärt, så en schedule-körning precis vid bytet kan krascha en
  gång.
- **Skyddet gäller SQL-injektion, inte kodkörning.** Webben och importern kör
  i samma php-fpm-pool som `www-data`, och importerns `.env` och
  `bootstrap/cache/config.php` är läsbara (644). Den som kan köra PHP via
  webben får därför importerns rättigheter. Egen pool/användare för importern
  ligger utanför #30.
- `routes/api.php:17` (`auth:api` → tabellen `users`) ger 500 i stället för
  401 om någon skickar `api_token`. Ingen legitim klient gör det.
- **Lösenord i klartext:** de nya hamnar i `.env` och nginx-confen, som idag,
  men med mycket mindre makt bakom.

## Confidence

hög för tekniken, medel för fullständigheten — kartläggningen är gjord två
gånger i koden och prod saknar vyer och triggers, men felen syns inte i CI,
så `USER_STATISTICS` får vara facit. Varje steg till och med 5b kan rullas
tillbaka på sekunder.
