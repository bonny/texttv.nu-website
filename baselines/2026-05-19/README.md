# Baseline 2026-05-19

Första prestanda/SEO-baselinen för `texttv.nu`. Tas innan några perf/SEO-fixar
har gjorts → används som referens för "innan vs efter".

**Setup:** Lighthouse 12.8.2, Chrome 148.0.7778.168, headless mobile (slow 4G,
4× CPU throttle), 3 körningar per URL, median rapporteras nedan. Repo-state
commit [`a85ba01`](https://github.com/parnerell/texttv.nu/commit/a85ba01).

## Resultat (median av 3 körningar)

| URL | Perf | A11y | BP | SEO | LCP | CLS | TBT | FCP | SI |
|---|---|---|---|---|---|---|---|---|---|
| `/` (start) | 96 | 96 | 96 | 92 | 2 605 ms | 0.023 | 22 ms | 1 757 ms | 2 102 ms |
| `/100` | 96 | 96 | 96 | 92 | 2 222 ms | 0.090 | 24 ms | 1 732 ms | 1 926 ms |
| `/300` | 95 | 96 | 96 | 92 | 2 227 ms | 0.090 | 26 ms | 1 767 ms | 2 008 ms |
| `/700` | 96 | 96 | 96 | **85** | 2 218 ms | 0.090 | 24 ms | 1 729 ms | 1 886 ms |
| `/119/man-dod-...` (arkiv) | 96 | 93 | 100 | 92 | **2 608 ms** | 0.023 | 28 ms | 1 736 ms | 1 990 ms |

**Tolkning:**
- Performance 95–96 över hela linjen — siten är redan i mycket bra form.
- LCP är på Core-Web-Vitals-gränsen (mål <2,5 s). Startpage och arkivsida är
  precis över. Det är det första som riskerar att slå om sidan blir tyngre.
- CLS 0.02–0.09 — alla under 0,1 ("good"). `/100`/`/300`/`/700` ligger
  närmre gränsen än `/` och arkiv.
- TBT 22–28 ms — utmärkt; INP-risk är låg.
- SEO `/700` sticker ut neråt (**85**) p.g.a. saknad meta description.

## Failing audits — gemensamma över alla 5 URLer

Det här är listan över saker som vi vill jobba bort. Numren är scores: `0` =
hårt fail, `0.5` = partiellt, `1` = passerar.

**Performance — render & laddning**
- `[0]` **`render-blocking-resources`** / `render-blocking-insight` — CSS i `<head>` och `<script>`-taggar utan `defer` i `<body>`. Matchar manuella auditen.
- `[0]` **`uses-http2`** / `modern-http-insight` — siten serveras över **HTTP/1.1**. Detta är förmodligen den största enskilda vinsten. Tre stylesheets och tre script-taggar tar var sin round-trip; H/2 multiplexar dem.
- `[0]` **`cache-insight`** / `[0.5]` `uses-long-cache-ttl` — statiska assets (CSS, JS, fonter) saknar långa cache-headers eller har för korta. Plus HTML utan `Cache-Control` (matchar manuella auditen).
- `[0.5]` **`font-display-insight`** — Ubuntu Mono saknar `font-display: swap`. Orsakar FOIT/synlig fördröjning.
- `[0.5]` **`legacy-javascript`** — transpilerad/legacy-JS serveras till moderna browsers (förmodligen jQuery + polyfills).
- `[0.5]` **`unminified-javascript`** — JS är inte minifierad i prod.
- `[0]/[0.5]` **`unused-javascript`** / **`unused-css-rules`** — död kod skickas över nätet.
- `[0.5]` **`dom-size`** — för många DOM-noder (förmodligen character-bildernas wrapper-spans).
- `[0]` **`largest-contentful-paint-element`** — LCP-elementet är inte preloaded.
- `[0]` **`network-dependency-tree-insight`** — chains av request blockerar (CSS → fonter → render).

**Best Practices**
- `[0]` **`errors-in-console`** — browser-errors loggas vid pageload. Behöver undersökas i devtools console.

**Accessibility**
- `[0]` **`color-contrast`** — text/bakgrund-kontrasten understiger WCAG AA (4.5:1) på minst en plats.

**SEO**
- `[0]` **`crawlable-anchors`** — något kan inte crawlas av Google (förmodligen JS-injicerade länkar eller `onclick`-anchors). Värt att titta på.

## URL-specifika failing audits

- `/700` — `[0]` **`meta-description`**: saknar `<meta name="description">`. Detta är **enda** anledningen till SEO 85 (övriga URL:er har generisk men närvarande description).
- arkiv `/119/...` — `[0]` **`list`**: någon `<ul>` innehåller andra element än `<li>`. `[0]` **`uses-rel-preconnect`**: explicit förslag att preconnect:a tredjeparter. **`largest-contentful-paint: 0.12`** — LCP är värst här.
- `/` och `/700` — saknar `uses-rel-preconnect` warning, men startpage:n hämtar samtidigt 5 sidors innehåll så ändå inte snabbare än enskilda sidor.

## Prioriterad fix-lista (uppdaterad mot Lighthouse-data)

1. **Aktivera HTTP/2 i nginx.** Enskilt största fyndet. Kräver bara nginx-konfig om TLS redan termineras där (vilket den gör — `server.md`). Bör tas före allt annat eftersom flera andra audits (render-blocking, network-dependency-tree) påverkas direkt.
2. **`defer` på `<script>`-taggarna i `footer.php`** — minutverk, bekräftat av `render-blocking-resources`.
3. **`Cache-Control` på HTML + längre TTL på statiska assets** — bekräftat av `cache-insight` + `uses-long-cache-ttl`. Två separata fixar i nginx.
4. **`font-display: swap` i `/css/fonts.css`** + (helst) self-hosta Ubuntu Mono.
5. **`<meta name="description">` på `/700`** — låghängande SEO-frukt, +7 poäng direkt.
6. **Generera dynamisk description per sida** — större jobb, lyfter alla sidor.
7. **Undersök console-errors** — öppna `/100` i devtools, läs felmeddelandena.
8. **`crawlable-anchors`** — kolla vilka länkar Lighthouse flaggar (finns i HTML-rapporten).
9. **`color-contrast`** — text-tv-paletten är speciell; kolla vad som flaggas innan reflexmässig fix.
10. **Minifiera + ta bort oanvänd JS/CSS** — `simplify`-skillen eller manuell genomgång av `scripts.js`/`styles.css`.

## Filerna i den här mappen

- `<url>-run<n>.report.json` — full Lighthouse-rapport, för diff/scripting (committas)
- `<url>-run<n>.report.html` — läsbar rapport (**gitignored**). Återskapa lokalt genom att köra `./run.sh` igen, eller dra-och-släpp en `.report.json` i [Lighthouse Viewer](https://googlechrome.github.io/lighthouse/viewer/).
- `run.sh` — reproducera baselinen: `./run.sh` (idempotent, hoppar över befintliga `.report.json`-filer)

## Nästa baseline

Kör om `./run.sh` efter varje större fix-batch och skapa en ny datum-katalog
(`baselines/YYYY-MM-DD/`) med samma struktur. Jämför sedan median-tabellen
mot den här.
