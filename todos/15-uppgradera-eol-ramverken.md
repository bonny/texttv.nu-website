**Status:** aktiv (granskad 2026-10-28: förberedelser på L8, sedan L9 → L12 på en branch och en deploy av L12, se sista avsnittet)
**Senast uppdaterad:** 2026-10-28
**Blockerad av:** #17, bara för sista steget till Laravel 13

# Todo #15 — Uppgradera EOL-ramverken (CodeIgniter 2, Laravel 8)

Utbruten ur #08 (fynd M4) 2026-10-14. Webben kör CodeIgniter 2.2.6 (2015) och
importern Laravel 8.83.27 (sista fixen jan 2023). Inga kända CVE:er träffar
koden som den används, men inga fixar kommer heller.

## Tillägg 2026-10-26: stegvis plan, Laravel 8 → 12 behöver inte vänta på #17

> Ändrad av granskningen 2026-10-28 längst ned: en deploy per major går inte, eftersom L9–L11 inte kan installeras stabilt.

Laravel 9, 10, 11 och 12 stöder alla PHP 8.2 (L9 8.0–8.2, L10 8.1–8.3, L11
8.2–8.4, L12 8.2–8.5). Det är bara sista hoppet, 12 → 13, som kräver PHP 8.3
och därmed Debian 13. Importern kan alltså uppgraderas **en major i taget på
nuvarande prod**, med en deploy och några dagars observation per steg, i
stället för allt på en gång ihop med OS-bytet.

1. **Förberedelse (oberoende):** #29 (timeout i importern), sedan byt
   `dgoring/dom-query` mot `symfony/dom-crawler` (fungerar på 8.2;
   `Dom\HTMLDocument` kräver 8.4). Ett eget steg med PHPUnit-korpusen som
   kontroll. **Klart:** #29 deployat 2026-10-26, bytet i `b476a54` 2026-10-27.
2. **Laravel 9:** ta bort `fideloper/proxy` och `fruitcake/laravel-cors`
   (inbyggda). PHPUnit, en import i Docker, `schedule:list` före/efter, deploy.
3. **Laravel 10.** Samma kontroller.
4. **Laravel 11:** det stora steget, slimmat skelett. Schemat flyttar till
   `routes/console.php`; diffa `schedule:list` mot steg 3, cron-tiderna från
   #12 får inte tappas. Valfritt: behåll gamla strukturen, L11 stöder det.
5. **Laravel 12.** Säkerhetsfixar till 2027-02-24, vilket räcker fram till
   Debian 13.
6. **Laravel 13** efter #17.

Varje steg är en egen branch och deploy; `composer install` på prod löser mot
8.2, så uppdatera i importer-containern som `CLAUDE.md` beskriver. Rollback =
revert och deploy.

CodeIgniter 2: angreppsytan granskades i #08 (inga inloggningar, sessioner
eller uppladdningar; SQL-input via `is_numeric`/`intval`/`escape`). Kvar som
alternativ 1 nedan.

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
  - Andra code review samma dag: lookbehinds även för tredje gruppen
    (`12 485 725`), smalare spärr efter bindestreck så att `101-102-103`
    länkar alla tre, och atomiskt intervall så att `100-200 kr` inte länkar 100. Samma resultat som förut på alla 16 971 rader i 421 prod-sidor.
    Kvar medvetet: `126 450` länkar båda (går inte att skilja från listan
    `101 104`), lookarounds ser inte över spangränser i HTML:en.
  - Tredje code review: lapparna öppnade nya hål (telefonnummer, årtal,
    `100-109f`). **Omskrivet:** `linkifySingleLine()` delar nu upp texten
    mellan taggarna i talsekvenser och bedömer varje sekvens som helhet;
    bara sekvenser av enbart sidnummer länkas, inte följda av en enhet
    (`kr`, `meter`, `personer` …) och inte placeringar (`132. Namn`).
    Testas av `tests/Unit/LinkifyTest.php`: en specifikation med exakta
    länkar per rad, och en korpus med 756 rader från 421 prod-sidor
    (`tests/TestPages/linkify-corpus.json`) som larmar på varje ändrad rad.
    Mot korpusen ändrades 80 rader, alla genomgångna: golfpengar, F1-tider,
    matchresultat, belopp, mått och placeringar slutade länkas, och `607f`
    länkas nu även när raden har ett annat sidnummer. Förlorad: `24.199` på
    701 (går inte att skilja från ett decimaltal). Övriga 676 rader ger
    byte-identisk HTML.
  - Fjärde code review (före push): `%` och versala enheter (`527 KR`)
    fångades inte, fler substantiv som enheter (`människor`, `fall` …),
    kommalistor (`101,102`) länkas, och mellanslag binder bara ihop riktiga
    tusental och telefonnummer, så `19.30 101` och `covid-19 101` länkar 101.
    Korpusen oförändrad. Känd lucka: `962 nya fall` länkar 962.
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

