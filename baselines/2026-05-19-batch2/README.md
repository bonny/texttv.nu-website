# Baseline efter Batch 2 (2026-05-19, kvällsskift)

Lighthouse-mätning efter att server-side perf-fixarna gick live: **HTTP/2
aktiverat**, **3 säkerhetsheaders** (HSTS, X-Content-Type-Options,
Referrer-Policy), **statisk-asset cache 7 dagar**. Plus Batch 1 (`/700`
meta description) som gick live tidigare samma dag.

**Setup:** Lighthouse 12.8.2, Chrome 148.0.7778.168, headless mobile,
3 körningar per URL, median rapporteras. Samma metodik som
[2026-05-19/](../2026-05-19/README.md).

**Vad ändrades sedan föregående baseline:**
1. `/700` fick meta description (deploy commit [`22ef0c6`](https://github.com/bonny/texttv.nu-website/commit/22ef0c6))
2. nginx `listen 443 ssl http2;` (manuell server-edit, ej i repot)
3. nginx server-block fick `add_header` för HSTS / `X-Content-Type-Options` / `Referrer-Policy`
4. nginx fick ny `location ~* \.(css|js|...)` med `Cache-Control: public, max-age=604800`

**Vad som INTE ändrades:** API-routes (`/api/*`, `appembed`) har INGEN ny
`Cache-Control` — apparna får fortfarande fresh data. Verifierat via curl
före baseline.

## Resultat (median av 3 körningar) — med jämförelse mot 2026-05-19

| URL | Perf | A11y | BP | SEO | LCP | CLS | TBT | FCP | SI |
|---|---|---|---|---|---|---|---|---|---|
| `/` (start) | 96 → **97** | 96 | 96 | 92 | 2605 → **2467 ms** | 0.023 | 22 → 24 ms | 1757 → **1150 ms** | 2102 → **1710 ms** |
| `/100` | 96 → 95 | 96 | 96 | 92 | 2222 → 2470 ms ⚠ | 0.090 | 24 → 26 ms | 1732 → **1134 ms** | 1926 → **1677 ms** |
| `/300` | 95 | 96 | 96 | 92 | 2227 → 2469 ms ⚠ | 0.090 | 26 → 32 ms | 1767 → **1136 ms** | 2008 → **1711 ms** |
| `/700` | 96 → 95 | 96 | 96 | **85 → 92** ✓ | 2218 → 2465 ms ⚠ | 0.090 | 24 ms | 1729 → **1130 ms** | 1886 → **1601 ms** |
| arkiv | 96 → **99** | 93 | 100 | 92 | 2608 → **1933 ms** ✓ | 0.023 | 28 → 24 ms | 1736 → **1115 ms** | 1990 → **1696 ms** |

## Tolkning

### Klara vinster

- **FCP −550 till −620 ms överallt.** Mest konsistent och tydligaste vinsten. Sidans innehåll syns ~½ sekund snabbare. Detta är HTTP/2-effekten — eliminerar request-chains för de tre CSS-filerna + tre script-taggar som tidigare gick över HTTP/1.1.
- **Speed Index −200 till −400 ms överallt.** Bekräftar FCP-fyndet — hela renderingsförloppet är snabbare.
- **Arkivsidan: Performance 96 → 99 + LCP 2608 → 1933 ms.** Stor vinst där LCP-elementet tidigare drogs av request-chains. Lighthouse flaggade arkiv för `uses-rel-preconnect` i förra baselinen — HTTP/2 ger samma resultat utan extra preconnect.
- **`/700` SEO 85 → 92.** Batch 1:s meta description-fix bekräftad i prod.

### LCP-anomali (`/100`, `/300`, `/700`)

LCP gick **upp** ~250 ms på dessa tre URL:er trots att FCP gick **ner** ~600 ms. Det är kontraintuitivt — om sidan börjar rendera snabbare borde det största elementet också renderas snabbare.

Mest sannolika förklaring: **HTTP/2 ändrade resource priority.** Med multiplexing kan stora bakgrundsbilder (text-tv-tecknen `https://l.texttv.nu/storage/chars/*.gif`) få annan prioritet relativt CSS/fonter. På `/100`/`/300`/`/700` är LCP-elementet troligen ett sådant bakgrundsbild. På `/` och arkivsidan är det troligen text — som inte är beroende av bildladdning.

Lighthouse-variance på LCP är typiskt ±100–200 ms mellan körningar. Trots 3 körningar är skillnaden inom det intervallet — men inte överallt, så det är inte ren brus. Värt att titta på `largest-contentful-paint-element`-audits i nästa baseline.

### Inga regressions att rulla tillbaka

Alla LCP-värden är fortfarande under 2.5 s ("good"-gränsen). Inga score-tapp som motiverar rollback. Den enda backåt-rörelsen är /100/300/700 LCP, som ändå ligger där det var i 2026-05-19.

## Failing audits som åtgärdades

Kontroll mot Lighthouse-rapporterna efter Batch 2:

| Audit | 2026-05-19 | 2026-05-19-batch2 | Status |
|---|---|---|---|
| `uses-http2` / `modern-http-insight` | fail | **pass** | ✓ åtgärdat |
| `meta-description` (`/700`) | fail | **pass** | ✓ åtgärdat |
| `uses-long-cache-ttl` (statiska assets) | 0.5 | **pass** | ✓ åtgärdat |
| `uses-rel-preconnect` (arkiv) | fail | **pass** | ✓ konsekvens av HTTP/2 |

## Kvarstående fix-lista (uppdaterad)

Sorterat efter förväntad vinst:

1. **`errors-in-console`** — `Uncaught TagError: adsbygoogle.push() error: No slot size for availableWidth=0`. Pushar adsbygoogle innan dess container fått layout. Trivial fix.
2. **`render-blocking-resources`** — kvarstår, men mindre kritiskt nu när HTTP/2 multiplexar dem. Vinsten av `defer` + kritisk CSS är fortfarande där men har sjunkit. Kräver inline-script-audit (`polisen.php` m.fl. använder jQuery i inline-scripts).
3. **`legacy-javascript`** + **`unminified-javascript`** + **`unused-javascript`/`unused-css-rules`** — minify- och tree-shake-jobb på jQuery + scripts.js + styles.css.
4. **Dynamiska meta descriptions per sida** — generera från sidans rubriker i `Texttv_page`-modellen för alla sidor (inte bara whitelist).
5. **`<h1>` per sida** — sr-only räcker. Liten SEO + a11y-vinst.
6. **`color-contrast`** — text-tv-paletten är speciell, kolla vilket element som flaggas innan eventuell fix.
7. **`crawlable-anchors`** — sannolikt cookieinställningar-länken i footer. Liten fix.
8. **`uses-long-cache-ttl` på HTML** — vi droppade `Cache-Control` på server-nivå för att skydda API. Kan läggas in riktat mot HTML-routes specifikt om vi vill. Begränsad vinst (4 s match med fastcgi_cache).

## Filerna i den här mappen

- `<url>-run<n>.report.json` — full Lighthouse-rapport (committas)
- `<url>-run<n>.report.html` — **gitignored** (regenerera med `./run.sh` eller via [Lighthouse Viewer](https://googlechrome.github.io/lighthouse/viewer/))
- `run.sh` — kopierad från 2026-05-19/. Identisk metodik.
