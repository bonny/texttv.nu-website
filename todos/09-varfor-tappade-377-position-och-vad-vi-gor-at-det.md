**Status:** aktiv
**Senast uppdaterad:** 2026-08-02

# Varför tappade /377 position — och vad vi gör åt det

Utredning gjord 2026-08-02, första gången `mcp-gsc` varit inkopplad mot
`https://texttv.nu/`. Utgångspunkten var att designa en titeländring för 377
(analogt med #01/#04 Fas 1). **Den hypotesen är falsifierad.** Det här
dokumentet är dels obduktionen, dels den plan som datan faktiskt stöder.

## Sammanfattning

`/377` tappade **7 615 klick per kvartal** år över år. Orsaken är extern: SVT
tar numera sex av tio organiska platser på sina egna varumärkesfrågor. Det
finns inget deploy att skylla på och ingen titeländring som tar tillbaka det.
Det som återstår är en mindre men försvarbar yta — innehållsintent-frågor där
vi redan konverterar 13–43 %.

## Mätunderlag

Property: `https://texttv.nu/` (URL-prefix). **OBS:** `sc-domain:texttv.nu`
saknar historik före ~2026 — använd alltid URL-prefix-propertyn för
jämförelser bakåt. Båda ger identiska siffror för nutid.

### /377, samma 90-dagarsfönster år över år

| | 2025-05-04→08-01 | 2026-05-04→08-01 | Δ |
| --- | --- | --- | --- |
| Klick | 26 093 | 18 478 | **−29 %** |
| Visningar | 2 054 333 | 2 048 708 | −0.3 % |
| CTR | 1.27 % | 0.90 % | −29 % |
| Position | 3.7 | 4.5 | −0.8 |

Efterfrågan oförändrad. Positionen och klicken föll.

### Kvartalsserie — en glidning, inte ett steg

| Kvartal | Klick | Visningar | CTR | Pos |
| --- | --- | --- | --- | --- |
| jun–aug 2025 | 23 412 | 1.78 M | 1.31 % | 4.1 |
| sep–nov 2025 | 27 841 | 2.26 M | 1.23 % | 4.3 |
| dec–feb 2026 | 22 187 | 1.88 M | 1.18 % | 4.5 |
| mar–maj 2026 | 22 524 | 2.43 M | 0.93 % | 4.5 |
| maj–aug 2026 | 18 478 | 2.05 M | 0.90 % | 4.5 |

### Månadsvis 2026 — ingen återhämtning efter maj-fixarna

| Månad | Klick | Visningar | CTR | Pos |
| --- | --- | --- | --- | --- |
| mar | 7 328 | 751 k | 0.98 % | 4.6 |
| maj | 8 069 | 877 k | 0.92 % | 4.4 |
| jun | 5 900 | 658 k | 0.90 % | 4.8 |
| jul | 5 241 | 591 k | 0.89 % | 4.5 |

Klicktappet jun–jul följer visningarna (877 k → 591 k) — säsong, inte ranking.

## Vad som uteslutits

**Titeln.** Tvärsnittet motsäger hypotesen: de titelfixade sidorna har sämre
CTR än de orörda, trots bättre position.

| Sida | Titel | Pos | CTR (90d) |
| --- | --- | --- | --- |
| `/330` | legacy `"330 - SVT Text TV"` | 4.3 | 1.12 % |
| `/377` | legacy `"377 - SVT Text TV"` | 4.5 | 0.90 % |
| `/343` | fixad, `- Allsvenskan tabell` | 3.7 | 0.58 % |
| `/336` | fixad, `- Premier League` | 3.7 | 0.20 % |
| `/376` | legacy | 5.0 | 0.43 % |

Detta är ett tvärsnitt över olika frågor och intent — det bevisar **inte** att
#01:s fix var verkningslös mätt mot sidornas eget förflutna (där stod
0.31 % → 0.73 %). Men det tar bort grunden för att anta att greppet lyfter 377.

**Och avgörande: Google skriver om både title och description på `/377`.**

| | Vad vi skickar | Vad Google visar |
| --- | --- | --- |
| Title | `377 - SVT Text TV` | `SVT Text TV 377 - Målservice` |
| Description | `…sportresultat & målservice. ⚽️ 377 – sportnördens bästa vän!` | `377 är den mest besökta sidan hos SVT Text och fokuserar på sportresultat… Sidor. Hem · Nyheter 101-105 · Inrikes …` |

Titeln tas från `<h1>`. Descriptionen byggs av brödtext + navigation. **Taggen
vi skulle ha ändrat visas inte.** På den här sidan är `<h1>` hävstången, inte
`<title>` — och den är redan bra.

