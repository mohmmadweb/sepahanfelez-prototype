# -*- coding: utf-8 -*-
"""قابلیت‌های نسخه‌ی ۲۱: نمودار قیمت، کارشناسان فروش، دیدگاه و امتیاز،
ماشین‌حساب وزن/هزینه/کرایه، و متن یکتای هر محصول.

هر تابع فقط HTML برمی‌گرداند؛ رفتار در assets/chart.js و assets/tools.js است.
"""
import json
import content as C
import analysis as A
from common import (CAT, esc, fa, fmt, icon, price_of, delta_of, unit_of, clean_name,
                    cat_stats, PH, PHS, WA, u_cat, u_prod, TODAY)

# ---------------------------------------------------------------------------
# نمودار
# ---------------------------------------------------------------------------
def chart(id_, price, prev, key, title, sub="", days=30, compact=False):
    """بلوک نمودار. داده‌ی واقعی: دو نقطه (prev → price)؛ سری روزانه در
    مرورگر ساخته می‌شود و زیرش نوشته می‌شود که نمونه است."""
    if not price:
        return ""
    ranges = "".join(
        f'<button type="button" data-range="{d}" aria-pressed="{"true" if d == days else "false"}" class="{"is-on" if d == days else ""}">{lbl}</button>'
        for d, lbl in ((7, "هفتگی"), (30, "ماهانه"), (90, "سه‌ماهه")))
    head = "" if compact else f'<div class="chart-head"><div><h3>{esc(title)}</h3>{f"<span class=dim>{esc(sub)}</span>" if sub else ""}</div><div class="chart-ranges" role="group" aria-label="بازه">{ranges}</div></div>'
    if compact:
        head = f'<div class="chart-head"><div class="chart-title">{esc(title)}</div><div class="chart-ranges" role="group" aria-label="بازه">{ranges}</div></div>'
    return f"""<div class="chart" id="{esc(id_)}" data-price="{price}" data-prev="{prev or price}" data-key="{esc(key)}" data-days="{days}">
  {head}
  <div class="chart-svg"></div>
  <div class="chart-stats"></div>
  <p class="chart-note">دو نقطه‌ی واقعی: آخرین قیمت ثبت‌شده (<b class="num">{fmt(prev or price)}</b> ریال) و قیمت روز (<b class="num">{fmt(price)}</b> ریال). سری روزانه در این نسخه‌ی نمایشی است و پس از اتصال به بک‌اند از تاریخچه‌ی واقعی ثبت قیمت‌ها خوانده می‌شود.</p>
</div>"""


def product_chart(key, row):
    p, d = price_of(row), delta_of(row)
    name = clean_name(row["نام محصول"])
    return chart("chart-product", p, d, row["نام محصول"], f"نمودار قیمت {name}",
                 f"ریال / {unit_of(row)} · مبنای روز درب کارخانه", days=30)


def category_chart(key):
    """میانگین قیمت دسته — معادل daily_avg_price در بک‌اند."""
    rows = [r for r in CAT[key]["rows"] if r["نام محصول"] not in C.SUSPECT and price_of(r)]
    if not rows:
        return ""
    cur = sum(price_of(r) for r in rows) // len(rows)
    prevs = [delta_of(r) for r in rows if delta_of(r)]
    prev = sum(prevs) // len(prevs) if prevs else cur
    c = C.CATS[key]
    return chart(f"chart-cat-{c['slug']}", cur, prev, key, f"نمودار میانگین قیمت {c['title']}",
                 f"میانگین {fa(len(rows))} نوع کالا · ریال / {unit_of(rows[0])}", days=90)


CHART_DIALOG = """<dialog id="chart-dialog" class="chart-dialog" aria-label="نمودار قیمت">
  <div class="chart-dialog-in">
    <button type="button" class="chart-close" data-close aria-label="بستن">×</button>
    <div class="chart" data-price="0" data-prev="0" data-key="" data-days="30">
      <div class="chart-head"><div class="chart-title"></div>
        <div class="chart-ranges" role="group" aria-label="بازه">
          <button type="button" data-range="7" class="">هفتگی</button><button type="button" data-range="30" class="is-on">ماهانه</button><button type="button" data-range="90">سه‌ماهه</button></div></div>
      <div class="chart-svg"></div><div class="chart-stats"></div>
      <p class="chart-note">دو نقطه‌ی واقعی (آخرین ثبت و قیمت روز)؛ سری روزانه نمایشی است و با اتصال به بک‌اند از تاریخچه‌ی واقعی خوانده می‌شود.</p>
    </div>
  </div>
</dialog>"""


