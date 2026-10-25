---
name: prod-health
description: "Kolla produktionsserverns hälsa — load, minne, disk, nginx/php-fpm/MariaDB, php-fpm-poolen och om importern faktiskt levererar färska sidor — via SSH till Hetzner. Använd när användaren frågar saker som 'hur mår prod', 'kolla cpu på prod', 'är allt OK på servern', 'varför är sajten långsam', 'uppdateras sidorna', eller när sajten är trög/nere och orsaken ska hittas. Subkommandon: (tomt)/all, cpu, mem, disk, services, fpm, importer, http."
---

# /prod-health — produktionsserver-hälsa

Snabb hälsokoll mot prod (`ssh texttv.nu`, loggar in som **root** via
`~/.ssh/config`). Servern heter `TextTV-hetzner`: **2 vCPU, 7.6 GB RAM,
75 GB disk**. Bare metal LEMP — **ingen Docker, ingen Redis**.

Paths: website `/usr/share/nginx/texttv.nu/`, importer
`/usr/share/nginx/l.texttv.nu/importer/`.

Allt i denna skill är read-only. Djupare bakgrund om stacken (cache-lager,
deploy-flöde, nginx-config) finns i [`server.md`](../../../server.md), synkad
mot samma mätningar (2026-08-09, delvis 2026-10-24).

**Känt normaltillstånd (mätt 2026-10-24):** `pm.max_children` höjdes 5 → 10
2026-10-16 20:15. Veckan efter slog poolen i taket **2 gånger på 8 dygn**
(18 okt 16:57 och 19 okt 04:07), mot 2–15 per dygn med 5. Slowloggen ligger
på 75–125 requests per dygn över 3 s, 200–350 på lördagar med mycket sport,
nästan alla `most_read` kl 16–23. p99 kl 21–22 är 0,019–0,026 s. Detaljer i
todo [#12](../../../todos/12-php-fpm-poolen-slar-i-taket.md). En enstaka
varning eller en slowlog på ~100 per dygn är normalläge, inte en incident.

## Tolka argumentet

| Argument                 | Visar                                                   |
| ------------------------ | ------------------------------------------------------- |
| (tomt) / `all`           | Sammanfattning av allt nedan, i en SSH-anslutning       |
| `cpu`                    | Load + topprocesser                                     |
| `mem` / `memory`         | RAM + swap                                              |
| `disk`                   | Diskutrymme + största loggarna                          |
| `services` / `systemd`   | nginx, php8.2-fpm, mariadb                              |
| `fpm`                    | php-fpm-poolen: workers, mättnadsvarningar, socket-kö   |
| `importer`               | Levererar importern färska sidor? Felfrekvens?          |
| `http`                   | Statuskod, responstid, `X-Cache` utifrån                |
| `updates`                | Senaste apt-ändringarna, väntande paket, needrestart    |

## Kommandon

### cpu

```bash
ssh texttv.nu "uptime && echo --- && top -bn1 | head -15"
```

Tolka: **servern har 2 vCPU** — load över 2 är ihållande mättnad. Normalt
ligger load runt 1–2 (verifierat 2026-08-09: `1.22, 1.89, 2.07`), så den
går redan nära taket i vardagsläge. Hög `wa` på `%Cpu(s)`-raden = disk-I/O-bunden.

**Toppen i listan är oftast `php8.2` som root — det är importern, inte webben.**
Schedulern kör `texttv:import` varje minut, och de processerna drar regelbundet
50–70 % CPU var (verifierat 2026-08-09). Det är alltså importen, inte
besökstrafiken, som sätter grundlasten på den här servern. Se `php-fpm8.2`-
och `www-data`-processer för det som faktiskt är webbtrafik.

**Historik med `sar`** (sysstat, installerat 2026-10-25, sampel var 10:e
minut, 28 dygn i `/var/log/sysstat/saDD`):

```bash
ssh texttv.nu 'sar -q'                              # load per 10 min idag
ssh texttv.nu 'sar -q -f /var/log/sysstat/sa18'     # load den 18:e
ssh texttv.nu 'sar -u -s 21:00:00 -e 23:00:00'      # CPU user/sys/iowait på kvällen
ssh texttv.nu 'sar -r'                              # minne
```

Använd det för att svara på "hur såg det ut i går kväll", inte bara nu.
Servern loggar i svensk tid.

### mem

```bash
ssh texttv.nu "free -h"
```

Tolka: `available` är det som räknas, inte `free` — Linux räknar buff/cache
som återanvändbart, och här är buff/cache ~6.6 GB medan `free` visar ~300 MB.
Det är friskt, inte alarmerande. Normalläge: ~1 GB `used`, ~6.4 GB `available`.
**Servern har 0 swap** — swap-raden ska vara helt 0. Är den inte det är något
omkonfigurerat.

### disk

```bash
ssh texttv.nu "df -h / && echo --- && du -sh /var/log/nginx /usr/share/nginx/texttv.nu /usr/share/nginx/l.texttv.nu 2>/dev/null"
```

Tolka: 75 GB totalt, **24 % använt** (verifierat 2026-08-09). Över 80 % är värt
att flagga. Största enskilda posten är `/var/log/nginx/access.log` — se Loggar nedan.

### services

```bash
ssh texttv.nu "systemctl is-active nginx php8.2-fpm mariadb && echo --- && systemctl status nginx php8.2-fpm mariadb --no-pager | grep -E 'Active:|Main PID'"
```

Tolka: alla tre ska vara `active`. Tjänstnamnen är exakt `nginx`,
`php8.2-fpm`, `mariadb` (inte `mysql` — MariaDB 10.11.18 kör, service-aliaset
`mysql` finns men använd inte det).

### fpm

**Kolla alltid den här när sajten är långsam.** Det är php-fpm som tar slut
först — inte CPU, RAM eller MariaDB.

**Det finns ingen php-fpm status-sida, och försök inte sätta upp en.**
`pm.status_path` provades 2026-08-10 och fungerade inte: php-fpm svarar
`File not found.` även med korrekt inläst config och en dedikerad
localhost-vhost. Den kända lösningen är att tömma `security.limit_extensions`
(idag `.php .phar`), vilket skulle tillåta poolen att exekvera filer med
godtycklig ändelse — en säkerhetsförsämring som inte är värd en statussida.
Ändringen är återställd. **Detaljer i todo #12.**

Använd istället de två som faktiskt fungerar:

1. **Mättnadsvarningarna** nedan — tidsstämplade, till skillnad från
   statussidans kumulativa räknare.
2. **Slowloggen** — `/var/log/php8.2-fpm.slow.log`, aktiv sedan 2026-08-10
   med tröskel 3 s. Den ger en **PHP-stacktrace** för varje långsam request,
   alltså exakt var koden stod. Det är det enda som skiljer "väntar på
   MariaDB" (fler workers hjälper) från "brinner CPU" (fler workers gör det
   värre).

