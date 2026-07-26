#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"
WORK="$(mktemp -d)"
PACKAGE="$WORK/mpro-text-canvas"

cleanup() {
  rm -rf "$WORK"
}
trap cleanup EXIT

mkdir -p "$DIST" "$PACKAGE"
rm -f "$DIST/mpro-text-canvas.zip"

cp "$ROOT/mpro-text-canvas.php" "$PACKAGE/"
cp "$ROOT/uninstall.php" "$PACKAGE/"
cp "$ROOT/README.md" "$PACKAGE/"
cp "$ROOT/readme.txt" "$PACKAGE/"
cp "$ROOT/CHANGELOG.md" "$PACKAGE/"
cp "$ROOT/RELEASE-NOTES.md" "$PACKAGE/"
cp "$ROOT/LICENSE" "$PACKAGE/"
cp -R "$ROOT/includes" "$PACKAGE/includes"
cp -R "$ROOT/assets" "$PACKAGE/assets"

find "$PACKAGE" -type d -exec chmod 755 {} +
find "$PACKAGE" -type f -exec chmod 644 {} +

(
  cd "$WORK"
  zip -q -r "$DIST/mpro-text-canvas.zip" mpro-text-canvas
)

echo "Built $DIST/mpro-text-canvas.zip"
