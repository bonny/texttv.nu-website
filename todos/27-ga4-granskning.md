**Status:** mest gjort 2026-10-24; granskad 2026-11-01 av fyra granskare (jurist, GDPR, AdSense, GA), beslut om samtyckesläget efter 2026-11-25 (se "Granskning av samtyckesläget"). Filtret för intern trafik kvar för Pär
**Senast uppdaterad:** 2026-11-01

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

## Granskning 2026-11-01

- **Koden står kvar** som 2026-10-24: gtag bara på `texttv.nu`,
  `page_location` utan parametrarna, `display_mode` i `config`
  (`header.php`). Inget `gtag('consent', 'default', …)`.
- **Filtret för intern trafik testar fortfarande.** `testDataFilterName`
  = "Internal Traffic" har rader varje dag 24–31 okt (1–4 användare,
  7–138 sidvisningar om dagen, 138 den 28 okt). I läget Testar räknas
  de med i alla rapporter. Ett aktivt filter tar bort dem.
- **Samtyckesläget är inte sparat.** Det finns ingen annotering, och
  användarna per dag ligger kvar på 1 400–2 150 (18–31 okt) utan något
  ras. Fallet kommer den dag grundläget sparas.
- **Botar (fynd 2) har minskat.** 25–31 okt: Sverige 5 031 användare,
  "(not set)" 301 och Kina 268 (båda med 0–3 % engagemang). Singapore
  syns inte längre bland de sex största. Botarna är nu ungefär 9 % av
  användarna mot 15–20 % i oktober. Mät fortfarande med Land = Sweden
  eller `engagedSessions`.

## Granskning av samtyckesläget 2026-11-01

Fyra granskare (subagenter) tittade på punkt 1 och filtret var för sig,
som jurist (LEK), GDPR-expert, AdSense-expert och GA-expert. Inget
beslut 2026-11-01: det tas efter sista uppföljningen 2026-11-25.

### Det alla är överens om

- **Dagens läge strider mot LEK 9 kap. 28 §.** `_ga` sätts före svaret
  och även hos den som tackar nej. Analyskakor är inte strikt
  nödvändiga, och PTS räknade dem så i kaktillsynen 2022–23
  (Folkhälsomyndigheten, Tele2, Swedbank, Konsumentverket). 28 § har
  ingen sanktionsavgift (12 kap. 1 §), bara böter vid uppsåt eller
  oaktsamhet (13 kap. 2 §), och ringa fall är inte brott.
- **Ingen rättslig grund enligt GDPR.** Berättigat intresse fungerar
  inte när kakorna själva kräver samtycke.
- **Den praktiska risken är låg.** PTS:s ärenden slutade med
  underrättelser och avslut efter rättelse, inga sanktioner. IMY:s
  GA-beslut 2023 (Tele2 12 mnkr, CDON 300 000 kr) gällde överföringen
  till USA före Data Privacy Framework. Google är certifierat under
  DPF, men Latombes överklagande (C-703/25 P) är inte avgjort, och DPF
  kan falla som Privacy Shield. Troligast för en liten sajt: ett
  klagomål och en uppmaning att rätta.
- **AdSense påverkas inte.** Annonserna styrs av TCF-strängen, inte av
  `analytics_storage`. Googles EU User Consent Policy nämner varken
  Consent Mode eller GA, och kravet på Consent Mode v2 (mars 2024)
  gäller annonsörer som skickar data till Google Ads, inte
  AdSense-publicister. Inga kända fall där AdSense begränsats för att
  GA mäter före samtycke.

### Det de inte är överens om

| Granskare | Hur                                                                                       | När        |
| --------- | ----------------------------------------------------------------------------------------- | ---------- |
| Jurist    | Grundläget som snabb lösning, helst basic mode                                            | nu         |
| GDPR      | Basic mode (gtag.js först efter ja). Advanced är en nödlösning                            | –          |
| AdSense   | Taggens inställning räcker, slå på annonsinställningarna också (`ad_storage` via GA)      | nu         |
| GA        | `gtag('consent','default',…)` i koden före `config`, inte taggens inställning             | 2026-11-26 |

- **Advanced eller basic.** Advanced mode, som taggens inställning ger,
  skickar cookielösa pingar (user agent, referrer, tidsstämpel) även
  från den som tackar nej. EDPB:s riktlinjer 2/2023 (v2, okt 2024)
  räknar "åtkomst" utan lagring, t.ex. pixlar, till artikel 5.3, så
  även pingarna kan kräva samtycke. Ingen tillsynsmyndighet har prövat
  det. Basic mode är juridiskt rent men ger ingen statistik alls från
  dem som säger nej.
