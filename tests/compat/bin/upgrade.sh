#!/bin/bash
# Update in place and roll back (inventory 14.3, 14.6) on a THROWAWAY site whose Free plugin folder is a git clone.
#
#   NODE_PATH=... tests/compat/bin/upgrade.sh <wp-cli wrapper> <site url> <plugins dir> <from ref> <to ref> <out dir> [<expected ref>]
#   e.g. upgrade.sh ~/Sites/kleo7-bbcompat/tools/wp http://kleo7-bbcompat.test \
#          ~/Sites/kleo7-bbcompat/public/wp-content/plugins 1ea71dd 2.0 /tmp/upgrade-192
#
# 1. <from>: assigns the starter-kit fixtures in bpb_settings (the real option, as a 1.x site has it), removes any
#    2.x state, snapshots the assigned pages (run.js --assigned).
# 2. <to>: loads WordPress once as an admin request would (migrations run), checks bpb_templates, snapshots, compares.
#    With <expected ref> (e.g. 1.9.2 when updating from 1.8.0, whose markup 1.9.x already changed), <to> is
#    compared with a snapshot of <expected ref> taken with the same options instead of with <from>.
# 3. wp buddybuilder migrate --rollback=1.9.2, back to <from>: checks the 2.x options are gone, snapshots, compares.
set -u
WP="$1"; SITE="$2"; PLUGINS="$3"; FROM="$4"; TO="$5"; OUT="$6"; EXPECT="${7:-}"
FREE="$PLUGINS/stax-buddy-builder"
HERE=$(cd "$(dirname "$0")/.." && pwd)
orig=$(git -C "$FREE" rev-parse HEAD)
fail=0
co() { git -C "$FREE" -c advice.detachedHead=false checkout -q --detach "$1"; }
# Extra run.js flags for every snapshot, e.g. BPB_COMPAT_RUN_ARGS=--split.
run() { node "$HERE/run.js" --site "$SITE" --wp "$WP" --assigned --no-axe ${BPB_COMPAT_RUN_ARGS:-} --label "$1" --out "$OUT/$1" | tail -1; }
check() { echo "CHECK $1: $2"; [ "$2" = ok ] || fail=1; }

mkdir -p "$OUT"
co "$FROM"
"$WP" eval-file "$HERE/bin/load-fixtures.php" >/dev/null 2>&1
"$WP" eval '
	$ids = [];
	foreach ( [ "member-profile", "members-directory", "group-profile", "groups-directory", "sitewide-activity" ] as $t ) {
		$p = get_posts( [ "post_type" => "elementor_library", "meta_key" => "_bpb_compat_fixture", "meta_value" => "starter-bp-" . $t, "fields" => "ids", "posts_per_page" => 1 ] );
		$ids[ $t ] = $p ? (int) $p[0] : 0;
	}
	$s = (array) get_option( "bpb_settings", [] );
	$s["templates"] = array_merge( isset( $s["templates"] ) ? (array) $s["templates"] : [], $ids );
	update_option( "bpb_settings", $s );
	foreach ( [ "bpb_templates", "bpb_migrations", "bpb_db_version", "bpb_migrations_checked", "bpb_migration_backup_2.0.0-a" ] as $o ) { delete_option( $o ); }
' >/dev/null 2>&1
run from
REF=from
if [ -n "$EXPECT" ]; then
	co "$EXPECT"
	run expected
	REF=expected
	co "$FROM"
fi

co "$TO"
# What the first admin page view after the update does (admin_init -> Runner::maybe_run_on_update).
"$WP" eval 'wp_set_current_user( 1 ); \Buddy_Builder\Migrations\Runner::maybe_run_on_update();' >/dev/null 2>&1
reg=$("$WP" eval 'echo wp_json_encode( get_option( "bpb_templates" ) );' 2>/dev/null | tail -1)
echo "bpb_templates after update: $reg"
check "registry migrated on first admin request" "$(echo "$reg" | grep -q '"member-profile":{"builder":"elementor"' && echo ok || echo missing)"
run to
node "$HERE/compare.js" "$OUT/$REF" "$OUT/to" --report "$OUT/$REF-to.md" | sed -n 3p
node "$HERE/compare.js" "$OUT/$REF" "$OUT/to" --report "$OUT/$REF-to.md" >/dev/null || fail=1

"$WP" buddybuilder migrate --rollback=1.9.2 2>/dev/null | tail -2
co "$FROM"
left=$("$WP" eval 'echo false === get_option( "bpb_templates", false ) ? "ok" : "left";' 2>/dev/null | tail -1)
check "rollback removed bpb_templates" "$left"
run back
node "$HERE/compare.js" "$OUT/from" "$OUT/back" --report "$OUT/from-back.md" | sed -n 3p
node "$HERE/compare.js" "$OUT/from" "$OUT/back" --report "$OUT/from-back.md" >/dev/null || fail=1

co "$orig"
echo "restored $orig; result: $([ $fail = 0 ] && echo PASS || echo FAIL)"
[ $fail = 0 ]
