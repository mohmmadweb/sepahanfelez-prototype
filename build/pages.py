# -*- coding: utf-8 -*-
"""صفحات سایت: اصلی، قیمت لحظه‌ای، دسته، محصول، فهرست دسته‌ها، درباره، تماس."""
import content as C
import analysis as A
import blog as B
import features as F
import schema as SC
from common import (CAT, PH, PHS, WA, WAS, TOTAL_SKUS, N_CATS, UPDATE_TIME, TODAY, TODAY_ISO,
                    esc, fa, fmt, icon, clean_name, clean_val, price_of, delta_of, unit_of,
                    slugify, delta_badge, cat_stats, cat_photo, stamp, page_shell, callband,
                    head, UTILBAR, masthead, mainnav, footer, dock, crumb,
                    u_home, u_price, u_catlist, u_about, u_contact, u_blog, u_cat, u_prod)
from tables import price_table, changes_table, spec_table, sibling_table, key_specs, SHORT_HEAD

TEHRAN_MAP = "https://maps.app.goo.gl/mVbRUQug9CN9P7wL7"

SABAD = "کامل‌ترین سبد کالایی صنایع مفتولی کشور"


# ---------------------------------------------------------------------------
# بنر اسلایدری صفحه‌ی اصلی
# ---------------------------------------------------------------------------
def slide_bg(img):
    import os
    from common import ROOT
    if not img:
        return ""
    stem, _, ext = img.rpartition(".")
    stem = stem or img
    base = os.path.join(ROOT, "assets", "slides")
    have = lambda f: os.path.exists(os.path.join(base, f))
    jpg = f"{stem}.jpg" if have(f"{stem}.jpg") else img
    parts = []
    for suffix, dens in ((f"{stem}.webp", "1x"), (f"{stem}@2x.webp", "2x")):
        if have(suffix):
            parts.append(f'url(/assets/slides/{suffix}) type("image/webp") {dens}')
    for suffix, dens in ((jpg, "1x"), (f"{stem}@2x.jpg", "2x")):
        if have(suffix):
            parts.append(f'url(/assets/slides/{suffix}) type("image/jpeg") {dens}')
    fallback = f"background-image:url(/assets/slides/{esc(jpg)})"
    if not parts:
        return f' style="{fallback}"'
    return f' style="{fallback};background-image:image-set({esc(", ".join(parts))})"'


def hero():
    slides, dots = [], []
    for i, sl in enumerate(C.SLIDES, 1):
        c = C.CATS.get(sl.get("cat"))
        href = u_cat(sl["cat"]) if c else u_price()
        bg = slide_bg(sl.get("img"))
        title = sl.get("title")
        if title:
            tag, endtag = ("h1", "h1") if i == 1 else ('p class="stitle"', "p")
            inner = (f'<div class="container"><div class="slide-in">'
                     f'<{tag}>{esc(title)}</{endtag}></div></div>')
        else:
            inner = (f'<a class="slide-link" href="{href}" '
                     f'aria-label="{esc(sl.get("alt") or "مشاهده قیمت‌ها")}"></a>')
        cls = "slide has-title" if title else "slide"
        label = esc(title or sl.get("alt") or f"اسلاید {i}")
        slides.append(f'<article class="{cls}" id="s{i}"{bg} '
                      f'aria-roledescription="اسلاید" aria-label="{label}">{inner}</article>')
        dots.append(f'<a href="#s{i}"><span class="vh">اسلاید {fa(i)}</span></a>')
    nav = (f'<nav class="hero-dots" aria-label="انتخاب اسلاید">{"".join(dots)}</nav>'
           if len(slides) > 1 else "")
    return f"""
<section class="hero" aria-label="معرفی محصولات" aria-roledescription="اسلایدر">
  <div class="hero-track">{''.join(slides)}</div>
  {nav}
</section>"""


# ---------------------------------------------------------------------------
# بخش کارخانه — سه واحد: دو کارخانه‌ی اصفهان و دفتر تهران
# ---------------------------------------------------------------------------
def plant(video, poster, alt, title, sub, link=None):
    cap_t = f'<a href="{esc(link)}" target="_blank" rel="noopener">{esc(title)} {icon("i-external")}</a>' if link else esc(title)
    return f"""<figure class="plant">
  <video controls preload="none" playsinline muted poster="{poster}" aria-label="ویدئوی هوایی {esc(title)}">
    <source src="{video}" type="video/mp4">
    <img src="{poster}" alt="{esc(alt)}">
  </video>
  <figcaption><b>{cap_t}</b><span>{esc(sub)}</span></figcaption>
</figure>"""


