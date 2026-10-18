**Status:** aktiv
**Senast uppdaterad:** 2026-10-18

# Todo #19 — Efter designbytet: annons-CLS på 377, 404:or och småsaker

Fynd från granskningen av live-sajten 2026-10-18 (UX, art director, copy,
SEO), efter designbytet i [#18](done/18-text-tv-designsystem.md). De fel som
designbytet självt orsakade rättades samma dag (`88b5d0b`): namnsökningen
som klipptes av `maxlength="3"`, Esc i sökfältet, Dela-raden med två sidor i
bredd, "0" i Mest lästa på `/nyheter/igar` och en hopplänk till "Gå till
sida". Det som står här fanns redan före designbytet eller kräver beslut.

Skärmdumpar från granskningen:
`/private/tmp/claude-501/-Users-bonnymacmini-Projects-TextTV-nu/18f1933d-f01e-4a31-8c0c-a94fceb2a0ff/scratchpad/review-live/`
(scratchpad, försvinner; ta om vid behov).

## Hög

### 1. Toppannonsen på 377 trycker ner resultaten (CLS 0,39)

`.ad--beforeMainText` (`views/pages_current_page_top.php`, CSS i
`css/styles.css`) ligger ovanför h1 utan reserverad höjd. När annonsen
laddas växer den till cirka 408 px och h1 flyttas från y=161 till y=564.
Uppmätt CLS: **0,39 på mobil 390 px, 0,14 på 1280 px** (gränsen för "dåligt"
är 0,25). På mobil syns inga resultat alls på första skärmen. `/101` och
startsidan ligger på 0,01–0,03; arkivsidorna reserverar redan höjd.

Rutan har funnits sedan 2021, ändrad 2025-03-09 (`47eab85`). /377 har 63,6 %
av klicken från Google.

Alternativ:

- **Fast minhöjd** (till exempel 300 px på mobil). Tar bort CLS, men
  resultaten börjar längre ner även när annonsen är liten eller tom.
- **Flytta annonsen under resultattabellen** på /377. Bäst för besökaren,
  men annonsen syns mindre; påverkar RPM.

Beslut för Pär (intäkter). Mät före och efter: CLS i GSC Core Web Vitals och
AdSense RPM för /377. Jämför med uppföljningen 2026-10-25 i #18.

## Medel

### 2. Ogiltiga sidnummer svarar 200

- `/1000` svarar 200 med en tom sida och canonical till startsidan.
- `/999` svarar 200 med "ej i sändning sedan 2021", canonical till sig själv
  och `Article`-schema. Länken "999 →" pekar på sig själv.
- `/abc` ger däremot en riktig 404 med den nya mallen.

Förslag: riktig 404 för nummer utanför 100–899 (eller utanför de nummer SVT
använder), och `noindex` eller 410 för sidor som inte sänts på länge. Rör
routing i `controllers/sida.php`; kör `make test-api` (appen hämtar sidor
via `api.php`, som inte ska påverkas).

### 3. Startsidans rutnät på desktop (1280, 1600 px)

"103 Sidan ej i sändning" tar en egen ruta, så 104 hamnar förskjuten, 105
blir ensam och centrerad och 102 lämnar ett tomrum. Förslag: hoppa över
sidor som inte sänds på startsidan, eller fasta kolumner så att paren alltid
står bredvid varandra.

### 4. Små klickytor inne i Text-TV-sidorna (mobil)

Sidnummerlänkarna i sidorna är cirka 27×18 px (54 länkar på startsidan) och
brödsmulorna 18 px höga. Förslag: större träffyta med `::after` och negativ
inset, så att radhöjden inte ändras. Testa att två länkar på samma rad inte
överlappar.

### 5. Sidomenyn på desktop: 14 px mot sidornas 22 px

Valt i #18 för att "Målservice & resultat . 376-395" ska rymmas i 250 px.
Förslag: pröva 16 px och en bredare meny, eller kortare namn.

### 6. Meta description och canonical saknas

FAQ, Om, `/svt-text-tv` och `/blogg`. Pär skriver texterna (samma omgång som
namnregeln i #18, "Kvar för Pär").

## Låg

- Brödsmulan "Hem" länkar till `http://texttv.nu/`, inte https.
- Livesidorna har `Article`-schema för innehåll som ändras hela tiden;
  `WebPage` stämmer bättre.
- `theme-color` är fortfarande `#0066B5` (appens blå); sajten är svart med
  `#00f`. `color-scheme: dark` saknas.
- Etiketterna "Annons" och "Fler nyheter efter annonsen" står kvar när ingen
  annons visas (till exempel med annonsblockerare).
- Samma sak heter "Mest läst igår" (startsidan), "Mest läst nu"
  (sökförslagen) och "Mest lästa" (`/nyheter/igar`). Rubriken på
  `/nyheter/igar` har inte bandstilen.
- `/svt-text-tv` har h2 "Inrikes" tre gånger (101, 102, 103).
- Bloggens utdrag upprepar rubriken som första mening.
- `/100,300` har `noindex` (rätt), men ← och → visar 299 och 301.

## Inte fel

- Granskningen saknade en "egen sifferknappsats". Beslutet i #18 var
  `inputmode="numeric"`, som finns. Textsökning är svårare att nå på mobil
  med den, medvetet: nästan alla skriver siffror.
- Kontrasten klarar WCAG AA överallt (cyan på svart 10,5:1, grått 7,5:1, gult
  på blått 7,9:1).
