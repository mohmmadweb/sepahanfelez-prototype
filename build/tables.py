# -*- coding: utf-8 -*-
"""
جدول قیمت فشرده.

الگو از آهن آنلاین: هر دسته یک کارت سفید با سرتیتر (نام، تعداد، آخرین
بروزرسانی)، ردیف‌های کوتاه با پس‌زمینه‌ی یک‌درمیان، اعداد درشت و بدون
دکمه‌ی بزرگ در هر ردیف. تنها اقدام هر ردیف یک آیکون تلفن است.
"""
import content as C
from common import (CAT, PH, PHS, esc, fa, fmt, icon, clean_name, clean_val,
                    price_of, delta_of, unit_of, num_key, pct, u_cat, u_prod,
                    slugify, delta_badge, cat_stats, TODAY_SHORT, TODAY_ISO, TODAY_BIDI,
                    stampchips)

# ستون‌های کلیدی هر دسته — آنچه خریدار برای انتخاب لازم دارد، نه همه‌ی ستون‌ها.
KEY_SPECS = {
    "توری-حصاری":              ["ضخامت مفتول (mm)", "چشمه (سانتی متر)", "وزن هر متر مربع (کیلوگرم)", "طول رول (متر)"],
    "توری-پرسی":               ["ضخامت مفتول (mm)", "چشمه (سانتی متر)", "وزن هر برگ"],
    "مش-جوشی-یا-مش-آهنی":      ["چشمه (سانتی متر)", "سایز میلگرد (میلی متر)", "طول*عرض (متر)", "وزن هر برگ"],
    "توری-مرغی":               ["چشمه (اینچ)", "عرض (سانتی متر)", "طول (متر)", "وزن (کیلوگرم)"],
    "توری-فرنگی":              ["ضخامت مفتول (mm)", "عرض (سانتی متر)", "طول (متر)", "وزن (کیلوگرم)"],
    "توری-گابیون":             ["ضخامت مفتول (mm)", "عرض (سانتی متر)", "چشمه (سانتی متر)", "وزن هر متر مربع (گرم)"],
    "سیم-خاردار":              ["نوع", "ضخامت مفتول (mm)", "وزن (کیلوگرم)", "ضخامت خار (میلی متر)"],
    "سیم-سیاه-و-آرماتور-بندی": ["ضخامت مفتول (mm)", "نوع مفتول", "وزن (کیلوگرم)"],
    "توری-جوشی--گالوانیزه-رول": ["طول*عرض (متر)", "متراژ رول (متر مربع)", "وزن هر رول (تقریبی)"],
}

SHORT_HEAD = {
    "ضخامت مفتول (mm)": "ضخامت مفتول (mm)", "چشمه (سانتی متر)": "چشمه (cm)",
    "وزن هر متر مربع (کیلوگرم)": "وزن هر m² (kg)", "طول رول (متر)": "طول رول (m)",
    "وزن هر برگ": "وزن هر برگ", "سایز میلگرد (میلی متر)": "میلگرد (mm)",
    "طول*عرض (متر)": "ابعاد (m)", "چشمه (اینچ)": "چشمه (اینچ)",
    "عرض (سانتی متر)": "عرض (cm)", "طول (متر)": "طول (m)", "وزن (کیلوگرم)": "وزن (kg)",
    "وزن هر متر مربع (گرم)": "وزن هر m² (گرم)", "نوع": "نوع",
    "ضخامت خار (میلی متر)": "ضخامت خار (mm)", "نوع مفتول": "نوع مفتول",
    "متراژ رول (متر مربع)": "متراژ رول (m²)", "وزن هر رول (تقریبی)": "وزن رول",
}


def key_specs(key):
    specs = KEY_SPECS.get(key)
    if specs:
        return [s for s in specs if s in CAT[key]["specs"]]
    return [s for s in CAT[key]["specs"] if s != "محل بارگیری"][:4]


def _row(key, row, specs):
    name = row["نام محصول"]
    p, d = price_of(row), delta_of(row)
    href = u_prod(key, name)
    flag = ""
    if name in C.SUSPECT:
        flag = f'<span class="review" title="{esc(C.SUSPECT[name])}">بازبینی</span>'
    tds = "".join(
        f'<td class="pt-spec{" pt-lo" if i >= 2 else ""}" data-label="{esc(SHORT_HEAD.get(s, s))}">'
        f'{esc(clean_val(row.get(s)))}</td>' for i, s in enumerate(specs))
    data = " ".join(f'data-s{n}="{esc(str(row.get(sp, "")))}"' for n, sp in enumerate(specs))
    return f"""<tr data-name="{esc(clean_name(name))}" data-price="{p}" data-prev="{d or p}" {data}>
<td class="pt-name"><a href="{href}">{esc(clean_name(name))}</a>{flag}</td>
{tds}
<td class="pt-price" data-label="قیمت"><b class="num">{fmt(p)}</b><span class="pt-u">ریال / {esc(unit_of(row))}</span></td>
<td class="pt-delta" data-label="نوسان">{delta_badge(d, p)}</td>
<td class="pt-act"><button type="button" class="pt-chart" data-chart-row title="نمودار قیمت">{icon('i-chart')}<span class="vh">نمودار قیمت {esc(clean_name(name))}</span></button><a class="pt-call" href="tel:{PH}" data-track="call-row" title="استعلام تلفنی">{icon('i-phone')}<span class="vh">استعلام تلفنی {esc(clean_name(name))}</span></a></td>
</tr>"""


