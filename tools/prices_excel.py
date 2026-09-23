#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""بروزرسانی قیمت‌ها از اکسل — دقیقاً با همان قرارداد بک‌اند فعلی.

چرا این شکلی: بک‌اند لاراول (Ahanamn) در app/Excel یک import و یک export
دارد. قرارداد ستون‌ها همان است و هیچ‌جا تغییر نمی‌کند:

    ستون ۰ = شناسه‌ی محصول (id)
    ستون ۱ = عنوان محصول (title) — فقط برای خواندن آدم، وارد نمی‌شود
    ستون ۲ = قیمت (price) — عدد صحیح، ریال

منطق بک‌اند در ProductsImport::updateProductAndCreatePrice:
  • اگر قیمت تازه با قیمت فعلی فرق دارد و عددِ مثبت است، یک رکورد در
    جدول prices ساخته می‌شود (first_price = قیمت قبلی، second_price =
    قیمت تازه، price_at = حالا) و قیمت محصول بروز می‌شود.
  • اگر فرقی ندارد، فقط price_at آخرین رکورد و updated_at محصول
    تازه می‌شوند. یعنی «امروز هم همین قیمت» ثبت می‌شود.

همین منطق اینجا پیاده شده تا وقتی پروتوتایپ به بک‌اند وصل شد، همان
فایل اکسلی که کارفرما برای سایت فعلی می‌سازد، بدون تغییر کار کند.

کاربرد:
    python3 tools/prices_excel.py export prices.xlsx        # قالب خروجی
    python3 tools/prices_excel.py import prices.xlsx        # بروزرسانی
    python3 tools/prices_excel.py import prices.xlsx --dry  # فقط گزارش
"""
import json
import os
import re
import sys
import datetime
import shutil

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CATALOG = os.path.join(ROOT, "build", "catalog.json")
HISTORY = os.path.join(ROOT, "build", "price-history.json")

PRICE_KEYS = ("قیمت روز (ريال)", "قیمت روز (ریال)")
DELTA_KEYS = ("نوسان قیمت (ریال)", "نوسان قیمت (ريال)")


def price_key(row):
    for k in PRICE_KEYS:
        if k in row:
            return k
    return PRICE_KEYS[0]


def delta_key(row):
    for k in DELTA_KEYS:
        if k in row:
            return k
    return DELTA_KEYS[0]


def to_int(v):
    """«۱٬۲۲۰٬۰۰۰» یا «1,220,000» یا 1220000 → 1220000"""
    if v is None:
        return None
    if isinstance(v, (int, float)):
        return int(v)
    s = str(v)
    s = s.translate(str.maketrans("۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩",
                                  "01234567890123456789"))
    s = re.sub(r"[^\d]", "", s)
    return int(s) if s else None


def fmt(n):
    return f"{n:,}"


def load():
    return json.load(open(CATALOG, encoding="utf-8"))


def iter_products(cat):
    """(pid, category_key, row) برای هر کالای واقعی.

    شناسه همان چیزی است که بک‌اند می‌شناسد. تا وقتی id واقعی پایگاه‌داده
    در دست نیست، از «کلید دسته : ردیف» می‌سازیم که پایدار و یکتاست.
    """
    for key, c in cat.items():
        if "rows" not in c:
            continue
        for r in c["rows"]:
            pid = r.get("id") or f"{key}:{r.get('ردیف', '')}".strip(":")
            yield pid, key, r


def do_export(path):
    from openpyxl import Workbook
    cat = load()
    wb = Workbook()
    ws = wb.active
    ws.title = "products"
    # بدون سطر عنوان: بک‌اند از سطر اول داده می‌خواند (ToCollection)
    n = 0
    for pid, key, r in iter_products(cat):
        pk = price_key(r)
        ws.append([pid, r.get("نام محصول", ""), to_int(r.get(pk)) or 0])
        n += 1
    for col, w in (("A", 34), ("B", 52), ("C", 16)):
        ws.column_dimensions[col].width = w
    wb.save(path)
    print(f"خروجی گرفته شد: {n} کالا → {path}")
    print("ستون‌ها: شناسه | عنوان | قیمت (ریال) — همان قرارداد بک‌اند")


def do_import(path, dry=False):
    from openpyxl import load_workbook
    cat = load()
    index = {pid: (key, r) for pid, key, r in iter_products(cat)}

    wb = load_workbook(path, data_only=True)
    ws = wb.active
    changed = touched = skipped = missing = 0
    events = []
    today = datetime.date.today().isoformat()

    for row in ws.iter_rows(values_only=True):
        if not row or row[0] in (None, ""):
            continue
        pid = str(row[0]).strip()
        new = to_int(row[2]) if len(row) > 2 else None
        if pid not in index:
            missing += 1
            print(f"  ✗ شناسه پیدا نشد: {pid}")
            continue
        key, r = index[pid]
        pk, dk = price_key(r), delta_key(r)
        cur = to_int(r.get(pk))

        # همان شرط بک‌اند: فقط عدد مثبت و متفاوت، قیمت را عوض می‌کند
        if new is not None and new > 0 and new != cur:
            if not dry:
                r[dk] = fmt(cur) if cur else r.get(dk, "")
                r[pk] = fmt(new)
            events.append({"product_id": pid, "first_price": cur,
                           "second_price": new, "price_at": today})
            changed += 1
        elif new is not None and new > 0:
            # قیمت همان است: فقط تاریخ ثبت تازه می‌شود
            events.append({"product_id": pid, "first_price": cur,
                           "second_price": cur, "price_at": today})
            touched += 1
        else:
            skipped += 1

    if not dry:
        json.dump(cat, open(CATALOG, "w", encoding="utf-8"),
                  ensure_ascii=False, indent=1)
        hist = []
        if os.path.exists(HISTORY):
            hist = json.load(open(HISTORY, encoding="utf-8"))
        hist.extend(events)
        json.dump(hist, open(HISTORY, "w", encoding="utf-8"),
                  ensure_ascii=False, indent=1)

    head = "گزارش (بدون تغییر فایل)" if dry else "انجام شد"
    print(f"{head}: {changed} قیمت تغییر کرد · {touched} بدون تغییر "
          f"(تاریخ تازه شد) · {skipped} رد شد · {missing} شناسه نامعتبر")
    if not dry and changed:
        print("حالا بیلد را اجرا کنید: python3 build/gen.py")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(__doc__)
        sys.exit(1)
    cmd, path = sys.argv[1], sys.argv[2]
    if cmd == "export":
        do_export(path)
    elif cmd == "import":
        if not os.path.exists(CATALOG + ".bak"):
            shutil.copy(CATALOG, CATALOG + ".bak")
        do_import(path, dry="--dry" in sys.argv)
    else:
        print(__doc__)
        sys.exit(1)
