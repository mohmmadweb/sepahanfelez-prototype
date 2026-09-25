# -*- coding: utf-8 -*-
"""
Write migration/pack.json into the site THROUGH THE ADMIN PANEL'S OWN FORMS.

Nothing touches the database directly: each value is typed into the same
form an admin would use and saved with the same button, so every piece of
backend logic runs as usual — slug checks, image upload + thumbnail, price
history, cache flushes, sitemap queue.

    # 1) log in once (SMS code), saves the session:
    python3 tools/apply_pack.py login --base https://sepahanfelez.ir --mobile 09…
    # 2) see what would change (reads only):
    python3 tools/apply_pack.py plan  --base https://sepahanfelez.ir
    # 3) do it:
    python3 tools/apply_pack.py apply --base https://sepahanfelez.ir [--only categories,products,…]

Sections, in the order applied: videos, about, information, home_setting,
socials, sliders, categories, products.

Before a form is saved, its current values are written to
migration/backup/<timestamp>/<section>.json — text can be restored from there
by hand. Images replaced in the panel stay on disk (the panel never deletes
the old file), so nothing uploaded before is lost.

Runs with Playwright (pip install playwright; playwright install chromium).
"""
import argparse
import asyncio
import html
import json
import os
import re
import sys
import time
import urllib.parse

from playwright.async_api import async_playwright

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PACK = os.path.join(ROOT, "migration", "pack.json")
STATE = os.path.join(ROOT, "migration", ".session.json")   # git-ignored
PAUSE = 1.5            # seconds between saves; the host is shared and busy


def log(*a):
    print(*a, flush=True)