## Granskning 2026-10-28 (subagent, Laravel-perspektiv)

Ändrar den stegvisa planen ovan. Verifierat mot kod och provlösningar i importer-containern om inget annat sägs.

**Hög**

1. **L9–L11 går inte att installera stabilt.** Composer blockerar alla stabila 9.x/10.x/11.x på grund av säkerhetsmeddelanden (bl.a. CVE-2026-48019, `>=9.0.0,<12.0.0`, ingen rättad version). Med `"minimum-stability": "dev"` (`importer/composer.json:60`) väljer Composer då tyst `9.x-dev`. L12 löser rent (v12.70.2, Carbon 3, Flysystem 3, PHPUnit 11.5, audit utan anmärkning). Inget av meddelandena träffar importern. → Sätt `minimum-stability: stable`. Ta L9–L11 som commits på en branch och deploya bara L12.
2. **Flysystem 3 kastar inte vid skrivfel.** `TeletextCharsExtractor.php:1066` kör `put()` utan att kolla returvärdet. I L8 blir ett skrivfel ett undantag (loggas som `NOT_IMPORTED_EXCEPTION`). I L9+ blir det tyst `false`, och sidan sparas med chars-URL:er som ger 404 (jfr #33). → `'throw' => true` på `local`-disken, plus ett test att varje refererad chars-fil finns på disk.
3. **Deployen är inte atomär.** `scp` skriver över koden före `composer install`, medan cron kör `schedule:run` varje minut, och rollback kräver en ny Actions-körning. → I deployen: `artisan down` före `scp`, vänta tills inga `texttv:import` kör, spara `vendor.prev` och den gamla lockfilen, `rm -f bootstrap/cache/*.php`, sedan `install`, `optimize`, `up`. Kör `composer check-platform-reqs --lock` på prod först. (Antagande: att nginx serverar `public/storage/chars` statiskt även i down-läge.)

**Medel**

4. `scp-action` raderar inget på servern; borttagna filer blir kvar. Överväg `rsync --delete` (exkludera `storage/`, `vendor/`, `.env`).
5. Att diffa `schedule:list` duger inte (formatet ändras mellan versioner). → Ett PHPUnit-test med ögonblicksbild i JSON av `Schedule::events()` (uttryck, kommando, overlapping, filter), skrivet på L8 först.
6. En import på kommandoraden testar inte schemaläggaren. → `make scheduler` 10–15 min per steg, kontrollera `page_imports_logs` och loggen.
7. Docker: efter varje major `make build` och `docker compose up -d -V scheduler lokalimport`, annars testas gamla vendor. PHPStan-baselinen ändras.
8. Flytta **inte** schemat till `routes/console.php` i L11: closurerna använder `$this->importRange()`. Behåll L10-strukturen i L11/L12, och höj `php` till `^8.2`.
9. PHPUnit 10/11: `LinkifyTest::specProvider()` måste bli `static`, `/** @test */` bli attribut, och `phpunit.xml` migreras. `collision` ^6/^7/^8 och `spatie/laravel-ignition` ^2 från L10.

**Låg / kan göras nu på L8**

10. Byt `fideloper/proxy` mot inbyggda `Illuminate\Http\Middleware\TrustProxies`. Ta bort `fruitcake/laravel-cors` och `config/cors.php` (`routes/api.php` har inga routes). Ta bort `laravel/sail`.
11. `symfony/dom-crawler` och `css-selector` ^5.4 blockerar inget; vidga till ^7.0 vid L12. Inga Carbon 3-fällor (`diffIn*` används inte).
12. Behåll `config/cache.php`, så att mutex-prefixet inte ändras.
13. Avsnitten "Rekommendation" (gå direkt till L13) och "Nästa steg (när #17 är på gång)" är inaktuella.

**Ny ordning:** (1) På L8, deploybart nu: punkt 10, härdad deploy (3–4), `minimum-stability: stable`, tester för schema, chars-filer och debug-rutterna. (2) En branch med L9 → L10 → L11 → L12, med full testkörning per commit. (3) En deploy av L12, följd av `/status/health`, `/prod-health importer` och `laravel.log`. Laravel Shift behövs inte.

### Läge 2026-10-28 kväll

**Klart:** testerna från punkt 2, 5 och förberedelsepunkten (`5669ecb`): `ScheduleTest` (schemat som JSON, inkl. underhållsläge och låsnycklar för `call()`), `CharsStorageTest` (grafikfilerna finns, skrivfel kastar från Flysystem) och `Feature/DebugRoutesTest` (`/live`, `/db`, `/importstatus`). Se CLAUDE.md. Fynd på vägen: i Laravel 8 läser `between()` klockan när schemat byggs, inte när filtret körs.

**Kvar av förberedelserna på L8:**

- [ ] `fideloper/proxy` → inbyggda `TrustProxies`; ta bort `fruitcake/laravel-cors`, `config/cors.php`, `laravel/sail`.
- [ ] `"minimum-stability": "stable"`.
- [ ] Härdad deploy (punkt 3–4). Ändrar `deploy-importer.yml`, Pär godkänner separat.
- [ ] `/pagecolors/{n}` ger 500 redan på L8 (`PageColors.php:19` läser `charAsImgTag`, som `TeletextCharsExtractor` inte längre sätter). Laga eller ta bort rutten; Pär bestämmer.

## Granskning 2026-10-28 kväll: paketen och förberedelserna på L8 (subagent)

Provkört i en worktree på `5effc0a`: `minimum-stability: stable`, `composer remove` av fideloper/fruitcake/sail, `Illuminate\Http\Middleware\TrustProxies`, `HandleCors` och `config/cors.php` borta. PHPUnit 104 tester / 370 assertions som i dag, `artisan optimize` (även `route:cache` med closures) OK.

**Paketen**

| Paket                                                               | Används                                                                                 | Rekommendation                                               |
| ------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| `laravel/framework` 8.83.27                                         | ja                                                                                      | `^12` vid L12 (8.83.28/29 blockeras av säkerhetsmeddelanden) |
| `guzzlehttp/guzzle` 7.5.0                                           | ja, `Http::timeout()` i `Importer.php`                                                  | behåll; 9 CVE:er, 7.16.1 går på L8; `^7.8` vid L12           |
| `symfony/dom-crawler`, `css-selector` ^5.4                          | ja (`filter()` kräver css-selector)                                                     | behåll, `^7.0` vid L12                                       |
| `laravel/tinker`                                                    | manuellt                                                                                | behåll i `require`, `^2.10` vid L12                          |
| `fideloper/proxy`                                                   | no-op (`$proxies = null`)                                                               | **ta bort nu**, inbyggda `TrustProxies` finns i 8.83         |
| `fruitcake/laravel-cors`                                            | no-op (`api/*`, tom `routes/api.php`)                                                   | **ta bort nu** med `config/cors.php` och Kernel-raden        |
| `laravel/sail` (dev)                                                | nej                                                                                     | **ta bort nu**                                               |
| `spatie/laravel-ignition` (dev)                                     | registrerar `_ignition/*` på prod (deployen installerar dev; 404 med `APP_DEBUG=false`) | försvinner med `--no-dev`; tas bort vid L12                  |
| `nunomaduro/collision`, `phpunit/phpunit` (dev)                     | ja                                                                                      | behåll; `^8.6` / `^11.5` vid L12                             |
| `mockery/mockery`, `fakerphp/faker`, `spatie/phpunit-watcher` (dev) | nej / skelettrest / `npm run watch-tests`                                               | kan tas bort                                                 |

Också oanvänt: `package.json`, `webpack.mix.js`, `resources/js`, `resources/css` (inga `mix()`), `server_deploy.sh` (död, php7.4). Skelettrester (`welcome`, `User`, `routes/api.php` m.fl.) är ofarliga.

**Stegen**

1. `minimum-stability: stable`: ofarligt, låsfilen har inga dev-versioner. Med `stable` misslyckas en full `composer update` på L8 (alla 8.x blockerade); uppdatera riktat. `install` från låsfilen påverkas inte.
2. fideloper/fruitcake/sail bort: verifierat, säkert även med dagens deploy.
3. **Härdad deploy + #33:**
   - `down` i ett eget ssh-steg **före** kopieringen.
   - Ett fel under `set -e` lämnar appen i `down` (importen står still). `cp -a importer importer.prev` (utan `storage`) före rsync och en `trap` som återställer koden och vendor och kör `up`; `up` i `if: always()` räcker inte.
   - `rsync --delete` måste undanta `public/storage` (symlänk; annars 404 på all grafik), `vendor/`, `storage/`, `.env`, `bootstrap/cache/`, `.phpunit.result.cache`, `node_modules`.
   - Vänta på `pgrep -f 'artisan (texttv:|schedule:|import-status)'` med tak (5 min, sedan avbryt och `up`).
   - `concurrency: deploy-importer`; pinna `appleboy/scp-action` och `ssh-action` (nu `@master`, har prod-nyckeln för root) till SHA; ta bort `setup-php 7.4`.
   - `check-platform-reqs --lock --no-dev` först, `rm -f bootstrap/cache/*.php` före `install`, `--no-dev`.
   - `umask 002` före `optimize`, #33:s `root:www-data` 2775/664 efter, `'permission' => 0664` på loggkanalen; ersätter `chown -R root:root .` och `chmod 777`.
   - Rök-test före `up`: `php artisan --version`, `schedule:list`. Testa med `workflow_dispatch` på oförändrad kod (workflow-ändringen triggar inte path-filtret).
4. `/pagecolors`: ta bort rutten, `PageColors.php`, `use`-raden och den döda `getImageDebugHtml()`. Att laga kräver bild-HTML per tecken i importen igen.
5. Saknades:
   - Säkerhetsuppdateringar inom L8: 47 meddelanden i 12 paket. `composer update "guzzlehttp/*" "symfony/*" league/commonmark psy/psysh phpunit/phpunit nesbot/carbon monolog/monolog -W` ger 55 uppdateringar, PHPUnit grönt; psr/log 2 och psr/http-message 2 följer med. Eget deploy, valfritt.
   - `'throw' => true` på `local`/`public` redan nu (ignoreras av Flysystem 1).
   - PHPUnit 10/11-förberedelser som fungerar på 9: `static` dataproviders, `assertObjectHasAttribute` bort, `@test` → `test_`-prefix.
   - `/live/{n}` hämtar från SVT per anrop och delar fpm-pool med webben; överväg throttle (utanför uppgraderingen).

**Ordning:** (A) deploy av bara workflowen: härdning + #33 + `--no-dev` + `concurrency` + pinnade actions, testad med `workflow_dispatch`. (B) en koddeploy: `minimum-stability`, paketen bort, `/pagecolors` bort, loggrättigheter och `throw`, PHPUnit-förberedelser, npm-filer och `server_deploy.sh` bort (kan gå före A). (C, valfri) säkerhetsuppdateringar inom L8 med `make scheduler` 10–15 min. (D) branch L9 → L12, en deploy, `php` `^8.2`.

### Deploy B klar 2026-10-28 (`e7ee028`, deploy 22:07)

`minimum-stability: stable`; fideloper/proxy, fruitcake/laravel-cors (+ asm89/stack-cors) och laravel/sail borta (riktad `composer update` av just dem: låsfilen tar bort fyra paket och ändrar inget annat; `composer remove` drog först med psr/log 2 och 14 Symfony-uppdateringar och gjordes om). `/pagecolors` och `getImageDebugHtml()` borta, `throw` på diskarna, `permission 0664` på loggen, PHPUnit-förberedelserna, npm-filerna och `server_deploy.sh` borta. Lokalt: PHPUnit 104/370, PHPStan OK, import OK.

Prod: `vendor/` utan de fyra paketen, `/importstatus` 200, `/pagecolors` 404, importen loggar som vanligt. **Ett fel i deployfönstret** 22:07:01: `include(…/fideloper/proxy/…TrustedProxyServiceProvider.php)`, när `composer install` hade tagit bort paketet men den gamla `bootstrap/cache/packages.php` ännu inte skrivits om. Precis det glapp deploy A (`rm -f bootstrap/cache/*.php`, `down`) stänger. `scp` raderar inte borttagna filer, så `PageColors.php`, `config/cors.php`, `package.json` m.fl. ligger kvar på servern (ofarligt; rutten och klasserna används inte) tills deploy A med `rsync --delete`.

/code-review av `e7ee028`: inga buggar. Rester rättade i följdcommiten (länken till `/pagecolors` på importerns startsida, död `gdImgToHTML()`, `DebugRoutesTest`-kommentaren, `readme-tech.md`, `.styleci.yml`). **Till L12-branchen:** `@dataProvider` i `CleanupTest` och `LinkifyTest` → attributet `#[DataProvider(...)]` (kräver PHPUnit 10; PHPUnit 11 varnar för docblock-metadata, 12 tar bort den). Loggrättigheten 0664 räcker först med `root:www-data` + setgid från deploy A.

### Deploy A klar 2026-10-28 (`59482b6`, testdeploy med `workflow_dispatch` 22:23)

Alla steg gröna. Importen pausade 22:23:12–22:25:02 (ett schemavarv hoppades över). Efteråt: inget `storage/framework/down`; de tio borttagna filerna (PageColors.php, cors.php, package.json, webpack.mix.js, server_deploy.sh, resources/js, resources/css) borta från servern; `public/storage` kvar och grafiken 200; `vendor/` utan dev-paket (Ignition, PHPUnit, phpunit-watcher); `storage/`, `storage/logs`, `chars/`, `bootstrap/cache` 2775 `root:www-data`, filer 664; koden 755 `root:root`. `/importstatus`, importen (81 loggrader de första minuterna) och en ny delningsbild på texttv.nu fungerar, inga nya fel i `laravel.log`. `importer.prev/` och `importer.incoming/` ligger bredvid importern (skrivs om vid varje deploy). #33 klar med detta.
