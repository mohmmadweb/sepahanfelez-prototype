# -*- coding: utf-8 -*-
"""نقشه‌ی سایت و robots.txt.

هر دو با توجه به SEPAHAN_LIVE ساخته می‌شوند: در حالت پروتوتایپ، robots
همه‌چیز را می‌بندد و نقشه‌ی سایت ساخته نمی‌شود؛ در حالت زنده هر دو درست
تولید می‌شوند. اولویت‌ها از روی اهمیت تجاری صفحه است نه حدس.
"""
import os
from datetime import date

SITE = "https://sepahanfelez.ir"


def _url(loc, lastmod, changefreq, priority, images=None):
    x = [f"  <url>\n    <loc>{loc}</loc>",
         f"    <lastmod>{lastmod}</lastmod>",
         f"    <changefreq>{changefreq}</changefreq>",
         f"    <priority>{priority}</priority>"]
    for im in (images or [])[:4]:
        x.append(f"    <image:image><image:loc>{SITE}{im}</image:loc></image:image>")
    x.append("  </url>")
    return "\n".join(x)


def build(C, CAT, cat_photo, u_cat, u_prod, u_article, u_blogcat, ARTS, BLOG_CATS):
    today = date.today().isoformat()
    out = []
    # قیمت هر روز ۱۲:۳۰ عوض می‌شود، پس صفحه‌های قیمت daily اند.
    out.append(_url(SITE + "/", today, "daily", "1.0"))
    out.append(_url(SITE + "/price", today, "daily", "0.9"))
    out.append(_url(SITE + "/category", today, "weekly", "0.8"))
    for key in C.ORDER:
        imgs = [cat_photo(key, i) for i in range(3)]
        imgs = [i for i in imgs if i]
        out.append(_url(SITE + u_cat(key), today, "daily", "0.9", imgs))
        for r in CAT[key]["rows"]:
            if r["نام محصول"] in C.SUSPECT:
                continue
            out.append(_url(SITE + u_prod(key, r["نام محصول"]), today, "daily", "0.7",
                            imgs[:1]))
    out.append(_url(SITE + "/blog", today, "weekly", "0.7"))
    for slug in BLOG_CATS:
        out.append(_url(SITE + u_blogcat(slug), today, "weekly", "0.5"))
    for a in ARTS:
        lm = (a.get("modified") or a.get("published") or today)[:10]
        im = [a["image"]] if (a.get("image") or "").startswith("/") else None
        out.append(_url(SITE + u_article(a["cat_slug"], a["slug"]), lm, "monthly", "0.6", im))
    out.append(_url(SITE + "/about", today, "monthly", "0.5"))
    out.append(_url(SITE + "/contact", today, "monthly", "0.5"))

    xml = ('<?xml version="1.0" encoding="UTF-8"?>\n'
           '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"\n'
           '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">\n'
           + "\n".join(out) + "\n</urlset>\n")
    return xml, len(out)


def robots(live):
    if not live:
        return ("# پروتوتایپ طراحی — نباید ایندکس شود.\n"
                "# محتوای این سایت عیناً همان محصولات sepahanfelez.ir است؛\n"
                "# ایندکس‌شدنش محتوای تکراری می‌سازد و به رتبه‌ی سایت اصلی آسیب می‌زند.\n"
                "User-agent: *\nDisallow: /\n")
    return ("User-agent: *\n"
            "Allow: /\n"
            "# مسیرهای بی‌ارزش برای موتور جست‌وجو\n"
            "Disallow: /assets/search-index.json\n"
            "\n"
            f"Sitemap: {SITE}/sitemap.xml\n")