class Panel:
    def __init__(self, base, apply, backup_dir):
        self.base = base.rstrip("/")
        self.apply = apply
        self.backup_dir = backup_dir
        self.changes = 0
        self.force_files = False
        self.failed_files = []

    def url(self, path):
        return self.base + path

    def backup(self, section, key, data):
        os.makedirs(self.backup_dir, exist_ok=True)
        f = os.path.join(self.backup_dir, section + ".json")
        cur = json.load(open(f, encoding="utf-8")) if os.path.exists(f) else {}
        cur[str(key)] = data
        json.dump(cur, open(f, "w", encoding="utf-8"), ensure_ascii=False, indent=1)

    async def open(self, ctx, path):
        pg = await ctx.new_page()
        for attempt in range(4):
            try:
                r = await pg.goto(self.url(path), wait_until="domcontentloaded", timeout=90000)
                await pg.wait_for_timeout(700)
                if "/login" in pg.url:
                    raise SystemExit("session expired — run `login` again")
                if r and r.status >= 400:
                    raise RuntimeError(f"{path} → {r.status}")
                return pg
            except SystemExit:
                raise
            except Exception as e:
                if attempt == 3:
                    raise
                await asyncio.sleep(4)

    async def form(self, pg, action_part):
        """The form whose action contains action_part (never the logout form)."""
        forms = pg.locator("form")
        for i in range(await forms.count()):
            f = forms.nth(i)
            act = (await f.get_attribute("action")) or ""
            if action_part in act and "logout" not in act:
                return f
        raise RuntimeError(f"no form with action ~ {action_part} on {pg.url}")

    async def values(self, f):
        return await f.evaluate("""f=>{const o={};f.querySelectorAll('input,textarea,select').forEach(e=>{
            if(!e.name||e.type==='file'||e.name==='_token'||e.name==='_method')return;
            o[e.name]=e.type==='checkbox'?e.checked:e.value});
            f.querySelectorAll('textarea').forEach(t=>{if(window.CKEDITOR&&CKEDITOR.instances[t.id])o[t.name]=CKEDITOR.instances[t.id].getData()});
            return o}""")

    async def set_fields(self, pg, f, fields, rich=(), files=None):
        for name, val in fields.items():
            if name in rich:
                ta = f.locator(f"textarea[name='{name}']")
                tid = await ta.get_attribute("id")
                await pg.evaluate("([id,v])=>{CKEDITOR.instances[id].setData(v); CKEDITOR.instances[id].updateElement();}", [tid, val])
            else:
                # Set directly: several fields sit in tabs that are not open,
                # which a click-and-type cannot reach.
                await f.evaluate("""(f,[n,v])=>{const e=f.querySelector('[name="'+n+'"]'); if(!e) throw new Error('no field '+n);
                    e.value=v; e.dispatchEvent(new Event('input',{bubbles:true})); e.dispatchEvent(new Event('change',{bubbles:true}));}""", [name, val])
        for name, path in (files or {}).items():
            await f.locator(f"input[type=file][name='{name}']").set_input_files(os.path.join(ROOT, path))

    async def submit(self, pg, f):
        """Save the form; fail loudly unless the panel answered with its usual
        redirect back to a page (a 4xx/5xx, or staying on the POST URL, means
        nothing was saved)."""
        action = (await f.get_attribute("action")) or ""
        async with pg.expect_navigation(wait_until="domcontentloaded", timeout=180000) as nav:
            btn = f.locator("[type=submit], button:not([type=button])")
            await btn.last.click()
        resp = await nav.value
        await pg.wait_for_timeout(600)
        if resp is None or resp.status >= 400:
            raise RuntimeError(f"save failed: HTTP {resp.status if resp else '?'} at {pg.url}")
        if action and pg.url.rstrip('/') == action.rstrip('/') and not resp.request.redirected_from:
            raise RuntimeError(f"save failed: the panel did not redirect after saving ({pg.url})")
        err = await pg.locator(".invalid-feedback, .alert-danger, .text-danger").all_inner_texts()
        err = [e.strip() for e in err if e.strip()]
        if err:
            raise RuntimeError("panel refused: " + " | ".join(err)[:400])
        self.changes += 1
        await asyncio.sleep(PAUSE)

    def diff(self, cur, want, rich=()):
        """Fields whose value differs. Rich text is compared by its words only:
        the editor rewrites markup (attributes, entities, line breaks) on save."""
        def norm(v, r=False):
            v = str(v or "")
            if r:
                v = re.sub(r"<[^>]+>", " ", v)
                v = html.unescape(v).replace("\u200c", " ")
            return re.sub(r"\s+", " ", v).strip()
        return {k: v for k, v in want.items() if norm(cur.get(k), k in rich) != norm(v, k in rich)}

    async def edit(self, ctx, section, key, path, action, want, rich=(), files=None):
        pg = await self.open(ctx, path)
        f = await self.form(pg, action)
        cur = await self.values(f)
        d = self.diff(cur, want, rich)
        # Files are re-sent only together with a text change (or --files), so a
        # second run does not upload every image again.
        if not d and (not files or not self.force_files):
            log(f"  = {section} {key}: already as in the pack")
            await pg.close()
            return
        log(f"  {'~' if self.apply else '?'} {section} {key}: " + ", ".join(sorted(d)) + (" + files" if files else ""))
        if self.apply:
            self.backup(section, key, cur)
            try:
                await self.set_fields(pg, f, d, rich=rich, files=files)
                await self.submit(pg, f)
            except RuntimeError as e:
                if not files or "save failed" not in str(e):
                    raise
                # An upload the server cannot store (typically a missing
                # images/<folder>/thumbnail directory) fails the whole save.
                # Save the text alone and report the picture.
                log(f"    ! {e} — saving without the picture")
                self.failed_files.append(f"{section} {key}: {', '.join(files.values())}")
                await pg.close()
                pg = await self.open(ctx, path)
                f = await self.form(pg, action)
                await self.set_fields(pg, f, d, rich=rich)
                await self.submit(pg, f)
        await pg.close()


