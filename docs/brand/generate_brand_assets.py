"""
Mahru (ماهرو) brand assets — new mark (2026-10-14): gold double crescent, three figures, constellation.
Source of truth: docs/brand/kit/ (designer's kit + brand-guide.md). Persian name stays «ماهرو» (decision 2026-10-14).

Regenerates public/brand/*, the root favicons and logo-512.png, and — when APP_ROOT is given — the Expo app
icons on the `mobile-app` orphan branch (assets/ = staff, with a «همکار» badge; assets/customer/ = customer).
Wordmarks («ماهرو» Vazirmatn ExtraBold, MAHRU Sora 600 tracked .22em) are shaped with HarfBuzz and written as
outlines, so the SVGs do not depend on any installed font.

Requirements: pip install uharfbuzz fonttools brotli cairosvg pillow
FONT_DIR must contain (OFL): Vazirmatn-ExtraBold.ttf, Vazirmatn-Bold.ttf (npm package `vazirmatn`) and
sora-latin-600-normal.woff2 (npm package `@fontsource/sora`).
Usage: python3 docs/brand/generate_brand_assets.py <FONT_DIR> <PROJECT_ROOT> [APP_ROOT]
"""
import io, re, sys, shutil, os, tempfile
import cairosvg, uharfbuzz as hb
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from PIL import Image, ImageChops

FONTS, WEB = sys.argv[1], sys.argv[2]
APP = sys.argv[3] if len(sys.argv) > 3 else None
KIT = os.path.join(WEB, 'docs', 'brand', 'kit')
VZ = f'{FONTS}/Vazirmatn-ExtraBold.ttf'
VZB = f'{FONTS}/Vazirmatn-Bold.ttf'
SORA_W2 = f'{FONTS}/sora-latin-600-normal.woff2'
SORA = os.path.join(tempfile.gettempdir(), 'mahru-sora-600.ttf')
f = TTFont(SORA_W2); f.flavor = None; f.save(SORA)

ESPRESSO, TAUPE, PAPER = '#1A1410', '#B7A78F', '#6B5D48'
transparent = open(f'{KIT}/mahru-logo-transparent.svg').read()
# بدنه‌ی نشان (بدون title/defs) — گرادیان کاربری 160,145 → 360,370
body = transparent[transparent.index('<g id="crescent"'):transparent.rindex('</svg>')].strip()

def grad(gid, c1, c2, x1=160, y1=145, x2=360, y2=370):
    return f'<linearGradient id="{gid}" gradientUnits="userSpaceOnUse" x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}"><stop offset="0" stop-color="{c1}"/><stop offset="1" stop-color="{c2}"/></linearGradient>'

def mark(gid='mahru-gold', fill=None):
    b = body.replace('url(#mahru-gold)', fill or f'url(#{gid})')
    return b

def svg(w, h, inner, defs='', label='ماهرو'):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:g} {h:g}" width="{w:g}" height="{h:g}" role="img" aria-label="{label}"><title>{label}</title><defs>{defs}</defs>{inner}</svg>\n'

GOLD_DARK = ('#EBD69B', '#A67F2E')          # روی تیره (کیت)
GOLD_LIGHT = ('#C9A455', '#7A5A1C')         # روی روشن (توکن‌های --lg1/--lg2 تم روشن لندینگ)

def text_path(text, font, size, rtl, tracking=0.0):
    """متن → (d، عرض، ascent) در مختصات y پایین‌رو؛ baseline روی y=0"""
    blob = hb.Blob.from_file_path(font); face = hb.Face(blob); hbf = hb.Font(face)
    buf = hb.Buffer(); buf.add_str(text); buf.guess_segment_properties()
    if rtl: buf.direction = 'rtl'
    hb.shape(hbf, buf, {})
    tt = TTFont(font); gs = tt.getGlyphSet(); order = tt.getGlyphOrder(); upm = tt['head'].unitsPerEm
    s = size / upm; x = 0.0; pen = SVGPathPen(gs)
    for info, pos in zip(buf.glyph_infos, buf.glyph_positions):
        name = order[info.codepoint]
        tp = TransformPen(pen, (s, 0, 0, -s, (x + pos.x_offset) * s, -pos.y_offset * s))
        gs[name].draw(tp)
        x += pos.x_advance + tracking * upm
    width = (x - tracking * upm) * s
    return pen.getCommands(), width

