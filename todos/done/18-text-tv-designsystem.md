**Status:** klar 2026-10-18
**Senast uppdaterad:** 2026-10-18

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

1. **Text-TV:s palett, inga mellantoner.** Svart bakgrund. Blått (`#00f`,
   samma som `.bgB` i `texttvpage.css`) för rubrikband. Blå *text* på
   Text-TV-sidorna är en annan färg (`#12c`, `.B`) och har för låg kontrast
   mot svart (cirka 2:1) för att användas i ramen. Gult för länkar och handlingar. Cyan för metadata som tid,
   placering och sidnummer. Vitt för brödtext. Inga grå ramar, inga
   genomskinliga understrykningar.
2. **Rubrik = färgat band i full bredd**, som "SVT Text"-bandet på sidorna.
3. **Länkar är färgad, understruken text**, som sidnumren på Text TV. Inga
   knappar med ram. Klickytan hålls på 44 px med padding.
4. **Ett rutnät i tecken.** Monospace, `ch`-mått, 40 tecken bred kolumn,
   allt vänsterställt på samma axel.
5. **Text-TV-sidan får alltid plats i bredd.** De 40 tecknen ska rymmas på
   alla mobiler, från 320 px och uppåt, utan radbrytning eller horisontell
   scroll. Allt runt sidan (band, listor, länkrader) följer sidans bredd och
   aldrig tvärtom. Krav från Pär 2026-10-17.
6. **Kort text.** Rubriken bär sammanhanget, så länkarna behöver inte upprepa
   det ("Nyheter →" i stället för "Gårdagens nyheter →").
7. **Innehållet före navigeringen.** Rubrikerna (värdet) ska synas mer än
   länkarna vidare. Förut drog tre gula knappar med ram blicken från de tre
   nyheterna.
8. **Rubriken säger vad listan är.** "Igår på Text TV:" över en lista sorterad
   på läsning såg ut som en kronologisk lista (22:54, 12:12, 06:16 föll av en
   slump). Nu "Mest läst igår" med placering 1–3. Finns ingen lista faller
   rubriken tillbaka till "Igår på Text TV", så att den inte lovar något som
   inte visas.
9. **Luft grupperar.** Mer avstånd mellan grupper än inom dem.
10. **Rubriklistor utan understrykning.** Vit text, gul vid hover och fokus.
    Grå, halvgenomskinliga understrykningar blev dubbla när rubriken bröts.
    (Krockar med hoverregeln i förslaget, se "Öppna beslut".)

### Granskning av planen 2026-10-17 (art director, UX, copy, SEO)

Planen granskades mot koden innan arbetet började. Slutsatserna är inarbetade
i avsnitten nedan. Två fel i den första versionen är rättade: bandets blå
(regel 1) och varför sida 377 bryts (förslag 1).

**Skisser:** designytan https://claude.ai/artifact/2zAjcXdysWXbMpe4cVbTwn
(privat) har grunden, besluten med alternativ och mobilskisser av
startsidan, sökfältet (klickbart), en arkivsida, en dagslista och en
informationssida.

**Beslut** (tagna av Pär 2026-10-17, alla enligt förslaget i designytan):

1. Hover: inverterat överallt, gul bakgrund och svart text.
2. Svart ram på webben. Appen kan följa efter senare.
3. Desktop: flera sidor i bredd på startsidan.
4. "Text TV" för tjänsten, "TextTV.nu" för sajten och appen, "Text-TV-" i
   sammansättningar.
5. Sökfältet: sifferknappsats på mobil.
6. Rubriklistor (`tt-list`) är täta: rader på 31 px, klickyta 25 px (WCAG
   minimum 24). 44 px gjorde "Senaste nyheterna" 54 % längre och gles.
   44 px gäller länkar och länkrader, inte rubriklistor. Beslut 2026-10-17
   efter jämförelse i `.playwright-mcp/18/radhojd/`.

Frågorna som de var:

1. **Hover.** Antingen gul bakgrund med svart text på alla länkar, eller
   färgbyte (vit → gul) på rubriklistor och understruken gul på textlänkar.
   Båda står i todon idag. Välj en regel.
