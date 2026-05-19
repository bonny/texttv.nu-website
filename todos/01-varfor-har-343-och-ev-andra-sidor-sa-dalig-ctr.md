**Status:** aktiv — deployat 2026-05-19 (commit 5f9c6ad, 19/19 sidor live-verifierade). Väntar 30d GSC-mätning (2026-06-18).
**Senast uppdaterad:** 2026-05-19

# Todo #01 — Varför har /343 och ev andra sidor så dålig CTR?

## Sammanfattning

Undersök varför `/343` (och eventuellt andra sidor) har dålig CTR i Google Search Console. Identifiera mönster — är det specifika page-numbers, sidotyper (nyheter/sport/väder/TV-tablå) eller queries där positionen är bra men klickfrekvensen låg?

## Bakgrund

GSC-data 2026-04-21 → 2026-05-19 (28 d), property `sc-domain:texttv.nu`.

**/343 vs /377** (samma sport-domän, helt olika utfall):

| Sida | Clicks | Impressions | CTR       | Pos |
| ---- | ------ | ----------- | --------- | --- |
| /377 | 4 178  | 472 905     | **0.88%** | 4.5 |
| /343 | 42     | 16 692      | **0.25%** | 4.8 |

/377 har **3.5× bättre CTR vid samma position** trots ~28× volymen.

**/343-queries — där rankningen är bra men ingen klickar:**

| Query         | Impressions | CTR   | Pos     |
| ------------- | ----------- | ----- | ------- |
| `343 text`    | 1 072       | 0.00% | **2.9** |
| `text 343`    | 1 493       | 0.27% | 2.9     |
| `text tv 343` | 2 538       | 0.08% | **3.5** |
| `svt343`      | 649         | 0.15% | 2.7     |
| `343 text tv` | 132         | 0.00% | 4.0     |

Pos 2.9–3.5 med 0–0.27 % CTR är extremt — siten rankar men SERP-resultatet
övertygar inte användaren att klicka.

**/377-queries — kontrast:** "resultat lången 377" 44.97% CTR @ pos 2.0,
"text 377 målservice" 11.27% @ pos 2.5, "377 målservice" 37.78% @ pos 2.3.
Alla högvärdes-queries där användaren söker en *specifik funktion*
(målservice, lången-resultat).

**Hela populationen med samma symptom** (28d, top-200 pages, filter: pos ≤ 5.5,
impressions ≥ 300, CTR < 0.5 %), sorterad efter impressions desc — högst upp =
störst förlorad volym:

| Sida | Clicks | Impressions | CTR       | Pos | Antagen sidotyp / kommentar      |
| ---- | ------ | ----------- | --------- | --- | -------------------------------- |
| /300 | 101    | 22 434      | 0.45%     | 4.7 | sport-startsida (Allsvenskan?)   |
| /343 | 42     | 16 692      | 0.25%     | 4.8 | sport-index                      |
| /336 | 9      | 9 339       | **0.10%** | 3.9 | tabell (Allsvenskan?)            |
| /345 | 5      | 3 940       | 0.13%     | 3.8 | hockey-tabell?                   |
| /358 | 9      | 3 591       | 0.25%     | 4.2 | sport                            |
| /344 | 3      | 2 059       | 0.15%     | 4.7 | sport                            |
| /101 | 6      | 1 854       | 0.32%     | 5.3 | nyheter följdsida                |
| /127 | 4      | 1 170       | 0.34%     | 3.8 | nyheter                          |
| /190 | 1      | 1 152       | **0.09%** | 3.7 | sport/nyheter — *extremt* lågt   |
| /349 | 2      | 904         | 0.22%     | 4.6 | sport                            |
| /106 | 2      | 856         | 0.23%     | 4.5 | nyheter                          |
| /130 | 1      | 745         | 0.13%     | 4.7 | nyheter                          |
| /399 | 1      | 612         | 0.16%     | 3.9 | sport                            |
| /402 | 1      | 571         | 0.18%     | 3.1 | väder/region — pos 3!            |
| /364 | 1      | 553         | 0.18%     | 5.5 | sport                            |
| /160 | 1      | 542         | 0.18%     | 5.0 | nyheter                          |
| /374 | 2      | 532         | 0.38%     | 4.9 | sport                            |
| /339 | 1      | 514         | 0.19%     | 4.6 | sport                            |
| /601 | 1      | 477         | 0.21%     | 4.9 | TV-tablå                         |
| /104 | 1      | 470         | 0.21%     | 3.8 | nyheter                          |
| /365 | 1      | 264         | 0.38%     | 6.3 | sport                            |
| /360 | 1      | 278         | 0.36%     | 8.3 | sport (pos sämre — ej i filter)  |

