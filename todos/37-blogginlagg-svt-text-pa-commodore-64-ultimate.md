**Status:** aktiv — publicerat 2026-11-07 14:13 (id 83, /blogg/svt-text-pa-commodore-64), med Franks nya foto. Pär: feedback på texten, skicka URL:en till Frank
**Senast uppdaterad:** 2026-11-07

# Todo #37 — Blogginlägg: SVT Text på Commodore 64 Ultimate

## Sammanfattning

Frank Putman (Nederländerna) bygger Teletext64U, ett text-tv-program för nya
Commodore 64 Ultimate, och har lagt till SVT Text. Programmet hämtar sidorna
från texttv.nu: han parsar HTML:en från `https://l.texttv.nu/db` och har
rättat en del sidor i koden. Han frågade efter ett sätt att hämta den "råa"
text-tv-sidan och hur filnamnen på mosaiktecknens `.gif`-filer är kodade.

Ett kort inlägg på /blogg för besökarna: någon kör SVT Text via texttv.nu på
en C64, med fotot. Tonen som i tidigare inlägg (#23, #26).

## Underlag

- Mejltråden i Gmail: "SVT Text on the Commodore 64 Ultimate" (första mejlet
  16 apr 2026, 11 meddelanden).
- Projektet: <https://github.com/fputmango/Teletext64U>
- Två foton att välja mellan, båda i tråden: `IMG_0127.jpeg` (första mejlet,
  Dell-skärm med SVT Text 100 och C64-tangentbordet) och `IMG_1736.jpeg`
  (nytt foto 2026-11-07).
- Tillstånd: Frank svarade 2026-11-07 "No problem" på frågan om inlägget och
  fotot.

## Publicera

Utkastet står i [`37-blogginlagg/inlagg.sql`](37-blogginlagg/inlagg.sql) som
HTML (permalänk `svt-text-pa-commodore-64`).

- **Publicerat 2026-11-07 14:13** av Claude på Pärs uppmaning: backup
  `/root/db-backups-2026-11-07/texttv_blogg.sql` (95 kB, "Dump completed"),
  sedan `infoga.sh` (id 83). Sidan svarar 200 och står i /blogg.
- Bilden: `IMG_1736.jpeg` från Franks mejl 7 nov (det foto han ville ha),
  roterad, nedskalad till 1200×1600 och utan EXIF (originalet hade GPS).
  Deployad i `56e810f`.
- Skripten loggar in som root via socket. Importerns konto (som #26 använde)
  når inte `texttv_blogg` sedan #30, så första backupförsöket avbröts med
  `Couldn't find table`.
- Ändra texten i efterhand: ändra `inlagg.sql` och `infoga.sh`, generera om SQL:en i `uppdatera.sh` och kör `ssh texttv.nu 'bash -s' < todos/37-blogginlagg/uppdatera.sh` (tar egen backup). Länkarna lades in så 2026-11-07 14:16.
  (backup först), och ändra `inlagg.sql` så att den stämmer.

Kvar:

1. Pär: feedback på texten.
2. Pär: skicka `https://texttv.nu/blogg/svt-text-pa-commodore-64` till Frank.

**Rubrik:** SVT Text på en Commodore 64

**Alternativa rubriker:** "Text-tv på en C64, via TextTV.nu" ·
"SVT Text 100 på en hemdator från 80-talet"

## Bakgrund att nämna (eller inte)

- Flytten till Debian 13 (2026-10-31) bytte filnamnen på mosaiktecknens
  gif-filer, så Teletext64U tappade grafiken. Fixat 2026-11-07 (`98aef86`):
  filnamnen är desamma som före trixie igen.

## Öppet

- Ska inlägget nämna API:et (`api/get`) som alternativ till att parsa
  HTML:en, och att filnamnen nu är stabila?
