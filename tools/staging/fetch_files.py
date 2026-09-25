"""Download the public images the dataset references into a staging copy.

    python3 tools/staging/fetch_files.py dataset.json <app>/public_html

Only public URLs (https://sepahanfelez.ir/images/...), the same files any
visitor's browser loads. For main/ images the thumbnail/ twin is fetched too.
"""
import json, os, re, sys, time, urllib.parse, urllib.request

data, root = json.load(open(sys.argv[1])), sys.argv[2]
want = set(data["files"])
# Images inside the rich text (category and article bodies, descriptions): /images/ckeditor/… and the
# like. Taken from src/href attributes, since live file names often contain spaces.
for rows in data["tables"].values():
    for r in rows:
        for v in r.values():
            if isinstance(v, str) and "/images/" in v:
                for m in re.findall(r"""(?:src|href)\s*=\s*["']([^"']+)["']""", v, re.I):
                    m = urllib.parse.unquote(re.sub(r"^https?://(?:www\.)?sepahanfelez\.ir", "", m.strip()))
                    if m.startswith("/images/") and re.search(r"\.(jpe?g|png|gif|webp|svg)$", m, re.I):
                        want.add(m)
# Files the admin theme loads that are on the host but not in the repository.
want |= {"/assets/js/jalalidatepicker.min.js", "/assets/css/jalalidatepicker.css"}
# The panel shows thumbnails, the site the main image: fetch both of each pair.
want |= {p.replace("/main/", "/thumbnail/") for p in want if "/main/" in p}
want |= {p.replace("/thumbnail/", "/main/") for p in want if "/thumbnail/" in p}
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
