# -*- coding: utf-8 -*-
"""
Keep the Laravel kit (laravel/) in lock-step with the prototype.

The prototype is the approved design. The kit is the same design running on
the real database. Everything the two share is generated from here, so they
cannot drift apart:

  laravel/resources/redesign/content.php   every piece of copy in build/content.py
                                           plus the lookup tables in tables.py,
                                           features.py and blog.py, as a PHP array
  laravel/resources/views/redesign/rd/icons.blade.php
                                           the inline SVG sprite
  laravel/public_html/rd/                  app.css, the JS, fonts, brand, photos,
                                           slides, factory media — with every
                                           /assets/ path rewritten to /rd/

/rd/ and not /assets/ because the Laravel app already owns public_html/assets
for the previous theme. Nothing is overwritten there.

Run:  python3 tools/build_kit.py          (build/gen.py also runs it at the end)
"""
import filecmp
import os
import re
import shutil
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.join(ROOT, "build"))

import content as C          # noqa: E402
import tables as T           # noqa: E402
import features as F         # noqa: E402
import blog as B             # noqa: E402
import pages as P            # noqa: E402
from common import UPDATE_TIME, PHOTOS   # noqa: E402
from icons import ICONS      # noqa: E402

KIT = os.path.join(ROOT, "laravel")
PUB = os.path.join(KIT, "public_html", "rd")
ASSET_PREFIX = "/rd/"

# Assets the kit ships. Blog images stay out: articles in the database carry
# their own images under /images/article/.
COPY_DIRS = ["fonts", "brand", "experts", "slides", "products", "factory"]
COPY_FILES = ["app.css", "site.js", "search.js", "chart.js", "table.js",
              "hero.js", "lightbox.js", "tools.js"]
REWRITE = {".css", ".js"}


# ---------------------------------------------------------------------------
# Python value -> PHP literal
# ---------------------------------------------------------------------------
def php(v, ind=0):
    pad, pad1 = "    " * ind, "    " * (ind + 1)
    if v is None:
        return "null"
    if v is True:
        return "true"
    if v is False:
        return "false"
    if isinstance(v, int):
        return str(v)
    if isinstance(v, float):
        return repr(v)
    if isinstance(v, str):
        return "'" + v.replace("\\", "\\\\").replace("'", "\\'") + "'"
    if isinstance(v, dict):
        if not v:
            return "[]"
        items = [f"{pad1}{php(k)} => {php(x, ind + 1)}," for k, x in v.items()]
        return "[\n" + "\n".join(items) + f"\n{pad}]"
    if isinstance(v, (list, tuple)):
        if not v:
            return "[]"
        items = [f"{pad1}{php(x, ind + 1)}," for x in v]
        return "[\n" + "\n".join(items) + f"\n{pad}]"
    raise TypeError(type(v))


def rd(path):
    """/assets/x -> /rd/x"""
    return re.sub(r"^/assets/", ASSET_PREFIX, path) if isinstance(path, str) else path


def slides():
    out = []
    for sl in C.SLIDES:
        style = P.slide_bg(sl.get("img")).replace("/assets/", ASSET_PREFIX)
        # slide_bg returns ` style="..."`; keep only the declaration list.
        m = re.search(r'style="([^"]*)"', style)
        out.append({"title": sl.get("title") or "", "alt": sl.get("alt") or "",
                    "cat": sl.get("cat") or "", "style": m.group(1) if m else ""})
    return out


def plants():
    """The three sites shown on the home and about pages."""
    return [
        {"video": "/rd/factory/toloue-sepahan-1.mp4", "poster": "/rd/factory/toloue-sepahan-1.jpg",
         "alt": "نمای هوایی کارخانه‌ی صنایع مفتولی طلوع سپاهان، واحد یک، شهرک صنعتی منتظریه نجف‌آباد",
         "title": "کارخانه — واحد یک",
         "sub": "اصفهان، شهرک صنعتی منتظریه (ویلاشهر)، خیابان قادری، پلاک ۱۸۱", "link": ""},
        {"video": "/rd/factory/toloue-sepahan-2.mp4", "poster": "/rd/factory/toloue-sepahan-2.jpg",
         "alt": "نمای هوایی کارخانه‌ی صنایع مفتولی طلوع سپاهان، واحد دو، شهرک صنعتی منتظریه نجف‌آباد",
         "title": "کارخانه — واحد دو",
         "sub": "اصفهان، کمربندی نجف‌آباد، شهرک صنعتی منتظریه، خیابان ۱۰۱", "link": ""},
        {"video": "/rd/factory/tehran-office.mp4", "poster": "/rd/factory/tehran-office.jpg",
         "alt": "نمای هوایی دفتر تهران سپاهان فلز در بازار آهن شادآباد، مجتمع پارس فلز",
         "title": "دفتر تهران",
         "sub": "بازار آهن شادآباد، بلوار شهید قربانخوانی، مجتمع پارس فلز، پلاک ۹",
         "link": P.TEHRAN_MAP},
    ]


