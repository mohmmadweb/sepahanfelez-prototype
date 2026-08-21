# -*- coding: utf-8 -*-
"""Build the Sepahan Felez A4 product catalog (catalog.html) from scraped live-site data.

Brand identity (from sepahanfelez.ir + logo):
  red #B42332 / dark red #7D1E32, navy #283264, steel #8C92AD, font IRANSans FaNum.
"""
import json, sys, re

DATA = sys.argv[1] if len(sys.argv) > 1 else "catalog-live.json"
PHOTO_MAP = sys.argv[2] if len(sys.argv) > 2 else None
OUT = "catalog.html"

photos_by_cat, hero_by_cat = {}, {}
if PHOTO_MAP:
    _pm = json.load(open(PHOTO_MAP, encoding="utf-8"))
    photos_by_cat = _pm["by_cat"]
    hero_by_cat = _pm["hero"]

RED = "#B42332"
RED_D = "#7D1E32"
NAVY = "#283264"
STEEL = "#8C92AD"
INK = "#1E2438"
PAPER_TINT = "#F3F4F8"

PRICE_COLS = {"نوسان قیمت (ریال)", "قیمت روز (ريال)", "قیمت روز (ریال)", "نمودار", "خرید", "ردیف"}

# ----------------------------------------------------------------------------
# Handcrafted copy per category — grounded in the site's own descriptive text.
# order matters: this is catalog order.
COPY = {
    "توری-حصاری": {
        "name": "توری حصاری (فنس)",
        "en": "Chain-Link Fence",
        "intro": "توری حصاری با بافت لوزی‌شکل از مفتول گالوانیزه تولید می‌شود و پرکاربردترین محصول برای حصارکشی زمین، باغ و محوطه‌های صنعتی است. چشمه و ضخامت مفتول در طیف گسترده‌ای تولید می‌شود و عرض رول نیز مطابق سفارش مشتری قابل تغییر است.",
        "apps": ["حصارکشی زمین و باغ", "فنس‌کشی ویلا و مجتمع", "محوطه‌های صنعتی", "زمین‌های ورزشی", "حاشیه جاده‌ها"],
        "table_note": "جدول بالا تنها بخشی از تولیدات ماست؛ امکان ثبت سفارش در تمامی سایزهای چشمه و ضخامت‌های مفتول وجود دارد.",
        "illo": "chainlink",
    },
    "توری-پرسی": {
        "name": "توری پرسی",
        "en": "Crimped Wire Mesh",
        "intro": "توری پرسی از بافت مشبک مفتول‌های ۲/۵ تا ۵ میلی‌متر به دست می‌آید و با نام‌های توری مشبک و مش پرسی نیز شناخته می‌شود. چشمه‌ها معمولاً مربع و از ۱×۱ تا ۵×۵ سانتی‌متر است و صفحه‌ها در ابعاد استاندارد ۱×۲ متر برش می‌خورند.",
        "apps": ["حصارکشی صنعتی", "نرده و حفاظ", "سرند معادن و دانه‌بندی", "کف‌سازی سازه", "دکوراسیون فلزی"],
        "illo": "crimped",
    },
    "توری-گابیون": {
        "name": "توری گابیون",
        "en": "Gabion Mesh",
        "intro": "توری گابیون (توری سنگی) برای مهار سنگ در سازه‌های حائل به کار می‌رود و باید فشار سنگ‌های درون خود را تحمل کند. با مفتول گالوانیزه یا سیاه در ضخامت‌های ۱/۵ تا ۳ میلی‌متر، عرض ۱ تا ۳ متر و چشمه‌های ۵×۶ تا ۱۰×۱۰ تولید می‌شود؛ چشمه ۸×۱۰ پرکاربردترین سایز آن است.",
        "apps": ["دیوار حائل و سنگ‌چین", "ساماندهی رودخانه و آبراه", "کنترل فرسایش خاک", "پایدارسازی ترانشه", "محوطه‌سازی و لنداسکیپ"],
        "notes": ["چشمهٔ ۸×۱۰ پرکاربردترین سایز بازار است", "طول رول از ۲۰ تا ۶۰ متر مطابق نیاز قابل سفارش است"],
        "illo": "gabion",
    },
    "توری-مرغی": {
        "name": "توری مرغی",
        "en": "Hexagonal Poultry Netting",
        "intro": "توری مرغی با چشمه‌های شش‌ضلعی و بافت تابیده از مفتول نازک گالوانیزه (گرم یا سرد) تولید می‌شود. رول‌های استاندارد ۴۵ متری در عرض‌های ۹۰ و ۱۲۰ سانتی‌متر و وزن‌های مختلف عرضه می‌شوند و سبکی و انعطاف، نصب آن را آسان می‌کند.",
        "apps": ["مرغداری و قفس‌سازی", "باغبانی و گلخانه", "زیرکار گچ و رابیتس", "سقف کاذب", "حصار سبک"],
        "notes": ["گالوانیزهٔ گرم دوام بیشتری نسبت به گالوانیزهٔ سرد دارد", "چشمهٔ استاندارد ۳/۴ اینچ است", "وزن رول شاخص تراکم بافت و استحکام توری است", "عرض‌های ۹۰ و ۱۲۰ سانتی‌متر در رول ۴۵ متری موجود است"],
        "illo": "chicken",
    },
    "توری-فرنگی": {
        "name": "توری فرنگی",
        "en": "Garden Wire Netting",
        "intro": "توری فرنگی گونهٔ مستحکم‌تر توری‌های بافته‌شده با چشمهٔ درشت‌تر است که با مفتول گالوانیزه گرم ۰/۸ میلی‌متر و چشمه ۴ سانتی‌متر در رول‌های ۲۰ متری تولید می‌شود. مقاومت مناسب در برابر زنگ‌زدگی، آن را برای فضای باز مناسب کرده است.",
        "apps": ["گلخانه و باغبانی", "حصار سبک محوطه", "قفس و محفظه", "مصارف ساختمانی"],
        "notes": ["بافت شش‌ضلعی با مفتول ضخیم‌تر و چشمهٔ درشت‌تر از توری مرغی", "پوشش گالوانیزهٔ گرم برای فضای باز و محیط مرطوب مناسب است", "رول استاندارد ۲۰ متری در عرض‌های ۱۲۰ تا ۱۸۰ سانتی‌متر"],
        "illo": "farangi",
    },
    "توری-جوشی--گالوانیزه-رول": {
        "name": "توری جوشی ریزبافت گالوانیزه رول",
        "en": "Welded Wire Mesh Roll",
        "intro": "توری جوشی ریزبافت از جوش نقطه‌ای مفتول‌های گالوانیزه در چشمه‌های ریز ۱ تا ۲/۵ سانتی‌متر ساخته و به صورت رول عرضه می‌شود. اتصال جوشی، چشمه‌ها را ثابت نگه می‌دارد و سطحی یکنواخت و مقاوم پدید می‌آورد.",
        "apps": ["حفاظ پنجره و کانال", "قفس‌سازی", "فیلتر و توری صنعتی", "تقویت اندودکاری", "محافظ تنه درخت"],
        "illo": "weldroll",
    },
    "مش-جوشی-یا-مش-آهنی": {
        "name": "مش جوشی (مش آهنی)",
        "en": "Welded Rebar Mesh",
        "intro": "مش جوشی از جوش نقطه‌ای میلگردهای ساده یا آجدار در چشمه‌های منظم ساخته می‌شود و به صورت برگ‌های ۶×۲ متر عرضه می‌گردد. جایگزین سریع و دقیق آرماتوربندی سنتی در بتن‌ریزی است و سرعت اجرا را به‌شکل چشمگیری بالا می‌برد.",
        "apps": ["تقویت بتن و کف‌سازی", "سقف و دیوار بتنی", "فونداسیون", "کانال‌سازی و ابنیه", "فنس‌های سنگین"],
        "notes": ["برگ استاندارد ۶×۲ متر عرضه می‌شود", "با میلگرد ساده یا آجدار در سایزهای مختلف تولید می‌شود", "اتصال جوش نقطه‌ای، چشمه‌ها را ثابت و اجرا را سریع می‌کند"],
        "illo": "rebarmesh",
    },
    "سیم-خاردار": {
        "name": "سیم خاردار",
        "en": "Barbed Wire",
        "intro": "سیم خاردار از تابیدن مفتول گالوانیزه گرم با خارهای برنده در دو نوع خطی و سوزنی (حلقوی) تولید می‌شود. پوشش گالوانیزه، دوام آن را در فضای باز تضمین می‌کند و کلاف‌های ۲۰ کیلوگرمی آن حمل و نصب آسانی دارند.",
        "apps": ["حفاظت پیرامونی تأسیسات", "مرزبندی اراضی و مزارع", "امنیت ساختمان و انبار", "پادگان‌ها و اماکن حفاظتی"],
        "notes": ["در دو نوع خطی و سوزنی (حلقوی) تولید می‌شود", "کلاف‌های حدود ۲۰ کیلوگرمی، حمل و نصب را آسان می‌کند", "گالوانیزهٔ گرم با ضخامت خار ۲ میلی‌متر", "برای نصب روی دیوار، فنس و حفاظ پیرامونی مناسب است"],
        "illo": "barbed",
    },
    "مفتول-گالوانیزه": {
        "name": "مفتول گالوانیزه",
        "en": "Galvanized Wire",
        "intro": "مفتول گالوانیزه از پوشش‌دهی مفتول فولادی با لایهٔ روی به دست می‌آید و در برابر زنگ‌زدگی و رطوبت مقاومت بالایی دارد؛ مادهٔ اولیهٔ اصلی تولید انواع توری، فنس و سیم خاردار است. این محصول در کارخانهٔ طلوع سپاهان تولید و به صورت کلاف عرضه می‌شود و قابلیت تولید کلاف‌های ۵۰ تا ۲۵۰ کیلوگرمی را داریم.",
        "apps": ["تولید انواع توری و فنس", "تولید سیم خاردار", "مهار و بسته‌بندی بار", "مصارف کشاورزی و باغبانی", "مصارف عمومی صنعتی"],
        "notes": ["پوشش گالوانیزه، عمر مفتول را در فضای باز چند برابر می‌کند", "قابلیت تولید کلاف‌های ۵۰ تا ۲۵۰ کیلوگرمی را داریم", "ضخامت مفتول مطابق سفارش مشتری تولید می‌شود"],
        "illo": "coil",
        "specs_panel": [
            ("نوع پوشش", "گالوانیزه"),
            ("وزن هر کلاف", "۵۰ تا ۲۵۰ کیلوگرم — طبق سفارش"),
            ("ضخامت مفتول", "طبق سفارش مشتری"),
            ("نحوه عرضه", "کلاف"),
            ("محل بارگیری", "کارخانه اصفهان"),
            ("قیمت", "استعلام تلفنی"),
        ],
    },
    "سیم-سیاه-و-آرماتور-بندی": {
        "name": "مفتول آرماتوربندی (سیم سیاه)",
        "en": "Black Annealed Tie Wire",
        "intro": "مفتول سیاه آرماتوربندی از بازپخت مفتول فولادی به دست می‌آید؛ نرم و انعطاف‌پذیر است و برای بستن میلگرد در آرماتوربندی به کار می‌رود. در ضخامت‌های ۱/۵ تا ۴ میلی‌متر و به صورت کلاف یا فله عرضه می‌شود.",
        "apps": ["بستن آرماتور و میلگرد", "ساختمان‌سازی", "قالب‌بندی بتن", "مصارف عمومی صنعتی"],
        "notes": ["مفتول بازپخت‌شده، نرم و انعطاف‌پذیر برای گره‌زدن آسان", "ضخامت‌های ۱/۵ تا ۴ میلی‌متر موجود است", "به صورت کلاف یا فله عرضه می‌شود"],
        "illo": "coil",
    },
}

