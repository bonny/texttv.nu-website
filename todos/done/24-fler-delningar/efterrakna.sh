#!/usr/bin/env bash
# Todo #24: räkna appens delningar i efterhand. Appen har sedan 2019 bara
# skrivit delningar till texttv_stats.page_actions, inte till texttv.is_shared.
# Sedan 07e98b0 räknas de direkt; det här lägger till dem som redan finns i
# page_actions (som sparar 10 dagar) från före den deployen.
#
# Pär kör från Mac minin:
#   ssh texttv.nu 'bash -s' < efterrakna.sh            # visa bara
#   ssh texttv.nu 'APPLY=1 bash -s' < efterrakna.sh    # backup + skriv
#
# Ångra: backupfilen har id och gamla is_shared, se slutet av skriptet.
set -euo pipefail

# 07e98b0 kopierades till servern 15:52:08–15:52:23 UTC 2026-10-18. Rader
# från och med då har den nya koden redan räknat.
CUTOFF_UTC="2026-10-18 15:52:08"
# OPENLINK ("Öppna i webbläsaren") räknas inte, som i Api::page().
TYPER="'SHARE','COPYLINK','COPYTEXT'"

E=/usr/share/nginx/l.texttv.nu/importer/.env
DIR=/root/db-backups-$(date +%F)
mkdir -p "$DIR"; chmod 700 "$DIR"
umask 077
CNF=$(mktemp); TMP=$(mktemp -d); trap 'rm -rf "$CNF" "$TMP"' EXIT
val() { grep -E "^$1=" "$E" | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//'; }
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\n' "$(val DB_USERNAME)" "$(val DB_PASSWORD)" "$(val DB_HOST)" > "$CNF"
M="mysql --defaults-file=$CNF -N -B"

# created_at sätts av PHP i dess tidszon. Skillnaden mot UTC räknas ur
# senaste raden (det kommer anrop varje sekund).
OFF_H=$($M -e "select round(timestampdiff(second, utc_timestamp(), max(created_at)) / 3600) from texttv_stats.page_actions")
case "$OFF_H" in
	0|1|2) ;;
	*) echo "Oväntad tidszon (UTC +$OFF_H h). Avbryter."; exit 1 ;;
esac
CUTOFF=$($M -e "select '$CUTOFF_UTC' + interval $OFF_H hour")
echo "== Tidszon: UTC +$OFF_H h, gräns $CUTOFF =="

echo "== Delningar i page_actions före gränsen, per typ =="
$M -e "select type, count(*), min(created_at), max(created_at) from texttv_stats.page_actions where type in ($TYPER) and created_at < '$CUTOFF' group by type"

$M -e "select page_ids from texttv_stats.page_actions where type in ($TYPER) and created_at < '$CUTOFF'" > "$TMP/rader.txt"

# Som i Api::page(): unika id per anrop, högst tio.
awk -F, '{
	split("", s); n = 0
	for (i = 1; i <= NF; i++) {
		id = $i + 0
		if (id > 0 && !(id in s) && n < 10) { s[id] = 1; n++; c[id]++ }
	}
} END { for (id in c) print id "\t" c[id] }' "$TMP/rader.txt" | sort -k2,2nr > "$TMP/antal.tsv"

echo "== $(wc -l < "$TMP/antal.tsv") arkivsidor, $(awk '{s += $2} END {print s + 0}' "$TMP/antal.tsv") delningar. Mest delade: =="
IDS=$(cut -f1 "$TMP/antal.tsv" | paste -sd, -)
[ -n "$IDS" ] || { echo "Inget att räkna."; exit 0; }
head -15 "$TMP/antal.tsv" | while IFS=$'\t' read -r id n; do
	printf '%5s  ' "$n"
	$M -e "select concat(page_num, '  ', left(title, 40), '  (is_shared nu ', is_shared, ')') from \`texttv.nu\`.texttv where id = $id" < /dev/null
done

if [ "${APPLY:-}" != "1" ]; then
	echo "== Bara visat. Kör med APPLY=1 för att skriva. =="
	exit 0
fi

BACKUP="$DIR/is_shared-fore-efterrakning-$(date +%H%M%S).tsv"
$M -e "select id, is_shared from \`texttv.nu\`.texttv where id in ($IDS)" > "$BACKUP"
echo "== Backup: $BACKUP ($(wc -l < "$BACKUP") rader) =="

{
	echo "start transaction;"
	while IFS=$'\t' read -r id n; do
		echo "update \`texttv.nu\`.texttv set is_shared = least(is_shared + $n, 127) where id = $id;"
	done < "$TMP/antal.tsv"
	echo "commit;"
} > "$TMP/update.sql"
$M < "$TMP/update.sql"

echo "== Klart. Efter: =="
$M -e "select count(*), sum(is_shared) from \`texttv.nu\`.texttv where id in ($IDS)"
echo "Ångra: awk -F'\t' '{print \"update \`texttv.nu\`.texttv set is_shared = \" \$2 \" where id = \" \$1 \";\"}' $BACKUP | mysql --defaults-file=..."
