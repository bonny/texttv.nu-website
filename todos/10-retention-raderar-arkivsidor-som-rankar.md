**Status:** pausad — mätt 2026-08-02, prioritet nedgraderad
**Senast uppdaterad:** 2026-08-02

# Retention raderar arkivsidor som rankar

Upptäckt 2026-08-02 när `/344`-regressionen i #01 utreddes. Se
[#09](09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md) för
trafikstrukturen som ledde hit.

## Problemet

Arkivsidor är den enda platsen där sajten får **ämnesfrågor** (till skillnad
från sidnummer- och varumärkesfrågor). Men vilka arkivsidor som får leva avgörs
av om någon människa råkade dela dem — inte av om de presterar i sök.

`importer/app/Console/Commands/CleanupOldPages.php`:

```php
$query = TextTV::where('date_updated', '<', Date::now()->subYear())
    ->where('is_shared', 0)
    ->whereNotIn('page_num', [100, 377]);
```

`is_shared` är en **delningsräknare**, inte en boolean — `where('is_shared', 0)`
matchar alltså bara sidor som aldrig delats en enda gång.

## Belägg

`resultatbörsen` är sajtens största rena ämnesfråga (26 klick, 4 213 visningar,
pos 6.9 på 90 dagar). Den serveras helt av arkivsidor från **2017**:

| URL | Datum | Delningar | Klick (90d) |
| --- | --- | --- | --- |
| `/330/resultatborsen-13586101` | 2017-02-28 | 20 | 14 |
| `/330/resultatborsen-14782676` | 2017-05-30 | 3 | 8 |
| `/330/resultatborsen-16782952` | 2017-10-28 | 10 | 2 |
| `/330/resultatborsen-13389199` | 2017-02-13 | 23 | 1 |

Levande `/330` får **0 klick** på frågan. De här sidorna överlevde nio år enbart
för att de har 3–23 delningar.

Rensningen fungerar som avsett — för sida 330 finns 259 rader med
`is_shared = 0`, äldst `2025-08-02`, dvs exakt tolv månader tillbaka. Allt
odelat äldre än så är borta.

**Konsekvens:** en arkivsida som börjar ranka men aldrig delas raderas efter tolv
månader. Sökprestanda konsulteras inte. De fyra sidorna ovan hade lika gärna
kunnat vara borta — det som räddade dem var delningar från 2017, inte att de
tjänar klick 2026.

## Mätning 2026-08-02 — oron var delvis obefogad

Samtliga 37 arkivsidor med klick i topp-130 (90d) slogs upp mot prod-DB:n.

| | Sidor | Klick | Datumspann |
| --- | --- | --- | --- |
| Delade (skyddade) | 9 | 152 | 2016-02-19 → 2026-06-04 |
| **Odelade (raderas)** | **28 (76 %)** | **296 (66 %)** | 2026-04-24 → 2026-06-08 |

Rå siffra: två tredjedelar av arkivtrafiken sitter på sidor som raderas. Men
sammansättningen upphäver det mesta av oron:

**De 28 odelade är utan undantag färska nyhetssidor** med
`?utm_source=brottsplatskartan&utm_medium=newsletter` — ebola i Milano,
rekordfartyg i Stockholm, rysk lyxyacht. Trafiken kommer från att
brottsplatskartan länkat dit, och efterfrågan på en nyhet från juni 2026 är
nära noll i juni 2027 när raderingen sker. Att slänga dem kostar sannolikt
nästan ingenting.

**De 9 skyddade är evergreen:** resultatbörsen 2017 (68+18+8 klick),
målservice-index 2017 (26+5), målservice 2016/2019 (4+4), kultur/nöje 2016 (9).
Sidor som fortfarande tjänar klick nio år senare.

**Slutsats: mekanismen träffar rätt av fel skäl.** Delningar korrelerar med
ålder, och gamla sidor är per definition de som visat sig hålla. Det finns
alltså ingen akut blödning att stoppa.

**Kvarvarande risk, oförändrad men mindre:** en evergreen-sida som aldrig delas
raderas ändå, och vi kan inte se om det hänt — bevisen försvinner med sidorna.
Sannolikheten är dock lägre än befarat, eftersom evergreen-sidor tenderar att
samla delningar över tid just genom att vara långlivade.

**Prioritet nedgraderad.** Inte värt en ombyggnad på nuvarande underlag.

## Det verkliga fyndet: arkivet rankar över den levande sidan

Mätningen sköt fram något mer intressant. På frågan `resultatbörsen` får
**levande `/330` noll klick** medan dess egna arkivsnapshots från 2017 får 25.
Samma mönster på målservice: `/376/malservice-11121821` (2016) och
`/378/malservice-index-376-13301833` (2017) rankar, de levande sidorna inte.

Google föredrar alltså nio år gamla ögonblicksbilder framför den sida som
faktiskt har aktuell data. Det är rimligen för att arkivsidorna har stabil,
oföränderlig text medan de levande sidorna byts ut var annan minut och därför
aldrig bygger upp något ämnessignalvärde.

Det är värt en egen utredning — och det är sannolikt samma underliggande orsak
som gör att `/343` ligger på position 51 för `allsvenskan tabell` trots rätt
innehåll (se [#09](09-varfor-tappade-377-position-och-vad-vi-gor-at-det.md)).
Om hypotesen stämmer är den generella lärdomen att **stabil text är det som
rankar på ämnesfrågor**, vilket är exakt vad #09 steg A bygger på.

## Möjliga åtgärder

- **Utöka undantagslistan** — enklast, men `[100, 377]` är redan en
  hårdkodad-lista-lukt och löser inte problemet generellt.
- **Skydda sidor med trafik.** Stats-DB:n (`texttv_stats.page_actions`) har
  pageviews per sida. Ett villkor som sparar rader med pageviews över en tröskel
  fångar både delade och organiskt hittade sidor. Kräver att importern når
  stats-DB:n — kolla om den gör det (webben gör det via en separat CI-connection).
- **Höj retentionen generellt** och mät diskkostnaden. `texttv`-tabellen är
  gzipad; #03 visade att 158M rader i `page_actions` var det som kostade disk,
  inte sidinnehållet. Kan vara billigare än det låter.

## Risker

Att sluta radera ökar diskanvändningen (75 GB, 52 % använt 2026-05-19 enligt
`server.md`). Mät innan retentionen ändras brett.

Att däremot *behålla* nuvarande beteende har en okänd men löpande kostnad i
förlorade rankande sidor — och den kostnaden är osynlig eftersom bevisen
raderas tillsammans med sidorna.

## Confidence

Hög. Mekanismen är verifierad mot prod-DB:n och storleksordningen är nu mätt:
76 % av arkivsidorna med trafik raderas, men de är nyhetssidor vars efterfrågan
ändå hunnit dö. Ingen åtgärd föreslås.

Det som däremot bör följas upp är arkiv-rankar-över-live-fyndet ovan.
