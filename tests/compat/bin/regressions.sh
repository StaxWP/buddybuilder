#!/bin/sh
# Runs the review regression cases (bin/regressions-test.php), each with the plugins it needs.
#   tests/compat/bin/regressions.sh <wp-cli wrapper>
# THROWAWAY site with the compat env mu-plugin only. preview-buddyboss also runs on BuddyBoss Platform when it is
# installed in the site's plugins folder (loaded for that run only, see env/buddyboss.php).
set -u
WP="$1"
HERE=$(cd "$(dirname "$0")/.." && pwd)
TEST="$HERE/bin/regressions-test.php"
NO_ELEMENTOR="--skip-plugins=elementor,elementor-pro"
FAIL=0

run() {
	OUT=$("$WP" "$@" 2>/dev/null)
	RC=$?
	echo "$OUT" | grep -E '^(PASS|FAIL|SKIP) ' || { echo "FAIL $*: no output (fatal?)"; FAIL=1; }
	[ "$RC" -eq 0 ] || FAIL=1
}

run eval-file "$TEST" customizer-block-layout
run $NO_ELEMENTOR eval-file "$TEST" elementor-off
run $NO_ELEMENTOR eval-file "$TEST" settings-save-no-elementor
run eval-file "$TEST" inserter-js-only-block
run eval-file "$TEST" preview-buddyboss
if [ -f "$("$WP" eval 'echo WP_PLUGIN_DIR;' 2>/dev/null)/buddyboss-platform/bp-loader.php" ]; then
	run --require="$HERE/env/buddyboss.php" eval-file "$TEST" preview-buddyboss
else
	echo "SKIP preview-buddyboss: BuddyBoss Platform not installed on this site"
fi
run eval-file "$TEST" sample-member-hidden

exit $FAIL