def plants():
    return f"""<div class="plants plants-3">
{plant("/assets/factory/toloue-sepahan-1.mp4", "/assets/factory/toloue-sepahan-1.jpg",
       "نمای هوایی کارخانه‌ی صنایع مفتولی طلوع سپاهان، واحد یک، شهرک صنعتی منتظریه نجف‌آباد",
       "کارخانه — واحد یک", "اصفهان، شهرک صنعتی منتظریه (ویلاشهر)، خیابان قادری، پلاک ۱۸۱")}
{plant("/assets/factory/toloue-sepahan-2.mp4", "/assets/factory/toloue-sepahan-2.jpg",
       "نمای هوایی کارخانه‌ی صنایع مفتولی طلوع سپاهان، واحد دو، شهرک صنعتی منتظریه نجف‌آباد",
       "کارخانه — واحد دو", "اصفهان، کمربندی نجف‌آباد، شهرک صنعتی منتظریه، خیابان ۱۰۱")}
{plant("/assets/factory/tehran-office.mp4", "/assets/factory/tehran-office.jpg",
       "نمای هوایی دفتر تهران سپاهان فلز در بازار آهن شادآباد، مجتمع پارس فلز",
       "دفتر تهران", "بازار آهن شادآباد، بلوار شهید قربانخوانی، مجتمع پارس فلز، پلاک ۹", TEHRAN_MAP)}
</div>"""


def factory_text():
    return f"""<div class="prose wide cols-2">
  <p><strong>سپاهان فلز</strong> فروشگاه اینترنتی <strong>{C.FACTORY['name']}</strong> است؛
     یعنی آنچه در این سایت سفارش می‌دهید از خط تولید همین کارخانه می‌آید و
     <strong>امکان خرید مستقیم از کارخانه و انبار تهران</strong> را دارید — نه از انبار واسطه.</p>
  <p>کارخانه در سال {C.FACTORY['year']} با شماره ثبت {C.FACTORY['reg']} و مجوز رسمی وزارت صنایع
     و معادن در استان اصفهان آغاز به کار کرد. امروز با {C.FACTORY['hall']} سالن تولید،
     {C.FACTORY['staff']} پرسنل تولید و ظرفیت سالانه‌ی {C.FACTORY['capacity']}،
     <strong>{SABAD}</strong> را عرضه می‌کند: {fa(TOTAL_SKUS)} نوع کالا در {fa(N_CATS)} دسته.</p>
  <p>چرخه‌ی تولید کامل و زیر یک سقف است: <strong>کشش مفتول، گالوانیزه و بافت هر سه در همین
     مجموعه انجام می‌شود</strong>. کیفیت مفتول اولیه، ضخامت پوشش روی و یکنواختی بافت — سه
     حلقه‌ای که در خرید از بازار قابل کنترل نیستند — اینجا کنترل می‌شوند.</p>
  <p>بخش ماشین‌سازی داخلی کارخانه بخشی از تجهیزات خط تولید را خودش طراحی و می‌سازد و همین،
     تولید سفارشی — عرض، چشمه و ضخامت مطابق نقشه‌ی پروژه — را ممکن می‌کند. دفتر فروش تهران
     در بازار آهن شادآباد، بار را از انبار تهران هم تحویل می‌دهد.</p>
</div>"""


# ---------------------------------------------------------------------------
# صفحه‌ی اصلی
# ---------------------------------------------------------------------------
def build_index():
    cards = []
    for key in C.ORDER:
        c, s = C.CATS[key], cat_stats(key)
        img = cat_photo(key, 0, thumb=True)
        im = f'<img src="{esc(img)}" alt="{esc(c["title"])}" loading="lazy" decoding="async">' if img else ""
        cards.append(f"""<a class="hc" href="{u_cat(key)}">
  <span class="hc-img">{im}</span>
  <span class="hc-t">{esc(c['title'])}</span>
  <span class="hc-n">{fa(s['n'])} نوع کالا · واحد: {esc(s['unit'])}</span>
  <span class="hc-go">مشاهده قیمت {icon('i-chev')}</span>
</a>""")
    facts = "".join(f'<div class="factnum"><div class="v">{v}</div><div class="k">{k}</div></div>'
                    for v, k in C.FACT_NUMBERS)
    trust = "".join(f'<div class="trustitem">{icon(ic)}<div><div class="t">{t}</div><div class="d">{d}</div></div></div>'
                    for ic, t, d in C.TRUST)
    body = f"""
{hero()}

  <section class="section home-cats" aria-labelledby="hc-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="hc-h">قیمت روز صنایع مفتولی طلوع سپاهان</h2>
          <div class="sub">{SABAD} — {fa(TOTAL_SKUS)} نوع کالا در {fa(N_CATS)} دسته · بروزرسانی هر روز ساعت {UPDATE_TIME}</div></div>
        <a class="btn btn-call btn-cta" href="{u_price()}">{icon('i-chart')} مشاهده قیمت لحظه‌ای</a>
      </div>
      <div class="homecats">{''.join(cards)}</div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="prose wide cols-2">
        {''.join(f'<p>{x}</p>' for x in C.HOME_INTRO)}
      </div>
    </div>
  </section>

  <section class="section alt" aria-labelledby="tr-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="tr-h">چرا خرید از طلوع سپاهان فرق دارد</h2>
          <div class="sub">{SABAD}، مستقیم از کارخانه</div></div>
      </div>
      <div class="trustgrid">{trust}</div>
      <div class="factnums">{facts}</div>
    </div>
  </section>

  <section class="section" id="factory" aria-labelledby="fa-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="fa-h">کارخانه‌ی صنایع مفتولی طلوع سپاهان</h2>
          <div class="sub">دو واحد تولیدی در شهرک صنعتی منتظریه‌ی اصفهان و دفتر فروش در بازار آهن تهران</div></div>
        <a href="{u_about()}">درباره کارخانه {icon('i-chev')}</a>
      </div>
      {plants()}
      {factory_text()}
    </div>
  </section>

{B.home_section()}
{callband()}"""
    ld = SC.graph(
        SC.organization(C), SC.website(),
        {"@type": "WebPage", "@id": SC.SITE + "/#page", "url": SC.SITE + "/",
         "name": "سپاهان فلز — قیمت روز صنایع مفتولی طلوع سپاهان",
         "inLanguage": "fa-IR", "isPartOf": {"@id": SC.SITE_ID},
         "about": {"@id": SC.ORG_ID}})
    return page_shell(
        "سپاهان فلز — قیمت روز صنایع مفتولی طلوع سپاهان",
        f"قیمت روز توری حصاری، پرسی، مش جوشی، مرغی، گابیون و سیم خاردار مستقیم از کارخانه. {fa(TOTAL_SKUS)} نوع کالا، بروزرسانی هر روز ساعت {UPDATE_TIME}.",
        None, body, canonical="/", jsonld=ld)