```bash
ssh texttv.nu 'ls -l /var/log/php8.2-fpm.slow.log; tail -40 /var/log/php8.2-fpm.slow.log'
```

Läs stacktracen nedifrån och upp — översta raden är där den stod när
tröskeln slog till. `imagegif()`/`TeletextCharsExtractor` = CPU-bunden
bildgenerering. En PDO/mysqli-rad högst upp = DB-väntan.

Den översta raden är nästan alltid `mysqli_query()` i CI:s drivrutin och
säger inget om *vilken* query. Gruppera i stället på första raden i
`application/`, och räkna per dag för att se trenden. Loggen roteras veckovis
(gzippade generationer: räkna rader, inte filstorlek):

```bash
ssh texttv.nu '
  awk "/script_filename/{f=0} /\/application\//{ if(!f){sub(/^\[0x[0-9a-f]+\] /,\"\"); print; f=1} }" /var/log/php8.2-fpm.slow.log | sort | uniq -c | sort -rn | head -5
  grep -E "^\[[0-9]{2}-" /var/log/php8.2-fpm.slow.log | cut -c2-12 | sort | uniq -c'
```

`texttv_helper.php:257` (var `:259` före 2026-10-24) = `most_read`-queryn (todo #12, #14). Den körs nästan
alltid som nginx bakgrundsregenerering, så besökarna väntar inte på den, men
den håller en worker.

```bash
ssh texttv.nu '
  echo "workers nu: $(ps -eo comm= | grep -c "^php-fpm")/10 (+1 master)"
  echo "mättnadsvarningar i denna logg: $(grep -c max_children /var/log/php8.2-fpm.log)"
  grep max_children /var/log/php8.2-fpm.log | tail -5
  ss -lx | grep php8.2-fpm.sock
'
```

Tolka:

- **`pm.max_children = 10`, `pm = dynamic`** (5 fram till 2026-10-16). Med 5
  slog poolen i taket regelbundet: 2026-08-09 ungefär var tionde minut, efter
  fixarna i #14 och #12 2–15 varningar per dygn med toppar på lördagskvällar
  (mätt 2026-10-14). Med 10: 2 varningar på 8 dygn (mätt 2026-10-24). Flera
  varningar per dygn igen är en avvikelse värd att titta på.
- `workers nu` nära 10 = poolen jobbar för fullt just nu. Siffran räknar
  även master-processen.
- **Varje varning betyder att requests köade.** Slår den i taket flera gånger
  i timmen under en incident är det förklaringen till trögheten.
- **`/var/log/php8.2-fpm.log` roteras veckovis** (`/etc/logrotate.d/php8.2-fpm`),
  så antalet varningar är "sedan senaste rotation" — inte sedan tidernas
  begynnelse. Jämför med tidsstämplarna, inte bara antalet. Filen växer
  ~1,3 MB per vecka, nästan bara slowloggens `about to trace`/`finished
  trace`-rader. Storleken säger alltså inget; räkna `max_children`-raderna.
- Socket: `/run/php/php8.2-fpm.sock`, backlog 4096. Stigande `Recv-Q` i
  `ss -lx` = anslutningar hinner inte plockas upp.

### importer

Hela sajtens innehåll hänger på att Laravel-schedulern körs varje minut från
root-cron. Två kollar, i den här ordningen:

**1. Kör cron alls?**

```bash
ssh texttv.nu 'journalctl -u cron --since "-10 min" --no-pager | grep schedule:run'
```

Ska ge en rad per minut. Tomt svar = schedulern står still, och då är det
orsaken — gå inte vidare till färskhetskollen. **Använd `journalctl`, inte
`/var/log/syslog` — den filen finns inte på servern** (ingen rsyslog, allt
går till journald).

**2. Kommer färska sidor ut?** Det testar cron + importer + DB + webb i ett svep.

```bash
for p in 100 300 401 104; do
  curl -s "https://texttv.nu/api/get/$p?cb=$RANDOM" | python3 -c "
import json,sys,time
d=json.load(sys.stdin)[0]
print(f'sida {d[\"num\"]}: {round((time.time()-int(d[\"date_updated_unix\"]))/60)} min sedan')"
done
```

Tolka:

- **`date_updated_unix` är när innehållet senast _ändrades_, inte när det
  senast importerades.** Importern skriver bara ny rad när sidan faktiskt
  skiljer sig. En enskild gammal sida betyder därför ingenting.
- **Titta på den _färskaste_ sidan i listan.** Spridningen är stor även när
  allt är friskt: 13–225 min (2026-08-09 kväll), 29–274 min (2026-08-10
  förmiddag) — båda gångerna med importern verifierat igång.
- **Tröskel: färskaste sidan äldre än ~45 min är värt att kolla vidare — inte
  att larma på.** En tidigare version av den här filen sa 20 min; det gav
  falsklarm redan dagen efter. Nyhetsflödet styr, inte importern.
- **Bekräfta alltid mot cron innan du drar slutsatsen att något står still.**
  Är `schedule:run` igång 10/10 och felfrekvensen normal, så importerar den —
  då är gamla sidor bara sidor som inte ändrats. Ett direkt kvitto:

```bash
ssh texttv.nu 'ps -eo etimes=,args= | grep "texttv:import" | grep -v grep | head -3'
```

  Rader här = import pågår i detta ögonblick.
- Felfrekvens:

```bash
ssh texttv.nu 'grep -oE "^\[[0-9]{4}-[0-9]{2}-[0-9]{2}" /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log | sort | uniq -c | tail -10'
```

  **1–9 fel/dag är normalt.** Det är nästan uteslutande `cURL error 18/35`
  mot svt.se — transienta nätverksfel, inte vår bugg, och sidan hämtas om
  nästa varv. Verifierad baslinje 2026-08-01→09: 40, 6, 9, 2, 1, 3, 2, 0, 1.
  **Dagar över ~20 är värda att titta på** — antingen strular svt.se eller så
  har importern gått sönder mot ett ändrat sidformat (t.ex.
  `Undefined array key` från `Importer.php`, som är vår bugg, inte SVT:s).

### http

```bash
curl -s -o /dev/null -w 'status=%{http_code} tid=%{time_total}s\n' "https://texttv.nu/100?cb=$RANDOM"
curl -sI "https://texttv.nu/100?cb=$RANDOM" | grep -iE 'x-cache|content-type'
```

Tolka: **~0.07 s är normalt** (verifierat 2026-08-09). `X-Cache: MISS` betyder
full PHP-render — det är det du vill mäta. `STALE` betyder att du fick en
cachad kopia och siffran säger inget om verklig renderingstid. **Använd alltid
`?cb=$RANDOM`**; nginx cache-key inkluderar query string, så cache-bustern
tvingar fram en äkta render. TTL är bara 4 s, så utan buster mäter du ofta
ingenting alls.

### updates

Vad som har uppdaterats, vad som väntar och om något behöver startas om. Read-only.

```bash
ssh texttv.nu '
  echo "--- apt-ändringar senaste 7 dagarna (auto = unattended-upgrades, hand = manuellt):"
  zcat -f /var/log/apt/history.log.1.gz /var/log/apt/history.log 2>/dev/null \
    | awk -v s="$(date -d "-7 days" +%F)" "/^Start-Date:/{d=\$2} /^Commandline:/{c=(\$0 ~ /unattended-upgrade/) ? \"auto\" : \"hand\"} /^(Install|Upgrade|Remove):/ && d>=s {print d, c, substr(\$0,1,200)}"
  echo "--- väntande paket (installeras inte automatiskt):"
  apt list --upgradable 2>/dev/null | grep upgradable | cut -d/ -f1 | tr "\n" " "; echo
  echo "--- needrestart (KSTA 1 = rätt kernel kör):"; needrestart -b | grep -E "KSTA|SVC"
  [ -f /var/run/reboot-required ] && echo "OMSTART KRÄVS: $(tr "\n" " " < /var/run/reboot-required.pkgs)" || echo "ingen omstart krävs"
  echo "--- senaste omstarter:"; last -x reboot -F | head -2
  echo "--- nästa automatiska körning:"; systemctl list-timers apt-daily-upgrade.timer --no-pager --no-legend
'
```

Tolka:

- **Nattschemat** (sedan 2026-10-25): uppdateringarna och needrestart körs i natt-fönstret, och
  en omstart som krävs sker samma natt. Unattended-upgrades installerar Debians huvudrelease,
  `-security` och `-updates` (tzdata m.m.).
- **needrestart** startar om tjänster som kör gamla bibliotek efter varje apt-körning. `dbus` och
  `unattended-upgrades` står kvar i `SVC`-listan med flit, de undantas i standardkonfigen. Andra
  tjänster där betyder att apt inte har kört sedan biblioteket byttes. `KSTA` 2 eller 3 = en nyare
  kernel är installerad men inte bootad; omstarten sker natten efter om `reboot-required` finns.
- **MariaDB är undantagen** från automatiska uppdateringar (`Package-Blacklist` i
  `51unattended-upgrades-local`, efter att uppgraderingen hängde sig 2026-10-16). Säkerhetsfixar
  för MariaDB dyker därför upp under "väntande" och ska installeras för hand, se `server.md`.
- ⚠️ **`unattended-upgrade --dry-run` lämnar falska rader i `history.log`** (med `Requested-By`)
  som här syns som `auto`. Hände 2026-10-25 med tzdata. Står paketet samtidigt under "väntande",
  eller visar `dpkg-query -W <paket>` gamla versionen, är raden en torrkörning.

## all / (tomt)

Kör allt i **en enda SSH-anslutning** (~0.6 s totalt, verifierat).

```bash
ssh texttv.nu '
  echo === LOAD ===; uptime
  echo === MEM ===; free -h | head -2
  echo === DISK ===; df -h / | tail -1
  echo === TJÄNSTER ===
  for s in nginx php8.2-fpm mariadb; do printf "%-14s %s\n" "$s" "$(systemctl is-active $s)"; done
  echo "misslyckade enheter: $(systemctl --failed --no-legend | wc -l) (ska vara 0)"; systemctl --failed --no-legend
  echo === PHP-FPM ===
  echo "workers nu: $(ps -eo comm= | grep -c "^php-fpm")/10 (+1 master)"
  echo "max_children-varningar: $(grep -c max_children /var/log/php8.2-fpm.log 2>/dev/null)"
  echo "senaste: $(grep max_children /var/log/php8.2-fpm.log 2>/dev/null | tail -1 | cut -d] -f1 | tr -d [)"
  echo === LOGGAR ===
  ls -lh /var/log/nginx/access.log /var/log/php8.2-fpm.log \
     /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log 2>/dev/null \
     | awk "{printf \"%-8s %s\n\", \$5, \$9}"
  echo === CRON ===
  echo "schedule:run senaste 10 min: $(journalctl -u cron --since "-10 min" --no-pager | grep -c schedule:run) (ska vara ~10)"
  echo === MARIADB-I/O ===
  P=$(pidof mariadbd)
  ps -o etimes= -p $P | awk "{printf \"uptime %.1f dygn\n\", \$1/86400}"
  awk "/read_bytes/ {printf \"läst från disk sedan start: %.2f GB\n\", \$2/1e9}" /proc/$P/io
  awk "\$3==\"sda\" {printf \"hela disken läst sedan boot: %.1f GB\n\", \$6*512/1e9}" /proc/diskstats
  top -bn1 | grep "%Cpu" | grep -oE "[0-9.]+ wa"
  echo === IMPORTER-FEL IDAG ===
  grep -c "^\[$(date +%Y-%m-%d)" /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log 2>/dev/null
'
```

Komplettera med `updates`, `importer` och `http` ovan (de går över HTTP utifrån,
inte via SSH, så de kan köras parallellt med SSH-anropet).

Tolka `misslyckade enheter`: allt annat än 0 är en avvikelse även om sajten mår bra —
en oneshot-tjänst som `logrotate.service` kan misslyckas varje natt i månader utan att
något annat märks (hände 2026-07 → 2026-09, se `server.md` under Loggar).

Tolka `MARIADB-I/O`: MariaDB kör på Debian-default, alltså 128 MB buffer pool
mot ~2 GB data (verifierat 2026-10-16). Det fungerar här eftersom
`innodb_flush_method` är default (`fsync`, inte `O_DIRECT`): det som inte ryms
i poolen läses ur Linux page cache (~4,4 GB buff/cache), inte från disk.
Normalläge 2026-10-16: hela disken ~33 GB läst på 36 dygn (~1 GB/dygn),
MariaDB ~0,8 GB på ett halvt dygn efter omstart (uppvärmning), 0 % iowait.
**Flagga om diskläsningen når tiotals GB per dygn eller `wa` ligger över
några procent** — då har page cachen börjat tränga ut databasen och buffer
poolen behöver höjas i `/etc/mysql/mariadb.conf.d/50-server.cnf`. (Samma
grundorsak fanns på Brottsplatskartan, men där kör MariaDB i Docker med
`O_DIRECT`, så varje miss gick till disk: 51 TB på 15 dygn.)

Sammanfatta till sist: "Allt OK" eller lista avvikelserna. Kondensera — visa
load + de 3 tyngsta processerna, inte hela `top`. Nämn bara tjänster som
**inte** är `active` (är alla uppe, säg det på en rad).

## Loggar

Storlekar verifierade 2026-10-24:

| Path                                                              | Roteras  | Normalstorlek |
| ----------------------------------------------------------------- | -------- | ------------- |
| `/var/log/nginx/access.log`                                        | dagligen, 14 gen. | **0,75–1,1 GB per dygn** (gz 46–76 MB), hela katalogen ~2,5 GB |
| `/var/log/nginx/error.log`                                         | ja       | ~200–300 KB   |
| `/var/log/php8.2-fpm.log`                                          | veckovis | **~1,3 MB per vecka** (slowloggens trace-rader) |
| `/var/log/php8.2-fpm.slow.log`                                     | veckovis | ~1,2 MB       |
| `/usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log`   | dagligen | 5–20 KB (några tiotal rader) |

**Trafiken är 3–4,5 miljoner requests per dygn**, 150–800 k per timme
(lördagar och kvällar högst). Nästan allt är appens polling
(`/api/last_updated/*`, `/api/get/*`, `/api/most_read/news`). Hundratusentals
requests i timmen är alltså normalt, inte en bot.

**`access.log` har responstid sedan 2026-08-10.** `log_format anonymized`
(`/etc/nginx/conf.d/general.conf`) avslutas med tre fält:

```
rt=0.004 urt=0.004 cache=MISS
```

- `rt` = `$request_time`, hela requesten inkl. nätet ut till besökaren.
- `urt` = `$upstream_response_time`, tiden php-fpm höll på. `urt=-` betyder
  att ingen upstream anropades — svaret kom ur nginx-cachen.
- `cache` = `$upstream_cache_status`: `HIT` / `MISS` / `STALE`.

Percentiler för en timme:

```bash
ssh texttv.nu 'grep "$(date +%d/%b/%Y:%H)" /var/log/nginx/access.log | grep -oE "rt=[0-9.]+" | cut -d= -f2 | sort -n | awk "{a[NR]=\$1} END {printf \"n=%d p50=%.3fs p95=%.3fs p99=%.3fs max=%.3fs\n\", NR, a[int(NR*0.50)], a[int(NR*0.95)], a[int(NR*0.99)], a[NR]}"'
```

Baslinje kl 21–22, 16–23 okt 2026 (480–800 k requests per timme):
**p50 0,002 s · p95 0,007–0,008 s · p99 0,019–0,026 s · max 6–60 s.**
Enstaka requests på tiotals sekunder finns varje kväll; titta på p95/p99,
inte max. Sajten är alltså mycket snabb i normalfallet, och en p95 som kryper
uppåt är en tidig signal långt innan besökare hör av sig. Den första
baslinjen (2026-08-10 kl 09) räknade bara 6 484 requests och är inte
jämförbar i volym.

> **Loggrader före 2026-08-10 saknar `rt=`** och är dessutom dubbletter — fram
> till dess loggade nginx varje request två gånger (se todo #13, åtgärdad).
> Greppar du i roterade loggar från före det datumet: filtrera på `rt=`,
> annars dubbelräknar du. Efter fixen har alla rader `rt=`.

## Konventioner

- **Aldrig modifiera** något på servern via denna skill. Ingen `systemctl
  restart`, ingen cache-rensning, ingen redigering av configar. För skrivande
  operationer: säg vad som borde göras och låt användaren köra det.
- **Säg vad du körde** — visa kommandot, så användaren kan reproducera manuellt.
- **Tolka, dumpa inte** — du har sett hela output; ge slutsatsen ("RAM mår bra,
  6.4 GB tillgängligt") inte bara siffrorna.
- **Skilj normaltillstånd från incident.** php-fpm-mättnad och en handfull
  cURL-fel om dagen är hur den här servern ser ut när den mår bra. Rapportera
  dem som stående kapacitetsbrist, inte som nyheter — annars drunknar riktiga
  avvikelser i brus.

## Edge cases

- **`crontab -l` och `mysql -e` blockeras av Claude Codes sandbox-klassificerare**
  (båda ser mutationsdugliga ut). Gå inte runt det — det behövs inte:
  `journalctl -u cron` visar vad cron faktiskt kör, rad för rad, och
  HTTP-kollarna i `importer` testar mer av kedjan än en DB-fråga gör.
  Crontabens innehåll är dessutom dokumenterat i `server.md`.
- **SSH timeout** — säg det och fråga om servern svarar alls; rekommendera
  Hetzner Cloud Console.
- **Sajten svarar men är trög** → kör `fpm` först, inte `cpu`.
- **Sajten svarar inte alls, ingen statuskod** → php-fpm-socketen tar inte
  emot anslutningar. Kolla `ss -lx` och `/var/log/nginx/error.log` efter
  `upstream` / `connect() to unix:` -fel.
- **En sida uppdateras inte** → kolla `importer`-felfrekvensen först. Är det
  bara den sidan och inga fel: sidan har troligen inte ändrats hos SVT.
- **`X-Cache: STALE` vid verifiering efter deploy** → normalt de första
  sekunderna. TTL är 4 s; kör om med ny `?cb=`.

## Håll siffrorna färska

Tröskelvärdena ovan är mätta 2026-08-09 där inget annat datum står; php-fpm,
slowlog, loggstorlekar, trafik och percentiler är uppdaterade 2026-10-24.
`server.md` är synkad mot samma mätningar. Servrar driver — när du märker att en siffra här inte stämmer
längre, **uppdatera både denna fil och `server.md`** i samma veva, och skriv
ut datumet du verifierade. En hälsokoll med föråldrade trösklar är värre än
ingen, för den larmar på fel saker och tiger om rätt.
