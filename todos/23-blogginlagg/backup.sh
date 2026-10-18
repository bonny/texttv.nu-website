#!/usr/bin/env bash
# Todo #23: backup av databaserna på prod innan blogginlägget läggs in.
# Pär kör från Mac minin:  ssh texttv.nu 'bash -s' < backup.sh
# Använder importerns DB-uppgifter via en tillfällig fil som bara root kan
# läsa och som tas bort efteråt. Dumpen görs med --single-transaction (inga
# lås på InnoDB) och låg prioritet (nice/ionice).
set -euo pipefail
E=/usr/share/nginx/l.texttv.nu/importer/.env
DIR=/root/db-backups-$(date +%F)
mkdir -p "$DIR"; chmod 700 "$DIR"
umask 077
CNF=$(mktemp); trap 'rm -f "$CNF"' EXIT
val() { grep -E "^$1=" "$E" | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//'; }
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\n' "$(val DB_USERNAME)" "$(val DB_PASSWORD)" "$(val DB_HOST)" > "$CNF"
M="mysql --defaults-extra-file=$CNF"
D="mysqldump --defaults-extra-file=$CNF --single-transaction --quick --routines --triggers --hex-blob"

echo "== Storlek (MB) =="
$M -N -e "select table_schema, round(sum(data_length+index_length)/1024/1024) from information_schema.tables where table_schema in ('texttv.nu','texttv_stats') group by table_schema"
echo "== Ledigt på / =="; df -h / | tail -1

echo "== 1. Bloggtabellen =="
$D 'texttv.nu' texttv_blogg > "$DIR/texttv_blogg.sql"
ls -la "$DIR/texttv_blogg.sql"

# Hela databaserna. Hoppas över om de är större än halva det lediga utrymmet.
NEED=$($M -N -e "select ceil(sum(data_length+index_length)/1024) from information_schema.tables where table_schema in ('texttv.nu','texttv_stats')")
FREE=$(df -k --output=avail / | tail -1)
if [ "$NEED" -gt "$((FREE / 2))" ]; then
	echo "Hoppar över hela dumpen: databaserna (${NEED} kB) är mer än halva det lediga utrymmet (${FREE} kB)."
	exit 0
fi
for DB in texttv.nu texttv_stats; do
	echo "== 2. Hela $DB ($(date +%T)) =="
	nice -n 19 ionice -c3 $D --databases "$DB" | gzip -1 > "$DIR/$DB.sql.gz"
	ls -la "$DIR/$DB.sql.gz"
done
echo "== Klart $(date +%T): $DIR =="
ls -la "$DIR"
