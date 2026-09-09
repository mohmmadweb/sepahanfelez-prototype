# -*- coding: utf-8 -*-
"""
مجله‌ی سپاهان فلز — مقالات واقعی سایت زنده (sepahanfelez.ir/blog).

داده از build/articles.json می‌آید: ۳۱ مقاله در ۶ دسته، برداشته‌شده از
سایت فعلی و پاک‌سازی‌شده. نشانی‌ها همان قرارداد بک‌اند است:
  /blog · /blog/{category} · /blog/{category}/{article}

ربط مقاله ↔ محصول با کلیدواژه ساخته می‌شود. در بک‌اند همین کار را جدول
taggables (برچسب مشترک دسته و مقاله) انجام می‌دهد.
"""
import json, os, re, datetime
import content as C
from common import (ROOT, esc, fa, fmt, icon, jalali_str, cat_stats, cat_photo,
                    u_blog, u_blogcat, u_article, u_cat, u_price, PH, PHS, TODAY_ISO)

ARTS = json.load(open(os.path.join(ROOT, "build", "articles.json"), encoding="utf-8"))

CAT_TITLES = {
    "Wires": "سیم و مفتول", "net": "توری", "barbed-wire": "سیم خاردار",
    "توری-مش": "توری مش", "میلگرد": "میلگرد", "پایه-فنس": "پایه فنس",
}


def cat_title(slug):
    return CAT_TITLES.get(slug, slug)


def _date(a):
    d = a.get("published") or a.get("modified")
    if not d:
        return None
    try:
        return datetime.date.fromisoformat(d[:10])
    except ValueError:
        return None


def date_str(a):
    d = _date(a)
    return jalali_str(d) if d else ""


def date_iso(a):
    d = _date(a)
    return d.isoformat() if d else ""


def _sort_key(a):
    d = a.get("published")
    try:
        return datetime.date.fromisoformat(d[:10]) if d else datetime.date(2022, 1, 1)
    except ValueError:
        return datetime.date(2022, 1, 1)


ARTS_SORTED = sorted(ARTS, key=_sort_key, reverse=True)

_counts = {}
for _a in ARTS:
    _counts[_a["cat_slug"]] = _counts.get(_a["cat_slug"], 0) + 1
BLOG_CATS = sorted(_counts, key=lambda s: -_counts[s])

# ---------------------------------------------------------------------------
# ربط مقاله ↔ دسته‌ی محصول
# ---------------------------------------------------------------------------
KEYWORDS = {
    "توری-حصاری":              [("توری حصاری", 5), ("فنس", 4), ("حصار", 2), ("پایه فنس", 3)],
    "توری-پرسی":               [("توری پرسی", 5), ("پرسی", 3)],
    "مش-جوشی-یا-مش-آهنی":      [("توری مش", 5), ("مش جوشی", 5), ("مش آهنی", 4), ("میلگرد", 3), ("توری آهنی", 2)],
    "توری-جوشی--گالوانیزه-رول": [("توری جوشی", 4), ("توری مش", 2), ("توری آهنی", 2), ("ریزبافت", 4)],
    "توری-مرغی":               [("توری مرغی", 5), ("مرغی", 3), ("قفس", 1)],
    "توری-گابیون":             [("گابیون", 5), ("سنگ", 1)],
    "سیم-خاردار":              [("سیم خاردار", 5), ("خاردار", 4), ("حلقوی", 2)],
    "توری-فرنگی":              [("توری فرنگی", 5), ("فرنگی", 3), ("توری باغی", 4), ("باغ", 1)],
    "سیم-سیاه-و-آرماتور-بندی": [("سیم سیاه", 5), ("آرماتور", 5), ("مفتول", 1), ("سیم مفتول", 2)],
}
# مقاله‌های عمومی مفتول به سه دسته‌ی پرفروش کارفرما ربط داده می‌شوند
FALLBACK = ["توری-حصاری", "سیم-خاردار", "توری-مرغی"]


def _score(a, key):
    hay = " ".join([a["title"], a.get("description", ""), " ".join(a.get("tags", [])),
                    a.get("cat_title", "")]).replace("‌", " ")
    return sum(w for kw, w in KEYWORDS[key] if kw in hay)


