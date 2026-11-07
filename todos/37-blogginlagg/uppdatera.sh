#!/usr/bin/env bash
# Todo #37: uppdaterar texten i det publicerade inlägget (id 83) från inlagg.sql.
# Kör från Mac minin:  ssh texttv.nu 'bash -s' < uppdatera.sh
# Tar backup av bloggtabellen först (eget filnamn med klockslag). Root via socket.
set -euo pipefail
DIR=/root/db-backups-$(date +%F)
mkdir -p "$DIR"; chmod 700 "$DIR"
umask 077
B="$DIR/texttv_blogg-$(date +%H%M%S).sql"
mysqldump --no-defaults -u root --single-transaction --quick --hex-blob 'texttv.nu' texttv_blogg > "$B"
tail -1 "$B" | grep -q 'Dump completed' || { echo "Backupen misslyckades: $B"; exit 1; }
echo "Backup: $B"
mysql --no-defaults -u root --default-character-set=utf8mb4 <<'SQL'
SET NAMES utf8mb4;
UPDATE `texttv.nu`.texttv_blogg SET content = '<p>I våras fick vi ett mejl från Frank Putman i Nederländerna. Han bygger <a href="https://github.com/fputmango/Teletext64U">Teletext 64 Ultimate</a>, ett program som visar text-tv från internet på en <a href="https://sv.wikipedia.org/wiki/Commodore_64">Commodore 64</a>, och hade just lagt till SVT Text. Sidorna hämtar programmet från TextTV.nu.</p>

<p><img src="https://texttv.nu/bloggimages/2026-svt-text-commodore-64.jpg" alt="SVT Text sidan 100 på en skärm, med ett Commodore 64-tangentbord framför" width="1200" height="1600"></p>

<p>Så här ser det ut hemma hos Frank. Bilden tog han i dag på morgonen: <a href="https://texttv.nu/100">SVT Text 100</a>, med samma rubriker, blockgrafik och blå SVT-logga som på tv:n.</p>

<h2>Samma format som text-tv</h2>

<p>Text-tv och C64:an kommer från samma tid, och det märks. Båda visar 40 tecken per rad och 25 rader, så en text-tv-sida fyller C64-skärmen precis.</p>

<p>Teletext 64 Ultimate har ett 40-tal text-tv-tjänster att välja mellan, bland annat NOS Teletekst, ARD, ZDF, YLE, DR och SVT Text. Programmet är gjort för nya <a href="https://www.commodore.net/">Commodore 64 Ultimate</a>, men går också på en vanlig C64 med ett nätverkskort som <a href="https://wic64.net/">WiC64</a>. Mer om projektet finns på <a href="https://github.com/fputmango/Teletext64U">GitHub</a>.</p>

<h2>Grafiken som försvann</h2>

<p>TextTV.nu sparar blockgrafiken från SVT:s sidor som små bilder, en för varje tecken. Teletext 64 Ultimate känner igen bilderna på filnamnet och ritar motsvarande grafiktecken på C64:an.</p>

<p>När vi flyttade TextTV.nu till en ny server i slutet av oktober fick bilderna nya filnamn utan att vi märkte det. På webben såg allt likadant ut, men på C64:an försvann grafiken. Nu har bilderna sina gamla namn igen.</p>

<h2>Tack, Frank</h2>

<p>Själv hade jag en C64 med en <a href="https://sv.wikipedia.org/wiki/Commodore_1581">1581-diskettstation</a>, en ovanlig modell i Sverige. Bilden väckte minnen.</p>

<p>Har du sett TextTV.nu någon annanstans än i mobilen eller på datorn? Skriv till <a href="mailto:kontakt@texttv.nu">kontakt@texttv.nu</a>. Vi läser allt.</p>
' WHERE id = 83 AND permalink = 'svt-text-pa-commodore-64';
SELECT id, permalink, LENGTH(content) FROM `texttv.nu`.texttv_blogg WHERE id = 83;
SQL
