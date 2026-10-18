#!/usr/bin/env bash
# Todo #23: lägger in blogginlägget "TextTV.nu ser ut som Text TV igen".
# Pär kör från Mac minin, efter backup.sh:  ssh texttv.nu 'bash -s' < infoga.sh
# Går att köra om: inlägget läggs bara in om permalänken inte redan finns.
# Ångra: DELETE FROM `texttv.nu`.texttv_blogg WHERE permalink = 'texttv-nu-ser-ut-som-text-tv-igen';
# Använder importerns DB-uppgifter via en tillfällig fil som bara root kan
# läsa och som tas bort efteråt. Avbryter om dagens backup av bloggtabellen
# saknas.
set -euo pipefail
E=/usr/share/nginx/l.texttv.nu/importer/.env
DIR=/root/db-backups-$(date +%F)
umask 077
CNF=$(mktemp); trap 'rm -f "$CNF"' EXIT
val() { grep -E "^$1=" "$E" | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//'; }
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\n' "$(val DB_USERNAME)" "$(val DB_PASSWORD)" "$(val DB_HOST)" > "$CNF"
[ -s "$DIR/texttv_blogg.sql" ] || { echo "Ingen backup av bloggtabellen i dag ($DIR). Kör backup.sh först."; exit 1; }
mysql --defaults-extra-file="$CNF" --default-character-set=utf8mb4 <<'SQL'
-- Todo #23: blogginlägget om nya utseendet. Körs av infoga.sh.
-- Läggs bara in om permalänken inte redan finns, så det går att köra om.
SET NAMES utf8mb4;
INSERT INTO `texttv.nu`.texttv_blogg (date_published, permalink, title, content)
SELECT NOW(), 'texttv-nu-ser-ut-som-text-tv-igen', 'TextTV.nu ser ut som Text TV igen', '<p>Den här helgen har TextTV.nu fått ett nytt utseende. Tanken är enkel: det ska se ut och kännas som Text TV, fast i mobilen och på datorn. Svart bakgrund, Text-TV:s egna färger och samma typsnitt överallt, inte bara i själva sidorna. Loggan med den blå rutan är kvar.</p>

<p>Här är det viktigaste som är nytt.</p>

<h2>Lättare att hitta rätt sida</h2>

<p>Rutan <strong>Gå till sida</strong> visar förslag direkt när du trycker i den: de mest lästa sidorna just nu och de populäraste sidorna. Du kan också skriva ett namn, till exempel "målservice", "väder" eller "premier league". Skriver du tre siffror går du direkt till sidan, precis som med fjärrkontrollen.</p>

<p>Inne i Text-TV-sidorna går hela raden att trycka på, inte bara det lilla sidnumret i slutet. Det är lättare att träffa rätt med tummen.</p>

<h2>Dagens och gårdagens nyheter</h2>

<p>Har du läst en nyhet i morse men inte minns sidnumret? Under <a href="https://texttv.nu/nyheter/idag">Dagens nyheter</a> finns allt som sänts hittills i dag, med klockslag. <a href="https://texttv.nu/nyheter/igar">Gårdagens nyheter</a> och gårdagens sportnyheter finns också, och på startsidan ser du vad som var mest läst i går.</p>

<p>För resultaten finns <a href="https://texttv.nu/377/igar">Gårdagens 377</a>, och på sportsidorna kan du under "Tidigare i dag" bläddra bland dagens tidigare versioner av sidan.</p>

<h2>Tydligt när du tittar på en äldre version</h2>

<p>Gamla versioner av en sida har nu ett gult band, "ÄLDRE VERSION", med datum och klockslag. En länk tar dig till sidan som den ser ut just nu.</p>

<h2>Polishändelserna från Brottsplatskartan</h2>

<p>Under nyheter som handlar om något polisen har rapporterat visas nu händelsen från <a href="https://brottsplatskartan.se/">Brottsplatskartan</a>, med en liten karta över platsen. Alla händelser som hör ihop med Text-TV-nyheter finns samlade på <a href="https://texttv.nu/sida/polisen">Polishändelser i Text TV</a>.</p>

<h2>Lugnare sida</h2>

<p>Annonsen överst har nu en fast plats, så sidan hoppar inte längre när den laddas. Det gör det lättare att läsa resultaten på 377 utan att texten flyttar sig.</p>

<h2>För dig som använder tangentbord</h2>

<p>Pilarna ← och → bläddrar till föregående och nästa sida. Skriv en siffra för att börja skriva ett sidnummer, eller tryck / för att gå till "Gå till sida". Tryck ? för att se alla kortkommandon.</p>

<h2>Säg vad du tycker</h2>

<p>Mycket är nytt på en gång, och allt blir säkert inte rätt från början. Saknar du något, eller är det något som var bättre förut? Skriv till <a href="mailto:kontakt@texttv.nu">kontakt@texttv.nu</a>. Vi läser allt.</p>
'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `texttv.nu`.texttv_blogg WHERE permalink = 'texttv-nu-ser-ut-som-text-tv-igen');
SELECT id, date_published, permalink, title FROM `texttv.nu`.texttv_blogg WHERE permalink = 'texttv-nu-ser-ut-som-text-tv-igen';
SQL
