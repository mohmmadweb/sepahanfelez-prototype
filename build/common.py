# -*- coding: utf-8 -*-
"""
قطعات مشترک همه‌ی صفحات: کمکی‌ها، نشانی‌ها، سر و پاصفحه.

هر چیزی که در بیش از یک صفحه تکرار می‌شود اینجاست تا یک تغییر
(مثلاً شماره‌ی تلفن یا ساعت بروزرسانی) یک‌جا اعمال شود.
"""
import json, os, re, html, unicodedata, datetime
import content as C
from icons import ICONS

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CAT  = json.load(open(os.path.join(ROOT, "build", "catalog.json"), encoding="utf-8"))

_photos_path = os.path.join(ROOT, "build", "photos.json")
PHOTOS = json.load(open(_photos_path, encoding="utf-8")) if os.path.exists(_photos_path) else {}

PH, PHS, WA, WAS = C.PHONE_RAW, C.PHONE_SHOW, C.WHATSAPP, C.WA_SHOW

# تعداد کل نوع کالای فعال — از کاتالوگ خوانده می‌شود.
TOTAL_SKUS = sum(len(CAT[k]["rows"]) for k in C.ORDER if k in CAT and CAT[k].get("rows"))
N_CATS = len(C.ORDER)

# ساعت بروزرسانی روزانه — به خواست کارفرما ۱۲:۳۰
UPDATE_TIME = "۱۲:۳۰"

# ---------------------------------------------------------------------------
# تاریخ شمسی — بدون وابستگی خارجی
# ---------------------------------------------------------------------------
JMONTHS = ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور",
           "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"]
JDAYS = {0: "دوشنبه", 1: "سه‌شنبه", 2: "چهارشنبه", 3: "پنجشنبه",
         4: "جمعه", 5: "شنبه", 6: "یکشنبه"}


