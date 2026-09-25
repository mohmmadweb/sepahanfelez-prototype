"""Turn a read-only export of sepahanfelez.ir/admin into table rows.

    python3 tools/staging/live_dataset.py <export-dir> <out.json>

<export-dir> is what tools/staging/live_export.py wrote: one JSON per admin
page (forms with their current values, list tables, image paths). The output
is {"tables": {name: [row, ...]}, "files": [public path, ...]} with the live
ids kept, so the staging copy lines up with the live panel one to one.

Personal data is never written: no users, orders, tickets, messages,
newsletter or collaboration rows, and comments keep only the name, text and
answer (no phone, no e-mail).
"""
import glob
import json
import os
import re
import sys

EXP, OUT = sys.argv[1], sys.argv[2]
NOW = "2026-09-25 12:00:00"
ADMIN = 1

MONTHS = {m: i + 1 for i, m in enumerate(
    "فروردین اردیبهشت خرداد تیر مرداد شهریور مهر آبان آذر دی بهمن اسفند".split())}
FA = str.maketrans("۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩", "01234567890123456789")


def j2g(jy, jm, jd):
    jy += 1595
    days = -355668 + 365 * jy + (jy // 33) * 8 + ((jy % 33) + 3) // 4 + jd
    days += (jm - 1) * 31 if jm < 7 else (jm - 7) * 30 + 186
    gy = 400 * (days // 146097)
    days %= 146097
    if days > 36524:
        days -= 1
        gy += 100 * (days // 36524)
        days %= 36524
        if days >= 365:
            days += 1
    gy += 4 * (days // 1461)
    days %= 1461
    if days > 365:
        gy += (days - 1) // 365
        days = (days - 1) % 365
    gd = days + 1
    leap = (gy % 4 == 0 and gy % 100 != 0) or gy % 400 == 0
    for gm, n in enumerate([31, 29 if leap else 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31], 1):
        if gd <= n:
            return gy, gm, gd
        gd -= n


def jdate(s):
    """'15:16 \xa0 20-خرداد-1405' or '26 مهر 1400 19:55' -> 'Y-m-d H:i:s'."""
    s = (s or "").translate(FA)
    m = re.search(r"(\d{1,2})[-\s]+([^\d\s-]+)[-\s]+(\d{4})", s)
    if not m or m.group(2) not in MONTHS:
        return NOW
    t = re.search(r"(\d{1,2}):(\d{2})", s)
    y, mo, d = j2g(int(m.group(3)), MONTHS[m.group(2)], int(m.group(1)))
    return "%04d-%02d-%02d %02d:%02d:00" % (y, mo, d, int(t.group(1)) if t else 12, int(t.group(2)) if t else 0)


def load(name):
    f = os.path.join(EXP, name + ".json")
    return json.load(open(f)) if os.path.exists(f) else None


def pages(name):
    out = []
    for f in [os.path.join(EXP, name + ".json")] + sorted(glob.glob(os.path.join(EXP, name + "_p*.json"))):
        if os.path.exists(f):
            out.append(json.load(open(f)))
    return out


def rows(pgs):
    for d in pgs:
        for t in d["tables"]:
            for r in t:
                if r["links"] or r["imgs"]:
                    yield r


def form(d, action_re):
    for f in (d or {}).get("forms", []):
        if re.search(action_re, f["action"]) and f["method"] == "post":
            return f["fields"]
    return {}


def sel(v, many=False):
    """Selected value(s) of a <select> field as the exporter stored it."""
    if isinstance(v, list):
        vals = [o["v"] if isinstance(o, dict) else o for o in v]
        return vals if many else (vals[0] if vals else None)
    return v


def multi(v):
    if v is None:
        return []
    if isinstance(v, list):
        out = []
        for x in v:
            out += multi(x) if isinstance(x, list) else [x["v"] if isinstance(x, dict) else x]
        return out
    return [v]


def num(s):
    s = str(s or "").translate(FA)
    d = re.sub(r"[^0-9]", "", s)
    return int(d) if d else 0


def base(path):
    return os.path.basename(path or "")


T = {k: [] for k in ["specs", "values", "categories", "category_spec", "features", "usages", "products",
                     "product_spec_value", "prices", "article_categories", "articles", "tags", "taggables",
                     "redirects", "sliders", "socials", "information", "home_settings", "abouts",
                     "general_settings", "tutorials", "product_comments", "article_comments", "discounts", "videos"]}
FILES = set()


def img(path, folder_hint=None):
    if path and not path.startswith("http") or (path or "").startswith("https://sepahanfelez.ir/"):
        p = re.sub(r"^https://sepahanfelez\.ir", "", path)
        if "nopic" not in p and "no-picture" not in p:
            FILES.add(p)
        return p
    return path


# --- specs and values -------------------------------------------------------
for r in rows(pages("spec")):
    m = [re.search(r"/spec/(\d+)/value$", h) for h in r["links"]]
    m = next((x for x in m if x), None)
    if m:
        sid = int(m.group(1))
        T["specs"].append({"id": sid, "title": r["cells"][1], "updated_at": jdate(r["cells"][2])})
        for vr in rows(pages(f"spec_{sid}_values")):
            vm = next((re.search(r"/value/(\d+)/edit$", h) for h in vr["links"] if re.search(r"/value/(\d+)/edit$", h)), None)
            if vm:
                T["values"].append({"id": int(vm.group(1)), "spec_id": sid, "title": vr["cells"][1],
                                    "updated_at": jdate(vr["cells"][2])})

# --- categories -------------------------------------------------------------
index_of = {}
for r in rows(pages("category")):
    m = next((re.search(r"/category/(\d+)/edit$", h) for h in r["links"] if re.search(r"/category/(\d+)/edit$", h)), None)
    if m:
        index_of[int(m.group(1))] = (r["cells"][5].strip().lower() == "index", jdate(r["cells"][3]))

cat_tags = {}
for cid, (indexed, updated) in index_of.items():
    d = load(f"cat_{cid}_edit")
    f = form(d, rf"/category/{cid}$")
    if not f:
        continue
    imgs = d["imgs"]
    icon = next((img(i) for i in imgs if "/category/icon/" in i), None)
    image = next((img(i) for i in imgs if "/category/main/" in i), None)
    parent = int(sel(f.get("parent_id")) or 0)
    T["categories"].append({
        "id": cid, "parent_id": parent or None, "user_id": ADMIN, "title": f["title"], "slug": f["slug"],
        "order": int(f["order"]) if str(f.get("order", "")).strip().isdigit() else None,
        "our_product": int(sel(f.get("our_product")) or 1), "status": 1, "index_by_crawler": int(indexed),
        "body": f.get("body"), "meta_title": f.get("meta_title"), "meta_description": f.get("meta_description"),
        "meta_keywords": f.get("meta_keywords"), "keyword": f.get("keyword"), "canonical": f.get("canonical") or None,
        "schema_tag": f.get("schema_tag"), "video": f.get("video") or None, "video_embed": f.get("video_embed") or None,
        "image": base(image) or None, "icon": base(icon) or None, "updated_at": updated,
    })
    sort = {int(k[5:-1]): int(v or 0) for k, v in form(load(f"cat_{cid}_columns"), r"/columns$").items()
            if k.startswith("spec[")}
    for sid in multi(f.get("specs[]")):
        T["category_spec"].append({"category_id": cid, "spec_id": int(sid), "sort": sort.get(int(sid), 0)})
    cat_tags[cid] = [int(t) for t in multi(f.get("tags[]"))]

    for kind in ("feature", "usage"):
        for r in rows(pages(f"cat_{cid}_{kind}")):
            m = next((re.search(rf"/{kind}/(\d+)/edit$", h) for h in r["links"] if re.search(rf"/{kind}/(\d+)/edit$", h)), None)
            if not m:
                continue
            fid = int(m.group(1))
            fd = load(f"cat_{cid}_{kind}_{fid}")
            ff = form(fd, rf"/{kind}/{fid}$")
            pic = next((img(i) for i in (r["imgs"] + (fd or {}).get("imgs", []))), "")
            T[kind + "s"].append({"id": fid, "category_id": cid, "title": ff.get("title") or r["cells"][2],
                                  "description": ff.get("description"), "picture": base(pic)})

    # --- products
    for r in rows(pages(f"cat_{cid}_products")):
        m = next((re.search(r"/product/(\d+)/edit$", h) for h in r["links"] if re.search(r"/product/(\d+)/edit$", h)), None)
        if not m:
            continue
        pid = int(m.group(1))
        e = form(load(f"prod_{pid}_edit"), rf"/product/{pid}$")
        cd = load(f"prod_{pid}_content")
        c = form(cd, rf"/product/{pid}/content$")
        if not e:
            continue
        pimg = next((img(i) for i in (cd or {}).get("imgs", []) if "/products/" in i and "nopic" not in i), None)
        T["products"].append({
            "id": pid, "category_id": cid, "title": e["title"], "price": num(e.get("price")),
            "unit": e.get("unit") or "", "order": int(e["order"]) if str(e.get("order", "")).strip().isdigit() else None,
            "status": int(sel(e.get("status")) or 0), "slug": c.get("slug") or None, "image": base(pimg) or None,
            "meta_title": c.get("meta_title") or None, "meta_keyword": c.get("meta_keyword") or None,
            "meta_description": c.get("meta_description") or None, "canonical": c.get("canonical") or None,
            "description": c.get("description") or None, "index_by_crawler": 1,
        })
        for k, v in e.items():
            mm = re.match(r"spec\[(\d+)\]$", k)
            vid = sel(v)
            if mm and vid not in (None, "", "0"):
                T["product_spec_value"].append({"product_id": pid, "spec_id": int(mm.group(1)), "value_id": int(vid)})
        for pr in rows(pages(f"prod_{pid}_price")):
            pm = next((re.search(r"/price/(\d+)/edit$", h) for h in pr["links"] if re.search(r"/price/(\d+)/edit$", h)), None)
            if pm:
                at = jdate(pr["cells"][3])
                T["prices"].append({"id": int(pm.group(1)), "product_id": pid, "first_price": num(pr["cells"][1]),
                                    "second_price": num(pr["cells"][2]), "price_at": at, "created_at": at, "updated_at": at})

# --- tags -------------------------------------------------------------------
for r in rows(pages("admin_tag")):
    m = next((re.search(r"/tag/(\d+)/edit$", h) for h in r["links"] if re.search(r"/tag/(\d+)/edit$", h)), None)
    if m:
        T["tags"].append({"id": int(m.group(1)), "title": r["cells"][1], "slug": r["cells"][2]})
for cid, tags in cat_tags.items():
    for t in tags:
        T["taggables"].append({"tag_id": t, "taggable_id": cid, "taggable_type": "App\\Models\\Category"})

# --- magazine ---------------------------------------------------------------
for r in rows(pages("admin_article_category")):
    m = next((re.search(r"/article-category/(\d+)/edit$", h) for h in r["links"] if re.search(r"/article-category/(\d+)/edit$", h)), None)
    if not m:
        continue
    aid = int(m.group(1))
    f = form(load(f"acat_{aid}_edit"), rf"/article-category/{aid}$")
    T["article_categories"].append({"id": aid, "user_id": ADMIN, "title": f.get("title") or r["cells"][1],
                                    "slug": f.get("slug") or r["cells"][1], "meta_title": f.get("meta_title"),
                                    "meta_description": f.get("meta_description"), "meta_keywords": f.get("meta_keywords"),
                                    "keyword": f.get("keyword"), "canonical": f.get("canonical") or None,
                                    "schema_tag": f.get("schema_tag")})
for r in rows(pages("admin_article") + pages("article")):
    m = next((re.search(r"/article/(\d+)/edit$", h) for h in r["links"] if re.search(r"/article/(\d+)/edit$", h)), None)
    if not m:
        continue
    aid = int(m.group(1))
    if any(a["id"] == aid for a in T["articles"]):
        continue
    d = load(f"art_{aid}_edit")
    f = form(d, rf"/article/{aid}$")
    if not f:
        continue
    aimg = next((img(i) for i in d["imgs"] if "/articles/" in i or "/article/" in i), None)
    T["articles"].append({
        "id": aid, "user_id": ADMIN, "category_id": int(sel(f.get("category_id")) or 0), "title": f["title"],
        "slug": f.get("slug"), "description": f.get("description"), "body": f.get("body") or "",
        "meta_title": f.get("meta_title"), "meta_description": f.get("meta_description"),
        "meta_keywords": f.get("meta_keywords"), "keyword": f.get("keyword"), "canonical": f.get("canonical") or None,
        "schema_tag": f.get("schema_tag"), "index_by_crawler": int(r["cells"][6].strip().lower() == "index"),
        "image": base(aimg) or "no-picture.jpg", "created_at": jdate(r["cells"][4]), "updated_at": jdate(r["cells"][5]),
    })
    for t in multi(f.get("tags[]")):
        T["taggables"].append({"tag_id": int(t), "taggable_id": aid, "taggable_type": "App\\Models\\Article"})

# --- settings ---------------------------------------------------------------
f = form(load("admin_informarion"), r"/informarion$")
T["information"].append({"id": 1, **{k: f.get(k) or "" for k in ["main_address", "factory_address_1", "factory_address_2",
                                                                  "factory_address_3", "email", "phone", "fax", "work_time", "about"]}})
d = load("admin_home_setting")
f = form(d, r"/home_setting$")
hi = [img(i) for i in d["imgs"]]
T["home_settings"].append({"id": 1, **{k: f.get(k) for k in ["about", "alt_about_pic", "url_about_pic", "alt_footer_pic1",
                                                              "url_footer_pic1", "alt_footer_pic2", "url_footer_pic2", "home_title",
                                                              "home_description", "home_canonical", "price_title",
                                                              "price_description", "price_canonical"]},
                           "about_pic": base(hi[0]) if len(hi) > 0 else "", "footer_pic1": base(hi[1]) if len(hi) > 1 else "",
                           "footer_pic2": base(hi[2]) if len(hi) > 2 else ""})
d = load("admin_about")
f = form(d, r"/about$")
T["abouts"].append({"id": 1, "text": f.get("text") or "", "video": f.get("video") or "", "canonical": f.get("canonical") or "",
                    "image": base(img(d["imgs"][0])) if d["imgs"] else ""})
f = form(load("admin_general_setting"), r"/general_setting$")
T["general_settings"].append({"id": 1, "bussiness_discount": float(f.get("bussiness_discount") or 0),
                              "min_expect": float(f.get("min_expect") or 0), "min_expect_discount": int(f.get("min_expect_discount") or 0),
                              "company_name": f.get("company_name") or "", "favicon": "favicon.ico"})
FILES.add("/favicon.ico")
f = form(load("admin_social"), r"/social$")
names = ["واتساپ", "تلگرام", "اینستاگرام", "یوتیوب", "فیسبوک", "توییتر", "لینکدین", "سایر"]
icons = ["whatsapp", "telegram", "instagram", "youtube", "facebook", "twitter", "linkedin", "link"]
for i in range(1, 9):
    if f"url[{i}]" in f:
        T["socials"].append({"id": i, "title": names[i - 1], "icon": f'<i class="bi bi-{icons[i - 1]}"></i>',
                             "url": f.get(f"url[{i}]") or None, "is_active": int(f"is_active[{i}]" in f)})
f = form(load("admin_tutorial"), r"/tutorial$")
T["tutorials"].append({"id": 1, "title": f.get("title"), "video": f.get("video"), "body": f.get("body")})
d = load("admin_setting_slider")
for r in rows([d]):
    m = next((re.search(r"/slider/(\d+)/edit$", h) for h in r["links"] if re.search(r"/slider/(\d+)/edit$", h)), None)
    if m:
        sid = int(m.group(1))
        sf = form(load(f"slider_{sid}_edit"), rf"/slider/{sid}$")
        T["sliders"].append({"id": sid, "image": base(img(r["imgs"][0])) if r["imgs"] else "", "link": sf.get("link") or "",
                             "alt": sf.get("alt") or r["cells"][2]})
for r in rows(pages("admin_redirect")):
    m = next((re.search(r"/redirect/(\d+)/edit$", h) for h in r["links"] if re.search(r"/redirect/(\d+)/edit$", h)), None)
    if m:
        T["redirects"].append({"id": int(m.group(1)), "defined_by": ADMIN, "from": r["cells"][1], "to": r["cells"][2],
                               "is_active": 1, "created_at": jdate(r["cells"][4])})
for r in rows(pages("admin_discount")):
    m = next((re.search(r"/discount/(\d+)/edit$", h) for h in r["links"] if re.search(r"/discount/(\d+)/edit$", h)), None)
    if m:
        pct = "درصد" in r["cells"][5]
        T["discounts"].append({"id": int(m.group(1)), "user_id": ADMIN, "code": r["cells"][1], "amount": num(r["cells"][6]),
                               "start_at": jdate(r["cells"][3]), "finish_at": jdate(r["cells"][4]),
                               "discount_type": "1" if pct else "0", "status": "0" if "غیر" in r["cells"][7] else "1"})
for v in (load("admin_video") or {}).get("videos", []):
    T["videos"].append({"video": base(img(v))})

# --- comments: name, text and answer only -----------------------------------
for key, table, fk in (("category_comment", "product_comments", "category_id"), ("article_comments", "article_comments", "article_id")):
    for r in rows(pages("admin_" + key)):
        m = next((re.search(rf"/{key}/(\d+)/edit$", h) for h in r["links"] if re.search(rf"/{key}/(\d+)/edit$", h)), None)
        if not m:
            continue
        cid = int(m.group(1))
        f = form(load(f"{key}_{cid}_edit"), rf"/{key}/{cid}$")
        target = sel(f.get(fk)) if f.get(fk) is not None else None
        if not f or not target:
            continue
        T[table].append({"id": cid, fk: int(target), "name": r["cells"][1], "body": f.get("body") or r["cells"][4],
                         "answer": f.get("answer") or None, "is_approved": int(str(sel(f.get("is_approved")) or "0") in ("1", "on"))})

json.dump({"tables": T, "files": sorted(FILES)}, open(OUT, "w"), ensure_ascii=False, indent=0)
print({k: len(v) for k, v in T.items()}, "files", len(FILES))
