"""Build docs/راهنمای-سایت-و-پنل-مدیریت.pdf from guide.html.

    ~/.venvs/pw/bin/python docs/report/build.py [facts.json]

guide.html holds the text. Placeholders:
  {{date}}            today in the Persian calendar
  {{stats}}           counts of the staging data (from facts.json)
  {{findings}}        the table of issues found in the live panel (facts.json)
  {{shot:name|cap}}   docs/report/shots/name.jpg with a caption, if it exists
"""
import asyncio
import html
import json
import os
import re
import sys

from playwright.async_api import async_playwright

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(os.path.dirname(HERE), "راهنمای-سایت-و-پنل-مدیریت.pdf")
FA = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")


def jalali_today():
    import datetime
    g = datetime.date.today()
    gy, gm, gd = g.year, g.month, g.day
    g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
    gy2 = gy + 1 if gm > 2 else gy
    days = 355666 + 365 * gy + (gy2 + 3) // 4 - (gy2 + 99) // 100 + (gy2 + 399) // 400 + gd + g_d_m[gm - 1]
    jy = -1595 + 33 * (days // 12053)
    days %= 12053
    jy += 4 * (days // 1461)
    days %= 1461
    if days > 365:
        jy += (days - 1) // 365
        days = (days - 1) % 365
    jm = 1 + days // 31 if days < 186 else 7 + (days - 186) // 30
    jd = 1 + (days % 31 if days < 186 else (days - 186) % 30)
    months = "فروردین اردیبهشت خرداد تیر مرداد شهریور مهر آبان آذر دی بهمن اسفند".split()
    return f"{jd} {months[jm - 1]} {jy}".translate(FA)


def render(facts):
    src = open(os.path.join(HERE, "guide.html"), encoding="utf-8").read()
    src = src.replace("{{date}}", jalali_today())
    s = facts.get("stats", {})
    src = src.replace("{{stats}}", (
        f"{s.get('categories', 0)} دسته، {s.get('products', 0)} کالا ({s.get('active', 0)} فعال)، "
        f"{s.get('prices', 0)} ردیف تاریخچه‌ی قیمت، {s.get('articles', 0)} مقاله و همه‌ی تنظیمات").translate(FA))

    tag = {"pack": '<span class="tag t-ok">با بسته‌ی محتوا</span>',
           "panel": '<span class="tag t-warn">در پنل، با شما</span>',
           "info": '<span class="tag t-dim">فقط اطلاع</span>'}
    rows = "".join(
        f"<tr><td><b>{html.escape(f['where'])}</b></td><td>{f['what']}</td><td>{tag[f['fix']]}"
        f"{'<br><span class=small>' + f['note'] + '</span>' if f.get('note') else ''}</td></tr>"
        for f in facts.get("findings", []))
    src = src.replace("{{findings}}", '<table><tr><th style="width:22%">بخش پنل</th><th>وضعیت فعلی</th>'
                                      f'<th style="width:24%">اصلاح</th></tr>{rows}</table>')

    def shot(m):
        name, cap = (m.group(1).split("|", 1) + [""])[:2]
        p = os.path.join(HERE, "shots", name + ".jpg")
        if not os.path.exists(p):
            return ""
        return f'<figure><img src="shots/{name}.jpg"><figcaption>{html.escape(cap)}</figcaption></figure>'
    src = re.sub(r"\{\{(?:shot|half):([^}]+)\}\}", shot, src)
    out = os.path.join(HERE, "_rendered.html")
    open(out, "w", encoding="utf-8").write(src)
    return out


async def main():
    facts = json.load(open(sys.argv[1])) if len(sys.argv) > 1 else {}
    page = render(facts)
    async with async_playwright() as p:
        b = await p.chromium.launch()
        pg = await b.new_page()
        await pg.goto("file://" + page)
        await pg.evaluate("document.fonts.ready")
        await pg.pdf(path=OUT, format="A4", print_background=True, prefer_css_page_size=True,
                     display_header_footer=True, header_template="<span></span>",
                     footer_template='<div style="width:100%;font-size:8px;color:#8a94a3;text-align:center;'
                                     'font-family:sans-serif"><span class="pageNumber"></span></div>')
        await b.close()
    os.remove(page)
    print(OUT)


asyncio.run(main())
