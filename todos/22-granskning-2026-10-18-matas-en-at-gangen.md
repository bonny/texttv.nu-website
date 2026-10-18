**Status:** aktiv
**Senast uppdaterad:** 2026-10-18

# Todo #22 — Granskningen 2026-10-18: det som mäts en ändring åt gången

## Sammanfattning

Fyra granskare gick igenom livesajten 2026-10-18: SEO, UX, AdSense och en
äldre besökare ("Gunnar, 74", mobil med stor text). Det säkra är gjort och
deployat (se nedan). Kvar är ändringar som påverkar intäkten eller de
viktigaste sökningarna. De görs en åt gången och mäts YoY med /377 som
kontroll, och AdSense-inställningar som bara Pär kan ändra.

## Gjort 2026-10-18

Commit `331d675` och `df41d5e`:

- Annonsbuggen: det nedre blocket (8021374801) fylldes aldrig på `/`,
  100–105 och flersidor. Den dolda toppannonsens `<ins>` fångade push().
- Noindex på arkivversioner av 330–399 och 550–599.
- Brödsmulorna pekar på enskilda sidor i stället för intervall med noindex.
- Noindex på `/appembed/`, 404 i stället för 200 på ogiltiga arkiv-id,
  högst 30 sidor per adress.
- Nyhetsarkiven: `<h1>`, rensad description, author som Organization.
- Hela raden tryckbar i Text-TV-sidan, ←/→ som snabbtangenter, fokus i
  menyn, gul fokusram, sökfältet ser ut som ett fält, 18 px i sidomenyn
  från 1280 px.
- `/nyheter/idag` och `/sport/idag`.
- Förslag på sidor som inte sänts på länge. "Gårdagens 377" i menyn.

## För Pär i AdSense (bara läsrätt via MCP)

1. **Stäng av Ad intents.** 2,2 % av intäkten (cirka 125 kr/mån).
   Chippet "Text-TV-app" ligger över brödsmulorna, och understrukna ord i
   377-tabellen går inte att skilja från sajtens länkar.
2. **Stäng av "Upptäck mer"** (relaterade sökningar i tomma annonsplatser).
   Cirka 1 kr i veckan, ser ut som navigering.
3. **Ändringshistoriken 11–14 oktober.** Vinjettvisningarna föll cirka 40 %
   (2 700–4 300/dag → 1 586–1 971) före designbytet. Det är ungefär halva
   RPM-fallet. Kolla vinjettinställningarna ("ytterligare utlösare") och
   annonsbelastningen.
4. **Ankarannonsen på mobil** tar 38–50 % av skärmen (Better Ads: max 30 %).
   AdSense-experiment 50/50 med en lägre ankare, jämför RPM per sidvisning.
5. **URL-grupp för `/` och flersidorna** med lägre annonsbelastning. Auto
   ads lägger 5–7 block med 6–11 % viewability, CLS 0,10 på desktop.
6. **Länka AdSense till GA4** (property 382798575, "texttv.nu webpage -
   GA4") för intäkt per sida. I dag är `totalAdRevenue` 0.

## Ändringar som mäts (en åt gången)

| Ordning | Ändring | Var | Mät |
| ------- | ------- | --- | --- |
| 1 | Toppannonsen på mobil max cirka 280 px i stället för 390×390, så att första raderna syns. Den har bäst RPM (5,85 kr, viewability 76 %), så flytta den inte. | `views/pages_current_page_top.php`, `css/tt.css` | RPM per sidvisning och blockets RPM, sidvisningar per session på mobil (5,5), 2 veckor |
| 2 | Startsidans title med "100" och "TextTV.nu", t.ex. "SVT Text TV 100 – nyheter och resultat 377 \| TextTV.nu". `/` och `/100` delar "text tv 100" med CTR 0,1–0,3 %. Ge `/svt-text-tv` en egen vinkel. | `views/header.php` (`$is_start`), `controllers/svttexttv.php` | GSC 28 d YoY, `/377` som kontroll |
| 3 | Canonical från en nyhetsversion till senaste versionen med samma sidnummer och rubrik samma dag. | `controllers/sida.php` → `arkiv()` | GSC: dubbletter av samma nyhet |
| 4 | Title på /377 bara om allt annat står stilla: "Text TV 377 – Målservice och dagens resultat". Rensa "⚽️️" och "sportnördens bästa vän" ur beskrivningen. /377 är 70 % av klicken. | `views/header.php` | GSC 28 d YoY, `/` som kontroll |
| 5 | Större Text-TV-text för den som har stor text i telefonen: artiklarna (106–199) får bryta rader, eller en "A+"-knapp. Sidzoomen gör inte texten större i dag, bara menyerna. | `css/tt.css`, `js/scripts.js` | – |

Mindre saker från SEO-granskningen, utan mätning: `og:url` saknas,
`og:type=article` på levande sidor, WebSite-schemat utan avslutande
snedstreck och med `alternateName` = `name`, radbrytningar i bloggens
`<title>`, `/text-tv-fakta` utan canonical. www-omdirigeringen går via http
(två 301:or), nginx på prod, kräver ok.

## Risker

- Punkt 1 och AdSense-punkterna 4–5 kan sänka intäkten. AdSense-experiment
  i stället för att ändra rakt av.
- Titeländringar kan ta veckor att slå igenom, och två samtidigt går inte
  att skilja åt.

## Confidence

medel — fynden är verifierade mot live, GSC och AdSense 2026-10-18.
Effekterna är gissningar.