# ---------------------------------------------------------------------------
# قیمت لحظه‌ای
# ---------------------------------------------------------------------------
def build_price():
    tables = "".join(price_table(key, search=False) for key in C.ORDER)
    jump = "".join(
        f'<li><a href="#pt-{C.CATS[k]["slug"]}">{esc(C.CATS[k]["title"])}<span class="num">{fa(cat_stats(k)["n"])}</span></a></li>'
        for k in C.ORDER)
    chips = "".join(f'<a class="chip" href="#pt-{C.CATS[k]["slug"]}">{esc(C.CATS[k]["nav"])}</a>' for k in C.ORDER)
    body = f"""
  <section class="board board-price">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>قیمت لحظه‌ای<span>صنایع مفتولی طلوع سپاهان</span></h1>
          <p class="lede">{fa(TOTAL_SKUS)} نوع کالا در {fa(N_CATS)} دسته با مشخصات فنی. قیمت به ریال، مبنای روز درب کارخانه؛
             هر روز ساعت {UPDATE_TIME} بروزرسانی می‌شود.</p>
        </div>
        {stamp()}
      </div>
      <div class="price-tools">
        <label class="gsearch">{icon('i-search')}<span class="vh">جست‌وجو در همه‌ی جدول‌ها</span>
          <input type="search" data-global-search placeholder="جست‌وجوی نام کالا در همه‌ی جدول‌ها — مثلاً: چشمه ۵/۵ یا مفتول ۳" autocomplete="off">
          <output data-global-count aria-live="polite"></output></label>
        <div class="chiprow">{chips}</div>
      </div>
    </div>
  </section>

  <section class="section price-section">
    <div class="container price-full">
        {changes_table(10)}
        {tables}
        <p class="tnote">ستون «نوسان» تغییر نسبت به آخرین قیمت ثبت‌شده است. آیکون نمودار در هر ردیف، روند قیمت همان کالا را باز می‌کند. قیمت قطعی سفارش به تناژ و مقصد بار بستگی دارد و در تماس اعلام می‌شود.</p>
    </div>
  </section>
{F.sales_unit_section()}
{callband()}"""
    ld = SC.graph(
        SC.organization(C), SC.website(),
        {"@type": "CollectionPage", "@id": SC.SITE + "/price#page",
         "url": SC.SITE + "/price", "name": f"قیمت لحظه‌ای {fa(TOTAL_SKUS)} نوع کالای مفتولی",
         "inLanguage": "fa-IR", "isPartOf": {"@id": SC.SITE_ID},
         "about": {"@id": SC.ORG_ID}},
        SC.breadcrumb([("خانه", "/"), ("قیمت لحظه‌ای", None)]))
    return page_shell(f"قیمت لحظه‌ای صنایع مفتولی — {fa(TOTAL_SKUS)} نوع کالا",
                      f"جدول قیمت لحظه‌ای {fa(TOTAL_SKUS)} نوع کالای مفتولی طلوع سپاهان در {fa(N_CATS)} دسته با مشخصات فنی و آخرین تغییرات قیمت. قیمت به ریال، بروزرسانی هر روز ساعت {UPDATE_TIME}.",
                      "price", body, crumbs=[("خانه", u_home()), ("قیمت لحظه‌ای", "#")],
                      canonical="/price", jsonld=ld)