def related_products(a, n=2):
    scored = sorted(((_score(a, k), i, k) for i, k in enumerate(C.ORDER)),
                    key=lambda t: (-t[0], t[1]))
    keys = [k for s, _, k in scored if s >= 2][:n]
    return keys or FALLBACK[:n]


def related_articles(key, n=3):
    scored = [( _score(a, key), a) for a in ARTS_SORTED]
    scored = [t for t in scored if t[0] >= 2]
    scored.sort(key=lambda t: (-t[0], -_sort_key(t[1]).toordinal()))
    out = [a for _, a in scored[:n]]
    if len(out) < n:
        for a in ARTS_SORTED:
            if a not in out:
                out.append(a)
            if len(out) >= n:
                break
    return out


def same_cat_articles(a, n=3):
    out = [x for x in ARTS_SORTED if x["cat_slug"] == a["cat_slug"] and x["slug"] != a["slug"]][:n]
    if len(out) < n:
        out += [x for x in ARTS_SORTED if x not in out and x["slug"] != a["slug"]][:n - len(out)]
    return out


# ---------------------------------------------------------------------------
# قطعات
# ---------------------------------------------------------------------------
PLACEHOLDER = ("data:image/svg+xml;utf8," 
               "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 9'><rect width='16' height='9' fill='%23E9EFF6'/></svg>")


def _img(a, cls=""):
    # مقاله‌ی بی‌عکس: عکس واقعی دستهٔ مرتبط، نه جای خالی
    src = a.get("image") or cat_photo(related_products(a)[0], 0) or PLACEHOLDER
    return f'<img src="{esc(src)}" alt="{esc(a["title"])}" loading="lazy" decoding="async"{(" class=%s" % cls) if cls else ""}>'


def card(a, size="md"):
    """کارت مقاله — سه اندازه: lg (شاخص)، md (شبکه)، sm (فهرست کناری)."""
    href = u_article(a["cat_slug"], a["slug"])
    meta = (f'<span class="mag-meta">{icon("i-calendar")}{esc(date_str(a)) or "—"}'
            f'<i>·</i>{icon("i-clock")}{fa(a["read_min"])} دقیقه مطالعه</span>')
    chip = f'<a class="chip" href="{u_blogcat(a["cat_slug"])}">{esc(cat_title(a["cat_slug"]))}</a>'
    if size == "lg":
        return f"""<article class="mag-feature">
  <a class="mag-img" href="{href}">{_img(a)}</a>
  <div class="mag-txt">{chip}<h2><a href="{href}">{esc(a['title'])}</a></h2>
    <p>{esc(a['description'][:170])}{'…' if len(a['description']) > 170 else ''}</p>{meta}</div>
</article>"""
    if size == "sm":
        return f"""<article class="mag-sm">
  <a class="mag-img" href="{href}">{_img(a)}</a>
  <div class="mag-txt">{chip}<h3><a href="{href}">{esc(a['title'])}</a></h3>{meta}</div>
</article>"""
    return f"""<article class="mag-card">
  <a class="mag-img" href="{href}">{_img(a)}</a>
  <div class="mag-txt">{chip}<h3><a href="{href}">{esc(a['title'])}</a></h3>
    <p>{esc(a['description'][:120])}{'…' if len(a['description']) > 120 else ''}</p>{meta}</div>
</article>"""


def chips(current=None):
    items = [f'<a class="chip{" is-on" if current is None else ""}" href="{u_blog()}">همه‌ی مطالب</a>']
    for s in BLOG_CATS:
        items.append(f'<a class="chip{" is-on" if current == s else ""}" href="{u_blogcat(s)}">'
                     f'{esc(cat_title(s))} <span class="num">({fa(_counts[s])})</span></a>')
    return f'<nav class="mag-chips" aria-label="دسته‌های مجله">{"".join(items)}</nav>'