2. **Appens blå.** `#0066B5` är `--ion-color-primary` i appen
   (`texttv.nu (app)/src/css/theme.css`) och finns troligen i appikonen. Blir
   webbens ram svart och blå skiljer sig webb och app åt. Okej, eller ska
   appen följa efter senare?
3. **Desktop och flera sidor i bredd.** Med 22 px som tak blir kolumnen cirka
   440 px på en 1280 px skärm. Vad ska den tomma ytan göra, och hur ser
   `/100,300` och `/101-103` ut?
4. **Hur namnet stavas.** Vyerna har fem stavningar: "Text TV" (164),
   "TextTV" (39), "text-tv" (21), "Texttv" (8), "Text-TV" (7). Förslag:
   "Text TV" för tjänsten, "TextTV.nu" för sajten, "Text-TV-" i
   sammansättningar.
5. **Sökfältet: siffror eller fritext.** Sifferknappsats och bara sidnummer
   på mobil, eller fritext med namnsökning överallt (se förslag 5).

### Gjort hittills

- **da92064 (2026-10-17), startsidans "Mest läst igår"**, efter en UX- och
  art director-granskning av "Igår på Text TV:" (grå centrerad etikett, tider,
  tre olika breda gula knappar med ram på olika axlar):
  - `views/day_archive_link.php`: blått band, placering i cyan, länkarna som
    en rad gula understrukna textlänkar, allt vänsterställt, 44 px
    klickyta. Blocket gick från 314 till 180 px höjd på mobil.
  - `controllers/sida.php`: kortare länktexter på startsidan ("Nyheter",
    "Sport", "Resultat 377"), rubriken beror på om listan finns.
  - Samma vy används under enskilda sidor (`/101` med flera). Där står
    fortfarande "Gårdagens nyheter →", nu som gul textlänk i stället för
    knapp.
  - Kvar: komponentens CSS ligger fortfarande i vyn, och bandet går inte
    kant i kant med sidan bredvid (se inventeringen). Löst i steg 2 nedan.
- **Steg 1 och 2 (2026-10-17, pushat 115941d, d18e2ee):**
  - Ny fil `css/tt.css`, laddas efter `texttvpage.css` i `header.php`
    (inte i `appembed/`): färgtokens, teckenstorlek ur `#pages` bredd med
    `cqi`, och komponenterna `tt-sidbredd`, `tt-band`, `tt-link`, `tt-list`
    och `tt-linkrow`. Delningsbilderna (`html.appShare`) är undantagna.
  - Teckenstorleken på sidorna: 14,7 px på 320, 16,7 på 360, 17,45 på 375,
    18,2 på 390, 19,4 på 414, 20,2 på 430 och 22 på desktop (förut 14, 18,
    18, 18, 18, 18 och 22). Ingen horisontell scroll på någon bredd.
  - `day_archive_link.php` byggd av komponenterna, utan eget `<style>`.
    Bandet går nu kant i kant med sidan och följer sidans storlek.
  - Skärmdumpar i `TextTV.nu/.playwright-mcp/18/` (`fore/`, `fore-mestlast/`,
    `steg1-storlek/`, `steg2-mestlast/`, jämförelser i `jamfor/`), tagna
    med ett Playwright-skript på 320–1280 px.
  - **Rättelser till granskningen:** sida 377 bryts inte av bredden. Raderna
    med `(X-X,X-X,X-X)` står på egen rad även på 1280 px, så det kommer från
    SVT:s innehåll. Och cookiebannern renderas inte alls
    (`include_once` är bortkommenterad i `footer.php:133`), så dess h1 är
    inget problem. Bannern på prod är Googles samtyckesmeddelande (se
    inventeringen). Steg 0 utgår; `cookie_banner.php` kan tas bort som död
    kod.
- **5abfe23 (2026-10-17), AMP borttagen.** `amp.php` och AMP-metoderna är
  borta; gamla `/amp`-adresser får 301 via `Sida::amp_redirect()`. AMP behöver
  inte tas hänsyn till i designen.