# ---------------------------------------------------------------------------
# فهرست دسته‌ها
# ---------------------------------------------------------------------------
def build_catlist():
    cards = []
    for key in C.ORDER:
        c, st = C.CATS[key], cat_stats(key)
        img = cat_photo(key, 0, thumb=True)
        im = f'<img src="{esc(img)}" alt="{esc(c["title"])}" loading="lazy">' if img else ""
        cards.append(f"""<a class="hc" href="{u_cat(key)}">
  <span class="hc-img">{im}</span>
  <span class="hc-t">{esc(c['title'])}</span>
  <span class="hc-n">{fa(st['n'])} نوع کالا · واحد: {esc(st['unit'])}</span>
  <span class="hc-pr">از <b class="num">{fmt(st['min'])}</b> تا <b class="num">{fmt(st['max'])}</b> ریال</span>
  <span class="hc-go">مشاهده قیمت {icon('i-chev')}</span>
</a>""")
    body = f"""
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h1>دسته‌های محصول</h1>
          <div class="sub">{fa(N_CATS)} دسته‌ی فعال — {SABAD}</div></div>
        <a href="{u_price()}">قیمت لحظه‌ای {fa(TOTAL_SKUS)} نوع کالا {icon('i-chev')}</a>
      </div>
      <div class="homecats">{''.join(cards)}</div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="prose wide cols-2">
        <h2>کدام دسته برای کار شما درست است</h2>
        {''.join(f'<p>{x}</p>' for x in C.CATLIST_INTRO)}
      </div>
      <div class="guides">
        {''.join(f'<details class="guide"><summary>{esc(t)}</summary><div class="prose"><p>{d}</p></div></details>' for t, d in C.CATLIST_HELP)}
      </div>
    </div>
  </section>
{callband()}"""
    ld = SC.graph(
        SC.organization(C), SC.website(),
        {"@type": "CollectionPage", "@id": SC.SITE + "/category#page",
         "url": SC.SITE + "/category", "name": "همه‌ی دسته‌های محصول",
         "inLanguage": "fa-IR", "isPartOf": {"@id": SC.SITE_ID}},
        {"@type": "ItemList", "name": "دسته‌های صنایع مفتولی طلوع سپاهان",
         "numberOfItems": len(C.ORDER),
         "itemListElement": [
            {"@type": "ListItem", "position": i+1, "name": C.CATS[k]["title"],
             "url": SC.SITE + u_cat(k)} for i, k in enumerate(C.ORDER)]},
        SC.breadcrumb([("خانه", "/"), ("دسته‌های محصول", None)]))
    return page_shell("همه‌ی دسته‌های محصول",
                      "فهرست کامل دسته‌های صنایع مفتولی طلوع سپاهان با بازه‌ی قیمت روز، واحد فروش و راهنمای انتخاب دسته بر پایه‌ی کاربرد، وزن و چشمه.",
                      None, body, crumbs=[("خانه", u_home()), ("دسته‌های محصول", "#")],
                      canonical="/category", jsonld=ld)