**Kvantifiering av problemet:** dessa ~20 sidor har **~65 000 impressions / 28d**
sammanlagt vid pos 3–5. Vid sajt-snitt 0.88 % CTR skulle de generera ~570 clicks
istället för dagens ~180 — d.v.s. **~400 missade clicks/4v ≈ 5 000 clicks/år**
bara från denna kohort.

**Tydliga underkategorier:**

- **Sport-sidor 300-tals** (/300, /343, /336, /345, /344, /358, /339, /349, /364,
  /365, /374, /399) — största gruppen, dominerar topp-listan.
- **Nyhets-huvudsidor 100-tals** (/101, /104, /106, /127, /130, /160, /190) —
  pos 3.7–5.3 men 0.09–0.34 % CTR. /190 är värst (0.09 % @ pos 3.7).
- **Pos < 4 men noll-klick** (/402 @ 3.1, /104 @ 3.8, /127 @ 3.8, /190 @ 3.7,
  /336 @ 3.9, /345 @ 3.8, /399 @ 3.9) — dessa är *värst i klassen* eftersom
  positionen är så bra att SERP-snippet är hela förklaringen.

**Detta är inte ett /343-problem** — det är ett **systematiskt mönster** över
sport- och nyhets-sidotyper. /377 (målservice) sticker ut som undantaget som
bekräftar regeln: funktions-laddade queries → hög CTR.

**Mönster:** sport-sidor som rankar på *navigations-queries* ("text tv 343",
"343 text") där användaren är generisk-nyfiken — inte funktions-queries.
/377 rankar tvärtom på intent-laddade queries ("målservice", "lången-resultat",
"<sida> idag/nu") där användaren redan vet vad de vill ha.

**Hypotes:** låg CTR drivs inte av rankning utan av **SERP-snippet-presentation**:

1. Title/`<title>` på /343 är troligen generisk ("Text-TV sida 343 - texttv.nu")
   och konkurrerar med svt.se egna text-tv-sida som har högre brand-tillit.
2. Sidor som /377 har troligen mer beskrivande titel + snippet som matchar
   funktionsintent ("Målservice", "Resultat Lången") → tydligare värdeprop.
3. Sidor på 300-tal/400-tal som är *underrubrik-sidor* (delar av en serie)
   saknar troligen unika titlar och får generic snippet från sidcontent.

**Steg 1 i förslaget bör vara:** verifiera title/meta-description-tags som
genereras för /343, /336, /345 vs /377 — och jämför med vad Google faktiskt
visar i SERP (kolla `site:texttv.nu 343` i privat browser).

## Verifiering (2026-05-19)

Rotorsak bekräftad i koden + live HTML.

**Kodvägen:** `texttv.nu/codeigniter/application/views/header.php` rad 167-241
har en **whitelist** över sidor som får kurerad `<title>` + `<meta name="description">`:

```
100, 376, 377, 330, 551, 552, 383, 553, 560, 561, 571, 202
```

Allt utanför whitelisten faller till **auto-genereringen** rad 302-355:

