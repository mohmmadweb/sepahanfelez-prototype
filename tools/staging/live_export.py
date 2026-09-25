"""READ-ONLY export (python3 tools/staging/live_export.py <dir>) of sepahanfelez.ir/admin, for a staging copy.

Only GET requests to an allow-list of admin pages; never submits, never
follows toggle/delete links. Personal data (users, orders, tickets, contact
messages, newsletter, collaboration requests) is not read.

Output: admin/export/<key>.json per page (forms with full values, tables,
image sources) and screenshots of the main sections for the report.
"""
import asyncio, json, os, re, sys, time, urllib.parse, urllib.request
from bs4 import BeautifulSoup
from playwright.async_api import async_playwright

# <dir> holds state.json (a logged-in admin session, e.g. from tools/apply_pack.py login);
# the export goes to <dir>/export and screenshots to <dir>/shots.
D = sys.argv[1] if len(sys.argv) > 1 else "migration/live"
OUT = D + "/export"
B = "https://sepahanfelez.ir"
FORBID = re.compile(r"toggle|destroy|delete|logout|/import|/export|/user|/order|/ticket|/contact|/newsletter|/collaboration|/access|/role", re.I)


def parse(html, url):
    """The same shape the browser version produced: forms with current values, tables, images."""
    soup = BeautifulSoup(html, "lxml")
    forms = []
    for f in soup.find_all("form"):
        if "logout" in (f.get("action") or ""):
            continue
        fields, options = {}, {}
        for e in f.find_all(["input", "textarea", "select"]):
            name = e.get("name")
            if not name or name == "_token":
                continue
            typ = (e.get("type") or "").lower()
            if e.name == "input" and typ == "file":
                v = "[file]"
            elif e.name == "select":
                opts = e.find_all("option")
                options[name] = [{"v": o.get("value", o.get_text(strip=True)), "t": o.get_text(strip=True)} for o in opts]
                chosen = [o for o in opts if o.has_attr("selected")]
                if not chosen and not e.has_attr("multiple") and opts:
                    chosen = [opts[0]]
                v = [{"v": o.get("value", o.get_text(strip=True)), "t": o.get_text(strip=True)} for o in chosen]
            elif e.name == "input" and typ in ("checkbox", "radio"):
                if not e.has_attr("checked"):
                    continue
                v = e.get("value") or "on"
            elif e.name == "textarea":
                v = e.string if e.string is not None else e.decode_contents()
            else:
                v = e.get("value", "")
            if name in fields:
                if not isinstance(fields[name], list) or name not in multi_names:
                    fields[name] = [fields[name]]
                    multi_names.add(name)
                fields[name].append(v)
            else:
                fields[name] = v
        multi_names.clear()
        forms.append({"action": f.get("action") or "", "method": (f.get("method") or "get").lower(),
                      "fields": fields, "options": options})
    tables = [[{"cells": [c.get_text(" ", strip=True) for c in tr.find_all(["td", "th"])],
                "links": [a.get("href") for a in tr.find_all("a", href=True)],
                "imgs": [i.get("src") for i in tr.find_all("img")]} for tr in t.find_all("tr")]
              for t in soup.find_all("table")]
    imgs = [i.get("src") for i in soup.select(".content img, form img, .card img, table img") if i.get("src")]
    videos = [v.get("src") for v in soup.select("video, video source") if v.get("src")]
    pages = [a.get("href") for a in soup.select(".pagination a[href]")]
    t = soup.select_one(".card-title, .subheader h5, h3, h5")
    return {"url": url, "title": t.get_text(strip=True) if t else "", "forms": forms, "tables": tables,
            "imgs": imgs, "videos": videos, "pages": pages}


multi_names = set()

SHOT = {"/admin", "/admin/category", "/admin/spec", "/admin/excel", "/admin/article", "/admin/article-category",
        "/admin/general_setting", "/admin/home_setting", "/admin/informarion", "/admin/social", "/admin/setting/slider",
        "/admin/about", "/admin/tutorial", "/admin/sitemap/edit", "/admin/redirect", "/admin/tag", "/admin/video",
        "/admin/category_comment", "/admin/article_comments", "/admin/discount", "/admin/notice", "/admin/news", "/admin/news_comments"}


