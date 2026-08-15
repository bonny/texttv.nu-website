# 12 – php-fpm-poolen slår i taket i normaldrift

**Status:** aktiv
**Senast uppdaterad:** 2026-08-09

## Sammanfattning

`pm.max_children = 5` i `/etc/php/8.2/fpm/pool.d/www.conf` är taket för antal
samtidiga PHP-requests. Poolen slår i det **regelbundet i vanlig drift** — inte
bara under toppar. Varje träff betyder att requests köade bakom poolen.

Upptäckt 2026-08-09 när `prod-health`-skillen byggdes.

## Mätning (2026-08-09, söndag kväll)

```
[09-Aug-2026 20:31:37] WARNING: [pool www] server reached pm.max_children setting (5), consider raising it
[09-Aug-2026 20:43:09] WARNING: ...
[09-Aug-2026 20:50:58] WARNING: ...
[09-Aug-2026 21:02:06] WARNING: ...
[09-Aug-2026 21:02:37] WARNING: ...
[09-Aug-2026 21:09:28] WARNING: ...
```

- **62 varningar** i den då aktuella loggen. `/var/log/php8.2-fpm.log` roteras
  veckovis, så det är ~62 mättnadstillfällen på under en vecka — ungefär
  **var tionde minut**, en helt vanlig söndagkväll.
- Trafik samtidigt: **~130 000 requests på 20 minuter** i `access.log`
  (≈ 100+ req/s in mot nginx). Merparten kortas av nginx `fastcgi_cache`
  (TTL 4 s), men det som släpps igenom trängs om 5 platser.
- Workers: 4 aktiva av 5 vid mätningen.

> ## Uppdatering 2026-08-14: ny rotorsak mätt — importerns tiominutersjobb
>
> #14:s fixar är deployade och verifierade (sajtens p99 kl 20–23: 0,493 s →
> 0,034 s). Men poolmättnaden finns kvar och har **ändrat karaktär**: från
> trafikstyrd till platt 4–8 varningar per timme dygnet runt, klustrade på
> minut 02, 11, 21, 31, 41 och 51.
>
> Det är importerns schema. `importer/app/Console/Kernel.php` har flera
> `everyTenMinutes()`-jobb som går samtidigt:
> - `importRange(500, 599)` — 100 sidor hämtas från svt.se, parsas och
>   GIF-genereras
> - `importRange(730, 750)` — 21 sidor
> - `texttv:cleanup-page-actions` — bulk-DELETE mot samma 2,7-miljonerstabell
>   som `most_read` läser
> - `texttv:cleanup-old-pages`
>
> **Och det drabbar besökarna.** Mätt kl 18–23 den 14 aug:
>
> | | p99 | Requests |
> | --- | --- | --- |
> | Cron-minuterna (~20 % av tiden) | **1,176 s** | 213 052 |
> | Övriga minuter | **0,027 s** | 859 318 |
>
> **43x sämre p99** under fönstret. Det är nu den dominerande orsaken till
> användarupplevd långsamhet — den har tagit över efter `most_read`.
>
> ### Utspridningen deployad 2026-08-15
>
> Verifierat med `php artisan schedule:list` på servern:
>
> | Jobb | Minuter |
> | ---- | ------- |
> | `importRange(500,599)` — 100 sidor | 1, 11, 21, 31, 41, 51 |
> | `cleanup-page-actions --limit=1000000` (natt) | 3, 13, 23, 33, 43, 53 |
> | `importRange(730,750)` — 21 sidor | 5, 15, 25, 35, 45, 55 |
> | `cleanup-old-pages` | 7, 17, 27, 37, 47, 57 |
> | `cleanup-old-pages --limit=300000` (natt) | 9, 19, 29, 39, 49, 59 |
>
> Alla på **udda** minuter, eftersom `everyTwoMinutes`-jobben (106–199 och
> 300–399, ~200 sidor) upptar alla jämna. Ingen logik ändrad, bara starttid.
> Importern verifierad frisk efter deploy: 0 fel, import igång, cron 5/5.
>
> ### Vad mätningen ska visa
>
> Jämför per minut-i-timmen, kl 18–23, mot baslinjen 2026-08-14
> (spikar på :02 :11 :21 :31 :41 :51, p99 1,75–2,28 s, normalminuter 0,027 s):
>
> ```bash
> ssh texttv.nu '\''F=/var/log/nginx/access.log.1; for m in 01 03 05 07 09 11 21 31 41 51; do
>   printf ":%s " $m; grep -E "DD/Aug/2026:(1[89]|2[0-3]):$m:" $F | grep -oE "rt=[0-9.]+" | cut -d= -f2 \
>   | sort -n | awk "{a[NR]=\$1} END {printf \"p99=%.3f\\n\", a[int(NR*0.99)]}"; done'\''
> ```
>
> Två utfall är intressanta:
> - **Spiken delar sig** i flera mindre → utspridningen fungerade, och vi ser
>   dessutom vilket jobb som bär vilken del.
> - **Spiken följer en enda minut** → det jobbet är ensam orsak, och då vet vi
>   exakt var nästa åtgärd ska sättas in.
>
> ### Övriga föreslagna åtgärder (alternativ C, nu med mätning bakom sig)
>
> 1. **Sprid ut jobben.** De ligger på samma minut idag. Ge dem olika offset
>    med `->cron("3-59/10 * * * *")` etc. Billigast, ändrar ingen logik.
> 2. **Flytta `texttv:cleanup-page-actions` från 10-minuterstakt** till
>    timvis eller nattetid. Bulk-DELETE mot tabellen webben läser är trolig
>    DB-sidig orsak.
> 3. **`nice` importern** så php-fpm vinner CPU-konkurrensen på de 2 kärnorna.
> 4. **Dela upp `importRange(500, 599)`** i mindre bitar spridda över de tio
>    minuterna i stället för en skur.
>
> Punkt 1 och 2 är låg risk och sannolikt tillräckliga. Mät samma
> cron-minut-vs-övriga-jämförelse efteråt.

