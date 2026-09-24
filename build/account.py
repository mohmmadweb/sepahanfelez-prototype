# -*- coding: utf-8 -*-
"""
ورود، ثبت‌نام، تأیید کد، ناحیه‌ی کاربری و صفحه‌ی ۴۰۴ — نسخه‌ی نمایشی.

در پروتوتایپ ایستا هیچ فرمی کار نمی‌کند؛ این صفحه‌ها فقط چیدمان را نشان
می‌دهند. نسخه‌ی زنده‌ی همین‌ها در laravel/resources/views/redesign/ است و
به همان مسیرهای بک‌اند (/submit-phone، /verify-phone، /user/*) وصل است.
متن‌ها از content.ACCOUNT می‌آیند تا هر دو نسخه یک متن داشته باشند.

سبد خرید عمداً ساخته نمی‌شود: سفارش تلفنی ثبت می‌شود.
"""
import content as C
from common import (esc, fa, icon, page_shell, PH, PHS, u_home)

A = C.ACCOUNT

DEMO = (f'<p class="cform-note">{icon("i-clock")} در این نسخه‌ی نمایشی فرم غیرفعال است. '
        f'در نسخه‌ی نهایی کد تأیید با پیامک ارسال می‌شود.</p>')


def _benefits():
    items = "".join(
        f'<div class="trustitem">{icon(ic)}<div><div class="t">{esc(t)}</div>'
        f'<div class="d">{esc(d)}</div></div></div>'
        for ic, t, d in A["benefits"])
    return f"""<aside class="auth-side">
      <h2>{esc(A['side_title'])}</h2>
      <div class="trustgrid unit-promise">{items}</div>
      <div class="callout"><div class="t">سفارش و قیمت قطعی تلفنی است</div>
        <p>{esc(A['order_note'])} <a class="num" href="tel:{PH}">{PHS}</a></p></div>
    </aside>"""


def _foot(k):
    q, label, href = A[k]["foot"]
    return f'<p class="auth-foot">{esc(q)} <a href="{href}">{esc(label)}</a></p>'


def _shell(title, card, crumb):
    body = f"""
  <section class="section">
    <div class="container">
      <div class="auth-wrap">
        <div class="auth-card">{card}</div>
        {_benefits()}
      </div>
    </div>
  </section>"""
    return page_shell(f"{title} | سپاهان فلز", A["login"]["lede"], None, body,
                      crumbs=[("خانه", u_home()), (crumb, "#")])


def build_login():
    t = A["login"]
    card = f"""<h1>{esc(t['title'])}</h1>
      <p class="lede">{esc(t['lede'])}</p>
      {DEMO}
      <form method="post" action="/submit-phone">
        <label class="ffield"><span>شماره موبایل</span>
          <input name="mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required disabled></label>
        <button class="btn btn-lg btn-block" type="submit" disabled>{esc(t['button'])}</button>
      </form>
      {_foot('login')}"""
    return _shell(t["title"], card, "ورود")


def build_register():
    t = A["register"]
    card = f"""<h1>{esc(t['title'])}</h1>
      <p class="lede">{esc(t['lede'])}</p>
      {DEMO}
      <form method="post" action="/submit-data">
        <label class="ffield"><span>شماره موبایل</span>
          <input name="mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required disabled></label>
        <div class="ffield">
          <label class="fcheck"><input type="checkbox" name="business_account" value="1" disabled> {esc(t['business'])}</label>
          <span class="hint">{esc(t['business_hint'])}</span>
        </div>
        <button class="btn btn-lg btn-block" type="submit" disabled>{esc(t['button'])}</button>
      </form>
      {_foot('register')}"""
    return _shell(t["title"], card, "ثبت‌نام")


def build_verify():
    t = A["verify"]
    card = f"""<h1>{esc(t['title'])}</h1>
      <p class="lede">{esc(t['lede'])}</p>
      {DEMO}
      <form method="post" action="/verify-phone">
        <label class="ffield code"><span>کد تأیید شش‌رقمی</span>
          <input name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required disabled></label>
        <button class="btn btn-lg btn-block" type="submit" disabled>{esc(t['button'])}</button>
      </form>
      <div class="auth-resend"><span>ارسال دوباره‌ی کد تا <b class="num">۰۲:۰۰</b> دیگر</span></div>
      {_foot('verify')}"""
    return _shell(t["title"], card, "تأیید کد")


