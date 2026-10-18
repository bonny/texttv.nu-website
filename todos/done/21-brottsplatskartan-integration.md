**Status:** klar 2026-10-18
**Senast uppdaterad:** 2026-10-18

# Todo #21 — Tydligare koppling till Brottsplatskartan

Pärs fråga 2026-10-18: kan integrationen mot Brottsplatskartan bli tydligare
och mer integrerad? "Kör på bara, fritt fram!"

## Läget före

- `/sida/polisen` ("Omnämnt av Polisen" i footern) hämtade
  `brottsplatskartan.se/api/eventsInMedia?media=texttv`. Senaste händelsen i
  flödet var från **2025-12-04**: endpointen läste bara de gamla handinlagda
  länkarna (`newsarticles`), inte Brottsplatskartans AI-matchning av Text
  TV-nyheter mot polishändelser (`crime_event_news`, todo #82/#110 där), som
  pågår varje dag.
- Sidan visade händelserna men inte vilken Text TV-nyhet de hörde till.

## Gjort

**Brottsplatskartan** (todo #127 där, `6fb4e81c`):

- `/api/eventsInMedia` läser även AI-matchningarna (säkerhet hög/medel).
- Ny `/api/texttvHandelser?dagar=14`: händelser per Text TV-nyhet med
  normaliserad rubrik som nyckel.

**texttv.nu:**

- **"Polisen rapporterade"** under levande nyhetssidor (100–199) och
  arkiverade nyheter när nyheten har en matchad händelse: typ, plats, tid och
  länk till kartan. `views/polis_handelser.php`.
- **"Karta →"** efter rubriken i dagslistorna (`/nyheter/igar` med flera).
- **`/sida/polisen`** heter "Polishändelser i Text TV" (även i footern), och
  varje händelse visar nu Text TV-nyheterna den hör till.
- Uppslag på rubrik: `bpk_titel_nyckel()` gör samma normalisering som
  Brottsplatskartans `NewsArticle::normalizeTitle()`. Samma nyhet har många
  versioner och adresser på texttv.nu, så id eller adress går inte att
  använda.
- Hämtning: `bpk_hamta_json()` med egen filcache i tempkatalogen (10 min,
  2 min vid fel, högst 3 s väntan). CI:s cachedrivrutin gav
  deprecation-varningar på PHP 8.2 och används inte längre av `/sida/polisen`.
- Länkarna till Brottsplatskartan har `utm_source=texttv.nu` och
  `utm_medium=polisruta|dagslista|polisen`.

## Efter kodgranskningen samma dag

- **Lås vid hämtningen** (`bpk_hamta_json`): bara en worker hämtar åt
  gången, de andra får gammal data direkt. Förut kunde alla php-fpm-workers
  (max 10) hänga på ett långsamt Brottsplatskartan när cachen gick ut. curl
  med 1 s anslutning och 2 s totalt. Vid fel behålls den senaste goda datan.
- **Tidsvillkor:** en händelse visas bara om sidans version ligger mellan ett
  dygn före och tre dygn efter nyhetens första version hos Brottsplatskartan.
  Utan det fick en arkivsida från 2019 med en återkommande rubrik ("Man
  skjuten i Malmö") en händelse från i dag.
- **Kartbild** i rutan och på `/sida/polisen` (`bild` i `/api/texttvHandelser`).
- Rutan står **efter annonsen** "Fler nyheter efter annonsen", så att
  kartbilden inte trycker ner den.
- `/sida/polisen` cachas 30 min; Brottsplatskartans `eventsInMedia` 15 min och
  escapar `%`/`_` (`22868113` där).
- "Tidigare i dag" hämtar 9 versioner i stället för upp till 500.

## Följ upp

- GA4 för brottsplatskartan.se (property 305258979): sessioner med
  `utm_source=texttv.nu` per `utm_medium`, 2026-11-14.
- Matchningarna gäller bara nyheter som Brottsplatskartan har hämtat (mest
  lästa och senast uppdaterade, var femte minut). En nyhet som aldrig var
  där får ingen ruta.
