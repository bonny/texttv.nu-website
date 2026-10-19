# 24. Fler delningar, och räkna dem som finns

**Status:** aktiv
**Senast uppdaterad:** 2026-10-19

## Bakgrund

`/sida/delat` visade 1–3 delningar per nyhet. Orsaken: appen slutade anropa
`/api/share` 2019-09-28 (app-commit `0ecb1310`). Sedan dess skickar den
delningar bara som statistik till `/api/page/{ids}/share` (och
`copyLinkToClipboard`, `copyTextToClipboard`, `openLinkInBrowser`), som
hamnar i `texttv_stats.page_actions`. `texttv.is_shared` räknade alltså bara
webbens Dela-knapp.

Firebase (appen), 28 dagar till 2026-10-17: 1 138 `share`-händelser från
465 användare, cirka 40 per dag. Webben mättes inte i GA4.

Följdfel: `texttv:cleanup-old-pages` raderar arkivsidor äldre än ett år med
`is_shared = 0`, så länkar som delats från appen slutade fungera efter ett år.

## Gjort 2026-10-18

- `b7f3d8f` Antalet delningar visas inte på `/sida/delat` (för få).
- `07e98b0` `Api::page()` räknar upp `is_shared` för SHARE, COPYLINK,
  COPYTEXT och OPENLINK. Högst tio unika id per anrop. Svaret oförändrat
  (`make test-api` identiskt).
- `a4d5523` `is_shared` är TINYINT och sql_mode strikt: uppräkningen stannar
  på 127 i stället för att ge databasfel i svaret.
- OPENLINK ("Öppna i webbläsaren") räknas inte längre som delning. Den var
  vanligast av de fyra (157 av 376 på tio dagar) och gav sida 700
  (Textningsinfo) och 401 (väder) överst i listan.
- `b2d1451` "Dela sidan" är en gul knapp i stället för en textlänk, finns även
  på arkivsidorna (dit delade länkar leder), och webbens delningar går till
  GA4 som `share` med `method` (webbläsarens delning, kopiera länk, facebook,
  x, e-post).

## Efterräkning: körd 2026-10-18 20:11

Körd med `APPLY=1`. Backup: `/root/db-backups-2026-10-18/is_shared-fore-efterrakning-201102.tsv`
(234 sidor, 218 hade `is_shared` 0 och 16 hade 1). En torrkörning 2026-10-19
visade 199 sidor och 230 delningar (2026-10-09 → 10-18; 10-08 hade redan
städats bort), och `is_shared` var då redan uppräknat med just de antalen.
**Kör inte skriptet igen**: det dubbelräknar.

### Så var det tänkt

`page_actions` sparar 10 dagar, så delningarna från cirka 2026-10-08 fram till
deployen av `07e98b0` (15:52:08 UTC 2026-10-18) kan läggas till i efterhand.
Auto mode nekar läsning och skrivning i prod-DB:n, så Pär kör:

```bash
ssh texttv.nu 'bash -s' < todos/24-fler-delningar/efterrakna.sh          # visa
ssh texttv.nu 'APPLY=1 bash -s' < todos/24-fler-delningar/efterrakna.sh  # skriv
```

Skriptet tar backup av berörda rader (`id`, gamla `is_shared`) i
`/root/db-backups-DATUM/` och skriver i en transaktion. Ingen nertid: ett
par hundra UPDATE på primärnyckel. Ångra med backupfilen (raden skrivs ut
sist). Provkört mot den lokala databasen. Kör det innan städningen tar bort
de äldsta dagarna (10 dagar).

## Uppföljning

- 2026-10-25: GA4 för webben (`properties/382798575`, händelsen `share`
  per `method`) mot Firebase för appen. Hur många delningar per dag på
  `/sida/delat` nu (mot 1–3)?
- Idé, inte påbörjad: "Mest läst" per dag. `page_actions` VIEW finns men
  sparas bara 10 dagar och per sidnummer; kräver en daglig sammanställning.