- **Steg 3 (2026-10-17, pushat fd03510):** raden under sidan ("Uppdaterad
  22:15" i cyan, "Dela →" som gul textlänk), annonsetiketterna i monospace
  och neutral grå, "Senaste nyheterna/sportnyheterna" med dubbelhöga gula
  rubriker (`tt-h2`) och `tt-list`. 70 rader oanvänd CSS borttagen.
- **ef847ee: versionsnummer på CSS och `scripts.js`** (`?v=` + filens
  ändringstid). CSS cachas i en vecka, så utan det fick återkommande
  besökare ny HTML med gammal CSS. Direkt efter en deploy serverar nginx
  ändå den gamla HTML:en i några sekunder (STALE), så en skärmdump direkt
  efter deploy kan se trasig ut; ta om den.
- **Egna cookiebannern borttagen (3c66139).**
- **Steg 4 (47bd2be): appramen** i svart i sidans bredd (loggan, "Sidor ≡",
  "← 100", blått "Gå till sida" med sifferknappsats, "102 →"), sidans h1 som
  blått band med oförändrad text, band och ram över båda kolumnerna med
  flera sidor i bredd. Alla brytpunkter per enhet och iPad-hacket med
  `letter-spacing` ersatta av en regel för brödtexten.
- **Steg 5 (bbca36a): sidomenyn och footern** delar `views/sidlista.php`
  (namn, prickar, sidnummer i cyan). Listan syns i footern på mobil och
  bara i sidomenyn från 700 px; länkarna finns i HTML:en på båda.
  `styles.css` är nere på 773 rader (1 322 när #18 började).
- **PHPStan (9d91ac0, b7b8076):** `make phpstan`, och deployen av
  webbsajten väntar på PHPStan-jobbet i `deploy-website.yml`.
- **Kvar på servern:** scp raderar inget, så `views/cookie_banner.php` ligger
  kvar på prod som död fil. Ofarlig, men kan tas bort för hand.

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
| Appramen (header, `Sidor`/`Hem`/`← 100`/`102 →`, "Gå till sida") | Ionic-blå `#0066B5`, systemfont, appikon. Ser ut som en app från 2015, inte som Text TV. Samma blå är appens primärfärg (öppet beslut 2) |
| Sidomenyn och footern ("Sidor"-listan) | Mörkblå `#141c2a`, Helvetica med vanlig understrykning, grå länkar, sidnummer i en annan font. Listan finns dessutom i både sidomenyn och footern. Footerlistan är sajtomfattande internlänkar till `/nyheter/igar`, `/sport/igar` och `/377/igar` (#16) |
| Cookiebannern | Bannern som syns på prod är Googles samtyckesmeddelande (`googlefc`, laddas av `adsbygoogle.js`), inte vår `cookie_banner.php`. Den egna `cookie_banner.php` var död kod sedan 2021 och är borttagen. Googles banner stylas i AdSense under Sekretess och meddelanden, inte i vår CSS; där kan färger och typsnitt närma sig Text TV. Syns inte lokalt, eftersom skärmdumpsskriptet blockerar annonsskripten |
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
  värden som `texttvpage.css` använder. De är inte rena (vitt
  `rgb(252,254,252)`, gult `rgb(252,254,1)`, cyan `#00c8ee`), och blått finns
  i två varianter: `--tt-blue-bg` (`#00f`) och `--tt-blue-fg` (`#12c`, bara
  inne på Text-TV-sidorna). Skriv vid varje token vad den får användas till.
  Lägg till högst en neutral färg för sådant som Text TV inte har, till
  exempel annonsetiketter. Inget annat.
- **Typografi:** Ubuntu Mono överallt. En storlek för brödtext och en för
  dubbelhöga rubriker, som på Text TV. Ingen sans-serif. Lägg en fallbackfont
  med `size-adjust` så att FOUT inte flyttar layouten.
- **Rubriknivåer:** tre, inte en. Blått band för sidans eller blockets
  rubrik (högst ett per block), dubbelhög gul eller cyan text för h2, cyan
  etikett för metadata. Blir allt band blir allt lika viktigt.
- **Sidans storlek:** idag sätts teckenstorleken med fasta brytpunkter från
  iPhone 5/6-tiden (`styles.css` cirka rad 1080–1120: 22, 16, 14 och 18 px,
  delvis på `device-width`, plus en regel för liggande läge med två sidor i
  bredd). På 375 px gäller `min-device-width: 375px` och ger 18 px. (Att
  sida 377 bryts beror inte på det, se "Gjort hittills".) Ersätt med en storlek
  som räknas fram ur *sidans* bredd, inte skärmens: sätt
  `container-type: inline-size` på sidans behållare och räkna med `cqi`,
  ungefär `font-size: clamp(10px, (100cqi - 2 * marginal) / 20, 1.375rem)`
  (Ubuntu Mono är 0,5 em per tecken, så 40 tecken blir 20 em). Med `vw`
  blir det fel så fort sidan inte har hela skärmen: sidomenyn öppen, flera
  sidor i bredd (`/100,300`) eller liggande läge. Taket i `rem` gör att
  användarens textstorlek respekteras. Prova på 320, 360, 375, 390, 414 och
  430 px, med sidomenyn öppen, i liggande läge och med flera sidor. Gör det
  här först: allt annat ska anpassas efter sidans bredd.
- **Mått:** `ch` och radhöjd som rutnät. Kolumn `40ch`. Avstånd i hela rader
  (`1lh` eller `1.25em`).
- **Interaktion:** hover och fokus = gul bakgrund med svart text, samma på alla
  länkar (eller färgbyte, se öppet beslut 1). Synlig fokusring som syns på
  gul hoverbakgrund, på det blå bandet och på svart.
- **Text:** en regel för hur namnet stavas (öppet beslut 4). Annonsetiketter
  behåller ord som AdSense godkänner ("Annons", "Annonser"); bara stilen
  ändras.

### 2. Komponenter (små, återanvändbara klasser)

- **`tt-band`**: rubrikband i full bredd, blått med vit eller gul text.
  Ersätter sidans och blockens rubriker i sajtens ram, inte varje rubrik
  (se rubriknivåer). När en befintlig rubrik flyttas in i bandet behålls
  orden och rubriknivån.
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

Ordningen styrs av var trafiken går. GSC 2026-09-17–10-14 (bara klick från
Google, inte direkttrafik eller appen): `/377` 63,6 % av klicken, startsidan
21,5 %, övriga levande sidor 12,3 %, arkivsidorna 1,6 % (6 393 adresser),
informationssidor 0,4 %, dagsarkiv 0,0 %. Arkivsidorna fick alltså inte
"mycket söktrafik"; det som syns på levande sidor kommer först.

Ordning efter steg 1–3 (reviderad 2026-10-17):

1. Appramen: header, "Gå till sida" med förslag (avsnitt 5), startsidans
   rubrik.
2. Sidomenyn och footern (punkt 4 nedan).
3. Textblocket under `/377` ("Text tv 377 – målservice och resultat"), i
   sans-serif idag och sajtens viktigaste SEO-text.
4. Resten i den ursprungliga ordningen nedan, arkivsidorna sist bland
   sidtyperna.

Den ursprungliga ordningen:

0. ~~Byt cookiebannerns `<h1>`~~: utgår, bannern renderas inte.
1. Raden under sidan, "Senaste nyheterna/sportnyheterna" och
   annonsetiketterna: syns på varje sidvisning. Behåll "Senaste nyheterna"
   och "Senaste sportnyheterna" som de är (sökord). "Fler nyheter efter
   annonsen" kan stå kvar, i gemener och monospace.
2. Arkivsidorna: ett band överst som säger att sidan är arkiverad och
   när, med länk till den levande sidan. (Trodde först att de fick mycket
   söktrafik; GSC visar 1,6 % av klicken, se ovan.)
   Lös först datumskillnaden mellan listen och sidans rubrikrad (se
   inventeringen), eftersom bandet ska visa datumet. Datumet i `<time
   datetime>`, länken beskrivande ("SVT Text TV 111 just nu →"). Bandet får
   inte trycka ner sidans h1 och innehåll så mycket att LCP blir sämre.
3. Appramen och navigeringen, och startsidans rubrik. "Gå till sida" får
   förslag, se avsnitt 5. Startsidans h1 "SVT
   Text TV - Nyheter och Sportresultat" flyttas in i bandet med samma ord.
4. Footern och sidomenyn. Gör en PHP-partial som båda använder, i stället
   för att ta bort listan på ett ställe. Länkarna ska finnas kvar i HTML:en
   även utan JS (internlänkar till arkiven, #16). Kolla först hur
   sidomenyn öppnas: knappen "Sidor" i `controls.php` är en länk till `/`.
5. Dagslistorna och dagsarkiven (#16). En gemensam rubrik: band med
   avdelning och datum en gång ("Nyheter · tors 15 okt 2026"),
   föregående/nästa dag som länkrad ("← 14 okt · 16 okt →"). Datumet tas
   bort ur underrubriken, inte ur h1 eller `<title>`.
6. Informationssidorna och bloggen. Skriv om texterna samtidigt, inte bara
   stilen: Om-sidans h1 "TextTV.nu är en smartare text-tv för din dator,
   mobil och surfplatta" är från 2012.
7. Resten: cookiebannerns stil, notiser, `/delade`, `/polisen` och fakta.

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

### 5. "Gå till sida" med förslag (önskemål från Pär 2026-10-17)

Idag är "Gå till sida" ett `type="number"`-fält utan förslag
(`controls.php`, `#goPageNum` och `#search-input`; JS i `js/scripts.js`).
Ett klick i fältet ska i stället visa en lista med förslag direkt, innan
något har skrivits. Hör till appramen (steg 3 ovan) och görs inom den här
todon.

**Hur andra gör.** Det vanliga mönstret är att listan öppnas när fältet får
fokus och då visar några senaste sökningar (sparade lokalt i webbläsaren,
högst cirka 4, sektionen döljs om historiken är tom) och sedan populära
sökningar (cirka 6). När man börjar skriva byts listan mot träffar. Algolia
Autocomplete har det som standard (`openOnFocus`, plugins för senaste och
populära sökningar). Command-paletter som Linear och GitHub (⌘K) visar
senaste och föreslagna mål på samma sätt. Baymard: autocomplete finns på
80 % av e-handelssajter men bara 19 % gör det helt rätt, och 58 % låter
inte användaren kopiera ett förslag in i fältet och fortsätta skriva.

**Förslag för TextTV.nu, i Text-TV-stil:**

- **Tomt fält:**
  - `Senast besökta`: sidnummer + namn från `localStorage`, högst 4.
  - `Mest läst nu`: 3 rubriker från `/api/most_read/news` (finns redan).
  - `Populära sidor`: fast lista med 100 Nyheter, 300 Sport, 377 Resultat,
    330 Resultatbörsen, 401 Väder och några till, samma källa som
    footerlistan (partialen i steg 4).
- **När man skriver:**
  - Siffror filtrerar på sidnummer: "3" visar 3xx, "37" visar 370–379
    med namn.
  - Bokstäver matchar namn: "väd" ger 401 Väder, "resu" ger 330 och 377.
  - Tre siffror + Enter går direkt till sidan som idag. Det snabba sättet
    får aldrig bli långsammare. Fundera på att gå direkt vid tre siffror,
    som med fjärrkontrollen.
- **Utseende:** svart lista, sidnummer i cyan, namn i vitt, rubrikerna i
  sektionerna som cyan etiketter (inte band), markerad rad enligt
  hoverregeln. Samma rutnät i `ch` som resten.
- **Tillgänglighet:** combobox enligt WAI-ARIA APG (`role="combobox"`,
  `aria-expanded`, `aria-controls`, `aria-activedescendant`), pil upp/ned,
  Enter, Esc. Fokus stannar i fältet.

**Fallgropar:**

- **Fältet får fokus automatiskt på desktop** när sidan laddas
  (`scripts.js`, cirka rad 200). Listan får inte öppnas då, utan bara vid
  klick, tangentbord eller `/`, annars ligger den öppen på varje sidvisning.
- **`type="number"` tar inte bokstäver.** För namnsökning krävs
  `type="text"` (eller `search`), men då förlorar mobilen sifferknappsatsen
  om inte `inputmode="numeric"` sätts, och då går bokstäver inte att skriva
  på mobilen. Bestäm: sifferknappsats och bara sidnummer på mobil, eller
  fritext överallt.
- **Utan JS** ska formuläret fungera som idag (`GET /?number=377`), och
  listan får inte ersätta länkarna i footern.
- **Annonser och CLS:** listan läggs ovanpå (position absolute/popover) och
  får inte flytta innehållet.
- **Rör inte API:t.** `/api/most_read` används av appen. Behövs
  "populära sidor" ur statistiken, gör en ny webbrutt i stället för att
  ändra den befintliga.

## Slutläge 2026-10-18 (natten, Claude ensam efter Pärs "fortsätt tills todo är helt klart")

Alla ytor i ordningen ovan är gjorda och pushade, en yta per commit, med
två granskningar av en subagent på vägen (fynden rättade eller listade nedan), och
PHPStan, `make test-api` och skärmdumpar före och efter för varje (i
`TextTV.nu/.playwright-mcp/18/`, jämförelser i `jamfor/`). Prod
kontrollerades efter varje deploy.

| Yta | Commit |
| --- | ------ |
| `tt.css`, teckenstorlek ur sidans bredd | 115941d |
| "Mest läst igår" av komponenterna | d18e2ee |
| Egna cookiebannern borttagen | 3c66139 |
| Raden under sidan, Senaste nyheterna/sportnyheterna | fd03510 |
| Versionsnummer på CSS/JS | ef847ee |
| Appramen, h1 som band, enhetsbrytpunkterna borta | 47bd2be |
| Sidomeny och footer: gemensam sidlista | bbca36a |
| PHPStan, deploy väntar på PHPStan | 9d91ac0, b7b8076 |
| "Gå till sida" med förslag (`js/tt-sok.js`) | 2a6b3cf |
| jQuery borttagen, Dela med `navigator.share`/`<dialog>` | 96114e2 |
| Rättelser efter första granskningen | 44f7da7 |
| Textblocket under 377 (`tt-page`) | 1c1bdb9 |
| Dagslistor och dagsarkiv | 26f9b47 |
| Arkivsidorna: band med tid och länk | bbc718b |
| Informationssidor, blogg, fakta, delat, polisen | f17a59a |
| `/svt-text-tv` med ram, sidomeny, en h1 | 108f783 |
| Ny version-raden, brödsmulor, "sänds inte just nu" | 11af41a |
| Städning: pages-thumbs, /delade, /polisen | 3ecbc17, 978c33e |
| Delningsbildernas CSS till `tt.css` | 6f2ae65 |
| Reservregler för äldre webbläsare | 5dff906 |
| Rättelser efter andra granskningen | e77c23f |

**Mått:** `styles.css` 1 322 → 502 rader, `tt.css` 1 239 rader (med
reservregler och utskrivna selektorlistor). jQuery (84 kB),
`js.cookie.js` och `jquery.cookie.js` borta. Inga `<style>`-block kvar i
vyerna utom `appembed/` (appen, rörs inte). `style=` finns bara på
AdSense-taggarna.

**Äldre webbläsare (GA4, 28 dagar, 463 000 sidvisningar):** 7,3 % saknar
container queries, 5,5 % `:is()`, 4,0 % `clamp()`, 3,1 % `<dialog>`. Alla
har reservregler (5dff906).

**Ändrat i ordningen:** steg 0 (cookiebannerns h1) utgick, bannern på prod
är Googles. Arkivsidorna flyttades ner efter GSC (1,6 % av klicken).

### Kvar för Pär (texter och beslut, inte kod)

1. **Namnregeln i löptext** (beslut 4). Inte ändrat i natt, eftersom
   texterna är sökord och FAQ-frågorna är strukturerad data (FAQPage):
   - `views/textsida-om-texttv-nu.php`: h1 "TextTV.nu är en smartare
     text-tv för din dator, mobil och surfplatta" (från 2012), h2
     "Text-tv – för att 2 miljoner…", löptexten, `title`-attribut "Till
     text-tv sida …".
   - `views/textsida-vanliga-fragor.php`: frågorna "Finns text-tv kvar
     fortfarande?", "Vilket år kom text tv?", "Hur många tittar på SVT
     text tv?", "Vilken text tv app är bäst?", "Hur får jag text tv på
     min smarta TV?" och svaren.
   - `views/blogg_overview.php`: "På väg mot en bättre text-tv-upplevelse",
     h1 "TextTV utvecklingsblogg".
   - `controllers/textsida.php`: titeln "Mest delade text-tv-sidorna".
   - `views/header.php` rad ~402 och ~405: meta-beskrivningar med "text-tv".
   - Prod-databasen `texttv_page_text`: rubriken "Text tv 377 – målservice
     och resultat" under 377 (och startsidans text).
2. **Googles samtyckesbanner** stylas i AdSense under Sekretess och
   meddelanden, inte i vår CSS. Svart, gult och Ubuntu Mono där vore
   konsekvent.
3. **Appen:** webben har svart ram nu, appen har kvar `#0066b5`
   (`texttv.nu (app)/src/css/theme.css`). Beslut 2 sa att appen kan följa
   efter.
4. **Arkivsidorna saknar h1** (före och efter #18). Bandet "ARKIV …" är en
   `<p>`. En h1 där skulle ge Google en rubrik att skriva titeln från, men
   arkivsidorna rankade redan över de levande sidorna (#11), så det är ett
   SEO-beslut och inte ändrat. Granskningen 2026-10-18 påpekade det.
5. **Död fil på servern:** deployen raderar inget, så
   `views/cookie_banner.php` och `views/pages-thumbs.php` ligger kvar på
   prod (ofarliga, laddas inte). Samma för `js/jquery.min.js`,
   `js/js.cookie.js`, `js/jquery.cookie.js`. Kan tas bort för hand eller
   med ett städsteg i `deploy-website.yml` som det för controllers.

### Uppföljning

- **AdSense:** baslinje RPM per sidvisning 2026-09-19–10-16: 14,59 kr i
  snitt, per vecka 15,35 → 14,74 → 14,26 → 13,37 (trenden var nedåtgående
  före designen). Jämför veckorna efter 2026-10-18, och samma veckor 2025.
- **GSC:** CTR och klick för `/377` och startsidan, YoY med `/377` som
  kontroll (se memory om GSC-mätning). Core Web Vitals (CLS) efter fyra
  veckor.

## Arbetssätt och lokal testning

- **Per yta:** UX- och art director-granskning, ändra lokalt, skärmdumpar
  före och efter på mobil (390 px, och 320 px för bredden) och desktop
  (1280 px), visa Pär innan push. Så gjordes "Mest läst igår". Kolla också
  tangentbordsnavigering och klickytor på 320 px.
- **Baslinje före arkivsidorna och appramen:** GSC för samma period året
  innan, med /377 som kontroll, och RPM i AdSense (filtrerat på
  `DOMAIN_NAME==texttv.nu`).
- **Lokal sajt:** `http://texttv.test:8380` (`make up`). Skärmdumpar med
  Playwright MCP måste sparas under `TextTV.nu/` (t.ex.
  `TextTV.nu/.playwright-mcp/`), inte i scratchpad.
- **Lokal data är tunn.** Schedulern är opt-in och importerar inget av sig
  själv, så `/nyheter/igar`, `/sport/igar` och `/377/igar` ger 404 lokalt.
  Använd datum som finns i den lokala databasen, t.ex.
  `/nyheter/2026-10-15` och `/377/2026-10-14`, eller importera med
  `make import`. Schedulern kraschade 2026-10-17 med
  `Class "Spatie\LaravelIgnition\IgnitionServiceProvider" not found`, så
  imagen behöver `make build` innan `make scheduler`.
- **"Mest läst igår" lokalt** kräver statistik som saknas. Lägg testdata i
  webbcontainerns cache och ta bort den efteråt:
  `docker compose exec website sh -c 'mkdir -p /tmp/texttv-most-read && cat > /tmp/texttv-most-read/startpage-news-<igår>.json'`
  med `[{"time":"22:54","title":"…","permalink":"/104/…"}, …]`.

## Risker

- **Annonserna (AdSense).** Annonsplatser och "efter annonsen"-texten styrs av
  villkor och kan ha krav. En ändrad layout kan flytta annonser och påverka
  intäkterna. Mät RPM i AdSense före och efter de ytor som rör annonserna.
- **CLS och prestanda.** Webbfonten används redan, men fler element i Ubuntu
  Mono betyder att FOUT syns mer. Kolla CWV i GSC efteråt. Ge annonserna
  plats med fast höjd så att de inte flyttar innehållet.
- **SEO.** Rubriker som flyttas in i band ska behålla orden och h1-nivån, och
  footerns internlänkar till arkiven får inte försvinna ur HTML:en när
  listorna slås ihop.
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
Inventeringen ovan bygger dock på sex skärmdumpar på mobil och en grep, och appramen
och footern kan ha beroenden (JS för sidomenyn, AdSense) som inte är kartlagda.