# ----------------------------------------------------------------------------
# SVG illustrations — one visual language: steel plate bg, navy structure, red accent.


def _card(inner, vb="0 0 300 300"):
    return f'''<svg viewBox="{vb}" xmlns="http://www.w3.org/2000/svg">
<defs>
 <linearGradient id="plate" x1="0" y1="0" x2="1" y2="1">
  <stop offset="0" stop-color="#EDEFF5"/><stop offset="1" stop-color="#DDE1EB"/>
 </linearGradient>
 <clipPath id="cardclip"><rect x="6" y="6" width="288" height="288" rx="18"/></clipPath>
</defs>
<rect x="6" y="6" width="288" height="288" rx="18" fill="url(#plate)" stroke="{STEEL}" stroke-width="1.5"/>
<g clip-path="url(#cardclip)">{inner}</g>
<rect x="6" y="6" width="288" height="288" rx="18" fill="none" stroke="{NAVY}" stroke-opacity=".25" stroke-width="1.5"/>
</svg>'''


def illo_chainlink():
    lines = []
    for i in range(-8, 16):
        x = i * 34
        lines.append(f'<path d="M {x},-20 L {x+340},300" stroke="{NAVY}" stroke-width="5.5" fill="none" stroke-linecap="round"/>')
        lines.append(f'<path d="M {x+340},-20 L {x},300" stroke="{NAVY}" stroke-width="5.5" fill="none" stroke-linecap="round" opacity=".82"/>')
    # posts + red rail
    extra = f'<rect x="24" y="0" width="13" height="300" fill="{RED_D}"/><rect x="263" y="0" width="13" height="300" fill="{RED_D}"/>'
    return _card(f'<g opacity=".95">{"".join(lines)}</g>{extra}')


