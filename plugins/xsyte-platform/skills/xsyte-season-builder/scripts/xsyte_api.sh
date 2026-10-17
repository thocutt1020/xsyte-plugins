#!/usr/bin/env bash
#
# xsyte_api.sh — thin wrapper over the xsyte Setup and Seasons APIs.
#
#   XSYTE_SITE_URL=https://club.hockeysyte.com  XSYTE_API_KEY=...  (environment)
#
#   xsyte_api.sh check
#   xsyte_api.sh structure
#   xsyte_api.sh competition payload.json [--apply]
#   xsyte_api.sh seasons-structure <season_id> [payload.json [--apply]]
#   xsyte_api.sh seasons-schedule  <season_id> [payload.json [--apply]]
#
# Without --apply every POST is forced to dry_run=true, whatever the file
# says. The key is read from the environment and never printed.
set -euo pipefail

usage() { sed -n '2,15p' "$0" | sed 's/^# \{0,1\}//'; exit 2; }

[ $# -ge 1 ] || usage
cmd="$1"; shift

: "${XSYTE_SITE_URL:?Set XSYTE_SITE_URL, e.g. https://club.hockeysyte.com}"
: "${XSYTE_API_KEY:?Set XSYTE_API_KEY from admin > Settings > API keys}"
BASE="${XSYTE_SITE_URL%/}"

have_jq() { command -v jq >/dev/null 2>&1; }
pretty() { if have_jq; then jq .; else cat; echo; fi; }

# Force dry_run unless --apply; needs jq for a safe rewrite, falls back to sed.
body_for() { # $1 file, $2 apply(0|1), $3 optional season_id
  local file="$1" apply="$2" sid="${3:-}"
  local dry; [ "$apply" = "1" ] && dry=false || dry=true
  if have_jq; then
    if [ -n "$sid" ]; then jq --argjson d "$dry" --argjson s "$sid" '.dry_run=$d | .season_id=$s' "$file"
    else jq --argjson d "$dry" '.dry_run=$d' "$file"; fi
  else
    # crude: replace an existing dry_run, or inject one after the opening brace
    if grep -q '"dry_run"' "$file"; then sed -E "s/\"dry_run\"[[:space:]]*:[[:space:]]*(true|false)/\"dry_run\": $dry/" "$file"
    else sed -E "0,/\{/s/\{/{ \"dry_run\": $dry,/" "$file"; fi
  fi
}

get()  { curl -sS -w '\n%{http_code}\n' "$BASE$1" -H "Authorization: Bearer $XSYTE_API_KEY" -H "Accept: application/json"; }
post() { curl -sS -w '\n%{http_code}\n' -X POST "$BASE$1" -H "Authorization: Bearer $XSYTE_API_KEY" -H "Content-Type: application/json" -H "Accept: application/json" --data-binary @-; }

# print body pretty, then the status on stderr; exit non-zero on >= 400
emit() {
  local out status body
  out="$(cat)"; status="${out##*$'\n'}"; body="${out%$'\n'*}"
  printf '%s' "$body" | pretty
  echo "HTTP $status" >&2
  [ "$status" -lt 400 ]
}

apply_flag() { for a in "$@"; do [ "$a" = "--apply" ] && { echo 1; return; }; done; echo 0; }

case "$cmd" in
  check)
    echo "site: $BASE"
    echo "key:  ${XSYTE_API_KEY:0:4}…${XSYTE_API_KEY: -4} (${#XSYTE_API_KEY} chars)"
    get /api/setup/structure | emit >/dev/null && echo "ok: key accepted"
    ;;
  structure)
    get /api/setup/structure | emit ;;
  competition)
    file="${1:?payload.json}"; shift || true
    apply="$(apply_flag "$@")"
    [ "$apply" = "1" ] && echo "APPLYING (dry_run=false)" >&2 || echo "dry run (add --apply to write)" >&2
    body_for "$file" "$apply" | post /api/setup/competition | emit ;;
  seasons-structure|seasons-schedule)
    path="/api/seasons/${cmd#seasons-}"
    sid="${1:?season_id}"; shift || true
    if [ $# -ge 1 ] && [ "$1" != "--apply" ]; then
      file="$1"; shift
      apply="$(apply_flag "$@")"
      [ "$apply" = "1" ] && echo "APPLYING (dry_run=false)" >&2 || echo "dry run (add --apply to write)" >&2
      body_for "$file" "$apply" "$sid" | post "$path" | emit
    else
      get "$path?season_id=$sid" | emit
    fi ;;
  *) usage ;;
esac
