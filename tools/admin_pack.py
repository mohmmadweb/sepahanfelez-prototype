# -*- coding: utf-8 -*-
"""
The prototype's approved content, expressed as admin-panel fields.

The live site is managed entirely from /admin. So the prototype's copy is not
shipped as a file: it is written ONCE into the panel's own fields at go-live
(tools/apply_pack.py does that through the panel's forms), and from then on
the admin edits it there like everything else.

Output: migration/pack.json — one entry per admin screen:

  categories[slug]  admin → دسته‌بندی → ویرایش   intro, body, meta_title,
                                                 meta_description, meta_keywords, image
  products[slug][title]  admin → محصول → محتوا    image
  home_setting      admin → تنظیمات صفحه اصلی    home/price SEO, about (HTML), about_pic
  information       admin → اطلاعات تماس         phone, email, work_time, addresses, about
  about             admin → درباره ما            text (HTML), image, video
  sliders           admin → اسلایدر              image, link, alt (the caption)
  videos            admin → ویدئوها              factory videos
  socials           admin → شبکه‌های اجتماعی      url + active, matched by network

Media are referenced by their path in this repository (assets/…), not copied.

Body HTML uses only what the panel's editor keeps: h2, h3, p, ul, li, b,
strong, a. The Laravel templates turn each <h2> into a section, and an <h2>
«پرسش‌های پرتکرار…» with <h3> questions into the FAQ accordion + FAQPage data.

Run:  python3 tools/admin_pack.py
"""
import html
import json
import os
import sys
from urllib.parse import quote

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.join(ROOT, "build"))

import content as C              # noqa: E402
from common import CAT, PHOTOS, SLUGMAP as SLUGS, slugify, clean_name   # noqa: E402

OUT = os.path.join(ROOT, "migration", "pack.json")
BRAND = "سپاهان فلز"
SITE = "https://sepahanfelez.ir"


def esc(s):
    return html.escape(str(s), quote=False)


def paras(items):
    """Items are already HTML-safe copy from content.py (they may hold <b>)."""
    return "".join(f"<p>{x}</p>" for x in items)


def asset(web_path):
    """/assets/x → assets/x, checked to exist."""
    rel = web_path.lstrip("/")
    if not os.path.exists(os.path.join(ROOT, rel)):
        raise FileNotFoundError(rel)
    return rel


def category_body(key):
    c = C.CATS[key]
    t = c["title"]
    out = [f"<h2>{esc(t)} چیست و کجا به کار می‌آید</h2>", paras(c.get("intro", []))]
    if c.get("pricing"):
        out += [f"<h2>{esc(c['pricing_title'])}</h2>", paras(c["pricing"]),
                "<p><b>قیمت‌ها به ریال است.</b> اگر با سایتی که تومانی کار می‌کند مقایسه می‌کنید، "
                "به واحد توجه کنید؛ عدد ریالی ده برابر عدد تومانی است.</p>"]
    if c.get("choose"):
        out.append(f"<h2>{esc(c['choose_title'])}</h2>")
        for h, d in c["choose"]:
            out.append(f"<h3>{esc(h)}</h3><p>{d}</p>")
    if c.get("mistakes"):
        out.append(f"<h2>اشتباه‌های رایج در خرید {esc(t)}</h2>")
        out.append("<ul>" + "".join(f"<li>{m}</li>" for m in c["mistakes"]) + "</ul>")
    for h, ps in C.DEEP.get(key, []):
        out += [f"<h2>{esc(h)}</h2>", paras(ps)]
    if c.get("faq"):
        out.append(f"<h2>پرسش‌های پرتکرار درباره‌ی {esc(t)}</h2>")
        for q, a in c["faq"]:
            out.append(f"<h3>{esc(q)}</h3><p>{esc(a)}</p>")
    return "".join(out)


def categories():
    out = {}
    for key in C.ORDER:
        c = C.CATS[key]
        photos = PHOTOS.get(key) or []
        out[key] = {
            "body": category_body(key),
            "meta_title": f"قیمت روز {c['title']} | {BRAND}"[:170],
            # Also the subtitle under the H1: the panel has no editable intro.
            "meta_description": c["meta"][:260],
            "meta_keywords": (c.get("alias") or "")[:191],
            "image": asset(photos[0]) if photos else None,
        }
    return out