def illo_crimped():
    parts = []
    # woven look: horizontal waves + vertical waves
    for r, y in enumerate(range(28, 300, 44)):
        d = f'M -10,{y} ' + " ".join(
            f'Q {x+11},{y + (14 if (i + r) % 2 == 0 else -14)} {x+22},{y}' for i, x in enumerate(range(-10, 310, 22)))
        parts.append(f'<path d="{d}" stroke="{NAVY}" stroke-width="9" fill="none" stroke-linecap="round"/>')
    for c, x in enumerate(range(28, 300, 44)):
        d = f'M {x},-10 ' + " ".join(
            f'Q {x + (14 if (i + c) % 2 == 0 else -14)},{y+11} {x},{y+22}' for i, y in enumerate(range(-10, 310, 22)))
        parts.append(f'<path d="{d}" stroke="{STEEL}" stroke-width="9" fill="none" stroke-linecap="round" opacity=".9"/>')
    parts.append(f'<path d="M -10,248 Q 1,262 12,248 Q 23,234 34,248 Q 45,262 56,248 Q 67,234 78,248 Q 89,262 100,248 Q 111,234 122,248 Q 133,262 144,248 Q 155,234 166,248 Q 177,262 188,248 Q 199,234 210,248 Q 221,262 232,248 Q 243,234 254,248 Q 265,262 276,248 Q 287,234 298,248 Q 309,262 320,248" stroke="{RED}" stroke-width="9" fill="none" stroke-linecap="round"/>')
    return _card("".join(parts))


