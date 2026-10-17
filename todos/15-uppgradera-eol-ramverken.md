**Status:** blockerad
**Senast uppdaterad:** 2026-10-17
**Blockerad av:** #17 (Debian 13 och PHP 8.4)

# Todo #15 — Uppgradera EOL-ramverken (CodeIgniter 2, Laravel 8)

Utbruten ur #08 (fynd M4) 2026-10-14. Webben kör CodeIgniter 2.2.6 (2015) och
importern Laravel 8.83.27 (sista fixen jan 2023). Inga kända CVE:er träffar
koden som den används, men inga fixar kommer heller.

## Snabbresearch 2026-10-14

### Det som faktiskt sätter tidsgränsen är PHP och OS, inte ramverken

- Prod kör **Debian 12 (bookworm) med Debians egen `php8.2`**. PHP-projektets
  säkerhetsstöd för 8.2 slutar 2026-12-31, men Debian backporterar
  säkerhetsfixar till sitt paketerade 8.2 under bookworms livstid (Debian LTS
  till och med juni 2028). Den verkliga gränsen är alltså OS-uppgraderingen,
  och Debian 13 (trixie) har PHP 8.4.
- **Kontrollerat på prod 2026-10-14:** `php8.2-fpm 8.2.34-1~deb12u1` kommer från
  `bookworm-security`, och changeloggen säger "Non-maintainer upload by the LTS
  Team" med fyra CVE-fixar (bl.a. mysqlnd), byggd 2026-10-04. Säkerhetsfixarna
  kommer alltså. Att byta till PHP 8.4 nu (via sury.org eller Debian 13) ger
  risk utan vinst. Gör det som en uppgradering till Debian 13 under 2027,
  tillsammans med Laravel 13. Plan i
  [#17](17-uppgradera-prod-till-debian-13.md) (på plats, inte ny server).
- **Båda apparna fungerar på PHP 8.4** (prod kör 8.2), provat i Docker med
  `serversideup/php:8.4`:
  - **Webben (CI 2):** 13 sidor och API-anrop byte-identiska med 8.2. Enda
    skillnaden är deprecation-notiser (`E_STRICT is deprecated` i
    `system/core/Exceptions.php`, `log_message()`-signaturen), som bara skrivs
    ut i development-läget. `system/` är redan lokalt patchad med
    `AllowDynamicProperties`.
  - **Importern (Laravel 8):** `texttv:import` fungerar, PHPUnit ger samma
    resultat som på 8.2 (samma två fel som redan fanns). Ramverket ger
    ~100 deprecation-notiser vid boot (t.ex. implicit nullable i
    `Illuminate\Log\Logger`), men Laravel 8.83 loggar dem i stället för att
    kasta.
- Slutsats: OS/PHP-uppgraderingen blockeras inte av ramverken. Ramverken kan
  uppgraderas i egen takt.

### Laravel 8 → 13 (importern)

- **Supportläge:** 18 månaders buggfixar och 2 års säkerhetsfixar per version.
  Laravel 11 är utan stöd sedan 2026-03-12. Laravel 12 (PHP 8.2–8.5) får bara
  säkerhetsfixar till 2027-02-24. Laravel 13 (PHP 8.3–8.5) har fullt stöd,
  med säkerhetsfixar till Q1 2028.
- **Laravel 13 kräver PHP 8.3**, alltså inte möjligt på prod:s Debian-8.2.
  Antingen Laravel 12 nu (säkerhetsstöd i ~4 månader) eller Laravel 13
  tillsammans med OS-uppgraderingen. Det senare är rimligast.
- **Appen är liten:** ~5 800 rader i `app/`, `routes/`, `config/` och
  `database/`. 6 artisan-kommandon, 3 controllers, 3 modeller, ingen auth,
  inga köer. Har PHPUnit-tester.
- **Paket som försvinner eller måste bytas på vägen:**
  - `fideloper/proxy` och `fruitcake/laravel-cors`: inbyggda i Laravel 9+.
  - `spatie/laravel-short-schedule`: **används inte** (granskat 2026-10-17).
    `Kernel.php` har ingen `shortSchedule()` och prod-cron kör bara
    `schedule:run`. Kan tas bort redan nu, oberoende av uppgraderingen.
  - `dgoring/dom-query`: v1.0.0 från 2022-10-27 är enda releasen, i praktiken
    övergivet. Används bara på två ställen (`Importer.php:81` och `:512`).
    Ersätt med PHP 8.4:s inbyggda `Dom\HTMLDocument` (har `querySelector()`)
    eller `symfony/dom-crawler` i samma veva. Litet byte, inte längre den
    stora risken.
- **Väg:** stegvis 8 → 9 → 10 → 11 → 12 → 13 enligt Laravels upgrade-guider,
  eller betald automatisering via Laravel Shift. Uppskattning: en till två
  dagar, mest konfig-flytt (Laravel 11 slimmade skelettet).

### CodeIgniter 2 (webben)

Fyra vägar:

1. **Stå kvar på CI 2.** Fungerar på 8.4 (provat i Docker). Ingen säkerhetsfix kommer,
   men angreppsytan är liten: inga inloggningar, inga sessioner, ingen
   uppladdning, all SQL-input går genom `is_numeric`/`intval`/`escape` (#08).
2. **CI 3.1.13.** Uppgraderingen är mest mekanisk (versala filnamn på
   controllers/modeller/libraries, ny `system/`, ny `mimes.php`, sessions-
   biblioteket omskrivet men används inte). Men CI 3 är själv i
   underhållsläge, och den officiella grenen är inte uppdaterad för PHP 8.2+.
   Den community-fork som håller CI 3 kompatibel med PHP 8.2–8.4
   (`pocketarc/codeigniter`) gör bara säkerhetsfixar. Vinsten mot alternativ
   1 är liten.
3. **CI 4.** Aktivt utvecklat (4.7.x, PHP 8.1+), men ett annat ramverk. I
   praktiken en omskrivning.
4. **Flytta webben in i Laravel-appen.** Ett ramverk och en kodbas i stället
   för två som delar en databas. Ungefär lika mycket arbete som CI 4:
   15 controllers (2 238 rader, varav `test.php` och `welcome.php` är död
   kod), en modell (1 069), en helper (1 465), en library (287) och 34 vyer
   (3 978). Räknat 2026-10-17. 33 routes som måste behålla exakt
   samma URL:er, och `/api/*` måste vara byte-identiskt för apparna.
   `make test-api` är byggt för just den kontrollen. Det behöver då jämföra
   två körande appar i stället för två git-refs.

## Rekommendation

- **Ingen brådska.** Inget i ramverken hindrar en PHP- eller OS-uppgradering.
- **Laravel:** gör det i samband med flytten till Debian 13 och PHP 8.4, och
  gå direkt till Laravel 13. Liten app med tester, låg risk.
- **CodeIgniter:** stå kvar (alternativ 1) tills det finns ett konkret behov.
  Blir det aktuellt är alternativ 4 bättre än 2 och 3, eftersom det ger ett
  ramverk i stället för två.

## Kan göras nu, oberoende av uppgraderingen

Från granskningen 2026-10-17:

- [x] Ta bort `spatie/laravel-short-schedule` ur importern. Gjort 2026-10-17
  (939e2af, deployat); bara paketet och `spatie/temporary-directory`
  försvann ur låsfilen.
- [x] Ta bort `controllers/test.php` och `controllers/welcome.php` och routen
  `$route['dev']` (pekar på en controller som inte finns). `/test` och
  `/welcome` svarar 200 i prod; `/test` skriver ut `<br>start` och sida 100
  rått. Gjort 2026-10-17, även vyn `welcome_message.php`.
- [x] Fixa de två PHPUnit-tester som redan fallerar, så att testsviten duger
  som verifiering inför Laravel-hoppet. Gjort 2026-10-17: båda var inaktuell
  testdata från 2021. `test_linkify` testar nu `linkifySingleLine()`, den
  metod prod använder, och `test_linkify_pipeline` kör hela kedjan
  cleanup → colorize → linkify. Det avslöjade att "Minst 2 300 strokefall"
  länkade 300.
  - Första fixen (5330773) gjorde hela-rad-spärren för tusental bredare, men
    den tog då också bort riktiga länkar på samma rad. Efter code review samma
    dag: spärren är borttagen och ersatt med lookbehinds i sidnummer-regexen
    som bara hoppar över själva tusentalet (`2 300`, `14 151`), tal direkt
    efter `siffra-` (`3 698-565`) och tal följda av ` kr`/` st`. Mot 421
    prod-sidor ändrades 5 rader på 763 och 778, alla belopp som slutade
    länkas. "Del 2 302" går inte att skilja från tusentalet "2 302" och
    länkas inte.
  - Död kod bort: `linkifySingleLineWithSpanSupport()` och parametern
    `$numberReplacements`, som aldrig sattes. Testsidan `100.html` har fått
    de gamla tabbarna borttagna i stället för att `cleanup()` ändras.
  - `test_colorize` saknar fortfarande assertions.

## Nästa steg (när #17 är på gång)

1. Laravel 8 → 13 på en branch. Byt `dgoring/dom-query` på vägen.
2. Verifiera med PHPUnit (gröna efter fixen ovan), en import i Docker på
   PHP 8.4, och **diffa `php artisan schedule:list` före och efter**.
   Laravel 11 flyttar schemat från `app/Console/Kernel.php` till
   `routes/console.php`, och där ligger de handjusterade cron-tiderna från
   #12 (t.ex. `1-59/10` för 500–599). De får inte tappas.

## Källor

- Laravels supporttabell: https://laravel.com/docs/12.x/releases
- CodeIgniter-versioner och supportläge: https://codeigniter.com/download
- Uppgradering CI 2.2 → 3.0:
  https://codeigniter.com/userguide3/installation/upgrade_300.html
- Community-fork för CI 3 på PHP 8.2+: https://github.com/pocketarc/codeigniter