def home_section(n=4):
    """بخش مجله در صفحه‌ی اصلی: یک شاخص + سه کارت."""
    arts = ARTS_SORTED[:n]
    feature = card(arts[0], "lg")
    rest = "".join(card(a, "sm") for a in arts[1:n])
    return f"""
  <section class="section alt mag-home" aria-labelledby="mag-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="mag-h">مجله سپاهان فلز</h2>
          <div class="sub">راهنمای خرید، مقایسه‌ی محصولات و نکات فنی صنایع مفتولی — {fa(len(ARTS))} مقاله</div></div>
        <a href="{u_blog()}">مشاهده همه‌ی مطالب {icon('i-chev')}</a>
      </div>
      <div class="mag-home-grid">{feature}<div class="mag-home-side">{rest}</div></div>
    </div>
  </section>"""


def related_section(key, n=3, title=None):
    """بخش «مقالات مرتبط» زیر محصول و دسته."""
    arts = related_articles(key, n)
    if not arts:
        return ""
    t = title or f"مقالات مرتبط با {C.CATS[key]['title']}"
    return f"""
  <section class="section mag-related" aria-labelledby="rel-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="rel-h">{esc(t)}</h2><div class="sub">از مجله‌ی سپاهان فلز</div></div>
        <a href="{u_blog()}">همه‌ی مقالات {icon('i-chev')}</a>
      </div>
      <div class="mag-grid mag-grid-3">{''.join(card(a) for a in arts)}</div>
    </div>
  </section>"""


def product_card(key):
    """کارت «محصول مرتبط» در صفحه‌ی مقاله — دسته با بازه‌ی قیمت."""
    c, s = C.CATS[key], cat_stats(key)
    img = cat_photo(key, 0, thumb=True)
    im = f'<img src="{esc(img)}" alt="{esc(c["title"])}" loading="lazy">' if img else ""
    rng = (f'از <b class="num">{fmt(s["min"])}</b> تا <b class="num">{fmt(s["max"])}</b> ریال'
           if s["min"] != s["max"] else f'<b class="num">{fmt(s["min"])}</b> ریال')
    return f"""<a class="relprod" href="{u_cat(key)}">
  <span class="relprod-img">{im}</span>
  <span class="relprod-txt"><b>{esc(c['title'])}</b><span>{fa(s['n'])} نوع کالا · هر {esc(s['unit'])}</span><span class="relprod-pr">{rng}</span></span>
  {icon('i-chev')}
</a>"""


def _toc(body):
    """فهرست مطلب از h2های بدنه؛ به هر h2 یک id می‌دهد."""
    items, out, i = [], [], 0
    def repl(m):
        nonlocal i
        i += 1
        text = re.sub(r"<[^>]+>", "", m.group(1)).strip()
        items.append((f"h-{i}", text))
        return f'<h2 id="h-{i}">{m.group(1)}</h2>'
    body = re.sub(r"<h2>(.*?)</h2>", repl, body, flags=re.S)
    if len(items) < 2:
        return body, ""
    lis = "".join(f'<li><a href="#{i}">{esc(t)}</a></li>' for i, t in items)
    return body, f'<nav class="toc" aria-label="فهرست مطلب"><h3>{icon("i-list")} در این مقاله</h3><ol>{lis}</ol></nav>'


# ---------------------------------------------------------------------------
# صفحات
# ---------------------------------------------------------------------------
def build_blog_index():
    from common import page_shell, callband
    arts = ARTS_SORTED
    feature = card(arts[0], "lg")
    side = "".join(card(a, "sm") for a in arts[1:4])
    grid = "".join(card(a) for a in arts[4:])
    body = f"""
  <section class="section mag-top">
    <div class="container">
      <div class="mag-head">
        <div><h1>مجله سپاهان فلز</h1>
          <p class="lede">راهنمای خرید، مقایسه و نکات فنی توری، مفتول و سیم خاردار — نوشته‌ی کارشناسان کارخانه‌ی صنایع مفتولی طلوع سپاهان.</p></div>
        {chips()}
      </div>
      <div class="mag-hero">{feature}<div class="mag-hero-side">{side}</div></div>
    </div>
  </section>
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>تازه‌ترین مطالب</h2><div class="sub">{fa(len(arts))} مقاله در {fa(len(BLOG_CATS))} دسته</div></div></div>
      <div class="mag-grid">{grid}</div>
    </div>
  </section>
{callband()}"""
    return page_shell("مجله سپاهان فلز — مقالات تخصصی توری، مفتول و سیم خاردار",
                      "راهنماها و مقالات فنی سپاهان فلز درباره‌ی انتخاب، کاربرد و قیمت انواع توری، مفتول و سیم خاردار.",
                      "blog", body, crumbs=[("خانه", "/"), ("مجله", "#")])


