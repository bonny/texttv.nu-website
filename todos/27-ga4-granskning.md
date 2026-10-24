**Status:** ny 2026-10-24
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

- `/343?embed=true` hade 2 848 visningar: någon bäddar in tabellen.
  `document.referrer` skickas inte med, så det syns inte var. Värt att ta
  reda på (access.log).
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

## Redan gjort 2026-10-24

- `search_open` räknas inte när en siffertangent öppnar listan (`e29acec`).
- `tab`, `placement`, `position` och `section` registrerade som anpassade
  dimensioner (se #25).