# ---------------------------------------------------------------------------
# صفحه‌ی دسته
# ---------------------------------------------------------------------------
def build_category(key):
    c, s = C.CATS[key], cat_stats(key)
    rows = CAT[key]["rows"]
    intro_lead = f"<p>{c['intro'][0]}</p>" if c["intro"] else ""
    intro_rest = "".join(f"<p>{x}</p>" for x in c["intro"][1:])
    pricing = "".join(f"<p>{p}</p>" for p in c["pricing"])
    choose = "".join(f"<h3>{esc(t)}</h3><p>{d}</p>" for t, d in c["choose"])
    mistakes = "".join(f"<li>{m}</li>" for m in c["mistakes"])
    faq = "".join(f"<details><summary>{esc(q)}</summary><div class=\"a\">{esc(a)}</div></details>"
                  for q, a in c["faq"])
    photos = "".join(
        f'<a class="ph" href="{esc(cat_photo(key, i))}" target="_blank" rel="noopener"><img src="{esc(cat_photo(key, i, thumb=True))}" alt="{esc(c["title"])} — تصویر {fa(i+1)}" loading="lazy"></a>'
        for i in range(3) if cat_photo(key, i))
    photos_html = f'<div class="board-photos">{photos}</div>' if photos else ""

    # بخش‌های عمیق: نصب، پوشش، محاسبه‌ی هزینه — موضوع‌هایی که رقبا دارند
    # و تا نسخه‌ی ۲۱ نداشتیم. هر بند روی عدد واقعی همین کاتالوگ نوشته شده.
    deep = C.DEEP.get(key, [])
    deep_sections = ""
    if deep:
        blocks = "".join(
            f'<div class="deep"><h2>{esc(t)}</h2>' + "".join(f"<p>{x}</p>" for x in ps) + "</div>"
            for t, ps in deep)
        deep_sections = f"""
  <section class="section" id="guide">
    <div class="container">
      <div class="section-head"><div><h2>راهنمای فنی {esc(c['title'])}</h2>
        <div class="sub">نصب، انتخاب پوشش و محاسبه‌ی هزینه — بر پایه‌ی مشخصات همین کاتالوگ</div></div></div>
      <div class="deepgrid">{blocks}</div>
    </div>
  </section>"""

    body = f"""
  <section class="board board-cat">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>{esc(c['h1'])}</h1>
          <p class="lede">{esc(c['lede'])}</p>
        </div>
        {stamp()}
      </div>
      <div class="board-grid">
        <div class="ixgrid ix-4">
          <div class="ix"><span class="k">نوع کالای فعال</span><span class="v"><span class="num">{fa(s['n'])}</span></span><span class="range">در این دسته</span></div>
          <div class="ix"><span class="k">کمترین قیمت</span><span class="v"><span class="num">{fmt(s['min'])}</span> <span class="u">ریال</span></span><span class="range">هر {esc(s['unit'])}</span></div>
          <div class="ix"><span class="k">بیشترین قیمت</span><span class="v"><span class="num">{fmt(s['max'])}</span> <span class="u">ریال</span></span><span class="range">هر {esc(s['unit'])}</span></div>
          <div class="ix"><span class="k">واحد فروش</span><span class="v">{esc(s['unit'])}</span><span class="range">{esc(c['unit_note'])}</span></div>
        </div>
        {photos_html}
      </div>
      <div class="board-call">
        <p class="say">برای {esc(c['title'])} با تناژ پروژه‌ای یا تولید سفارشی،
          <b>قیمت قطعی خود را از کارشناسان ما بگیرید.</b></p>
        <a class="tel" href="tel:{PH}" data-track="call-board">
          {icon('i-phone')}<span><span class="l">قیمت قطعی همین حالا، تلفنی</span><span class="n num">{PHS}</span></span></a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h2>جدول قیمت روز {esc(c['title'])}</h2>
          <div class="sub">{fa(s['n'])} نوع کالا با مشخصات فنی — قیمت به ریال، بروزرسانی هر روز ساعت {UPDATE_TIME}</div></div>
      </div>
      {price_table(key, search=True, title=False, guide_link=False)}
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>روند قیمت {esc(c['title'])}</h2><div class="sub">میانگین قیمت دسته؛ نمودار هر کالا با آیکون نمودار در جدول باز می‌شود</div></div></div>
      <div class="chart-with-rep">
        <div>{F.category_chart(key)}</div>
        <div>{F.experts_box(key)}</div>
      </div>
    </div>
  </section>
{F.calculator(key)}

  <section class="section alt">
    <div class="container">
      <div class="prose">
        <h2>{esc(c['title'])} چیست و کجا به کار می‌آید</h2>
        {intro_lead}
      </div>
      <div class="guides">
        <details class="guide" open><summary>{esc(c['title'])} — ادامه‌ی معرفی</summary><div class="prose">{intro_rest}</div></details>
        <details class="guide"><summary>{esc(c['pricing_title'])}</summary><div class="prose">{pricing}
            <div class="callout"><div class="t">قیمت‌ها به ریال است</div>
              <p>همه‌ی قیمت‌های این جدول به ریال است. اگر با سایتی که تومانی کار می‌کند مقایسه می‌فرمایید، به واحد توجه بفرمایید؛ عدد ریالی ده برابر عدد تومانی است.</p></div></div></details>
        <details class="guide"><summary>{esc(c['choose_title'])}</summary><div class="prose">{choose}</div></details>
        <details class="guide"><summary>چهار اشتباه رایج در خرید {esc(c['title'])}</summary><div class="prose"><ul class="bul">{mistakes}</ul></div></details>
      </div>
    </div>
  </section>

  {deep_sections}

  <section class="section">
    <div class="container">
      {spec_table(key)}
      <p class="tnote">این جدول مشخصات، داده‌ی خط تولید صنایع مفتولی طلوع سپاهان است. مقادیر وزن، مبنای محاسبه‌ی هزینه‌ی هر مترمربع یا هر برگ است و هنگام تحویل با باسکول قابل بررسی است.</p>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>پرسش‌های پرتکرار درباره‌ی {esc(c['title'])}</h2></div></div>
      <div class="faq">{faq}</div>
    </div>
  </section>
{F.reviews_block(key)}
{B.related_section(key)}
{callband()}"""
    # ── داده‌ی ساختاریافته ─────────────────────────────────────────────
    # CollectionPage + ItemList از کالاهای همین دسته + پرسش‌های متداولی که
    # روی خود صفحه دیده می‌شوند + مسیر راهنما.
    prods = []
    for r in rows[:30]:
        nm = clean_name(r["نام محصول"])
        prods.append(SC.product(
            name=nm, url=u_prod(key, r["نام محصول"]),
            desc=f"{nm} — تولید صنایع مفتولی طلوع سپاهان، قیمت روز درب کارخانه‌ی اصفهان.",
            price=price_of(r), unit_name=unit_of(r),
            image=cat_photo(key, 0) or None,
            props=[(k2, clean_val(r.get(k2))) for k2 in CAT[key].get("specs", [])][:6]))
    ld = SC.graph(
        SC.organization(C), SC.website(),
        {"@type": "CollectionPage",
         "@id": SC.SITE + u_cat(key) + "#page",
         "url": SC.SITE + u_cat(key),
         "name": f"قیمت روز {c['title']}",
         "description": c["meta"],
         "inLanguage": "fa-IR",
         "isPartOf": {"@id": SC.SITE_ID},
         "about": {"@id": SC.ORG_ID},
         "primaryImageOfPage": (SC.SITE + cat_photo(key, 0)) if cat_photo(key, 0) else None},
        SC.item_list(prods, f"کالاهای {c['title']}"),
        SC.faq(c["faq"]),
        SC.breadcrumb([("خانه", "/"), ("قیمت لحظه‌ای", "/price"), (c["title"], None)]))
    return page_shell(f"قیمت روز {c['title']} — طلوع سپاهان", c["meta"],
                      c["slug"], body,
                      crumbs=[("خانه", u_home()), ("قیمت لحظه‌ای", u_price()), (c["title"], "#")],
                      canonical=u_cat(key), jsonld=ld)


# ---------------------------------------------------------------------------
# صفحه‌ی محصول — خلوت و کاربردی
# ---------------------------------------------------------------------------
ANA = {"price_of": price_of, "slugify": slugify, "esc": esc, "fa": fa, "phone": PHS, "u_prod": None}


