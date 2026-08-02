**Status:** aktiv
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

## Att utreda

Hur mycket detta faktiskt kostar går **inte** att mäta bakåt — raderade sidor
finns varken i DB:n eller i GSC (som bara har 16 månader). Storleksordningen
måste uppskattas framåt eller från proxy:

1. Hur många odelade arkivrader raderas per år? (`is_shared = 0` per page_num,
   jämför med totalen.)
2. Hur stor andel av arkivtrafiken går till odelade sidor idag? Gå igenom
   GSC-sidor med `/{num}/{slug}-{id}`-mönster, slå upp `is_shared` för varje.
   Om en märkbar andel har `is_shared = 0` raderas de vid tolvmånadersstrecket.

Steg 2 är det avgörande och går att göra direkt.

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

Hög på mekanismen (verifierad mot prod-DB:n), **låg på storleksordningen**.
Steg 2 ovan behövs innan något byggs om.
