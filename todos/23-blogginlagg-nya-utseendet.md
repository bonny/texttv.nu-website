**Status:** aktiv — utkast klart, skript för backup och publicering i `23-blogginlagg/`, Pär kör
**Senast uppdaterad:** 2026-10-18

# Todo #23 — Blogginlägg om nya utseendet (2026-10-17–18)

## Sammanfattning

Utkast till ett inlägg på /blogg om det som gjordes 17–18 oktober (todo #18–#22).
Skrivet för besökarna, inte för utvecklare: vad som är nytt och var man
hittar det. Tonen som i tidigare inlägg ("vi", korta stycken, kontaktadressen
sist). Pär publicerar själv.

Kontrollera innan publicering:

- Att ad intents verkligen har försvunnit (påverkar inte texten, men stycket
  om annonser lovar ingenting om dem).
- Att länkarna fungerar: `/nyheter/idag`, `/nyheter/igar`, `/377/igar`,
  `/sida/polisen`.
- Rubrikförslag 2 och 3 om förslag 1 känns för stort.

## Publicera

Claude får inte skriva till prod-DB:n (auto mode), så Pär kör skripten i
[`23-blogginlagg/`](23-blogginlagg/) från repo-roten:

```bash
ssh texttv.nu 'bash -s' < todos/23-blogginlagg/backup.sh   # backup till /root/db-backups-YYYY-MM-DD/
ssh texttv.nu 'bash -s' < todos/23-blogginlagg/infoga.sh   # lägger in inlägget (avbryter utan dagens backup)
```

- `backup.sh`: bloggtabellen, sedan hela `texttv.nu` och `texttv_stats`
  (gzip, `--single-transaction`, nice/ionice). Hela dumpen hoppas över om
  databaserna är större än halva det lediga utrymmet.
- `infoga.sh`: SQL:en i `inlagg.sql`. Går att köra om, lägger bara in om
  permalänken saknas. Ångra med `DELETE … WHERE permalink =
  'texttv-nu-ser-ut-som-text-tv-igen'`.
- Provkört lokalt 2026-10-18: inlägget renderas på
  `/blogg/texttv-nu-ser-ut-som-text-tv-igen`, och två körningar ger en rad.

## Utkast

**Rubrik:** TextTV.nu ser ut som Text TV igen

**Alternativa rubriker:** "Nytt utseende och nya sätt att hitta" ·
"Svart, blått och gult: TextTV.nu har fått nytt utseende"

---

Den här helgen har TextTV.nu fått ett nytt utseende. Tanken är enkel: det ska
se ut och kännas som Text TV, fast i mobilen och på datorn. Svart bakgrund,
Text-TV:s egna färger och samma typsnitt överallt, inte bara i själva sidorna.
Loggan med den blå rutan är kvar.

Här är det viktigaste som är nytt.

**Lättare att hitta rätt sida**

Rutan "Gå till sida" visar förslag direkt när du trycker i den: de mest lästa
sidorna just nu och de populäraste sidorna. Du kan också skriva ett namn, till
exempel "målservice", "väder" eller "premier league". Skriver du tre siffror
går du direkt till sidan, precis som med fjärrkontrollen.

Inne i Text-TV-sidorna går hela raden att trycka på, inte bara det lilla
sidnumret i slutet. Det är lättare att träffa rätt med tummen.

**Dagens och gårdagens nyheter**

Har du läst en nyhet i morse men inte minns sidnumret? Under
<a href="/nyheter/idag">Dagens nyheter</a> finns allt som sänts hittills i
dag, med klockslag. <a href="/nyheter/igar">Gårdagens nyheter</a> och
gårdagens sportnyheter finns också, och på startsidan ser du vad som var mest
läst i går.

För resultaten finns <a href="/377/igar">Gårdagens 377</a>, och på
sportsidorna kan du under "Tidigare i dag" bläddra bland dagens tidigare
versioner av sidan.

**Tydligt när du tittar på en äldre version**

Gamla versioner av en sida har nu ett gult band, "ÄLDRE VERSION", med datum
och klockslag. En länk tar dig till sidan som den ser ut just nu.

**Polishändelserna från Brottsplatskartan**

Under nyheter som handlar om något polisen har rapporterat visas nu händelsen
från <a href="https://brottsplatskartan.se/">Brottsplatskartan</a>, med en
liten karta över platsen. Alla händelser som hör ihop med Text-TV-nyheter
finns samlade på <a href="/sida/polisen">Polishändelser i Text TV</a>.

**Lugnare sida**

Annonsen överst har nu en fast plats, så sidan hoppar inte längre när den
laddas. Det gör det lättare att läsa resultaten på 377 utan att texten flyttar
sig.

**För dig som använder tangentbord**

Pilarna ← och → bläddrar till föregående och nästa sida. Skriv en siffra för
att börja skriva ett sidnummer, eller tryck / för att gå till "Gå till sida".
Tryck ? för att se alla kortkommandon.

**Säg vad du tycker**

Mycket är nytt på en gång, och allt blir säkert inte rätt från början. Saknar
du något, eller är det något som var bättre förut? Skriv till
kontakt@texttv.nu. Vi läser allt.