# Words that carry no information inside a product slug; dropped first when
# a slug is over the panel's 30-character limit.
_FILLER = ["سانتیمتر", "سانتی", "امتر", "متر", "عرض", "ارتفاع", "کیلو", "وزن", "توری", "مفتول", "چشمه"]


def short_slug(name, taken):
    """A product slug the panel accepts: at most 30 characters, unique.

    admin → محصول → محتوا validates slug as required|max:30 and refuses a slug
    another product already has, so the prototype's long slugs cannot be used
    as they are. Filler words go first, then the tail is cut on a hyphen.
    """
    base = slugify(clean_name(name).replace("×", "x").translate(str.maketrans("۰۱۲۳۴۵۶۷۸۹", "0123456789")))
    parts = [p for p in base.split("-") if p]
    for w in _FILLER:
        if len("-".join(parts)) <= 30:
            break
        if len(parts) > 2 and w in parts[1:]:
            parts.remove(w)
    slug = "-".join(parts)
    while len(slug) > 30 and "-" in slug:
        slug = slug.rsplit("-", 1)[0]
    slug = slug[:30].strip("-")
    cand, n = slug, 2
    while cand in taken:
        suffix = f"-{n}"
        cand = slug[:30 - len(suffix)].strip("-") + suffix
        n += 1
    taken.add(cand)
    return cand


def products():
    """Each product gets a real photo of its own category, in turn, and a slug
    so it gets its own page (a product without a slug has none)."""
    out = {}
    taken = set(v for k, v in SLUGS.items() if not k.startswith("_"))
    for key in C.ORDER:
        rows = (CAT.get(key) or {}).get("rows") or []
        photos = PHOTOS.get(key) or []
        if not photos:
            continue
        out[key] = {}
        for i, r in enumerate(rows):
            name = r["نام محصول"]
            slug = SLUGS.get(name) or short_slug(name, taken)
            out[key][name] = {"slug": slug, "image": asset(photos[i % len(photos)])}
    return out


def home_setting():
    trust = "".join(f"<li><b>{esc(t)}.</b> {esc(d)}</li>" for _, t, d in C.TRUST)
    facts = "".join(f"<li><b>{esc(v)}</b> — {esc(k)}</li>" for v, k in C.FACT_NUMBERS)
    about = (paras(C.HOME_INTRO)
             + "<h2>چرا خرید از طلوع سپاهان فرق دارد</h2>"
             + f"<ul>{trust}</ul>"
             + "<h2>کارخانه در یک نگاه</h2>"
             + f"<ul>{facts}</ul>")
    return {
        "home_title": f"{BRAND} — قیمت روز صنایع مفتولی طلوع سپاهان"[:180],
        "home_description": "قیمت روز توری حصاری، پرسی، مش جوشی، مرغی، گابیون و سیم خاردار مستقیم از کارخانه، "
                            "با مشخصات فنی و بروزرسانی روزانه."[:200],
        "price_title": f"قیمت لحظه‌ای صنایع مفتولی | {BRAND}"[:180],
        "price_description": "جدول قیمت لحظه‌ای محصولات مفتولی طلوع سپاهان با مشخصات فنی و آخرین تغییرات "
                             "قیمت؛ قیمت به ریال و مبنای روز."[:200],
        "about": about,
        "about_pic": asset("/assets/factory/toloue-sepahan-1.jpg"),
        "alt_about_pic": "نمای هوایی کارخانه‌ی صنایع مفتولی طلوع سپاهان",
    }


def information():
    tehran = next((f"{t}: {v}" for t, v in C.ADDRESSES if "تهران" in t), "")
    factories = [f"{t}: {v}" for t, v in C.ADDRESSES if "تهران" not in t]
    return {
        "phone": C.PHONE_RAW,
        "email": "info@sepahanfelez.ir",
        "work_time": "شنبه تا چهارشنبه ۸ تا ۱۷ — پنجشنبه ۸ تا ۱۳",
        "main_address": tehran[:400],
        "factory_address_1": (factories[0] if len(factories) > 0 else "")[:400],
        "factory_address_2": (factories[1] if len(factories) > 1 else "")[:400],
        # The Khavarshahr warehouse is no longer in use (owner's instruction).
        "factory_address_3": "",
        "about": f"<p>فروشگاه اینترنتی کارخانه‌ی <b>{esc(C.FACTORY['name'])}</b> — "
                 "کامل‌ترین سبد کالایی صنایع مفتولی کشور.</p>",
    }


