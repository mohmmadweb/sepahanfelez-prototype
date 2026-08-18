# -*- coding: utf-8 -*-
"""
سازنده‌ی صفحات سپاهان فلز.

خروجی: index.html · price.html · ۹ صفحه‌ی دسته · ۷۰ صفحه‌ی محصول.
داده از build/catalog.json (برداشت‌شده از sepahanfelez.ir)، متن از content.py.

اجرا:  python3 build/gen.py
"""
import json, os, re, html, unicodedata
import content as C
import analysis as A

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CAT  = json.load(open(os.path.join(ROOT, "build", "catalog.json"), encoding="utf-8"))

PH, PHS, WA, WAS = C.PHONE_RAW, C.PHONE_SHOW, C.WHATSAPP, C.WA_SHOW

# تعداد کل کدهای فعال — از کاتالوگ خوانده می‌شود تا با افزودن یا حذف
# محصول، عددِ روی بنر خودبه‌خود درست بماند.
TOTAL_SKUS = sum(len(CAT[k]["rows"]) for k in C.ORDER if k in CAT and CAT[k].get("rows"))
TODAY = "۱۷ مرداد ۱۴۰۵"

# ---------------------------------------------------------------------------
# کمکی‌ها
# ---------------------------------------------------------------------------
FA_DIGITS = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")

def fa(s):
    """اعداد لاتین → فارسی. فقط برای متن روان، نه برای ستون قیمت."""
    return str(s).translate(FA_DIGITS)

def esc(s):
    return html.escape(str(s), quote=True)

def money(s):
    """رشته‌ی قیمت را به int تبدیل می‌کند. '1,220,000' → 1220000"""
    d = re.sub(r"[^0-9]", "", str(s))
    return int(d) if d else 0

def fmt(n):
    return f"{n:,}"

