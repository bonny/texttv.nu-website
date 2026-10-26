**Status:** aktiv
**Senast uppdaterad:** 2026-10-26

# Todo #29 — Importern hänger: HTTP-anropet saknar timeout

## Sammanfattning

`texttv:import` hänger sig regelbundet, och ett cronjobb på prod dödar alla
importprocesser var 30:e minut:

```
*/30 * * * * ps aux | grep 'texttv:import' | grep -v grep | awk '{print $2}' | xargs -r kill -9
```

Det är symptomdämpning. Trolig orsak, hittad 2026-10-26: anropet mot SVT i
`app/Classes/Importer.php:44` är `Http::get($url)` utan timeout. Laravel 8:s
HTTP-klient sätter bara `http_errors => false` som standard (kontrollerat i
prods `vendor/…/PendingRequest.php`, v8.83.27), så Guzzle väntar **för evigt**
när SVT inte svarar eller en anslutning blir stående.

Två saker gör det värre:

- Jobben i `app/Console/Kernel.php` körs varannan minut med `runInBackground()`
  men utan `withoutOverlapping()`, så hängda processer staplas på varandra.
- `kill -9` var 30:e minut slår även mot importer som går som de ska, mitt i
  en skrivning.

## Förslag

1. `Http::timeout(20)->withOptions(['connect_timeout' => 5])->get($url)` i
   `Importer.php`. Ett misslyckat anrop ska loggas och hoppa till nästa sida,
   inte stoppa hela intervallet.
2. `->withoutOverlapping(10)` på de schemalagda `texttv:import`-jobben, så ett
   nytt varv inte startar medan förra fortfarande går. Låset släpper efter
   10 minuter om en process dör.
3. Deploya och låt kill-jobbet ligga kvar några dagar. Kolla med
   `ps -eo etimes=,args= | grep texttv:import` (se `prod-health importer`)
   att inga processer blir äldre än någon minut.
4. Ta bort kill-raden ur root-crontab när det varit tyst en vecka.

Testa lokalt med `make up` och `make import RANGE=100-110`; en timeout kan
provas genom att peka URL:en mot en adress som inte svarar.

## Risker

- För kort timeout kan ge luckor när SVT är långsamt. 20 s är generöst jämfört
  med normala svarstider; sidan hämtas igen nästa varv ändå.
- `withoutOverlapping()` kräver cache-lås. Importern kör med filcache på prod,
  vilket fungerar för en enda server.

## Confidence

medel–hög — saknad timeout är en känd orsak till exakt det här mönstret, men
att det är *den enda* orsaken är inte bevisat förrän kill-jobbet kan tas bort.
