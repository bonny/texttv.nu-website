**Status:** väntar — baslinje 2026-10-17–30 (GA4, GSC). Samma frågor efter 14 dagar; samlar in #25, #16, #21, #20 och avgör Dela-knappen, `/sida/senast-uppdaterat` och `/sida/polisen`
**Senast uppdaterad:** 2026-11-10

# Todo #35 — Används de nya sidorna och funktionerna?

## Sammanfattning

Mellan 2026-10-17 och 2026-10-28 kom en rad nya sidor och funktioner på webben (designbytet #18, Mest läst #25, delning #24, Brottsplatskartan #21, Gå till sida, live-uppdatering av resultatsidorna, dagslistorna, `/sida/senast-uppdaterat`, `/status`). Den 2026-10-30 mättes användningen första gången i GA4 och Search Console. Funktionerna som hjälper folk vidare från sidan de redan är på används dagligen. De fristående sidorna och Dela-knappen används knappt. Omkring 2026-11-24 mäts samma sak igen för att avgöra vad som ska vara kvar, flyttas eller tas bort.

Delarna som redan har egna uppföljningar mäts där och samlas bara in här: #25 Mest läst (2026-11-07), #16 GSC för `/377/igar` och datumsidorna (2026-11-14), #21 trafik till Brottsplatskartan (2026-11-14), #20 Tidigare i dag (2026-11-14).

## Baslinje 2026-10-17 – 2026-10-30 (GA4 382798575, webben)

Hela sajten: 221 609 sidvisningar, 10 063 användare.

| Funktion / sida                  | Händelser eller visningar | Användare | Not                                                                                                     |
| -------------------------------- | ------------------------- | --------- | ------------------------------------------------------------------------------------------------------- |
| Gå till sida, `search_open`      | 10 583                    | 1 208     | 1 000–2 200 per dag sedan 2026-10-23                                                                    |
| Gå till sida, `search_select`    | 420                       | 158       | 4 % av öppnade listor. Senast besökta 239, Populära sidor 83, Träffar 24, Mest läst nu 18               |
| Mest läst, `select_content`      | ~640                      | ~370      | Startsidan 324 (Idag 261), under sidan 175, arkivsidor 136. Startsidan 28 141 visningar, alltså ~1,2 %  |
| Mest läst, `most_read_tab`       | 130                       | 28        |                                                                                                         |
| `live_update`                    | 4 216                     | 766       | 500–1 000 per dag. Mäter uppdateringar, inte klick                                                      |
| `share` (Dela-knappen)           | 26                        | 24        |                                                                                                         |
| `police_view` (sedan 2026-10-27) | 47                        | 40        | 3 klick (`select_content` polisruta)                                                                    |
| `/sida/delat`                    | 399 (+50 `/sida/delat/`)  | 365       | 94 % Direct, en visning per användare. Troligen gamla delade länkar som skickas vidare, inte verifierat |
| `/sport/idag`, `/nyheter/idag`   | 180 / 128                 | 116 / 79  |                                                                                                         |
| `/nyheter/igar`, `/sport/igar`   | 88 / 81                   | 63 / 62   |                                                                                                         |
| `/377/igar`                      | 135                       | 108       |                                                                                                         |
| `/sida/senast-uppdaterat`        | 26                        | 15        |                                                                                                         |
| `/sida/polisen`                  | 25                        | 21        |                                                                                                         |
| `/status`                        | 89                        | 3         | Nästan bara Pär                                                                                         |

Search Console (`sc-domain:texttv.nu`, 2026-10-15 – 2026-10-28):

| Sida                            | Klick | Visningar               | Position                                                       |
| ------------------------------- | ----- | ----------------------- | -------------------------------------------------------------- |
| `/377/igar`                     | 2     | ~650 (245 av dem 15/10) | 7–9, för samma frågor som `/377` ("377 målservice", "svt 377") |
| `/sport/igar` · `/nyheter/igar` | 0 · 0 | 17 · 10                 | 2–10                                                           |
| `/sport/idag` · `/nyheter/idag` | 0 · 1 | 18 · 12                 | 2–30                                                           |
| `/sida/senast-uppdaterat`       | 1     | 29                      | 3,9                                                            |
| `/sida/polisen`                 | 1     | 28                      | 11,9                                                           |

GA4:s "Organic Search" på `/377/igar` (61) och `/sport/idag` (70) räknas per besök: folk kommer från Google till en annan sida och klickar sig vidare. Dagslistorna är ett steg efter Google, inte vägen in.

## Mellanmätning 2026-11-10

GA4 2026-10-31 – 2026-11-09 (10 dagar) mot baslinjen 2026-10-17 – 2026-10-30 (14 dagar, siffrorna har satt sig sedan 10-30: 232 814 sidvisningar). Allt per dag eftersom fönstren är olika långa. Hela sajten ~16 600 → ~17 100 sidvisningar per dag, alltså stilla.

| Sida / händelse                 | Baslinje per dag | Nu per dag  | Not                                                |
| ------------------------------- | ---------------- | ----------- | -------------------------------------------------- |
| `/sport/idag` · `/nyheter/idag` | 13,6 · 9,4       | 14,8 · 11,0 | stabila                                            |
| `/377/igar`                     | 9,7              | 5,6         |                                                    |
| `/nyheter/igar` · `/sport/igar` | 6,6 · 6,0        | 5,2 · 5,3   |                                                    |
| `/sida/delat` (+ `/sida/delat/`) | 32               | 5,7         | Baslinjen var en tillfällig topp                   |
| `/sida/senast-uppdaterat`       | 2,1              | 1,1         |                                                    |
| `/sida/polisen`                 | 1,9              | 1,5         |                                                    |
| `/status`                       | 6,9              | 0,6         |                                                    |
| `/sida/vanliga-fragor` (inte ny) | 6,0              | 12,2        | GSC: 73 klick, 1 813 visningar, pos 5,6            |
| `search_open`                   | 816              | 1 671       | Baslinjen räknas från 10-17 men eventet från 10-23 |
| `search_select`                 | 32               | 66          | Fortfarande 4 % av öppnade listor                  |
| `select_content`                | 74               | 116         |                                                    |
| `live_update`                   | 333              | 595         |                                                    |
| `police_view`                   | 13 (från 10-27)  | 38          |                                                    |
| `share`                         | 1,9              | 1,8         |                                                    |
| `most_read_tab`                 | 9,3              | 3,6         |                                                    |

GSC 2026-10-29 – 2026-11-08: `/377/igar` 1 klick, 401 visningar, pos 9,4. Dagslistorna 1–3 visningar var. `/sida/senast-uppdaterat` 1 klick, 18 visningar. `/sida/polisen` 0 klick, 6 visningar.

Samma mönster som i baslinjen, men tydligare: det som hjälper folk vidare (Gå till sida, live-uppdatering, polisrutan) växer. De fristående sidorna och Dela-knappen används knappt, och Google skickar nästan ingen trafik till dem. Att `/sida/delat` rasade stöder att baslinjens trafik kom från vidareskickade gamla länkar, men det är inte kontrollerat i `access.log`.

## Att mäta 2026-11-24

Samma fönsterlängd (14 dagar, t.ex. 2026-11-10 – 2026-11-23) så att siffrorna går att jämföra direkt.

1. GA4: `eventName` med `totalUsers`; `select_content`/`search_select` per `placement`, `tab`, `via`, `section`; `pagePath` med `match_type: 6` (delmatchning; 5 är helmatchning och missar nästan allt) för `^/(sida|nyheter|sport|status)|/(igar|idag)$`.
2. GSC: `page` innehåller `/igar`, `/idag`, `/sida/`.
3. Gå till sida: räknas Enter på ett inskrivet sidnummer som `search_select`? Om inte är 4 % för lågt. Kolla `js/tt-sok.js` före mätningen.
4. `/sida/delat`: ta referer från `access.log` för att se om trafiken är vidareskickade gamla länkar.

## Frågor att besluta efter mätningen

- Dela-knappen: kvar, mindre eller bort, när den ger ~2 delningar per dag?
- `/sida/senast-uppdaterat` och `/sida/polisen`: kvar i sidlistan?
- Mest läst-flikarna: avgörs i #25.
- Polisrutan: avgörs med #21:s trafik till Brottsplatskartan.

## Risker

Två veckor efter lanseringen är kort tid, särskilt för Google. Siffrorna i november kan fortfarande vara för tunna för att säga något om sidorna under `/sida/`.

## Confidence

medel — baslinjen är uppmätt, men flera av sidorna har så få besök att slumpen styr.