def build_product(key, row, idx):
    c = C.CATS[key]
    rows, specs = CAT[key]["rows"], CAT[key]["specs"]
    name = row["نام محصول"]
    dname = clean_name(name)
    p, d = price_of(row), delta_of(row)
    unit = unit_of(row)
    ctx = dict(ANA, u_prod=lambda nm, k=key: u_prod(k, nm))

    spec_rows = "".join(
        f'<div class="kv"><span class="k">{esc(x)}</span><span class="v">{esc(clean_val(row.get(x)))}</span></div>'
        for x in specs if x != "محل بارگیری" and str(row.get(x, "")).strip())
    flag = (f'<p class="callout slim"><b>قیمت در حال بازبینی است.</b> {esc(C.SUSPECT[name])} پیش از سفارش، قیمت را تلفنی بگیرید.</p>'
            if name in C.SUSPECT else "")

    # عکس‌های دسته — تا زمانی که عکس اختصاصی هر کالا ثبت شود
    main = cat_photo(key, 0)
    thumbs = "".join(
        f'<button type="button" class="thumb{" is-on" if i == 0 else ""}" data-src="{esc(cat_photo(key, i))}" aria-label="تصویر {fa(i+1)}"><img src="{esc(cat_photo(key, i, thumb=True))}" alt="" loading="lazy"></button>'
        for i in range(3) if cat_photo(key, i))
    gallery = (f'<div class="gallery"><div class="gallery-main"><img id="gmain" src="{esc(main)}" alt="{esc(c["title"])} — {esc(dname)}"></div>'
               f'<div class="gallery-thumbs">{thumbs}</div><p class="gallery-note">تصاویر نمونه‌ی محصولات این دسته از خط تولید طلوع سپاهان</p></div>'
               if main else "")

    notes = A.buying_notes(key, row, ctx)[:3]
    sib = A.sibling_notes(key, row, rows, specs, ctx)[:1]
    tips = "".join(f"<li>{x}</li>" for x in notes[:-1] + sib)   # بند تکراری قیمت حذف؛ در کارت قیمت هست
    faq = "".join(f"<details><summary>{esc(q)}</summary><div class=\"a\">{esc(a)}</div></details>"
                  for q, a in c["faq"][:3])
    prev_line = (f'<span class="pcard-prev">قیمت ثبت قبلی: <span class="num">{fmt(d)}</span> ریال</span>' if d else "")

    body = f"""
  <section class="section product">
    <div class="container">
      <div class="pgrid">
        {gallery}
        <div class="pinfo">
          <a class="chip" href="{u_cat(key)}">{esc(c['title'])}</a>
          <h1>{esc(dname)}</h1>
          <p class="plede">{esc(c['title'])} — تولید صنایع مفتولی طلوع سپاهان، امکان خرید مستقیم از کارخانه و انبار تهران.</p>
          <div class="pcard">
            <div class="pcard-price"><span class="k">قیمت روز</span>
              <span class="v"><b class="num">{fmt(p)}</b> <span class="u">ریال / {esc(unit)}</span></span>
              <span class="pcard-meta">{delta_badge(d, p)} نسبت به آخرین ثبت {prev_line}</span></div>
            <div class="pcard-upd">{stamp(short=True)} <span class="dim">· ساعت {UPDATE_TIME} هر روز</span></div>
            {flag}
            <div class="pcard-cta">
              <a class="btn btn-call btn-lg2" href="tel:{PH}" data-track="call-product">{icon('i-phone')} استعلام و ثبت سفارش <span class="num">{PHS}</span></a>
              <a class="btn btn-ghost btn-lg2" href="https://wa.me/{WA}" data-track="wa-product">{icon('i-whatsapp')} واتساپ</a>
            </div>
            <p class="pcard-note">قیمت جدول مبنای روز است؛ قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود.</p>
          </div>
          <div class="kvgrid">
            <div class="kv"><span class="k">دسته</span><span class="v"><a href="{u_cat(key)}">{esc(c['title'])}</a></span></div>
            <div class="kv"><span class="k">واحد فروش</span><span class="v">{esc(unit)}</span></div>
            {spec_rows}
            <div class="kv"><span class="k">تحویل</span><span class="v">کارخانه‌ی اصفهان / انبار تهران</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="prose wide cols-2">
        <h2>درباره‌ی {esc(dname)}</h2>
        {F.product_intro(key, row, rows)}
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container two-col">
      <div>{F.product_chart(key, row)}</div>
      <div>{F.experts_box(key, title="کارشناس فروش این محصول")}</div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      {A.price_block(key, row, rows, ctx)}
    </div>
  </section>
{F.calculator(key, row)}

  <section class="section">
    <div class="container two-col">
      <div>
        <div class="section-head"><div><h2>نکات خرید این محصول</h2><div class="sub">از مشخصات همین کالا</div></div></div>
        <ul class="tips">{tips}</ul>
        <p><a class="guidelink" href="{u_cat(key)}">راهنمای کامل خرید {esc(c['title'])} {icon('i-chev')}</a></p>
      </div>
      <div>
        <div class="section-head"><div><h2>محصولات هم‌دسته</h2><div class="sub">{fa(len(rows) - 1)} نوع کالای دیگر در {esc(c['title'])}</div></div></div>
        {sibling_table(key, row)}
        <p><a class="guidelink" href="{u_cat(key)}">جدول کامل {esc(c['title'])} {icon('i-chev')}</a></p>
      </div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>پرسش‌های پرتکرار</h2></div></div>
      <div class="faq">{faq}</div>
    </div>
  </section>
{F.reviews_block(key, subject=dname)}
{B.related_section(key, title=f"مقالات مرتبط با {c['title']}")}
{callband()}"""
    # ── داده‌ی ساختاریافته‌ی محصول ─────────────────────────────────────
    # مهم‌ترین بلوک کل سایت: همین است که قیمت را زیر نتیجه‌ی گوگل می‌آورد.
    specs = [(k2, clean_val(row.get(k2))) for k2 in CAT[key].get("specs", [])]
    prod_url = u_prod(key, row["نام محصول"])
    ld = SC.graph(
        SC.organization(C), SC.website(),
        SC.product(
            name=dname, url=prod_url,
            desc=(f"{dname} از تولیدات صنایع مفتولی طلوع سپاهان. "
                  f"قیمت روز {fmt(p)} ریال بر {unit}، درب کارخانه‌ی اصفهان، "
                  f"بروزرسانی هر روز ساعت {UPDATE_TIME}."),
            price=p, unit_name=unit,
            image=cat_photo(key, 0) or None,
            props=specs),
        SC.faq(c["faq"][:3]),
        SC.breadcrumb([("خانه", "/"), ("قیمت لحظه‌ای", "/price"),
                       (c["title"], u_cat(key)), (dname, None)]))
    return page_shell(f"قیمت {dname}",
                      f"قیمت روز {dname}: {fmt(p)} ریال / {unit} — تولید طلوع سپاهان، با مشخصات فنی و بروزرسانی روزانه.",
                      c["slug"], body,
                      crumbs=[("خانه", u_home()), ("قیمت لحظه‌ای", u_price()), (c["title"], u_cat(key)), (dname, "#")],
                      canonical=prod_url, jsonld=ld)


