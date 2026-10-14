**Status:** aktiv
**Senast uppdaterad:** 2026-10-14

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
  risk utan vinst. Gör det som en flytt till en ny Debian 13-server under 2027,
  tillsammans med Laravel 13.
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
  - `spatie/laravel-short-schedule`: Laravel har inbyggd schemaläggning under
    minuten (`everyTenSeconds()` m.fl.) sedan 10.x, så paketet kan troligen
    tas bort.
  - `dgoring/dom-query`: okänt underhåll, kontrollera först. Det är den enda
    egentliga risken.
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
   13 controllers (~1 800 rader), en modell (~1 050), en helper (~750), en
   library (~290) och 30 vyer (~3 400). 33 routes som måste behålla exakt
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

## Nästa steg (när det blir aktuellt)

1. Kontrollera underhållsläget för `dgoring/dom-query`.
2. Planera OS-uppgraderingen av prod till Debian 13 (egen todo; påverkar
   nginx, MariaDB och php-fpm-konfigen i `server.md`).
3. Laravel 8 → 13 på en branch, verifiera med PHPUnit och en import i Docker
   på PHP 8.4.

## Källor

- Laravels supporttabell: https://laravel.com/docs/12.x/releases
- CodeIgniter-versioner och supportläge: https://codeigniter.com/download
- Uppgradering CI 2.2 → 3.0:
  https://codeigniter.com/userguide3/installation/upgrade_300.html
- Community-fork för CI 3 på PHP 8.2+: https://github.com/pocketarc/codeigniter