# ---------------------------------------------------------------------------
# کارشناسان فروش
# ---------------------------------------------------------------------------
def rep_for(key=None):
    """کارشناس مسئول یک دسته.

    با یک عضو تیم هم درست کار می‌کند: همان یک نفر مسئول همه‌ی دسته‌هاست و
    هیچ‌جا گفته نمی‌شود چند نفرند. با چند عضو، ASSIGN تعیین می‌کند کدام
    دسته به کدام کارشناس برسد.
    """
    team = C.SALES_TEAM
    if not team:
        return None
    if key:
        who = C.ASSIGN.get(key)
        if who:
            for m in team:
                if m["id"] == who:
                    return m
    return team[0]


def rep_card(e, compact=False):
    """کارت کارشناس. هیچ شمارشی از تیم نشان نمی‌دهد."""
    if not e:
        return ""
    wa_text = esc(f"سلام، درباره‌ی قیمت سؤال دارم.")
    return f"""<div class="expert{' compact' if compact else ''}">
  <img src="{esc(e['photo'])}" alt="{esc(e['name'])} — {esc(e['role'])}" width="72" height="72" loading="lazy" decoding="async">
  <div class="expert-t">
    <b>{esc(e['name'])}</b><span>{esc(e['role'])}</span>
    <div class="expert-links">
      <a class="expert-tel" href="tel:{PH},{e['ext']}" data-track="call-expert"><span class="num">{PHS}</span> <em>داخلی <b class="num">{fa(e['ext'])}</b></em></a>
      <a class="expert-wa" href="https://wa.me/{WA}?text={wa_text}" rel="noopener">{icon('i-whatsapp')} واتساپ</a>
    </div>
    <span class="dim">{esc(e['hours'])}</span>
  </div>
</div>"""


def experts_box(key=None, title=None):
    """جعبه‌ی کناری: واحد فروش + کارشناس مسئول همین دسته."""
    e = rep_for(key)
    if not e:
        return ""
    return f"""<aside class="experts" aria-label="واحد فروش">
  <h3>{icon('i-user')} {esc(title or 'کارشناس مسئول این دسته')}</h3>
  {rep_card(e)}
  <p class="dim">شماره‌ی دفتر را بگیرید و داخلی را وارد کنید تا مستقیم به کارشناس همین محصول وصل شوید.</p>
</aside>"""


def sales_unit_section():
    """بخش «واحد فروش» — جای شبکه‌ی چندنفره‌ی قبلی.

    به‌جای چیدن چند کارت کنار هم (که تعداد تیم را لو می‌دهد)، سه تعهد
    عملیاتی واحد فروش را می‌گوید و یک کارشناس مسئول را معرفی می‌کند.
    """
    u = C.SALES_UNIT
    e = rep_for(None)
    rows = "".join(
        f'<div class="trustitem">{icon(ic)}<div><div class="t">{esc(t)}</div><div class="d">{esc(d)}</div></div></div>'
        for ic, t, d in u["promise"])
    return f"""<section class="section alt" id="sales-unit">
  <div class="container">
    <div class="section-head"><div><h2>{esc(u['title'])}</h2><div class="sub">{esc(u['lede'])}</div></div></div>
    <div class="unit-grid">
      <div class="trustgrid unit-promise">{rows}</div>
      <div class="unit-rep">
        <h3>{icon('i-user')} کارشناس پاسخگو</h3>
        {rep_card(e)}
        <a class="btn btn-call btn-lg2 unit-call" href="tel:{PH}" data-track="call-unit">{icon('i-phone')} تماس با واحد فروش <span class="num">{PHS}</span></a>
      </div>
    </div>
  </div>
</section>"""