def build_blog_category(slug):
    from common import page_shell, callband
    arts = [a for a in ARTS_SORTED if a["cat_slug"] == slug]
    grid = "".join(card(a) for a in arts)
    body = f"""
  <section class="section">
    <div class="container">
      <div class="mag-head">
        <div><h1>مقالات {esc(cat_title(slug))}</h1>
          <p class="lede">{fa(len(arts))} مقاله در این دسته از مجله‌ی سپاهان فلز.</p></div>
        {chips(slug)}
      </div>
      <div class="mag-grid">{grid}</div>
    </div>
  </section>
{callband()}"""
    return page_shell(f"مقالات {cat_title(slug)} | مجله سپاهان فلز",
                      f"همه‌ی مقالات دسته‌ی {cat_title(slug)} در مجله‌ی سپاهان فلز.",
                      "blog", body, crumbs=[("خانه", "/"), ("مجله", u_blog()), (cat_title(slug), "#")])


def build_article(a):
    from common import page_shell, callband
    body_html, toc = _toc(a["body"])
    prods = "".join(product_card(k) for k in related_products(a))
    rel = "".join(card(x) for x in same_cat_articles(a))
    tags = ("".join(f'<span class="tag">{icon("i-tag")}{esc(t)}</span>' for t in a["tags"])
            if a.get("tags") else "")
    hero_src = a.get("image") or cat_photo(related_products(a)[0], 0)
    hero = (f'<figure class="post-hero"><img src="{esc(hero_src)}" alt="{esc(a["title"])}" decoding="async"></figure>'
            if hero_src else "")
    d = date_str(a)
    body = f"""
  <article class="post">
    <header class="post-head">
      <div class="container narrow">
        <a class="chip" href="{u_blogcat(a['cat_slug'])}">{esc(cat_title(a['cat_slug']))}</a>
        <h1>{esc(a['title'])}</h1>
        <p class="post-lede">{esc(a['description'])}</p>
        <div class="post-meta">
          <span>{icon('i-user')} کارشناسان سپاهان فلز</span>
          {f'<span>{icon("i-calendar")}<time datetime="{date_iso(a)}">{esc(d)}</time></span>' if d else ''}
          <span>{icon('i-clock')} {fa(a['read_min'])} دقیقه مطالعه</span>
        </div>
      </div>
    </header>
    <div class="container narrow">{hero}</div>
    <div class="container post-grid">
      <div class="post-body article-body">{body_html}
        <div class="post-tags">{tags}</div>
      </div>
      <aside class="post-side">
        {toc}
        <div class="side-box"><h3>{icon('i-box')} محصول مرتبط</h3>{prods}
          <a class="side-more" href="{u_price()}">جدول کامل قیمت لحظه‌ای {icon('i-chev')}</a></div>
        <a class="side-call" href="tel:{PH}" data-track="call-article">
          {icon('i-phone')}<span><span class="l">مشاوره و استعلام قیمت</span><span class="n num">{PHS}</span></span></a>
      </aside>
    </div>
  </article>
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>مقالات مرتبط</h2><div class="sub">از دسته‌ی {esc(cat_title(a['cat_slug']))}</div></div>
        <a href="{u_blog()}">همه‌ی مقالات {icon('i-chev')}</a></div>
      <div class="mag-grid mag-grid-3">{rel}</div>
    </div>
  </section>
{callband()}"""
    return page_shell(f"{a['title']} | مجله سپاهان فلز", a["description"] or a["title"], "blog", body,
                      crumbs=[("خانه", "/"), ("مجله", u_blog()),
                              (cat_title(a["cat_slug"]), u_blogcat(a["cat_slug"])), (a["title"], "#")])
