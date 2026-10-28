**Status:** aktiv
**Senast uppdaterad:** 2026-10-28

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
