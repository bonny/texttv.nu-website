#!/bin/bash
# Kör lighthouse 5 URL × 3 körningar (mobil) och spara JSON+HTML.
# Idempotent: hoppar över redan-existerande filer.
set -uo pipefail

CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
export CHROME_PATH
OUT="$(dirname "$0")"

declare -a URLS=(
  "root|https://texttv.nu/"
  "p100|https://texttv.nu/100"
  "p300|https://texttv.nu/300"
  "p700|https://texttv.nu/700"
  "arkiv|https://texttv.nu/119/man-dod-pa-hotell-i-ostersund-37167002"
)

for entry in "${URLS[@]}"; do
  slug="${entry%%|*}"
  url="${entry#*|}"
  for run in 1 2 3; do
    json="$OUT/${slug}-run${run}.json"
    html="$OUT/${slug}-run${run}.html"
    if [ -s "$json" ]; then
      echo "SKIP $slug run$run (finns)"
      continue
    fi
    echo "=== $slug run$run -> $url ==="
    npx -y lighthouse "$url" \
      --quiet \
      --chrome-flags="--headless=new --no-sandbox" \
      --form-factor=mobile \
      --output=json --output=html \
      --output-path="$OUT/${slug}-run${run}" \
      2>&1 | tail -3
  done
done

echo "=== KLART ==="
ls -la "$OUT"/*.json 2>/dev/null | wc -l
