#!/usr/bin/env bash
# Todo #26: backup av bloggtabellen på prod innan blogginlägget läggs in.
# Pär kör från Mac minin:  ssh texttv.nu 'bash -s' < backup.sh
# Bara bloggtabellen (inlägget är en rad i den); #23 tog hela databaserna.
# Använder importerns DB-uppgifter via en tillfällig fil som bara root kan
# läsa och som tas bort efteråt. --defaults-file, inte --defaults-extra-file:
# /root/.my.cnf har ett gammalt lösenord och läses annars efter och vinner.
set -euo pipefail
E=/usr/share/nginx/l.texttv.nu/importer/.env
DIR=/root/db-backups-$(date +%F)
mkdir -p "$DIR"; chmod 700 "$DIR"
umask 077
CNF=$(mktemp); trap 'rm -f "$CNF"' EXIT
val() { grep -E "^$1=" "$E" | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//'; }
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\n' "$(val DB_USERNAME)" "$(val DB_PASSWORD)" "$(val DB_HOST)" > "$CNF"
mysqldump --defaults-file="$CNF" --single-transaction --quick --hex-blob 'texttv.nu' texttv_blogg > "$DIR/texttv_blogg.sql"
ls -la "$DIR/texttv_blogg.sql"
tail -1 "$DIR/texttv_blogg.sql"