def jalali(gy, gm, gd):
    """گرگوری → شمسی. الگوریتم متعارف (jdf)."""
    g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
    gy2 = gy + 1 if gm > 2 else gy
    days = (355666 + (365 * gy) + ((gy2 + 3) // 4) - ((gy2 + 99) // 100)
            + ((gy2 + 399) // 400) + gd + g_d_m[gm - 1])
    jy = -1595 + (33 * (days // 12053))
    days %= 12053
    jy += 4 * (days // 1461)
    days %= 1461
    if days > 365:
        jy += (days - 1) // 365
        days = (days - 1) % 365
    if days < 186:
        jm = 1 + days // 31
        jd = 1 + days % 31
    else:
        jm = 7 + (days - 186) // 30
        jd = 1 + (days - 186) % 30
    return jy, jm, jd


def jalali_str(d, with_day=False, bidi=False):
    """تاریخ شمسی بلند. با bidi=True روز و سال داخل <bdi> می‌روند.

    بدون آن، «۳۱ شهریور ۱۴۰۵» وقتی کنار متن یا آیکن فارسی بنشیند با
    الگوریتم bidi جابه‌جا می‌شود و روز و سال به هم می‌چسبند؛ کاربر
    «شهریور ۳۱۱۴۰۵» می‌بیند.
    """
    jy, jm, jd = jalali(d.year, d.month, d.day)
    if bidi:
        # چیدمان با flex تعیین می‌شود، نه با direction. هر بخش یک span
        # مستقل است، پس الگوریتم bidi نمی‌تواند روز و سال را جابه‌جا یا
        # به هم بچسباند — همان دو اشکالی که با direction پیش آمد.
        s = (f'<span class="d-d">{jd}</span>'
             f'<span class="d-m">{JMONTHS[jm-1]}</span>'
             f'<span class="d-y">{jy}</span>')
    else:
        s = f"{jd} {JMONTHS[jm-1]} {jy}"
    if not with_day:
        return s
    # نام روز هم باید span باشد، وگرنه گره‌ی متنی خام است و flex
    # نمی‌تواند ترتیبش را تعیین کند.
    day = f'<span class="d-w">{JDAYS[d.weekday()]}</span>' if bidi else JDAYS[d.weekday()]
    return f"{day}{'' if bidi else ' '}{s}"


def jalali_short(d):
    jy, jm, jd = jalali(d.year, d.month, d.day)
    return f"{jy}/{jm:02d}/{jd:02d}"


_today = datetime.date.today()
TODAY = jalali_str(_today)              # «۱۸ شهریور ۱۴۰۵»
TODAY_BIDI = jalali_str(_today, bidi=True)   # همان، با span دور هر بخش
TODAY_DAY = jalali_str(_today, with_day=True, bidi=True)   # با نام روز هفته
TODAY_ISO = _today.isoformat()
TODAY_SHORT = jalali_short(_today)      # «۱۴۰۵/۰۶/۱۸»


def stampchips():
    """دو تراشه‌ی کنار هم: ساعت زنده و تاریخ کامل با نام روز هفته.

    ساعت در مرورگر بازدیدکننده زنده می‌شود (site.js هر ثانیه بروزش
    می‌کند)؛ در زمان ساخت ساعت بروزرسانی نوشته می‌شود تا بدون
    جاوااسکریپت هم عدد بی‌معنی نبینیم.
    """
    return (f'<div class="stampchips">'
            f'<span class="chipx" data-live-clock>{icon("i-clock")}'
            f'<b class="num">{UPDATE_TIME}</b></span>'
            f'<span class="chipx"><time data-live-day datetime="{TODAY_ISO}">'
            f'{TODAY_DAY}</time></span>'
            f'</div>')

# ---------------------------------------------------------------------------
# کمکی‌ها
# ---------------------------------------------------------------------------
FA_DIGITS = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")


def fa(s):
    return str(s).translate(FA_DIGITS)


def esc(s):
    return html.escape(str(s), quote=True)


def money(s):
    d = re.sub(r"[^0-9]", "", str(s))
    return int(d) if d else 0


def fmt(n):
    return f"{n:,}"


def slugify(s):
    s = unicodedata.normalize("NFKC", str(s)).strip()
    s = s.replace("*", "x").replace("/", "-").replace('"', "").replace("،", "")
    s = re.sub(r"\s+", "-", s)
    s = re.sub(r"[^\w\-؀-ۿ]", "", s)
    return re.sub(r"-{2,}", "-", s).strip("-")


def tidy_decimals(s):
    """3/0 → 3 ، 2/20 → 2/2 ، 1/200 → 1/2 — صفرهای بی‌معنی اعشار حذف می‌شوند."""
    def _fix(m):
        a, b = m.group(1), m.group(2).rstrip("0")
        return f"{a}/{b}" if b else a
    return re.sub(r"(?<![\d/])(\d+)/(\d+)(?![\d/])", _fix, str(s))


def clean_name(s):
    """نام محصول برای نمایش: فاصله‌های جاافتاده، «امتر» و اعشارهای اضافه.
    فقط نمایش؛ اسلاگ و نشانی از نام خام ساخته می‌شود تا با بک‌اند بخواند."""
    s = str(s).strip()
    s = s.replace("سانتیمترعرض", "سانتیمتر عرض").replace(" امتر ", " ۱ متر ")
    s = s.replace("سانتیمتر", "سانتی‌متر").replace("سانتی متر", "سانتی‌متر")
    s = re.sub(r"(\d)([آ-ی])", r"\1 \2", s)
    s = re.sub(r"([آ-ی])(\d)", r"\1 \2", s)
    s = s.replace("*", "×").replace("1.5", "1/5")
    s = re.sub(r"\s+", " ", s)
    return tidy_decimals(s)


def clean_val(v):
    """مقدار مشخصات فنی برای نمایش."""
    s = str(v or "").strip()
    if not s:
        return "—"
    s = s.replace('"', "").replace("*", "×")
    s = s.replace("کیلو گرم", "کیلوگرم")
    return tidy_decimals(s)


def price_of(row):
    for k in row:
        if k.startswith("قیمت روز"):
            return money(row[k])
    return 0


def delta_of(row):
    for k in row:
        if k.startswith("نوسان"):
            return money(row[k])
    return 0


def unit_of(row):
    return row.get("واحد", "")


def num_key(v):
    m = re.search(r"\d+(?:[/.]\d+)?", str(v))
    return float(m.group(0).replace("/", ".")) if m else 9e9


def pct(price, prev):
    if not price or not prev:
        return 0.0
    return (price - prev) / prev * 100


def icon(name, cls=""):
    c = f' class="{cls}"' if cls else ""
    return f'<svg{c} aria-hidden="true"><use href="#{name}"/></svg>'


# ---------------------------------------------------------------------------
# نشانی‌ها — دقیقاً همان قرارداد بک‌اند لاراول (routes/web.php)
# ---------------------------------------------------------------------------
SLUGMAP = {}
_slugmap_path = os.path.join(ROOT, "build", "slugs.json")
if os.path.exists(_slugmap_path):
    SLUGMAP = {k: v for k, v in
               json.load(open(_slugmap_path, encoding="utf-8")).items()
               if not k.startswith("_")}


def prod_slug(name):
    return SLUGMAP.get(name) or slugify(name)


def u_home():                return "/"
def u_price():               return "/price"
def u_catlist():             return "/category/"
def u_about():               return "/about"
def u_contact():             return "/contact"
def u_blog():                return "/blog"
def u_blogcat(slug):         return f"/blog/{slug}"
def u_article(cat, slug):    return f"/blog/{cat}/{slug}"
def u_cat(key):              return f"/category/{key}"
def u_prod(key, name):       return f"/category/{key}/{prod_slug(name)}"


def out_path(url):
    u = url.strip("/")
    return "index.html" if not u else f"{u}/index.html"


# ---------------------------------------------------------------------------
# آمار هر دسته
# ---------------------------------------------------------------------------
def cat_stats(key):
    rows = CAT[key]["rows"]
    prices = [price_of(r) for r in rows
              if price_of(r) > 0 and r["نام محصول"] not in C.SUSPECT]
    return {
        "n": len(rows),
        "min": min(prices) if prices else 0,
        "max": max(prices) if prices else 0,
        "avg": sum(prices) // len(prices) if prices else 0,
        "unit": unit_of(rows[0]) if rows else "",
    }


def cat_photo(key, i=0, thumb=False):
    lst = PHOTOS.get(key) or []
    if not lst:
        return ""
    p = lst[min(i, len(lst) - 1)]
    return p.replace("/photo-", "/thumb-") if thumb else p


# ---------------------------------------------------------------------------
# قطعات مشترک صفحه
# ---------------------------------------------------------------------------
# ایندکس‌شدن: پروتوتایپ نباید ایندکس شود، ولی روز انتقال به دامنه‌ی
# اصلی باید شود. با متغیر محیطی SEPAHAN_LIVE=1 بیلد «زنده» ساخته می‌شود:
# noindex برداشته می‌شود، canonical به دامنه‌ی اصلی می‌خورد و robots.txt
# و نقشه‌ی سایت درست تولید می‌شوند. یک سوییچ، نه ویرایش دستی ۱۲۲ صفحه.
LIVE = os.environ.get("SEPAHAN_LIVE") == "1"
SITE = "https://sepahanfelez.ir"

_ROBOTS = ("" if LIVE else
           '\n<meta name="robots" content="noindex, nofollow">'
           '\n<meta name="googlebot" content="noindex, nofollow">')


def head(title, desc, css="/assets/app.css", extra="", canonical=None, jsonld=""):
    canon = (f'\n<link rel="canonical" href="{SITE}{canonical}">'
             if canonical else "")
    return f"""<!doctype html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">{_ROBOTS}{canon}
<meta name="theme-color" content="#B42332">
<title>{esc(title)}</title>
<meta name="description" content="{esc(desc)}">
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Estedad-Regular.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Estedad-Black.woff2" crossorigin>
<link rel="icon" href="/assets/brand/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/brand/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/brand/favicon-180.png">
<meta property="og:image" content="/assets/brand/og-image.png">
<meta property="og:title" content="{esc(title)}">
<meta property="og:description" content="{esc(desc)}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="{css}">{extra}{jsonld}
</head>
<body>
{ICONS}
<a class="skip" href="#main">رفتن به محتوای اصلی</a>"""


UTILBAR = f"""
<div class="utilbar">
  <div class="container">
    <ul class="util-left">
      <li>{icon('i-clock')} شنبه تا چهارشنبه ۸ تا ۱۷ — پنجشنبه ۸ تا ۱۳</li>
      <li><a href="mailto:info@sepahanfelez.ir">{icon('i-mail')} info@sepahanfelez.ir</a></li>
    </ul>
    <ul>
      <li><a href="#">{icon('i-cart')} لیست سفارش (۰)</a></li>
      <li><a href="#">{icon('i-user')} ناحیه کاربری</a></li>
    </ul>
  </div>
</div>"""


def masthead():
    return f"""
<header class="masthead">
  <div class="container">
    <a class="brand" href="{u_home()}">
      <!-- نشان واقعی برند: مکعب لوگو، جدا از قاب شش‌ضلعی.
           قاب در ارتفاع ۴۴ پیکسلیِ هدر به خط‌های نامفهوم تبدیل می‌شد. -->
      <img class="mark" src="/assets/brand/mark-88.png"
           srcset="/assets/brand/mark-88.png 2x, /assets/brand/mark-132.png 3x"
           width="44" height="44" alt="نشان سپاهان فلز" decoding="async">
      <span><span class="name">سپاهان فلز</span><br><span class="sub">فروشگاه اینترنتی صنایع مفتولی طلوع سپاهان</span></span>
    </a>
    <div class="search">
      <label class="vh" for="q">جست‌وجو در کل سایت</label>
      <input id="q" type="search" placeholder="جست‌وجو در کالاها، مجله و صفحه‌های سایت">
      <button type="button" aria-label="جستجو">{icon('i-search')}</button>
    </div>
    <a class="callbox" href="tel:{PH}" data-track="call-header">
      <span class="icon">{icon('i-phone')}</span>
      <span>
        <span class="label">مشاوره و استعلام قیمت</span>
        <span class="number num">{PHS}</span>
        <span class="hint">۱۰ خط ویژه — پاسخگویی همین حالا</span>
      </span>
    </a>
  </div>
</header>"""


def mainnav(current=None):
    def a(href, label, key, cls=""):
        cur = ' aria-current="page"' if current == key else ""
        c = f' class="{cls}"' if cls else ""
        return f'<a href="{href}"{cur}{c}>{label}</a>'
    links = [a(u_price(), "قیمت لحظه‌ای", "price", "nav-price")]
    for key in C.ORDER:
        c = C.CATS[key]
        links.append(a(u_cat(key), c["nav"], c["slug"]))
    links.append('<span class="spacer"></span>')
    links.append(a(u_blog(), "مجله", "blog"))
    links.append(a(u_about(), "درباره کارخانه", "about"))
    links.append(a(u_contact(), "تماس با ما", "contact"))
    return f"""
<nav class="mainnav" aria-label="منوی اصلی">
  <div class="container">
    <div class="nav-scroll">{''.join(links)}</div>
  </div>
</nav>
<!-- کادر جست‌وجوی موبایل: کادر هدر زیر ۱۰۰۰px پنهان می‌شود و بدون این،
     کاربر موبایل اصلاً راهی برای جست‌وجو نداشت. -->
<div class="msearch">
  <div class="container">
    <div class="search">
      <label class="vh" for="q-m">جست‌وجو در کل سایت</label>
      <input id="q-m" data-site-search type="search"
             placeholder="جست‌وجو در کالاها، مجله و صفحه‌ها">
      <button type="button" aria-label="جست‌وجو">{icon('i-search')}</button>
    </div>
  </div>
</div>"""


def stamp(short=False):
    """نشان بروزرسانی — تاریخ در زمان ساخت نوشته می‌شود و site.js آن را
    با تاریخ روزِ بازدید جایگزین می‌کند."""
    t = f'<time data-live-date datetime="{TODAY_ISO}">{TODAY_BIDI}</time>'
    if short:
        return f'<span class="stamp"><span class="dot"></span>بروزرسانی: {t}</span>'
    return (f'<span class="stamp"><span class="dot"></span>'
            f'بروزرسانی: امروز {t} — ساعت {UPDATE_TIME}</span>')


def callband():
    return f"""
<section class="callband" aria-labelledby="cb-h">
  <div class="container">
    <div>
      <h2 id="cb-h">قیمت قطعی خود را از کارشناسان ما بگیرید</h2>
      <p>جدول، قیمت مبنای روز است. برای تناژ بالا، بار مخلوط، تولید
         سفارشی یا تحویل در محل، کارشناس فروش قیمت نهایی و زمان تحویل را در کمتر
         از دو دقیقه اعلام می‌کند.</p>
      <p class="hours">{icon('i-clock')} شنبه تا چهارشنبه ۸ تا ۱۷ — پنجشنبه ۸ تا ۱۳</p>
    </div>
    <div class="lines">
      <a class="line primary" href="tel:{PH}" data-track="call-band">
        <span class="label">استعلام قیمت و ثبت سفارش</span>
        <span class="number num">{PHS}</span>
        <span class="who">دفتر فروش — {C.PHONE_LINES}</span>
      </a>
      <a class="line" href="https://wa.me/{WA}" data-track="whatsapp-band">
        <span class="label">واتساپ کارشناس فروش</span>
        <span class="number num">{WAS}</span>
        <span class="who">ارسال لیست و پیش‌فاکتور</span>
      </a>
    </div>
  </div>
</section>"""


def footer(show_addr=True):
    addr = "".join(
        f'<li><span class="a-t">{esc(t)}</span>{esc(a)}</li>'
        for t, a in C.ADDRESSES)
    addr_col = (f'<div class="fcol-addr"><h3>کارخانه و دفتر فروش</h3>'
                f'<ul class="faddr">{addr}</ul></div>') if show_addr else ""
    soc = "".join(
        f'<a href="{u}" aria-label="{esc(n)} سپاهان فلز" '
        f'rel="noopener" target="_blank">{icon(i)}</a>'
        for i, n, u in C.SOCIALS)
    quick = "".join(f'<li><a href="{h}">{l}</a></li>' for h, l in [
        (u_price(), "قیمت لحظه‌ای"), (u_catlist(), "دسته‌های محصول"),
        (u_blog(), "مجله سپاهان فلز"), (u_about(), "درباره کارخانه"),
        (u_contact(), "تماس با ما")])
    return f"""
<footer class="site">
  <div class="container">
    <div class="fgrid fgrid-3">
      <div class="fcol-call">
        <h3>تماس با سپاهان فلز</h3>
        <p class="fwho">فروشگاه اینترنتی کارخانه‌ی
          <b>{C.FACTORY['name']}</b> — کامل‌ترین سبد کالایی صنایع مفتولی کشور</p>
        <a class="fcall" href="tel:{PH}" data-track="call-footer">
          <span class="label">دفتر فروش — {C.PHONE_LINES}</span>
          <span class="number num">{PHS}</span>
        </a>
        <p class="fmob">واتساپ و شبکه‌های اجتماعی:
          <a href="https://wa.me/{WA}" class="num">{WAS}</a></p>
        <div class="socials">{soc}</div>
        <a class="fmail" href="mailto:info@sepahanfelez.ir">
          {icon('i-mail')}info@sepahanfelez.ir</a>
      </div>
      <div class="fcol-links"><h3>دسترسی سریع</h3><ul class="flinks">{quick}</ul></div>
      {addr_col}
    </div>
    <p class="copyright">فروشگاه اینترنتی صنایع مفتولی طلوع سپاهان</p>
  </div>
</footer>"""


# چت آنلاین گفتینو — شناسه‌ی ویجت کارفرما. اسکریپت async است و رندر را
# عقب نمی‌اندازد. در محیط noindex هم بار می‌شود تا رفتار واقعی دیده شود.
GOFTINO = """<script type="text/javascript">
  !function(){var i="fugB8i",d=document,g=d.createElement("script"),s="https://www.goftino.com/widget/"+i,l=localStorage.getItem("goftino_"+i);g.type="text/javascript",g.async=!0,g.src=l?s+"?o="+l:s;d.getElementsByTagName("head")[0].appendChild(g);}();
</script>"""

# دیالوگ نمودار یک مرجع دارد: features.CHART_DIALOG. اینجا تعریفش
# نمی‌کنیم چون دو نسخه از هم جدا می‌افتند — دقیقاً همان اتفاقی که افتاد
# و دکمه‌های بازه‌ی قدیمی در دیالوگ باقی مانده بود.


def _chart_dialog():
    """دیالوگ نمودار از features خوانده می‌شود. import داخل تابع است چون
    features خودش common را وارد می‌کند و import بالای فایل حلقه می‌سازد."""
    import features
    return features.CHART_DIALOG


def dock():
    return f"""
<nav class="dock" aria-label="تماس سریع">
  <a class="primary" href="tel:{PH}" data-track="call-dock">
    <span class="l">تماس با کارشناس فروش</span>
    <span class="n num">{PHS}</span>
  </a>
  <a href="https://wa.me/{WA}">{icon('i-whatsapp')}واتساپ</a>
  <a href="{u_price()}">{icon('i-chart')}قیمت‌ها</a>
</nav>
{_chart_dialog()}
<script src="/assets/table.js" defer></script>
<script src="/assets/hero.js" defer></script>
<script src="/assets/site.js" defer></script>
<script src="/assets/search.js" defer></script>
<script src="/assets/lightbox.js" defer></script>
<script src="/assets/chart.js" defer></script>
<script src="/assets/tools.js" defer></script>
{GOFTINO}
</body>
</html>"""


def crumb(items):
    out = []
    for i, (label, href) in enumerate(items):
        last = i == len(items) - 1
        if last:
            out.append(f'<li aria-current="page">{esc(label)}</li>')
        else:
            out.append(f'<li><a href="{href}">{esc(label)}</a></li><li class="sep">/</li>')
    return f'<nav class="crumb" aria-label="مسیر"><div class="container"><ol>{"".join(out)}</ol></div></nav>'


def delta_badge(d, price):
    """تغییر نسبت به آخرین قیمت ثبت‌شده."""
    if not d or not price:
        return f'<span class="delta flat">{icon("i-flat")}<span class="num">۰</span></span>'
    p = pct(price, d)
    if p > 0.05:
        return f'<span class="delta up">{icon("i-up")}<span class="num">+{p:.1f}٪</span></span>'
    if p < -0.05:
        return f'<span class="delta down">{icon("i-down")}<span class="num">{p:.1f}٪</span></span>'
    return f'<span class="delta flat">{icon("i-flat")}<span class="num">۰</span></span>'


def page_shell(title, desc, nav_key, body, crumbs=None, show_addr=True,
               canonical=None, jsonld=""):
    """اسکلت کامل صفحه: head + نوارها + main + footer + dock.

    canonical و jsonld از همین‌جا به head می‌روند تا هر صفحه‌ای که ساخته
    می‌شود خودبه‌خود نشانی متعارف و داده‌ی ساختاریافته داشته باشد.
    """
    return (head(title, desc, canonical=canonical, jsonld=jsonld)
            + UTILBAR + masthead() + mainnav(nav_key)
            + (crumb(crumbs) if crumbs else "")
            + f'\n<main id="main" tabindex="-1">{body}\n</main>'
            + footer(show_addr) + dock())
