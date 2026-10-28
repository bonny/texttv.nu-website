**Status:** aktiv
**Senast uppdaterad:** 2026-10-28

# Todo #33 — Importerns storage-rättigheter återställs vid varje deploy

## Sammanfattning

`deploy-importer.yml` kör `sudo chown -R root:root .` och `chmod 777 -R
storage/` vid varje deploy. Det tog bort rättningen från 2026-10-27
(`storage/logs` 2775 `root:www-data`, `laravel.log` 664), och sedan dess är
`storage/`, `storage/logs` och `laravel.log` åter 777 `root:root` på prod
(kontrollerat 2026-10-28 efter deployen `ddda08e`). Samma sorts problem som
webbens `shares/`, som rättades i webb-workflowen 2026-10-28.

## Bakgrund

Två användare skriver i `storage/`:

- **root** via cron (`schedule:run` varje minut): `laravel.log`,
  `framework/cache`, schemats lås (`framework/schedule-*`).
- **www-data** via webben på `l.texttv.nu` (`/importstatus`, `/live`, `/db`):
  `laravel.log` och eventuellt `framework/views`.

777 löser det genom att alla får skriva. Rättningen 2026-10-27 gav i stället
gruppen `www-data` skrivrätt med setgid på katalogerna. Missen 2026-10-27
(`config:cache` med umask 077 gav 600 och 500-fel) visar att filer som root
skapar också måste vara läsbara och skrivbara för gruppen.

## Förslag

1. Byt i workflowen ut `chmod 777 -R storage/` mot samma läge som
   2026-10-27:
   ```
   sudo chown -R root:www-data storage bootstrap/cache
   sudo find storage bootstrap/cache -type d -exec chmod 2775 {} +
   sudo find storage bootstrap/cache -type f -exec chmod 664 {} +
   ```
   och kör `artisan optimize` med `umask 002`, så att filer som root skapar
   får gruppskrivrätt.
2. Se till att nya filer från cron också får `664`: `umask 002` först i
   cron-raden, eller Laravels `permission` i loggkanalen
   (`config/logging.php`, `'permission' => 0664`).
3. Kontrollera logrotate-regeln för `laravel.log` (todo #02): `create 664
root www-data`.
4. Verifiera efter deploy: `stat -c '%a %U:%G' storage storage/logs
storage/logs/laravel.log`, en loggrad som www-data (`/importstatus`), och
   `schedule:run` som root i några minuter utan "Permission denied".

## Risker

- Fel rättigheter ger 500 på `l.texttv.nu` eller att importen inte kan
  skriva lås och loggar. Rulla tillbaka genom att återställa workflowen;
  777 fungerar under tiden.

## Confidence

hög — samma mönster som `shares/` och rättningen 2026-10-27, bara flyttat in
i deployen så att det håller.