def illo_gabion():
    g = []
    # isometric box: front face grid + top face
    g.append(f'<polygon points="40,110 210,110 210,270 40,270" fill="#E7EAF2" stroke="{NAVY}" stroke-width="4"/>')
    g.append(f'<polygon points="40,110 100,60 270,60 210,110" fill="#DCE0EC" stroke="{NAVY}" stroke-width="4"/>')
    g.append(f'<polygon points="210,110 270,60 270,220 210,270" fill="#CDD3E3" stroke="{NAVY}" stroke-width="4"/>')
    # stones in front face
    stones = [(70,160,26,20,-8),(120,150,30,24,10),(172,162,26,21,-14),(92,205,30,22,6),(150,212,32,24,-6),(188,224,22,18,12),(60,238,24,18,-4),(118,248,28,20,8),(174,196,20,16,0)]
    for (cx, cy, rx, ry, rot) in stones:
        g.append(f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="#B9BFCF" stroke="#8C92AD" stroke-width="2.5" transform="rotate({rot} {cx} {cy})"/>')
    # mesh over front face
    for x in range(40, 211, 34):
        g.append(f'<line x1="{x}" y1="110" x2="{x}" y2="270" stroke="{NAVY}" stroke-width="3"/>')
    for y in range(110, 271, 32):
        g.append(f'<line x1="40" y1="{y}" x2="210" y2="{y}" stroke="{NAVY}" stroke-width="3"/>')
    # top face mesh
    for i in range(6):
        x0 = 40 + i * 34; g.append(f'<line x1="{x0}" y1="110" x2="{x0+60}" y2="60" stroke="{NAVY}" stroke-width="2.5"/>')
    for i in range(3):
        y = 94 - i * 17; x_off = (110 - y) * 60 / 50
        g.append(f'<line x1="{40 + x_off}" y1="{y}" x2="{210 + x_off}" y2="{y}" stroke="{NAVY}" stroke-width="2.5"/>')
    # red frame edge
    g.append(f'<polyline points="40,110 210,110 210,270" fill="none" stroke="{RED}" stroke-width="5" stroke-linecap="round"/>')
    return _card("".join(g))


def _hexpath(cx, cy, r):
    import math
    pts = []
    for a in range(6):
        ang = math.radians(60 * a - 30)
        pts.append(f"{cx + r * math.cos(ang):.1f},{cy + r * math.sin(ang):.1f}")
    return "M " + " L ".join(pts) + " Z"


def _hexgrid(r, stroke_w, color, opacity=1.0):
    import math
    parts = []
    dx = r * math.sqrt(3)
    dy = r * 1.5
    row = 0
    y = -10
    while y < 320:
        x0 = -10 + (dx / 2 if row % 2 else 0)
        x = x0
        while x < 320:
            parts.append(f'<path d="{_hexpath(x, y, r)}" fill="none" stroke="{color}" stroke-width="{stroke_w}" opacity="{opacity}" stroke-linejoin="round"/>')
            x += dx
        y += dy
        row += 1
    return "".join(parts)


def illo_chicken():
    inner = _hexgrid(24, 4, NAVY, .92)
    inner += f'<path d="{_hexpath(150, 150, 24)}" fill="none" stroke="{RED}" stroke-width="6" stroke-linejoin="round"/>'
    return _card(inner)


def illo_farangi():
    inner = _hexgrid(38, 6, NAVY, .95)
    inner += f'<rect x="6" y="6" width="288" height="14" fill="{RED_D}"/><rect x="6" y="280" width="288" height="14" fill="{RED_D}"/>'
    return _card(inner)


def illo_weldroll():
    g = []
    # flat fine grid at right (unrolled)
    for x in range(120, 310, 18):
        g.append(f'<line x1="{x}" y1="30" x2="{x}" y2="270" stroke="{NAVY}" stroke-width="2.6"/>')
    for y in range(30, 280, 18):
        g.append(f'<line x1="120" y1="{y}" x2="300" y2="{y}" stroke="{NAVY}" stroke-width="2.6"/>')
    # the roll
    g.append(f'<rect x="30" y="28" width="80" height="244" rx="40" fill="#D6DAE6" stroke="{NAVY}" stroke-width="4"/>')
    for i, rx in enumerate([28, 20, 12]):
        g.append(f'<ellipse cx="70" cy="150" rx="{rx}" ry="{rx+12}" fill="none" stroke="{NAVY}" stroke-width="3" opacity="{1 - i*.2}"/>')
    g.append(f'<ellipse cx="70" cy="150" rx="5" ry="8" fill="{RED}"/>')
    # grid hint on the roll surface
    for y in range(40, 268, 16):
        g.append(f'<line x1="34" y1="{y}" x2="106" y2="{y}" stroke="{STEEL}" stroke-width="1.6" opacity=".8"/>')
    return _card("".join(g))


def illo_expanded():
    g = []
    w, h = 62, 30
    row = 0
    y = 12
    while y < 310:
        off = (w / 2) if row % 2 else 0
        x = -20 + off
        while x < 320:
            g.append(f'<path d="M {x},{y+h/2} L {x+w/2},{y} L {x+w},{y+h/2} L {x+w/2},{y+h} Z" fill="#CFD4E2" stroke="{NAVY}" stroke-width="4" stroke-linejoin="round"/>')
            x += w
        y += h - 4
        row += 1
    g.append(f'<path d="M 119,{12+7*26+15} L 150,{12+7*26} L 181,{12+7*26+15} L 150,{12+7*26+30} Z" fill="none" stroke="{RED}" stroke-width="5" stroke-linejoin="round" />')
    return _card("".join(g))


def illo_rebarmesh():
    g = []
    for x in range(36, 300, 56):
        g.append(f'<line x1="{x}" y1="0" x2="{x}" y2="300" stroke="{NAVY}" stroke-width="10" stroke-linecap="round"/>')
    for y in range(36, 300, 56):
        g.append(f'<line x1="0" y1="{y}" x2="300" y2="{y}" stroke="{NAVY}" stroke-width="10" stroke-linecap="round" opacity=".88"/>')
    for x in range(36, 300, 56):
        for y in range(36, 300, 56):
            g.append(f'<circle cx="{x}" cy="{y}" r="7" fill="{RED}"/>')
    return _card("".join(g))


def illo_barbed():
    g = []
    for wy, op in [(80, .9), (150, 1.0), (220, .9)]:
        d = f'M -10,{wy} ' + " ".join(f'Q {x+15},{wy + (10 if i % 2 == 0 else -10)} {x+30},{wy}' for i, x in enumerate(range(-10, 310, 30)))
        color = RED_D if wy == 150 else NAVY
        g.append(f'<path d="{d}" stroke="{color}" stroke-width="7" fill="none" opacity="{op}" stroke-linecap="round"/>')
        d2 = f'M -10,{wy+6} ' + " ".join(f'Q {x+15},{wy+6 + (-10 if i % 2 == 0 else 10)} {x+30},{wy+6}' for i, x in enumerate(range(-10, 310, 30)))
        g.append(f'<path d="{d2}" stroke="{color}" stroke-width="7" fill="none" opacity="{op*.85}" stroke-linecap="round"/>')
        for bx in range(38, 300, 62):
            for (a, b, c, dd) in [(-16, -18, 16, 18), (16, -18, -16, 18)]:
                g.append(f'<line x1="{bx+a}" y1="{wy+3+b}" x2="{bx+c}" y2="{wy+3+dd}" stroke="{color}" stroke-width="5" stroke-linecap="round" opacity="{op}"/>')
    return _card("".join(g))


def illo_coil():
    g = []
    for i in range(14):
        cx = 66 + i * 13
        op = 1 - abs(i - 7) * 0.06
        color = RED if i in (6, 7) else NAVY
        g.append(f'<ellipse cx="{cx}" cy="150" rx="34" ry="100" fill="none" stroke="{color}" stroke-width="6.5" opacity="{op:.2f}"/>')
    g.append(f'<path d="M 248,60 Q 285,80 280,150" fill="none" stroke="{NAVY}" stroke-width="6.5" stroke-linecap="round"/>')
    return _card("".join(g))


ILLOS = {
    "chainlink": illo_chainlink, "crimped": illo_crimped, "gabion": illo_gabion,
    "chicken": illo_chicken, "farangi": illo_farangi, "weldroll": illo_weldroll,
    "expanded": illo_expanded, "rebarmesh": illo_rebarmesh, "barbed": illo_barbed,
    "coil": illo_coil,
}

# tiny wire-mesh background pattern for the cover
COVER_MESH = f'''<svg class="cover-mesh" viewBox="0 0 800 1131" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
<defs><pattern id="cm" width="72" height="72" patternUnits="userSpaceOnUse">
<path d="M 0,0 L 72,72 M 72,0 L 0,72" stroke="#FFFFFF" stroke-width="2" fill="none"/>
</pattern></defs>
<rect width="800" height="1131" fill="url(#cm)" opacity="0.05"/>
</svg>'''


def fa_digits(s):
    return s.translate(str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹"))


def tidy_decimals(s):
    """3/0 -> 3, 2/20 -> 2/2, 1/200 -> 1/2 — strip trailing zeros of slash-decimals."""
    def _fix(m):
        a, b = m.group(1), m.group(2).rstrip("0")
        return f"{a}/{b}" if b else a
    return re.sub(r"(?<![\d/])(\d+)/(\d+)(?![\d/])", _fix, s)


def clean_cell(s):
    s = s.strip()
    if not s:
        return "—"
    return fa_digits(tidy_decimals(s))


def clean_name(s):
    """Fix site typos in product names: missing spaces around digits, 'امتر' for '۱ متر'."""
    s = s.strip()
    s = s.replace("سانتیمترعرض", "سانتیمتر عرض").replace("امتر ", "۱ متر ")
    s = re.sub(r"(\d)([آ-ی])", r"\1 \2", s)
    s = re.sub(r"([آ-ی])(\d)", r"\1 \2", s)
    s = re.sub(r"\s+", " ", s)
    return fa_digits(tidy_decimals(s))


# ----------------------------------------------------------------------------
data = json.load(open(DATA, encoding="utf-8"))

pages = []
page_no = [0]


def add_page(cls, body, numbered=True, header=True):
    page_no[0] += 1
    n = page_no[0]
    hdr = ""
    ftr = ""
    if header:
        hdr = f'''<div class="pg-head">
  <div class="pg-head-brand"><img src="assets/sepahan-felez-864x1616.png" alt=""/><span>سپاهان فلز <b>|</b> کاتالوگ محصولات</span></div>
  <div class="pg-head-rule"></div>
</div>'''
    if numbered:
        ftr = f'''<div class="pg-foot">
  <span class="pg-foot-site">sepahanfelez.ir</span>
  <span class="pg-foot-tel">۰۲۱-۹۱۳۲۶۰۳۰</span>
  <span class="pg-num">{fa_digits(str(n))}</span>
</div>'''
    pages.append(f'<section class="page {cls}">{hdr}{body}{ftr}</section>')
    return n


# ============================== COVER =======================================
cover = f'''
{COVER_MESH}
<div class="cover-inner">
  <div class="cover-topline"></div>
  <div class="cover-logo"><img src="assets/sepahan-felez-1728x3232.png" alt="سپاهان فلز"/></div>
  <h1 class="cover-title">کاتالوگ محصولات</h1>
  <div class="cover-sub">توری، مش، سیم خاردار و مفتول‌های صنعتی و ساختمانی</div>
  <div class="cover-band"><span>صنایع مفتولی طلوع سپاهان</span></div>
  <div class="cover-meta">
    <span>sepahanfelez.ir</span><i></i><span>۰۲۱-۹۱۳۲۶۰۳۰</span><i></i><span>۱۴۰۵</span>
  </div>
</div>'''
add_page("cover", cover, numbered=False, header=False)

# ============================== ABOUT / FACTORY =============================
about = f'''
<h2 class="sec-title"><span>معرفی مجموعه</span></h2>
<p class="lede">صنایع مفتولی <b>طلوع سپاهان</b> به شمارهٔ ثبت ۹۶، از سال ۱۳۸۲ به عنوان تولیدکنندهٔ انواع مفتول‌های صنعتی و ساختمانی، مفتول گالوانیزه، انواع توری و سیم خاردار با مجوز رسمی وزارت صنایع و معادن در استان اصفهان فعالیت می‌کند. این مجموعه در راستای خدمت‌رسانی سریع‌تر به مشتریان، واحد فروش اینترنتی <b>سپاهان فلز</b> را راه‌اندازی کرده و محصولات را از انبارهای اصفهان و تهران عرضه می‌کند.</p>

<div class="stats">
  <div class="stat"><b>۱۳۸۲</b><span>سال تأسیس</span></div>
  <div class="stat"><b>۱۰٬۰۰۰ تن</b><span>ظرفیت تولید سالانه</span></div>
  <div class="stat"><b>۹۶</b><span>شمارهٔ ثبت</span></div>
  <div class="stat"><b>۱۰ گروه</b><span>محصول تولیدی</span></div>
</div>

<h3 class="sub-title">چرا سپاهان فلز؟</h3>
<div class="feats">
  <div class="feat"><i>{'✓'}</i>تأمین مستقیم از کارخانه، بدون واسطه</div>
  <div class="feat"><i>{'✓'}</i>فروش عمده و جزئی با مشاورهٔ تخصصی</div>
  <div class="feat"><i>{'✓'}</i>ارسال به سراسر کشور از انبار اصفهان و تهران</div>
  <div class="feat"><i>{'✓'}</i>تولید سفارشی در ابعاد و مشخصات درخواستی</div>
  <div class="feat"><i>{'✓'}</i>مجوز رسمی وزارت صنایع و معادن</div>
  <div class="feat"><i>{'✓'}</i>محصولات با نشان کارخانهٔ طلوع سپاهان</div>
</div>

<h3 class="sub-title">دفتر و کارخانه‌ها</h3>
<div class="addr-grid">
  <div class="addr"><b>دفتر تهران</b><span>بازار آهن شادآباد، بلوار شهید قربانخوانی، مجتمع پارس فلز، پلاک ۹ و ۱۰</span></div>
  <div class="addr"><b>کارخانه شماره ۱</b><span>اصفهان، شهرک صنعتی منتظریه، خیابان قادری شمالی، پلاک ۱۸۱</span></div>
  <div class="addr addr-wide"><b>کارخانه شماره ۲</b><span>اصفهان، شهرک صنعتی منتظریه، فرعی ۱۰۳، کارخانه صنایع مفتولی طلوع سپاهان</span></div>
</div>'''
add_page("about", about)

# ============================== TOC =========================================
toc_cards = []
cat_page_start = page_no[0] + 2  # toc itself is next page; categories start after
pno = cat_page_start
toc_page_map = {}
for slug, c in COPY.items():
    toc_page_map[slug] = pno
    pno += 1
for i, (slug, c) in enumerate(COPY.items(), 1):
    svg = ILLOS[c["illo"]]()
    toc_cards.append(f'''<div class="toc-card">
  <div class="toc-illo">{svg}</div>
  <div class="toc-txt"><b>{c["name"]}</b><span>صفحهٔ {fa_digits(str(toc_page_map[slug]))}</span></div>
</div>''')
toc = f'''
<h2 class="sec-title"><span>فهرست محصولات</span></h2>
<div class="toc-grid">{"".join(toc_cards)}</div>
<div class="toc-note">قیمت کلیهٔ محصولات به صورت روزانه تعیین می‌شود؛ برای دریافت قیمت روز و ثبت سفارش با شمارهٔ <b><span dir="ltr">۰۲۱-۹۱۳۲۶۰۳۰</span></b> تماس بگیرید یا به <b><span dir="ltr">sepahanfelez.ir</span></b> مراجعه کنید.</div>'''
add_page("toc", toc)

# ============================== CATEGORY PAGES ==============================
for i, (slug, c) in enumerate(COPY.items(), 1):
    d = data.get(slug, {})
    cols = d.get("columns", [])
    rows = d.get("rows", [])

    # pick spec columns (drop index + price/chart/buy)
    keep_idx = [j for j, col in enumerate(cols) if col not in PRICE_COLS]
    kept_cols = [cols[j] for j in keep_idx]

    table_html = ""
    if rows:
        head = "".join(f"<th>{col}</th>" for col in ["ردیف"] + kept_cols)
        body_rows = []
        name_j = cols.index("نام محصول") if "نام محصول" in cols else 1
        for rn, r in enumerate(rows, 1):
            cells = "".join(
                f"<td>{(clean_name(r[j]) if j == name_j else clean_cell(r[j])) if j < len(r) else '—'}</td>"
                for j in keep_idx)
            body_rows.append(f"<tr><td>{fa_digits(str(rn))}</td>{cells}</tr>")
        tn = c.get("table_note")
        tn_html = f'<div class="table-note">{tn}</div>' if tn else ""
        table_html = f'''<h3 class="sub-title tight">مشخصات فنی محصولات</h3>
<table class="spec"><thead><tr>{head}</tr></thead><tbody>{"".join(body_rows)}</tbody></table>{tn_html}'''
    elif "specs_panel" in c:
        items = "".join(f'<div class="kv"><b>{k}</b><span>{v}</span></div>' for k, v in c["specs_panel"])
        table_html = f'''<h3 class="sub-title tight">مشخصات فنی</h3>
<div class="kv-grid">{items}</div>
<div class="order-note">این محصول بر اساس مشخصات سفارش مشتری تولید می‌شود — برای اعلام مشخصات و دریافت قیمت تماس بگیرید.</div>'''

    hero_files = hero_by_cat.get(slug, [])
    strip_html = ""
    if hero_files:
        cells = "".join(f'<div class="ph"><img src="assets/photos/{f}" alt=""/></div>' for f in hero_files[:3])
        sm = " strip-sm" if len(rows) >= 13 else ""
        if len(hero_files) == 1:
            sm += " strip-one"
        strip_html = f'<div class="photo-strip{sm}">{cells}</div>'

    notes_html = ""
    if c.get("notes"):
        lis = "".join(f'<div class="note-item"><i></i><span>{n}</span></div>' for n in c["notes"])
        notes_html = f'<h3 class="sub-title tight">نکات فنی و خرید</h3><div class="notes-grid">{lis}</div>'
    if len(rows) <= 8 and not hero_files:
        banner_svg = ILLOS[c["illo"]]().replace("<svg ", '<svg preserveAspectRatio="xMidYMid slice" ', 1)
        notes_html += f'<div class="cat-banner">{banner_svg}</div>'
    apps = "".join(f"<span>{a}</span>" for a in c["apps"])
    svg = ILLOS[c["illo"]]()
    body = f'''
<div class="cat-head">
  <div class="cat-title-wrap">
    <div class="cat-no">{fa_digits(f"{i:02d}")}</div>
    <div><h2 class="cat-title">{c["name"]}</h2><div class="cat-en">{c["en"]}</div></div>
  </div>
  <div class="cat-illo">{svg}</div>
</div>
{strip_html}
<p class="cat-intro">{c["intro"]}</p>
<div class="apps"><span class="apps-label">کاربردها</span>{apps}</div>
{table_html}
{notes_html}
<div class="callout"><span class="callout-label">استعلام قیمت روز و ثبت سفارش</span><span class="callout-tel" dir="ltr">۰۲۱-۹۱۳۲۶۰۳۰</span></div>'''
    add_page("cat", body)

# ============================== PHOTO GALLERY ===============================
if photos_by_cat:
    used = {f for files in hero_by_cat.values() for f in files[:3]}
    gallery_items = []  # (category display name, file)
    for slug, c in COPY.items():
        for f in photos_by_cat.get(slug, []):
            if f not in used:
                gallery_items.append((c["name"], f))
    PER = 12
    total_pages = (len(gallery_items) + PER - 1) // PER
    for p in range(total_pages):
        chunk = gallery_items[p * PER:(p + 1) * PER]
        cells = "".join(
            f'<div class="g-cell"><div class="g-ph"><img src="assets/photos/{f}" alt=""/></div>'
            f'<div class="g-cap">{name}</div></div>'
            for name, f in chunk)
        head_html = ""
        if p == 0:
            head_html = '<h2 class="sec-title"><span>آلبوم تصاویر محصولات</span></h2>'
        else:
            head_html = f'<div class="g-cont">آلبوم تصاویر محصولات — ادامه ({fa_digits(str(p + 1))} از {fa_digits(str(total_pages))})</div>'
        body = f'{head_html}<div class="g-grid">{cells}</div>'
        add_page("gallery", body)

# ============================== BACK COVER ==================================
back = f'''
{COVER_MESH}
<div class="back-inner">
  <div class="back-logo"><img src="assets/sepahan-felez-1728x3232.png" alt=""/></div>
  <h2 class="back-title">با ما در تماس باشید</h2>
  <div class="back-tel">۰۲۱-۹۱۳۲۶۰۳۰</div>
  <div class="back-rows">
    <div class="back-row"><b>موبایل / واتس‌اپ</b><span dir="ltr">0900 106 6030</span></div>
    <div class="back-row"><b>ایمیل</b><span dir="ltr">info@sepahanfelez.ir</span></div>
    <div class="back-row"><b>وب‌سایت</b><span dir="ltr">sepahanfelez.ir</span></div>
    <div class="back-row"><b>ساعت کاری</b><span>شنبه تا چهارشنبه ۸ تا ۱۷ — پنجشنبه ۸ تا ۱۲:۳۰</span></div>
    <div class="back-row"><b>دفتر تهران</b><span>بازار آهن شادآباد، بلوار شهید قربانخوانی، مجتمع پارس فلز، پلاک ۹ و ۱۰</span></div>
    <div class="back-row"><b>کارخانه شماره ۱</b><span>اصفهان، شهرک صنعتی منتظریه، خیابان قادری شمالی، پلاک ۱۸۱</span></div>
    <div class="back-row"><b>کارخانه شماره ۲</b><span>اصفهان، شهرک صنعتی منتظریه، فرعی ۱۰۳، کارخانه صنایع مفتولی طلوع سپاهان</span></div>
  </div>
  <div class="back-qr"><img src="assets/qr.svg" alt="QR"/><span>اسکن کنید و قیمت روز را ببینید</span></div>
  <div class="back-foot">صنایع مفتولی طلوع سپاهان — کلیهٔ حقوق متعلق به شرکت رادمان جاده ابریشم است.</div>
</div>'''
add_page("back", back, numbered=False, header=False)

# ============================== CSS =========================================
css = f'''
@font-face {{ font-family: IRANSans; src: url("assets/IRANSansWeb_FaNum.woff2") format("woff2"); font-weight: 400; }}
@font-face {{ font-family: IRANSans; src: url("assets/IRANSansWeb_FaNum_Bold.woff2") format("woff2"); font-weight: 700; }}
@font-face {{ font-family: IRANSans; src: url("assets/IRANSansWeb_FaNum_Medium.woff2") format("woff2"); font-weight: 500; }}
@font-face {{ font-family: IRANSans; src: url("assets/IRANSansWeb_FaNum_Light.woff2") format("woff2"); font-weight: 300; }}

* {{ margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }}
html, body {{ font-family: IRANSans, Tahoma, sans-serif; direction: rtl; color: {INK}; background: #fff; }}

@page {{ size: A4; margin: 0; }}
.page {{ width: 210mm; height: 297mm; overflow: hidden; position: relative;
  page-break-after: always; padding: 13mm 13mm 16mm; background: #fff; }}

/* ---------- page chrome ---------- */
.pg-head {{ display: flex; flex-direction: column; gap: 2.2mm; margin-bottom: 6mm; }}
.pg-head-brand {{ display: flex; align-items: center; gap: 2.6mm; font-size: 8.6pt; color: {NAVY}; font-weight: 500; }}
.pg-head-brand img {{ height: 8.4mm; }}
.pg-head-brand b {{ color: {RED}; font-weight: 400; }}
.pg-head-rule {{ height: 0.8mm; background: linear-gradient(to left, {RED} 0 26mm, {NAVY} 26mm 100%); border-radius: 1mm; }}
.pg-foot {{ position: absolute; left: 13mm; right: 13mm; bottom: 7mm; display: flex; align-items: center;
  gap: 4mm; font-size: 8pt; color: #6b7186; border-top: 0.35mm solid #E2E5EE; padding-top: 2.6mm; }}
.pg-foot-site {{ direction: ltr; }}
.pg-num {{ margin-right: auto; background: {NAVY}; color: #fff; min-width: 8mm; text-align: center;
  padding: 0.8mm 2.4mm; border-radius: 2mm; font-size: 8.6pt; }}

/* ---------- cover ---------- */
.cover {{ background: linear-gradient(160deg, {NAVY} 0%, #1B2347 58%, #151B38 100%); color: #fff; padding: 0; }}
.cover-mesh {{ position: absolute; inset: 0; width: 100%; height: 100%; }}
.cover-inner {{ position: relative; height: 100%; display: flex; flex-direction: column; align-items: center;
  justify-content: center; text-align: center; gap: 6mm; padding: 20mm; }}
.cover-topline {{ position: absolute; top: 0; left: 0; right: 0; height: 4.5mm; background: {RED}; }}
.cover-logo img {{ height: 64mm; filter: drop-shadow(0 3mm 6mm rgba(0,0,0,.35)); }}
.cover-title {{ font-size: 34pt; font-weight: 700; letter-spacing: -0.2mm; }}
.cover-sub {{ font-size: 12.5pt; font-weight: 300; color: #C9CFE4; }}
.cover-band {{ margin-top: 4mm; background: {RED}; padding: 2.8mm 12mm; border-radius: 2mm; font-size: 12pt; font-weight: 500; }}
.cover-meta {{ position: absolute; bottom: 14mm; left: 0; right: 0; display: flex; justify-content: center;
  align-items: center; gap: 5mm; font-size: 10.5pt; color: #C9CFE4; }}
.cover-meta i {{ width: 1.6mm; height: 1.6mm; background: {RED}; border-radius: 50%; }}

/* ---------- shared ---------- */
.sec-title {{ display: flex; align-items: center; gap: 4mm; font-size: 17pt; color: {NAVY}; margin-bottom: 6mm; }}
.sec-title::after {{ content: ""; flex: 1; height: 1mm; background: {PAPER_TINT}; border-radius: 1mm; }}
.sec-title span {{ position: relative; padding-right: 4.5mm; }}
.sec-title span::before {{ content: ""; position: absolute; right: 0; top: 12%; bottom: 12%; width: 1.8mm; background: {RED}; border-radius: 1mm; }}
.sub-title {{ font-size: 11.5pt; color: {NAVY}; margin: 6mm 0 3.2mm; }}
.sub-title.tight {{ margin-top: 4.5mm; }}
.lede {{ font-size: 10.3pt; line-height: 1.95; text-align: justify; color: #333A52; }}

/* ---------- about ---------- */
.stats {{ display: grid; grid-template-columns: repeat(4, 1fr); gap: 4mm; margin-top: 6mm; }}
.stat {{ background: {NAVY}; color: #fff; border-radius: 2.6mm; padding: 4.6mm 3mm 4mm; text-align: center; }}
.stat b {{ display: block; font-size: 15.5pt; margin-bottom: 1.4mm; }}
.stat span {{ font-size: 8.6pt; color: #C9CFE4; font-weight: 300; }}
.stat:nth-child(2) {{ background: {RED}; }}
.stat:nth-child(2) span {{ color: #F2C9CF; }}
.feats {{ display: grid; grid-template-columns: 1fr 1fr; gap: 2.6mm 5mm; }}
.feat {{ display: flex; align-items: center; gap: 2.6mm; font-size: 9.6pt; background: {PAPER_TINT};
  border-radius: 2.2mm; padding: 3mm 3.4mm; }}
.feat i {{ font-style: normal; color: #fff; background: {RED}; width: 5mm; height: 5mm; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center; font-size: 8pt; flex-shrink: 0; }}
.addr-grid {{ display: grid; grid-template-columns: 1fr 1fr; gap: 3.4mm; }}
.addr {{ border: 0.45mm solid #E2E5EE; border-right: 1.6mm solid {NAVY}; border-radius: 2.2mm; padding: 3.4mm 4mm; }}
.addr b {{ display: block; color: {NAVY}; font-size: 10pt; margin-bottom: 1.4mm; }}
.addr span {{ font-size: 9pt; color: #4A5169; line-height: 1.75; }}
.addr.addr-wide {{ grid-column: 1 / -1; }}
.addr:first-child {{ border-right-color: {RED}; }}
.addr:first-child b {{ color: {RED_D}; }}

/* ---------- toc ---------- */
.toc-grid {{ display: grid; grid-template-columns: 1fr 1fr; gap: 4.2mm; }}
.toc-card {{ display: flex; align-items: center; gap: 4mm; border: 0.45mm solid #E2E5EE; border-radius: 2.6mm;
  padding: 3mm 3.6mm; }}
.toc-illo svg {{ width: 17mm; height: 17mm; display: block; }}
.toc-txt b {{ display: block; font-size: 10.6pt; color: {NAVY}; margin-bottom: 1.2mm; }}
.toc-txt span {{ font-size: 8.6pt; color: {RED_D}; }}
.toc-note {{ margin-top: 6mm; background: {PAPER_TINT}; border-radius: 2.6mm; padding: 4mm 5mm;
  font-size: 9.6pt; line-height: 1.9; color: #333A52; }}
.toc-note b {{ color: {RED_D}; }}

/* ---------- category pages ---------- */
.cat-head {{ display: flex; align-items: center; justify-content: space-between; gap: 6mm; }}
.cat-title-wrap {{ display: flex; align-items: center; gap: 4.4mm; }}
.cat-no {{ font-size: 21pt; font-weight: 700; color: #fff; background: {NAVY}; border-radius: 2.6mm;
  width: 15mm; height: 15mm; display: flex; align-items: center; justify-content: center; }}
.cat-title {{ font-size: 17.5pt; color: {NAVY}; }}
.cat-en {{ font-size: 9pt; color: {STEEL}; direction: ltr; text-align: right; margin-top: 1mm; }}
.cat-illo svg {{ width: 34mm; height: 34mm; display: block; }}
.cat-intro {{ font-size: 9.9pt; line-height: 1.95; text-align: justify; color: #333A52; margin-top: 3.4mm; }}

table.spec {{ width: 100%; border-collapse: collapse; font-size: 7.5pt; }}
table.spec th {{ background: {NAVY}; color: #fff; font-weight: 500; padding: 1.8mm 1mm; font-size: 7.1pt; white-space: nowrap; }}
table.spec th:first-child {{ border-radius: 0 2mm 2mm 0; }}
table.spec th:last-child {{ border-radius: 2mm 0 0 2mm; }}
table.spec td {{ padding: 1.5mm 1mm; text-align: center; border-bottom: 0.3mm solid #E8EAF2; color: #333A52; white-space: nowrap; }}
table.spec td:nth-child(2) {{ text-align: right; font-weight: 500; color: {INK}; white-space: normal; }}
table.spec tr:nth-child(even) td {{ background: #F7F8FB; }}

.table-note {{ margin-top: 2.4mm; font-size: 8.8pt; color: {NAVY}; background: {PAPER_TINT};
  border-radius: 2.2mm; padding: 2.6mm 4mm; border-right: 1.4mm solid {RED}; }}

.kv-grid {{ display: grid; grid-template-columns: 1fr 1fr; gap: 2.8mm; }}
.kv {{ display: flex; justify-content: space-between; align-items: center; gap: 3mm; border: 0.45mm solid #E2E5EE;
  border-radius: 2.2mm; padding: 3mm 4mm; font-size: 9.4pt; }}
.kv b {{ color: {NAVY}; }}
.kv span {{ color: #4A5169; }}
.order-note {{ margin-top: 3.4mm; font-size: 9.2pt; color: {RED_D}; background: #FBF1F2; border-radius: 2.2mm; padding: 3mm 4mm; }}

.apps {{ display: flex; flex-wrap: wrap; align-items: center; gap: 2mm; margin-top: 3.6mm; }}
.apps span {{ font-size: 8.4pt; color: {NAVY}; background: {PAPER_TINT}; border: 0.4mm solid #DDE1EE;
  padding: 1.4mm 3.2mm; border-radius: 5mm; }}
.apps .apps-label {{ background: {NAVY}; color: #fff; border-color: {NAVY}; font-weight: 500; }}

.cat-banner {{ margin-top: 5mm; height: 38mm; border-radius: 2.6mm; overflow: hidden; }}
.cat-banner svg {{ width: 100%; height: 100%; display: block; }}

.photo-strip {{ display: grid; grid-template-columns: 1.6fr 1fr 1fr; gap: 2.6mm; margin-top: 4mm; }}
.photo-strip .ph {{ height: 27mm; border-radius: 2.6mm; overflow: hidden; border: 0.45mm solid #DDE1EE;
  border-bottom: 1.4mm solid {NAVY}; background: {PAPER_TINT}; }}
.photo-strip .ph img {{ width: 100%; height: 100%; object-fit: cover; display: block; }}
.photo-strip .ph:first-child {{ border-bottom-color: {RED}; }}
.photo-strip.strip-sm .ph {{ height: 22mm; }}
.photo-strip.strip-one {{ grid-template-columns: 1fr; }}
.photo-strip.strip-one .ph {{ height: 37mm; border-bottom-color: {RED}; }}

.g-grid {{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 4mm; }}
.g-cell {{ border: 0.45mm solid #E2E5EE; border-radius: 2.6mm; overflow: hidden; }}
.g-ph {{ height: 44mm; background: {PAPER_TINT}; }}
.g-ph img {{ width: 100%; height: 100%; object-fit: cover; display: block; }}
.g-cap {{ font-size: 8.2pt; color: {NAVY}; font-weight: 500; text-align: center; padding: 1.8mm 2mm;
  background: #F7F8FB; border-top: 0.35mm solid #E2E5EE; }}
.g-cont {{ font-size: 10pt; color: {STEEL}; margin-bottom: 5mm; }}
.notes-grid {{ display: grid; grid-template-columns: 1fr 1fr; gap: 2.6mm; }}
.note-item {{ display: flex; align-items: flex-start; gap: 2.4mm; background: #F7F8FB; border: 0.4mm solid #E4E7F0;
  border-radius: 2.2mm; padding: 2.8mm 3.4mm; font-size: 8.8pt; line-height: 1.75; color: #333A52; }}
.note-item i {{ width: 2.2mm; height: 2.2mm; border-radius: 50%; background: {RED}; margin-top: 2.1mm; flex-shrink: 0; }}

.callout {{ position: absolute; left: 13mm; right: 13mm; bottom: 12mm; background: {RED}; color: #fff;
  border-radius: 2.6mm; display: flex; align-items: center; justify-content: space-between;
  padding: 3mm 6mm; }}
.callout-label {{ font-size: 10pt; font-weight: 500; }}
.callout-tel {{ font-size: 15pt; font-weight: 700; letter-spacing: 0.3mm; }}
.page.cat {{ padding-bottom: 32mm; }}
.page.cat .pg-foot {{ display: none; }}

/* ---------- back cover ---------- */
.back {{ background: linear-gradient(200deg, {NAVY} 0%, #1B2347 58%, #151B38 100%); color: #fff; }}
.back-inner {{ position: relative; height: 100%; display: flex; flex-direction: column; align-items: center;
  padding-top: 26mm; gap: 5mm; }}
.back-logo img {{ height: 40mm; }}
.back-title {{ font-size: 19pt; margin-top: 4mm; }}
.back-tel {{ font-size: 27pt; font-weight: 700; background: {RED}; padding: 3mm 14mm; border-radius: 3mm; }}
.back-rows {{ width: 132mm; margin-top: 5mm; display: flex; flex-direction: column; gap: 3mm; }}
.back-row {{ display: flex; justify-content: space-between; gap: 6mm; background: rgba(255,255,255,.06);
  border: 0.35mm solid rgba(255,255,255,.14); border-radius: 2.4mm; padding: 3.2mm 5mm; font-size: 10pt; }}
.back-row b {{ color: #C9CFE4; font-weight: 500; flex-shrink: 0; }}
.back-qr {{ margin-top: 5mm; display: flex; flex-direction: column; align-items: center; gap: 2.6mm; }}
.back-qr img {{ width: 26mm; height: 26mm; background: #fff; padding: 2.4mm; border-radius: 2.4mm; }}
.back-qr span {{ font-size: 9pt; color: #C9CFE4; }}
.back-foot {{ position: absolute; bottom: 10mm; left: 0; right: 0; text-align: center; font-size: 8.4pt; color: #8A92B5; }}
'''

html = f'''<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"/>
<title>کاتالوگ محصولات سپاهان فلز</title>
<style>{css}</style>
</head>
<body>
{"".join(pages)}
</body>
</html>'''

with open(OUT, "w", encoding="utf-8") as f:
    f.write(html)
print(f"wrote {OUT}: {page_no[0]} pages")
