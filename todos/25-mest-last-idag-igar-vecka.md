**Status:** aktiv — deployat 2026-10-19, mät 2026-10-24
**Senast uppdaterad:** 2026-10-20

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

## Granskning 2026-10-19 (SEO, UX, art director)

Sammanvägt, så som steg 1 ska byggas:

- **Utseende (AD, alternativ A):** bandet heter "Mest läst" och flikarna
  sitter i bandet till höger: `██ Mest läst      Idag [Igår] 7 dagar ██`.
  Aktiv flik inverterad (vit bakgrund, blå text), inaktiva vit text på blått
  utan understrykning, hover/fokus gul med svart text och vit outline som
  `.tt-link`. Gult betyder handling (#18), så inga gula eller färgade
  fastext-flikar (alternativ B avfärdat). Klasser `.tt-band--flikar`,
  `.tt-flikar`, `.tt-flik`. Flikarna minst 44 px höga (bandet växer ~12 px på
  desktop, eller osynlig `::after` som klickyta).
- **Knappar, inte ARIA-tabs (UX):** `button` med `aria-pressed` och
  `aria-controls`. WAI-ARIA-tabs byter med ← →, som redan byter sida
  (`js/scripts.js` rad 113). Tab/Enter räcker, och pilarna behåller sin
  funktion.
- **Alla listor i HTML:en (SEO + cache):** panelerna server-renderade med
  vanliga `<a href>` till permalänkarna, de dolda med `hidden`. Ingen fetch,
  inga flik-URL:er (`?flik=`, `#idag`), ingen strukturerad data för rutan
  (dagssidorna har `ItemList`). Länktexten är rubriken, som nu.
- **Utan JS:** som idag, "Mest läst igår" och flikarna `hidden`.
- **Förval i JS:** Igår före kl 10 (Europe/Stockholm), sedan Idag. Ett val
  användaren själv gjort sparas i `localStorage` (try/catch) och går före.
  Startsidan cachas i nginx, så valet kan inte göras på servern.
- **Samma höjd:** lika många rader i varje flik (5 på mobil, 8 från
  1180 px), och en min-höjd, så att sidorna bredvid och under inte hoppar.
  Idag med färre än ~3 rader: visa det som finns plus "Fler läses under
  dagen → Dagens nyheter".
- **Länkraden följer fliken:** Idag → `/nyheter/idag`, `/sport/idag`; Igår →
  som nu. Raden är kvar synlig (den ger länkkraft till dagssidorna).
- **Mätning:** GA4 `most_read_tab` `{tab, default}` vid byte och
  `select_content` `{tab, position}` vid klick i listan.
- **7 dagar (steg 3):** som flik är det ok för SEO, med veckodag i
  `tt-list__meta`. Ingen egen indexerbar `/nyheter/vecka` förrän GSC eller
  klicken visar efterfrågan (ingen veckofråga syns i #16; GSC-sökningen på
  "vecka" inte gjord).

## Status

- [x] Steg 1 deployat 2026-10-19: flikarna Idag och Igår (se git log).
- [x] 7 dagar deployat samma kväll, på Pärs begäran direkt i stället för
  efter mätningen. `week_top_news()` summerar visningarna per nyhet över
  sju dygn (idag inräknat), cache 10 min. En sida matchas mot sin dags och
  föregående dags nyheter. Passerade dygn läses med
  `most_read_pages_cached()`, som inte räknar om ett avslutat dygn varje
  timme (#12). Veckodag i listan inte gjord.
- [x] Rättelser efter kodgranskningen (`9d98327`): dagens lista har en egen
  cachefil (`idag-…`), annars hade "Igår" visat dagens ögonblicksbild nästa
  dag.
- [x] `/377/igar` tillbaka i Idag-raden 2026-10-20 (se nedan).
- [ ] Mät 2026-10-24 med #16: `most_read_tab` och `select_content` i GA4.

## Första dygnet (GA4, 2026-10-19 20:14 – 10-20)

- `most_read_tab`: 43 byten från 3 användare på söndagskvällen (troligen
  Pärs egna tester), 6 från 2 användare på tisdagen.
- `select_content`: 10 + 11 klick från 9 + 11 användare.

**Parametern `tab` går inte att läsa ut.** Den är inte registrerad som
anpassad dimension i GA4 (`customEvent:tab` ger 400), så klicken kan inte
delas per flik. Pär: lägg till anpassade dimensioner (händelseomfattning)
för `tab` och `position` i GA4 382798575, Admin → Anpassade definitioner.
Gäller bara data från och med registreringen, så gör det före mätningen
2026-10-24. Bara Mest läst skickar `select_content` idag, så antalet klick
går att läsa även utan dimensionerna.

## Länkraden i fliken Idag tar bort `/377/igar`

Länkraden följer fliken: Idag → `/nyheter/idag`, `/sport/idag`, `/377`;
Igår → `/nyheter/igar`, `/sport/igar`, `/377/igar`. Efter kl 10 är Idag
förval, så de flesta ser inte länken till `/377/igar` på startsidan längre.
Startsidan var ungefär hälften av hänvisningarna till `/377/igar`
(GA4 14–20 okt), som föll till nästan noll 19–20 okt (se #16, Mätning).
`/377` finns redan överallt, så länken dit i Idag-raden tillför lite.

**Ändrat 2026-10-20:** Idag-raden (även under 7 dagar) har `/377/igar`
("Resultat igår") i stället för `/377`. Följ upp 2026-10-24 att
`/377/igar` är tillbaka på 25–40 per dag.
