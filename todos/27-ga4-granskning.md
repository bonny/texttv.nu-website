**Status:** mest gjort 2026-10-24; kvar för Pär: samtyckesläget och filtret för intern trafik (se nedan)
**Senast uppdaterad:** 2026-10-24

# #27 Granskning av hur webben använder GA4

GA4-egendom 382798575 (`G-J9BM4E3WHD`), webben. Appen har en egen egendom
(171074401, Firebase) och ingår inte här. Underlag: koden (`header.php`,
`js/scripts.js`, `js/tt-sok.js`, `day_archive_link.php`,
`mest_last_under_sidan.php`), GA4 Data API för 28 dagar till 2026-10-23,
och texttv.nu/100 i en ren webbläsare.

## Fynd, viktigast först

### 1. GA sätter kakor innan besökaren har svarat på samtyckesrutan

På en ny besökare finns `_ga` och `_ga_J9BM4E3WHD` medan Googles
samtyckesruta (Funding Choices, via AdSense) fortfarande visas. `dataLayer`
har inget `gtag('consent', 'default', …)` före `config`. Rutan styr alltså
annonserna (TCF), inte GA, och GA mäter även den som tackar nej.

Åtgärd: Consent Mode v2. Antingen slå på "Google Consent Mode" i AdSense →
Sekretess och meddelanden (då sätter Googles CMP samtycket), eller ett
`gtag('consent', 'default', {analytics_storage: 'denied', …})` före
`config` i `header.php`. Med Consent Mode i grundläget skickas cookielösa
pingar och GA modellerar resten. **Siffrorna lär sjunka** när det slås på:
anteckna dagen i GA (annotering), annars ser det ut som ett trafiktapp i
alla jämförelser framåt. Pärs beslut, det rör både juridik och AdSense.

### 2. Botar är 15–20 % av "användarna"

