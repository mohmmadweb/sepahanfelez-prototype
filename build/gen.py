# -*- coding: utf-8 -*-
"""
سازنده‌ی بسته‌ی محتوا و کیت لاراول (صفحه‌های نمایشی از tools/export_static.py می‌آیند).

متن قدیمی زیر برای سابقه مانده است:
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
    """Content → admin pack, and theme → Laravel kit.

    The pages of the prototype are NOT written here any more. They are a
    snapshot of the Laravel kit running on a database filled through the
    admin panel (tools/export_static.py), so the demo can never differ from
    what goes live. build/content.py stays the source of the approved copy:
    tools/admin_pack.py turns it into admin-panel fields (migration/pack.json).

    Full rebuild of the demo:
        python3 build/gen.py              # pack + kit
        (lab: tools/lab/README.md)        # seed the lab from the pack
        python3 tools/export_static.py    # crawl the lab into the repo
    """
    sys.path.insert(0, os.path.join(ROOT, "tools"))
    import build_kit
    import admin_pack
    build_kit.main()
    admin_pack.main()


if __name__ == "__main__":
    main()
