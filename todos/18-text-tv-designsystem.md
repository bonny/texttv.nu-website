**Status:** aktiv
**Senast uppdaterad:** 2026-10-17

# Todo #18 — Text-TV-designsystem: gör om det som inte känns Text TV

## Sammanfattning

"Mest läst igår" på startsidan (da92064, 2026-10-17) blev en bra förebild.
Det kändes som en del av Text TV och inte som en webbwidget. Resten av sajten är
skiktad i flera stilar från olika år: en Ionic-blå appram, en mörkblå footer i
Helvetica, informationssidor i systemfont och små komponenter med egen inline-CSS.

Förslag: ta fram ett litet designsystem byggt på Text-TV:s egna regler, med
färgtokens, typografi och ett fåtal återanvändbara komponenter. Gå sedan igenom
sajten yta för yta och flytta varje yta till systemet. Förenkla och modernisera
HTML och CSS på vägen.

## Bakgrund

### Varför "Mest läst igår" fungerade

Reglerna, så att de kan återanvändas:

1. **Text-TV:s palett, inga mellantoner.** Svart bakgrund. Blått (`#00f`)
   för rubrikband. Gult för länkar och handlingar. Cyan för metadata som tid,
   placering och sidnummer. Vitt för brödtext. Inga grå ramar, inga
   genomskinliga understrykningar.
2. **Rubrik = färgat band i full bredd**, som "SVT Text"-bandet på sidorna.
3. **Länkar är färgad, understruken text**, som sidnumren på Text TV. Inga
   knappar med ram. Klickytan hålls på 44 px med padding.
4. **Ett rutnät i tecken.** Monospace, `ch`-mått, 40 tecken bred kolumn,
   allt vänsterställt på samma axel.
5. **Kort text.** Rubriken bär sammanhanget, så länkarna behöver inte upprepa
   det ("Nyheter →" i stället för "Gårdagens nyheter →").

### Inventering 2026-10-17 (snabb, lokalt på 390 px bredd)

Skärmdumparna ligger i `TextTV.nu/.playwright-mcp/inv-*.jpg` (scratch, inte
i repot): startsidan, 101, en arkivsida, dagsarkivet 377, dagslistan
nyheter och Om-sidan.

**CSS och HTML idag**

- `css/styles.css` 1 322 rader, `texttvpage.css` 104, `app-landing.css` 441.
- Nio vyer har egna `<style>`-block:
  - `cookie_banner`, `delade`, `day_headlines`, `day_archive_link`
  - `not_broadcasted_notice`, `footer`, `pages_inner_output_archive`
  - `pages-thumbs`, `pages_day_archive`
- Sju vyer har `style=`-attribut.
- 64 olika färgvärden i CSS och vyer.
- Minst sex olika `font-family`-deklarationer: Ubuntu Mono, sans-serif,
  "Helvetica Neue", helvetica/verdana, arial, systemfont.
- Bara en CSS-variabel finns (`--font-system-ui`).

**Ytor som inte känns Text TV** (att bekräfta och komplettera i första
steget):

