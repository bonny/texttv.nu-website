**Status:** aktiv — plan klar, steg 1 (kartläggning) näst
**Senast uppdaterad:** 2026-10-27

# Todo #30 — Egna databasanvändare för webben och importern

## Sammanfattning

Både webben (CodeIgniter) och importern (Laravel) loggar in i MariaDB som
`root`. Den som får köra kod eller SQL via webben — en SQL-injektion i
CI 2-koden räcker — får därmed full kontroll: alla databaser, `GRANT`, och
`FILE`, som läser och skriver filer på servern. `secure_file_priv` är inte
satt, så `LOAD_FILE()` når t.ex. importerns `.env` (verifierat 2026-10-27,
bara längden lästes).

Förslag: två användare med bara de rättigheter apparna behöver,
`texttv_web` och `texttv_importer`, och `root` bara via unix_socket.

## Bakgrund

- **Webben:** nginx sätter `fastcgi_param DB_USERNAME root`, `DB_PASSWORD`,
  `DB_DATABASE`, `DB_DATABASE_STATS` i `/etc/nginx/sites-enabled/texttv.nu`.
  `database.php` använder samma användare för båda anslutningarna:
  `default` (`texttv.nu`) och `stats` (`texttv_stats`). Host `localhost`,
  alltså unix-socket.
- **Importern:** `.env` har `DB_USERNAME=root`, `DB_HOST=localhost`.
  Anslutningen `mysql_stats_db` (`config/database.php`) används av
  `texttv:cleanup-page-actions` för att radera i `texttv_stats.page_actions`.
- **Korsdatabas-query:** `/api/most_read` (`texttv_helper.php`) läser
  `` `texttv.nu`.texttv `` och `texttv_stats.page_actions` på
  default-anslutningen, så webbanvändaren behöver `SELECT` i båda.
- `texttv_blogg`, `texttv_page_text` och `texttv_log` är tabeller i
  `texttv.nu`, inte egna databaser.
- **Migrationer** körs inte av deployen (bara `composer install` +
  `php artisan optimize`). De kan köras för hand som root.
- 2026-10-27: `root@localhost` är `unix_socket OR mysql_native_password`
  (samma hash som förut), se Obsidian-noten `Logg - texttv.nu`. Det gör steg 6 möjligt.
- Lösenordet för root läckte i en granskares transkript 2026-10-27. Pär valde
  att inte byta det; det blir ofarligt när root inte längre har lösenord
  (steg 6).

## Förslag

1. **Kartlägg vad som används** (read-only). Utgångsläge ovan, verifiera
   med:
   - grep i `texttv.nu/` och `importer/` efter tabellnamn och skrivande SQL
     (`INSERT`, `UPDATE`, `DELETE`, `REPLACE`, `TRUNCATE`, `CREATE`, `ALTER`).
   - `performance_schema`/general log är överkurs; grep räcker för en
     liten kodbas, och `make test-api` täcker webbens läsvägar.
   - Resultat: en tabell databas → tabell → läs/skriv per app.
2. **Skapa användarna** som root via socket, med starka lösenord som aldrig
   skrivs ut (genereras på servern med `openssl rand`, läggs direkt i
   konfigurationen):
   ```sql
   CREATE USER texttv_web@localhost IDENTIFIED BY '…';
   GRANT SELECT, INSERT, UPDATE, DELETE ON `texttv.nu`.* TO texttv_web@localhost;    -- smalna av efter steg 1
   GRANT SELECT, INSERT, UPDATE, DELETE ON texttv_stats.* TO texttv_web@localhost;
   CREATE USER texttv_importer@localhost IDENTIFIED BY '…';
   GRANT SELECT, INSERT, UPDATE, DELETE ON `texttv.nu`.* TO texttv_importer@localhost;
   GRANT SELECT, DELETE ON texttv_stats.page_actions TO texttv_importer@localhost;
   ```
   Inga globala rättigheter, ingen `CREATE`/`ALTER`/`DROP`, ingen `FILE`.
3. **Provkör** som `www-data` med PHP mot de nya användarna, innan något
   byts: en läsning per tabell, en skrivning i en transaktion som rullas
   tillbaka.
4. **Byt importern först** (enklast att verifiera):
   `.env` → `php artisan config:cache` (prod kör med cachad config,
   `bootstrap/cache/config.php`). Kontrollera nästa import,
   `/importstatus` och `laravel.log`. Tillbaka: återställ `.env` + `config:cache`.
5. **Byt webben:** `fastcgi_param DB_USERNAME/DB_PASSWORD` i nginx,
   `nginx -t`, `systemctl reload nginx`. Kontrollera `/`, `/100`,
   `/api/get/100`, `/api/most_read/news`, `/api/page/...` (statistikskrivning),
   arkiv, RSS och blogg. Kör gärna `tests/api/urls.txt` mot prod. Tillbaka:
   återställ raderna + reload.
6. **Efter en lugn vecka:** `ALTER USER root@localhost IDENTIFIED VIA unix_socket`
   (utan lösenord). Ta bort root-lösenordet ur servernoten och Proton Pass,
   och rensa de sista backup-filerna som innehåller det
   (`/root/nginx-texttv.nu.bak-20261026-php2shell`,
   `/root/config-backups-2026-10-15/texttv.nu`).
7. **Extra skydd, vid nästa planerade MariaDB-omstart** (#17 är ett bra
   tillfälle): `secure_file_priv` mot en tom root-ägd katalog och
   `local_infile = 0`, i en egen fil under `/etc/mysql/mariadb.conf.d/`.
   Stänger filfunktionerna även för root.

Logga varje prod-steg i Obsidian-noten `Logg - texttv.nu` samma dag.

## Risker

- **En missad rättighet** ger fel först när just den koden körs (t.ex. ett
  sällan använt API eller en cleanup-kommando nattetid). Därför
  kartläggning först, importern och webben på olika steg, och
  `laravel.log` + nginx error-log efter ett dygn.
- **Cachad config i importern:** ändras `.env` utan `config:cache` används
  fortfarande root. Verifiera med `current_user()` från appen.
- **`make test-api` täcker inte `/api/page` och `share`/`screenshot`** —
  testa dem för hand efter steg 5.
- **Lösenord i klartext:** de nya lösenorden hamnar i `.env` och nginx-confen,
  som idag. Det är samma nivå som nu, men med mindre makt bakom.

## Confidence

medel-hög — tekniken är enkel och varje steg kan rullas tillbaka på sekunder
(root-lösenordet är oförändrat till steg 6). Osäkerheten ligger i om
kartläggningen hittar alla skrivvägar.
