# -*- coding: utf-8 -*-
"""
سازنده‌ی صفحات سپاهان فلز.

خروجی: صفحه‌ی اصلی · قیمت لحظه‌ای · فهرست دسته‌ها · ۹ دسته · ۷۰ محصول ·
درباره · تماس · مجله (فهرست، ۶ دسته، ۳۱ مقاله).
داده از build/catalog.json و build/articles.json، متن از content.py.

اجرا:  python3 build/gen.py
"""
import os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import content as C
import pages as P
import blog as B
from common import ROOT, CAT, out_path, u_home, u_price, u_catlist, u_about, u_contact, \
    u_blog, u_blogcat, u_article, u_cat, u_prod

# ---------------------------------------------------------------------------
# ارقام فارسی در سراسر خروجی — پس‌پردازش روی HTML نهایی
# ---------------------------------------------------------------------------
_FA = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")
_SKIP = re.compile(r"(?is)<(script|style)\b.*?</\1>")
_ENT = re.compile(r"&[#A-Za-z0-9]+;")


def fa_digits(html_out):
    parts, last = [], 0
    for blk in _SKIP.finditer(html_out):
        parts.append((last, blk.start(), True))
        parts.append((blk.start(), blk.end(), False))
        last = blk.end()
    parts.append((last, len(html_out), True))
    out = []
    for a, b, convert in parts:
        seg = html_out[a:b]
        if not convert:
            out.append(seg); continue
        buf, pos = [], 0
        for m in re.finditer(r">([^<>]+)<", seg):
            buf.append(seg[pos:m.start(1)])
            txt = m.group(1)
            sub, k = [], 0
            for e in _ENT.finditer(txt):
                sub.append(txt[k:e.start()].translate(_FA))
                sub.append(e.group(0))
                k = e.end()
            sub.append(txt[k:].translate(_FA))
            buf.append("".join(sub))
            pos = m.end(1)
        buf.append(seg[pos:])
        out.append("".join(buf))
    return "".join(out)


def write(path, s):
    s = fa_digits(s)
    full = os.path.join(ROOT, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with open(full, "w", encoding="utf-8") as f:
        f.write(s)


def main():
    n = 0
    write(out_path(u_home()), P.build_index()); n += 1
    write(out_path(u_price()), P.build_price()); n += 1
    write(out_path(u_catlist()), P.build_catlist()); n += 1
    write(out_path(u_about()), P.build_about()); n += 1
    write(out_path(u_contact()), P.build_contact()); n += 1
    for key in C.ORDER:
        write(out_path(u_cat(key)), P.build_category(key)); n += 1
        for i, row in enumerate(CAT[key]["rows"]):
            write(out_path(u_prod(key, row["نام محصول"])), P.build_product(key, row, i)); n += 1
    write(out_path(u_blog()), B.build_blog_index()); n += 1
    for slug in B.BLOG_CATS:
        write(out_path(u_blogcat(slug)), B.build_blog_category(slug)); n += 1
    for a in B.ARTS:
        write(out_path(u_article(a["cat_slug"], a["slug"])), B.build_article(a)); n += 1
    import account as AC
    for url, fn in (("/login", AC.build_login), ("/register", AC.build_register),
                    ("/verify-phone", AC.build_verify),
                    ("/user/profile", AC.build_user_profile),
                    ("/user/tickets", AC.build_user_tickets)):
        write(out_path(url), fn()); n += 1
    # GitHub Pages هر نشانیِ ناموجود را به 404.html می‌فرستد.
    write("404.html", AC.build_404()); n += 1
    import searchindex as SI
    m = SI.write(os.path.join(ROOT, "assets", "search-index.json"))

    # نقشه‌ی سایت و robots
    import sitemap as SM
    from common import LIVE, cat_photo
    xml, count = SM.build(C, CAT, cat_photo, u_cat, u_prod, u_article, u_blogcat,
                          B.ARTS, B.BLOG_CATS)
    sm_path = os.path.join(ROOT, "sitemap.xml")
    if LIVE:
        open(sm_path, "w", encoding="utf-8").write(xml)
    elif os.path.exists(sm_path):
        os.remove(sm_path)          # در حالت پروتوتایپ نباید وجود داشته باشد
    open(os.path.join(ROOT, "robots.txt"), "w", encoding="utf-8").write(SM.robots(LIVE))

    # ریدایرکت نشانی‌های ایندکس‌شده‌ای که معادل مستقیم ندارند
    for src, dst in SM.REDIRECTS.items():
        rp = os.path.join(ROOT, src)
        os.makedirs(rp, exist_ok=True)
        open(os.path.join(rp, "index.html"), "w", encoding="utf-8").write(
            SM.redirect_html(dst))
    mode = "زنده" if LIVE else "پروتوتایپ"
    # کیت لاراول (laravel/) باید همیشه با همین پروتوتایپ یکی بماند.
    sys.path.insert(0, os.path.join(ROOT, "tools"))
    import build_kit
    build_kit.main()
    print(f"ساخته شد: {n} صفحه · فهرست جست‌وجو: {m} رکورد · "
          f"نقشه‌ی سایت: {count} نشانی ({mode})")


if __name__ == "__main__":
    main()
