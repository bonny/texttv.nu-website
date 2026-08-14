# 14 – `most_read`-queryn är orsaken till poolmättnaden

**Status:** klar 2026-08-14
**Senast uppdaterad:** 2026-08-14

## Åtgärd 1 deployad 2026-08-11 (commit c8aa622)

`X-Accel-Expires: 60` sätts i `api.php` för `most_read`. nginx cachar därmed
svaret i 60 s i stället för vhostens `fastcgi_cache_valid 4s`.

Verifierat efter deploy:

| Kontroll | Resultat |
| -------- | -------- |
| Headern läcker till klienten? | **Nej** — nginx strippar `X-Accel-Expires` som avsett |
| API-svar oförändrat? | **Ja** — byte-identiskt mot fyra baslinjer tagna före deploy (avslutade datum 2026-08-08/09, alla typer) |
| Cachar den i 60 s? | **Ja** — `x-cache: HIT` vid t=6, 12, 25 och 45 s på samma URL. Med 4s TTL hade den slagit om till MISS/STALE vid 6 s |
| Sajten | `/100`, `/`, `/api/get/100`, `api.texttv.nu` — alla 200 |

### Effektmätning 2026-08-11 kl 11:54 — TTL:en fungerar

Jämförelse mellan **samma timmar** (kl 10:00–11:59) före och efter, på
jämförbar trafik (235 477 → 255 881 requests, dvs. något *mer* idag):

**Cachebeteende för `/api/most_read`** — antal svar som krävde att PHP körde:

| | Igår (4s TTL) | Idag (60s TTL) |
| --- | --- | --- |
| HIT | 5 878 (63 %) | **9 142 (96 %)** |
| STALE | 2 075 | 261 |
| UPDATING | 1 340 | 132 |
| MISS | 3 | 1 |
| **Nådde PHP** | **3 418** | **394** |

**8,7 gånger färre regenereringar.**

**Responstid `/api/most_read`:**

| | Igår | Idag |
| --- | --- | --- |
| p95 | 1,114 s | **0,000 s** |
| p99 | 1,453 s | 0,783 s |
| max | 3,392 s | 1,883 s |

**Responstid för hela sajten** — det här är huvudresultatet, eftersom det
visar att regenereringarna höll upp *orelaterade* requests:

| | Igår | Idag |
| --- | --- | --- |
| p50 | 0,002 s | 0,002 s |
| p95 | 0,008 s | 0,007 s |
| **p99** | **0,521 s** | **0,030 s** |
| max | 17,697 s | 9,877 s |

**p99 för hela sajten är 17 gånger bättre.** Mekanismen i #12 är därmed
bekräftad: `most_read`-regenereringarna ockuperade php-fpm-workers och lade
sekunder på requests som inte hade med `most_read` att göra.

**php-fpm-mättnad**, samma fönster (kl 06–11:54): **7 varningar igår → 1 idag.**

**Slowlog:** noll träffar kl 08–11 idag. Igår samma timmar: 1–3/h, och
kvällstopparna låg på 271 (kl 21), 361 (kl 22), 333 (kl 23).

### Kvällsprovet bekräftat 2026-08-14 — vinsten håller

p99 för **hela sajten** kl 20–23, alltså i toppen:

| Dygn | p99 | Requests |
| ---- | --- | -------- |
| 10 aug (före) | **0,493 s** | 953 365 |
| 12 aug | 0,031 s | 719 727 |
| 13 aug | 0,124 s | 880 566 |
| 14 aug | **0,034 s** | 718 446 |

Slowlog-träffar per dygn: **1 198 (10 aug) → 272–411** efter fixarna.

**Denna todo är därmed avklarad.** `most_read` är fortfarande den enskilt
tyngsta queryn i slowloggen (597 av 659 träffar 13–14 aug), men den anropas
nu så sällan att den inte längre driver poolmättnaden. Vidare optimering av
själva queryn (N+1-loopen, materialiserat aggregat) är inte motiverad förrän
något annat pekar dit.

### Men mättnaden finns kvar — av en ny orsak

`max_children`-varningar per dygn: 52 (10 aug) → 27 (11 aug, deploydagen) →
82 → 138 → 131. Alltså **uppåt**, trots att slowloggen gick ner.

Mönstret har dock ändrats i grunden: från trafikstyrt till **platt 4–8 per
timme dygnet runt**, med klustring på minut 02, 11, 21, 31, 41, 51 — var
tionde minut. Det är importerns schema, inte besökslast. **Överlämnas till
todo #12**, som nu har en ny och mätt rotorsak.

### Utgångsläge för effektmätning

Mätt precis före deploy (05:36) respektive efter (05:42):

- slowlog: 16 008 rader → 16 048
- `max_children`-varningar: 142 → 142, senaste 04:50:50