class X:
    def __init__(self, ctx):
        self.ctx = ctx
        self.done = set()

    async def get(self, path, key=None, shot=False):
        if FORBID.search(path.split("?")[0]) and path != "/admin":
            return None
        key = key or re.sub(r"[^a-z0-9]+", "_", path.lower()).strip("_") or "root"
        f = f"{OUT}/{key}.json"
        if os.path.exists(f):
            return json.load(open(f))
        if shot:
            await self.shot(path, key)
        url = B + urllib.parse.quote(path, safe="/?=&%")
        for attempt in range(5):
            try:
                req = urllib.request.Request(url, headers={"Cookie": self.cookie, "User-Agent": "Mozilla/5.0"})
                with urllib.request.urlopen(req, timeout=90) as r:
                    if "/login" in r.url:
                        sys.exit("session expired: log in again")
                    data = parse(r.read().decode("utf-8", "replace"), r.url)
                    data["status"] = r.status
            except urllib.error.HTTPError as e:
                data = {"url": url, "title": "", "forms": [], "tables": [], "imgs": [], "videos": [], "pages": [],
                        "status": e.code}
            except Exception:
                time.sleep(4)
                continue
            json.dump(data, open(f, "w"), ensure_ascii=False)
            time.sleep(0.3)
            return data
        print("FAIL", path, flush=True)
        return None

    async def shot(self, path, key):
        """Screenshots for the report only; the data comes from plain requests."""
        pg = await self.ctx.new_page()
        try:
            await pg.goto(B + path, wait_until="domcontentloaded", timeout=90000)
            await pg.wait_for_timeout(900)
            await pg.screenshot(path=f"{D}/shots/{key}.png", full_page=False)
        except Exception:
            pass
        await pg.close()

    async def paged(self, path, key):
        first = await self.get(path, key, shot=path in SHOT)
        out = [first] if first else []
        seen = {path}
        for h in (first or {}).get("pages", []):
            p = h.replace(B, "")
            if "page=" in p and p not in seen:
                seen.add(p)
                d = await self.get(p, key + "_p" + re.search(r"page=(\d+)", p).group(1))
                if d:
                    out.append(d)
        return out


def links(pages, pattern):
    ids = []
    for d in pages:
        for t in d["tables"]:
            for row in t:
                for h in row["links"]:
                    m = re.search(pattern, h or "")
                    if m and m.group(1) not in ids:
                        ids.append(m.group(1))
    return ids


async def main():
    os.makedirs(OUT, exist_ok=True)
    os.makedirs(D + "/shots", exist_ok=True)
    async with async_playwright() as p:
        b = await p.chromium.launch()
        ctx = await b.new_context(storage_state=D + "/state.json", viewport={"width": 1440, "height": 900})
        x = X(ctx)
        st = json.load(open(D + "/state.json"))
        x.cookie = "; ".join(f"{c['name']}={c['value']}" for c in st["cookies"] if "sepahanfelez" in c["domain"])
        for s in SHOT:
            await x.get(s, shot=True)
        # specs and values
        specs = await x.paged("/admin/spec", "spec")
        for sid in links(specs, r"/admin/spec/(\d+)/value$"):
            await x.paged(f"/admin/spec/{sid}/value", f"spec_{sid}_values")
        # categories
        cats = await x.paged("/admin/category", "category")
        for cid in links(cats, r"/admin/category/(\d+)/edit$"):
            await x.get(f"/admin/category/{cid}/edit", f"cat_{cid}_edit", shot=(cid == "20"))
            await x.get(f"/admin/category/{cid}/columns", f"cat_{cid}_columns", shot=(cid == "20"))
            for kind in ("feature", "usage"):
                lst = await x.paged(f"/admin/category/{cid}/{kind}", f"cat_{cid}_{kind}")
                for fid in links(lst, rf"/admin/category/{cid}/{kind}/(\d+)/edit$"):
                    await x.get(f"/admin/category/{cid}/{kind}/{fid}/edit", f"cat_{cid}_{kind}_{fid}")
            prods = await x.paged(f"/admin/category/{cid}/product", f"cat_{cid}_products")
            for pid in links(prods, rf"/admin/category/{cid}/product/(\d+)/edit$"):
                sh = pid == "30"
                await x.get(f"/admin/category/{cid}/product/{pid}/edit", f"prod_{pid}_edit", shot=sh)
                await x.get(f"/admin/category/{cid}/product/{pid}/content", f"prod_{pid}_content", shot=sh)
                await x.paged(f"/admin/product/{pid}/price", f"prod_{pid}_price")
            print("category", cid, flush=True)
        # magazine
        acats = await x.paged("/admin/article-category", "article_category")
        for aid in links(acats, r"/admin/article-category/(\d+)/edit$"):
            await x.get(f"/admin/article-category/{aid}/edit", f"acat_{aid}_edit")
        arts = await x.paged("/admin/article", "article")
        for aid in links(arts, r"/admin/article/(\d+)/edit$"):
            await x.get(f"/admin/article/{aid}/edit", f"art_{aid}_edit", shot=False)
        print("articles", len(links(arts, r"/admin/article/(\d+)/edit$")), flush=True)
        # sliders, tags, redirects, comments
        sl = await x.get("/admin/setting/slider")
        for sid in links([sl], r"/admin/setting/slider/(\d+)/edit$"):
            await x.get(f"/admin/setting/slider/{sid}/edit", f"slider_{sid}_edit")
        for path, key in (("/admin/tag", "tag"), ("/admin/redirect", "redirect"), ("/admin/category_comment", "category_comment"),
                          ("/admin/article_comments", "article_comments")):
            pages = await x.paged(path, key)
            if key.endswith("comment") or key.endswith("comments"):
                for cid in links(pages, rf"{path}/(\d+)/edit$"):
                    await x.get(f"{path}/{cid}/edit", f"{key}_{cid}_edit")
        await b.close()
    print("done", len(os.listdir(OUT)), flush=True)


asyncio.run(main())