| Yta | Vad som skaver |
| --- | --- |
| Appramen (header, `Sidor`/`Hem`/`← 100`/`102 →`, "Gå till sida") | Ionic-blå `#0066B5`, systemfont, appikon. Ser ut som en app från 2015, inte som Text TV |
| Sidomenyn och footern ("Sidor"-listan) | Mörkblå `#141c2a`, Helvetica med vanlig understrykning, grå länkar, sidnummer i en annan font. Listan finns dessutom i både sidomenyn och footern |
| Raden under sidan ("Sidan uppdaterad 20:25" + "Dela"-knapp) | Liten grå text plus knapp med ikon och ram. Webbspråk |
| "FLER NYHETER EFTER ANNONSEN", "Senaste nyheterna", "Senaste sportnyheterna" | Versal sans-serif och fet Helvetica |
| Startsidan, rubriken överst ("SVT Text TV - Nyheter och Sportresultat") | Fet monospace utan band, centrerad, ovanför sida 100. Vanlig webbrubrik. Bandet "Igår på Text TV" längre ner sticker ut cirka 10 px till vänster om sidans kolumn och har inte samma kant |
| Arkivsidorna (`/111/forsvaret-skot-skarpt-med-patriot-908` med flera) | Grå gradientlist överst ("SVT Text TV 111 / Tors 15 okt 2026, 20:25") i ett helt annat formspråk. Centrerad etikett "Annons:" med tom yta. Att sidan är arkiverad står bara i en centrerad rad längst ner ("Du tittar på en arkiverad version av …"); något band eller en tydlig länk till den levande sidan överst finns inte. Listens datum (15 okt 20:25) och sidans egen rubrikrad (16 okt) skiljer sig lokalt, så kolla vilket datum som visas var |
| Dagsarkiven (`/377/igar`, `/377/<datum>`) | Samma rubrikmönster som dagslistorna: fet centrerad rubrik där datumet står två gånger, "Så här såg … ut"-texten centrerad. Annonsetiketten "ANNONS" i versal sans-serif. Sida 377 är för bred för 375 px: resultatraderna bryts och `(X-X,X-X,X-X)` hamnar på egen rad |
| Dagslistorna `/nyheter/<datum>`, `/sport/<datum>` | Monospace, men stor fet rubrik utan band, datumet står två gånger, föregående/nästa dag bryts ojämnt. Listan är nära Text TV redan; band och rutnät saknas |
| Informationssidorna `/sida/*` (Om, Vanliga frågor, Cookies, Integritetspolicy), bloggen | Ren webbsida i systemfont med punktlistor. Ingen koppling till Text TV alls |
| Övrigt att granska | Cookiebannern, "ej sänd"-notisen, arkivsidornas topp, `/delade`, `/polisen`, `/svt-text-tv`, `/text-tv-fakta`, 404 (redan i Text-TV-stil sedan #16, kan bli förebild) |

## Förslag

### 1. Grund: tokens och regler

Samla allt i en ny fil, eller överst i `styles.css`, som CSS custom properties:

- **Färger:** de åtta Text-TV-färgerna (`--tt-black`, `--tt-blue`, `--tt-yellow`,
  `--tt-cyan`, `--tt-white`, `--tt-red`, `--tt-green`, `--tt-magenta`), samma
  värden som `texttvpage.css` använder. Lägg till högst en neutral färg för
  sådant som Text TV inte har, till exempel annonsetiketter. Inget annat.
- **Typografi:** Ubuntu Mono överallt. En storlek för brödtext och en för
  dubbelhöga rubriker, som på Text TV. Ingen sans-serif.
- **Mått:** `ch` och radhöjd som rutnät. Kolumn `40ch`. Avstånd i hela rader
  (`1lh` eller `1.25em`).
- **Interaktion:** hover och fokus = gul bakgrund med svart text, samma på alla
  länkar. Synlig fokusring.

### 2. Komponenter (små, återanvändbara klasser)

- **`tt-band`**: rubrikband i full bredd, blått med vit eller gul text.
  Ersätter alla rubriker i sajtens ram.
- **`tt-link`**: gul, understruken länktext med 44 px klickyta. Ersätter
  knappar med ram ("Dela", gårdagslänkarna med flera).
- **`tt-list`**: lista med en metadatakolumn (tid, placering eller sidnummer i
  cyan) och hängande indrag. Används av "Mest läst igår", dagslistorna och
  "Senaste nyheterna".
- **`tt-linkrow`**: rad med länkar som bryts snyggt ("Nyheter → Sport →
  Resultat 377 →", föregående/nästa dag).
- **`tt-page`**: textspalt för informationssidor, med `h1`/`h2` som band och
  listor med Text-TV-tecken i stället för punkter.

Bygg om `day_archive_link.php` först, som referens, och ta bort dess egna
`<style>`.

### 3. Gå igenom ytorna en i taget

Ett förslag till ordning, där det som syns mest kommer först:

1. Raden under sidan, "Senaste nyheterna/sportnyheterna" och
   annonsetiketterna: syns på varje sidvisning.
2. Arkivsidorna: ett band överst som säger att sidan är arkiverad och
   när, med länk till den levande sidan. De får mycket söktrafik (#16).
3. Appramen och navigeringen, och startsidans rubrik.
4. Footern och sidomenyn. Gör en lista av två, så att den finns på ett ställe.
5. Dagslistorna och dagsarkiven (#16). En gemensam rubrik: band med
   avdelning och datum en gång, föregående/nästa dag som länkrad.
6. Informationssidorna och bloggen.
7. Resten: cookiebannern, notiser, `/delade`, `/polisen` och fakta.

För varje yta: skärmdump före och efter på 390 px och desktop, flytta
inline-CSS till komponenterna och ta bort CSS som blir oanvänd.

### 4. Förenkla HTML och CSS

- Semantisk HTML: `nav`, `main`, `header`, `ol` och rubriknivåer i rätt
  ordning.
- Ta bort CSS för saker som inte finns längre. Mät `styles.css` före och
  efter.
- Inget byggsteg behövs: vanlig CSS med custom properties, nesting och
  `:is()` räcker i de webbläsare som besöker sajten idag (kolla GA4).
- Inga `<style>`-block i vyerna när det är klart.

## Risker

- **Annonserna (AdSense).** Annonsplatser och "efter annonsen"-texten styrs av
  villkor och kan ha krav. En ändrad layout kan flytta annonser och påverka
  intäkterna. Mät RPM i AdSense före och efter de ytor som rör annonserna.
- **CLS och prestanda.** Webbfonten används redan, men fler element i Ubuntu
  Mono betyder att FOUT syns mer. Kolla CWV i GSC efteråt.
- **Appen.** `appembed/` och API:t används av appen. Rör inte API:t. Kolla
  `appembed`-vyerna om de delar CSS med webben.
- **Läsbarhet för långa texter.** Monospace på informationssidor och i bloggen
  är svårare att läsa i långa stycken. Prova på en sida innan resten görs om.
- **Kontrast.** Gult på vitt eller blått på svart har för låg kontrast. Bara
  kombinationer som klarar WCAG AA.
- **Stort på en gång.** Gör en yta per commit. Varje push till main deployar
  direkt utan staging.

## Confidence

medel — Riktningen är tydlig och "Mest läst igår" visar att den fungerar.
Inventeringen ovan bygger dock på fyra skärmdumpar och en grep, och appramen
och footern kan ha beroenden (JS för sidomenyn, AdSense) som inte är kartlagda.