> **Uppdatering 2026-08-10 kväll:** orsaken är nu känd — se **todo #14**.
> Requesterna är **DB-bundna** (605 av 608 fångade långsamma requests stod i
> `mysqli_query()`), inte CPU-bundna. Det betyder att alternativ A (höj
> `max_children`) faktiskt skulle hjälpa, tvärtemot vad som befarades nedan.
> Men #14 åtgärdar orsaken; A är symptomlindring. Ta #14 först.

## Varför det inte är ett minnesproblem

| Mått                        | Värde                    |
| --------------------------- | ------------------------ |
| RES per php-fpm-worker      | ~28 MB snitt, 37 MB max  |
| 5 workers totalt            | ~110 MB                  |
| RAM i servern               | 7,6 GB (~6,4 GB available) |

Att gå från 5 till 20 workers skulle kosta ~560 MB — helt oproblematiskt.
**Minnet är alltså inte det som motiverar det låga taket.** Värdet ser ut att
vara en kvarlämnad default snarare än ett medvetet val.

## Varför det ändå inte räcker att bara höja siffran

Servern har **2 vCPU**, och load ligger redan på 1,2–2,1. Att höja
`max_children` flyttar köandet från php-fpm till CPU-körkön om flaskhalsen är
CPU snarare än I/O-väntan. Två saker måste vägas in:

1. **Hur mycket av requesten är CPU vs väntan på MariaDB?** Är den mest
   DB-väntan hjälper fler workers direkt. Är den CPU-bunden hjälper de mindre.
2. **Importern äter en stor del av CPU:n.** `top` 2026-08-09 visade två
   `php8.2`-CLI-processer (root) på 70 % respektive 47 % CPU — det är
   `texttv:import` från schedulern, som kör varje minut. **Grundlasten på den
   här servern kommer alltså till stor del från importen, inte från
   besökstrafiken.** De konkurrerar direkt med webbrenderingen om samma 2 kärnor.

## Alternativ

| # | Åtgärd | Kostnad | Effekt |
| - | ------ | ------- | ------ |
| A | Höj `pm.max_children` 5 → 15, skala `start_servers`/`max_spare_servers` med | Minutar, reload av php8.2-fpm | Tar bort mättnadsvarningarna. Hjälper mest om requests är DB-väntande. |
| B | Mät först: sätt `pm.status_path` + ev. `$request_time` i nginx `log_format` | Litet, men kräver nginx-reload | Ger data att fatta beslut på istället för gissning. **Idag finns ingen responstidsmätning alls.** |
| C | Dämpa importerns CPU-andel (`nice`, glesare schema för lågfrekventa sidor) | Kodändring i `Kernel.php` | Frigör CPU till webben. Rör inte poolen. |
| D | Fler vCPU (Hetzner-uppgradering) | Pengar | Löser både A och C:s underliggande orsak. |

**Föreslagen ordning: B → A → C.** B är billigast och gör A och C mätbara
istället för gissade. Idag går det inte att svara på "blev det bättre?" —
`log_format anonymized` loggar varken `$request_time` eller
`$upstream_response_time`, och php-fpm har ingen status-sida.

## Instrumentering — genomförd 2026-08-10 (alternativ B)

| Instrument | Status | Utfall |
| ---------- | ------ | ------ |
| `rt=`/`urt=`/`cache=` i nginx `log_format` | **aktiv** | Första responstidsdata som funnits. Baslinje: p50 0,002 s, p95 0,006 s, p99 0,037 s. |
| `slowlog` + `request_slowlog_timeout = 3s` | **aktiv** | Ger PHP-stacktrace per långsam request. Rotation tillagd i `/etc/logrotate.d/php8.2-fpm-slowlog`. |
| `pm.status_path` | **återställd — fungerade inte** | Se nedan. |

