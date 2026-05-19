# Claude TODO – TextTV.nu

Index över förbättringsarbete. Varje todo har en egen fil under
[`todos/`](todos/) med fullständig analys. Konvention och
mappstruktur: [`todos/README.md`](todos/README.md).

Senast uppdaterad: 2026-05-19 (+#03 Fixa cleanup-page-actions DB-auth).

## Aktiva

| #   | Titel | Status | Fil |
| --- | ----- | ------ | --- |
| 01  | Varför har /343 och ev andra sidor så dålig CTR? | deployat 2026-05-19 (commit 5f9c6ad), 19/19 sidor live-verifierade — 30d-mätning till 2026-06-18 | [todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md) |
| 02  | Logrotate för laravel.log (8 GB) + diagnostik | fas 1+2 klara 2026-05-19 (LOG_LEVEL=warning, 99.9 % INFO-spam borta) — kvar: fas 3 logrotate + fas 4 truncate | [todos/02-logrotate-laravel-log-8gb.md](todos/02-logrotate-laravel-log-8gb.md) |
| 03  | Fixa `texttv:cleanup-page-actions` DB-auth | ny — `mysql_stats_db` connection nekas (Access denied for user 'root'); blockerar städning av `page_actions` | [todos/03-fix-cleanup-page-actions-db-auth.md](todos/03-fix-cleanup-page-actions-db-auth.md) |

### Beroenden

_(inga ännu)_

### Föreslagen ordning

_(inga ännu)_

## Uppföljningar — datum att komma ihåg

Datum-bundna manuella åtgärder som inte går att autoschemalägga (kräver lokala
MCP:s som `mcp-gsc`, SSH-nycklar till prod, eller mänsklig bedömning).
Granska veckovis. När en åtgärd är gjord, flytta raden till "Avklarade" nedan
eller markera todon som klar.

| Datum      | Åtgärd                                                                                                                                  | Todo                                                                       |
| ---------- | --------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| 2026-06-18 | #01 GSC-mätning — 30d post-deploy: kohort-CTR (343/336/345 etc.) från ~0.27 % → mål 0.88 %. Verifiera även live `<title>`/`<meta>` på 18 sidor | [#01](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md)         |
| 2026-07-18 | #01 GSC-mätning — 60d: slutbeslut om restpopulation behöver egen fix-todo                                                              | [#01](todos/01-varfor-har-343-och-ev-andra-sidor-sa-dalig-ctr.md)         |

### Avklarade uppföljningar

| Planerat | Utfört | Åtgärd | Todo |
| -------- | ------ | ------ | ---- |

## Klara

| #   | Titel | Datum | Fil |
| --- | ----- | ----- | --- |

## Avfärdade / sammanslagna

| #   | Titel | Skäl | Fil |
| --- | ----- | ---- | --- |