def filter_bar(key, specs, rows, tid):
    """نوار جست‌وجوی جدول — بدون جاوااسکریپت پنهان می‌ماند."""
    sels = []
    for n, sp in enumerate(specs[:3]):
        vals = sorted({str(r.get(sp, "")).strip() for r in rows if str(r.get(sp, "")).strip()},
                      key=lambda v: (num_key(v), v))
        if len(vals) < 2:
            continue
        opts = "".join(f'<option value="{esc(v)}">{fa(esc(clean_val(v)))}</option>' for v in vals)
        sels.append(f'<label class="ffield"><span>{esc(SHORT_HEAD.get(sp, sp))}</span>'
                    f'<select data-spec="{n}"><option value="">همه</option>{opts}</select></label>')
    return f"""<form class="tfilter slim" data-for="{tid}" hidden onsubmit="return false" aria-label="جست‌وجو در جدول">
  <div class="tfilter-row">
    <label class="ffield fsearch"><span>جست‌وجو</span>
      <input type="search" data-q placeholder="مثلاً: چشمه ۵/۵ یا مفتول ۳" autocomplete="off"></label>
    {''.join(sels)}
    <label class="ffield"><span>مرتب‌سازی</span>
      <select data-sort><option value="">پیش‌فرض</option><option value="asc">ارزان به گران</option><option value="desc">گران به ارزان</option></select></label>
  </div>
  <div class="tfilter-foot"><output data-count aria-live="polite"></output>
    <button type="button" class="btn btn-ghost btn-sm" data-reset>پاک‌کردن</button></div>
</form>"""


def price_table(key, limit=None, search=False, title=True, guide_link=True, note=True):
    """کارت جدول قیمت یک دسته."""
    c, s = C.CATS[key], cat_stats(key)
    specs = key_specs(key)
    rows = CAT[key]["rows"][:limit] if limit else CAT[key]["rows"]
    tid = f"t-{slugify(key)}"
    th = "".join(f'<th scope="col" class="pt-spec{" pt-lo" if i >= 2 else ""}">{esc(SHORT_HEAD.get(x, x))}</th>'
                 for i, x in enumerate(specs))
    body = "".join(_row(key, r, specs) for r in rows)
    bar = filter_bar(key, specs, rows, tid) if search else ""
    head_title = (f'<h2><a href="{u_cat(key)}">{esc(c["title"])}</a></h2>' if title else "")
    upd = (f'<div class="pt-upd">{icon("i-clock")} آخرین بروزرسانی: <b>امروز</b> '
           f'(<time data-live-short datetime="{TODAY_ISO}">{TODAY_SHORT}</time>)</div>')
    foot_link = (f'<a href="{u_cat(key)}">راهنمای خرید و مشخصات کامل {icon("i-chev")}</a>'
                 if guide_link else "")
    foot_note = ('<span>قیمت‌ها به ریال و مبنای روز درب کارخانه‌ی اصفهان است؛ قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود.</span>'
                 if note else "")
    return f"""<section class="pt-card" id="pt-{esc(c['slug'])}">
  <header class="pt-head">
    <div class="pt-title">{head_title}<span class="pt-meta">{fa(s['n'])} نوع کالا · واحد فروش: {esc(s['unit'])}</span></div>
    {upd}
  </header>
  {bar}
  <div class="pt-wrap">
  <table class="pt" id="{tid}">
    <caption class="vh">قیمت روز {esc(c['title'])}</caption>
    <thead><tr>
      <th scope="col" class="pt-name">نام کالا</th>
      {th}
      <th scope="col" class="pt-price">قیمت (ریال)</th>
      <th scope="col" class="pt-delta">نوسان</th>
      <th scope="col" class="pt-act"><span class="vh">استعلام</span></th>
    </tr></thead>
    <tbody>{body}</tbody>
  </table>
  </div>
  <footer class="pt-foot">{foot_note}{foot_link}</footer>
</section>"""


