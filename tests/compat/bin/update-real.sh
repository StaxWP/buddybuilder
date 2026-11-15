#!/bin/bash
# Review G8: update through WordPress' real updater, from a 1.9.3 zip install (not a git checkout) to a 2.0 package,
# with a persistent object cache when the site has one. THROWAWAY site only.
#
#   tests/compat/bin/update-real.sh <wp-cli> <site url> <plugins dir> <from zip> <to ref> <work dir>
#
# 1. Moves the plugin folder aside if it is a git clone (put back at the end), installs <from zip> as a normal plugin,
#    assigns the starter-kit fixtures in bpb_settings (as a 1.x site has them) and snapshots the pages.
# 2. Builds a test package of <to ref> (git archive of this repo minus .distignore, into <work dir>; never published),
#    serves it from the site itself, offers it through the update_plugins transient and runs `wp plugin update`
#    (Plugin_Upgrader: download, unzip, replace the folder, upgrader_process_complete), then loads an admin request.
# 3. Checks: same version string, migration ran, bpb_templates written, no fatal, pages identical (compare.js).
set -u
WP="$1"; SITE="$2"; PLUGINS="$3"; FROM_ZIP="$4"; TO="$5"; WORK="$6"
HERE=$(cd "$(dirname "$0")/.." && pwd)
REPO=$(cd "$HERE/../.." && pwd)
SLUG=stax-buddy-builder
fail=0
check() { echo "CHECK $1: $2"; [ "$2" = ok ] || fail=1; }
mkdir -p "$WORK"

aside=""
if [ -d "$PLUGINS/$SLUG/.git" ] || [ -L "$PLUGINS/$SLUG" ]; then
	aside="$WORK/aside-$SLUG-$$"
	mv "$PLUGINS/$SLUG" "$aside"
fi
restore() {
	rm -rf "${PLUGINS:?}/$SLUG"
	[ -n "$aside" ] && mv "$aside" "$PLUGINS/$SLUG"
	rm -f "$(dirname "$PLUGINS")/../bpb-update-test.zip"
	"$WP" plugin activate "$SLUG" >/dev/null 2>&1
}
trap restore EXIT

"$WP" plugin install "$FROM_ZIP" --force --activate >/dev/null 2>&1
from=$("$WP" plugin get "$SLUG" --field=version 2>/dev/null)
echo "installed $from from zip"
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
	foreach ( [ "bpb_templates", "bpb_migrations", "bpb_db_version", "bpb_migrations_checked" ] as $o ) { delete_option( $o ); }
' >/dev/null 2>&1
node "$HERE/run.js" --site "$SITE" --wp "$WP" --assigned --no-axe --label before --out "$WORK/before" | tail -1

# Test package of <to ref> (same rules as bin/build-zip.sh), served by the site under test.
pkg="$WORK/pkg"; rm -rf "$pkg"; mkdir -p "$pkg/$SLUG"
git -C "$REPO" archive "$TO" | tar -x -C "$pkg/$SLUG"
while IFS= read -r line; do
	case "$line" in ''|\#*) continue ;; esac
	rm -rf "${pkg:?}/$SLUG/${line#/}"
done < "$REPO/.distignore"
( cd "$pkg" && zip -qr "$WORK/bpb-update-test.zip" "$SLUG" )
public=$(dirname "$(dirname "$PLUGINS")")
cp "$WORK/bpb-update-test.zip" "$public/bpb-update-test.zip"
to_version=$(grep -m1 "Version:" "$pkg/$SLUG/loader.php" | sed 's/.*Version: *//')

"$WP" eval "
	\$t = get_site_transient( 'update_plugins' );
	if ( ! is_object( \$t ) ) { \$t = new stdClass(); }
	\$t->last_checked = time();
	\$t->response['$SLUG/loader.php'] = (object) [ 'slug' => '$SLUG', 'plugin' => '$SLUG/loader.php', 'new_version' => '$to_version', 'package' => '$SITE/bpb-update-test.zip', 'url' => '' ];
	set_site_transient( 'update_plugins', \$t );
	add_filter( 'http_request_host_is_external', '__return_true' );
" >/dev/null 2>&1
upd=$("$WP" plugin update "$SLUG" 2>&1 | tail -2 | tr '\n' ' ')
echo "updater: $upd"
now=$("$WP" plugin get "$SLUG" --field=version 2>/dev/null)
check "updated by the real updater to $to_version" "$([ "$now" = "$to_version" ] && echo ok || echo "got $now")"
check "no .git in the updated folder (zip install)" "$([ ! -d "$PLUGINS/$SLUG/.git" ] && echo ok || echo git)"
"$WP" eval 'wp_set_current_user( 1 ); do_action( "admin_init" );' >/dev/null 2>&1
reg=$("$WP" eval 'echo wp_json_encode( get_option( "bpb_templates" ) );' 2>/dev/null | tail -1)
check "migration on the first admin request (object cache: $("$WP" eval 'echo wp_using_ext_object_cache() ? "on" : "off";' 2>/dev/null | tail -1))" "$(echo "$reg" | grep -q '"member-profile":{"builder":"elementor"' && echo ok || echo "$reg")"
node "$HERE/run.js" --site "$SITE" --wp "$WP" --assigned --no-axe --label after --out "$WORK/after" | tail -1
cmp=$(node "$HERE/compare.js" "$WORK/before" "$WORK/after" --report "$WORK/before-after.md" | sed -n 3p)
echo "$cmp"
node "$HERE/compare.js" "$WORK/before" "$WORK/after" --report "$WORK/before-after.md" >/dev/null || fail=1
rm -f "$public/bpb-update-test.zip"
echo "result: $([ $fail = 0 ] && echo PASS || echo FAIL)"
[ $fail = 0 ]
