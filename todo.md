# Claude TODO – TextTV.nu

Index över förbättringsarbete. Varje todo har en egen fil under
[`todos/`](todos/) med fullständig analys. Konvention och
mappstruktur: [`todos/README.md`](todos/README.md).

Senast uppdaterad: 2026-06-22 (#01 30d-mätning klar — kohort-CTR 0.31%→0.73%, vinst bekräftad; #04 D Fas 1 avblockad).

## Aktiva

| #   | Titel | Status | Fil |
| --- | ----- | ------ | --- |
| 01  | Varför har /343 och ev andra sidor så dålig CTR? | **30d-mätning klar 2026-06-22: kohort-CTR 0.31%→0.73% (~2.3×), clicks 236→534 — vinst bekräftad.** Kvar: 60d-slutmätning 2026-07-18 | [todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 04  | Perf/SEO-fixar från Lighthouse-baseline 2026-05-19 | G/E/H + **D Fas 1+2** klara (Fas 1 deployad+live-verifierad 2026-06-22, 20 sidor), A delvis, F släppt, I/B/C/J deprio:ade. Kvar: 60d-effektmätning (m. #01 2026-07-18) | [todos/04-perf-seo-fixar-fran-baseline-2026-05-19.md](todos/04-perf-seo-fixar-fran-baseline-2026-05-19.md) |
| 06  | Byt facade/ignition mot spatie/laravel-ignition | ny — workaround i `AppServiceProvider` (commit 6ba0656) maskerar problemet, vill byta paket istället | [todos/06-byt-facade-ignition-mot-spatie-laravel-ignition.md](todos/06-byt-facade-ignition-mot-spatie-laravel-ignition.md) |
| 07  | Docker Compose för lokal utveckling | aktionerbar — image-val (`serversideup/php:8.2-fpm-nginx`), service-struktur och seed-strategi specificerade. Pattern kopierat från brottsplatskartan | [todos/07-docker-compose-lokal-utveckling.md](todos/07-docker-compose-lokal-utveckling.md) |

### Beroenden

_(inga ännu)_

### Föreslagen ordning

_(inga ännu)_

## Uppföljningar — datum att komma ihåg

Datum-bundna manuella åtgärder som inte går att autoschemalägga (kräver lokala
MCP:s som `mcp-gsc`, SSH-nycklar till prod, eller mänsklig bedömning).
Granska veckovis. När en åtgärd är gjord, flytta raden till "Avklarade" nedan
eller markera todon som klar.

| 2026-07-18 | #01 GSC-mätning — 60d: slutbeslut om restpopulation behöver egen fix-todo                                                              | [#01](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md)         |

### Avklarade uppföljningar

| Planerat | Utfört | Åtgärd | Todo |
| -------- | ------ | ------ | ---- |
| 2026-06-18 | 2026-06-22 | #01 GSC-mätning 30d post-deploy. Kohort (12 sidor) CTR **0.31% → 0.73%** (~2.3×), clicks **236 → 534** vid ~oförändrade impressions. /343 0.26→0.63% (pos 4.7→3.6), /345 0.11→0.70%, /336 0.10→0.42%. Strax under sajt-snitt 0.88% (crawl-lag). **Vinst bekräftad.** | [#01](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 2026-06-18 | 2026-06-22 | #04 D Fas 1 gate-check: #01:s mätperiod stängd, vinst bekräftad → Fas 1 avblockad. `mcp-gsc` topp-sidor efter impressions hämtade; kandidatlista för 30–50 nya whitelist-entries klar. Implementation pendar användarbeslut. | [#04](todos/04-perf-seo-fixar-fran-baseline-2026-05-19.md) |
| 2026-05-26 | 2026-05-27 | #03 OPTIMIZE TABLE texttv_stats.page_actions efter cleanup-städning. Backlog: 158M → 3.6M rader (10d retention). Frigjorde **~13 GB** disk (data_free 13 252 MB → 4 MB; total 13.4 GB → 317 MB). | [#03](todos/done/03-fix-cleanup-page-actions-db-auth.md) |

## Klara

| #   | Titel | Datum | Fil |
| --- | ----- | ----- | --- |
| 05  | Utvärdera externa SEO-skills (addyosmani + coreyhaines31) | 2026-05-19 | [todos/done/05-utvardera-seo-skills.md](todos/done/05-utvardera-seo-skills.md) |
| 03  | Fixa `texttv:cleanup-page-actions` DB-auth | 2026-05-19 | [todos/done/03-fix-cleanup-page-actions-db-auth.md](todos/done/03-fix-cleanup-page-actions-db-auth.md) |
| 02  | Logrotate för laravel.log (8 GB) + diagnostik | 2026-05-19 | [todos/done/02-logrotate-laravel-log-8gb.md](todos/done/02-logrotate-laravel-log-8gb.md) |

## Avfärdade / sammanslagna

| #   | Titel | Skäl | Fil |
| --- | ----- | ---- | --- |
