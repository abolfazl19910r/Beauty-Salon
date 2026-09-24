"""
Mahru (ماهرو) brand assets — logo direction «الف» (crescent + sparkle), chosen 2026-09-25.

Regenerates every file in public/brand/ plus the root favicons/icons. The Persian wordmark
(Noto Nastaliq Urdu, weight 700) and the Latin wordmark (Cormorant Garamond, weight 600) are
shaped with HarfBuzz and written as outlines, so the SVGs do not depend on any font.

Requirements: pip install uharfbuzz fonttools cairosvg ; ImageMagick (convert) for favicon.ico
Fonts (OFL, from github.com/google/fonts): run with FONT_DIR containing
  NotoNastaliqUrdu[wght].ttf, CormorantGaramond[wght].ttf, Vazirmatn[wght].ttf
Usage: python3 docs/brand/generate_brand_assets.py <FONT_DIR> <PROJECT_ROOT>
"""
import math, os, subprocess, sys, io
import uharfbuzz as hb
import cairosvg
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen

FONT_DIR, ROOT = sys.argv[1], sys.argv[2]
OUT = os.path.join(ROOT, 'public', 'brand')
os.makedirs(OUT, exist_ok=True)

DARK, BROWN, CREAM, GOLD, GOLD_LIGHT, GOLD_DEEP, STAR_LIGHT = '#1A1410', '#2E2117', '#F8F3E9', '#C9A24B', '#EBD69B', '#A67F2E', '#F3E4B8'


def instance(name, weight):
    font = TTFont(os.path.join(FONT_DIR, name))
    if 'fvar' in font:
        instancer.instantiateVariableFont(font, {'wght': weight}, inplace=True)
    buf = io.BytesIO(); font.save(buf)
    return TTFont(io.BytesIO(buf.getvalue())), buf.getvalue()


def shape(font_name, weight, text, size, tracking=0.0):
    """Outline of `text` at `size` px: (svg path d, ink bbox (x0,y0,x1,y1)), baseline at y=0."""
    tt, data = instance(font_name, weight)
    upem = tt['head'].unitsPerEm
    face = hb.Face(data); font = hb.Font(face)
    buf = hb.Buffer(); buf.add_str(text); buf.guess_segment_properties()
    hb.shape(font, buf, {})
    glyphs = tt.getGlyphSet(); order = tt.getGlyphOrder()
    scale = size / upem
    pen = SVGPathPen(glyphs); bounds = BoundsPen(glyphs)
    x = 0.0
    for info, pos in zip(buf.glyph_infos, buf.glyph_positions):
        name = order[info.codepoint]
        t = (scale, 0, 0, -scale, (x + pos.x_offset) * scale, -pos.y_offset * scale)
        glyphs[name].draw(TransformPen(pen, t)); glyphs[name].draw(TransformPen(bounds, t))
        x += pos.x_advance + tracking * upem
    return pen.getCommands(), bounds.bounds


def crescent(cx1, cy1, r1, cx2, cy2, r2):
    """Path of circle 1 minus circle 2 (the two must intersect)."""
    d = math.hypot(cx2 - cx1, cy2 - cy1)
    a = (r1 * r1 - r2 * r2 + d * d) / (2 * d); h = math.sqrt(r1 * r1 - a * a)
    mx, my = cx1 + a * (cx2 - cx1) / d, cy1 + a * (cy2 - cy1) / d
    p = (mx + h * (cy2 - cy1) / d, my - h * (cx2 - cx1) / d)
    q = (mx - h * (cy2 - cy1) / d, my + h * (cx2 - cx1) / d)

    def arc(c, r, start, end, toward):
        # travel on circle c from start to end through the side facing `toward`, choosing direction
        ang = lambda pt: math.atan2(pt[1] - c[1], pt[0] - c[0])
        s, e, t = ang(start), ang(end), ang(toward)
        span = (e - s) % (2 * math.pi); tspan = (t - s) % (2 * math.pi)
        sweep = 1 if tspan < span else 0
        arc_len = span if sweep else 2 * math.pi - span
        return f'A{r:.3f} {r:.3f} 0 {1 if arc_len > math.pi else 0} {sweep} {end[0]:.3f} {end[1]:.3f}'

    away = (cx1 - (cx2 - cx1), cy1 - (cy2 - cy1))
    outer = arc((cx1, cy1), r1, p, q, away)
    inner = arc((cx2, cy2), r2, q, p, (cx1, cy1))
    return f'M{p[0]:.3f} {p[1]:.3f} {outer} {inner} Z'


