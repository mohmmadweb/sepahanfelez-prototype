#!/usr/bin/env python3
"""Scrape all category pages of sepahanfelez.ir into catalog.json."""
import json, re, sys, urllib.request
from bs4 import BeautifulSoup

CATS = [
    "توری-حصاری",
    "توری-پرسی",
    "توری-گابیون",
    "توری-مرغی",
    "توری-فرنگی",
    "توری-جوشی--گالوانیزه-رول",
    "توری-کششی(expanded-metal)",
    "مش-جوشی-یا-مش-آهنی",
    "سیم-خاردار",
    "سیم-سیاه-و-آرماتور-بندی",
]

BASE = "https://sepahanfelez.ir/category/"
HDRS = {"User-Agent": "Mozilla/5.0 (X11; Linux x86_64) Chrome/120"}

def fetch(url):
    req = urllib.request.Request(url, headers=HDRS)
    with urllib.request.urlopen(req, timeout=40) as r:
        return r.read().decode("utf-8", "replace")

def clean(s):
    return re.sub(r"\s+", " ", s).strip()

out = {}
for slug in CATS:
    url = BASE + urllib.parse.quote(slug)
    try:
        html = fetch(url)
    except Exception as e:
        print(f"FAIL {slug}: {e}", file=sys.stderr)
        continue
    soup = BeautifulSoup(html, "html.parser")

    h1 = soup.find("h1")
    title = clean(h1.get_text()) if h1 else slug

    # main price table
    table = soup.find("table")
    columns, rows = [], []
    if table:
        ths = table.find_all("th")
        columns = [clean(t.get_text()) for t in ths]
        for tr in table.find_all("tr"):
            tds = tr.find_all("td")
            if not tds:
                continue
            rows.append([clean(td.get_text()) for td in tds])

    # SEO/description text: paragraphs and h2/h3 outside the table
    content = []
    for el in soup.find_all(["h2", "h3", "p", "li"]):
        # skip nav/footer junk by requiring persian text length
        txt = clean(el.get_text())
        if len(txt) > 40 and not el.find_parent("table") and not el.find_parent("footer") and not el.find_parent("nav"):
            content.append({"tag": el.name, "text": txt})

    # product images on page
    imgs = []
    for img in soup.find_all("img"):
        src = img.get("src", "")
        if "/images/" in src and "icon" not in src.lower() and "logo" not in src.lower():
            imgs.append({"src": src, "alt": img.get("alt", "")})

    meta = soup.find("meta", attrs={"name": "description"})
    out[slug] = {
        "title": title,
        "url": url,
        "meta_description": clean(meta["content"]) if meta and meta.get("content") else "",
        "columns": columns,
        "rows": rows,
        "content": content[:40],
        "images": imgs[:20],
    }
    print(f"OK {slug}: {len(rows)} rows, {len(columns)} cols, {len(content)} content blocks")

with open(sys.argv[1], "w") as f:
    json.dump(out, f, ensure_ascii=False, indent=1)
print("saved", sys.argv[1])
