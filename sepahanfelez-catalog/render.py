# -*- coding: utf-8 -*-
"""Render catalog.html to PDF + per-page PNG previews with Chromium."""
import sys, pathlib
from playwright.sync_api import sync_playwright

here = pathlib.Path(__file__).parent.resolve()
url = (here / "catalog.html").as_uri()
shots = here / "preview"
shots.mkdir(exist_ok=True)

with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={"width": 794, "height": 1123})  # A4 @ 96dpi
    pg.goto(url)
    pg.wait_for_timeout(1200)  # let fonts settle
    pg.pdf(path=str(here / "کاتالوگ-محصولات-سپاهان-فلز.pdf"),
           format="A4", print_background=True,
           margin={"top": "0", "bottom": "0", "left": "0", "right": "0"})
    # page previews for visual review
    n = pg.evaluate("document.querySelectorAll('.page').length")
    for i in range(n):
        el = pg.query_selector_all(".page")[i]
        el.screenshot(path=str(shots / f"page-{i+1:02d}.png"))
    b.close()
print(f"PDF + {n} previews done")
