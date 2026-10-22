**Status:** aktiv — utkast klart, väntar på deploy av live-uppdateringen och Pärs publicering
**Senast uppdaterad:** 2026-10-22

# Todo #26 — Blogginlägg: resultaten uppdaterar sig själva

## Sammanfattning

Utkast till ett inlägg på /blogg om det som gjordes 2026-10-22:

- Resultatsidorna (330, 343, 344, 358, 359, 376–383) byts ut på plats när
  en ny version kommer, och ändrade rader lyser gult en stund
  (`Texttv_page::LIVE_RESULT_PAGES`, `js/scripts.js`).
- Flikar mellan målservicesidorna 376–383 och 330
  (`views/malservice_flikar.php`).
- "Senast besökta" och "Mest läst nu" under Gå till sida länkar till
  versionen som rubriken hör till.
- Arkivversioner av tabellsidorna får ämnet och datumet som titel, `<h1>`,
  `og:title` och delningstitel.

Skrivet för besökarna, i samma ton som #23 ("vi", korta stycken,
kontaktadressen sist). Pär publicerar själv.

Kontrollera innan publicering:

- Att live-uppdateringen är deployad och fungerar på en matchkväll (se
  att en rad lyser upp på /377 eller /378 utan omladdning).
- Att påståendet "Appen är sig lik" fortfarande stämmer.
- Rubrikförslagen nedan.

## Publicera

Skripten i den här mappen, från repo-roten:

```bash
ssh texttv.nu 'bash -s' < todos/26-blogginlagg-live-resultat/backup.sh   # backup av bloggtabellen till /root/db-backups-YYYY-MM-DD/
ssh texttv.nu 'bash -s' < todos/26-blogginlagg-live-resultat/infoga.sh   # lägger in inlägget (avbryter utan dagens backup)
```

- `backup.sh`: bara bloggtabellen. #23 tog också hela databaserna (1,5 GB),
  vilket är onödigt för att lägga in en rad.
- `infoga.sh`: SQL:en i `inlagg.sql` (kopierad in i skriptet; ändras texten,
  ändra båda). Går att köra om, lägger bara in om permalänken saknas.
  Ångra med `DELETE … WHERE permalink = 'resultaten-uppdaterar-sig-sjalva'`.
- Provkört lokalt 2026-10-22: inlägget renderas på
  `/blogg/resultaten-uppdaterar-sig-sjalva`, och två körningar ger en rad.

## Utkast

**Rubrik:** Resultaten uppdaterar sig själva

**Alternativa rubriker:** "Målservice utan att ladda om" ·
"Se målen komma in på 377"

Texten står i [`inlagg.sql`](inlagg.sql), som HTML.
