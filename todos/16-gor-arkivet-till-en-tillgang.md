**Status:** aktiv — canonical och dagsarkiv för 377/330 deployade 2026-10-14. Gårdagens mest lästa på startsidan 2026-10-16. Mät 2026-11-14
**Senast uppdaterad:** 2026-10-20

# Todo #16 — Gör arkivet till en tillgång: idéer för användare, Google och AI

Brainstorm 2026-10-14 efter att #09 stängts. Frågan: hur gör vi sidorna mer
intressanta för användare, Google och AI-motorer, så att de rankar högre och
citeras oftare? Det här är en idékatalog med grova bedömningar, inte en plan.
Varje idé som väljs ska brytas ut till en egen todo med mätning.

## Det vi vet (från #01, #04, #09, #10, #11)

- **Söktrafiken är nästan bara sidnummer och varumärke.** 90 dagar
  (2026-07-14 → 10-11): `text tv 377` 5 023 klick, `text tv` 3 122,
  `svt text 377` 2 903, `377` 2 763. Där konkurrerar vi med SVT, som tar sex av
  tio platser, och med AI-översikter som svarar direkt i sökresultatet (#09).
  Titlar och H1:or har gett bättre snippets men ingen ny trafik.
- **AI-översikten citerar oss redan.** För `text tv 377 idag` hänvisar Googles
  AI-översikt till "TextTV.nu 377" för fullständig översikt (SERP-koll
  2026-10-14).
- **Vår unika tillgång är historiken.** SVT:s sidor visar bara "nu". Vi har
  varje version av varje sida, ett år bakåt för allt och längre för det som
  delats (se #10). Ingen annan kan svara på "vad stod på 377 i söndags".
- **Efterfrågan på tid finns redan:** `text tv 377 idag` 208 klick på 90
  dagar, `text tv 100 idag` 5, `text tv 100 nu` 92, `resultat lången 377`.

## Svar på Pärs två frågor

### "Ändras inte en nyhetssida mycket under en dag?"

Jo. En nyhetssida (106–199) kan få en helt ny nyhet senare samma dag, medan
sportens resultatsidor (330, 343, 377 m.fl.) är samma "sak" hela dagen och bara
fylls på. Därför bör arkivet byggas olika för de två typerna:

| Sidtyp | Vad en "enhet" är | Vad som ska sparas och visas |
| --- | --- | --- |
| **Nyheter** (100–199) | En **nyhet**, alltså en titel | Sista versionen per sida **och titel** och dag. Det är redan så permalänkarna fungerar (`/117/hushallens-ekonomi-mycket-stark-37317159`: slug från titeln). |
| **Resultat och tabeller** (330, 343–349, 376–379 m.fl.) | **Dagen** | Sista versionen per sida och dag = dagens slutresultat. |
| **Översikter** (100, 300, 101–105) | Ögonblicksbild | Lite värde i arkivet; låt dem rotera som idag. |

Det här behöver verifieras mot prod-DB:n innan något byggs. Förslag på fråga
att köra (Pär kör, se minnesanteckningen om prod-DB i auto mode):

```sql
-- Hur många versioner och hur många olika titlar per sida och dag, senaste veckan
SELECT page_num,
       ROUND(COUNT(*) / 7)           AS versioner_per_dag,
       ROUND(COUNT(DISTINCT title, DATE(date_added)) / 7) AS titlar_per_dag
FROM `texttv.nu`.texttv
WHERE date_added > NOW() - INTERVAL 7 DAY
GROUP BY page_num
ORDER BY page_num;
```

Hypotes att bekräfta: nyhetssidor har flera titlar per dag, resultatsidor
har en titel men tiotals versioner.

### "Får vi någon trafik på ämnesord idag?"

Väldigt lite, men det finns en lång svans. 90 dagar (2026-07-14 → 10-11):

- **Bland de 400 största frågorna** är bara en handfull ämnesfrågor:
  `resultatbörsen` 97 klick, sedan `tågstrejk norge` 3, `jonas oredsson` 2,
  `mord mariestad` 2, `mc olycka vänersborg` 2, `partiuppgörelse` 2,
  `trollfjäril` 2, `två svenskar gripna i thailand` 2.
- **Arkivsidorna** (URL med slug och id) får en svans av hundratals sidor med
  1–3 klick vardera, plus `/330/resultatborsen-13586101` med 135. Grovt
  uppskattat några hundra klick per kvartal totalt, mot ~5 000 för
  `text tv 377` ensamt. Sökfrågorna är nyhetshändelser (olyckor, brott,
  politik) som dör inom dagar.
- **Slutsats:** ämnes- och entitetssidor (`/amne/ebola`, `/lag/hammarby`)
  skulle bygga på en efterfrågan vi knappt ser idag. Undantaget är sport:
  `resultatbörsen` och resultatfrågor är verklig efterfrågan. Ämnessidor för
  nyheter flyttas därför ner i prioritet. Datum- och resultatsidor flyttas upp.

## Snabba vinster som upptäcktes under brainstormen

- **Arkivsidorna saknar `<link rel="canonical">`.** Live-sidorna har det
  (`/377?app=x` → `https://texttv.nu/377`), men arkivsidor och startsidan har
  inget. Följden syns i GSC: dussintals arkivsidor är indexerade **med**
  `?utm_source=brottsplatskartan&utm_medium=newslist` i URL:en, alltså som
  dubbletter av sig själva, och delar på länkkraften. Fix: canonical till den
  rena permalänken på arkivsidor, och till `https://texttv.nu/` på startsidan.
  Liten kodändring i `views/header.php` (raderna kring 617–626 hanterar redan
  live-sidorna). **Gör först, oavsett vad mer som väljs.**
  **Deployad 2026-10-14 (`0e95a99`).** Verifierat i prod: `/` → `https://texttv.nu/`,
  utm-varianten av `/117/hushallens-ekonomi-mycket-stark-37317159` → den rena
  permalänken, `/377` oförändrad. Gamla URL-format (`/117/arkiv/slug/id/`) och
  inaktuella slugs pekar också på permalänken. `/100,300`-kombinationer får
  medvetet ingen canonical. Mät 2026-11-14: antal sidor med `utm_` i GSC.
- **`/sida/vanliga-fragor` fick 84 klick** och `/blogg/texttv-api` 25 på 90
  dagar. Det finns efterfrågan på "om Text TV"-innehåll; se 7 nedan.

## Idékatalog

Bedömning: **värde** (hur mycket trafik eller citeringar det troligen ger) och
**insats**. Grov känsla, inte mätt.

### 1. Arkivet som sökbar historik — störst potential

| Idé | Värde | Insats |
| --- | --- | --- |
| **Dagsarkiv per resultatsida**: `/377/2026-10-12` med dagens slutversion överst och tidigare versioner under. Fångar `377 igår`, `allsvenskan resultat igår`, `text tv 377 12 oktober`, `målservice söndag`. | Högt | Medel |
| **Fasta URL:er för "igår" och "i helgen"**: `/377/igar`, `/330/helgen`. Korta, stabila, länkbara, och samlar länkkraft över tid till skillnad från arkiv-id:n. | Medel–högt | Låg |
| **Dagsnavigering på sidorna**: "← igår · i morgon →" på live- och arkivsidor, så Google hittar och värderar arkivet via interna länkar. | Medel | Låg |
| **Nyhetsarkiv per dag**: `/nyheter/2026-10-12`, alla nyheter (titlar) som sändes den dagen, med länk till varje permalänk. | Medel | Medel |
| **Retention som passar (#10)**: behåll sista versionen per sida och dag (resultat) respektive per titel och dag (nyheter) för alltid, radera mellanversionerna. Litet lagringsbehov, och historiken blir permanent. Förutsättning för allt ovan bortom ett år. | Möjliggörande | Medel |

### 2. Startsidan och dagssammanfattningar

| Idé | Värde | Insats |
| --- | --- | --- |
| **"Igår på Text TV" på startsidan**: gårdagens mest lästa nyheter. Data finns redan (`/api/most_read/news?date=<igår>`, cachad sedan #12). Interna länkar in i arkivet. | Medel | Låg |
| **Dagssida per datum**: `/dag/2026-10-13`, dagens mest lästa nyheter, slutresultat och väder på en sida. Unikt, citerbart innehåll ("vad hände 13 oktober"). | Medel | Medel |
| **"Det här hände för ett år / tio år sedan"**: nostalgi, delbart. Kräver arkiv längre bak än ett år (retention). | Låg–medel | Låg |

### 3. Sportens historik

| Idé | Värde | Insats |
| --- | --- | --- |
| **Resultatbörsen per datum**: `/330/2026-10-12`. `resultatbörsen` är vår största ämnesfråga och serveras idag av slumpvisa arkivsidor från 2017 (#10, #11). | Högt | Låg (samma mekanik som dagsarkivet) |
| **Allsvenskans tabell över tid**: 343 per omgång som en serie. Nisch, men ingen annan har det. | Låg–medel | Medel |
| **Lagsidor ur målservicen**: tolka 377/330/343 och visa "Hammarbys senaste resultat enligt Text TV". Stor yta för långa frågor, men tolkningen av Text-TV-text är bräcklig. | Medel | Hög |

### 4. Maskinläsbart, för AI och Google

| Idé | Värde | Insats |
| --- | --- | --- |
| **Canonical på arkivsidor och startsidan** (se snabba vinster). | Medel | Låg |
| **Tydliga, maskinläsbara tidsstämplar**: `<time datetime>` och "uppdaterad 14 okt 21:47" synligt. AI-översikter prioriterar färskhet. | Medel | Låg |
| **Strukturerad data**: `SportsEvent`/resultat på 377 och 330, `CollectionPage` på dagsarkiv. `NewsArticle` finns på arkivsidor sedan tidigare; kontrollera att `datePublished` stämmer. | Låg–medel | Medel |
| **Ren textversion** per sida, t.ex. `/377.txt`, utan Text-TV-grafik, och länkad från `llms.txt`. Ger AI-crawlers exakt det som ska citeras. | Okänt, billigt att prova | Låg |
| **RSS/Atom per sida eller sidgrupp** ("377-flödet", "nyheter 100–199"). `rssfeed` finns för bloggen. | Låg | Låg |

### 5. Engagemang (signaler till Google, och nöjdare användare)

| Idé | Värde | Insats |
| --- | --- | --- |
| **"Vad har ändrats?"**: markera rader som är nya sedan förra versionen, t.ex. nytt mål på 377. Unikt för oss eftersom vi har alla versioner. | Medel | Medel |
| **Bevaka en sida**: notis när 377 ändras eller när ett lag nämns. Appen har push via Firebase; webben kan få webbnotiser. | Medel (appen) | Hög |
| **Inbäddning som funktion**: `oembed`/`appembed` finns. En "bädda in 377 på din sajt"-sida ger länkar tillbaka. | Låg–medel | Låg |

### 6. Vilda idéer

| Idé | Värde | Insats |
| --- | --- | --- |
| **Text-TV-tidsmaskin**: valfri sida som den såg ut ett visst datum och klockslag, med en tidslinje att dra i. Viralt material ("så här såg 100 ut när …"). | Okänt, potentiellt högt | Medel |
| **Månads- och årskrönikor ur arkivet**: "Text-TV 2026: årets mest lästa sidor". Årlig artikel som andra länkar till. | Medel | Medel |
| **Öppet arkiv-API eller datadump** för forskare och journalister. Länkar och omnämnanden från universitet och media. Licensfråga mot SVT, kräver eftertanke. | Medel | Medel + juridik |
| **MCP-server / verktyg för AI-agenter**: "läs Text-TV sida X, nu eller på datum Y". Positionerar texttv.nu som källan för AI-assistenter. Tidigt. | Okänt | Medel |

### 7. "Om Text TV"-innehåll

`/sida/vanliga-fragor` (84 klick på 90 dagar) och frågor som
`finns text tv kvar` (12), `text tv samsung fjärrkontroll` (6),
`text tv på samsung smart tv` (2), `text tv app` (21) visar att folk söker
efter hjälp kring Text TV. Ett par riktiga guider ("Text-TV på din smart-TV",
"Finns Text-TV kvar?", "Vilka sidor finns?") är billigt evergreen-innehåll som
SVT inte skriver.

## Rekommenderad ordning

1. **Canonical på arkivsidor och startsidan.** Liten, säker, rättar en
   faktisk indexeringsbrist. Verifiera med `make test-api` att `/api/*` inte
   påverkas.
2. **"Igår" och dagsarkiv för 377 och 330**, med dagsnavigering. Börja med
   resultatsidorna, där en dag är en enhet och efterfrågan finns
   (`377 idag`, `resultatbörsen`).
3. **"Igår på Text TV" på startsidan.** Nästan gratis, data finns.
4. **Prod-frågan ovan om versioner och titlar per dag**, och sedan beslut om
   retention (#10) för nyheter.
5. Resten utvärderas efter att 1–3 har mätts.

## Genomfört

### Dagsarkiv för 377 och 330 — deployat 2026-10-14

Commits `b4a7729`, `fd94325`. URL:er: `/377/igar`, `/330/igar`,
`/377/YYYY-MM-DD`, `/330/YYYY-MM-DD`.

- Visar sidan som den såg ut vid dagens slut: senaste versionen före
  midnatt, högst sju dygn bakåt (importern sparar bara ändringar, så en dag
  utan ändringar har inga egna rader). "Sidan ej i sändning" (tom titel)
  hoppas över.
- Länkar till dagens alla versioner, navigering mellan dagar ("← föregående"
  bara om det finns data där), länk "← 377 igår" på de levande sidorna.
- Egen titel, beskrivning och canonical per URL. `/igar` har canonical till
  sig själv för att kunna ranka på "igår"-frågor.
- Idag → 302 till levande sidan. Framtid, dagar utan data och andra sidor
  → 404. Sidlistan styrs av `Sida::DAY_ARCHIVE_PAGES`.
- Cache: 3600 s för passerade dagar, 600 s för `/igar`, aldrig förbi
  midnatt för `/igar` och gårdagens datumsida.
- Verifierat i prod direkt efter deploy: `/377/igar` visar 69 versioner,
  senast 22:43, med canonical och bakåtlänk. Datumsidor 2 och 7 dagar
  bakåt fungerar.

**Mät 2026-11-14:** GSC sida-dimension för `/377/igar`, `/330/igar` och
datum-URL:er (indexering, visningar, klick), och frågor med "igår" eller
datum. Jämför `/377`:s egna klick YoY så att arkivet inte äter av den
levande sidan.

### Gårdagens nyheter och sportnyheter — deployat 2026-10-14

Commit `9efc8be`. URL:er: `/nyheter/igar`, `/sport/igar`,
`/nyheter/YYYY-MM-DD`, `/sport/YYYY-MM-DD` (högst 365 dagar bakåt, eftersom
nyheternas arkivsidor raderas efter ett år om ingen delat dem).

- Alla unika rubriker en dag (`get_headlines_for_day()`), grupperade
  Inrikes 106–129, Utrikes 130–149, Kultur och övrigt 150–199, Sport
  303–329. Gränserna kontrollerade mot SVT:s indexsidor 101 och 104.
  Notissidor 127, 128, 148, kulturindex 150 och nyhetsrullen 188 utelämnas.
- Mest lästa överst, ur most_read-filcachen (flyttad från `Api` till
  `texttv_helper`), dedupad per rubrik, bara för de 14 dagar cachen täcker.
- Titel och beskrivning per dag (antal + mest lästa rubrikerna), canonical,
  JSON-LD `CollectionPage`/`ItemList`.
- Länkar från startsidan ("Igår på Text TV: …"), enskilda sidor 100–199 och
  300–399, sidfot och sidomeny. Sportlistan länkar till samma dags
  `/377/…` och `/330/…`.
- Prod direkt efter deploy: igår 66 nyheter, mest lästa och fyra grupper.
  Svarstid ~0,09 s okachad, ~2,5 s första gången most_read räknas för ett
  datum (sedan filcache 1 h, nginx 1 h).

### SEO- och UX-granskning, runda 1 (2026-10-14)

Två oberoende granskningar mot prod. Åtgärdat samma dag (`27794b6`):

- **Datakvalitet** (UX, störst): kvarliggare från kvällen innan stod som
  00:08 (varje sida får en ny version vid midnatt när datumraden ändras),
  snarlika rubriker flera gånger, brus från 170/190–199 och sidrubriker.
  Nu: titlar som fanns dagen innan räknas inte, rubriker slås ihop vid
  ≥ 85 % likhet efter normalisering (testat på 14 riktiga par: 13 rätt,
  missen är en dubblett hellre än en försvunnen nyhet), rubriker under tre
  ord och "Test" bort, 190–199 utanför. Mest lästa matchas mot listan.
  Igår i prod: 66 rader → 31 nyheter, sport 31 → 8.
- **330 bort ur dagsarkivet** (SEO + UX): 330 är en meny över
  resultatsidorna, inte resultat. 365 nästan identiska sidor. 301 → /330.
- **/377/igar** titel och H1 börjar inte med "377" (kannibalisering).
- **/nyheter, /sport** → 302 till /igar i stället för 404.
- **Ingångarna** var ~2 700–3 500 px ner. Nu knappar direkt under sidan,
  före annonsen. Samma namn överallt ("Gårdagens resultat 377").
- **Listorna**: tidskolumn med hängande indrag, större rubriker,
  hopplänkar, "Dagens nyheter →" i stället för "idag".
- **Länge borta sidor**: länk till senast sända versionen, ingen
  "Sidan uppdaterad"-rad.
- **404-texten** och dagsarkivets intro/aria-labels.

Medvetet inte åtgärdat: `/igar` och datumsidan med samma innehåll har var
sin canonical (Pärs beslut; följ "Sidindexering" i GSC), ordningen i
JSON-LD, sitemap (`/sitemap.xml` avstängd sedan 2013 av lastskäl), 410
efter 365 dagar, Open Graph.

### SEO- och UX-granskning, runda 2 (2026-10-14)

Sju av nio rättningar från runda 1 verifierade i prod. Nya fynd, åtgärdade:

- **Sammanslagningen var för lös.** 85 % likhet slog ihop olika nyheter
  ("Riksbanken sänker/höjer räntan", "Sverige till final/semifinal"), och
  kvarliggarfiltret jämförde ungefärligt mot alla gårdagens rubriker, så en
  ny nyhet kunde försvinna. Nu: kvarliggare kräver exakt samma rubrik;
  ungefärlig sammanslagning 85 % bara på samma sidnummer, 94 % mellan
  sidor. 13 testpar rätt.
- **Brusfiltret** tog bort alla rubriker under tre ord, även "Påven död".
  Nu bara korta rubriker som börjar med en sport ("Ishockey NHL").
- **Introtexten** lovade fel version; nu "nyhetens sista version den dagen".
- **Startsidans knappar** låg efter alla sju sidorna (~3 400 px ner på
  mobil). Nu direkt efter sida 100.
- **Knapparna** 44 px höga (var 33 px).
- **550-meddelandet** hamnade under den fasta menyn på sidor utan
  brödsmulor.

Kvar medvetet: `/igar` och gårdagens datumsida har var sin canonical.

### Liten sitemap och llms.txt — deployat 2026-10-14

`/sitemap.xml` (avstängd sedan 2013, då den listade varje rad i
`texttv` med en query per sida) ersatt av en liten: 27 viktiga sidnummer,
startsidan, de tre `/igar`-sidorna, datumsidorna för nyheter, sport och 377
de senaste 30 dagarna och sju redaktionella sidor, 128 adresser. `lastmod`
som W3C-tidsstämpel där den är pålitlig (senaste versionen för levande
sidor, midnatt för `/igar` och datumsidorna). En indexerad fråga, ~0,06 s,
nginx-cache 10 min. `robots.txt` pekar på den. `llms.txt` har ett nytt
avsnitt om gårdagen och datumsidorna. Inte inskickad i Search Console än.

### Gårdagens mest lästa på startsidan — 2026-10-16

Startsidans "Igår på Text TV" hade bara tre knappar. Den 15 oktober gav de
3 klick till `/nyheter/igar` på 8 568 visningar av startsidan. Rutan visar
nu också gårdagens tre mest lästa nyheter, med tid och länk till nyhetens
arkivsida. Pärs beslut: funktionen är ny och unik (vi har data bakåt i
tiden), så den ska få tid att få genomslag.

- **Samma lista som `/nyheter/igar`.** Sammanställningen (brus,
  kvarliggare, sammanslagning, matchning mot most_read) är flyttad från
  `Dagsnyheter` till `texttv_helper.php` (`day_sections()`,
  `get_day_stories()`, `get_day_most_read()`). Dagslistorna är
  byte-identiska före och efter, kontrollerat lokalt på fem adresser.
- **En gång per dygn.** Startsidan renderas om var fjärde sekund, så
  `yesterday_top_news()` sparar resultatet i
  `/tmp/texttv-most-read/startpage-news-YYYY-MM-DD.json` (tio nyheter, så
  att antalet kan ändras utan omräkning) och städas med most_read-filerna.
  Låset väntar inte: räknar en annan worker redan visas bara knapparna.
  En ögonblicksbild av gårdagen från före midnatt sparas inte.
- **Antalet** är `Sida::STARTPAGE_YESTERDAY_COUNT` (3), så att sidorna under
  inte trycks ner för mycket på mobil (+~150 px). På bred skärm står rutan
  bredvid sida 100.
- Saknas data visas bara knapparna, som tidigare.
- `make test-api`: 31 av 31 identiska. API:t och appen berörs inte.
- **Deployad 2026-10-16 20:33** (`045dce2`). Prod direkt efter: startsidan 200 på 0,10–0,15 s, samma topp tre som "Mest lästa" på `/nyheter/igar`, filen skriven en gång 20:33:52. `/sport/igar` tog 5,8 s en gång, när most_read-filen för gårdagen räknades om (sker en gång i timmen, fanns före ändringen), sedan 0,12 s.

**Mät** en vecka efter deploy: klick från startsidan till arkivsidor och
`/nyheter/igar` (referer `https://texttv.nu/`) per dygn, mot 3 före.

### Ny 404-sida — deployad 2026-10-14

Commits `f415ff9`, `fd94325`. Inte en idé från katalogen, men upptäckt under
arbetet: okända adresser fick CodeIgniters engelska standardsida. Nu går
alla 404:or genom `show_texttv_404()`, med en 404 ritad som en Text-TV-sida
och sajtens meny. Mjuka 404:or på `/sida/<okänd>` (status 200) och
`/blogg/<okänd>` ("Doh!") rättade. 404:an cachas 60 s i nginx; verifierat
i prod att andra anropet blir `cache=HIT`.

## SEO-analys av det som byggts (2026-10-14)

GSC 2025-10-01 → 2026-10-11.

**Efterfrågan på "igår" är nästan noll.** Alla frågor med "igår" på ett år:
`lången resultat igår` 83 visningar (pos 13,8), `fotbollsresultat igår` 8,
resten 1–10. Ingen fråga med "377 igår". `/377/igar` och `/330/igar` ska
därför inte väntas ge mycket söktrafik i sig; värdet är användarnytta,
citerbarhet och datumfrågornas långa svans. Mät, men budgetera inte.

**Titlar och H1 på datumsidorna behöver justeras:**
- Datumsidornas H1 saknar datum ("SVT Text TV 377 - Målservice och
  resultat"). Google skriver om titlarna på 377-sidorna från H1 (#09), så
  datumsidorna riskerar att visas med samma rubrik som `/377` och tävla med
  den levande sidan. Datumet ska in i H1.
- Datumsidornas titel är ~66 tecken med datumet sist
  ("377 - Målservice och resultat tisdag 13 oktober 2026 - SVT Text TV") och
  kapas i sökresultatet just där datumet står. Datumet först, kortare.
- Förslag: titel `Resultat tisdag 13 okt 2026 – Målservice SVT Text TV 377`,
  H1 `SVT Text TV 377 – Resultat tisdag 13 oktober 2026`. 330 motsvarande med
  "Resultatbörsen".

**`/igar` och gårdagens datumsida är samma innehåll med olika canonical.**
Google väljer själv en av dem. Eftersom `/igar` byter innehåll varje dygn kan
den inte samla rankning för ett visst innehåll; datumsidan är permanent.
Förslag: canonical från `/igar` till gårdagens datumsida, men behåll `/igar`
som bekväm adress för användare.

**Lången: tappet började 2026-06-10, inte med textändringen i #09.**
`resultat lången 377` gav ~8 klick per dag (~15 visningar, pos 2,0) från
oktober 2025 till 2026-06-09, och föll till nästan noll från 2026-06-10, vid
oförändrad position. #09 A (ny `page_text`) deployades först i början av
augusti. Inget ändrades på sajten runt 10 juni (git log). VM 2026 började
11 juni. Lången-frågor totalt höll ~20 visningar per dag efter 10 juni men
klicken gick till noll. Orsaken är extern och okänd; slutsatsen i #09 om att
texten "kan ha" orsakat tappet stöds inte av tidslinjen.

**Rättelse samma dag, efter deploy: tipssidorna sänds inte alls längre.**
Kontrollerat i prod: "ej i sändning"-raden (som läggs till när sidan
försvinner) är från 2024-10-13 för 551, 2026-01-28 för 552–554, 2026-02-04
för 560, 2024-07-02 för 570–571 och **2026-06-09 för 550** (tipsindexet).
SVT har alltså tagit bort tipsavdelningen, och sista resten försvann dagen
före lången-klippan 2026-06-10. Det är den troliga förklaringen till
lången-tappet. Premissen nedan, att sidorna "sänds kring helgerna", var fel
och kontrollerades inte före bygget. Konsekvens:
- Fallbacken till senast sända version gör inget för tipssidorna (ingen
  version de senaste 60 dygnen). Den finns kvar för sidor som är borta
  tillfälligt.
- Sidor som inte sänts på över 14 dygn visar i stället "SVT har inte sänt
  sidan sedan 9 juni 2026", vilket är ärligare än "ej i sändning".
- Tipssidorna togs bort ur dagsarkivet.
- Efterfrågan (`stryktipset text tv` m.fl.) finns kvar men går inte att
  möta med SVT:s innehåll. Lämnas.

**Ursprunglig idé (premissen visade sig fel, se ovan): visa senaste sända versionen när en sida inte sänds.**
Tipssidorna (551 Stryktipset, 553 Europatipset, 554, 571 V75) sänds bara
kring helgerna och visar "Sidan ej i sändning" resten av veckan. Google
crawlar alltså ofta en tom sida. Efterfrågan finns: `europatipset resultat
text tv` 518 visningar pos 9, `text tv europatipset` 710 pos 13,
`text tv topptipset` 504 pos 19, `stryktipset text tv` 459 pos 14,
`v75 resultat text tv` 275 pos 15 — tusentals visningar på position 9–19.
Att visa senaste versionen med innehåll ("Senaste resultat, lördag 10 okt")
när sidan inte sänds, plus dagsarkiv för de sidorna, är troligen värt mer än
dagsarkivet för 377. Samma mekanik som `get_page_version_at_end_of_day()`.

## Mätning

Som tidigare: GSC, samma period året innan, `/377` som kontroll (se
minnesanteckningen om YoY). För nya URL:er: indexering (URL-inspektion) efter
en vecka, klick och visningar efter 30 och 90 dagar. För canonical: antalet
indexerade URL:er med `utm_` i GSC ska gå mot noll.

### Första veckan (GA4, 2026-10-14 – 10-20)

| Sida | Visningar | Användare |
| --- | ---: | ---: |
| `/377/igar` | 222 | 136 |
| `/nyheter/igar` | 97 | 69 |
| `/sport/igar` | 92 | 68 |
| `/nyheter/idag` (från 10-18) | 23 | 16 |
| `/sport/idag` (från 10-18) | 22 | 17 |
| Datumsidor (`/377/2026-10-14` m.fl.) | ~25 | några få |

Nästan allt är internt: startsidan (även `?homescreen=1`), `/377` och
sportsidorna. Google 6 visningar till `/377/igar`, 2 till `/nyheter/idag`.
Några visningar är Pärs egna tester (`texttv.test`, `/377/2099-01-01`).
`/377/igar` är 2–3 % av `/377`.

**`/377/igar` föll 2026-10-19:** 28–40 per dag 16–18 okt (GA4, mest
återkommande användare), sedan 7 på måndagen och 0 i GA4 till kl 14 på
tisdagen. `access.log` bekräftar: cirka 75 → 21 → 9 träffar. Sidan svarar
200 och länkarna finns i HTML:en. Två orsaker:

1. Måndag: `/377` själv föll till hälften (2 311 → 1 294 navigeringar via
   service workern). `/377/igar` föll mer än så.
2. Från 2026-10-19 20:14 (#25): efter kl 10 visar "Mest läst" fliken Idag,
   vars länkrad har `/377` i stället för `/377/igar`. Startsidan var
   ungefär hälften av hänvisningarna. Se #25.

Obs: navigeringar via service workern får referer
`https://texttv.nu/service-worker.js` i `access.log`, så den riktiga
hänvisningen syns bara i GA4.

## Relaterat

- [#10](10-retention-raderar-arkivsidor-som-rankar.md): retentionen, pausad.
  Blir aktuell igen med idé 1.
- [#09](done/09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md):
  trafikstrukturen och SERP-kollen.
- [#11](done/11-arkivsidor-rankar-over-de-levande-sidorna.md): arkivsidor som
  rankade över de levande sidorna på `resultatbörsen`.