# ---------------------------------------------------------------------------
# درباره و تماس
# ---------------------------------------------------------------------------
def build_about():
    body = f"""
  <section class="section">
    <div class="container">
      <div class="prose prose-lead">
        <h1>کارخانه‌ی صنایع مفتولی طلوع سپاهان</h1>
        <p class="lede"><strong>سپاهان فلز</strong> فروشگاه اینترنتی <strong>{C.FACTORY['name']}</strong> است —
           <strong>{SABAD}</strong>، با {fa(TOTAL_SKUS)} نوع کالا در {fa(N_CATS)} دسته، تولید کارخانه‌ی اصفهان و امکان خرید مستقیم از کارخانه و انبار تهران.</p>
      </div>
      {plants()}
      <div class="factnums">
        <div class="fact"><span class="k">سال تأسیس</span><span class="v">{C.FACTORY['year']}</span></div>
        <div class="fact"><span class="k">شماره ثبت</span><span class="v">{C.FACTORY['reg']}</span></div>
        <div class="fact"><span class="k">ظرفیت سالانه</span><span class="v">{C.FACTORY['capacity']}</span></div>
        <div class="fact"><span class="k">سالن تولید</span><span class="v">{C.FACTORY['hall']}</span></div>
        <div class="fact"><span class="k">پرسنل تولید</span><span class="v">{C.FACTORY['staff']}</span></div>
        <div class="fact"><span class="k">نوع کالای فعال</span><span class="v">{fa(TOTAL_SKUS)}</span></div>
      </div>
      {factory_text()}
      <div class="prose wide">
        <h2>چه چیزی تولید می‌شود</h2>
        <p>{fa(TOTAL_SKUS)} نوع کالای فعال در {fa(N_CATS)} دسته. قیمت روز همه‌شان در <a href="{u_price()}">قیمت لحظه‌ای</a> هست و هر روز ساعت {UPDATE_TIME} بروزرسانی می‌شود.</p>
        <ul class="bul cols-3">{''.join(f'<li><a href="{u_cat(k)}">{esc(C.CATS[k]["title"])}</a> — {fa(cat_stats(k)["n"])} نوع کالا، {esc(cat_stats(k)["unit"])}</li>' for k in C.ORDER)}</ul>
      </div>
      <div class="prose wide cols-2">
        {''.join(f'<h2>{esc(t)}</h2>' + ''.join(f'<p>{x}</p>' for x in ps) for t, ps in C.ABOUT_SECTIONS)}
      </div>
    </div>
  </section>
{F.sales_unit_section()}
{callband()}"""
    ld = SC.graph(
        SC.organization(C), SC.website(),
        {"@type": "AboutPage", "@id": SC.SITE + "/about#page",
         "url": SC.SITE + "/about", "name": "درباره کارخانه‌ی طلوع سپاهان",
         "inLanguage": "fa-IR", "isPartOf": {"@id": SC.SITE_ID},
         "mainEntity": {"@id": SC.ORG_ID}},
        SC.breadcrumb([("خانه", "/"), ("درباره کارخانه", None)]))
    return page_shell("درباره کارخانه‌ی طلوع سپاهان",
                      f"صنایع مفتولی طلوع سپاهان، {SABAD}: تولیدکننده‌ی توری و محصولات مفتولی در شهرک صنعتی منتظریه‌ی اصفهان با دفتر فروش در بازار آهن تهران.",
                      "about", body, crumbs=[("خانه", u_home()), ("درباره کارخانه", "#")],
                      canonical="/about", jsonld=ld)