### Varför `pm.status_path` ströks

Configen lästes in korrekt (`php-fpm8.2 -tt` visade `pm.status_path = /status`)
och poolen laddades om, men php-fpm svarade `File not found.` Två
nginx-uppsättningar provades: en `location = /status` i texttv.nu-vhosten
(fungerar inte — vhostens `if (!-e $request_filename) { rewrite ... }` körs på
server-nivå före location-matchning och skickar `/status` till CodeIgniter),
och en dedikerad server-block på `127.0.0.1:8081`.

Den kända lösningen är att tömma `security.limit_extensions` (idag
`.php .phar`). Det skulle låta poolen exekvera filer med godtycklig ändelse
på en pool som servar publik PHP — en säkerhetsförsämring som inte är värd en
statussida, särskilt när mättnadsvarningarna redan är tidsstämplade.
**Ändringen är helt återställd.**

### Vad slowloggen visade direkt

Första fångade requesten (`/live/399` på importern):

```
[10-Aug-2026 09:38:53]  [pool www] pid 2232902
script_filename = /usr/share/nginx/l.texttv.nu/importer/public/index.php
imagegif()             TeletextCharsExtractor.php:1052
getCharImageString()   TeletextCharsExtractor.php:1043
getCharImageHash()     TeletextCharsExtractor.php:1060
saveCharImageToDisk()  TeletextCharsExtractor.php:72
parseImage()           Importer.php:368
```

Den stod i **GD-bildgenerering, inte i ett svt.se-anrop och inte i databasen.**

Viktig reservation: slowloggen tar en ögonblicksbild av stacken när tröskeln
löser ut, inte en tidsfördelning över hela requesten. `/live/` gör *också* en
`fromRemote()`-hämtning från svt.se, så svt.se-latens kan mycket väl vara en
del av de 3–4 sekunderna — men vid den sampladepunkten var tiden lokal CPU.
Fler prover behövs för att väga de två mot varandra.

## ✗ Hypotesen om `/live/` är motbevisad (2026-08-10 kväll)

Efter ett dygns slowlog-data: **`/live/` står för 1 av 608 fångade långsamma
requests.** 607 är webbplatsen, och 578 av dem är en enda query — se
**todo #14**, som är den faktiska orsaken.

Hypotesen nedan byggdes på ett enda slowlog-prov plus en tidskorrelation, och
höll inte när underlaget växte. Den sparas för spårbarhet.

Två saker den lärde oss som fortfarande gäller: importern och webben **delar
pool**, och `/live/` **är** publikt nåbar (fortfarande värt att stänga under
#08 M5) — men det är inte det som mättar poolen.

### Ursprunglig hypotes (motbevisad)

Mättnadsvarningarna 2026-08-10 kl 09:25:00 och 09:25:07 sammanfaller exakt
med en skur `/live/`-requests kl 09:25:00–09:25:08, var och en på 3–4 s.

Mekanismen: **importern och webbplatsen delar samma php-fpm-pool** (båda går
mot `/run/php/php8.2-fpm.sock`). En `/live/`-request blockerar en worker i
3–4 s. Med `pm.max_children = 5` räcker en handfull samtidiga `/live/` för
att tömma hela poolen — och då köar även vanliga besökare på texttv.nu.

`/live/{n}` är en **debug-rutt som är publikt nåbar** — samma öppna dev-yta
som todo #08 M5 pekar ut. 78 anrop 2026-08-10 fram till kl 09:40, från
externa ip:n med webbläsar-user-agents.

**Om detta stämmer är rätt åtgärd att stänga `/live/` för publik åtkomst
(#08 M5), inte att höja `max_children`.** Det skulle vara både billigare och
lösa ett säkerhetsproblem samtidigt.

Ej bekräftat: varningarna nattetid (00:30, 01:10, 01:31, 01:41, 01:50, 02:50,
03:01, 03:41, 04:01, 07:10) förklaras inte av `/live/`-volymen. `rt=`-data
finns bara från 2026-08-10 kl 09:06, så natten går inte att analysera än.
**Kolla om ett dygn — då finns full historik.**

## Öppna frågor

- Är requests mest CPU- eller DB-bundna? Kräver B för att besvaras.
- Är mättnaden korrelerad med importer-körningarna (varje minut) eller med
  trafiktoppar? Tidsstämplarna finns i php-fpm-loggen och kan korsas mot
  `journalctl -u cron`.
- Hur ser det ut på en vardagsmorgon? Mätningen ovan är söndag kväll.

## Relaterat

- Skillen [`prod-health`](../.claude/skills/prod-health/SKILL.md) — subkommandot
  `fpm` visar aktuellt läge. Där står mättnaden dokumenterad som
  **normaltillstånd**, så framtida hälsokollar inte rapporterar den som en
  färsk incident.
- [`server.md`](../server.md) — avsnittet "php-fpm-poolen" har den faktiska configen.