def lockup(light, vertical):
    c1, c2 = GOLD_LIGHT if light else GOLD_DARK
    word_fill = '#21190F' if light else '#F3EADB'
    tag_fill = PAPER if light else TAUPE
    fa, fa_w = text_path('ماهرو', VZ, 96, True)
    la, la_w = text_path('MAHRU', SORA, 26, False, tracking=0.22)
    defs = grad('mahru-gold', c1, c2)
    # نشان در آرت‌بورد ۵۱۲ از حدود 145 تا 367 است → برش 140..372 (232)
    if not vertical:
        mk = 232; pad = 16; gap = 18
        text_w = max(fa_w, la_w)
        W = pad + text_w + gap + mk + pad; H = mk + 2 * pad
        mx = W - pad - mk
        inner = f'<g transform="translate({mx - 140:.2f} {pad - 140:.2f})">{mark()}</g>'
        tx = pad + (text_w - fa_w)
        inner += f'<path d="{fa}" fill="{word_fill}" transform="translate({tx:.2f} {pad + 128:.2f})"/>'
        inner += f'<path d="{la}" fill="{tag_fill}" transform="translate({pad + text_w - la_w:.2f} {pad + 186:.2f})"/>'
    else:
        mk = 232; pad = 20
        W = max(mk, fa_w, la_w) + 2 * pad; H = pad + mk + 150 + pad
        inner = f'<g transform="translate({(W - mk) / 2 - 140:.2f} {pad - 140:.2f})">{mark()}</g>'
        inner += f'<path d="{fa}" fill="{word_fill}" transform="translate({(W - fa_w) / 2:.2f} {pad + mk + 92:.2f})"/>'
        inner += f'<path d="{la}" fill="{tag_fill}" transform="translate({(W - la_w) / 2:.2f} {pad + mk + 140:.2f})"/>'
    return svg(round(W, 2), round(H, 2), inner, defs)

def png(svgtext, w, h=None):
    return Image.open(io.BytesIO(cairosvg.svg2png(bytestring=svgtext.encode(), output_width=w, output_height=h or w))).convert('RGBA')

B = f'{WEB}/public/brand'
# ── وب ──
for name in ['favicon.svg', 'favicon.ico', 'apple-touch-icon.png']:
    shutil.copy(f'{KIT}/{name}', f'{WEB}/public/{name}')
png(open(f'{KIT}/favicon.svg').read(), 32).save(f'{WEB}/public/favicon-32x32.png', optimize=True)
Image.open(f'{KIT}/mahru-logo-1024.png').resize((512, 512), Image.LANCZOS).save(f'{WEB}/public/logo-512.png', optimize=True)
for n in ['icon-192.png', 'icon-512.png', 'icon-maskable-512.png']:
    shutil.copy(f'{KIT}/icons/{n}', f'{B}/{n}')
for n in ['mahru-logo-full.svg', 'mahru-logo-transparent.svg', 'mahru-mono-black.svg', 'mahru-mono-white.svg']:
    shutil.copy(f'{KIT}/{n}', f'{B}/{n}')