def content():
    team = [dict(m, photo=rd(m["photo"])) for m in C.SALES_TEAM]
    return {
        "update_time": UPDATE_TIME,
        "basket_claim": P.SABAD,
        "phone": {"raw": C.PHONE_RAW, "show": C.PHONE_SHOW, "lines": C.PHONE_LINES,
                  "mobile_raw": C.MOBILE_RAW, "mobile_show": C.MOBILE_SHOW,
                  "whatsapp": C.WHATSAPP, "whatsapp_show": C.WA_SHOW},
        "email": "info@sepahanfelez.ir",
        "hours": "شنبه تا چهارشنبه ۸ تا ۱۷ — پنجشنبه ۸ تا ۱۳",
        "tehran_map": P.TEHRAN_MAP,
        "socials": [list(s) for s in C.SOCIALS],
        "addresses": [list(a) for a in C.ADDRESSES],
        "factory": C.FACTORY,
        "fact_numbers": [list(x) for x in C.FACT_NUMBERS],
        "trust": [list(x) for x in C.TRUST],
        "order": C.ORDER,
        "cats": {k: {**v,
                     "choose": [list(x) for x in v.get("choose", [])],
                     "faq": [list(x) for x in v.get("faq", [])]}
                 for k, v in C.CATS.items()},
        "deep": {k: [[t, ps] for t, ps in v] for k, v in C.DEEP.items()},
        "suspect": C.SUSPECT,
        "slides": slides(),
        "plants": plants(),
        "home_intro": C.HOME_INTRO,
        "catlist_intro": C.CATLIST_INTRO,
        "catlist_help": [list(x) for x in C.CATLIST_HELP],
        "about_sections": [[t, ps] for t, ps in C.ABOUT_SECTIONS],
        "contact_help": [list(x) for x in C.CONTACT_HELP],
        "blog_cat_intro": C.BLOG_CAT_INTRO,
        "sales_team": team,
        "assign": C.ASSIGN,
        "sales_unit": {**C.SALES_UNIT, "promise": [list(x) for x in C.SALES_UNIT["promise"]]},
        "freight": [list(x) for x in C.FREIGHT],
        "freight_min_ton": C.FREIGHT_MIN_TON,
        "sample_reviews": {k: [list(r) for r in v] for k, v in C.SAMPLE_REVIEWS.items()},
        "key_specs": T.KEY_SPECS,
        "short_head": T.SHORT_HEAD,
        "use": {k: list(v) for k, v in F._USE.items()},
        "chart_ranges": [list(x) for x in F.RANGES],
        "keywords": {k: [list(x) for x in v] for k, v in B.KEYWORDS.items()},
        "fallback_cats": B.FALLBACK,
        "photos": {k: [rd(p) for p in v] for k, v in PHOTOS.items()},
        "account": {**C.ACCOUNT,
                    "benefits": [list(x) for x in C.ACCOUNT["benefits"]],
                    "nav": [list(x) for x in C.ACCOUNT["nav"]],
                    **{k: {**C.ACCOUNT[k], "foot": list(C.ACCOUNT[k]["foot"])}
                       for k in ("login", "register", "verify")}},
    }


def write_if_changed(path, text):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    if os.path.exists(path) and open(path, encoding="utf-8").read() == text:
        return False
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(text)
    return True


def sync_assets():
    changed = 0
    src_root = os.path.join(ROOT, "assets")
    os.makedirs(PUB, exist_ok=True)
    wanted = set()
    for name in COPY_FILES:
        wanted.add(name)
        src, dst = os.path.join(src_root, name), os.path.join(PUB, name)
        text = open(src, encoding="utf-8").read().replace("/assets/", ASSET_PREFIX)
        changed += write_if_changed(dst, text)
    for d in COPY_DIRS:
        for base, _, files in os.walk(os.path.join(src_root, d)):
            for f in files:
                src = os.path.join(base, f)
                rel = os.path.relpath(src, src_root)
                wanted.add(rel)
                dst = os.path.join(PUB, rel)
                os.makedirs(os.path.dirname(dst), exist_ok=True)
                if os.path.splitext(f)[1] in REWRITE:
                    text = open(src, encoding="utf-8").read().replace("/assets/", ASSET_PREFIX)
                    changed += write_if_changed(dst, text)
                elif not (os.path.exists(dst) and filecmp.cmp(src, dst, shallow=False)):
                    shutil.copy2(src, dst)
                    changed += 1
    # Anything in the kit that the prototype no longer has goes too.
    for base, _, files in os.walk(PUB):
        for f in files:
            rel = os.path.relpath(os.path.join(base, f), PUB)
            if rel not in wanted:
                os.remove(os.path.join(base, f))
                changed += 1
    return changed


def main():
    header = ("<?php\n\n"
              "/*\n"
              " * GENERATED by tools/build_kit.py from the prototype's build/content.py.\n"
              " * Do not edit here — edit the prototype and rerun the tool, or the next\n"
              " * run overwrites the change. Loaded once per request by App\\Support\\Rd::c().\n"
              " */\n\n"
              "return ")
    body = header + php(content()) + ";\n"
    n1 = write_if_changed(os.path.join(KIT, "resources", "redesign", "content.php"), body)
    icons = ("{{-- GENERATED by tools/build_kit.py from build/icons.py --}}\n"
             + ICONS.strip() + "\n")
    n2 = write_if_changed(os.path.join(KIT, "resources", "views", "redesign", "rd", "icons.blade.php"), icons)
    n3 = sync_assets()
    print(f"kit: content.php {'updated' if n1 else 'unchanged'} · "
          f"icons {'updated' if n2 else 'unchanged'} · assets changed: {n3}")


if __name__ == "__main__":
    main()