- **Ordningen i koden.** gtag.js och `config` ligger i `<head>`
  (`header.php`). CMP:n har ingen egen tagg utan följer med
  `adsbygoogle.js` längst ner i `footer.php`. Första sidvisningen
  skickas alltså innan CMP:n har laddats. Hur taggens inställning
  samspelar med det i tid är inte beskrivet hos Google, därför vill
  GA-granskaren ha grundläget i koden.
- **Vad det gör med siffrorna (bedömning, inte uppmätt).** Med Googles
  CMP lär 20–40 % av svenska besökare inte samtycka, så användare och
  sidvisningar faller ungefär lika mycket. Botarna utanför Europa
  påverkas inte, så deras andel ökar. Modellering kräver minst 1 000
  händelser per dag med denied i 7 dagar (lätt, cirka 43 000 per dag)
  och minst 1 000 användare per dag med granted under 7 av 28 dagar
  (på gränsen: 900–1 600), Reporting identity = Blended, och gäller
  aldrig bakåt. Uppföljningarna 2026-11-07 (#25), 11-15 (#22, #32) och
  11-24 (#35) blir ojämförbara om det slås på före dem; #28 mäter i GSC
  och påverkas inte.
- GDPR-granskaren menade att sökparametern `q` följer med i
  `page_location`. Sajten har ingen `q`-parameter (grep i vyer och
  `js/`), så det avfärdades.

### Övrigt från GDPR-granskningen, ej kontrollerat

- Integritetspolicyn bör nämna GA4, ändamål, Google som mottagare,
  överföringen till USA och lagringstiden.
- Google Ads Data Processing Terms ska vara godkända i GA4-adminen
  (personuppgiftsbiträdesavtalet).
- Lagringstiden 14 månader bör ha ett dokumenterat skäl (jämförelser
  med året innan).
- Funding Choices-rutan bör nämna analys tydligt.

### Filtret för intern trafik

Alla som tyckte till: aktivera. 24–31 okt var "Internal Traffic"
7 användare och 308 sidvisningar mot 142 073 totalt (0,2 %), men det är
Pärs klick på nya funktioner, som mäts med små tal (`har_prick`,
flikbyten, `search_select`). Filtrerad data kommer inte tillbaka, och
laptopen borta hemifrån räknas fortfarande. Lägg en annotering samma dag.

### Förslag till beslutet efter 2026-11-25

1. Aktivera filtret (kan göras när som helst).
2. Basic mode i koden: ladda gtag.js först när CMP:n har gett ja för
   analys. Det är det enda som både juristen och GDPR-granskaren tycker
   räcker, och det tar bort frågan om laddordningen.
3. Annotering i GA4 samma dag och 2026-11-26 som ny baslinje.

### Källor

- [LEK 2022:482](https://lagen.nu/2022:482) (9 kap. 28 §, 12 kap. 1 §, 13 kap. 2 §)
- [PTS underrättelse Tele2, kaktillsyn](https://www.pts.se/contentassets/7b02c828f0984bfba1d1614dc666ab1a/underrattelse-tele2-kaktillsyn.pdf)
- [PTS avslutsbeslut Folkhälsomyndigheten](https://www.pts.se/contentassets/7b02c828f0984bfba1d1614dc666ab1a/avslutsbeslut-dnr-22-11376-folkhalsomyndigheten_.pdf)
- [IMY beslut CDON (GA)](https://www.imy.se/globalassets/dokument/beslut/2023/beslut-tillsyn-ga-cdon.pdf)
- [Google: Privacy frameworks (DPF)](https://policies.google.com/privacy/frameworks)
- [WilmerHale om EU-domstolens prövning av DPF](https://www.wilmerhale.com/en/insights/blogs/wilmerhale-privacy-and-cybersecurity-law/20251201-european-court-of-justice-to-review-challenge-to-eu-us-data-privacy-framework)
- [EDPB:s riktlinjer 2/2023 v2](https://edpb.europa.eu/system/files/2024-10/edpb_guidelines_202302_technical_scope_art_53_eprivacydirective_v2_en_0.pdf)
- [arXiv 2312.08806 (cookielösa pingar)](https://arxiv.org/html/2312.08806v6)
- [Google EU User Consent Policy](https://www.google.com/about/company/user-consent-policy/)
- [AdSense: krav på CMP i EES, UK och CH](https://support.google.com/adsense/answer/13554116?hl=en)
- [GA4: krav för modellering](https://support.google.com/analytics/answer/11161109)
- [GA4: filter för intern trafik](https://support.google.com/analytics/answer/10108813)
- [GA4: datafilter är permanenta](https://support.google.com/analytics/answer/13296761)
- [Google: consent default före config](https://developers.google.com/tag-platform/security/guides/consent)
