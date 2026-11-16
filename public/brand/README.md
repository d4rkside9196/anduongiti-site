# An Duong ITI brand assets

These files use only the approved `public/anduong-logo-dark.png` (495×200 RGBA) and the existing Cream (`#faf7f2`) / Forest (`#0f3623`) identity. No substitute logo, stock photography, or external asset is used.

| Asset | Dimensions | Source and treatment |
| --- | --- | --- |
| `favicon.svg` | 128×128 SVG viewport | The green mark is cropped from the approved PNG at `95x100+400+0`, resized proportionally, centered, and embedded as a PNG data URL so the favicon is self-contained. |
| `apple-touch-icon.png` | 180×180 PNG | Full approved dark logo, resized within 150×150 and centered on an opaque Cream background. |
| `og-default.png` | 1200×630 PNG | Full approved dark logo, resized within 720×720 and centered on a Cream background. |

## Regeneration

From the repository root, with ImageMagick 7 (`magick`) and PHP available:

```bash
bash scripts/generate-brand-assets.sh
```

The script overwrites only the three generated files in `public/brand/`. Review the visual result and commit the source generator, assets, and this README together. Do not run it as part of the frontend build.
