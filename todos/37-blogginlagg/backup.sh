#!/usr/bin/env bash
# Todo #37: backup av bloggtabellen på prod innan blogginlägget läggs in.
# Pär kör från Mac minin:  ssh texttv.nu 'bash -s' < backup.sh
# Bara bloggtabellen (inlägget är en rad i den), som i #26.
# Loggar in som root via socket: sedan TT-30 når importerns konto inte bloggtabellen.
set -euo pipefail
DIR=/root/db-backups-$(date +%F)
mkdir -p "$DIR"; chmod 700 "$DIR"
umask 077
mysqldump --no-defaults -u root --single-transaction --quick --hex-blob 'texttv.nu' texttv_blogg > "$DIR/texttv_blogg.sql"
ls -la "$DIR/texttv_blogg.sql"
tail -1 "$DIR/texttv_blogg.sql"
