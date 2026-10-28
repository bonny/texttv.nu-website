**Status:** aktiv
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

## Förslag

1. Ny klass/hjälpare som tar sidans innehåll (samma data som arkivsidan
   använder) och ritar med `imagettftext` per tecken på rutnätet:
   - färgklasserna (text-tv:s åtta färger, förgrund och bakgrund),
   - dubbelhög text,
   - blockgrafiken (mosaiktecknen i loggor och rubriker; ritas som
     rektanglar per cell, som statussidan gör med CSS-celler),
   - understrukna länkar (sidnummer),
   - sidhuvud ("100 SVT Text <datum>") och sidfot med TextTV.nu-loggan.
2. Flera sidor i samma delning: höjden blir antal sidor × sidhöjd.
3. `api.php` `screenshot()`: rita i stället för `exec`. Låset kan troligen
   tas bort eller bli kortare (inget HTTP-anrop till sig själv, ingen extra
   php-fpm-worker).
4. **Format:** PNG med palett är skarpast och minst (~1/8 av dagens JPEG).
   Adresserna slutar på `.jpg` och står i utskickade appar och delade länkar.
   Testa hur Facebook, iMessage, Slack och Google hanterar PNG med
   `Content-Type: image/png` på en `.jpg`-adress. Fungerar det: behåll
   adressen. Annars JPEG.
5. Jämför nya bilder mot dagens för ett urval sidor (nyheter, sport,
   börs/tabeller, sidor med blockgrafik, flersidiga delningar) innan
   `wkhtmltoimage` tas bort.
6. Gamla bilder i `shares/` ligger kvar; bara nya ritas i PHP.

## Risker

- Utseendet måste matcha sajten; blockgrafik och dubbelhöjd är det svåra.
- Ubuntu Mono-licensen (Ubuntu Font Licence) tillåter användning och
  inbäddning; kontrollera ändå.
- Blockerar #17: måste vara klart före OS-bytet, annars slutar nya
  delningsbilder fungera.

## Confidence

medel–hög — tekniken är enkel och snabb; arbetet ligger i att få bilden
lik dagens.
