# Prestanda/SEO-baselines

Lagrade snapshot:s av Lighthouse-resultat mot prod (`https://texttv.nu`). Används
för att kunna jämföra "innan vs efter" när vi gör perf/SEO-fixar.

## Hur baselinen tas

Per datum-katalog (`baselines/YYYY-MM-DD/`):

- **Tool:** Lighthouse CLI via `npx lighthouse`
- **Form factor:** mobile (default — slow 4G, 4× CPU throttle)
- **Headless Chrome:** `--headless=new --no-sandbox`
- **Antal körningar:** 3 per URL → ta median av perf-score och CWV i sammanställningen (Lighthouse är brusig, ±5–10 mellan körningar är normalt)
- **Output:** både `.json` (för diff/scripting) och `.html` (för läsbar rapport)
- **URLer som mäts:** representativa sidtyper
  - `root` — `/` (startpage-composite: 100,300,401,101-105)
  - `p100` — `/100` (enskild nyhetssida, ofta high-cache-hit)
  - `p300` — `/300` (sport-startsida)
  - `p700` — `/700` (innehåll/index)
  - `arkiv` — en arkivsida `/{n}/{slug}-{id}` (äldre rendering, längre URL)

Reproducera baseline:

```bash
cd baselines/<datum>
./run.sh
```

Scriptet är idempotent — befintliga `.json`-filer hoppas över.

## Caveats

- **Lab data, inte fältdata.** Lighthouse simulerar throttling. För riktiga
  besökares Core Web Vitals — kolla **Search Console → Core Web Vitals**
  (där sajten är verifierad). Lighthouse fångar regressioner; SC fångar
  upplevd verklighet.
- **Varians.** En enskild körning kan svänga ±10. Lita på median av 3+, och
  jämför helst flera datapunkter om förändringen är liten.
- **Maskin-/nätverkskontext.** Körs från `bonnymacmini` lokalt. Om vi
  senare flyttar till CI kommer absoluta tal att skifta — då måste vi
  ta en ny baseline i CI-miljön innan vi börjar jämföra där.

## Datum-kataloger

- [`2026-05-19/`](2026-05-19/) — första baselinen. Pre-fixar. Commit
  [`a85ba01`](https://github.com/parnerell/texttv.nu/commit/a85ba01).
  Lighthouse 12.8.2, Chrome 148.0.7778.168.