def sparkle(x, y, s):
    k = lambda dx, dy: f'{x + dx * s:.3f} {y + dy * s:.3f}'
    return (f'M{k(0,-15)} C{k(2,-4)} {k(4,-2)} {k(15,0)} C{k(4,2)} {k(2,4)} {k(0,15)} '
            f'C{k(-2,4)} {k(-4,2)} {k(-15,0)} C{k(-4,-2)} {k(-2,-4)} {k(0,-15)} Z')


GRAD = (f'<linearGradient id="mahru-gold" x1="0" y1="0" x2="1" y2="1">'
        f'<stop offset="0" stop-color="{GOLD_LIGHT}"/><stop offset="0.55" stop-color="{GOLD}"/>'
        f'<stop offset="1" stop-color="{GOLD_DEEP}"/></linearGradient>')

# نشان در مختصات ۲۰۰×۲۰۰ — همون هندسه‌ی برگه‌ی الف
CRESCENT = crescent(100, 104, 78, 132, 76, 66)
STARS = [(142, 82, 1.0, STAR_LIGHT), (168, 124, 0.45, GOLD_LIGHT)]


STARS_ON_LIGHT = [GOLD_DEEP, GOLD]  # ستاره‌های روشن روی زمینه‌ی روشن دیده نمی‌شن


def mark_group(fill='url(#mahru-gold)', stars=True, star_fill=None, light=False):
    parts = [f'<path d="{CRESCENT}" fill="{fill}"/>']
    if stars:
        for i, (x, y, s, c) in enumerate(STARS):
            color = star_fill or (STARS_ON_LIGHT[i] if light else c)
            parts.append(f'<path d="{sparkle(x, y, s)}" fill="{color}"/>')
    return ''.join(parts)


def crescent_bbox(cx1, cy1, r1, cx2, cy2, r2, extra=()):
    """جعبه‌ی جوهر هلال (نقاط دایره‌ی ۱ بیرون از دایره‌ی ۲) + نقاط اضافه (ستاره‌ها)."""
    pts = [(cx1 + r1 * math.cos(t / 720 * 2 * math.pi), cy1 + r1 * math.sin(t / 720 * 2 * math.pi)) for t in range(720)]
    pts = [pt for pt in pts if math.hypot(pt[0] - cx2, pt[1] - cy2) >= r2] + list(extra)
    xs, ys = [pt[0] for pt in pts], [pt[1] for pt in pts]
    return min(xs), min(ys), max(xs), max(ys)


def svg(w, h, body, defs=GRAD, title='ماهرو'):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.2f} {h:.2f}" width="{w:.0f}" height="{h:.0f}" '
            f'role="img" aria-label="{title}"><title>{title}</title><defs>{defs}</defs>{body}</svg>\n')


def write(name, content):
    with open(os.path.join(OUT, name), 'w', encoding='utf-8') as f:
        f.write(content)


def png(svg_text, path, size):
    cairosvg.svg2png(bytestring=svg_text.encode(), write_to=path, output_width=size, output_height=size)


fa_d, fa_b = shape('NotoNastaliqUrdu[wght].ttf', 700, 'ماهرو', 104)
en_d, en_b = shape('CormorantGaramond[wght].ttf', 600, 'MAHRU', 22, tracking=0.62)
tag_d, tag_b = shape('Vazirmatn[wght].ttf', 300, 'نوبت‌دهی آنلاین سالن‌های زیبایی', 17)
w = lambda b: b[2] - b[0]
hgt = lambda b: b[3] - b[1]

# ── نشان تنها ──
write('mahru-mark.svg', svg(200, 200, mark_group(), title='نشان ماهرو'))
write('mahru-mark-on-light.svg', svg(200, 200, mark_group(light=True), title='نشان ماهرو'))
write('mahru-mark-mono.svg', svg(200, 200, mark_group(fill='currentColor', star_fill='currentColor'), defs='', title='نشان ماهرو'))


