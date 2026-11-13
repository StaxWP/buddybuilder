#!/bin/sh
# Release zip from the committed tree (git archive), minus the paths in .distignore.
#   bin/build-zip.sh [ref] [out dir]   ->   <out dir>/<slug>-<version>.zip, top folder <slug>/
# Builds only; publishing is a separate, approved step.
set -eu
ROOT=$(cd "$(dirname "$0")/.." && pwd)
REF=${1:-HEAD}
OUT=${2:-"$ROOT/../dist"}
# Slug (zip top folder) = text domain; version from the main file header, both read at REF.
HEADER=$(git -C "$ROOT" show "$REF:loader.php")
SLUG=$(printf '%s\n' "$HEADER" | sed -n 's/^ \* Text Domain:[[:space:]]*//p' | head -1 | tr -d '\r')
VERSION=$(printf '%s\n' "$HEADER" | sed -n 's/^ \* Version:[[:space:]]*//p' | head -1 | tr -d '\r')
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

mkdir -p "$OUT" "$TMP/$SLUG"
git -C "$ROOT" archive "$REF" | tar -x -C "$TMP/$SLUG"

# .distignore: "/path" is relative to the plugin root, other patterns match anywhere.
grep -v '^\s*#' "$ROOT/.distignore" | grep -v '^\s*$' | while read -r pattern; do
	case "$pattern" in
		/*) rm -rf "$TMP/$SLUG$pattern" ;;
		*) find "$TMP/$SLUG" -name "$pattern" -exec rm -rf {} + ;;
	esac
done

ZIP="$OUT/$SLUG-$VERSION.zip"
rm -f "$ZIP"
( cd "$TMP" && zip -qrX "$ZIP" "$SLUG" )
echo "$ZIP"
