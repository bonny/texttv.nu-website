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
mot samma mätning 2026-08-09.

**Känt normaltillstånd:** php-fpm-poolen slår i `pm.max_children = 5` ungefär
var tionde minut. Det är dokumenterat i todo
[#12](../../../todos/12-php-fpm-poolen-slar-i-taket.md) — rapportera det som
stående kapacitetsbrist, inte som en färsk incident.

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

```bash
ssh texttv.nu '
  echo "workers nu: $(ps -eo comm= | grep -c "^php-fpm")/5"
  echo "mättnadsvarningar i denna logg: $(grep -c max_children /var/log/php8.2-fpm.log)"
  grep max_children /var/log/php8.2-fpm.log | tail -5
  ss -lx | grep php8.2-fpm.sock
'
```

Tolka:

- **`pm.max_children = 5`, `pm = dynamic`.** Taket är lågt — och poolen slår
  i det regelbundet. Verifierat 2026-08-09: 62 varningar i den då aktuella
  loggen, senast var 7 minuter tidigare. **Detta är normaltillståndet idag,
  inte en incident.** Rapportera det som en kapacitetsbrist, inte som ett larm.
- `workers nu` nära 5 = poolen jobbar för fullt just nu.
- **Varje varning betyder att requests köade.** Slår den i taket flera gånger
  i timmen under en incident är det förklaringen till trögheten.
- **`/var/log/php8.2-fpm.log` roteras veckovis** (`/etc/logrotate.d/php8.2-fpm`),
  så antalet varningar är "sedan senaste rotation" — inte sedan tidernas
  begynnelse. Jämför med tidsstämplarna, inte bara antalet.
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

## all / (tomt)

Kör allt i **en enda SSH-anslutning** (~0.6 s totalt, verifierat).

```bash
ssh texttv.nu '
  echo === LOAD ===; uptime
  echo === MEM ===; free -h | head -2
  echo === DISK ===; df -h / | tail -1
  echo === TJÄNSTER ===
  for s in nginx php8.2-fpm mariadb; do printf "%-14s %s\n" "$s" "$(systemctl is-active $s)"; done
  echo === PHP-FPM ===
  echo "workers nu: $(ps -eo comm= | grep -c "^php-fpm")/5"
  echo "max_children-varningar: $(grep -c max_children /var/log/php8.2-fpm.log 2>/dev/null)"
  echo "senaste: $(grep max_children /var/log/php8.2-fpm.log 2>/dev/null | tail -1 | cut -d] -f1 | tr -d [)"
  echo === LOGGAR ===
  ls -lh /var/log/nginx/access.log /var/log/php8.2-fpm.log \
     /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log 2>/dev/null \
     | awk "{printf \"%-8s %s\n\", \$5, \$9}"
  echo === CRON ===
  echo "schedule:run senaste 10 min: $(journalctl -u cron --since "-10 min" --no-pager | grep -c schedule:run) (ska vara ~10)"
  echo === IMPORTER-FEL IDAG ===
  grep -c "^\[$(date +%Y-%m-%d)" /usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log 2>/dev/null
'
```

Komplettera med `importer`- och `http`-kollarna ovan (de går över HTTP utifrån,
inte via SSH, så de kan köras parallellt med SSH-anropet).

Sammanfatta till sist: "Allt OK" eller lista avvikelserna. Kondensera — visa
load + de 3 tyngsta processerna, inte hela `top`. Nämn bara tjänster som
**inte** är `active` (är alla uppe, säg det på en rad).

## Loggar

| Path                                                              | Roteras | Normalstorlek |
| ----------------------------------------------------------------- | ------- | ------------- |
| `/var/log/nginx/access.log`                                        | ja      | **1.3 GB**    |
| `/var/log/nginx/error.log`                                         | ja      | ~300 KB       |
| `/var/log/php8.2-fpm.log`                                          | veckovis| ~7 KB         |
| `/usr/share/nginx/l.texttv.nu/importer/storage/logs/laravel.log`   | ja      | ~13 MB        |

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

Baslinje 2026-08-10 kl 09 (6 484 requests): **p50 0,002 s · p95 0,006 s ·
p99 0,037 s · max 1,93 s.** Sajten är alltså mycket snabb i normalfallet —
en p95 som kryper uppåt är en tidig signal långt innan besökare hör av sig.

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

Tröskelvärdena ovan är mätta 2026-08-09. `server.md` är synkad mot samma
mätning. Servrar driver — när du märker att en siffra här inte stämmer
längre, **uppdatera både denna fil och `server.md`** i samma veva, och skriv
ut datumet du verifierade. En hälsokoll med föråldrade trösklar är värre än
ingen, för den larmar på fel saker och tiger om rätt.
