"""Download the public images the dataset references into a staging copy.

    python3 tools/staging/fetch_files.py dataset.json <app>/public_html

Only public URLs (https://sepahanfelez.ir/images/...), the same files any
visitor's browser loads. For main/ images the thumbnail/ twin is fetched too.
"""
import json, os, sys, time, urllib.parse, urllib.request

data, root = json.load(open(sys.argv[1])), sys.argv[2]
want = set(data["files"])
want |= {p.replace("/main/", "/thumbnail/") for p in want if "/main/" in p}
ok = miss = 0
for p in sorted(want):
    dest = os.path.join(root, p.lstrip("/"))
    if os.path.exists(dest) and os.path.getsize(dest) > 0:
        ok += 1
        continue
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    url = "https://sepahanfelez.ir" + urllib.parse.quote(p)
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (staging mirror)"})
            with urllib.request.urlopen(req, timeout=60) as r, open(dest, "wb") as f:
                f.write(r.read())
            ok += 1
            break
        except urllib.error.HTTPError as e:
            if e.code == 404:
                miss += 1
                break
            time.sleep(3)
        except Exception:
            time.sleep(3)
    time.sleep(0.3)
print("files ok", ok, "missing", miss)
