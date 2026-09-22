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
    import searchindex as SI
    m = SI.write(os.path.join(ROOT, "assets", "search-index.json"))
    print(f"ساخته شد: {n} صفحه · فهرست جست‌وجو: {m} رکورد")


if __name__ == "__main__":
    main()
