#!/usr/bin/env bash
# Todo #28 spår A: vad sportsidorna har visat det senaste året, en version
# per sida och månad. Bara läsning, låg prioritet.
# Pär kör från Mac minin:
#   ssh texttv.nu 'bash -s' < todos/28-sportsidor-historik.sh > /tmp/sportsidor-historik.tsv
# Använder importerns DB-uppgifter via en tillfällig fil som bara root kan
# läsa och som tas bort efteråt (som todos/done/23-blogginlagg/backup.sh).
set -euo pipefail
E=/usr/share/nginx/l.texttv.nu/importer/.env
umask 077
CNF=$(mktemp); trap 'rm -f "$CNF"' EXIT
val() { grep -E "^$1=" "$E" | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//'; }
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\ndatabase=%s\n' \
	"$(val DB_USERNAME)" "$(val DB_PASSWORD)" "$(val DB_HOST)" "$(val DB_DATABASE)" > "$CNF"
M="nice -n 19 mysql --defaults-file=$CNF -N -B"

# Första versionen varje månad per sida, och början av sidans innehåll.
# page_content är COMPRESS():at (serialiserad array med HTML), så den packas
# upp här: binärdata överlever inte textutdatan.
$M -e "
select t.page_num, date_format(t.date_added, '%Y-%m'),
	replace(replace(replace(substring(convert(uncompress(t.page_content) using utf8mb4), 1, 1500), '\n', ' '), '\r', ' '), '\t', ' ')
from texttv t join (
	select page_num, min(id) id from texttv
	where page_num in (333, 338, 340, 341, 342, 350, 354, 356, 360, 361, 362, 733, 745, 190)
		and date_added >= now() - interval 13 month
	group by page_num, date_format(date_added, '%Y-%m')
) x on x.id = t.id
order by t.page_num, 2"
