#!/bin/bash
# Free/Pro pairing and upgrade matrix (inventory 14.5, 14.6) on a THROWAWAY site whose plugin folders are git clones.
#
#   tests/compat/bin/pairing.sh <wp-cli wrapper> <site url> <plugins dir> <free ref>@<pro ref> [...]
#   e.g. pairing.sh ~/Sites/kleo7-bbcompat/tools/wp http://kleo7-bbcompat.test \
#          ~/Sites/kleo7-bbcompat/public/wp-content/plugins 1ea71dd@124127b v1.8.0@v1.6.7 compat-2.0@124127b
#   A ref of "-" deactivates that plugin for the row.
#
# Per row: checks out both refs (detached), loads WordPress through WP-CLI, requests the home page, a member
# profile, the members directory and three admin screens, and reports HTTP codes, fatals ("critical error"),
# BuddyBuilder notices in debug.log, and which BuddyBuilder admin notices are shown.
# The clones are put back on their original commits at the end.
set -u
WP="$1"; SITE="$2"; PLUGINS="$3"; shift 3
FREE="$PLUGINS/stax-buddy-builder"; PRO="$PLUGINS/stax-buddy-builder-pro"
LOG="$(dirname "$PLUGINS")/debug.log"
HERE=$(cd "$(dirname "$0")" && pwd)

orig_free=$(git -C "$FREE" rev-parse HEAD); orig_pro=$(git -C "$PRO" rev-parse HEAD)
if [ -n "$(git -C "$FREE" status --porcelain --untracked-files=no)" ] || [ -n "$(git -C "$PRO" status --porcelain --untracked-files=no)" ]; then
	echo "plugin clones have local changes: refusing" >&2; exit 2
fi

setref() { # <clone> <ref> <plugin slug>
	if [ "$2" = "-" ]; then
		"$WP" plugin deactivate "$3" >/dev/null 2>&1
	else
		git -C "$1" -c advice.detachedHead=false checkout -q --detach "$2" || return 1
		"$WP" plugin activate "$3" >/dev/null 2>&1
	fi
}

cookie=$("$WP" eval 'wp_set_current_user(1); $e=time()+3600; $s=is_ssl()||0===strpos(home_url(),"https"); echo "\nCK:".($s?SECURE_AUTH_COOKIE:AUTH_COOKIE)."=".rawurlencode(wp_generate_auth_cookie(1,$e,$s?"secure_auth":"auth"))."; ".LOGGED_IN_COOKIE."=".rawurlencode(wp_generate_auth_cookie(1,$e,"logged_in"));' 2>/dev/null | grep '^CK:' | cut -c4-)

printf '%-26s %-6s %-30s %-24s %-8s %s\n' "free@pro" "cli" "front (home member members)" "admin (dash plugins bb)" "bpbLog" "notices"
fails=0
for pair in "$@"; do
	f="${pair%@*}"; p="${pair#*@}"
	# Pro first off, so a Pro that refuses this free version cannot block the free switch.
	"$WP" plugin deactivate stax-buddy-builder-pro >/dev/null 2>&1
	setref "$FREE" "$f" stax-buddy-builder || { echo "$pair: bad free ref"; fails=$((fails+1)); continue; }
	setref "$PRO" "$p" stax-buddy-builder-pro || { echo "$pair: bad pro ref"; fails=$((fails+1)); continue; }

	before=$(wc -c < "$LOG" 2>/dev/null || echo 0)
	cli=$("$WP" eval 'echo "ok";' 2>&1 | grep -q '^ok' && echo ok || echo FAIL)
	front=""; bad=0
	for u in / /members/ana/ /members/; do
		body=$(curl -s -w '\n%{http_code}' "$SITE$u"); code=$(echo "$body" | tail -1)
		echo "$body" | grep -q 'critical error' && code="$code!"
		front="$front $code"; [ "$code" = "200" ] || bad=1
	done
	admin=""; notices=""
	for u in /wp-admin/ /wp-admin/plugins.php "/wp-admin/admin.php?page=buddy-builder-settings"; do
		body=$(curl -s -w '\n%{http_code}' -H "Cookie: $cookie" "$SITE$u"); code=$(echo "$body" | tail -1)
		echo "$body" | grep -q 'critical error' && code="$code!"
		admin="$admin $code"
		# Without Free the BuddyBuilder screen does not exist: 403 is the expected answer there.
		if [ "$code" != "200" ] && ! { [ "$f" = "-" ] && [ "$code" = "403" ]; }; then bad=1; fi
		n=$(echo "$body" | grep -oE 'bpb-pairing-notice|requires BuddyBuilder base plugin to be update|plugin requires <strong> BuddyBuilder <\/strong> plugin to be [a-z]+|Update BuddyBuilder Pro to 2|needs BuddyBuilder [0-9.]+ or newer' | sort -u | tr '\n' '|')
		notices="$notices$n"
	done
	bpblog=$(tail -c +"$((before+1))" "$LOG" 2>/dev/null | grep -c 'stax-buddy-builder' || true)
	[ "$cli" = ok ] || bad=1
	[ "$bad" = 1 ] && fails=$((fails+1))
	printf '%-26s %-6s %-30s %-24s %-8s %s\n' "$pair" "$cli" "$front" "$admin" "$bpblog" "$(echo "$notices" | tr '|' '\n' | sort -u | tr '\n' ' ')"
done

"$WP" plugin deactivate stax-buddy-builder-pro >/dev/null 2>&1
git -C "$FREE" -c advice.detachedHead=false checkout -q --detach "$orig_free"
git -C "$PRO" -c advice.detachedHead=false checkout -q --detach "$orig_pro"
"$WP" plugin activate stax-buddy-builder stax-buddy-builder-pro >/dev/null 2>&1
echo "restored free $orig_free, pro $orig_pro; rows failing: $fails"
[ "$fails" = 0 ]
