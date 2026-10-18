**Status:** klar 2026-10-18
**Senast uppdaterad:** 2026-10-18

# Todo #20 — "Tidigare i dag" på sportsidorna

Pärs fråga 2026-10-18: hur gör vi 377 och andra välbesökta sportsidor mer
intressanta? Historiska resultat direkt? Bläddra bland dagens uppdateringar?

## Underlag

Sökningarna på 377 är nästan bara navigering (GSC 2026-09-18–10-15):
"text tv 377" 2 435 klick, "svt text 377" 1 671, "text377" 1 348, "377"
1 218. "text tv 377 idag" 19 klick; "igår" saknas bland de 40 största.
Besökaren vill se läget nu. `/377/igar` och datumsidorna (#16) mäts
2026-11-14 innan mer historik byggs.

## Förslag och beslut

1. **Markera vad som ändrats sedan förra besöket**, och uppdatera sidan på
   plats. Pär: för avancerat, inte nu.
2. **"Tidigare i dag": länkar till dagens äldre versioner under sidan.**
   Gjort, se nedan.
3. **Följ ditt lag** (markera ett lag på 377 och 330). Pär: inte nu.

## Gjort

Raden "Tidigare i dag 12:30 09:12 06:40" under sidorna 300–399, mellan
"Uppdaterad … Dela →" och gårdagens länkar. Högst 8 versioner, nyaste först.
Versionen som visas räknas inte. Länkarna går till arkivsidorna för
respektive version.

- `Sida::_today_versions()` i `controllers/sida.php`, med
  `get_page_versions_for_day()` (samma fråga som dagsarkivet, index på
  `page_num, date_added`). Dygnet i svensk tid.
- Bara enskilda sidor som sänds. Inte i `api/get_html` (appen), som också
  laddar `pages_inner_output_current.php`: datan sätts bara av
  `Sida::visa()`.
- CSS `.tt-tidigare` i `css/tt.css`.

## Följ upp

Klick från raden: arkivsidornas trafik med referer `/377` (och övriga
3xx) i `access.log`, tillsammans med mätningen av #16 2026-11-14.
