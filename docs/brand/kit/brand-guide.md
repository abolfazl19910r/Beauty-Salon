# Mahru · مهرو — Brand Guide

Mahru is a Persian booking and management platform for luxury salons. The mark is a gold double crescent moon. Inside it, three figures stand under a small constellation. Together they stand for care, community and a booking that's always open, day or night.

---

## 1. Logo files

| File | Use it for | Background |
|---|---|---|
| `mahru-logo-full.svg` | Social avatars, splash screens, slides, anywhere the mark sits on its own tile | Espresso `#1A1410` built in |
| `mahru-logo-transparent.svg` | Website header and footer, the app, any dark surface you control | Transparent |
| `mahru-mono-black.svg` | Print, stamps, receipts, embossing, fax/photocopy, light backgrounds | Transparent |
| `mahru-mono-white.svg` | Dark photos, gold foil, one-colour reversed printing | Transparent |
| `favicon.svg` | Browser tab, bookmarks, PWA icon (crescent only, thickened for 16 px) | Rounded espresso square |
| `favicon.ico` | Legacy browser fallback (16 / 32 / 48 px) | — |
| `apple-touch-icon.png` | iOS home screen (180 px, full-bleed; iOS rounds the corners) | — |
| `icons/icon-192.png`, `icons/icon-512.png` | PWA install icons | — |
| `icons/icon-maskable-512.png` | Android adaptive icon (crescent kept inside the 80 % safe zone) | — |
| `mahru-logo-1024.png` | Places that can't take SVG (some social platforms, docs) | Espresso |

All SVGs use a 512 × 512 artboard. Every shape is a plain `<path>`, `<circle>` or `<line>`. There are no masks, clip paths, filters or embedded images, so the files open fully editable in Illustrator, Figma, Inkscape and Affinity. Layers are named `crescent`, `figures` (`heads`, `shoulders`) and `constellation`.

---

## 2. Colour

### Core palette

| Token | Hex | RGB | Role |
|---|---|---|---|
| **Espresso** | `#1A1410` | 26 20 16 | Primary background, ink on gold |
| **Champagne** | `#EBD69B` | 235 214 155 | Gradient start, highlights, headings on dark |
| **Antique Gold** | `#A67F2E` | 166 127 46 | Gradient end, accents |

### Supporting UI tones (from the landing page)

| Token | Hex | Role |
|---|---|---|
| Espresso 2 | `#211912` | Cards, panes |
| Espresso 3 | `#2B2119` | Raised surfaces, gradients |
| Ivory | `#F3EADB` | Body text on dark |
| Taupe | `#B7A78F` | Secondary / muted text |
| Line | `rgba(235,214,155,.15)` | Hairlines and borders |
| Paper | `#F5EFE6` | Light background for print and mono-black use |

### The gold gradient

```css
--mahru-gold: linear-gradient(135deg, #EBD69B 0%, #A67F2E 100%);
```

In the SVGs the gradient is one `linearGradient` (`id="mahru-gold"`) in user-space coordinates. It runs from top-left (160, 145) to bottom-right (360, 370), so every shape shares one continuous light sweep instead of each shape getting its own.

### Contrast (WCAG 2.x)

| Foreground on background | Ratio | OK for |
|---|---|---|
| Champagne `#EBD69B` on Espresso | 12.7 : 1 | Any text |
| Ivory `#F3EADB` on Espresso | 15.3 : 1 | Any text |
| Taupe `#B7A78F` on Espresso | 7.8 : 1 | Any text |
| Antique Gold `#A67F2E` on Espresso | 4.95 : 1 | Body text (AA), icons |
| Espresso on Champagne (gold buttons) | 12.7 : 1 | Any text |
| Antique Gold on Paper `#F5EFE6` | 3.2 : 1 | Large text / decoration only, not body copy |

---

## 3. Typography

