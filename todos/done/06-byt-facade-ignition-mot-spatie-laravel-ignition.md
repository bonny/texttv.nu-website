**Status:** klar 2026-10-14
**Senast uppdaterad:** 2026-10-14

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

## Genomfört 2026-10-14

- `facade/ignition` ersatt med `spatie/laravel-ignition` 1.7.2. Laravel var
  redan 8.83.27, så ingen ramverksuppgradering behövdes. Inget annat paket
  berodde på `facade/ignition`.
- Lock-ändringen hölls minimal (`--minimal-changes -w`, inte `-W`): tre paket
  bort (`facade/ignition`, `facade/flare-client-php`, `symfony/polyfill-php72`),
  sex nya för spatie, och patch-uppdateringar av `symfony/{process,mime,
  http-foundation,polyfill-intl-idn}` 5.4 och `nesbot/carbon` 2.65 → 2.73.
  Ett första försök med `-W` uppgraderade allt och kastades.
- `"php"` höjd till `^8.1`, eftersom `carbonphp/carbon-doctrine-types` 3.x
  kräver det. Prod kör 8.2. CI:s PHP 7.4 påverkas inte: workflowen kör ingen
  PHP, `composer install` sker på servern.
- `error_reporting`-workarounden i `AppServiceProvider` borttagen.
- Verifierat i Docker (PHP 8.2): ett undantag via artisans riktiga
  error-handler renderas rent utan deprecation eller fatal, `strlen(null)` i
  en artisan-körning loggas och körningen fortsätter (Laravel 8.83 loggar
  deprecations i stället för att kasta), `texttv:import 100,129` fungerar,
  och importerns webbsidor svarar 200/404 som förut.
- PHPUnit: samma resultat före och efter (9 tester, 2 fel i
  `ImportTest::test_cleanup` och `test_linkify`, 1 risky). Felen fanns redan
  och har inget med bytet att göra.

Punkt 5 i förslaget (`composer install --no-dev` i deployen) är inte gjord.
Den skulle ta bort Ignition helt från prod, men är en egen ändring.

Efter deploy: kontrollera att `composer install` gick igenom i
workflow-loggen och att nästa schemalagda import i `importer`-loggen på prod
inte visar några nya fel.