def about():
    F = C.FACTORY
    facts = "".join(f"<li><b>{esc(k)}:</b> {esc(v)}</li>" for k, v in [
        ("سال تأسیس", F["year"]), ("شماره ثبت", F["reg"]), ("ظرفیت سالانه", F["capacity"]),
        ("سالن تولید", F["hall"]), ("پرسنل تولید", F["staff"])])
    text = (f"<p><b>سپاهان فلز</b> فروشگاه اینترنتی <b>{esc(F['name'])}</b> است؛ آنچه در این سایت "
            "سفارش می‌دهید از خط تولید همین کارخانه می‌آید و امکان خرید مستقیم از کارخانه و انبار تهران را دارید.</p>"
            f"<p>کارخانه در سال {F['year']} با شماره ثبت {F['reg']} و مجوز رسمی وزارت صنایع و معادن در استان اصفهان "
            f"آغاز به کار کرد و امروز با {F['hall']} سالن تولید، {F['staff']} پرسنل تولید و ظرفیت سالانه‌ی "
            f"{F['capacity']} کار می‌کند.</p>"
            f"<h2>کارخانه در یک نگاه</h2><ul>{facts}</ul>"
            + "".join(f"<h2>{esc(t)}</h2>" + paras(ps) for t, ps in C.ABOUT_SECTIONS))
    return {
        "text": text,
        "image": asset("/assets/factory/toloue-sepahan-1.jpg"),
        # About.video is a path; the file is uploaded through admin → ویدئوها
        # first, and apply_pack writes the stored name here.
        "video_upload": asset("/assets/factory/toloue-sepahan-1.mp4"),
        "canonical": f"{SITE}/about",
    }


def sliders():
    out = []
    for sl in C.SLIDES:
        img = sl.get("img") or ""
        stem = img.rsplit(".", 1)[0]
        jpg = f"/assets/slides/{stem}.jpg"
        # The panel validates the link as a URL of at most 150 characters, so
        # the Persian path is percent-encoded; a slug too long for that links
        # to the price page instead.
        link = f"{SITE}/category/{quote(sl['cat'])}" if sl.get("cat") else f"{SITE}/price"
        if len(link) > 150:
            link = f"{SITE}/price"
        out.append({"image": asset(jpg),
                    "link": link,
                    "alt": sl.get("title") or ""})
    return out


def videos():
    return [asset(f"/assets/factory/{n}.mp4") for n in ("toloue-sepahan-1", "toloue-sepahan-2", "tehran-office")]


def socials():
    """Matched to the socials rows by network; networks not listed are switched off."""
    urls = {"whatsapp": f"https://wa.me/{C.MOBILE_RAW}", "telegram": f"https://t.me/+{C.MOBILE_RAW}",
            "instagram": f"https://ig.me/m/+{C.MOBILE_RAW}"}
    return {"active": urls, "deactivate_others": True}


def main():
    pack = {
        "_readme": "Content of the approved prototype as admin-panel fields. Applied once with "
                   "tools/apply_pack.py; afterwards edit everything in /admin.",
        "categories": categories(),
        "products": products(),
        "home_setting": home_setting(),
        "information": information(),
        "about": about(),
        "sliders": sliders(),
        "videos": videos(),
        "socials": socials(),
    }
    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, "w", encoding="utf-8") as fh:
        json.dump(pack, fh, ensure_ascii=False, indent=1)
    n_prod = sum(len(v) for v in pack["products"].values())
    print(f"pack: {len(pack['categories'])} categories · {n_prod} product images · "
          f"{len(pack['sliders'])} slides · {len(pack['videos'])} videos → {os.path.relpath(OUT, ROOT)}")


if __name__ == "__main__":
    main()
