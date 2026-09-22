# -*- coding: utf-8 -*-
"""فهرست جست‌وجوی سراسری سایت.

یک فایل JSON ساخته می‌شود که همه‌ی چیزهای قابل جست‌وجو را دارد: محصول،
دسته، مقاله‌ی مجله، و صفحه‌های ثابت. مرورگر همین یک فایل را می‌گیرد و
جست‌وجو تماماً سمت کاربر انجام می‌شود — بدون سرور، بدون تأخیر شبکه در هر
حرف. بعد از اتصال به بک‌اند، همین ساختار را می‌شود از یک اندپوینت
لاراول داد و کد سمت مرورگر دست‌نخورده می‌ماند.

هر رکورد: t=عنوان، u=نشانی، k=نوع، s=زیرعنوان، x=متن کمکی برای تطبیق،
p=قیمت (فقط محصول)، w=وزن رتبه‌بندی.
"""
import json
import re

import content as C
from common import (CAT, fa, price_of, unit_of, clean_name, u_cat, u_prod,
                    u_article, u_blogcat, u_blog, u_catlist)
from blog import ARTS as ARTICLES

# نوع‌ها به ترتیب اهمیت برای کاربری که دنبال قیمت است
W_PRODUCT, W_CATEGORY, W_ARTICLE, W_PAGE = 100, 90, 60, 50


def _plain(html):
    """متن ساده از HTML، برای تطبیق در متن مقاله."""
    txt = re.sub(r"<[^>]+>", " ", html or "")
    txt = re.sub(r"\s+", " ", txt)
    return txt.strip()


def build():
    out = []

    # ── دسته‌ها ──────────────────────────────────────────────────────────
    for key, c in C.CATS.items():
        rows = CAT[key]["rows"]
        # alias رشته است نه لیست؛ " ".join روی رشته حرف‌حرفش می‌کرد
        alias = c.get("alias") or ""
        out.append({
            "t": c["title"], "u": u_cat(key), "k": "cat",
            "s": f"{fa(len(rows))} نوع کالا",
            "x": f"{alias} {c.get('lede','')}"[:300],
            "w": W_CATEGORY,
        })

    # ── محصول‌ها ────────────────────────────────────────────────────────
    for key, c in C.CATS.items():
        for r in CAT[key]["rows"]:
            name = r["نام محصول"]
            if name in C.SUSPECT:
                continue
            p = price_of(r)
            out.append({
                # ارقام فارسی، هماهنگ با بقیه‌ی متن‌های سایت. نام خام در x
                # می‌ماند تا کسی که لاتین تایپ می‌کند هم نتیجه بگیرد.
                "t": fa(clean_name(name)), "u": u_prod(key, name), "k": "prod",
                "s": c["title"],
                "x": name,
                "p": p or 0, "unit": unit_of(r),
                "w": W_PRODUCT,
            })

    # ── مجله ────────────────────────────────────────────────────────────
    for a in ARTICLES:
        out.append({
            "t": a["title"], "u": u_article(a["cat_slug"], a["slug"]), "k": "art",
            "s": a["cat_title"],
            # فقط ابتدای متن؛ کل بدنه فایل را بی‌دلیل سنگین می‌کند
            "x": (a.get("description") or "") + " " + _plain(a.get("body"))[:400],
            "w": W_ARTICLE,
        })

    # دسته‌های مجله
    seen = {}
    for a in ARTICLES:
        seen.setdefault(a["cat_slug"], a["cat_title"])
    for slug, title in seen.items():
        out.append({"t": f"مجله — {title}", "u": u_blogcat(slug), "k": "blogcat",
                    "s": "دسته‌ی مقالات", "x": title, "w": W_PAGE})

    # ── صفحه‌های ثابت ───────────────────────────────────────────────────
    for t, u, s, x in (
        ("قیمت لحظه‌ای", "/price", "جدول قیمت همه‌ی کالاها",
         "قیمت روز لیست قیمت نرخ امروز استعلام"),
        ("دسته‌های محصول", u_catlist(), "همه‌ی دسته‌ها", "محصولات لیست کالا"),
        ("مجله سپاهان فلز", u_blog(), "مقالات تخصصی", "بلاگ اخبار مقاله وبلاگ"),
        ("درباره کارخانه", "/about", "صنایع مفتولی طلوع سپاهان",
         "درباره ما کارخانه تولید سابقه"),
        ("تماس با ما", "/contact", "دفتر فروش و کارخانه",
         "تماس شماره آدرس تلفن دفتر تهران اصفهان"),
    ):
        out.append({"t": t, "u": u, "k": "page", "s": s, "x": x, "w": W_PAGE})

    return out


def write(path="assets/search-index.json"):
    data = build()
    with open(path, "w", encoding="utf-8") as fh:
        json.dump(data, fh, ensure_ascii=False, separators=(",", ":"))
    return len(data)
