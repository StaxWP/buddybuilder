#!/bin/sh
# Prepares a THROWAWAY WordPress copy for the compat suite (never a real site):
#   tests/compat/bin/setup-env.sh <wp-cli wrapper> <site wp-content dir>
# - links env/bpb-compat-env.php as a mu-plugin (template switch per request, notice capture, local avatars)
# - activates the BuddyPress components the fixtures need (groups, friends, messages, xprofile, notifications)
# - removes on-screen noise that is not BuddyBuilder: Elementor Pro popups (set to draft), myCred badge plugin
set -eu
WP="$1"
CONTENT="$2"
HERE=$(cd "$(dirname "$0")/.." && pwd)

mkdir -p "$CONTENT/mu-plugins"
ln -sf "$HERE/env/bpb-compat-env.php" "$CONTENT/mu-plugins/bpb-compat-env.php"

for c in xprofile settings friends messages groups notifications activity; do
	"$WP" bp component activate "$c" >/dev/null 2>&1 || true
done

for id in $("$WP" post list --post_type=elementor_library --meta_key=_elementor_template_type --meta_value=popup --post_status=publish --format=ids 2>/dev/null); do
	"$WP" post update "$id" --post_status=draft >/dev/null 2>&1
done
"$WP" plugin deactivate mycred >/dev/null 2>&1 || true
"$WP" rewrite flush >/dev/null 2>&1
echo "compat env ready"