| Script | Typeface | Weights | Use |
|---|---|---|---|
| Persian / Arabic | **Vazirmatn** | 400 Regular · 500 Medium · 700 Bold · 800–900 for display | Everything in Persian: headings, UI, body |
| Latin & numerals in a Latin context | **Sora** | 400 · 600 · 700 | The "MAHRU" wordmark, URLs, prices or times shown in Latin digits, English copy |

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Vazirmatn:wght@400;500;700;800;900&display=swap" rel="stylesheet">
```

```css
body  { font-family: "Vazirmatn", "Sora", system-ui, sans-serif; line-height: 1.8; }
.latin, .num { font-family: "Sora", "Vazirmatn", sans-serif; }
.wordmark-latin { font: 600 0.75em "Sora"; letter-spacing: .22em; text-transform: uppercase; }
```

- Persian body text needs generous line height (1.7–1.9). Don't add letter spacing to Persian text, because it breaks the joins between letters.
- In Persian copy use Persian digits (۰۱۲۳). Use Sora with Latin digits only where Latin is expected, such as URLs and codes.
- Wordmark lockup: mark, then **مهرو** in Vazirmatn 800, then **MAHRU** in Sora 600, all caps, tracked +0.22 em, in Taupe.

---

## 4. Logo construction

All values are in the 512-unit artboard. The mark is centred on **(256, 256)**.

| Element | Geometry |
|---|---|
| Outer crescent (thin) | Circle r 111 minus a bite circle r 107.3 shifted 4.3 units toward −32°; tips at −62° and −2°; 8 units at its thickest |
| Inner crescent (thick) | Circle r 94, **concentric** with the outer one, minus a bite circle r 89 at (289, 241) |
| Heads | 3 circles, r 13, at x = 241 / 283 / 325, y = 259 (42-unit rhythm) |
| Shoulders | 3 identical half-ellipses (rx 29, ry 28, top at y 280) on the same 42-unit rhythm, trimmed to the r 94 disc. Each body is overlapped by the one to its left, separated by a circle cut (r 28, offset +21, +7.5), which gives the "crowd" depth |
| Constellation | Stars at (249, 196) r 8 · (321, 221) r 8 · (314, 183) r 7; one 5-unit round-capped line joins the first two |

**Clear space:** keep at least the height of one head and shoulder (≈ 0.25 × the mark's width) empty on every side. In the 512 artboard that is already built into the padding.

**Minimum size**

| Asset | Digital | Print |
|---|---|---|
| Full mark (figures + constellation) | 64 px | 18 mm |
| Favicon (crescent only) | 16 px | 6 mm |

Below 64 px the figures and stars blur together, so switch to `favicon.svg`.

---

## 5. Usage rules

**Do**
- Put the gold mark on Espresso or another dark, low-detail surface.
- Use the mono-black mark on light paper and the mono-white mark on photos or colour.
- Scale the mark proportionally, from the SVG.
- Use the crescent-only favicon for anything 48 px or smaller.

**Don't**
- Recolour the gradient, add extra colours, or swap the gradient direction.
- Add drop shadows, outer glows, bevels or outlines to the mark. A soft gold glow is allowed only behind the mark in hero UI, never baked into the file.
- Rotate, skew or stretch the mark, or flip the crescent so it opens to the left.
- Add a fourth figure, change the number of stars, or add more constellation lines.
- Put the gold mark on mid-tone gold, beige or busy photographs, where contrast falls below 3 : 1.
- Fill the dark gaps between crescents or between figures. They are what make the mark legible.
- Rebuild the mark from a screenshot. Always start from these SVGs.

---

## 6. Web install

1. Copy `favicon.svg`, `favicon.ico`, `apple-touch-icon.png`, `site.webmanifest` and the `icons/` folder to the site root.
2. Paste `head-snippet.html` into `<head>`.
3. If you serve the manifest from a different path, update the `"src"` URLs in `site.webmanifest`.
