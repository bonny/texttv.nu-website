**Status:** aktiv
**Senast uppdaterad:** 2026-05-27

# Todo #06 — Byt facade/ignition mot spatie/laravel-ignition

## Sammanfattning

`facade/ignition` v2.x ligger som `require-dev` i `importer/composer.json` men paketet är arkiverat sedan länge och innehåller PHP-syntax (`${var}`-interpolation i `vendor/facade/ignition/src/SolutionProviders/MergeConflictSolutionProvider.php:52`) som blivit deprecated i PHP 8.2. När Laravels error-handler under exception-rendering autoloadar den filen kastar PHP en deprecation-notis, som Whoops konverterar till en fatal exception — vilket dödar artisan-kommandon mitt i en loop och döljer den ursprungliga felorsaken.

Idag (2026-05-27) maskerades detta genom att exkludera `E_DEPRECATED` från `error_reporting` i `AppServiceProvider::register()` (commit 6ba0656). Det är en workaround, inte en fix — vi tappar legit deprecation-rapportering från övrig kod, och nästa gång ett vendor-paket gör något knepigt får vi det tyst.

Korrekt fix: byt till `spatie/laravel-ignition` (efterföljaren), ta bort `error_reporting`-workarounden, verifiera att error-rendering fungerar.

## Bakgrund

- `facade/ignition` arkiverades när Spatie tog över utvecklingen. För Laravel 8+ är `spatie/laravel-ignition` v1.x rätt paket. v1 stödjer Laravel 8.77+ och PHP 8.0+.
- Vår `composer.json`: Laravel `^8.12`, PHP `^7.3|^8.0`. Vi kör 8.2.31 på prod, 8.1 lokalt (Valet). CI pinnar 7.4 men bara för composer/phpunit-stegen, inte runtime (per `server.md`).
- Det här är dev-dep, men deployen på Hetzner installerar uppenbarligen även dev-deps eftersom buggen syns där. Värt att verifiera och eventuellt byta till `composer install --no-dev` i deploy-workflowen som separat åtgärd.

## Förslag

1. I `importer/composer.json`:
   - Ta bort `"facade/ignition": "^2.5"` från `require-dev`.
   - Lägg till `"spatie/laravel-ignition": "^1.0"` i `require-dev`.
2. `composer update facade/ignition spatie/laravel-ignition` lokalt, kontrollera att det går igenom utan att låsa övriga deps.
3. Ta bort `error_reporting`-raden i `app/Providers/AppServiceProvider.php` (workarounden från commit 6ba0656). Lämna kommentaren kvar tills nästa cron-cykel verifierat att inga deprecation-exceptions kommer tillbaka.
4. Trigga en kontrollerad exception lokalt (t.ex. importera en sida med tillfälligt trasig URL) och bekräfta att stack trace renderas snyggt och kommandot fortsätter loopa via vår nya try/catch.
5. Separat övervägande: byt deploy-stegen till `composer install --no-dev` så vi inte ens får dev-deps på prod. Egen todo om det.

## Risker

- `spatie/laravel-ignition` v1 kräver Laravel 8.77+. Vi har `^8.12` — bör verifiera att låst version i `composer.lock` är ≥ 8.77, annars uppgradera Laravel först.
- Andra paket kan ha transitive dep på `facade/ignition` (osannolikt men kolla `composer why facade/ignition`).
- PHP 7.3-stödet i vår `composer.json` är kosmetiskt sedan länge — `spatie/laravel-ignition` v1 kräver PHP 8.0+, så installen kommer inte gå igenom på 7.x. Bör samtidigt höja `"php"` i `composer.json` till `^8.0` så vi inte ljuger om vad vi stödjer.

## Confidence

Medel — själva paketbytet är rakt, men risken ligger i att Laravel-versionen är gammal nog att `spatie/laravel-ignition` v1 inte plug-and-playas. Behöver verifieras med ett dry-run `composer update` innan vi vet.
