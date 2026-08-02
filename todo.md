# Claude TODO – TextTV.nu

Index över förbättringsarbete. Varje todo har en egen fil under
[`todos/`](todos/) med fullständig analys. Konvention och
mappstruktur: [`todos/README.md`](todos/README.md).

Senast uppdaterad: 2026-08-02 (+#09 /377-diagnos — titelhypotesen falsifierad, extern orsak fastställd; #01 stängd efter 60d-mätning — vinsten håller).

## Aktiva

| #   | Titel | Status | Fil |
| --- | ----- | ------ | --- |
| 04  | Perf/SEO-fixar från Lighthouse-baseline 2026-05-19 | G/E/H + **D Fas 1+2** klara (Fas 1 deployad+live-verifierad 2026-06-22, 20 sidor), A delvis, F släppt, I/B/C/J deprio:ade. Kvar: 60d-effektmätning (m. #01 2026-07-18) | [todos/04-perf-seo-fixar-fran-baseline-2026-05-19.md](todos/04-perf-seo-fixar-fran-baseline-2026-05-19.md) |
| 06  | Byt facade/ignition mot spatie/laravel-ignition | ny — workaround i `AppServiceProvider` (commit 6ba0656) maskerar problemet, vill byta paket istället | [todos/06-byt-facade-ignition-mot-spatie-laravel-ignition.md](todos/06-byt-facade-ignition-mot-spatie-laravel-ignition.md) |
| 08  | Säkerhetsgranskning 2026-08-01 | **12 av 19 fynd stängda 2026-08-01** och deployade (K1–K4, M3, L1, L3–L6, L8, L9). Kvar: M1, M2, L7 (rate limiting), M4 (EOL-ramverk), M5, M6 (serversidiga: dev-ytor + CSP/headers), L2 (escaping i importern) | [todos/08-sakerhetsgranskning-2026-08-01.md](todos/08-sakerhetsgranskning-2026-08-01.md) |
| 10  | Retention raderar arkivsidor som rankar | ny — `is_shared` är en delningsräknare, så `CleanupOldPages` sparar bara sidor någon råkat dela. Arkivet är enda stället sajten får ämnesfrågor (`resultatbörsen` 26 klick går helt till 2017-arkivsidor, levande /330 får 0). Kvar: mät hur stor andel av arkivtrafiken som är odelad | [todos/10-retention-raderar-arkivsidor-som-rankar.md](todos/10-retention-raderar-arkivsidor-som-rankar.md) |
| 09  | Varför tappade /377 position — och vad vi gör åt det | **Diagnos klar 2026-08-02: −7 615 klick/kvartal, extern orsak (SVT tar 6/10 platser via boilerplate). Titelhypotesen falsifierad — Google skriver om både title och description.** Kvar: A (skriv om `texttv_page_text` för 377), B (snippet-läckage), C (syskonsidor 378/379), D (30d/60d-mätning) | [todos/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md](todos/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md) |

### Beroenden

_(inga ännu)_

### Föreslagen ordning

_(inga ännu)_

## Uppföljningar — datum att komma ihåg

Datum-bundna manuella åtgärder som inte går att autoschemalägga (kräver lokala
MCP:s som `mcp-gsc`, SSH-nycklar till prod, eller mänsklig bedömning).
Granska veckovis. När en åtgärd är gjord, flytta raden till "Avklarade" nedan
eller markera todon som klar.

| 2026-09-01 | #09 steg A+C — 30d-mätning. **C:** H1:orna på 330/376/378/379 (commit `d7b585b`). **A:** ny `page_text` för 377 (prod-DB 2026-08-02). Baselines i todon. Mät **klick och position**, inte CTR. Kolla särskilt att `resultat lången 377` inte tappat. | [#09](todos/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md) |

### Avklarade uppföljningar

| Planerat | Utfört | Åtgärd | Todo |
| -------- | ------ | ------ | ---- |
| 2026-07-18 | 2026-08-02 | #01 GSC-mätning 60d (fönster 06-19→07-18). **Vinsten håller:** kohort-CTR stilla på **0.73%**, klick/dag **8.4 → 17.8 → 19.3** (+129% mot baseline), 10 av 12 sidor över baseline. /343 0.69% (pos 4.7→3.3), /101 1.26%, /104 3.26%. Restpopulationen behöver ingen egen fix-todo → **#01 stängd**. Undantag: **/344 regresserade under baseline** (0.13→0.07%, pos 4.7→6.2) — egen titt. /336 föll men jun–jul är PL-uppehåll. | [#01](todos/done/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 2026-06-18 | 2026-06-22 | #01 GSC-mätning 30d post-deploy. Kohort (12 sidor) CTR **0.31% → 0.73%** (~2.3×), clicks **236 → 534** vid ~oförändrade impressions. /343 0.26→0.63% (pos 4.7→3.6), /345 0.11→0.70%, /336 0.10→0.42%. Strax under sajt-snitt 0.88% (crawl-lag). **Vinst bekräftad.** | [#01](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 2026-06-18 | 2026-06-22 | #04 D Fas 1 gate-check: #01:s mätperiod stängd, vinst bekräftad → Fas 1 avblockad. `mcp-gsc` topp-sidor efter impressions hämtade; kandidatlista för 30–50 nya whitelist-entries klar. Implementation pendar användarbeslut. | [#04](todos/04-perf-seo-fixar-fran-baseline-2026-05-19.md) |
| 2026-05-26 | 2026-05-27 | #03 OPTIMIZE TABLE texttv_stats.page_actions efter cleanup-städning. Backlog: 158M → 3.6M rader (10d retention). Frigjorde **~13 GB** disk (data_free 13 252 MB → 4 MB; total 13.4 GB → 317 MB). | [#03](todos/done/03-fix-cleanup-page-actions-db-auth.md) |

## Klara

| #   | Titel | Datum | Fil |
| --- | ----- | ----- | --- |
| 01  | Varför har /343 och ev andra sidor så dålig CTR? | 2026-08-02 | [todos/done/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md](todos/done/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 07  | Docker Compose för lokal utveckling | 2026-06-24 | [todos/done/07-docker-compose-lokal-utveckling.md](todos/done/07-docker-compose-lokal-utveckling.md) |
| 05  | Utvärdera externa SEO-skills (addyosmani + coreyhaines31) | 2026-05-19 | [todos/done/05-utvardera-seo-skills.md](todos/done/05-utvardera-seo-skills.md) |
| 03  | Fixa `texttv:cleanup-page-actions` DB-auth | 2026-05-19 | [todos/done/03-fix-cleanup-page-actions-db-auth.md](todos/done/03-fix-cleanup-page-actions-db-auth.md) |
| 02  | Logrotate för laravel.log (8 GB) + diagnostik | 2026-05-19 | [todos/done/02-logrotate-laravel-log-8gb.md](todos/done/02-logrotate-laravel-log-8gb.md) |

## Avfärdade / sammanslagna

| #   | Titel | Skäl | Fil |
| --- | ----- | ---- | --- |
