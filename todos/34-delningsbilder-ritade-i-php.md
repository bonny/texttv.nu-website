**Status:** aktiv — byggd lokalt 2026-10-28, väntar på deploy och jämförelse på prod
**Senast uppdaterad:** 2026-10-28

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
[`34-underlag/prototyp-render.php`](34-underlag/prototyp-render.php),
jämförelse i
[`34-underlag/jamforelse-idag-mot-prototyp.jpg`](34-underlag/jamforelse-idag-mot-prototyp.jpg)
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
- Kvar: jämförelse mot dagens bilder på prod, Facebook/Slack/iMessage,
  liggande variant (punkt 10), städning av `shares/` (punkt 12),
  `limit_req`, ta bort `wkhtmltopdf`-paketet efter några dagar.

## Risker

- Utseendet måste matcha sajten; blockgrafik och dubbelhöjd är det svåra.
- Ubuntu Mono-licensen (Ubuntu Font Licence) tillåter användning och
  inbäddning; kontrollera ändå.
- Blockerar #17: måste vara klart före OS-bytet, annars slutar nya
  delningsbilder fungera.

## Confidence

medel–hög — tekniken är enkel och snabb; arbetet ligger i att få bilden
lik dagens.
