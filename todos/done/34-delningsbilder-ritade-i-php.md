**Status:** klar 2026-11-09 — Delningsbilder i PHP deployade 28 okt; städning efter mtime och limit_req 2/s för /api/screenshot fungerar (uppföljd 9 nov: 0 st 429 sedan 2 nov)
**Senast uppdaterad:** 2026-11-09

# Todo #34 — Delningsbilder ritade i PHP i stället för skärmdump

## Sammanfattning

Delningsbilderna (`/api/screenshot/<id>.jpg`) skapas i dag av
`wkhtmltoimage`, som hämtar arkivsidan över HTTP från samma server och
skärmdumpar den. `wkhtmltopdf` finns inte i Debian 13, så något måste
ersätta det före OS-bytet (#17). Rita bilden direkt i PHP med GD från sidans
innehåll i databasen: en text-tv-sida är ett fast rutnät med 40 × 24 tecken
och få färger, så ingen webbläsare eller layout behövs. Pär valde detta
2026-10-28 framför headless Chromium.

## Bakgrund

- Bilderna är sajtens `og:image` och JSON-LD-`image`
  (`views/header.php:596` och `:753`), så de hämtas mest av botar. 2026-10-27
  och 28 till 17:20: ~4 850 anrop (Googlebot ~1 600, PerplexityBot ~1 000,
  Bingbot ~260, Facebook ~165), 254 svar 503 från låset. 650–1 300 nya
  bilder per dygn.
- Jämfört (samma sida, bookworm-container):

    | Sätt                        | Tid per bild | Minne      | Fil          |
    | --------------------------- | ------------ | ---------- | ------------ |
    | wkhtmltoimage (i dag)       | ~1 s         | ~120 MB    | JPEG 164 kB  |
    | Chromium headless           | ~2,6 s       | 250–400 MB | PNG 58 kB    |
    | wkhtmltox (projektets .deb) | ~1 s         |            | renderar fel |
    | GD i PHP (uppskattat)       | 20–50 ms     | några MB   | PNG ~20 kB   |

- Prod har GD med FreeType, PNG och JPEG. Sajtens typsnitt finns som TTF:
  `texttv.nu/fonts/ubuntumono-r-webfont.ttf` och `-b-` (Ubuntu Mono).
- Lokala testbilder från 2026-10-28 (dagens, wkhtmltox, Chromium) ligger i
  scratchpad, inte i repot.

## Prototyp och granskning 2026-10-28

En granskare (subagent) byggde en prototyp i GD:
[`34-underlag/prototyp-render.php`](../34-underlag/prototyp-render.php),
jämförelse i
[`34-underlag/jamforelse-idag-mot-prototyp.jpg`](../34-underlag/jamforelse-idag-mot-prototyp.jpg)
(dagens bild och prototypen för sida 100 i dag och sida 377 från 2019).
Ritningen tar 10–24 ms och 16 MB; PNG med 64-färgspalett 17 kB, JPEG 85
101 kB, mot dagens 164 kB.

**Vad renderaren får:** `page_content` är serialiserad HTML, en
`<div class="root">` per undersida. Importerns format sedan 2021 har
`span.line` med färgklasser per span (`bgB`, `Y` …), dubbelhöjd som
`class="line DH"` (raden under är tom). **Blockgrafiken är inga
mosaiktecken** utan `<span class="bgImg"
style="background:url(https://l.texttv.nu/storage/chars/<crc32>.gif)">`,
13 × 16 px GIF:ar klippta ur SVT:s sidbild. De ligger lokalt på prod i
`importer/storage/app/public/chars/` (2 481 filer, 9,8 MB) och läggs in
skalade med `imagecopyresized`, pixelrätt. Dubbelhöga rader ritas på en
tillfällig rad och sträcks med `imagecopyresampled`, sist, så att de täcker
raden under som `scaleY(2)` i CSS.

## Förslag

1. **Renderare** (utgå från prototypen) som går igenom hela DOM-trädet och
   byter rad vid `\n`, så att även **arkivets gamla format** (före 2021: ingen
   `span.line`, `<h1>` i toppraden, text utan span, sidor utan sidbild)
   ritas rätt. Dubbelhöjd bara för `span.line.DH`; gamla `h1.DH` ritas med
   fet font (de visades aldrig dubbelhöga). Toppraden är `#ddd`.
   Undersidor (flera `root` per id) staplas; höjden är summan av raderna.
2. **Adresser:** tolka med regex `^([\d,]+)(?:\.(png|jpg))?$`. `.png` ger PNG
   (palett), `.jpg` **och utan ändelse** JPEG; `api/share` och
   `screenshotCurrent` i gamla appar ger adressen utan ändelse. Kontrollera
   att `.png` når PHP (i dag tar `str_replace(".jpg")` bara bort `.jpg`, och
   `.png` svarar med HTML).
3. **Kontrollera sid-id och sätt tak:** `load(true)` per id, 404 utan att
   skriva fil om någon saknas (i dag sparas 404-sidan som bild för alltid),
   högst 6 sidor och 12 undersidor per bild.
4. **Cache-headers:** `Cache-Control: public, max-age=31536000, immutable`
   och `Last-Modified`; ett arkiv-id ändras aldrig.
5. **Servera befintliga filer utan att läsa dem i PHP:** `X-Accel-Redirect:
/shares/<fil>` (nginx `if (!-e) rewrite` gör att en egen location för
   `/api/screenshot` aldrig nås). Nya filnamn `shares/v2/<id>.png|jpg` för en
   sida och md5 för flera; `v2` gör att allt kan ritas om genom att byta
   version.
6. **Atomär skrivning:** temporär fil + `rename()`. Ta bort flock/503 (93
   svar 503 per dygn i dag, som botar kan spara som trasig bild); skydda
   med taket i punkt 3 och gärna `limit_req` för `/api/screenshot`.
7. Ta bort debug-headers (`X-texttv-cmd_wkhtml`, `memory_*`; visar
   sökvägar).
8. GIF-katalogen i config, monterad i dev-stacken; saknas en GIF ritas bara
   bakgrundsfärgen.
9. **Typsnitt:** webfonten täcker bara Latin-1 och några tecken (ł, č, ć, ş,
   ğ, Š blir rutor). Använd fullständiga Ubuntu Mono från `fonts-ubuntu` på
   servern.
10. **og:image:** byt till `.png` i `views/header.php` (og:image och JSON-LD),
    lägg till `og:image:width/height/type`. Överväg en liggande variant 1 200
    × 630 för `og:image` (Facebook/LinkedIn beskär stående bilder till
    1,91:1, Discover vill ha ≥ 1 200 px) och `summary_large_image`; behåll
    hela sidan för appdelning.
11. **Jämför** ett urval (100, 377 från 2019, tabeller, flersidiga delningar,
    sidor utan sidbild) mot dagens bilder, och testa Facebook Sharing
    Debugger, Slack och iMessage, innan `wkhtmltoimage` tas bort.
12. `shares/` växer utan städning (22 162 filer, 4,2 GB sedan 7 oktober; cron
    tar bara `*.gif`). Lägg till städning, eller låt v2-filerna leva och
    städa gamla jpg.

Rubriken "Delad från TextTV.NU / sidorna X / datum" för flersidiga delningar
används bara av gamla appar och kan bli en enkel textrad.

**Tidsuppskattning:** 1,5–2 dagar (renderare 0,5, controller 0,5,
jämförelse och delningstester 0,5, nginx och driftsättning några timmar).

## Byggt 2026-10-28

- `libraries/Share_image.php`: renderaren, fristående från CodeIgniter.
  Fullständiga Ubuntu Mono (UFL) i `third_party/ubuntu-mono/`, så inget
  `fonts-ubuntu` behövs på servern.
- `Api::screenshot()`: regex för adressen, högst 6 id, 404 (text, 10 min
  cache) för okända id utan att spara något, högst 12 undersidor,
  `shares/v2/<id>.png|jpg` (md5 för flera id) med temporär fil + `rename()`,
  `Cache-Control: public, max-age=31536000, immutable`, `Last-Modified`.
  Lås, 503 och debug-headers borta.
- **Avvikelse från förslaget:** PHP läser filen själv i stället för
  `X-Accel-Redirect`. Statiska locationen i nginx sätter 7 dagars cache och
  skulle behöva en egen location för `shares/v2`; filerna är 20–140 kB och
  läsningen tar några ms. `X-Accel-Expires: 60` hindrar att nginx
  fastcgi_cache fylls med bilder enligt årscachen.
- `og:image` → `.png` med `og:image:type`; JSON-LD:s felaktiga 800 × 800
  borttaget (bilden är 672 px bred och höjden varierar).
- Dev: `gd` i webbimagen, importerns `chars/` monterad på prods sökväg.
- Mätt lokalt: PNG 18–27 kB och 35–50 ms per sida, cachad ~7 ms; värsta
  fallet 12 undersidor 467 ms, 254 kB.
- Deployad 2026-10-28 (`11646b9`); Pär testade delning i Facebook, Slack
  och iMessage samma kväll, OK.
- Kvar: liggande variant (punkt 10), städning av `shares/` (punkt 12),
  `limit_req`, ta bort `wkhtmltopdf`-paketet efter några dagar.

## Kontroll 2026-11-01 (uppföljningen från 11-04, gjord tidigt)

- **`shares/` 5,2 GB:** 22 339 gamla wkhtmltoimage-JPEG (7–28 okt) och
  `v2/` 998 MB med 10 966 filer på fyra dagar (PNG 5 078, snitt 29 kB;
  JPEG 5 888, snitt 145 kB). Disk 19 av 75 GB.
- **Den gamla städningen tar aldrig något.** Cron 01:45 kör
  `/root/texttv-tools/remove-old-share-images.js`, som tar bort bilder
  äldre än 7 dagar efter **ctime**. Deployens `chown -R www-data shares`
  (`deploy-website.yml:97`) sätter ny ctime på alla filer vid varje
  deploy, så loggen säger "Found 0 images to remove" (senast 01:45 i dag;
  ctime på en fil från 18 okt är 16:25 i dag). Skriptet går inte heller in
  i `v2/`.
- **Ingen läser de gamla filerna.** Koden läser bara `shares/v2/`, och
  access.log har noll `GET /shares/` på 8 dygn.
- **Toppen i dag är NVIDIA:s crawler** (`NVIDIABOT`, en IP, 3.231.27.0):
  3 865 anrop mellan 03:20 och 03:29 (~7 per sekund), alla gamla
  `.jpg`-adresser, 4 122 unika id. Den gav 4 402 nya filer och 545 MB i
  dag. Inte sedd tidigare under de 7 dygn loggen räcker. Svarstid för
  `/api/screenshot` i dag p50 0,10 s, p99 0,40 s, max 1,6 s; inga fel i
  nginx error.log, 88 svar 404.
- Utan städning och med fler sådana besök: cirka 0,2–0,5 GB per dygn.

**Förslag (prod, kräver ok):**

1. Byt cron-raden mot `find` på mtime, för båda katalogerna:
    ```
    45 1 * * * find /usr/share/nginx/texttv.nu/shares -type f \( -name '*.jpg' -o -name '*.png' \) -mtime +7 -delete; find /usr/share/nginx/texttv.nu/shares/v2 -name '*.tmp' -mmin +60 -delete
    ```
    De gamla JPEG:erna försvinner inom en vecka (eller direkt med en
    engångskörning). En v2-bild som tas bort ritas om på 50 ms nästa gång;
    bara `Last-Modified` ändras.
2. `limit_req` för `/api/screenshot` per IP i nginx (`map $request_uri`
   ger tom nyckel för allt annat, eftersom `if (!-e) rewrite` gör att en
   egen location aldrig nås), t.ex. 2 per sekund, burst 20, svar 429.
   NVIDIA-toppen hade fått 429 i stället för 3 800 nya filer.
3. Dokumentera städningen i `server.md` (cron finns bara på servern).

**Gjort 2026-11-01 (Pär gav ok):**

- Crontab: node-skriptet ersatt med `find` på mtime enligt punkt 1, och de
  bortkommenterade mysql-raderna med root-lösenordet i klartext borttagna.
  Backup `/root/crontab.bak-20261101-34` (utan lösenordsraderna).
- De 22 339 gamla JPEG:erna i `shares/` raderade direkt: 5,2 GB → 1,0 GB,
  disken 19 → 15 GB.
- nginx: `/etc/nginx/conf.d/screenshot-limit.conf` (map + zon) och
  `limit_req zone=screenshot burst=20 nodelay; limit_req_status 429;` i
  `location ~ \.php$`. `nginx -t` ok, reload. Test: 30 samtidiga anrop mot
  en delningsbild gav 22 × 200 och 8 × 429, 30 mot `/api/get/100` gav
  30 × 200. Backup av vhosten `/root/nginx-texttv.nu.bak-20261101-34`
  (innehåller DB-lösenordet, raderas med de andra 2026-11-07).
- `server.md` uppdaterad (cron, spärren, deployens rättigheter).
- Efter kodgranskningen samma dag: `map` på `screenshot` var som helst i
  adressen, oavsett skiftläge (`/index.php/api/screenshot`, `/api//screenshot`
  och `/api/Screenshot` gav bild utan spärr), och länkförhandsvisningar
  (Facebook/iMessage, Slack, Twitter, LinkedIn, WhatsApp m.fl.) undantagna
  med en `map` på user agent. Test, 30 samtidiga anrop per variant: alla
  adressvarianter 21–22 svar + 8–9 × 429; `facebookexternalhit … Facebot
Twitterbot` och `Slackbot` 30 × 200; `/api/get/100` 30 × 200. Backup
  `/root/screenshot-limit.conf.bak-20261101-34`.
- `wkhtmltopdf` är redan borta (Debian 13).

**Liggande 1200 × 630 struken 2026-11-01** (Pär). Den skulle bara användas
av länkförhandsvisningar (`og:image`, `summary_large_image`) och Google
Discover; appen delar hela sidan. Webben har cirka 2,6 delningar per dag
(#24), och en andra variant av varje bild ger dubbelt så många filer i
`shares/v2` och fler anrop från botarna. Den stående bilden beskärs i
Facebook till överkanten, där sidhuvudet och de första raderna står.

Uppföljning 2026-11-02: nya cron-raden körde 01:45 (första natten). `shares/`
12 370 filer, 1,1 GB, inga äldre än 7 dagar, inga `.tmp`. Äldsta i `v2` är från
2026-10-28 19:17, så inget har raderats än; uppföljningen flyttad till 2026-11-06.
429 för `/api/screenshot`: 43 av 7 023 anrop 2026-11-01, alla `curl` från en IP
16:57–17:15 (testet av spärren), 0 i dag. Inga sociala crawlers.

## Risker

- Utseendet måste matcha sajten; blockgrafik och dubbelhöjd är det svåra.
- Ubuntu Mono-licensen (Ubuntu Font Licence) tillåter användning och
  inbäddning; kontrollera ändå.
- ~~Blockerar #17~~: löst, renderaren deployades 2026-10-28, före OS-bytet
  2026-10-31.

## Confidence

medel–hög — tekniken är enkel och snabb; arbetet ligger i att få bilden
lik dagens.
