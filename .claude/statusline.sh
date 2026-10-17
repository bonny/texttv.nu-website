#!/bin/bash
# Statusrad för Claude Code i det här projektet: den globala statusraden
# plus en klickbar länk till lokala dev-miljön om app-containern körs.
input=$(cat)

GLOBAL="$HOME/.claude/statusline-command.sh"
BASE=""
if [ -f "$GLOBAL" ]; then
    BASE=$(printf '%s' "$input" | bash "$GLOBAL")
fi

URL="http://texttv.test:8380"
ESC=$(printf '\033')

# Snabbare än `docker compose ps`; faller tyst om Docker inte är igång.
if docker ps -q --filter name=texttvnu-website-website-1 --filter status=running 2>/dev/null | grep -q .; then
    DEV="🐳 ${ESC}]8;;${URL}${ESC}\\${URL#http://}${ESC}]8;;${ESC}\\"
else
    DEV="🐳 ${ESC}[31mdocker nere${ESC}[0m"
fi

if [ -n "$BASE" ]; then
    printf '%s | %s\n' "$BASE" "$DEV"
else
    printf '%s\n' "$DEV"
fi