def build_contact():
    addr = "".join(f'<li><span class="a-t">{esc(t)}</span>{esc(a)}</li>' for t, a in C.ADDRESSES)
    soc = "".join(
        f'<a class="soc-row" href="{u}" rel="noopener" target="_blank">'
        f'{icon(i)}<span>{esc(n)}</span><b class="num">{C.MOBILE_SHOW}</b></a>'
        for i, n, u in C.SOCIALS)
    body = f"""
  <section class="section">
    <div class="container">
      <div class="prose prose-lead">
        <h1>تماس با دفتر فروش</h1>
        <p class="lede">قیمت جدول مبنای روز است. <strong>قیمت قطعی خود را از کارشناسان ما بگیرید</strong>؛
           به تناژ و مقصد بار بستگی دارد و در همان تماس اعلام می‌شود.</p>
      </div>
      <div class="contact-grid">
        <div class="contact-main">
          <a class="contact-tel" href="tel:{PH}" data-track="call-contact">
            {icon('i-phone')}
            <span><span class="l">دفتر فروش — {C.PHONE_LINES}</span><span class="n num">{PHS}</span>
              <span class="h">شنبه تا چهارشنبه ۸ تا ۱۷ · پنجشنبه ۸ تا ۱۳</span></span>
          </a>
          <div class="soc-list">{soc}</div>
          <a class="fmail" href="mailto:info@sepahanfelez.ir">{icon('i-mail')}info@sepahanfelez.ir</a>
        </div>
        <div class="contact-addr">
          <h2>کارخانه و دفتر فروش</h2>
          <ul class="faddr">{addr}</ul>
          <a class="maplink" href="{TEHRAN_MAP}" target="_blank" rel="noopener">
            <img src="/assets/factory/tehran-office.jpg" alt="نمای هوایی دفتر تهران در بازار آهن شادآباد" loading="lazy">
            <span>{icon('i-map')} دفتر تهران روی نقشه‌ی گوگل {icon('i-external')}</span></a>
        </div>
      </div>
      <div class="section-head"><div><h2>واحد فروش</h2><div class="sub">شماره‌ی دفتر را بگیرید و داخلی کارشناس مربوط به محصول خود را وارد کنید</div></div></div>
      <div class="unit-rep unit-rep-wide">{F.rep_card(F.rep_for(None))}</div>
      <div class="prose wide cols-2">
        <h2>راهنمای تماس</h2>
        {''.join(f'<h3>{esc(t)}</h3><p>{esc(d)}</p>' for t, d in C.CONTACT_HELP)}
      </div>
      <div class="prose">
        <h2>پیام بفرستید</h2>
        <p>در صورت مراجعه خارج از ساعات اداری، مشخصات و تناژ موردنیاز خود را ثبت بفرمایید؛ در نخستین فرصت اداری با شما تماس گرفته خواهد شد.</p>
      </div>
      <form class="cform" method="post" action="{u_contact()}">
        <p class="cform-note">{icon('i-clock')} در این نسخه‌ی نمایشی، فرم غیرفعال است. جهت دریافت پاسخ فوری با شماره <b class="num">{PHS}</b> تماس حاصل فرمایید.</p>
        <div class="cform-grid">
          <label class="ffield"><span>نام</span><input name="first_name" type="text" minlength="2" maxlength="25" required disabled></label>
          <label class="ffield"><span>نام خانوادگی</span><input name="last_name" type="text" minlength="2" maxlength="25" required disabled></label>
          <label class="ffield"><span>شماره تماس</span><input name="phone" type="tel" inputmode="numeric" maxlength="15" required disabled></label>
          <label class="ffield"><span>ایمیل</span><input name="email" type="email" maxlength="60" required disabled></label>
          <label class="ffield fsearch"><span>موضوع</span><input name="subject" type="text" minlength="2" maxlength="150" required disabled></label>
          <label class="ffield fsearch"><span>متن پیام</span><textarea name="body" rows="4" minlength="10" maxlength="500" required disabled></textarea></label>
        </div>
        <button class="btn btn-call" type="submit" disabled>ارسال پیام</button>
      </form>
    </div>
  </section>
{callband()}"""
    ld = SC.graph(
        SC.organization(C), SC.website(),
        {"@type": "ContactPage", "@id": SC.SITE + "/contact#page",
         "url": SC.SITE + "/contact", "name": "تماس با سپاهان فلز",
         "inLanguage": "fa-IR", "isPartOf": {"@id": SC.SITE_ID},
         "mainEntity": {"@id": SC.ORG_ID}},
        SC.breadcrumb([("خانه", "/"), ("تماس با ما", None)]))
    return page_shell("تماس با سپاهان فلز — دفتر فروش کارخانه",
                      f"تماس با دفتر فروش صنایع مفتولی طلوع سپاهان: {C.PHONE_SHOW} با {C.PHONE_LINES}. نشانی دو واحد کارخانه در شهرک صنعتی منتظریه‌ی اصفهان و دفتر تهران در بازار آهن شادآباد.",
                      "contact", body, crumbs=[("خانه", u_home()), ("تماس با ما", "#")], show_addr=False,
                      canonical="/contact", jsonld=ld)