crop = '140 140 232 232'  # فقط نشان، بی‌حاشیه
def mark_only(c1, c2, label='نشان ماهرو'):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{crop}" width="232" height="232" role="img" aria-label="{label}"><title>{label}</title><defs>{grad("mahru-gold", c1, c2)}</defs>{mark()}</svg>\n'
open(f'{B}/mahru-mark.svg', 'w').write(mark_only(*GOLD_DARK))
open(f'{B}/mahru-mark-on-light.svg', 'w').write(mark_only(*GOLD_LIGHT))
open(f'{B}/mahru-mark-mono.svg', 'w').write(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{crop}" width="232" height="232" role="img" aria-label="نشان ماهرو"><title>نشان ماهرو</title>{mark(fill="currentColor")}</svg>\n')
shutil.copy(f'{KIT}/mahru-logo-full.svg', f'{B}/mahru-app-icon.svg')
for light in (False, True):
    for vertical in (False, True):
        n = f'mahru-logo-{"vertical" if vertical else "horizontal"}-on-{"light" if light else "dark"}.svg'
        open(f'{B}/{n}', 'w').write(lockup(light, vertical))
v = open(f'{B}/mahru-logo-vertical-on-dark.svg').read()
vw, vh = [float(x) for x in re.search(r'viewBox="0 0 ([\d.]+) ([\d.]+)"', v).groups()]
png(v, 600, round(600 * vh / vw)).save(f'{B}/mahru-logo-vertical-on-dark.png', optimize=True)

# og ۱۲۰۰×۶۳۰
tag, tag_w = text_path('نوبت‌دهی آنلاین و مدیریت سالن زیبایی', VZB, 44, True)
h = open(f'{B}/mahru-logo-horizontal-on-dark.svg').read()
hw, hh = [float(x) for x in re.search(r'viewBox="0 0 ([\d.]+) ([\d.]+)"', h).groups()]
inner_h = h[h.index('</defs>') + 7:h.rindex('</svg>')]
sc = 560 / hw
og = (f'<rect width="1200" height="630" fill="{ESPRESSO}"/>'
      f'<circle cx="600" cy="260" r="300" fill="url(#glow)"/>'
      f'<g transform="translate({600 - 280:.2f} {120:.2f}) scale({sc:.4f})">{inner_h}</g>'
      f'<path d="{tag}" fill="{TAUPE}" transform="translate({600 - tag_w / 2:.2f} 500)"/>')
ogdefs = grad('mahru-gold', *GOLD_DARK) + '<radialGradient id="glow"><stop offset="0" stop-color="#A67F2E" stop-opacity=".22"/><stop offset="1" stop-color="#A67F2E" stop-opacity="0"/></radialGradient>'
ogsvg = svg(1200, 630, og, ogdefs, 'ماهرو — نوبت‌دهی آنلاین سالن‌های زیبایی')
open(f'{B}/mahru-og.svg', 'w').write(ogsvg)
png(ogsvg, 1200, 630).convert('RGB').save(f'{B}/mahru-og.png', optimize=True)

# آیکون اپ مشتری روی صفحه‌ی وب (بنر «باز کردن در اپ»)
shutil.copy(f'{KIT}/icons/icon-192.png', f'{B}/mahru-customer-app-192.png')

# ── اپ (Expo) ──
def app_set(out, badge):
    os.makedirs(out, exist_ok=True)
    bdg_w = 0.30
    def compose(size, mark_frac, cy_frac, with_bg, with_badge, mono=False):
        S = size; mk = S * mark_frac; scale = mk / 232
        cx = S / 2; cy = S * cy_frac
        fill = '#FFFFFF' if mono else None
        parts = []
        if with_bg:
            parts.append(f'<rect width="{S}" height="{S}" fill="{ESPRESSO}"/>')
        parts.append(f'<g transform="translate({cx - mk / 2:.2f} {cy - mk / 2:.2f}) scale({scale:.5f}) translate(-140 -140)">{mark(fill=fill)}</g>')
        if with_badge:
            pw, ph = S * bdg_w, S * 0.105
            px, py = cx - pw / 2, cy + mk / 2 + S * 0.035
            t, tw = text_path('همکار', VZ, ph * 0.62, True)
            text_el = f'<path d="{t}" transform="translate({cx - tw / 2:.2f} {py + ph * 0.73:.2f})"'
            if mono:
                # آیکون تک‌رنگ اندروید فقط آلفا را می‌خواند: متن باید از قرص بریده شود
                parts.append(f'<mask id="badge-cut"><rect x="{px:.2f}" y="{py:.2f}" width="{pw:.2f}" height="{ph:.2f}" rx="{ph / 2:.2f}" fill="#FFFFFF"/>{text_el} fill="#000000"/></mask>')
                parts.append(f'<rect x="{px:.2f}" y="{py:.2f}" width="{pw:.2f}" height="{ph:.2f}" rx="{ph / 2:.2f}" fill="#FFFFFF" mask="url(#badge-cut)"/>')
            else:
                parts.append(f'<rect x="{px:.2f}" y="{py:.2f}" width="{pw:.2f}" height="{ph:.2f}" rx="{ph / 2:.2f}" fill="url(#mahru-gold-badge)"/>')
                parts.append(f'{text_el} fill="{ESPRESSO}"/>')
        defs = grad('mahru-gold', *GOLD_DARK) + grad('mahru-gold-badge', '#EBD69B', '#A67F2E', 0, 0, S, S * 0.2)
        return png(svg(S, S, ''.join(parts), defs), S)
    # آیکون قدیمی (اندروید قدیم): کاشی کامل؛ نشان ۵۶٪ مثل کیت
    compose(1024, 0.50 if badge else 0.56, 0.44 if badge else 0.5, True, badge).save(f'{out}/icon.png', optimize=True)
    Image.new('RGBA', (1024, 1024), ESPRESSO).save(f'{out}/android-icon-background.png', optimize=True)
    # پیش‌زمینه‌ی adaptive: ناحیه‌ی امن دایره‌ی ۶۱٪ وسط
    compose(1024, 0.40 if badge else 0.46, 0.45 if badge else 0.5, False, badge).save(f'{out}/android-icon-foreground.png', optimize=True)
    # تک‌رنگ: آلفای نسخه‌ی رنگی، با بریدن متن تیره‌ی «همکار» از قرص (cairosvg ماسک SVG را نمی‌کشد)
    color = compose(1024, 0.40 if badge else 0.46, 0.45 if badge else 0.5, False, badge)
    alpha = color.getchannel('A')
    lum = color.convert('L')
    cut = Image.eval(lum, lambda v: 255 if v < 90 else 0)
    alpha = ImageChops.subtract(alpha, cut)
    mono_img = Image.new('RGBA', color.size, (255, 255, 255, 0)); mono_img.putalpha(alpha)
    mono_img.save(f'{out}/android-icon-monochrome.png', optimize=True)
    compose(1024, 0.70, 0.5, False, False).save(f'{out}/splash-icon.png', optimize=True)

if APP:
    app_set(f'{APP}/assets', badge=True)           # «ماهرو همکار»
    app_set(f'{APP}/assets/customer', badge=False)  # «ماهرو»
print('ok')
