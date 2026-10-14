#!/usr/bin/env bash
# Kontraktstest för /api/*: kör samma anrop mot en äldre version av koden
# (BASE, default origin/main = det som ligger i prod) och mot arbetskopian,
# och kräver att svaren är identiska. Status, Content-Type, CORS- och
# cacheheaders jämförs, och kroppen byte för byte.
#
# Varför differentiellt och inte sparade facit-filer: lokal data och "idag"
# ändras, men två versioner av koden mot samma DB i samma ögonblick ska ge
# samma svar. Shippade appversioner kan inte uppdateras, så en API-ändring
# som inte är avsiktlig får inte gå ut.
#
# Kräver att stacken kör (make up). Basversionen körs i en extra container
# från samma image på port 8382 mot samma mariadb.
#
#   make test-api                 # jämför mot origin/main
#   make test-api BASE=HEAD~1     # jämför mot valfri git-ref
#
# Utdata hamnar i .api-test/ (gitignorerad): base/ och head/ per anrop.
set -euo pipefail

cd "$(dirname "$0")/../.."
REPO=$PWD
BASE=${BASE:-origin/main}
OUT=$REPO/.api-test
HEAD_URL=http://localhost:8380
BASE_URL=http://localhost:8382
BASE_CONTAINER=texttv-api-test-base

if ! docker compose ps --status running --services | grep -qx website; then
	echo "Stacken kör inte. Starta den med: make up" >&2
	exit 2
fi

echo "== Förbereder DB:n (tests/api/setup.sql)"
docker compose exec -T mariadb mariadb -uroot -p"${DB_ROOT_PASSWORD:-root}" < tests/api/setup.sql

sql() {
	docker compose exec -T mariadb mariadb -uroot -p"${DB_ROOT_PASSWORD:-root}" -N -B texttv_nu -e "$1"
}
ID100=$(sql "SELECT MAX(id) FROM texttv WHERE page_num = 100")
ID377=$(sql "SELECT MAX(id) FROM texttv WHERE page_num = 377")
TODAY=$(date +%F)
YESTERDAY=$(date -v-1d +%F 2>/dev/null || date -d yesterday +%F)
DAYS_AGO_20=$(date -v-20d +%F 2>/dev/null || date -d '20 days ago' +%F)

echo "== Startar basversionen ($BASE, $(git rev-parse --short "$BASE")) på $BASE_URL"
rm -rf "$OUT"
mkdir -p "$OUT/base-code" "$OUT/base" "$OUT/head"
git archive "$BASE" texttv.nu | tar -x -C "$OUT/base-code"

docker rm -f "$BASE_CONTAINER" >/dev/null 2>&1 || true
cleanup() { docker rm -f "$BASE_CONTAINER" >/dev/null 2>&1 || true; }
trap cleanup EXIT

# Samma miljö som website-tjänsten i compose.yaml + compose.override.yaml.
NETWORK=$(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{end}}' "$(docker compose ps -q website)")
docker run -d --name "$BASE_CONTAINER" --network "$NETWORK" \
	-p 127.0.0.1:8382:8080 \
	-e SSL_MODE=off -e NGINX_WEBROOT=/var/www/html \
	-e PHP_OPCACHE_ENABLE=1 -e PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 -e PHP_OPCACHE_REVALIDATE_FREQ=0 \
	-v "$OUT/base-code/texttv.nu:/var/www/html" \
	texttv-website:local >/dev/null

for _ in $(seq 1 30); do
	curl -fsS -o /dev/null "$BASE_URL/api/last_updated/news" 2>/dev/null && break
	sleep 1
done

# Töm most_read-filcachen så att den nya koden börjar kallt.
docker compose exec -T website sh -c 'rm -rf "$(php -r "echo sys_get_temp_dir();")/texttv-most-read"'

# fetch URL FIL: sparar normaliserade headers + kropp.
fetch() {
	local url=$1 file=$2
	curl -sS -D "$file.hdr.raw" -o "$file.body" "$url"
	# Bara headers som påverkar klienterna eller nginx-cachen. Date,
	# Server m.fl. skiljer sig alltid och är ointressanta.
	grep -iE '^(HTTP/|content-type:|access-control-|x-accel-expires:|cache-control:|location:|expires:|pragma:)' "$file.hdr.raw" \
		| tr -d '\r' | awk -F': ' '{ $1 = tolower($1); print }' OFS=': ' | sort > "$file.hdr"
	rm "$file.hdr.raw"
	# CodeIgniters varningsblock ("A PHP Error was encountered") skrivs bara
	# ut i development-läget lokalt, inte i prod, och innehåller radnummer
	# som flyttas så fort filen ändras. Jämför utan dem.
	perl -0pe 's{<div style="border:1px solid #990000;.*?</div>}{}gs' "$file.body" > "$file.cmp"
}

fail=0
n=0
while IFS= read -r line; do
	case "$line" in '' | '#'*) continue ;; esac
	path=$line
	path=${path//\{TODAY\}/$TODAY}
	path=${path//\{YESTERDAY\}/$YESTERDAY}
	path=${path//\{DAYS_AGO_20\}/$DAYS_AGO_20}
	path=${path//\{ID100\}/$ID100}
	path=${path//\{ID377\}/$ID377}
	n=$((n + 1))
	name=$(printf '%02d' "$n")

	fetch "$BASE_URL$path" "$OUT/base/$name"
	# Två gånger mot den nya koden: första fyller ev. cache, andra läser den.
	fetch "$HEAD_URL$path" "$OUT/head/$name"
	fetch "$HEAD_URL$path" "$OUT/head/$name.warm"

	status=ok
	for variant in "$name" "$name.warm"; do
		if ! cmp -s "$OUT/base/$name.hdr" "$OUT/head/$variant.hdr" \
			|| ! cmp -s "$OUT/base/$name.cmp" "$OUT/head/$variant.cmp"; then
			status=DIFF
		fi
	done

	# Ogiltig JSON fångas redan av jämförelsen om den är ny. Finns den även i
	# basversionen är det ett känt problem; visa det men låt testet passera.
	# Lokalt är det oftast PHP-varningar, som bara skrivs ut i
	# development-läget (APP_ENV=local), inte i prod.
	note=
	if grep -qi '^content-type: application/json' "$OUT/head/$name.hdr" \
		&& ! jq -e . "$OUT/head/$name.body" >/dev/null 2>&1; then
		note="  (ogiltig JSON, även i basversionen)"
	fi

	code=$(awk '/^http\//{print $2}' "$OUT/head/$name.hdr")
	printf '%-5s %s  %s%s\n' "$status" "$code" "$path" "$note"
	echo "$path" > "$OUT/head/$name.url"

	if [ "$status" != ok ]; then
		fail=1
		for variant in "$name" "$name.warm"; do
			diff -u "$OUT/base/$name.hdr" "$OUT/head/$variant.hdr" || true
			if ! cmp -s "$OUT/base/$name.cmp" "$OUT/head/$variant.cmp"; then
				diff -u <(jq -S . "$OUT/base/$name.cmp" 2>/dev/null || cat "$OUT/base/$name.cmp") \
					<(jq -S . "$OUT/head/$variant.cmp" 2>/dev/null || cat "$OUT/head/$variant.cmp") | head -40 || true
			fi
		done
	fi
done < tests/api/urls.txt

echo
if [ "$fail" = 0 ]; then
	echo "OK: $n anrop identiska mot $BASE."
else
	echo "FEL: svaren skiljer sig från $BASE (detaljer ovan, filer i .api-test/)." >&2
	exit 1
fi