async def run(args):
    pack = json.load(open(PACK, encoding="utf-8"))
    only = set(args.only.split(",")) if args.only else None
    want = lambda s: only is None or s in only
    stamp = time.strftime("%Y%m%d-%H%M%S")
    P = Panel(args.base, args.cmd == "apply", os.path.join(ROOT, "migration", "backup", stamp))
    P.force_files = args.files
    async with async_playwright() as pw:
        b = await pw.chromium.launch()
        ctx = await b.new_context(storage_state=args.state, viewport={"width": 1440, "height": 900})

        # -- videos: upload the factory videos not already in the panel -------------
        stored = {}
        if want("videos") or want("about"):
            pg = await P.open(ctx, "/admin/video")
            have = await pg.evaluate("()=>[...document.querySelectorAll('video source,video,a')].map(e=>e.getAttribute('src')||e.getAttribute('href')||'').filter(s=>/\\/videos\\//.test(s))")
            await pg.close()
            for v in pack["videos"]:
                base = os.path.basename(v)
                hit = next((h for h in have if h.endswith(base)), None)
                if hit:
                    stored[base] = hit
                    log(f"  = video {base}: already uploaded")
                    continue
                log(f"  {'~' if P.apply else '?'} video {base}: upload")
                if P.apply and want("videos"):
                    pg = await P.open(ctx, "/admin/video")
                    f = await P.form(pg, "/admin/video")
                    await f.locator("input[type=file][name=video]").set_input_files(os.path.join(ROOT, v))
                    await P.submit(pg, f)
                    have2 = await pg.evaluate("()=>[...document.querySelectorAll('video source,video,a')].map(e=>e.getAttribute('src')||e.getAttribute('href')||'').filter(s=>/\\/videos\\//.test(s))")
                    stored[base] = next((h for h in have2 if h.endswith(base)), "")
                    await pg.close()

        # -- about ---------------------------------------------------------------
        if want("about"):
            a = pack["about"]
            vid = stored.get(os.path.basename(a["video_upload"]), "")
            vid = urllib.parse.urlparse(vid).path if vid else ""
            fields = {"text": a["text"], "canonical": a["canonical"]}
            if vid:
                fields["video"] = vid
            await P.edit(ctx, "about", "about", "/admin/about", "/admin/about", fields, rich=("text",),
                         files={"image": a["image"]} if a.get("image") else None)

        # -- information -----------------------------------------------------------
        if want("information"):
            await P.edit(ctx, "information", "information", "/admin/informarion", "/admin/informarion",
                         pack["information"], rich=("about",))

        # -- home settings -----------------------------------------------------------
        if want("home_setting"):
            h = dict(pack["home_setting"])
            pic = h.pop("about_pic", None)
            await P.edit(ctx, "home_setting", "home", "/admin/home_setting", "/admin/home_setting", h,
                         rich=("about",), files={"about_pic": pic} if pic else None)

        # -- socials ---------------------------------------------------------------
        if want("socials"):
            pg = await P.open(ctx, "/admin/social")
            f = await P.form(pg, "/admin/social")
            rows = await f.evaluate("""f=>[...f.querySelectorAll('input[name^="url["]')].map(i=>{
                const id=i.name.match(/\\d+/)[0]; const row=i.closest('.row')||i.closest('tr')||i.parentElement;
                return {id, url:i.value, text:((row?row.innerHTML:'')+' '+(i.placeholder||'')).toLowerCase(),
                        active:!!f.querySelector('[name="is_active['+id+']"]:checked')}})""")
            active = pack["socials"]["active"]
            names = {"whatsapp": ["واتساپ", "whatsapp", "wa.me"], "telegram": ["تلگرام", "telegram", "t.me"],
                     "instagram": ["اینستاگرام", "instagram"]}
            plan = {}
            for r in rows:
                net = next((n for n, keys in names.items() if any(k in (r["text"] + r["url"]).lower() for k in keys)), None)
                if net in active:
                    plan[r["id"]] = (active[net], True)
                elif pack["socials"].get("deactivate_others"):
                    plan[r["id"]] = (r["url"], False)
            changes = {i: p for i, p in plan.items()
                       if p[0] != next(r["url"] for r in rows if r["id"] == i) or p[1] != next(r["active"] for r in rows if r["id"] == i)}
            log(f"  {'~' if P.apply else '?'} socials: {len(changes)} row(s) " + ", ".join(f"{i}→{'on' if p[1] else 'off'}" for i, p in changes.items()) if changes else "  = socials: already as in the pack")
            if changes and P.apply:
                P.backup("socials", "socials", rows)
                for i, (u, on) in plan.items():
                    await f.locator(f"[name='url[{i}]']").fill(u or "")
                    # The theme hides the real checkbox behind a styled span.
                    await f.evaluate("([n,on])=>{const b=document.querySelector('[name=\"'+n+'\"]'); if(b) b.checked=on;}",
                                     [f"is_active[{i}]", on])
                await P.submit(pg, f)
            await pg.close()

        # -- sliders: edit existing slides in order, add any missing ------------------
        if want("sliders"):
            pg = await P.open(ctx, "/admin/setting/slider")
            ids = sorted({int(m) for m in re.findall(r"/admin/setting/slider/(\d+)/edit", await pg.content())})
            await pg.close()
            for n, s in enumerate(pack["sliders"]):
                if n < len(ids):
                    await P.edit(ctx, "sliders", ids[n], f"/admin/setting/slider/{ids[n]}/edit",
                                 f"/admin/setting/slider/{ids[n]}", {"link": s["link"], "alt": s["alt"]},
                                 files={"image": s["image"]})
                else:
                    log(f"  {'~' if P.apply else '?'} sliders new: {s['alt']}")
                    if P.apply:
                        pg = await P.open(ctx, "/admin/setting/slider")
                        f = await P.form(pg, "/admin/setting/slider")
                        await P.set_fields(pg, f, {"link": s["link"], "alt": s["alt"]}, files={"image": s["image"]})
                        await P.submit(pg, f)
                        await pg.close()
            if len(ids) > len(pack["sliders"]):
                log(f"  ! sliders: {len(ids) - len(pack['sliders'])} extra slide(s) left as they are — delete in the panel if unwanted")

        # -- categories ------------------------------------------------------------
        cats = {}
        if want("categories") or want("products"):
            pg = await P.open(ctx, "/admin/category")
            cids = sorted({int(m) for m in re.findall(r"/admin/category/(\d+)/edit", await pg.content())})
            await pg.close()
            for cid in cids:
                pg = await P.open(ctx, f"/admin/category/{cid}/edit")
                slug = await pg.locator("input[name=slug]").first.input_value()
                cats[slug] = cid
                await pg.close()
        if want("categories"):
            for slug, c in pack["categories"].items():
                if slug not in cats:
                    log(f"  ! category {slug}: not in the panel — skipped")
                    continue
                fields = {k: c[k] for k in ("body", "meta_title", "meta_description", "meta_keywords")}
                await P.edit(ctx, "categories", slug, f"/admin/category/{cats[slug]}/edit",
                             f"/admin/category/{cats[slug]}", fields, rich=("body",),
                             files={"image": c["image"]} if c.get("image") else None)

        # -- products: slug (= its own page) and image, on the content tab ----------
        if want("products"):
            for slug, rows in pack["products"].items():
                if slug not in cats:
                    continue
                cid = cats[slug]
                pg = await P.open(ctx, f"/admin/category/{cid}/product")
                listing = await pg.evaluate("""()=>[...document.querySelectorAll('tr')].map(tr=>{
                    const a=[...tr.querySelectorAll('a')].find(x=>/\\/content$/.test(x.getAttribute('href')||''));
                    const td=tr.querySelectorAll('td'); return a&&td.length>1?[td[1].innerText.trim(),a.getAttribute('href')]:null}).filter(Boolean)""")
                await pg.close()
                by_title = {re.sub(r"\s+", " ", t): h for t, h in listing}
                for title, p in rows.items():
                    href = by_title.get(re.sub(r"\s+", " ", title))
                    if not href:
                        log(f"  ! product «{title}»: not found in category {slug}")
                        continue
                    path = urllib.parse.urlparse(href).path
                    await P.edit(ctx, "products", title, path, path.rsplit("/content", 1)[0] + "/content",
                                 {"slug": p["slug"]}, files={"image": p["image"]} if p.get("image") else None)

        await b.close()
    if P.failed_files:
        log("\npictures the server refused (create the images/<folder>/main and /thumbnail\n"
            "directories in cPanel → File Manager, then run `apply --files` again):")
        for x in P.failed_files:
            log("  - " + x)
    log(f"\n{'applied' if P.apply else 'planned (nothing saved)'}: {P.changes if P.apply else '—'}"
        + (f"\nbackup: {os.path.relpath(P.backup_dir, ROOT)}" if P.apply else ""))


