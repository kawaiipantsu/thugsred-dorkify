#!/usr/bin/env sh
# THUGSred Dorkify - export a fully static copy to dist/ (or $1).
# The page has no server-side logic beyond rendering, so the output runs on
# any static host: nginx, Caddy, S3, GitHub Pages, or straight from disk.
#   sh scripts/build-static.sh [outdir]
set -eu

root=$(cd "$(dirname "$0")/.." && pwd)
out=${1:-"$root/dist"}

php "$root/scripts/lint-dorks.php"

rm -rf "$out"
mkdir -p "$out"
php "$root/src/public/index.php" > "$out/index.html"
cp -R "$root/src/public/assets" "$root/src/public/fonts" "$out/"
cp "$root/src/public/favicon.svg" "$root/src/public/robots.txt" "$out/"

echo "static build: $out ($(du -sh "$out" | cut -f1))"