# ---------------------------------------------------------------------------
# دیدگاه و امتیاز
# ---------------------------------------------------------------------------
def stars(n, size=""):
    full = "".join(f'<i class="{"on" if i < n else ""}">★</i>' for i in range(5))
    return f'<span class="stars {size}" aria-label="{fa(n)} از ۵">{full}</span>'


def reviews_block(key, subject=None):
    """جمع امتیاز + دیدگاه‌های نمونه (با نشان) + فرم ثبت دیدگاه."""
    c = C.CATS[key]
    subj = subject or c["title"]
    items = C.SAMPLE_REVIEWS.get(key, [])
    avg = round(sum(r[1] for r in items) / len(items), 1) if items else 0
    dist = "".join(
        f'<div class="rdist"><span>{fa(s)}★</span><div class="rbar"><i style="width:{int(100*sum(1 for r in items if r[1]==s)/len(items)) if items else 0}%"></i></div><span class="num">{fa(sum(1 for r in items if r[1]==s))}</span></div>'
        for s in (5, 4, 3, 2, 1))
    lst = "".join(f"""<article class="review">
    <header><b>{esc(who)}</b>{stars(sc, 'sm')}<span class="badge-sample" title="این دیدگاه نمونه است و پس از اتصال بک‌اند با دیدگاه واقعی جایگزین می‌شود">نمونه</span></header>
    <p>{esc(txt)}</p></article>""" for who, sc, txt in items)
    radios = "".join(f'<input type="radio" name="rating" id="r{key_id(key)}-{s}" value="{s}"{" checked" if s == 5 else ""}><label for="r{key_id(key)}-{s}" title="{fa(s)}">★</label>' for s in (1, 2, 3, 4, 5))
    return f"""<section class="section" id="reviews">
  <div class="container">
    <div class="section-head"><div><h2>دیدگاه و امتیاز خریداران {esc(subj)}</h2>
      <div class="sub">دیدگاه‌ها پس از تأیید کارشناس منتشر می‌شوند</div></div></div>
    <div class="reviews-grid">
      <div class="rsummary">
        <div class="ravg"><b class="num">{fa(avg) if avg else '—'}</b><span>از ۵</span>{stars(round(avg), 'lg')}<span class="dim">{fa(len(items))} دیدگاه نمونه</span></div>
        {dist}
        <p class="dim">امتیاز و دیدگاه‌های این بخش <b>نمونه</b> است تا چیدمان دیده شود؛ با اتصال به بک‌اند، دیدگاه‌های تأییدشده‌ی جدول product_comments نمایش داده می‌شود.</p>
      </div>
      <div class="rlist">{lst if lst else '<p class="dim">هنوز دیدگاهی ثبت نشده است. اولین نفر باشید.</p>'}</div>
      <form class="rform" method="post" action="{u_cat(key)}/comment">
        <h3>دیدگاه خود را بنویسید</h3>
        <div class="star-input" role="radiogroup" aria-label="امتیاز">{radios}</div>
        <div class="cform-grid">
          <label class="ffield"><span>نام</span><input name="name" type="text" maxlength="40" disabled></label>
          <label class="ffield"><span>شماره تماس (منتشر نمی‌شود)</span><input name="phone" type="tel" inputmode="numeric" maxlength="15" disabled></label>
          <label class="ffield fsearch"><span>متن دیدگاه</span><textarea name="body" rows="3" maxlength="500" disabled></textarea></label>
        </div>
        <button class="btn btn-ghost" type="submit" disabled>ارسال دیدگاه</button>
        <p class="dim">در این نسخه‌ی نمایشی فرم غیرفعال است؛ در نسخه‌ی نهایی به مسیر ثبت دیدگاه بک‌اند وصل می‌شود.</p>
      </form>
    </div>
  </div>
</section>"""


def key_id(key):
    return C.CATS[key]["slug"]


