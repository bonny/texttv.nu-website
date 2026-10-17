# Claude TODO – TextTV.nu

Index över förbättringsarbete. Varje todo har en egen fil under
[`todos/`](todos/) med fullständig analys. Konvention och
mappstruktur: [`todos/README.md`](todos/README.md).

Senast uppdaterad: 2026-10-18 (#18 klar.)

## Aktiva

| #   | Titel | Status | Fil |
| --- | ----- | ------ | --- |
| 12  | php-fpm-poolen slår i taket i normaldrift | **`most_read`-filcachen mätt 2026-10-16:** slowloggen 400–1 100 → 184 per dygn (15 okt), varningarna 5. **`pm.max_children` höjd 5 → 10 2026-10-16 20:15** (backup i `/root/config-backups-2026-10-16/`). Väntar på mätning till 2026-10-23, sedan stäng. Kvar: omräkningen av `most_read` tar fortfarande över 3 s på kvällar (`page_actions`-storlek/queryplan) | [todos/12-php-fpm-poolen-slar-i-taket.md](todos/12-php-fpm-poolen-slar-i-taket.md) |
| 16  | Gör arkivet till en tillgång: idéer för användare, Google och AI | ny 2026-10-14, idékatalog. **Deployat 2026-10-14:** canonical på arkivsidor och startsidan, dagsarkiv `/377/igar` med datumsidor (330 borttagen, den är en meny), gårdagens nyheter/sportnyheter `/nyheter/igar` och `/sport/igar`, ny 404-sida i Text-TV-stil. **2026-10-16:** gårdagens tre mest lästa nyheter i startsidans "Igår på Text TV". Mät 2026-11-14. Nästa: retention (#10). Ämnessidor nedprioriterade (nästan ingen ämnestrafik idag) | [todos/16-gor-arkivet-till-en-tillgang.md](todos/16-gor-arkivet-till-en-tillgang.md) |
| 15  | Uppgradera EOL-ramverken (CodeIgniter 2, Laravel 8) | **Blockerad av: #17.** Granskad 2026-10-17; småsakerna som gick att göra nu är klara och deployade (oanvända `laravel-short-schedule`, döda `test`/`welcome`-controllers, importerns tester gröna och en länkbugg fixad). Research klar 2026-10-14: **båda apparna fungerar på PHP 8.4** (provat i Docker, prod kör 8.2), så OS/PHP-uppgraderingen blockeras inte. Förslag: Laravel 8 → 13 i samband med Debian 13, CI 2 står kvar tills behov | [todos/15-uppgradera-eol-ramverken.md](todos/15-uppgradera-eol-ramverken.md) |
| 17  | Uppgradera prod till Debian 13 (trixie) och PHP 8.4 | ny 2026-10-17, utbruten ur #15. Ingen brådska: Debian 12 LTS ger PHP 8.2-fixar till 2028-06-30. Förslag: uppgradera på plats under 2027 med Hetzner-snapshot som rollback och provkörning på en klon först, Laravel 13 i samma veva | [todos/17-uppgradera-prod-till-debian-13.md](todos/17-uppgradera-prod-till-debian-13.md) |
| 10  | Retention raderar arkivsidor som rankar | **pausad — mätt 2026-08-02: 76 % av arkivsidorna med trafik raderas, men de är färska nyhetssidor vars efterfrågan ändå dör. Ingen åtgärd.** Fyndet som blev kvar: arkivsnapshots från 2016–17 rankar över de levande sidorna på ämnesfrågor (`resultatbörsen`: arkiv 25 klick, live /330 noll) | [todos/10-retention-raderar-arkivsidor-som-rankar.md](todos/10-retention-raderar-arkivsidor-som-rankar.md) |

### Beroenden

- #15 (Laravel 13) blockeras av #17 (Debian 13, PHP 8.3+ krävs).

### Föreslagen ordning

_(inga ännu)_

## Uppföljningar — datum att komma ihåg

Datum-bundna manuella åtgärder som inte går att autoschemalägga (kräver lokala
MCP:s som `mcp-gsc`, SSH-nycklar till prod, eller mänsklig bedömning).
Granska veckovis. När en åtgärd är gjord, flytta raden till "Avklarade" nedan
eller markera todon som klar.

| Datum | Åtgärd | Todo |
| ----- | ------ | ---- |
| 2026-10-25 | #18: AdSense en vecka efter designbytet (klart 2026-10-18). RPM per sidvisning (`DOMAIN_NAME==texttv.nu`) mot baslinjen 14,59 kr (2026-09-19–10-16, veckor 15,35 → 13,37, sjunkande redan före) och samma vecka 2025. | [#18](todos/done/18-text-tv-designsystem.md) |
| 2026-10-23 | #12: en vecka efter höjningen till `max_children = 10` (2026-10-16 20:15). Räkna `max_children`-varningar per dygn (ska vara nära 0), slowlog per dygn, p99 kl 21–22 och load på kvällen. Stiger load märkbart: överväg att gå tillbaka. Annars stäng #12. | [#12](todos/12-php-fpm-poolen-slar-i-taket.md) |
| 2026-10-24 | #16: en vecka efter "Igår på Text TV"-rubrikerna på startsidan (2026-10-16). Räkna klick per dygn från startsidan (referer `https://texttv.nu/`) till arkivsidor och `/nyheter/igar` i `access.log`, mot 3 per dygn före. | [#16](todos/16-gor-arkivet-till-en-tillgang.md) |
| 2026-11-14 | #16: kolla i GSC att arkiv-URL:er med `utm_` försvinner ur indexet efter canonical-deployen, och hur `/377/igar`, `/330/igar` och datumsidorna indexeras och får klick (deployade 2026-10-14). | [#16](todos/16-gor-arkivet-till-en-tillgang.md) |
| 2026-11-15 | #18: GSC fyra veckor efter designbytet. Klick och CTR för `/377` och startsidan YoY med `/377` som kontroll, och CLS i Core Web Vitals. | [#18](todos/done/18-text-tv-designsystem.md) |
| 2026-11-16 | MariaDB uppgraderas för hand sedan 2026-10-16 (blacklistad i `/etc/apt/apt.conf.d/51unattended-upgrades-local` efter att auto-uppgraderingen 10.11.18 → .19 hängde och gav 502 06:54–08:36). Månadsvis: `apt list --upgradable 2>/dev/null \| grep -i mariadb`, finns något → uppgradera i lågtrafik (kräver ok), verifiera `dpkg --audit` tomt och att appens `api/get?cb=` svarar 200. Lägg sedan in nästa månad här. | – |

### Avklarade uppföljningar

| Planerat | Utfört | Åtgärd | Todo |
| -------- | ------ | ------ | ---- |
| 2026-10-16 | 2026-10-16 | #12 mätning efter `most_read`-filcachen. Slowloggen 400–1 100 → 184 per dygn (15 okt), varningar 5 (16 okt: 2 utanför MariaDB-incidenten), p99 0,02 s. `max_children` höjd 5 → 10 samma kväll. | [#12](todos/12-php-fpm-poolen-slar-i-taket.md) |
| 2026-09-01 | 2026-10-14 | #11 30d-mätning (gjord som 70d + YoY). **Äkta vinst:** `resultatbörsen` 35 → 105 klick, levande /330 0 → 46 (pos 7.8 → 4.7), arkivsidorna tappade inte. | [#11](todos/done/11-arkivsidor-rankar-over-de-levande-sidorna.md) |
| 2026-09-01 | 2026-10-14 | #09 A+C 30d-mätning (gjord som 70d/28d + YoY, eftersom juli-baslinjen störs av VM-uppehållet). **C:** ingen effekt, 390 → 251 klick YoY. **A:** `resultat lången 377` 250 → 19 klick YoY vid pos 2.1, `text tv 377 idag` 279 → 103. | [#09](todos/done/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md) |
| 2026-07-18 | 2026-08-02 | #01 GSC-mätning 60d (fönster 06-19→07-18). **Vinsten håller:** kohort-CTR stilla på **0.73%**, klick/dag **8.4 → 17.8 → 19.3** (+129% mot baseline), 10 av 12 sidor över baseline. /343 0.69% (pos 4.7→3.3), /101 1.26%, /104 3.26%. Restpopulationen behöver ingen egen fix-todo → **#01 stängd**. Undantag: **/344 regresserade under baseline** (0.13→0.07%, pos 4.7→6.2) — egen titt. /336 föll men jun–jul är PL-uppehåll. | [#01](todos/done/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 2026-06-18 | 2026-06-22 | #01 GSC-mätning 30d post-deploy. Kohort (12 sidor) CTR **0.31% → 0.73%** (~2.3×), clicks **236 → 534** vid ~oförändrade impressions. /343 0.26→0.63% (pos 4.7→3.6), /345 0.11→0.70%, /336 0.10→0.42%. Strax under sajt-snitt 0.88% (crawl-lag). **Vinst bekräftad.** | [#01](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 2026-06-18 | 2026-06-22 | #04 D Fas 1 gate-check: #01:s mätperiod stängd, vinst bekräftad → Fas 1 avblockad. `mcp-gsc` topp-sidor efter impressions hämtade; kandidatlista för 30–50 nya whitelist-entries klar. Implementation pendar användarbeslut. | [#04](todos/done/04-perf-seo-fixar-fran-baseline-2026-05-19.md) |
| 2026-05-26 | 2026-05-27 | #03 OPTIMIZE TABLE texttv_stats.page_actions efter cleanup-städning. Backlog: 158M → 3.6M rader (10d retention). Frigjorde **~13 GB** disk (data_free 13 252 MB → 4 MB; total 13.4 GB → 317 MB). | [#03](todos/done/03-fix-cleanup-page-actions-db-auth.md) |

## Klara

| #   | Titel | Datum | Fil |
| --- | ----- | ----- | --- |
| 18  | Text-TV-designsystem: gör om det som inte känns Text TV | 2026-10-18 | [todos/done/18-text-tv-designsystem.md](todos/done/18-text-tv-designsystem.md) |
| 09  | Varför tappade /377 position — och vad vi gör åt det | 2026-10-14 | [todos/done/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md](todos/done/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md) |
| 08  | Säkerhetsgranskning 2026-08-01 | 2026-10-14 | [todos/done/08-sakerhetsgranskning-2026-08-01.md](todos/done/08-sakerhetsgranskning-2026-08-01.md) |
| 06  | Byt facade/ignition mot spatie/laravel-ignition | 2026-10-14 | [todos/done/06-byt-facade-ignition-mot-spatie-laravel-ignition.md](todos/done/06-byt-facade-ignition-mot-spatie-laravel-ignition.md) |
| 13  | `access.log` dubbelloggar — ip-anonymiseringen är verkningslös | 2026-10-14 | [todos/done/13-access-log-dubbelloggar-ip-anonymisering-verkningslos.md](todos/done/13-access-log-dubbelloggar-ip-anonymisering-verkningslos.md) |
| 11  | Arkivsidor rankar över de levande sidorna | 2026-10-14 | [todos/done/11-arkivsidor-rankar-over-de-levande-sidorna.md](todos/done/11-arkivsidor-rankar-over-de-levande-sidorna.md) |
| 04  | Perf/SEO-fixar från Lighthouse-baseline 2026-05-19 | 2026-10-14 | [todos/done/04-perf-seo-fixar-fran-baseline-2026-05-19.md](todos/done/04-perf-seo-fixar-fran-baseline-2026-05-19.md) |
| 14  | `most_read`-queryn är orsaken till poolmättnaden | 2026-08-14 | [todos/done/14-most-read-queryn-ar-orsaken-till-poolmattnaden.md](todos/done/14-most-read-queryn-ar-orsaken-till-poolmattnaden.md) |
| 01  | Varför har /343 och ev andra sidor så dålig CTR? | 2026-08-02 | [todos/done/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md](todos/done/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 07  | Docker Compose för lokal utveckling | 2026-06-24 | [todos/done/07-docker-compose-lokal-utveckling.md](todos/done/07-docker-compose-lokal-utveckling.md) |
| 05  | Utvärdera externa SEO-skills (addyosmani + coreyhaines31) | 2026-05-19 | [todos/done/05-utvardera-seo-skills.md](todos/done/05-utvardera-seo-skills.md) |
| 03  | Fixa `texttv:cleanup-page-actions` DB-auth | 2026-05-19 | [todos/done/03-fix-cleanup-page-actions-db-auth.md](todos/done/03-fix-cleanup-page-actions-db-auth.md) |
| 02  | Logrotate för laravel.log (8 GB) + diagnostik | 2026-05-19 | [todos/done/02-logrotate-laravel-log-8gb.md](todos/done/02-logrotate-laravel-log-8gb.md) |

## Avfärdade / sammanslagna

| #   | Titel | Skäl | Fil |
| --- | ----- | ---- | --- |