def vertical(text_color, tagline=True, light=False):
    """قفل عمودی: نشان، «ماهرو»، MAHRU، (خط + شعار)."""
    W = max(w(fa_b), w(en_b), w(tag_b) if tagline else 0, 190) + 80
    y = 20
    items = [f'<g transform="translate({(W - 190) / 2:.2f} {y}) scale(0.95)">{mark_group(light=light)}</g>']
    y += 190 + 6
    items.append(f'<path transform="translate({(W - w(fa_b)) / 2 - fa_b[0]:.2f} {y - fa_b[1]:.2f})" d="{fa_d}" fill="{text_color}"/>')
    y += hgt(fa_b) + 22
    items.append(f'<path transform="translate({(W - w(en_b)) / 2 - en_b[0]:.2f} {y - en_b[1]:.2f})" d="{en_d}" fill="{'#8A6A2A' if light else GOLD}"/>')
    y += hgt(en_b)
    if tagline:
        y += 22
        items.append(f'<rect x="{W / 2 - 32:.2f}" y="{y:.2f}" width="64" height="1.2" fill="{GOLD}" opacity="0.6"/>')
        y += 20
        items.append(f'<path transform="translate({(W - w(tag_b)) / 2 - tag_b[0]:.2f} {y - tag_b[1]:.2f})" d="{tag_d}" fill="{text_color}" opacity="0.72"/>')
        y += hgt(tag_b)
    return svg(W, y + 20, ''.join(items))


def horizontal(text_color, light=False):
    """قفل افقی (راست‌به‌چپ): نشان سمت راست، «ماهرو» سمت چپ. MAHRU عمداً نیست — دم «ر» بهش می‌خورد
    و در ارتفاع کم (هدر سایت) ریز و ناخوانا می‌شد؛ نسخه‌ی لاتین در قفل عمودی هست."""
    pad, gap = 28, 26
    bx0, by0, bx1, by1 = crescent_bbox(100, 104, 78, 132, 76, 66, extra=[(142 + 15, 82 - 15), (168 + 7, 124)])
    mark_h = hgt(fa_b) * 0.62              # هم‌اندازه‌ی بدنه‌ی حروف، نه بلندای «ا»
    sc = mark_h / (by1 - by0)
    mark_w = (bx1 - bx0) * sc
    W = pad + w(fa_b) + gap + mark_w + pad
    H = pad + hgt(fa_b) + pad
    body_mid = pad + hgt(fa_b) * 0.52      # مرکز بدنه‌ی «ماهرو» (زیر کشیده‌ی «ا»، بالای دم «ر»)
    items = [
        f'<g transform="translate({W - pad - mark_w - bx0 * sc:.2f} {body_mid - mark_h / 2 - by0 * sc:.2f}) scale({sc:.4f})">{mark_group(light=light)}</g>',
        f'<path transform="translate({pad - fa_b[0]:.2f} {pad - fa_b[1]:.2f})" d="{fa_d}" fill="{text_color}"/>',
    ]
    return svg(W, H, ''.join(items))


write('mahru-logo-vertical-on-dark.svg', vertical(CREAM))
write('mahru-logo-vertical-on-light.svg', vertical(BROWN, light=True))
write('mahru-logo-horizontal-on-dark.svg', horizontal(CREAM))
write('mahru-logo-horizontal-on-light.svg', horizontal(BROWN, light=True))


def app_icon(rounded=True, star=True, inset=1.0, heavy=False):
    """آیکون اپ: زمینه‌ی تیره + هلال (طبق برگه‌ی چهارم؛ در اندازه‌ی کوچک بدون ستاره و هلال پررنگ‌تر).
    هلال + ستاره بر اساس جعبه‌ی جوهرشان وسط چیده می‌شن (نه مرکز دایره) و ۵۸٪ آیکون رو پر می‌کنن."""
    r = 46 if rounded else 0
    geo = (100, 104, 58 if heavy else 54, 124, 82, 44)
    cres = crescent(*geo)
    star_pts = [(130 + 13, 92 - 13)] if star else []
    bx0, by0, bx1, by1 = crescent_bbox(*geo, extra=star_pts)
    size = (0.66 if heavy else 0.58) * 200 * inset
    sc = size / max(bx1 - bx0, by1 - by0)
    tx = 100 - (bx0 + bx1) / 2 * sc
    ty = 100 - (by0 + by1) / 2 * sc
    body = [f'<rect width="200" height="200" rx="{r}" fill="{DARK}"/>',
            f'<g transform="translate({tx:.3f} {ty:.3f}) scale({sc:.4f})">',
            f'<path d="{cres}" fill="{"#D4AF5A" if heavy else "url(#mahru-gold)"}"/>']
    if star:
        body.append(f'<path d="{sparkle(130, 92, 0.85)}" fill="{STAR_LIGHT}"/>')
    body.append('</g>')
    return svg(200, 200, ''.join(body), title='ماهرو')


icon = app_icon()
write('mahru-app-icon.svg', icon)
favicon = app_icon(star=False, heavy=True)
with open(os.path.join(ROOT, 'public', 'favicon.svg'), 'w', encoding='utf-8') as f:
    f.write(favicon)

pub = os.path.join(ROOT, 'public')
png(favicon, os.path.join(pub, 'favicon-32x32.png'), 32)
for size in (16, 32, 48):
    png(favicon, f'/tmp/fav-{size}.png', size)
subprocess.run(['convert', '/tmp/fav-16.png', '/tmp/fav-32.png', '/tmp/fav-48.png', os.path.join(pub, 'favicon.ico')], check=True)
png(app_icon(rounded=False), os.path.join(pub, 'apple-touch-icon.png'), 180)   # iOS خودش گوشه‌ها را گرد می‌کند
png(icon, os.path.join(pub, 'logo-512.png'), 512)
png(icon, os.path.join(OUT, 'icon-192.png'), 192)
png(icon, os.path.join(OUT, 'icon-512.png'), 512)
png(app_icon(rounded=False, inset=0.8), os.path.join(OUT, 'icon-maskable-512.png'), 512)  # ناحیه‌ی امن ۸۰٪
cairosvg.svg2png(bytestring=vertical(CREAM).encode(), write_to=os.path.join(OUT, 'mahru-logo-vertical-on-dark.png'), scale=2)
print('done')


# ── تصویر OG برای اشتراک‌گذاری صفحه‌ی فروش (۱۲۰۰×۶۳۰، استاندارد تلگرام/واتساپ/توییتر/لینکدین) ──
og_fa_d, og_fa_b = shape('NotoNastaliqUrdu[wght].ttf', 700, 'ماهرو', 150)
og_tag_d, og_tag_b = shape('Vazirmatn[wght].ttf', 300, 'نوبت‌دهی آنلاین سالن‌های زیبایی', 38)
og_dom_d, og_dom_b = shape('CormorantGaramond[wght].ttf', 600, 'MAHRU.IR', 28, tracking=0.35)
OW, OH = 1200, 630
mark_size = 330
text_right = OW - 110 - mark_size - 70          # لبه‌ی راست ستون متن (نشان سمت راست، RTL)
col_w = max(w(og_fa_b), w(og_tag_b), w(og_dom_b))
block_h = hgt(og_fa_b) + 34 + hgt(og_tag_b) + 40 + hgt(og_dom_b)
ty = (OH - block_h) / 2
og_items = [
    f'<rect width="{OW}" height="{OH}" fill="{DARK}"/>',
    f'<rect x="28" y="28" width="{OW - 56}" height="{OH - 56}" rx="18" fill="none" stroke="{GOLD}" stroke-opacity="0.35" stroke-width="1.5"/>',
    f'<g transform="translate({OW - 110 - mark_size} {(OH - mark_size) / 2:.2f}) scale({mark_size / 200:.4f})">{mark_group()}</g>',
    f'<path transform="translate({text_right - w(og_fa_b) - og_fa_b[0]:.2f} {ty - og_fa_b[1]:.2f})" d="{og_fa_d}" fill="{CREAM}"/>',
    f'<path transform="translate({text_right - w(og_tag_b) - og_tag_b[0]:.2f} {ty + hgt(og_fa_b) + 34 - og_tag_b[1]:.2f})" d="{og_tag_d}" fill="{CREAM}" opacity="0.78"/>',
    f'<path transform="translate({text_right - w(og_dom_b) - og_dom_b[0]:.2f} {ty + hgt(og_fa_b) + 34 + hgt(og_tag_b) + 40 - og_dom_b[1]:.2f})" d="{og_dom_d}" fill="{GOLD}"/>',
]
og_svg = svg(OW, OH, ''.join(og_items), title='ماهرو — نوبت‌دهی آنلاین سالن‌های زیبایی')
write('mahru-og.svg', og_svg)
cairosvg.svg2png(bytestring=og_svg.encode(), write_to=os.path.join(OUT, 'mahru-og.png'), output_width=OW, output_height=OH)
print('og done')