- `<title>` = `trim($pages[0]->title)` — råa första raden från SVT:s sida
- `<meta name="description">` = första 200 tecken av `arr_contents[0]`, med
  "SVT Text {dag} {datum}"-prefix borttagen via `/\d{4}/`-regex

Live `<title>` (curl mot prod 2026-05-19):

| Sida | `<title>`                                                  | `<meta description>`         |
| ---- | ---------------------------------------------------------- | ---------------------------- |
| /377 | `377 - SVT Text TV`                                        | "På SVT Text TV 377 finns dagens sportresultat & målservice. ⚽️️ 377 – sportnördens bästa vän!" |
| /330 | `330 - SVT Text TV`                                        | "Resultatbörsen på SVT Text TV 330" |
| /202 | `202 - SVT Text TV - Börsen`                               | "Följ omsättningen för stockholmsbörsen varje dag på SVT Text TV sid 202..." |
| /300 | `300 Sport - SVT Text TV`                                  | **saknas**                   |
| /343 | `343 1 Sirius         8  7  1  0  22-9   22 - SVT Text TV` | **saknas**                   |
| /336 | `336 11/5  Tottenham   - Leeds         1-1 - SVT Text TV`  | **saknas**                   |
| /345 | `345 14/5  Ljungskile  - Sundsvall     1-0 - SVT Text TV`  | **saknas**                   |
| /344 | `344 11/5  Sirius      - Örgryte       2-0 - SVT Text TV`  | **saknas**                   |
| /190 | `190  - SVT Text TV`                                       | **saknas**                   |

**Två separata problem:**

1. **Inga sidor utanför whitelisten har `<meta name="description">`** —
   `if ($twitter_description)` på rad 361/371/383 gatear taggen, men auto-genen
   producerar i praktiken tom sträng för dessa sidor (sannolikt eter att
   prefix-regexen `/\d{4}/` slukar fel substring — det första 4-siffriga
   talet i sport-tabeller är ofta inte ett årtal utan ett spelresultat-värde).
2. **`<title>` är råinnehåll, inte sökoptimerad** — "343 1 Sirius 8 7 1 0
   22-9 22 - SVT Text TV" är obegripligt i SERP. Google ersätter ofta detta
   med egen heuristik (varför positionen ändå är 3-5), men det räcker inte
   för att övertyga klickaren.
3. **/190 har bara `<title>190  - SVT Text TV`** — `$pages[0]->title` är tom
   sträng. Sidan rankar pos 3.7 men har bokstavligt talet ingen value-prop.

**/300 är ett delvis-undantag:** `<title>300 Sport - SVT Text TV` är okej,
men ändå 0.45 % CTR — sannolikt för att description saknas och titeln inte
säger något konkret (vad finns på 300? Allsvenskan? Hockey? Resultat?).

**Fix-storlek:** lägga till ~15-20 nya entries i whitelisten (one-liner per sida)
löser hela kohorten. Estimerad vinst: ~5 000 clicks/år (se Bakgrund). Risk:
låg — ren content-addition, inga schema-ändringar. Värt en separat todo för
implementationen.

## Implementation (2026-05-19)

Whitelist utökad i `texttv.nu/codeigniter/application/views/header.php` (efter rad 215, befintlig `202`-entry). 18 nya entries — alla genererade av en sport-copywriter-subagent som mimickar tonen från befintliga 377/330/202/571.

**Nya kurerade sidor:**