def toman(n):
    """معادل تومان زیر عدد ریال. هر یازده رقیب تومانی‌اند؛ خریداری که دو تب
    باز کرده باید بتواند در یک نگاه تطبیق بدهد، وگرنه ما را گران می‌بیند."""
    return fmt(n // 10)

def slugify(s):
    s = unicodedata.normalize("NFKC", str(s)).strip()
    s = s.replace("*", "x").replace("/", "-").replace('"', "").replace("،", "")
    s = re.sub(r"\s+", "-", s)
    s = re.sub(r"[^\w\-؀-ۿ]", "", s)
    return re.sub(r"-{2,}", "-", s).strip("-")

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

# ---------------------------------------------------------------------------
# آیکون‌ها — یک بار در هر صفحه، بقیه با <use>
# ---------------------------------------------------------------------------
ICONS = """<svg style="display:none" aria-hidden="true">
<symbol id="i-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.9.6 2.8a2 2 0 0 1-.5 2.1L8.1 9.7a16 16 0 0 0 6 6l1.1-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.9 2.2z"/></symbol>
<symbol id="i-whatsapp" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.4A10 10 0 1 0 12 2zm5.6 14.1c-.2.6-1.2 1.2-1.7 1.3-.5.1-1 .1-1.6-.1l-1.7-.6c-2.9-1.3-4.8-4.3-5-4.5-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.9 2.1c.1.2 0 .4-.1.5l-.3.4-.4.4c-.1.1-.3.3-.1.6a9 9 0 0 0 1.5 1.9 8 8 0 0 0 2.2 1.4c.3.1.4.1.6-.1l.9-1.1c.2-.2.4-.2.6-.1l1.9.9c.3.2.5.2.6.4z"/></symbol>
<symbol id="i-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
<symbol id="i-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6 9 6 9-6"/></symbol>
<symbol id="i-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></symbol>
<symbol id="i-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
<symbol id="i-cart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2.5l2.2 11.2a2 2 0 0 0 2 1.6h8.4a2 2 0 0 0 2-1.6L21 7H5.5"/></symbol>
<symbol id="i-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
<symbol id="i-up" viewBox="0 0 24 24" fill="currentColor"><path d="M12 5 4 17h16z"/></symbol>
<symbol id="i-down" viewBox="0 0 24 24" fill="currentColor"><path d="M12 19 4 7h16z"/></symbol>
<symbol id="i-flat" viewBox="0 0 24 24" fill="currentColor"><path d="M5 10h14v4H5z"/></symbol>
<symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20h18"/><path d="m4 15 4-5 4 3 5-7"/></symbol>
<symbol id="i-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><path d="m14 6-6 6 6 6"/></symbol>
<symbol id="i-truck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6h11v11H2zM13 9h4l4 4v4h-8z"/><circle cx="6.5" cy="18.5" r="1.7"/><circle cx="17" cy="18.5" r="1.7"/></symbol>
<symbol id="i-factory" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V10l6 4V10l6 4V6h6v15z"/><path d="M3 21h18"/></symbol>
<symbol id="i-badge" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.5 9.3 8 11 4.5-1.7 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></symbol>
<symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.5 9.3 8 11 4.5-1.7 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></symbol>
<symbol id="i-doc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M8 13h8M8 17h5"/></symbol>
<symbol id="i-scale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 7h14M4 7 2 13h4zM20 7l-2 6h4zM8 21h8"/></symbol>
<symbol id="i-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></symbol>
<symbol id="i-instagram" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></symbol>
<symbol id="i-telegram" viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.9 19c-.2 1-.8 1.3-1.7.8l-4.6-3.4-2.2 2.1c-.3.3-.5.5-1 .5l.3-4.6 8.4-7.6c.4-.3-.1-.5-.6-.2L6.2 13 1.7 11.6c-1-.3-1-1 .2-1.4l19-7.3c.8-.3 1.5.2 1 3.4z"/></symbol>
<symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m4 12.5 5 5L20 6.5"/></symbol>
<symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
</svg>"""

def icon(name, cls=""):
    c = f' class="{cls}"' if cls else ""
    return f'<svg{c} aria-hidden="true"><use href="#{name}"/></svg>'


# ---------------------------------------------------------------------------
# نشانی‌ها — دقیقاً همان قراردادی که بک‌اند لاراول دارد
# ---------------------------------------------------------------------------
# routes/web.php:
#   /price                            PriceListController@index
#   /category/                        CategoryController@list
#   /category/{category}              CategoryController@index
#   /category/{category}/{product}    ProductController@show
#
# اسلاگ دسته همان کلید catalog.json است، چون کاتالوگ از روی همین نشانی‌های
# زنده برداشته شده و مو‌به‌مو با ستون slug جدول categories یکی است.
#
# اسلاگ محصول از عنوان ساخته می‌شود مگر اینکه در build/slugs.json نگاشت
# صریح داشته باشد. این فایل برای همان مواردی است که ادمین اسلاگ را دستی
# عوض کرده — مثلاً «سیم خاردار سوزنی قطر ۶۰ سانتی متر» که در دیتابیس
# «سیم-خاردار-حلقوی-60» است و از عنوان درنمی‌آید.
SLUGMAP = {}
_slugmap_path = os.path.join(ROOT, "build", "slugs.json")
if os.path.exists(_slugmap_path):
    SLUGMAP = {k: v for k, v in
               json.load(open(_slugmap_path, encoding="utf-8")).items()
               if not k.startswith("_")}


def cat_slug(key):
    return key


def prod_slug(name):
    return SLUGMAP.get(name) or slugify(name)


def u_home():              return "/"
def u_price():             return "/price"
def u_catlist():           return "/category/"
def u_cat(key):            return f"/category/{cat_slug(key)}"
def u_prod(key, name):     return f"/category/{cat_slug(key)}/{prod_slug(name)}"


def out_path(url):
    """نشانی → مسیر فایل. هر نشانی یک پوشه با index.html می‌شود تا
    سرور ایستا همان چیزی را بدهد که لاراول می‌داد."""
    u = url.strip("/")
    return "index.html" if not u else f"{u}/index.html"


# ---------------------------------------------------------------------------
# قطعات مشترک
# ---------------------------------------------------------------------------
def head(title, desc, css="/assets/app.css"):
    return f"""<!doctype html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="googlebot" content="noindex, nofollow">
<title>{esc(title)}</title>
<meta name="description" content="{esc(desc)}">
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Estedad-Regular.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/Estedad-Black.woff2" crossorigin>
<link rel="stylesheet" href="{css}">
</head>
<body>
{ICONS}
<a class="skip" href="#main">رفتن به محتوای اصلی</a>"""

PV_STRIP = f"""
<div class="pv-strip">
  <div class="container">
    <span><span class="dotp"></span>پروتوتایپ طراحی — سایت اصلی: <a href="https://sepahanfelez.ir">sepahanfelez.ir</a></span>
    <span>نسخه ۵ · {TODAY}</span>
  </div>
</div>"""

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
      <svg class="mark" viewBox="0 0 54 54" aria-hidden="true">
        <rect width="54" height="54" rx="9" fill="#B42332"/>
        <path d="M13 35c0-6.2 4.2-9.3 9.3-9.3h8.3c3.1 0 5.2-2.1 5.2-4.2s-2.1-4.2-5.2-4.2H15" stroke="#fff" stroke-width="3.4" fill="none" stroke-linecap="round"/>
        <path d="M41 19c0 6.2-4.2 9.3-9.3 9.3h-8.3c-3.1 0-5.2 2.1-5.2 4.2s2.1 4.2 5.2 4.2H39" stroke="#fff" stroke-width="3.4" fill="none" stroke-linecap="round" opacity=".55"/>
      </svg>
      <span><span class="name">سپاهان فلز</span><br><span class="sub">تولیدکننده و مرجع قیمت صنایع مفتولی</span></span>
    </a>
    <div class="search">
      <label class="vh" for="q">جستجوی محصول</label>
      <input id="q" type="search" placeholder="جستجوی محصول یا کد کالا">
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
    links = ['<a href="{u_price()}"%s>قیمت روز</a>' % (' aria-current="page"' if current == "price" else "")]
    for key in C.ORDER:
        c = C.CATS[key]
        cur = ' aria-current="page"' if current == c["slug"] else ""
        links.append(f'<a href="{u_cat(key)}"{cur}>{c["nav"]}</a>')
    links.append('<span class="spacer"></span><a href="#">درباره کارخانه</a><a href="#">تماس با ما</a>')
    return f"""
<nav class="mainnav" aria-label="منوی اصلی">
  <div class="container">
    <div class="nav-scroll">{''.join(links)}</div>
  </div>
</nav>"""

FACTORYBAR = f"""
<div class="factorybar">
  <div class="container">
    <div class="lead">
      {icon('i-factory')}
      <span>
        <span class="t">ما خودمان تولیدکننده‌ایم، نه واسطه</span>
        <span class="s">کشش مفتول، گالوانیزه و بافت — هر سه در کارخانه‌ی اصفهان</span>
      </span>
    </div>
    <ul>
      <li><span class="k">سال تأسیس</span><span class="v">{C.FACTORY['year']}</span></li>
      <li><span class="k">ظرفیت سالانه</span><span class="v">{C.FACTORY['capacity']}</span></li>
      <li><span class="k">سالن تولید</span><span class="v">{C.FACTORY['hall']}</span></li>
      <li><span class="k">کد کالای فعال</span><span class="v">۷۰</span></li>
    </ul>
  </div>
</div>"""

def callband():
    return f"""
<section class="callband" aria-labelledby="cb-h">
  <div class="container">
    <div>
      <h2 id="cb-h">قیمت قطعی را از خود کارخانه بگیرید</h2>
      <p>جدول، قیمت مبنای روز درب کارخانه است. برای تناژ بالا، بار مخلوط، تولید
         سفارشی یا تحویل در محل، کارشناس فروش قیمت نهایی و زمان تحویل را در کمتر
         از دو دقیقه اعلام می‌کند.</p>
      <p class="hours">{icon('i-clock')} شنبه تا چهارشنبه ۸ تا ۱۷ — پنجشنبه ۸ تا ۱۳</p>
    </div>
    <div class="lines">
      <a class="line primary" href="tel:{PH}" data-track="call-band">
        <span class="label">دفتر فروش کارخانه — ۱۰ خط</span>
        <span class="number num">{PHS}</span>
        <span class="who">استعلام قیمت و ثبت سفارش</span>
      </a>
      <a class="line" href="https://wa.me/{WA}" data-track="whatsapp-band">
        <span class="label">واتساپ کارشناس فروش</span>
        <span class="number num">{WAS}</span>
        <span class="who">ارسال لیست و پیش‌فاکتور</span>
      </a>
    </div>
  </div>
</section>"""

def footer():
    """پاصفحه‌ی جمع‌وجور.

    نسخه‌ی قبلی سه ستون بلند بود و فهرست کامل ۹ دسته را تکرار می‌کرد —
    همان فهرستی که در منوی اصلی هست. اینجا فقط چیزی می‌ماند که کسی
    واقعاً ته صفحه دنبالش می‌گردد: راه تماس و آدرس انبارها.

    آدرس‌ها عیناً از sepahanfelez.ir است؛ چیزی به آن‌ها اضافه نشده.
    """
    addr = "".join(
        f'<li><span class="a-t">{esc(t)}</span>{esc(a)}</li>'
        for t, a in C.ADDRESSES)
    soc = "".join(
        f'<a href="{u}" aria-label="{esc(n)} سپاهان فلز" '
        f'rel="noopener" target="_blank">{icon(i)}</a>'
        for i, n, u in C.SOCIALS)
    return f"""
<footer class="site">
  <div class="container">
    <div class="fgrid">
      <div class="fcol-call">
        <h3>تماس با سپاهان فلز</h3>
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

      <div class="fcol-addr">
        <h3>کارخانه و انبارها</h3>
        <ul class="faddr">{addr}</ul>
      </div>
    </div>
    <p class="copyright">سپاهان فلز — فروشگاه اینترنتی {C.FACTORY['name']} ·
      شماره ثبت {C.FACTORY['reg']} · سال تأسیس {C.FACTORY['year']}</p>
  </div>
</footer>"""


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
<script src="/assets/table.js" defer></script>
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

def delta_badge(d, price, light=True):
    """تغییر نسبت به «آخرین قیمت ثبت‌شده» — که در داده‌ی فعلی حدود دو ماه پیش
    است، نه دیروز. پس این درصد را هرگز به‌عنوان «تغییر امروز» برچسب نمی‌زنیم؛
    ستون و کارت هر دو صراحتاً می‌گویند مبنا چیست. با بروزرسانی روزانه، همین
    عدد به ۰/۵ تا ۲ درصد می‌رسد و تازه معنی پیدا می‌کند."""
    if not d or not price:
        return f'<span class="delta flat">{icon("i-flat")}<span class="num">۰</span></span>'
    pct = (price - d) / d * 100
    if pct > 0.05:
        return f'<span class="delta up">{icon("i-up")}<span class="num">+{pct:.1f}٪</span></span>'
    if pct < -0.05:
        return f'<span class="delta down">{icon("i-down")}<span class="num">{pct:.1f}٪</span></span>'
    return f'<span class="delta flat">{icon("i-flat")}<span class="num">۰</span></span>'

def spark(up=True, seed=0):
    pts = [(0, 20), (14, 18), (28, 19), (42, 15), (56, 14), (70, 11), (84, 9)]
    if not up:
        pts = [(x, 30 - y) for x, y in pts]
    p = " ".join(f"{x},{y + (seed % 3)}" for x, y in pts)
    col = "#5FD9A4" if up else "#FF9AA4"
    return (f'<svg width="86" height="30" viewBox="0 0 86 30" aria-hidden="true">'
            f'<polyline fill="none" stroke="{col}" stroke-width="2" stroke-linecap="round" '
            f'stroke-linejoin="round" points="{p}"/></svg>')

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

# ---------------------------------------------------------------------------
# ردیف جدول قیمت
# ---------------------------------------------------------------------------
def price_row(key, row, idx, specs, with_cat=False):
    c = C.CATS[key]
    name = row["نام محصول"]
    p = price_of(row)
    d = delta_of(row)
    href = u_prod(key, name)
    cat_line = f'{c["title"]} · ' if with_cat else ""
    # ستون چهارم به بعد کم‌اولویت‌اند: زیر ۱۶۰۰px پنهان می‌شوند تا جدول در عرض
    # دسکتاپ جا شود. مقدار کاملشان در جدول «مشخصات فنی کامل» همین صفحه هست.
    spec_tds = "".join(
        f'<td class="spec{" spec-lo" if i >= 3 else ""}" data-label="{esc(s)}">'
        f'{esc(row.get(s, "—")) or "—"}</td>' for i, s in enumerate(specs))
    flag = ""
    if name in C.SUSPECT:
        flag = (f'<span class="review" title="{esc(C.SUSPECT[name])}">'
                f'قیمت نیازمند بازبینی</span>')
    data_specs = " ".join(
        f'data-s{n}="{esc(str(row.get(sp,"")))}"' for n, sp in enumerate(specs))
    return f"""<tr data-name="{esc(name)}" data-price="{p}" {data_specs}>
  <td class="cell-name" data-label="محصول">
    <div class="p-name"><a href="{href}">{esc(name)}</a></div>
    <div class="p-meta">{cat_line}واحد: {esc(unit_of(row))} <span class="mine">{icon('i-shield')}تولید ما</span></div>
    <div class="p-meta"><span class="stock stock-in">موجود</span> {flag}</div>
  </td>
  <td class="cell-price" data-label="قیمت روز">
    <span class="p-price num">{fmt(p)}</span><span class="riyal">ریال</span>
    <span class="toman">معادل <span class="num">{toman(p)}</span> تومان</span>
  </td>
  <td data-label="تغییر از آخرین ثبت">{delta_badge(d, p)}</td>
  {spec_tds}
  <td class="cell-act">
    <div class="rowactions">
      <a class="btn btn-call" href="tel:{PH}" data-track="call-row">{icon('i-phone')} استعلام تلفنی</a>
      <a class="btn btn-ghost btn-icon" href="{href}" aria-label="جزئیات {esc(name)}">{icon('i-chev')}</a>
    </div>
  </td>
</tr>"""

def filter_bar(key, specs, rows, tid):
    """نوار جست‌وجوی پیشرفته‌ی جدول.

    بدون جاوااسکریپت هم صفحه سالم است: جدول کامل رندر شده و این نوار
    با `hidden` پنهان می‌ماند؛ اسکریپت که اجرا شد بازش می‌کند. یعنی
    اگر جاوااسکریپت نیامد، کاربر جدول کامل را دارد نه یک فرم بی‌کار.

    فیلترها از مقدارهای واقعی همان دسته ساخته می‌شوند، نه فهرست ثابت.
    """
    sels = []
    for n, sp in enumerate(specs[:4]):
        vals = sorted({str(r.get(sp, "")).strip() for r in rows if str(r.get(sp, "")).strip()},
                      key=lambda v: (num_key(v), v))
        if len(vals) < 2:
            continue
        opts = "".join(f'<option value="{esc(v)}">{esc(v)}</option>' for v in vals)
        sels.append(f"""<label class="ffield">
        <span>{esc(sp)}</span>
        <select data-spec="{n}"><option value="">همه</option>{opts}</select>
      </label>""")
    return f"""<form class="tfilter" data-for="{tid}" hidden
      onsubmit="return false" aria-label="جست‌وجوی پیشرفته در جدول">
  <div class="tfilter-row">
    <label class="ffield fsearch">
      <span>جست‌وجوی نام یا کد کالا</span>
      <input type="search" data-q placeholder="مثلاً: چشمه ۵/۵ یا مفتول ۳" autocomplete="off">
    </label>
    {''.join(sels)}
    <label class="ffield">
      <span>مرتب‌سازی</span>
      <select data-sort>
        <option value="">پیش‌فرض جدول</option>
        <option value="asc">قیمت: ارزان به گران</option>
        <option value="desc">قیمت: گران به ارزان</option>
      </select>
    </label>
  </div>
  <div class="tfilter-foot">
    <output data-count aria-live="polite"></output>
    <button type="button" class="btn btn-ghost" data-reset>پاک‌کردن فیلترها</button>
  </div>
</form>"""


def num_key(v):
    m = re.search(r"\d+(?:[/.]\d+)?", str(v))
    return float(m.group(0).replace("/", ".")) if m else 9e9


def price_table(key, caption, limit=None, with_cat=False, search=False):
    specs = CAT[key]["specs"]
    rows = CAT[key]["rows"][:limit] if limit else CAT[key]["rows"]
    th = "".join(f'<th scope="col" class="spec{" spec-lo" if i >= 3 else ""}">{esc(s)}</th>'
                 for i, s in enumerate(specs))
    body = "".join(price_row(key, r, i, specs, with_cat) for i, r in enumerate(rows))
    tid = f"t-{slugify(key)}"
    bar = filter_bar(key, specs, rows, tid) if search else ""
    return f"""{bar}<div class="ptable-wrap on-light">
<table class="ptable" id="{tid}">
  <caption>{esc(caption)}</caption>
  <thead><tr>
    <th scope="col">محصول</th>
    <th scope="col">قیمت روز</th>
    <th scope="col">تغییر از آخرین ثبت</th>
    {th}
    <th scope="col" class="c">استعلام</th>
  </tr></thead>
  <tbody>{body}</tbody>
</table>
</div>"""

# ---------------------------------------------------------------------------
# صفحه اصلی

# ---------------------------------------------------------------------------
# بنر بزرگ صفحه‌ی اصلی
# ---------------------------------------------------------------------------
# الگو از ahanonline گرفته شده — بنر تمام‌عرض، تیتر درشت، شماره‌ی تلفن روی
# خود بنر. با یک تفاوت عمدی: آنجا بنر یک اسلایدر عکس محصول است و جدول قیمت
# را به زیر خط تا می‌راند. مزیت اصلی این سایت دقیقاً همان چیزی است که آن
# اسلایدر دفن می‌کند، پس بنر خودش قیمت را حمل می‌کند: سه عدد زنده‌ی امروز
# داخل بنر می‌نشیند و تابلوی کامل بلافاصله زیرش می‌آید.
#
# پس‌زمینه SVG درون‌خطی است — بافت توری، یعنی خود محصول. عکس محصولی در
# مخزن نیست و بافت توری هم در هر اندازه تیز می‌ماند و فایلی هم بار نمی‌کند.
def hero():
    """اسلایدر تمام‌عرض صفحه‌ی اصلی.

    الگو از ahanonline. بدون جاوااسکریپت: لغزش با scroll-snap و نقطه‌های
    پایین لینک لنگرند. عکس‌ها از assets/slides/ می‌آیند و در content.SLIDES
    تعریف می‌شوند؛ نبودِ فایل عکس چیزی را نمی‌شکند.
    """
    slides, dots = [], []
    for i, sl in enumerate(C.SLIDES, 1):
        c = C.CATS.get(sl["cat"])
        href = u_cat(sl["cat"]) if c else u_price()
        # عکس درون style چون نام فایل داده است نه کلاس ثابت
        bg = (f' style="background-image:url(/assets/slides/{esc(sl["img"])})"'
              if sl.get("img") else "")
        # تیتر اسلاید اول h1 است، بقیه h2 — در هر صفحه فقط یک h1
        tag = "h1" if i == 1 else 'h2 class="stitle"'
        endtag = "h1" if i == 1 else "h2"
        slides.append(f"""<article class="slide" id="s{i}"{bg}
        aria-roledescription="اسلاید" aria-label="{esc(sl['title'])}">
      <div class="container"><div class="slide-in">
        <p class="eyebrow">{esc(sl['eyebrow'])}</p>
        <{tag}>{esc(sl['title'])}<span>{esc(sl['sub'])}</span></{endtag}>
        <p class="sdesc">{esc(sl['desc'])}</p>
        <div class="slide-cta">
          <a class="slide-tel" href="tel:{PH}" data-track="call-slide">
            {icon('i-phone')}
            <span><span class="l">دفتر فروش کارخانه — {C.PHONE_LINES}</span>
            <span class="n num">{PHS}</span></span>
          </a>
          <a class="btn btn-ghost" href="{href}">مشاهده قیمت‌ها {icon('i-chev')}</a>
        </div>
      </div></div>
    </article>""")
        dots.append(f'<a href="#s{i}"><span class="vh">اسلاید {fa(i)}</span></a>')

    return f"""
<section class="hero" aria-label="معرفی محصولات">
  <div class="hero-track">{''.join(slides)}</div>
  <nav class="hero-dots" aria-label="انتخاب اسلاید">{''.join(dots)}</nav>
</section>"""


# ---------------------------------------------------------------------------
def build_index():
    tiles = []
    for i, key in enumerate(C.ORDER):
        c, s = C.CATS[key], cat_stats(key)
        rows = CAT[key]["rows"]
        d = delta_of(rows[0]) if rows else 0
        p = price_of(rows[0]) if rows else 0
        up = p >= d
        rng = (f'از <span class="num">{fmt(s["min"])}</span> تا <span class="num">{fmt(s["max"])}</span>'
               if s["min"] != s["max"] else f'<span class="num">{fmt(s["min"])}</span>')
        tiles.append(f"""<a class="ix" href="{u_cat(key)}">
  <span class="k">{esc(c['title'])}</span>
  <span class="v"><span class="num">{fmt(s['min'])}</span> <span class="u">ریال / {esc(s['unit'])}</span></span>
  <span class="d">{delta_badge(d, p)}{spark(up, i)}</span>
  <span class="range">{rng}</span>
</a>""")

    cards = []
    for key in C.ORDER:
        c, s = C.CATS[key], cat_stats(key)
        pr = (f'از <b class="num">{fmt(s["min"])}</b> تا <b class="num">{fmt(s["max"])}</b> <span class="u">ریال</span>'
              if s["min"] != s["max"] else f'<b class="num">{fmt(s["min"])}</b> <span class="u">ریال</span>')
        cards.append(f"""<a class="catcard" href="{u_cat(key)}">
  <span class="t">{esc(c['title'])}</span><span class="n">{fa(s['n'])} کد کالا · واحد: {esc(s['unit'])}</span>
  <span class="pr">{pr}</span></a>""")

    facts = "".join(f'<div class="factnum"><div class="v">{v}</div><div class="k">{k}</div></div>'
                    for v, k in C.FACT_NUMBERS)
    trust = "".join(f'<div class="trustitem">{icon(ic)}<div><div class="t">{t}</div><div class="d">{d}</div></div></div>'
                    for ic, t, d in C.TRUST)

    return f"""{head("قیمت روز توری و محصولات مفتولی — کارخانه سپاهان فلز",
      "قیمت روز توری حصاری، توری پرسی، مش جوشی، توری مرغی، توری گابیون، سیم خاردار و مفتول آرماتوربندی مستقیم از خط تولید کارخانه اصفهان. ۷۰ کد کالا، قیمت به ریال، بروزرسانی هر روز کاری.")}
{PV_STRIP}{UTILBAR}{masthead()}{mainnav()}{FACTORYBAR}

<main id="main" tabindex="-1">
{hero()}

  <!-- ★ تابلوی قیمت — امضای طراحی -->
  <section class="board">
    <div class="container">
      <div class="board-head">
        <div>
          <h2 class="board-title">قیمت روز صنایع مفتولی<span>مستقیم از خط تولید، بدون واسطه</span></h2>
          <p class="lede">۷۰ کد کالای فعال در ۹ دسته. قیمت درب کارخانه‌ی اصفهان به ریال —
             و معادل تومانی هر عدد، زیر همان عدد.</p>
        </div>
        <span class="stamp"><span class="dot"></span>بروزرسانی: امروز {TODAY} — ساعت ۹:۰۰</span>
      </div>

      <div class="ixgrid">{''.join(tiles)}</div>

      <div class="board-call">
        <p class="say">قیمت جدول، قیمت مبناست. <b>قیمت قطعی سفارش شما به تناژ و مقصد بار
          بستگی دارد</b> و کارشناس فروش آن را در همان تماس اعلام می‌کند.</p>
        <a class="tel" href="tel:{PH}" data-track="call-board">
          {icon('i-phone')}
          <span><span class="l">دفتر فروش کارخانه — ۱۰ خط</span>
          <span class="n num">{PHS}</span></span>
        </a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="policy">
        {icon('i-refresh')}
        <div>
          <div class="t">قیمت‌ها هر روز کاری ساعت ۹ صبح بروزرسانی می‌شوند</div>
          <div class="d">ستون «نوسان» درصد تغییر نسبت به آخرین قیمت ثبت‌شده را نشان می‌دهد،
            نه اختلاف ریالی خام. قیمت‌ها درب کارخانه‌ی اصفهان است و ارزش افزوده جداگانه
            محاسبه می‌شود.</div>
        </div>
      </div>

      <div class="section-head">
        <div><h2>نمونه‌ای از جدول قیمت</h2>
          <div class="sub">شش کد از پرمصرف‌ترین‌ها — جدول کامل ۷۰ کد در صفحه‌ی قیمت روز</div></div>
        <a href="{u_price()}">جدول کامل ۷۰ کد کالا {icon('i-chev')}</a>
      </div>
      {price_table("توری-حصاری", "قیمت روز توری حصاری — شش کد نخست", limit=6)}
      <div class="tfoot">
        <p>قیمت‌ها به ریال و درب کارخانه‌ی اصفهان است. ارزش افزوده جداگانه محاسبه می‌شود.</p>
        <a class="btn btn-ghost" href="{u_price()}">مشاهده جدول کامل {icon('i-chev')}</a>
      </div>
    </div>
  </section>

{callband()}

  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h2>قیمت به تفکیک دسته</h2>
          <div class="sub">نُه دسته‌ی فعال، با بازه‌ی قیمت و واحد فروش هر کدام</div></div>
      </div>
      <div class="catgrid">{''.join(cards)}</div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="section-head">
        <div><h2>چرا خرید از کارخانه فرق دارد</h2>
          <div class="sub">شش تفاوتی که واسطه نمی‌تواند تکرارشان کند</div></div>
      </div>
      <div class="trustgrid">{trust}</div>
      <div class="factnums">{facts}</div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="prose">
        <h2>کارخانه‌ی سپاهان فلز</h2>
        <p>سپاهان فلز فروشگاه اینترنتی <strong>{C.FACTORY['name']}</strong> است؛ کارخانه‌ای که
           در سال {C.FACTORY['year']} با شماره ثبت {C.FACTORY['reg']} و با مجوز رسمی وزارت صنایع
           و معادن در استان اصفهان کار خود را آغاز کرد و امروز با ظرفیت سالانه‌ی
           {C.FACTORY['capacity']} محصولات مفتولی تولید می‌کند.</p>
        <p>آنچه این مجموعه را از فروشندگان بازار جدا می‌کند، کامل‌بودن چرخه‌ی تولید است:
           <strong>کشش مفتول، گالوانیزه و بافت هر سه در همین مجموعه انجام می‌شود</strong>. یعنی
           کیفیت مفتول اولیه، ضخامت پوشش روی و یکنواختی بافت، سه حلقه‌ای که در خرید از بازار
           قابل کنترل نیستند، اینجا زیر یک سقف کنترل می‌شوند.</p>
        <p>بخش ماشین‌سازی داخلی کارخانه بخشی از تجهیزات خط تولید را طراحی و می‌سازد، و
           همین امکان تولید سفارشی — عرض، چشمه و ضخامت مطابق نقشه‌ی پروژه — را عملی می‌کند.
           تحویل بار از انبارهای <strong>اصفهان و تهران</strong> انجام می‌شود.</p>
      </div>
    </div>
  </section>
</main>
{footer()}{dock()}"""

# ---------------------------------------------------------------------------
# صفحه‌ی قیمت کل
# ---------------------------------------------------------------------------
def build_price():
    blocks = []
    for key in C.ORDER:
        c, s = C.CATS[key], cat_stats(key)
        blocks.append(f"""<div class="section-head catjump" id="{c['slug']}">
  <div><h2>{esc(c['title'])}</h2>
    <div class="sub">{fa(s['n'])} کد فعال · واحد فروش: {esc(s['unit'])} · {esc(c['unit_note'])}</div></div>
  <a href="{u_cat(key)}">راهنمای خرید و مشخصات کامل {icon('i-chev')}</a>
</div>
{price_table(key, f"قیمت روز {c['title']} — {fa(s['n'])} کد کالا", search=True)}""")

    chips = "".join(f'<a class="btn btn-ghost" href="#{C.CATS[k]["slug"]}">{C.CATS[k]["nav"]}</a>' for k in C.ORDER)

    return f"""{head("جدول کامل قیمت روز — ۷۰ کد کالا | سپاهان فلز",
      "جدول کامل قیمت روز ۷۰ کد کالای مفتولی سپاهان فلز در ۹ دسته، با مشخصات فنی کامل هر کد. قیمت به ریال، درب کارخانه اصفهان.")}
{PV_STRIP}{UTILBAR}{masthead()}{mainnav("price")}{FACTORYBAR}
{crumb([("خانه", u_home()), ("جدول کامل قیمت", "#")])}

<main id="main" tabindex="-1">
  <section class="board">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>جدول کامل قیمت روز<span>۷۰ کد کالا در ۹ دسته</span></h1>
          <p class="lede">همه‌ی کدهای فعال با مشخصات فنی کامل. هر دسته ستون‌های
             مخصوص خودش را دارد — چون مقایسه‌ی چشمه با متراژ رول بی‌معناست.</p>
        </div>
        <span class="stamp"><span class="dot"></span>بروزرسانی: امروز {TODAY} — ساعت ۹:۰۰</span>
      </div>
      <div class="board-call">
        <p class="say">دنبال کد خاصی می‌گردید یا تناژ بالا می‌خواهید؟
          <b>یک تماس، قیمت قطعی و زمان تحویل.</b></p>
        <a class="tel" href="tel:{PH}" data-track="call-board">
          {icon('i-phone')}
          <span><span class="l">دفتر فروش کارخانه — ۱۰ خط</span>
          <span class="n num">{PHS}</span></span>
        </a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="policy">
        {icon('i-refresh')}
        <div>
          <div class="t">همه‌ی قیمت‌ها به ریال و درب کارخانه‌ی اصفهان</div>
          <div class="d">زیر هر قیمت، معادل تومانی آن نوشته شده تا مقایسه با سایت‌هایی که
            تومانی کار می‌کنند بدون محاسبه‌ی ذهنی ممکن باشد. ارزش افزوده جداگانه است.</div>
        </div>
      </div>
      <div class="chiprow">{chips}</div>
      {''.join(blocks)}
    </div>
  </section>
{callband()}
</main>
{footer()}{dock()}"""

# ---------------------------------------------------------------------------
# صفحه‌ی دسته
# ---------------------------------------------------------------------------
def build_category(key):
    c, s = C.CATS[key], cat_stats(key)
    rows = CAT[key]["rows"]
    specs = CAT[key]["specs"]

    # بند اول باز می‌ماند، بقیه داخل آکاردئون
    intro_lead = f"<p>{c['intro'][0]}</p>" if c["intro"] else ""
    intro_rest = "".join(f"<p>{x}</p>" for x in c["intro"][1:])
    pricing = "".join(f"<p>{p}</p>" for p in c["pricing"])
    choose = "".join(f"<h3>{esc(t)}</h3><p>{d}</p>" for t, d in c["choose"])
    mistakes = "".join(f"<li>{m}</li>" for m in c["mistakes"])
    faq = "".join(f"<details><summary>{esc(q)}</summary><div class=\"a\">{esc(a)}</div></details>"
                  for q, a in c["faq"])

    # جدول مشخصات فنی — داده‌ی ساختاریافته‌ای که هیچ رقیبی ندارد
    sp_head = "".join(f"<th>{esc(x)}</th>" for x in ["نام کد"] + specs)
    sp_body = "".join(
        "<tr><td>" + esc(r["نام محصول"]) + "</td>" +
        "".join(f'<td class="num">{esc(r.get(x, "—")) or "—"}</td>' for x in specs) + "</tr>"
        for r in rows)

    return f"""{head(f"قیمت روز {c['title']} — خرید از کارخانه | سپاهان فلز", c["meta"])}
{PV_STRIP}{UTILBAR}{masthead()}{mainnav(c["slug"])}{FACTORYBAR}
{crumb([("خانه", u_home()), ("جدول کامل قیمت", u_price()), (c["title"], "#")])}

<main id="main" tabindex="-1">
  <section class="board">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>{esc(c['h1'])}</h1>
          <p class="lede">{esc(c['lede'])}</p>
        </div>
        <span class="stamp"><span class="dot"></span>بروزرسانی: امروز {TODAY}</span>
      </div>
      <div class="ixgrid ix-4">
        <div class="ix"><span class="k">کد کالای فعال</span>
          <span class="v"><span class="num">{fa(s['n'])}</span></span>
          <span class="range">در این دسته</span></div>
        <div class="ix"><span class="k">کمترین قیمت</span>
          <span class="v"><span class="num">{fmt(s['min'])}</span> <span class="u">ریال</span></span>
          <span class="range">هر {esc(s['unit'])}</span></div>
        <div class="ix"><span class="k">بیشترین قیمت</span>
          <span class="v"><span class="num">{fmt(s['max'])}</span> <span class="u">ریال</span></span>
          <span class="range">هر {esc(s['unit'])}</span></div>
        <div class="ix"><span class="k">واحد فروش</span>
          <span class="v">{esc(s['unit'])}</span>
          <span class="range">{esc(c['unit_note'])}</span></div>
      </div>
      <div class="board-call">
        <p class="say">برای {esc(c['title'])} با تناژ پروژه‌ای یا تولید سفارشی،
          <b>قیمت قطعی را در یک تماس بگیرید.</b></p>
        <a class="tel" href="tel:{PH}" data-track="call-board">
          {icon('i-phone')}
          <span><span class="l">دفتر فروش کارخانه — ۱۰ خط</span>
          <span class="n num">{PHS}</span></span>
        </a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h2>جدول قیمت روز {esc(c['title'])}</h2>
          <div class="sub">{fa(s['n'])} کد فعال با مشخصات فنی کامل — قیمت به ریال</div></div>
      </div>
      {price_table(key, f"قیمت روز {c['title']} — {TODAY}", search=True)}
    </div>
  </section>

  <!-- راهنمای خرید در آکاردئون است، نه باز روی صفحه.

       این بخش ۱۷۸۶ پیکسل بود — یک‌سوم کل صفحه — و جدول قیمت را که دلیل
       اصلی آمدن بازدیدکننده است، به عمق صفحه می‌راند. متن حذف نشده چون
       برای سئو لازم است؛ <details> را گوگل می‌خواند و ایندکس می‌کند.
       فقط اولین بند باز می‌ماند تا صفحه بی‌مقدمه شروع نشود. -->
  <section class="section alt">
    <div class="container">
      <div class="prose">
        <h2>{esc(c['title'])} چیست و کجا به کار می‌آید</h2>
        {intro_lead}
      </div>

      <div class="guides">
        <details class="guide" open>
          <summary>{esc(c['title'])} — ادامه‌ی معرفی</summary>
          <div class="prose">{intro_rest}</div>
        </details>

        <details class="guide">
          <summary>{esc(c['pricing_title'])}</summary>
          <div class="prose">{pricing}
            <div class="callout">
              <div class="t">قیمت‌ها به ریال است</div>
              <p>زیر هر عدد ریالی، معادل تومانی‌اش نوشته شده. اگر این قیمت را با
                 سایتی که تومانی کار می‌کند مقایسه می‌کنید، عدد کوچک‌تر را ملاک
                 بگیرید تا مقایسه درست باشد.</p>
            </div>
          </div>
        </details>

        <details class="guide">
          <summary>{esc(c['choose_title'])}</summary>
          <div class="prose">{choose}</div>
        </details>

        <details class="guide">
          <summary>چهار اشتباه رایج در خرید {esc(c['title'])}</summary>
          <div class="prose"><ul class="bul">{mistakes}</ul></div>
        </details>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="tablescroll">
      <table class="spectable">
        <caption>مشخصات فنی کامل — {fa(s['n'])} کد {esc(c['title'])}</caption>
        <thead><tr>{sp_head}</tr></thead>
        <tbody>{sp_body}</tbody>
      </table>
      </div>
      <p class="tnote">
        این جدول مشخصات، داده‌ی خط تولید خودمان است. مقادیر وزن، مبنای محاسبه‌ی
        هزینه‌ی هر مترمربع یا هر برگ است و هنگام تحویل با باسکول قابل بررسی است.</p>
    </div>
  </section>

{callband()}

  <section class="section">
    <div class="container">
      <div class="section-head"><div><h2>پرسش‌های پرتکرار درباره‌ی {esc(c['title'])}</h2></div></div>
      <div class="faq">{faq}</div>
    </div>
  </section>
</main>
{footer()}{dock()}"""

# ---------------------------------------------------------------------------
# صفحه‌ی محصول — محتوای یکتا از روی مشخصات واقعی همان کد
# ---------------------------------------------------------------------------
# context لازم برای ماژول تحلیل — توابع کمکی همین فایل
ANA = {"price_of": price_of, "slugify": slugify, "esc": esc,
       "fa": fa, "phone": PHS, "u_prod": None}   # u_prod در build_product پر می‌شود


def build_product(key, row, idx):
    c = C.CATS[key]
    rows = CAT[key]["rows"]
    specs = CAT[key]["specs"]
    name = row["نام محصول"]
    p, d = price_of(row), delta_of(row)
    unit = unit_of(row)

    spec_rows = "".join(
        f"<tr><th>{esc(x)}</th><td class=\"num\">{esc(row.get(x, '—')) or '—'}</td></tr>"
        for x in specs)

    # مقایسه با هم‌دسته‌ای‌ها — عددی، از داده‌ی واقعی
    others = [r for r in rows if r["نام محصول"] != name][:6]
    rel = "".join(
        f'<li><a href="{u_prod(key, r["نام محصول"])}">{esc(r["نام محصول"])}</a> — '
        f'<span class="num">{fmt(price_of(r))}</span> ریال</li>' for r in others)

    # جایگاه این کد در دسته
    prices = sorted({price_of(r) for r in rows if price_of(r) > 0})
    if p and prices:
        if p == min(prices):
            rank = "ارزان‌ترین کد فعال این دسته است."
        elif p == max(prices):
            rank = "گران‌ترین کد فعال این دسته است."
        else:
            rank = f"در میانه‌ی بازه‌ی قیمتی این دسته قرار دارد ({fa(len(prices))} سطح قیمت فعال)."
    else:
        rank = "قیمت این کد تلفنی اعلام می‌شود."

    spec_sentence = "؛ ".join(f"{s} برابر {row.get(s)}" for s in specs if row.get(s) and row.get(s) != "—")

    return f"""{head(f"قیمت {name} | سپاهان فلز",
      f"قیمت روز {name} از دسته‌ی {c['title']}، مستقیم از کارخانه اصفهان. مشخصات فنی کامل، واحد فروش {unit} و قیمت به ریال.")}
{PV_STRIP}{UTILBAR}{masthead()}{mainnav(c["slug"])}{FACTORYBAR}
{crumb([("خانه", u_home()), ("جدول کامل قیمت", u_price()),
        (c["title"], u_cat(key)), (name, "#")])}

<main id="main" tabindex="-1">
  <section class="board">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>{esc(name)}<span>{esc(c['title'])} — تولید کارخانه‌ی اصفهان</span></h1>
          <p class="lede">قیمت روز به ریال، واحد فروش {esc(unit)}. {esc(rank)}</p>
        </div>
        <span class="stamp"><span class="dot"></span>بروزرسانی: امروز {TODAY}</span>
      </div>
      <div class="ixgrid ix-3">
        <div class="ix"><span class="k">قیمت روز</span>
          <span class="v"><span class="num">{fmt(p)}</span> <span class="u">ریال / {esc(unit)}</span></span>
          <span class="range">معادل <span class="num">{toman(p)}</span> تومان</span></div>
        <div class="ix"><span class="k">نوسان نسبت به ثبت قبلی</span>
          <span class="v">{delta_badge(d, p)}</span>
          <span class="range">قیمت قبلی: <span class="num">{fmt(d)}</span> ریال</span></div>
        <div class="ix"><span class="k">وضعیت</span>
          <span class="v">موجود</span>
          <span class="range">بارگیری از کارخانه‌ی اصفهان</span></div>
      </div>
      <div class="board-call">
        <p class="say">برای این کد <b>قیمت قطعی، تناژ و زمان تحویل</b> را تلفنی بگیرید.
          قیمت بالا مبنای روز است و با تناژ و مقصد بار نهایی می‌شود.</p>
        <a class="tel" href="tel:{PH}" data-track="call-product">
          {icon('i-phone')}
          <span><span class="l">دفتر فروش کارخانه — ۱۰ خط</span>
          <span class="n num">{PHS}</span></span>
        </a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="prose">
        <h2>مشخصات فنی این کد</h2>
        <p>این کد از دسته‌ی <strong>{esc(c['title'])}</strong> است و {esc(spec_sentence)} دارد.
           واحد فروشش {esc(unit)} است — {esc(c['unit_note'])}</p>
      </div>
      <div class="tablescroll">
      <table class="spectable narrow">
        <caption>{esc(name)}</caption>
        <tbody>
          <tr><th>دسته</th><td><a href="{u_cat(key)}">{esc(c['title'])}</a></td></tr>
          <tr><th>واحد فروش</th><td>{esc(unit)}</td></tr>
          {spec_rows}
          <tr><th>قیمت روز</th><td><b class="num">{fmt(p)}</b> ریال</td></tr>
        </tbody>
      </table>
      </div>

      {A.price_block(key, row, rows, dict(ANA, u_prod=lambda nm, k=key: u_prod(k, nm)))}

      <div class="prose">
        <!-- راهنمای قیمت‌گذاری دسته عمداً اینجا تکرار نمی‌شود: همان متن روی
             صفحه‌ی دسته هست و تکرارش روی هر ۱۳ کد، ۱۳ صفحه‌ی تقریباً یکسان
             می‌سازد. صفحه‌ی محصول فقط عددِ خودِ این کد را می‌گوید، و برای
             راهنما به دسته لینک می‌دهد. -->
        <h2>پیش از سفارش این کد</h2>
        {''.join(f'<p>{x}</p>' for x in A.buying_notes(key, row, ANA))}
        {''.join(f'<p>{x}</p>' for x in A.sibling_notes(key, row, rows, specs, dict(ANA, u_prod=lambda nm, k=key: u_prod(k, nm))))}

        <p><a class="guidelink" href="{u_cat(key)}"><strong>راهنمای کامل خرید {esc(c['title'])} — چطور قیمت بدهید و چه چیزی را مقایسه کنید ←</strong></a></p>

        <h2>کدهای دیگر همین دسته</h2>
        <ul class="bul">{rel}</ul>
        <p><a href="{u_cat(key)}"><strong>مشاهده‌ی راهنمای کامل خرید {esc(c['title'])} و جدول هر {fa(len(rows))} کد ←</strong></a></p>
      </div>
    </div>
  </section>
{callband()}
</main>
{footer()}{dock()}"""

# ---------------------------------------------------------------------------
def build_catlist():
    """صفحه‌ی /category/ — معادل CategoryController@list در بک‌اند.

    بدون این، سرور ایستا فهرست پوشه می‌داد: صفحه‌ای بی‌قالب با نام
    پوشه‌های فارسی. بک‌اند برای همین نشانی یک روت جدا دارد.
    """
    cards = []
    for key in C.ORDER:
        c, st = C.CATS[key], cat_stats(key)
        cards.append(f"""<a class="catcard" href="{u_cat(key)}">
      <h3>{esc(c['title'])}</h3>
      <p class="n">{fa(st['n'])} کد فعال · واحد فروش {esc(st['unit'])}</p>
      <p class="rng">از <b class="num">{fmt(st['min'])}</b> تا
         <b class="num">{fmt(st['max'])}</b> ریال</p>
      <span class="go">مشاهده قیمت‌ها {icon('i-chev')}</span>
    </a>""")
    return f"""{head("همه‌ی دسته‌های محصول — قیمت روز | سپاهان فلز",
      "فهرست کامل دسته‌های صنایع مفتولی سپاهان فلز با بازه‌ی قیمت روز و واحد فروش هر دسته.")}
{PV_STRIP}{UTILBAR}{masthead()}{mainnav()}{FACTORYBAR}
{crumb([("خانه", u_home()), ("دسته‌های محصول", "#")])}
<main id="main" tabindex="-1">
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h1>دسته‌های محصول</h1>
          <div class="sub">{fa(len(C.ORDER))} دسته‌ی فعال — قیمت هر روز کاری ساعت ۹ صبح بروزرسانی می‌شود</div></div>
        <a href="{u_price()}">جدول کامل {fa(TOTAL_SKUS)} کد کالا {icon('i-chev')}</a>
      </div>
      <div class="catgrid">{''.join(cards)}</div>
    </div>
  </section>
{callband()}
</main>
{footer()}{dock()}"""


def write(path, s):
    full = os.path.join(ROOT, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)   # مسیرها حالا تودرتواند
    with open(full, "w", encoding="utf-8") as f:
        f.write(s)

def main():
    n = 0
    write(out_path(u_home()), build_index()); n += 1
    write(out_path(u_price()), build_price()); n += 1
    write(out_path(u_catlist()), build_catlist()); n += 1
    for key in C.ORDER:
        write(out_path(u_cat(key)), build_category(key)); n += 1
        for i, row in enumerate(CAT[key]["rows"]):
            write(out_path(u_prod(key, row["نام محصول"])), build_product(key, row, i)); n += 1
    print(f"ساخته شد: {n} صفحه")

if __name__ == "__main__":
    main()
