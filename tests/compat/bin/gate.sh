#!/bin/bash
# Release gate driver (inventory 14.6, review 5.5): theme rows x (baseline, candidates), compare + budgets.
#
#   NODE_PATH=<playwright + @axe-core/playwright> tests/compat/bin/gate.sh <wp-cli> <site url> <plugins dir> <out dir> \
#       [--base <free ref>@<pro ref>] [--cand <free ref>@<pro ref>]... [--rows <theme>[:<k-elements dir>],...] [--only <fixture substring>] [--no-axe]
#
# Defaults: base 1ea71dd@124127b (free 1.9.2 + Pro 1.7.0), candidates main@main and main@124127b (free 2.0 next to
# Pro 1.7.0). Free only (no Pro): --base 1ea71dd@none --cand main@none. Rows: KLEO 6.2.4, KLEO 6.2.7, KLEO 7 (with K Elements 7), SweetDate 4.1.7, BuddyApp 2.1.7,
# Twenty Twenty-Five, Hello Elementor, Astra. A theme row whose theme folder is missing is reported as SKIP.
#
# THROWAWAY site only: the plugin folders must be git clones used as test subjects (refs are checked out detached).
# Per row and candidate: compare.js against the row's baseline (identical widget HTML, <= 1% pixels, no new notices
# or axe ids; intended differences decided for that theme in accepted.json pass as accepted) and budgets.js on the
# candidate (JS/CSS gzip, no jQuery handles, no dashicons for visitors).
# Prints one summary table; exit 0 only when every compare and budget passes.
set -u
WP="$1"; SITE="$2"; PLUGINS="$3"; OUT="$4"; shift 4
HERE=$(cd "$(dirname "$0")/.." && pwd)
BASE="1ea71dd@124127b"; CANDS=(); ROWS="kleo:k-elements,kleo627:k-elements,kleo7:k-elements-7,sweetdate417,buddyapp,twentytwentyfive,hello-elementor,astra"; ONLY=""; EXTRA=""
while [ $# -gt 0 ]; do
	case "$1" in
		--base) BASE="$2"; shift 2 ;;
		--cand) CANDS+=("$2"); shift 2 ;;
		--rows) ROWS="$2"; shift 2 ;;
		--only) ONLY="--only $2"; shift 2 ;;
		--no-axe) EXTRA="$EXTRA --no-axe"; shift ;;
		*) echo "unknown option $1" >&2; exit 2 ;;
	esac
done
[ ${#CANDS[@]} -eq 0 ] && CANDS=("main@main" "main@124127b")
FREE="$PLUGINS/stax-buddy-builder"; PRO="$PLUGINS/stax-buddy-builder-pro"
THEMES="$(dirname "$PLUGINS")/themes"
for c in "$FREE" "$PRO"; do
	[ -d "$c/.git" ] || { echo "$c is not a git clone: refusing" >&2; exit 2; }
	[ -z "$(git -C "$c" status --porcelain --untracked-files=no)" ] || { echo "$c has local changes: refusing" >&2; exit 2; }
	git -C "$c" fetch -q origin 2>/dev/null
done
orig_theme=$("$WP" theme list --status=active --field=name 2>/dev/null)

ref() { # <clone> <ref>: branch names resolve to the fetched origin branch.
	if git -C "$1" rev-parse -q --verify "origin/$2" >/dev/null; then echo "origin/$2"; else echo "$2"; fi
}
use() { # <free>@<pro>; pro "none" runs BuddyBuilder alone (free only rows)
	local f="${1%@*}" p="${1#*@}"
	"$WP" plugin deactivate stax-buddy-builder-pro >/dev/null 2>&1
	git -C "$FREE" -c advice.detachedHead=false checkout -q --detach "$(ref "$FREE" "$f")" || return 1
	if [ "$p" = "none" ]; then
		"$WP" plugin activate stax-buddy-builder >/dev/null 2>&1
		return 0
	fi
	git -C "$PRO" -c advice.detachedHead=false checkout -q --detach "$(ref "$PRO" "$p")" || return 1
	"$WP" plugin activate stax-buddy-builder stax-buddy-builder-pro >/dev/null 2>&1
}
snap() { node "$HERE/run.js" --site "$SITE" --wp "$WP" --label "$1" --out "$OUT/$1" $ONLY $EXTRA | tail -1; }

mkdir -p "$OUT"
SUMMARY="$OUT/summary.md"
printf '# Gate %s\n\nBase %s. Site %s.\n\n| Row | Candidate | Compare | Budgets |\n|---|---|---|---|\n' "$(date '+%Y-%m-%d %H:%M')" "$BASE" "$SITE" > "$SUMMARY"
fail=0
IFS=',' read -ra RLIST <<< "$ROWS"
for row in "${RLIST[@]}"; do
	theme="${row%%:*}"; ke=""; [ "$row" != "$theme" ] && ke="${row#*:}"
	if [ ! -d "$THEMES/$theme" ]; then
		printf '| %s | | SKIP (theme not installed) | |\n' "$theme" >> "$SUMMARY"; continue
	fi
	"$WP" plugin deactivate k-elements k-elements-7 >/dev/null 2>&1
	[ -n "$ke" ] && "$WP" plugin activate "$ke" >/dev/null 2>&1
	"$WP" theme activate "$theme" >/dev/null 2>&1
	echo "== row $theme ${ke:+($ke)}"
	use "$BASE" || { echo "bad base ref"; exit 2; }
	snap "$theme-base"
	for cand in "${CANDS[@]}"; do
		lab="$theme-$(echo "$cand" | tr '@/' '+-')"
		use "$cand" || { printf '| %s | %s | bad ref | |\n' "$theme" "$cand" >> "$SUMMARY"; fail=1; continue; }
		snap "$lab"
		cmp=$(node "$HERE/compare.js" "$OUT/$theme-base" "$OUT/$lab" --row "$theme" --report "$OUT/$lab-compare.md"); [ $? -eq 0 ] || fail=1
		cmp=$(echo "$cmp" | grep -m1 -oE 'Cases: [0-9]+, passed: [0-9]+, failed: [0-9]+, accepted differences: [0-9]+' | sed -E 's/Cases: ([0-9]+), passed: ([0-9]+), failed: [0-9]+, accepted differences: ([0-9]+)/\2\/\1 (\3 accepted)/; s/ \(0 accepted\)//' || echo "no result")
		bud=$(node "$HERE/budgets.js" "$OUT/$lab" --report "$OUT/$lab-budgets.md"); [ $? -eq 0 ] || fail=1
		printf '| %s | %s | %s | %s |\n' "$theme" "$cand" "$cmp" "$(echo "$bud" | sed 's/ -> .*//; s/^budgets [^:]*: //')" >> "$SUMMARY"
		echo "   $cand: $cmp; $bud"
	done
done

"$WP" plugin deactivate k-elements-7 >/dev/null 2>&1
"$WP" plugin activate k-elements >/dev/null 2>&1
"$WP" theme activate "$orig_theme" >/dev/null 2>&1
use "main@main" >/dev/null 2>&1
"$WP" plugin activate stax-buddy-builder-pro >/dev/null 2>&1
echo; cat "$SUMMARY"
echo "result: $([ $fail = 0 ] && echo PASS || echo FAIL)"
[ $fail = 0 ]