def _panel(current, head, content):
    links = "".join(
        f'<a href="{href}"{" aria-current=page" if key == current else ""}>{icon(ic)}{esc(label)}</a>'
        for key, href, ic, label in A["nav"])
    return f"""
  <section class="section">
    <div class="container panel">
      <nav class="panel-nav" aria-label="ناحیه‌ی کاربری">
        <div class="panel-who"><span class="av">{icon('i-user')}</span>
          <div><b>کاربر نمونه</b><span class="num">۰۹۱۲۳۴۵۶۷۸۹</span></div></div>
        {links}
        <button type="button" disabled>{icon('i-arrow')}خروج از حساب</button>
      </nav>
      <div class="panel-main">
        <div class="panel-head"><div>{head}</div></div>
        <p class="cform-note">{icon('i-clock')} نسخه‌ی نمایشی: داده‌ها نمونه‌اند و فرم‌ها غیرفعال.</p>
        {content}
      </div>
    </div>
  </section>"""


def build_user_profile():
    fields = "".join(
        f'<label class="ffield{" full" if full else ""}"><span>{esc(lbl)}</span>'
        f'<input name="{n}" type="{typ}" value="{esc(v)}" disabled></label>'
        for n, lbl, typ, v, full in [
            ("full_name", "نام و نام خانوادگی", "text", "کاربر نمونه", False),
            ("email", "ایمیل", "email", "", False),
            ("national_code", "کد ملی", "text", "", False),
            ("phone", "تلفن ثابت", "tel", "", False),
            ("company", "نام شرکت (حساب حقوقی)", "text", "", False),
            ("economic_code", "کد اقتصادی", "text", "", False),
        ])
    content = f"""<form class="pcard2" method="post" action="/user/profile">
          <h2>اطلاعات شخصی</h2>
          <div class="fgrid2">{fields}</div>
          <div class="factions"><button class="btn btn-lg" type="submit" disabled>ذخیره‌ی تغییرات</button></div>
        </form>"""
    return page_shell("مشخصات حساب | سپاهان فلز", A["order_note"], None,
                      _panel("profile", "<h1>مشخصات حساب</h1><div class=\"sub\">شماره‌ی موبایل، شناسه‌ی ورود شماست و تغییر نمی‌کند.</div>", content),
                      crumbs=[("خانه", u_home()), ("ناحیه‌ی کاربری", "#")])


def build_user_tickets():
    rows = "".join(
        f'<tr><td data-label="موضوع"><a href="#">{esc(s)}</a></td><td data-label="واحد">{esc(d)}</td>'
        f'<td data-label="وضعیت"><span class="badge {cls}">{esc(st)}</span></td>'
        f'<td data-label="آخرین بروزرسانی" class="num">{fa(t)}</td></tr>'
        for s, d, cls, st, t in [
            ("پیش‌فاکتور ۳ تن توری حصاری چشمه ۶/۵", "فروش", "badge-ok", "پاسخ داده شده", "1405/06/28"),
            ("نقشه‌ی تولید سفارشی توری جوشی عرض ۱۵۰", "فروش", "badge-warn", "در انتظار پاسخ", "1405/06/30"),
        ])
    content = f"""<div class="pcard2">
          <div class="pt-wrap"><table class="dtable"><thead><tr><th>موضوع</th><th>واحد</th><th>وضعیت</th><th>آخرین بروزرسانی</th></tr></thead>
          <tbody>{rows}</tbody></table></div>
        </div>"""
    head = ('<h1>تیکت‌ها و درخواست‌ها</h1><div class="sub">درخواست کتبی پیش‌فاکتور، نقشه یا پیگیری بار</div>'
            '</div><a class="btn btn-lg" href="#">تیکت تازه</a><div>')
    return page_shell("تیکت‌ها | سپاهان فلز", A["order_note"], None,
                      _panel("tickets", head, content),
                      crumbs=[("خانه", u_home()), ("ناحیه‌ی کاربری", "/user/profile"), ("تیکت‌ها", "#")])


def build_404():
    body = f"""
  <section class="section">
    <div class="container errpage">
      <div class="code num">۴۰۴</div>
      <h1>این صفحه پیدا نشد</h1>
      <p>ممکن است نشانی عوض شده یا کالا از فهرست خارج شده باشد. قیمت روز همه‌ی کالاها در جدول قیمت لحظه‌ای هست.</p>
      <div class="factions">
        <a class="btn btn-lg" href="/price">قیمت لحظه‌ای</a>
        <a class="btn btn-ghost btn-lg2" href="/category/">دسته‌های محصول</a>
        <a class="btn btn-call" href="tel:{PH}">{icon('i-phone')} <span class="num">{PHS}</span></a>
      </div>
    </div>
  </section>"""
    return page_shell("صفحه پیدا نشد | سپاهان فلز", "این صفحه پیدا نشد.", None, body)
