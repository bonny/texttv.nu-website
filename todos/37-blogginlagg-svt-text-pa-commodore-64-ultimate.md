**Status:** aktiv — utkast klart 2026-11-07 (todos/37-blogginlagg/). Pär: läs texten, välj foto (reserv ur skärmdumpen ligger i bloggimages), publicera och skicka URL:en till Frank
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
HTML (permalänk `svt-text-pa-commodore-64`). Provkört lokalt 2026-11-07:
renderas på `/blogg/svt-text-pa-commodore-64`, två körningar ger en rad.

1. Pär: läs texten. Stycket "Själv hade jag en C64 med en 1581…" är i jag-form,
   resten i vi-form som tidigare inlägg. Stryk eller skriv om om det skaver.
2. Pär: välj foto. `texttv.nu/bloggimages/2026-svt-text-commodore-64.jpg`
   är just nu en reserv beskuren ur skärmdumpen av mejlet (846×1128,
   `IMG_0127`). Byt den mot originalet `IMG_0127.jpeg` eller `IMG_1736.jpeg`
   från Gmail-tråden, nedskalat till cirka 1000 px bredd, och rätta `width`/
   `height` i `inlagg.sql` och `infoga.sh` om proportionerna ändras.
3. Committa och pusha bilden (deploy-website triggas av `texttv.nu/**`) och
   kontrollera att `https://texttv.nu/bloggimages/2026-svt-text-commodore-64.jpg`
   svarar 200.
4. Från repo-roten:

    ```bash
    ssh texttv.nu 'bash -s' < todos/37-blogginlagg/backup.sh   # bloggtabellen till /root/db-backups-YYYY-MM-DD/
    ssh texttv.nu 'bash -s' < todos/37-blogginlagg/infoga.sh   # lägger in inlägget (avbryter utan dagens backup)
    ```

5. Pär: skicka `https://texttv.nu/blogg/svt-text-pa-commodore-64` till Frank.

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
