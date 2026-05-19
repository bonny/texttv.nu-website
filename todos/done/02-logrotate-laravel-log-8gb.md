**Status:** klar 2026-05-19 — LOG_LEVEL=warning + config:cache (99.9 % INFO-spam borta), logrotate-config installerad i /etc/logrotate.d/texttv-importer (daily, rotate 7, copytruncate), engångs-truncate frigjorde ~8 GB
**Senast uppdaterad:** 2026-05-19

# Todo #02 — Logrotate för laravel.log (8 GB) + diagnostik

## Sammanfattning

Importerns `laravel.log` på prod är **8.0 GB** och växer kontinuerligt sedan 2025-06-05 (~11 månader). Ingen logrotate konfigurerad. Disk-utrymme är inte akut (35 GB ledigt av 75), men:

1. `tail`/`grep` är opraktiskt → svårt att debugga importerns problem
2. Disk-tillväxt: ~8 GB/år nu, accelererar troligen
3. Antyder antingen för verbose log-level eller att schedulern spammar errors konstant

Path: `/usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log` (verifierad 2026-05-19, ägare `www-data:www-data`).

## Bakgrund

Upptäckt vid server-stack-inventering 2026-05-19 (se [[texttv-prod-stack]] /server.md). `ls -la` visade:

```
-rw-r--r-- 1 www-data www-data 8042978705 May 19 11:07 laravel.log
-rw-r--r-- 1 www-data www-data     135254 Jun  6  2025 laravel.log.1
```

`laravel.log.1` är från 2025-06-06 och är minimal (135 KB) → någon manuell/halvfärdig rotation gjordes en gång men aldrig automatiserats.

Cron-tabellen (root) har **ingen** logrotate-relaterad rad för Laravel. `/etc/logrotate.d/` har inte heller någon explicit entry (behöver verifieras).

Andra loggfiler för jämförelse:
- nginx access.log: 441 MB (växer, men logrotate finns för nginx default)
- php8.2-fpm.log: 113 KB (rotateras veckovis, 12 veckor kvar — bra)

## Förslag

**Fas 1 — Diagnostik (innan rotation):**

1. Sampla logginnehållet för att se *vad* som loggas:
   ```
   tail -c 100M /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log \
     | tail -50000 > /tmp/laravel-sample.log
   ```
2. Klassificera de senaste ~50k raderna: är det INFO-spam, WARNING-mönster, eller ERROR som ingen sett? `grep -E '\[[0-9-]+ [0-9:]+\] [a-z]+\.(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL)'` → räkna per level.
3. Identifiera top-3 mest frekventa log-meddelanden (sannolikt sker spam från en specifik code path).

**Fas 2 — Log-level-skärpning (om diagnostik visar INFO-spam):**

Importerns `.env` på prod sätter `LOG_LEVEL`. Justera om det är `debug`/`info` → `warning` eller `error`. **Påverkar inte felmeddelanden** — vi förlorar bara debug-brus.

**Fas 3 — Logrotate-config:**

Skapa `/etc/logrotate.d/texttv-importer`:

```
/usr/share/nginx/l.texttv.nu/importer/storage/logs/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    su www-data www-data
}
```

- `copytruncate` så vi inte behöver signala Laravel (den öppnar log-filen via append varje gång)
- `rotate 7` → 7 dagars historik räcker oftast, kan justeras
- `compress + delaycompress` → gamla loggar gzip:as men senaste rotation håller plain text för enkel tail

**Fas 4 — Engångs-städ:**

```
# Backupera senaste 100 MB ifall vi vill grep-titta:
cp -p laravel.log /tmp/laravel-pre-cleanup-snapshot.log
truncate -s 0 /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log
```

(`truncate -s 0` tömmer utan att stoppa Laravel — file handle:n behåller sin position.)

## Risker

**Låg-medel.**

- Om felmeddelanden gömmer sig i debug-spam → fas 2 kan dölja viktiga signaler. Mitigation: diagnostik först (fas 1), inte log-level-byte innan vi vet vad som loggas.
- `copytruncate` har en pytteliten race-window där rader kan tappas under rotation. Acceptabelt för app-logging (inte säkerhetsrelaterat).
- Engångs-trunkering av 8 GB är destruktivt (gamla loggar förloras). Säkra backup först.

Inga risker för prod-trafik eller importerns funktion.

## Confidence

**Hög** — standardlösning för rotating Laravel-logs på enskild server. Logrotate-mönstret är välkänt och `copytruncate` är specifikt designat för appar som inte hanterar SIGHUP.

Den enda osäkerheten är fas 2 (log-level) — där behövs faktisk diagnostik innan beslut.