def changes_table(n=10):
    """آخرین تغییرات قیمت در همه‌ی دسته‌ها — معادل «جدول تغییرات لحظه‌ای»
    سایت فعلی. ردیف‌های مشکوک کنار گذاشته می‌شوند."""
    items = []
    for key in C.ORDER:
        for r in CAT[key]["rows"]:
            name = r["نام محصول"]
            if name in C.SUSPECT:
                continue
            p, d = price_of(r), delta_of(r)
            if not p or not d:
                continue
            items.append((abs(pct(p, d)), key, r, p, d))
    items.sort(key=lambda t: -t[0])
    trs = []
    for _, key, r, p, d in items[:n]:
        name = r["نام محصول"]
        up = p >= d
        cls = "up" if up else "down"
        trs.append(f"""<tr data-name="{esc(clean_name(name))}" data-price="{p}" data-prev="{d}">
<td class="ch-dir {cls}">{icon('i-up' if up else 'i-down')}</td>
<td class="ch-name"><a href="{u_prod(key, name)}">{esc(clean_name(name))}</a><span class="ch-cat">{esc(C.CATS[key]['title'])} · {esc(unit_of(r))}</span></td>
<td class="ch-price" data-label="قیمت لحظه‌ای"><b class="num">{fmt(p)}</b> <span class="riyal">ریال</span></td>
<td class="ch-pct" data-label="نوسان">{delta_badge(d, p)}</td>
<td class="ch-diff" data-label="تغییر نسبت به آخرین ثبت"><span class="num">{fmt(abs(p - d))}</span> ریال <span class="{cls}">{'افزایش' if up else 'کاهش'}</span></td>
<td class="pt-act"><button type="button" class="pt-chart" data-chart-row title="نمودار قیمت">{icon('i-chart')}<span class="vh">نمودار</span></button></td>
</tr>""")
    return f"""<section class="pt-card ch-card" id="changes">
  <header class="pt-head">
    <div class="pt-title"><h2>آخرین تغییرات قیمت</h2><span class="pt-meta">{fa(len(trs))} کالا با بیشترین تغییر نسبت به آخرین قیمت ثبت‌شده</span></div>
    {stampchips()}
  </header>
  <div class="pt-wrap">
  <table class="pt ch">
    <caption class="vh">آخرین تغییرات قیمت محصولات</caption>
    <thead><tr><th scope="col"><span class="vh">جهت</span></th><th scope="col">نام کالا</th><th scope="col">قیمت لحظه‌ای</th><th scope="col">نوسان</th><th scope="col">مقدار تغییر نسبت به آخرین ثبت</th><th scope="col"><span class="vh">نمودار</span></th></tr></thead>
    <tbody>{''.join(trs)}</tbody>
  </table>
  </div>
</section>"""


def spec_table(key):
    """جدول مشخصات فنی کامل یک دسته (همه‌ی ستون‌ها)."""
    rows = CAT[key]["rows"]
    specs = [x for x in CAT[key]["specs"] if x != "محل بارگیری"]
    c, s = C.CATS[key], cat_stats(key)
    sp_head = "".join(f"<th>{esc(x)}</th>" for x in ["نام کالا"] + specs)
    sp_body = "".join(
        f'<tr><td><a href="{u_prod(key, r["نام محصول"])}">{esc(clean_name(r["نام محصول"]))}</a></td>'
        + "".join(f'<td class="num">{esc(clean_val(r.get(x)))}</td>' for x in specs) + "</tr>"
        for r in rows)
    return f"""<div class="tablescroll">
      <table class="spectable">
        <caption>مشخصات فنی کامل — {fa(s['n'])} نوع {esc(c['title'])}</caption>
        <thead><tr>{sp_head}</tr></thead>
        <tbody>{sp_body}</tbody>
      </table>
      </div>"""


def sibling_table(key, row, n=8):
    """محصولات هم‌دسته در صفحه‌ی محصول — جدول کوچک، نه فهرست گلوله‌ای."""
    specs = key_specs(key)[:2]
    others = [r for r in CAT[key]["rows"] if r["نام محصول"] != row["نام محصول"]][:n]
    th = "".join(f'<th>{esc(SHORT_HEAD.get(x, x))}</th>' for x in specs)
    trs = "".join(
        f'<tr><td class="pt-name"><a href="{u_prod(key, r["نام محصول"])}">{esc(clean_name(r["نام محصول"]))}</a></td>'
        + "".join(f'<td data-label="{esc(SHORT_HEAD.get(x, x))}">{esc(clean_val(r.get(x)))}</td>' for x in specs)
        + f'<td class="pt-price" data-label="قیمت (ریال)"><b class="num">{fmt(price_of(r))}</b></td></tr>'
        for r in others)
    return f"""<div class="pt-wrap sib"><table class="pt pt-mini"><thead><tr><th>نام کالا</th>{th}<th>قیمت (ریال)</th></tr></thead><tbody>{trs}</tbody></table></div>"""
