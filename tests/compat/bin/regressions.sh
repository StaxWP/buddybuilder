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
run eval-file "$TEST" sample-member-preview
for v in valid forged subscriber; do
	run eval-file "$TEST" sample-member-preview-ajax "$v"
done
for v in self friend member guest ''; do
	run eval-file "$TEST" preview-seen-by "$v"
done
run eval-file "$TEST" community-pages-read-only
run eval-file "$TEST" checker-known-issues
run eval-file "$TEST" setup-new-install
run eval-file "$TEST" checker-readylaunch
run eval-file "$TEST" change-look-undo
run eval-file "$TEST" cards-follow-layout
run eval-file "$TEST" layout-actions
run $NO_ELEMENTOR eval-file "$TEST" layout-actions
run eval-file "$TEST" element-settings
run eval-file "$TEST" elementor-basic-controls
run eval-file "$TEST" elementor-panel
run eval-file "$TEST" layout-import
run eval-file "$TEST" notice-dismiss
run eval-file "$TEST" set-as-active
run eval-file "$TEST" html-tag
run eval-file "$TEST" upsell-delay
run eval-file "$TEST" card-line
run eval-file "$TEST" community-pages-rows
run eval-file "$TEST" blocks-stay-on
run eval-file "$TEST" header-parts
run eval-file "$TEST" theme-title-space
run eval-file "$TEST" profile-fields
run eval-file "$TEST" split-header
run eval-file "$TEST" fresh-site-points
run eval-file "$TEST" pro-locks
run eval-file "$TEST" pro-unlicensed
run eval-file "$TEST" pro-expired
run eval-file "$TEST" dead-settings
run eval-file "$TEST" split-header-elementor
run eval-file "$TEST" plain-containers
if [ -n "${BPB_BUDDYBOSS_PLUGIN:-}" ]; then
	run --require="$HERE/env/buddyboss.php" eval-file "$TEST" checker-readylaunch
fi

exit $FAIL