Senaste 7 dagarna: Singapore 414, Kina 330 och "(not set)" 188 användare,
alla med 0–1,6 % engagemang och en sidvisning per session. Sverige 4 659.
Kina landar nästan bara på `/sida/delat` (211 av 211 visningar 17–23 okt).
**`/sida/delat` har alltså bara cirka 44 visningar från 13 riktiga
användare den veckan, inte 268** (rättat i #16).

GA4 kan inte filtrera bort länder i data. Åtgärder: jämför användare med
filtret Land = Sweden eller med `engagedSessions` i stället för
`totalUsers`. Eventuellt botskydd i nginx för mönstret, men det rör prod
(#12).

### 3. Utvecklingstrafik hamnar i produktionsegendomen

28 dagar: `localhost` 92 och `texttv.test` 77 sidvisningar. Det är lite,
men det är just testerna av nya funktioner (t.ex. `most_read_tab` 43 byten
från 3 användare 19 okt), som är det som mäts. Åtgärd: ladda inte gtag när
värden inte är `texttv.nu`, i `header.php`. Och Pärs egna besök i prod:
definiera intern trafik (Pärs IP) under Datastreams → Taggningsinställningar
och slå på filtret "Internal Traffic".

### 4. `?uppdaterad=<tidsstämpel>` ger tusentals unika sidadresser

Knappen "Ladda om" (`js/scripts.js`, `.pages-updated-reload`) lägger på
`?uppdaterad=` för att komma förbi nginx-cachen. 28 dagar: 9 690 unika
`pagePathPlusQueryString`, nästan alla sådana. `pagePath` påverkas inte,
men `page_location` blir högkardinal, och rapporter på hela adressen hamnar
i "(other)". Åtgärd: ta bort parametern med `history.replaceState` innan
gtag skickar sidvisningen, eller sätt `page_location` utan den i `config`.
Samma för `?number=` (47 + 9 med stavfelet `?numbe=`).

### 5. Brottsplatskartans länkar hamnar i "Unassigned"

`brottsplatskartan / newslist` (258 sessioner på 7 dagar, upp 345 %) har
ett `utm_medium` som GA4 inte känner igen, så det räknas som Unassigned.
Åtgärd i Brottsplatskartan-repot: `utm_medium=referral` och kampanjnamnet i
`utm_campaign=newslist`. Resten av Unassigned är `(not set)`, mest sessioner
som landar på `/` och `/377`: troligen hemskärmsappen (PWA) eller
sessioner som startat om.

### 6. Inga viktiga händelser (key events)

Ingen händelse är markerad som viktig, så GA:s rapporter för trafikkällor
har inget att jämföra källorna på. Förslag: `share`, `search_select` och
`select_content`. Det är en klickning per händelse i Admin → Händelser.

### 7. PWA-användningen syns bara via startadressen

`/?homescreen=1` hade 17 742 sidvisningar på 28 dagar, men bara den första
sidan i sessionen har parametern. Förslag: skicka
`display_mode: standalone|browser` som parameter i `config` (och registrera
den som dimension). Då går hemskärmsanvändarna att jämföra hela vägen.

### Mindre saker

- `/343?embed=true` hade 2 848 visningar. Utrett 2026-10-24 i
  `access.log`: en digital skylt (Philips BDL4550D, SmartSign ADAPI,
  enhetsnamn "Kinnarps Test", IP 194.237.14.0/24) som visar Allsvenskans
  tabell 07–17 på vardagar och laddar om ungefär var fjärde minut. En
  användare i GA4, ingen hänvisning. Troligen en tv-skärm på Kinnarps.
- Utgående klick (förbättrad mätning) fungerar: Play Store 31, App Store 9
  klick på 28 dagar.
- `live_update` skickas en gång per uppdatering, inte per besök (475
  händelser från 199 användare). Det är rätt för det den mäter. Sidvisningar
  räknas inte vid uppdatering på plats, så siffrorna för sidvisningar är
  jämförbara med tiden före.
- Datalagringen (2 eller 14 månader) går inte att läsa via API:t och
  kontrollerades inte. Med 2 månader går det inte att göra jämförelser med
  året innan i Utforska, bara i standardrapporterna. Kolla under Admin →
  Datainsamling och modifiering → Datalagring.

## Gjort 2026-10-24

- `search_open` räknas inte när en siffertangent öppnar listan (`e29acec`).
- `tab`, `placement`, `position` och `section` registrerade som anpassade
  dimensioner (se #25).
- **Punkt 3, 4 och 7 i koden** (`header.php`, deployat): gtag.js och
  `config` bara när värden är `texttv.nu`; `page_location` utan
  `uppdaterad`, `live`, `number`, `numbe` och `cb`; `display_mode`
  (`standalone`/`browser`) på alla händelser, registrerad som dimensionen
  "Visningsläge".
- **Punkt 5:** Brottsplatskartan skickar `utm_medium=referral` och
  `utm_campaign=newslist` (brottsplatskartan-web `9892baac`, deployat).
  `brottsplatskartan / newslist` slutar alltså växa i rapporterna och
  ersätts av `brottsplatskartan / referral`.
- **Punkt 6:** `share`, `search_select` och `select_content` är viktiga
  händelser.
- **Datalagring:** var 2 månader för händelsedata, nu 14. Gäller data
  framåt; Utforska kan alltså jämföra med året innan först hösten 2027.
- **Intern trafik:** regeln "Pär hemma" (155.4.219.33/32, `internal`) i
  Google-taggens inställningar.

### Efter granskningen (subagent, 2026-10-24)

- `search_select` har `via`: `siffra` när listan öppnades med en
  siffertangent (den öppningen räknas inte som `search_open`), annars
  `lista`. Räkna val per öppning som `search_select` med `via = lista`
  delat med `search_open`. Registrerad som dimensionen "Sökval via".
- `ios/index.php` och `android/index.php` (appsidorna) laddar gtag.js bara
  på `texttv.nu`, som `header.php`.
- Medvetet: HTML via `api.texttv.nu` (samma vhost) mäts inte längre. Bara
  `texttv.nu` räknas.

## Kvar för Pär

- **Samtyckesläget (punkt 1).** AdSense "Integritet och meddelanden" →
  Europeiska förordningar har redan samtyckesläge för annonser och
  analys påslaget, så Googles CMP skickar uppdateringar. Det som saknas
  är grundläget: GA4 → Dataflöden → webbflödet → Konfigurera
  tagginställningar → Visa mer → "Åsidosätt standardinställningar för
  samtyckesläge" → Analysinställningar på, Specifika regioner =
  "Geographic region - Europe" → Spara. Ifyllt men inte sparat (auto mode
  får inte ändra stående kontoregler). Annonsinställningarna där lämnas
  av: AdSense sköter annonserna via TCF. Lägg en annotering i GA4 samma
  dag, siffrorna sjunker.
- **Aktivera filtret "Internal Traffic"** under Datainsamling och
  modifiering → Datafilter. Det står i läget Testar tills det aktiveras.
  Bärbara datorn borta hemifrån har en annan IP och räknas fortfarande.
