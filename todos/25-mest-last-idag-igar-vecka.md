# #25 Mest läst: växla mellan idag, igår och senaste 7 dagarna

Ny 2026-10-19, från inboxen (Obsidian).

## Önskemålet

> Denna vy är fin och jag gillar den, men kan man ha flikar eller så så man
> kan växla mellan "igår" och "idag"? När man läser mest läst igår blir iaf jag
> automatiskt lite sugen på även "idag". Kanske t.om. utöka med "denna vecka"
> eller "senaste 7 dagarna"

Gäller rutan "Mest läst igår" bredvid 100 på startsidan
(`views/day_archive_link.php`, data från `yesterday_top_news()` i
`texttv_helper.php`, åtta rader enligt `Sida::STARTPAGE_YESTERDAY_COUNT`).

## Hur det går att göra

- **Igår** finns: en fil per dygn (`startpage-news-{datum}.json`), räknas
  fram en gång.
- **Idag** finns redan som data: `most_read_pages_cached("news", idag)` med
  60 s cache, samma som `/api/most_read/news` och `/nyheter/idag`. Kostar en
  omräkning per minut, inte per sidvisning. Obs #12: omräkningen tar över 3 s
  på kvällar; den görs redan för appen och `/nyheter/idag`, så startsidan
  lägger inte till någon ny fråga, bara fler läsningar av filen.
- **Senaste 7 dagarna** kan räknas ur de sju dygnsfilerna (cachen sparar 14
  dagar, `page_actions` 10): summera visningar per nyhet. Ingen ny tung fråga,
  men en ny sammanslagning som också måste matcha samma nyhet på olika
  sidnummer och dagar (rubriken).
- **Flikarna:** server-renderade listor och tre knappar med
  `role="tablist"`, en liten JS som visar en lista i taget. Utan JS syns
  "Igår" och länkar till `/nyheter/idag` och `/nyheter/igar`. Valet sparas i
  `localStorage` (bara en bekvämlighet). Startsidan cachas i nginx, så alla
  listor måste finnas i HTML:en.

## Förslag

1. Idag + Igår först (liten ändring, ingen ny data). Igår som förval, så att
   rutan ser ut som idag tills man trycker.
2. Mät klick på flikarna (GA4-händelse) och klick från listorna till
   arkivsidorna, mot uppföljningen i #16 (2026-10-24).
3. Senaste 7 dagarna sedan, om "Idag" används.

## Status

- [ ] Beslut från Pär: bygga steg 1?