async def login(args):
    async with async_playwright() as pw:
        b = await pw.chromium.launch()
        ctx = await b.new_context()
        pg = await ctx.new_page()
        await pg.goto(args.base.rstrip("/") + "/login")
        await pg.fill("input[name=mobile]", args.mobile)
        await pg.click("button[type=submit]")
        await pg.wait_for_load_state("domcontentloaded")
        code = input("کد پیامکی: ").strip()
        await pg.fill("input[name=code]", code)
        await pg.click("button[type=submit]")
        await pg.wait_for_load_state("domcontentloaded")
        os.makedirs(os.path.dirname(args.state), exist_ok=True)
        await ctx.storage_state(path=args.state)
        log("session saved" if "/login" not in pg.url and "verify" not in pg.url else "login failed")
        await b.close()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("cmd", choices=["login", "plan", "apply"])
    ap.add_argument("--base", default="https://sepahanfelez.ir")
    ap.add_argument("--state", default=STATE)
    ap.add_argument("--mobile", default="")
    ap.add_argument("--files", action="store_true", help="re-upload images even when no text changed")
    ap.add_argument("--only", default="", help="comma list: videos,about,information,home_setting,socials,sliders,categories,products")
    args = ap.parse_args()
    asyncio.run(login(args) if args.cmd == "login" else run(args))


if __name__ == "__main__":
    main()