# ---------------------------------------------------------------------------
# ماشین‌حساب وزن / هزینه / کرایه
# ---------------------------------------------------------------------------
def calculator(key, row=None):
    """برای دسته: روی نماینده‌ی دسته (ارزان‌ترین سالم) — برای محصول: همان کالا."""
    rows = [r for r in CAT[key]["rows"] if r["نام محصول"] not in C.SUSPECT and price_of(r)]
    if row is None:
        if not rows:
            return ""
        row = rows[0]
        selector = "".join(
            f'<option value="{price_of(r)}|{_kg(key, r)}|{_m2(key, r)}"{" selected" if r is row else ""}>{esc(clean_name(r["نام محصول"]))}</option>'
            for r in rows)
        pick = f'<label class="ffield"><span>نوع کالا</span><select name="pick" data-pick>{selector}</select></label>'
    else:
        pick = ""
    p = price_of(row); unit = unit_of(row)
    kg, m2 = _kg(key, row), _m2(key, row)
    modes = [("unit", f"بر حسب {unit}")]
    if m2: modes.append(("m2", "بر حسب متر مربع"))
    if kg: modes.append(("kg", "بر حسب کیلوگرم"))
    mode_opts = "".join(f'<option value="{v}">{esc(l)}</option>' for v, l in modes)
    dest_opts = "".join(f'<option value="{rate}">{esc(name)}{"" if not rate else " — " + fmt(rate) + " ریال/تن"}</option>' for name, rate in C.FREIGHT)
    name = clean_name(row["نام محصول"])
    parts = []
    if kg and unit != "کیلوگرم":
        parts.append(f"وزن هر {unit}: {fa(f'{kg:g}')} کیلوگرم")
    if m2 and unit != "مترمربع":
        parts.append(f"سطح هر {unit}: {fa(f'{round(m2, 2):g}')} متر مربع")
    if unit == "کیلوگرم" and m2:
        parts.append(f"هر کیلوگرم ≈ {fa(f'{round(m2, 2):g}')} متر مربع")
    basis = " · ".join(parts)
    return f"""<section class="section alt" id="calculator">
  <div class="container">
    <div class="section-head"><div><h2>ماشین‌حساب وزن و هزینه</h2>
      <div class="sub">مقدار را وارد کنید تا وزن بار، مبلغ کالا و برآورد کرایه‌ی حمل را ببینید</div></div></div>
    <form class="calc" data-price="{p}" data-unit="{esc(unit)}" data-kg-per-unit="{kg or 0}" data-m2-per-unit="{m2 or 0}" data-freight='{json.dumps([r for _, r in C.FREIGHT])}' data-freight-min="{C.FREIGHT_MIN_TON}" hidden>
      <div class="calc-grid">
        {pick}
        <label class="ffield"><span>مقدار</span><input name="qty" type="text" inputmode="decimal" placeholder="مثلاً ۱۰" autocomplete="off"></label>
        <label class="ffield"><span>واحد</span><select name="mode">{mode_opts}</select></label>
        <label class="ffield"><span>مقصد بار</span><select name="dest">{dest_opts}</select></label>
      </div>
      <p class="calc-basis">مبنا: <b>{esc(name)}</b> — <span class="num">{fmt(p)}</span> ریال / {esc(unit)}{(' · ' + basis) if basis else ''}</p>
      <div class="calc-out" hidden></div>
      <p class="dim">مبلغ کالا با قیمت مبنای روز حساب می‌شود و کرایه‌ی حمل برآورد تقریبی است (نرخ نمونه به ازای هر تن، کف یک تن). قیمت و کرایه‌ی قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود: <span class="num">{PHS}</span>.</p>
    </form>
  </div>
</section>"""


def _kg(key, row):
    _, area, weight, w_m2 = A.geometry(key, row)
    unit = unit_of(row)
    if unit == "کیلوگرم": return 1.0
    if unit == "مترمربع": return round(w_m2, 3) if w_m2 else 0
    return round(weight, 2) if weight else 0


def _m2(key, row):
    _, area, weight, w_m2 = A.geometry(key, row)
    unit = unit_of(row)
    if unit == "مترمربع": return 1.0
    if unit == "کیلوگرم": return round(1 / w_m2, 3) if w_m2 else 0
    return round(area, 2) if area else 0