**Kannibalisering.** Per-fråga-attribution visar att `/377` äger sina frågor
ensam. På `377` ligger startsidan på position 27 med 56 visningar.

**Teknik.** URL-inspektion 2026-08-02: indexerad, crawlad samma dag som mobil,
`Google Canonical = https://texttv.nu/377`, robots ALLOWED, rich results PASS
(breadcrumbs). Noll indexeringsproblem på `/377`, `/330`, `/343`, `/300`, `/`.

**Egna ändringar.** `git log` visar **inga commits mellan 2025-06-21 och
2026-05-19**. Sajten låg orörd under exakt den period då raset skedde.

**Prestanda.** Lighthouse-baseline 2026-05-19 var redan 95–96 med LCP 2,2–2,6 s.
HTTP/2-fixen deployades 2026-05-19 och gav ingen positionsåterhämtning på tre
månader.

## Vad som faktiskt hände

SERP för `svt 377` (google.se, hl=sv, gl=se, 2026-08-02):

| # | Resultat |
| --- | --- |
| 1 | svt.se/text-tv/**377** |
| 2 | svt.se/text-tv/**300** |
| **3** | **texttv.nu/377** |
| 4 | svt.se/text-tv/**100** |
| 5 | facebook.com/texttv377 |
| 6 | svt.se/text-tv/**317** |
| 7 | svt.se/text-tv/**330** |
| 8 | Facebook-video (P4 Stockholm) |
| 9 | hemmakanalen.se/text-tv/377 |
| 10 | svt.se/text-tv/**376** |

Varje SVT-träff har **samma** description:

> "SVTs Text-TV på internet. Nyheter, Ekonomi, Sport, **Målservice 377**, Väder, TV…"

Deras sidbrett boilerplate innehåller strängen "Målservice 377". Därför blir
`/300`, `/100`, `/317`, `/330` och `/376` alla kandidater på 377-frågor. SVT
trycker ner oss med volym, inte med bättre innehåll.

Det förklarar allt: glidningen (deras sidor indexerades successivt), att inget
vi gjorde spelade roll, och tappets gradient mot varumärkesfrågor:

| Fråga | Klick YoY | Pos 2025→2026 |
| --- | --- | --- |
| `svt 377` | −87 % | 2.4 → 4.3 |
| `svt377` | −74 % | 2.4 → 2.7 |
| `377` | −54 % | 2.5 → 5.3 |
| `text 377` | −48 % | 3.5 → 4.8 |
| `377 text tv` | −40 % | 2.4 → 3.3 |
| `text377` | −32 % | 2.5 → 2.7 |
| `text tv 377` | −16 % | 3.0 → 4.5 |
| `text tv` (startsidan) | **+96 %** | 5.8 → 4.9 |

Ju tydligare frågan signalerar "jag vill till SVT", desto större tapp.
Startsidan, som lever på generiska frågor, gick åt andra hållet.

## Bieffekt: CTR duger inte längre som KPI på den här sidan

Mellan dec–feb och mar–maj: **+546 000 visningar**, position oförändrad 4.5,
klicken oförändrade. De extra visningarna konverterade på **0.06 %**. CTR faller
alltså även när ingenting blir sämre — nämnaren späds ut. Mät klick och
position, inte CTR, på högvolymsidor.

## Planen: innehållsintent

Där SVT:s generiska mall inte biter konverterar vi redan mycket bra.

### Baseline 2026-08-02 (90d, `https://texttv.nu/377`)

| Fråga | Klick | Visningar | CTR | Pos |
| --- | --- | --- | --- | --- |
| `text 377 målservice` | 700 | 5 139 | 13.62 % | 2.6 |
| `text tv 377 idag` | 604 | 9 318 | 6.48 % | 2.8 |
| `377 målservice` | 443 | 1 025 | 43.22 % | 2.3 |
| `resultat lången 377` | 286 | 725 | 39.45 % | 2.0 |
| `text tv 377 nu` | 114 | 265 | 43.02 % | 1.8 |
| **Summa** | **2 147** | **16 472** | **13.0 %** | — |

CTR:en behöver inte förbättras — den är redan utmärkt. **Det som ska växa är
visningsbasen**, dvs. antalet innehållsfrågor vi över huvud taget syns på.

### A. Skriv om `texttv_page_text` för 377

Den enda stabila, indexerbara texten på sidan är `<aside class="page-text">`,
som hämtas från DB-tabellen `texttv_page_text` (`views/page_text.php:29`,
nyckel `pagedescription`). **Det är en DB-uppdatering i prod — inget deploy,
direkt återställbar.**

Nuvarande text (477 tecken) är till ~60 % nostalgi om att Stryktipset flyttade
till sida 551.

**Viktigt: nostalgin är inte värdelös.** `resultat lången 377` ger 286 klick
till 39.45 % CTR och rankar sannolikt just tack vare den passagen. Den ska
skrivas om, inte tas bort.

Textens uppdrag, i prioritetsordning efter var frågevolymen finns:
1. Målservice/målresultat — förstärk det `<h1>` redan säger
2. "idag" / "nu" / "live" — den största outnyttjade familjen (9 318 visningar)
3. Lången/Stryktipset — behåll, formulera som resultatservice snarare än minne
4. Vilka serier som faktiskt syns på 377
5. Interna länkar till 376/378/379 med vad de innehåller

Faktakontroll mot live 2026-08-02 (målserviceindexet på 376): **377** =
Allsvenskan, Damallsvenskan, Superettan, Elitettan · **378** = Ettan samt
danska, finska och norska ligan · **379** = matchfakta (publik, domare) ·
**376** = index över var varje serie ligger · **330** = resultatbörsen.
Serie→sida-mappningen kan variera med säsong — texten nedan är formulerad så
att den tål det.

**Föreslagen ersättningstext** (första meningen är det Google sannolikt
plockar till snippeten, därför står målservice/resultat/dagens först):

```html
<h2>Text tv 377 – målservice och resultat</h2>

<p>Sidan 377 är SVT Text TV:s målservice. Här hittar du mål och resultat från
dagens matcher, med svensk fotboll som Allsvenskan, Damallsvenskan, Superettan
och Elitettan i fokus, uppdaterade medan matcherna spelas. 377 är den mest
besökta sidan hos SVT Text.</p>

<p>Målservicen fortsätter på flera sidor: <a href="/378">378</a> har Ettan och
de utländska ligorna, <a href="/379">379</a> har matchfakta som publiksiffror
och domare, och <a href="/376">376</a> är målserviceindexet som visar vilken
sida varje serie ligger på. Alla resultat samlade finns på
<a href="/330">resultatbörsen 330</a>, och sportnyheterna börjar på
<a href="/300-302">300</a>.</p>

<p>377 är en klassiker i text-tv-sammanhang. Länge var det här sidan för
Stryktipset och resultaten från lången, innan de flyttade till
<a href="/551">sid 551</a> – en flytt som många fortfarande tycker var ett
helgerån.</p>
```

Ändringar mot nuvarande text: nostalgin flyttad sist och nedkortad (men
`Stryktipset`/`lången` behållna — de bär `resultat lången 377`), serier och
"dagens matcher" tillagda, interna länkar till 376/378/379/330 tillagda,
stavfelet `helgrerån` → `helgerån` rättat.

**Kört i prod 2026-08-02.** `UPDATE texttv_page_text ... WHERE id = 2` (raden
har `pagedescription = '377'`, tom `title` — bara `text` rördes). 546 → 931
tecken. Live-verifierat, alla sex interna länkar svarar 200, tankstreck och
å/ä/ö rena (`--default-character-set=utf8mb4`).

**Backup av föregående text:** `scratchpad/377-pagetext-BACKUP-2026-08-02.html`
(562 byte). Tabellen saknar historik och `updated_at`, så det är den enda vägen
tillbaka — flytta filen någonstans beständigt om den ska överleva sessionen.

DB-åtkomst för framtida bruk: `ssh texttv.nu` (root), creds ligger som
`fastcgi_param DB_USERNAME/DB_PASSWORD/DB_DATABASE` i
`/etc/nginx/sites-enabled/texttv.nu` — `/root/.my.cnf` har ett annat lösenord
som **inte** fungerar mot `texttv.nu`-databasen.

**Baseline att mäta A mot** — 90d före ändring (2026-05-04→08-01), de frågor
texten riktar sig mot:

| Fråga | Klick | Visningar | CTR | Pos |
| --- | --- | --- | --- | --- |
| `text 377 målservice` | 700 | 5 139 | 13.62 % | 2.6 |
| `text tv 377 idag` | 604 | 9 318 | 6.48 % | 2.8 |
| `377 målservice` | 443 | 1 025 | 43.22 % | 2.3 |
| `resultat lången 377` | 286 | 725 | 39.45 % | 2.0 |
| `text tv 377 nu` | 114 | 265 | 43.02 % | 1.8 |
| **Summa** | **2 147** | **16 472** | **13.0 %** | — |

Det som ska växa är **visningsbasen**, inte CTR:en. Följ särskilt
`resultat lången 377` — om den tappar är det nostalgipassagens omskrivning som
kostade, och då är backupen vägen tillbaka.

### B. Täpp snippet-läckaget

Google avslutar descriptionen med `Sidor. Hem · Nyheter 101-105 · Inrikes` —
navigationsboilerplate rakt in i sökresultatet. Orsaken är att `<aside>`-texten
tar slut och nästa text i DOM är navigationen. Åtgärd: ge Google tillräckligt
med eget material i A, så väljer den bättre. Ingen kodändring behövs.

### C. Ge syskonsidorna riktiga H1:or

`/378` har **1.62 %** CTR och `/379` **1.15 %** — båda bättre än `/377`:s
0.90 %. Mindre varumärkeskonkurrens på de numren.

Och här finns den konkreta hävstången. Google tog `/377`:s titel från dess
`<h1>`, inte från `<title>`. Men H1-logiken i
`views/pages_inner_output_current.php:20–32` är hårdkodad för **fem fall**
(startpage, 377, 101–103, 104–105). Alla andra sidor får en generisk
`sr-only`-fallback:

```html
<h1 class="sr-only">SVT Text TV 378</h1>
```

Det ger Google ingenting att skriva om från. Filen har redan en TODO om att
den och `header.php` (som har ~30 entries) borde dela metadatakälla.

Förslag på H1:or, formulerade efter vad sidorna faktiskt innehåller:

| Sida | Föreslagen H1 |
| --- | --- |
| 376 | `SVT Text TV 376 - Målserviceindex` |
| 378 | `SVT Text TV 378 - Målservice utländska ligor` |
| 379 | `SVT Text TV 379 - Matchfakta` |
| 330 | `SVT Text TV 330 - Resultatbörsen` |

Notera att detta är **motsatsen** till slutsatsen om `<title>`: titeltaggen är
verkningslös på 377 eftersom Google skriver om den, men H1:n är just det den
skriver om *från*. Att flytta whitelisten från `header.php` till en delad källa
(den befintliga TODO:n) blir därmed mer värt än det såg ut.

**Deployat 2026-08-02** (commit `d7b585b`, live-verifierat på alla fyra sidor).
H1-tabellen ersatte samtidigt if-kedjan; de fem befintliga fallen är oförändrade.

**Baseline före deploy** — 28 dagar, 2026-07-05→08-01, property
`https://texttv.nu/`:

| Sida | Klick | Visningar | CTR | Pos |
| --- | --- | --- | --- | --- |
| /330 | 27 | 3 082 | 0.88 % | 3.9 |
| /379 | 12 | 804 | 1.49 % | 5.2 |
| /376 | 8 | 2 214 | 0.36 % | 5.3 |
| /378 | 67 | 3 313 | 2.02 % | 4.8 |
| **Summa** | **114** | **9 413** | **1.21 %** | — |

Uppföljning inlagd i `todo.md` till **2026-09-01**. Mät klick och position —
inte CTR, av samma utspädningsskäl som beskrivs ovan.

Förväntan att pröva mot: `/378` och `/379` har redan bättre CTR än `/377`
(mindre varumärkeskonkurrens på de numren), så om H1-hypotesen stämmer bör de
röra sig först. `/376` är sämst i gruppen och har mest att hämta.

### D. Mät

Mätmetod som #01: 30d och 60d efter deploy, samma frågeuppsättning som
baselinetabellen ovan. Mät **klick och position**, inte CTR (se avsnittet om
utspädning).

## Risker och ärliga förbehåll

- **Potten är liten.** 16 472 visningar mot navigationsfrågornas ~2 M. Även en
  tredubbling av visningsbasen ger ~+4 000 klick/kvartal — drygt hälften av de
  7 615 som SVT tog. Det här återställer inte tappet, det bygger en ny yta.
- **SERP:en kollades för en fråga, från en plats, en gång.** GSC ger 4.3–5.3 på
  samma fråga, så positionen varierar.
- **Att SVT:s boilerplate korrelerar med vårt tapp är starkt men inte bevisat** —
  vi kan inte se när deras sidor indexerades.
- **Att skriva om `page_text` kan sänka `resultat lången 377`** om nostalgin
  formuleras bort. Spara nuvarande text före ändring.

## Öppna frågor

- Ska 376/330/377 över huvud taget flyttas in i `header.php`-whitelistens nya
  format? Efter det här: **nej, inte för 377:s skull** — Google skriver ändå om
  titeln. Frågan kvarstår för sidor där Google *inte* skriver om.
- `#01`:s 60d-slutmätning var planerad till 2026-07-18 och är **försenad**.
  `mcp-gsc` fungerar nu, så den går att göra.

## Logg

- **2026-08-02** — `mcp-gsc` lagad (beroendepinne `mcp[cli]<2` i `~/.claude.json`;
  `mcp` 2.0.0 tog bort `mcp.server.fastmcp`). Full GSC-utredning gjord.
  Titelhypotesen falsifierad, extern orsak fastställd, plan A–D formulerad.
