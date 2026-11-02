**Status:** väntar — deployad 2026-11-01 (`100371b`): "ny" i cyan i Senast besökta, `har_prick` på `search_select` (GA4 "Ny i Senast besökta"); mäts
**Senast uppdaterad:** 2026-11-01

# #32 En prick när en sida har uppdaterats sedan du såg den

Ny 2026-10-28, från inboxen (Obsidian, triage 2026-10-27).

## Idén

Visa en prick vid sidor som har uppdaterats sedan besökaren senast såg dem,
men bara för sidor man själv har besökt. 101–105 uppdateras hela tiden, så en
prick på alla sidor skulle alltid vara tänd och inte säga något.

Bästa platsen är "Senast besökta" i "Gå till sida", den sektion som får flest
val (103 av 254 `search_select`, GA4 17–27 okt). Sidomenyn kan komma sedan.

Målet är fler sidvisningar per besök, som har sjunkit från 5,9 till 5,6.

## Det som finns idag

- `texttv.nu/js/tt-sok.js` sparar senast besökta i `localStorage` under
  `tt-senaste` (högst 4, `SENASTE_MAX`), som `{ num, namn, url }`. Där saknas
  en tidpunkt för när sidan visades.
- `/api/last_updated` duger inte som källa: den täcker bara 100–200, 300–329
  och 401 (`controllers/api.php`, `last_updated()`). Senast besökta kan vara
  vilken sida som helst, till exempel 377 eller 500.
- `/api/updated/{sidor}/{tidpunkt}` (`Api::updated`) tar kommalistor. Alla
  fyra senaste ryms i ett anrop. Appen använder samma endpoint, så den får
  inte ändras (se appkontrollen i `CLAUDE.md`). Den ska bara anropas, inte
  ändras. Kontrollera svarsformatet (`update_available`, `res`) innan.

## Förslag

1. Spara `sedd: <unix-tid>` i varje post i `tt-senaste` när sidan visas.
   Gamla poster utan tid får ingen prick.
2. När "Gå till sida" öppnas med tomt fält: ett anrop till `/api/updated` med
   de senaste sidorna och den äldsta `sedd`. Jämför per sida i klienten, eller
   gör ett anrop per sida om svaret inte säger vilken sida som uppdaterats.
3. Prick i Text-TV-stil på raden. Ingen prick på sidan man står på.
4. GA4: skicka med `har_prick` på `search_select`, så att val med och utan
   prick kan jämföras.

## Mät

`search_select` från "Senast besökta" med och utan prick, och sidvisningar per
besök, två veckor före och efter.

## Byggt 2026-11-01

`js/tt-sok.js` och `css/tt.css`, inget på servern. `/api/updated` är oförändrad.

- `tt-senaste`-posterna får `sedd`, sidans `added` från `<body data-pages>`.
  Det är versionens tid i databasen, samma tid som "Ny version av sidan" i
  `js/scripts.js` frågar med, och inte klientens klocka. Besöker man sidan
  igen får den en ny `sedd` och pricken försvinner.
- När listan öppnas första gången på en sidvisning: ett anrop till
  `/api/updated/{num}/{sedd}?app=texttvnu.web` per post (högst fyra, i
  praktiken tre eftersom sidan man står på inte räknas). Ett gemensamt anrop
  går inte. `GROUP BY page_num` ger en godtycklig rad per sida, så svaret
  säger inte om just den sidan är nyare än när den sågs.
- Ingen prick på nyheter med permalänk: länken går till versionen man läste,
  och en ny version på numret är en annan nyhet. Inte heller på gamla poster
  utan `sedd`, eller på sidan man står på.
- Markeringen är ordet "ny" i cyan, 1ch efter namnet och svart på markerad
  rad. Först byggdes en gul ruta, men UX- och AD-granskningen samma dag bytte
  den: gult är för länkar och handlingar i designsystemet (#18), cyan för
  metadata, Text TV markerar med ord och inte ikoner, och en ruta förklarar
  inte sig själv på pekskärm (`title` syns inte). Skärmläsaren läser
  "401 Väder ny". En tid ("12:04") vore bäst, men `/api/updated` ger en
  godtycklig nyare rad per sida (`GROUP BY page_num`), inte den senaste.
- `SENASTE_MAX` 4 → 5: sidan man står på sparas men visas inte, så nu syns
  fyra i stället för tre.
- `search_select` har fått `har_prick: "ja" | "nej"`.

Testat i Playwright mot den lokala stacken (även 390 px bredd): sidor med
gammal `sedd` fick "ny", 377 (aktuell) och poster utan `sedd` fick inget,
fyra senast besökta syns, och valet skickade `har_prick: "ja"`.

**Deployad 2026-11-01** (`100371b`), JS och CSS kontrollerade på prod.

**GA4:** `har_prick` registrerad 2026-11-01 som anpassad dimension
(händelseomfattning) "Ny i Senast besökta" i 382798575, i Data API
`customEvent:har_prick`. Gäller data från och med registreringen.

**Kvar:** mät två veckor före och efter enligt Mät ovan (uppföljning
2026-11-15).