# ---------------------------------------------------------------------------
# متن یکتای هر محصول — از مشخصات همان ردیف
# ---------------------------------------------------------------------------
_USE = {
 "توری-حصاری": ("حصارکشی محوطه، باغ و زمین کشاورزی", "پیمانکاران محوطه‌سازی، باغداران و مدیران تأسیسات"),
 "توری-پرسی": ("نرده، حفاظ پنجره و سرند", "کارگاه‌های نرده‌سازی و پروژه‌های صنعتی"),
 "مش-جوشی-یا-مش-آهنی": ("دال بتنی، کف‌سازی و دیوار حائل", "پیمانکاران ساختمانی و مجریان کف‌سازی"),
 "توری-مرغی": ("قفس، مرغداری، زیرسازی گچ و پوشش", "مرغداران، باغداران و گچ‌کاران"),
 "توری-گابیون": ("دیوار حائل، تثبیت شیب و ساماندهی رودخانه", "مهندسان ناظر و پیمانکاران عمرانی"),
 "توری-جوشی--گالوانیزه-رول": ("حفاظ پنجره، قفس و جداکننده", "قفس‌سازان و کارگاه‌های حفاظ"),
 "سیم-خاردار": ("حفاظت پیرامونی و مرزبندی", "مدیران تأسیسات و کشاورزان"),
 "توری-فرنگی": ("حصار باغ، ویلا و کرت", "ویلاسازان و باغداران"),
 "سیم-سیاه-و-آرماتور-بندی": ("بستن گره‌ی میلگرد پیش از بتن‌ریزی", "آرماتوربندان و پیمانکاران"),
}


def product_intro(key, row, rows):
    """دو بند یکتا از اعداد خود ردیف: جایگاه در دسته، مشخصه‌ی متمایز، کاربرد."""
    c = C.CATS[key]
    name = clean_name(row["نام محصول"])
    p = price_of(row); unit = unit_of(row)
    prices = sorted({price_of(r) for r in rows if price_of(r) and r["نام محصول"] not in C.SUSPECT})
    pos = (prices.index(p) + 1) if p in prices else None
    n = len(prices)
    use, who = _USE.get(key, ("کاربردهای متداول", "خریداران صنعتی"))
    specs = [s for s in CAT[key]["specs"] if s != "محل بارگیری" and str(row.get(s, "")).strip()]
    spec_txt = "، ".join(f"{s.split(' (')[0]} {fa(str(row.get(s)))}" for s in specs[:3])
    if pos == 1 and n > 2:
        rank = f"ارزان‌ترین گزینه‌ی این دسته است"
    elif pos == n and n > 2:
        rank = f"بالاترین قیمت دسته را دارد و برای کاربرد سنگین ساخته شده"
    elif pos and n > 2:
        rank = f"از نظر قیمت در جایگاه {fa(pos)} از {fa(n)} سطح قیمتی دسته قرار می‌گیرد"
    elif pos:
        rank = "هم‌قیمت با بیشتر کالاهای این دسته است و تفاوت آن در مشخصات فنی است"
    else:
        rank = "قیمتش در حال بازبینی است"
    _, area, weight, w_m2 = A.geometry(key, row)
    econ = ""
    if w_m2 and unit != "مترمربع":
        econ = f" هر متر مربع آن حدود {fa(f'{w_m2:g}')} کیلوگرم فولاد دارد؛ یعنی هزینه‌ی هر متر مربع حدود <b class=\"num\">{fmt(int(p * w_m2 if unit == 'کیلوگرم' else (p / area if area else 0)))}</b> ریال درمی‌آید."
    elif area and unit in ("رول", "برگ"):
        econ = f" هر {unit} {fa(f'{area:g}')} متر مربع را می‌پوشاند، پس هزینه‌ی هر متر مربع حدود <b class=\"num\">{fmt(int(p / area))}</b> ریال است."
    return (f"<p><strong>{esc(name)}</strong> یکی از {fa(len(rows))} نوع {esc(c['title'])} تولید صنایع مفتولی طلوع سپاهان است با مشخصات {esc(spec_txt)}. "
            f"این کالا {rank} و به {unit} فروخته می‌شود.{econ}</p>"
            f"<p>کاربرد اصلی آن {use} است و بیشتر {who} آن را سفارش می‌دهند. تحویل از کارخانه‌ی اصفهان یا انبار تهران انجام می‌شود و قیمت قطعی سفارش با تناژ و مقصد بار در تماس با کارشناس فروش اعلام می‌گردد.</p>")