**Detta säger ingenting ännu** — deployen skedde 05:38, i lågtrafik.
Problemet toppar på kvällen: 271 slowlog-träffar kl 21, 147 kl 20.
**Mät kvällen 2026-08-11 kl 21–22 och jämför mot de siffrorna.**

Blir förbättringen liten trots 15x färre regenereringar är nästa steg att
titta på N+1-loopen (`Texttv_page::load()` per rad) eller på att materialisera
aggregatet i en egen tabell.

### Deployen var trasig av orsaker utan samband

Första försöket failade: `HETZNER_HOST` pekade på `hetzner.texttv.nu`, vars
DNS-post rensades bort 2026-08-10. Hemligheten är uppdaterad till `texttv.nu`.
**Ingen automatisk deploy hade fungerat sedan dess** — det upptäcktes bara för
att vi råkade deploya. `hetzner.texttv.nu` och `api-hetzner.texttv.nu` står
kvar som `server_name` i vhosten trots att de inte längre resolvar; städbart.

## Sammanfattning

Slowloggen (aktiverad 2026-08-10, se #12) fångade **608 långsamma requests
under första dygnet**. Fördelningen är inte jämn — den är nästan helt en enda
kodväg:

| Andel | Var |
| ----- | --- |
| 605 / 608 | stod i `mysqli_query()` — dvs. **väntade på MariaDB** |
| 578 / 608 | `get_most_read_pages_for_period()` i `application/helpers/texttv_helper.php:228`, anropad från `application/controllers/api.php:60` |
| 607 / 608 | webbplatsen (CodeIgniter), **inte** importern |

**95 % av all långsamhet på sajten kommer från `/api/most_read`.**

## Varför det spelar roll för #12

Det här besvarar den öppna frågan i todo #12 — CPU eller DB?

**Requesterna är DB-bundna, inte CPU-bundna.** En worker som står i
`mysqli_query()` förbrukar ingen CPU; den ockuperar bara en av poolens fem
platser medan MariaDB arbetar. Med `pm.max_children = 5` räcker ett fåtal
samtidiga `most_read`-regenereringar för att fylla poolen — och då köar alla
andra besökare bakom dem.

Det betyder också att **höja `pm.max_children` faktiskt skulle hjälpa** (till
skillnad från vad som befarades när flaskhalsen antogs vara CPU). Men det
behandlar symptomet; queryn är orsaken.

## Trafikbild (2026-08-10)

- **132 924 anrop** till `/api/most_read` under dygnet.
- Cachestatus: 52 222 `HIT`, 22 381 `UPDATING`, 13 164 `STALE`, 23 `MISS`.
- Responstid för endpointen: p50 0,000 s, **p95 1,756 s, max 6,389 s**.

nginx-cachen (TTL 4 s, `fastcgi_cache_lock on`, stale-while-revalidate) döljer
det mesta — därav p50 på noll. Men `UPDATING`/`STALE` betyder att origin
regenererar i bakgrunden hela tiden, och **varje regenerering binder en worker
i upp till 6 sekunder**.

Slowlog-träffar per timme visar att det eskalerar med kvällstrafiken:

```
15:00   1
17:00   9
19:00  51
20:00 147
21:00 271
22:00 115
```

## Vad som är fel med queryn

`texttv_helper.php` rad ~160–225. Den gör en **cross-schema JOIN** mellan
`texttv_stats.page_actions` (analytics-tabellen, hög volym) och
`` `texttv.nu`.texttv ``, filtrerar på `pa.created_at BETWEEN ...`, grupperar
på `pa.page_ids` och sorterar — med `LIMIT 50` sist.

Misstänkta orsaker, i fallande sannolikhet:

1. **`UNCOMPRESS(tt.page_content)` ligger i SELECT-listan.** Sidinnehållet
   dekomprimeras för *varje* joinad rad — alltså även för alla rader som
   sedan kastas av `LIMIT 50`. Det är sannolikt den enskilt största posten.
2. **Index på `page_actions`.** Filtret är `created_at BETWEEN` + `type IN (...)`.
   Saknas ett sammansatt index på `(created_at, type)` blir det full scan över
   en tabell som växer med varje sidvisning.
3. **`GROUP BY pa.page_ids` med många icke-aggregerade `tt.*`-kolumner** —
   tvingar fram temporär tabell.

## Åtgärd 2 deployad 2026-08-11 (commit 1533f41): UNCOMPRESS ut ur grupperingen

### Den verkliga orsaken, hittad via EXPLAIN

Den gamla queryn hade `UNCOMPRESS(tt.page_content)` i SELECT-listan på den
grupperande queryn. Det betyder att **temptabellen innehöll en BLOB** — och
MariaDB kan inte hålla BLOB i minnestabeller. Temptabellen tvingades därför
till disk **vid varje anrop, oavsett `tmp_table_size`**.

Servern hade 180 641 `Created_tmp_disk_tables` (~20 000/dygn) när detta
mättes. Att höja minnesinställningar hade inte hjälpt en enda byte — det var
datatypen, inte storleken, som tvingade fram diskskrivningen.

Den nya grupperingen ger 3 610 rader per dygn utan BLOB och ryms i minnet med
nuvarande 16 MB.

### Verifierat i produktion

| Kontroll | Resultat |
| -------- | -------- |
| API-svar oförändrat | **Ja** — byte-identiskt mot alla fyra baslinjer, körda mot live prod efter deploy |
| Disk-temptabeller | **10 ocachade anrop → +52 temptabeller i minnet, +0 på disk** |
| Sajten | `/100`, `/`, `/api/get/100`, `api.texttv.nu` — alla 200 |
| Ocachad `most_read` | 0,53–0,65 s (mot p95 1,11 s och max 3,39 s före TTL-fixen) |

Mekanismen är därmed bekräftad kausalt, inte bara i EXPLAIN.

## Mätt mot prod-DB 2026-08-11

Den omskrivna queryn (UNCOMPRESS flyttad till ytterste SELECT, se
`texttv_helper.php`) kördes mot prod sida vid sida med den gamla, för
2026-08-09, fyra gånger.

**Korrekthet: bevisad.** 4 av 4 körningar gav **byte-identiskt** resultat —
samma 50 rader, samma ordning, samma uppackade sidinnehåll. Även med
`MIN(pa.created_at)` i stället för den tidigare icke-aggregerade
`pa.created_at` i ORDER BY.

**Prestanda: inte bevisad.** Spridningen är större än skillnaden:

| Körning | Gammal | Ny |
| ------- | ------ | -- |
| 1 | 2 724 ms | 1 744 ms |
| 2 | 3 494 ms | **5 817 ms** |
| 3 | 7 878 ms | 4 975 ms |
| 4 | 7 326 ms | 4 610 ms |

I körning 2 var den nya långsammare. Servern kör produktion samtidigt på
2 vCPU, så en ren A/B-mätning går inte att få här. Påstå inte att omskrivningen
är snabbare utan bättre underlag.

### Två saker mätningen faktiskt fastslog

1. **Index är inte problemet.** `page_actions` har redan både
   `idx_created_at` och det sammansatta `idx_created_at_type`. Den hypotesen
   är stängd.
2. **Queryn tar 3–8 sekunder under verklig last.** Det är väsentligt värre än
   p95 1,76 s som mättes via nginx — den siffran dominerades av cache-träffar.
   Varje *regenerering* är alltså flersekundersarbete som binder en av fem
   php-fpm-workers.

Tabellen är 2,7 miljoner rader (137 MB data + 97 MB index) och växer med
~250–340 k rader per dygn. Att aggregera ett helt dygn kräver att ~300 k rader
joinas mot `texttv` och grupperas — det är inneboende tungt oavsett
UNCOMPRESS.

**Slutsats: TTL-höjningen är den säkra vinsten, inte SQL-omskrivningen.**
Att köra en 3–8-sekundersquery mer sällan hjälper garanterat; att göra den
möjligen 20 % snabbare gör det inte.

## Föreslagen åtgärd

1. **Mät först.** `EXPLAIN` på queryn (den ligger redan förberedd i koden —
   det finns en utkommenterad `#EXPLAIN` på rad ~161). Kontrollera index på
   `texttv_stats.page_actions`.
2. **Flytta `UNCOMPRESS()` utanför.** Kör aggregeringen först, hämta de 50
   vinnande id:na, och slå upp innehållet i ett andra anrop. Det tar bort
   dekompressionen från alla rader som ändå kastas.
3. **Höj cache-TTL för just `/api/most_read`.** "Mest lästa senaste timmen"
   behöver inte 4 sekunders färskhet — 60 s vore rimligt och skulle minska
   regenereringarna med ~15x.

Punkt 3 är billigast och kan göras utan att röra SQL:en.

## Varning: `/api/*` konsumeras av apparna

`api.php` servar iOS- och Android-apparna (se `CLAUDE.md`). Ändringar i
svarsformat, statuskoder eller `Cache-Control` kan **tyst gå sönder för redan
installerade appversioner** som användarna inte kan uppgradera bort från.
Punkt 2 och 3 ändrar inte svarets form — men verifiera mot Bruno-collectionen
(`Bruno API files/`) innan deploy.

## Relaterat

- Todo [#12](../12-php-fpm-poolen-slar-i-taket.md) — poolmättnaden. Detta är
  orsaken; #12 är symptomet.
- Skillen [`prod-health`](../../.claude/skills/prod-health/SKILL.md) — subkommandot
  `fpm` beskriver hur slowloggen läses.
