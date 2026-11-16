#!/usr/bin/env bash
set -euo pipefail

if ! command -v magick >/dev/null 2>&1; then
    printf 'ImageMagick 7 (magick) is required.\n' >&2
    exit 1
fi

mkdir -p public/brand
TEMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TEMP_DIR"' EXIT

magick public/anduong-logo-dark.png \
    -crop 95x100+400+0 +repage \
    -resize 120x128 -background none -gravity center -extent 128x128 \
    "$TEMP_DIR/brand-mark.png"
MARK_BASE64="$(php -r 'echo base64_encode(file_get_contents($argv[1]));' "$TEMP_DIR/brand-mark.png")"

cat > public/brand/favicon.svg <<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <title>An Duong ITI</title>
  <image width="128" height="128" href="data:image/png;base64,${MARK_BASE64}"/>
</svg>
SVG

magick -size 180x180 xc:'#faf7f2' \
    \( public/anduong-logo-dark.png -resize 150x150 \) \
    -gravity center -composite \
    public/brand/apple-touch-icon.png

magick -size 1200x630 xc:'#faf7f2' \
    \( public/anduong-logo-dark.png -resize 720x720 \) \
    -gravity center -composite \
    public/brand/og-default.png