| Sida | Title                                          | Tema                            |
| ---- | ---------------------------------------------- | ------------------------------- |
| 101  | 101 - SVT Text TV - Inrikes                    | Inrikes-index                   |
| 104  | 104 - SVT Text TV - Utrikes                    | Utrikes-index                   |
| 106  | 106 - SVT Text TV - Inrikes nyhet              | Dagens topp-inrikesartikel      |
| 127  | 127 - SVT Text TV - Börsindex                  | Börsindex Tokyo/OMX/DAX/Dow     |
| 130  | 130 - SVT Text TV - Utrikes nyhet              | Dagens topp-utrikesartikel      |
| 300  | 300 - SVT Text TV - Sport                      | Sport-startsida ⚽️              |
| 336  | 336 - SVT Text TV - Premier League             | Premier League ⚽️               |
| 339  | 339 - SVT Text TV - La Liga                    | La Liga / Spanien ⚽️            |
| 343  | 343 - SVT Text TV - Allsvenskan tabell         | Allsvenskan tabell ⚽️           |
| 344  | 344 - SVT Text TV - Allsvenskan resultat       | Allsvenskan matcher ⚽️          |
| 345  | 345 - SVT Text TV - Superettan                 | Superettan ⚽️                   |
| 349  | 349 - SVT Text TV - Damallsvenskan             | Damallsvenskan ⚽️               |
| 358  | 358 - SVT Text TV - SHL tabell                 | SHL ishockey 🏒                 |
| 364  | 364 - SVT Text TV - Hockeyettan slutspel       | Hockeyettan slutspel 🏒         |
| 365  | 365 - SVT Text TV - SHL poängliga              | SHL toppscorers 🏒              |
| 374  | 374 - SVT Text TV - Beijer Hockey Games        | Landslagsturnering ishockey 🏒  |
| 399  | 399 - SVT Text TV - TV-tider sport             | Sport på SVT (program-schema)   |
| 402  | 402 - SVT Text TV - Temperaturer Sverige       | Väder/temperaturer              |
| 601  | 601 - SVT Text TV - TV-tablå SVT1              | TV-tablå SVT1                   |

**/160, /190 hoppades över** — sidorna är just nu "Sidan ej i sändning" hos SVT.

**Verifiering:**
- `php -l` OK på filen ✓
- Commit 5f9c6ad pushad till main 2026-05-19 ✓
- GitHub Actions deploy (run 26086488075) success 29s ✓
- Live curl mot prod: 19/19 sidor returnerar kurerad `<title>` + `<meta name="description">` ✓ (cache-bust krävdes — sajten cachar HTML per page-num, vilket innebär att Google-bot får nya versioner vid nästa crawl)

**Att göra härnäst:**

1. ~~Användare reviewar diff:en~~ ✓
2. ~~Commit + push → deploy~~ ✓
3. ~~Live-verifiera titles på alla 18 sidor~~ ✓ (19 sidor: 18 enligt plan + /601 räknades med)
4. **2026-06-18** — 30d GSC-mätning (mål: kohort-CTR ~0.27 % → ~0.88 %)
5. **2026-07-18** — 60d slutmätning + ev. restpopulation-todo

## Förslag

Stegvis analys via `mcp-gsc`:

1. Hämta GSC-data för `sc-domain:texttv.nu` med dimension `page` — sortera på impressions desc, filtrera CTR < snittet.
2. För `/343` specifikt: hämta `query`-dimension för att se vilka söktermer som triggar, jämför med titel/snippet som SVT/Google visar.
3. Kategorisera lågpresterande sidor: är de samma sidotyp? Samma intervall (300-tals, 600-tals)?
4. Jämför med högpresterande motsvarigheter (t.ex. /377 har bra CTR enligt 7d-data — vad gör den annorlunda?).

Möjliga rotorsaker att utesluta:

- Titel/`<title>`-tag matchar inte query-intentet
- Sidan rankar på fel queries (intent mismatch)
- Snippet/description saknas eller är generisk
- Konkurrens från svt.se egen text-tv-sida vinner CTR trots lägre position
- Sidan visas för queries där användaren vill ha annat (t.ex. live-TV, inte text-tv)

## Risker

Låg — ren analys, inga kodändringar i steg 1. Om åtgärd krävs (titel-rewrite, structured data) kommer separat todo per fix.

## Confidence

medel — `mcp-gsc` ger data direkt, men "dålig CTR" behöver kvantifieras (vs vad — sidans egen baseline, sajt-snittet, eller branschsnitt?). Bakgrund-sektionen behöver fyllas i av användaren.
