# -*- coding: utf-8 -*-
"""داده‌ی ساختاریافته (JSON-LD) برای همه‌ی صفحه‌ها.

چرا مهم است: همین داده است که باعث می‌شود گوگل زیر نتیجه‌ی ما مسیر
راهنما، قیمت، امتیاز و پرسش‌های متداول نشان بدهد. سایت فعلی کارفرما
روی صفحه‌ی دسته چهار نوع دارد و پروتوتایپ هیچ نداشت — یعنی عقب‌گرد.

قواعدی که رعایت شده:
  • فقط چیزی ادعا می‌شود که روی همان صفحه دیده می‌شود. امتیاز و دیدگاه
    در این نسخه نمونه‌اند، پس AggregateRating عمداً ساخته نمی‌شود؛
    ادعای دروغ در اسکیما جریمه دارد.
  • قیمت‌ها ریال‌اند و priceCurrency=IRR. گوگل IRR را می‌شناسد.
  • هر صفحه حداکثر یک بلوک؛ چند بلوک جدا فقط سردرگمی می‌سازد.
"""
import json

SITE = "https://sepahanfelez.ir"
ORG_ID = f"{SITE}/#organization"
SITE_ID = f"{SITE}/#website"


def _j(obj):
    return ('<script type="application/ld+json">'
            + json.dumps(obj, ensure_ascii=False, separators=(",", ":"))
            + "</script>")


def organization(C):
    """هویت کارخانه. یک‌بار در هر صفحه با @id مشترک می‌آید تا گوگل
    همه‌ی صفحه‌ها را به یک کسب‌وکار وصل کند."""
    return {
        "@type": "Organization",
        "@id": ORG_ID,
        "name": "صنایع مفتولی طلوع سپاهان",
        "alternateName": "سپاهان فلز",
        "url": SITE + "/",
        "logo": {"@type": "ImageObject", "url": SITE + "/assets/brand/logo-864.png"},
        "image": SITE + "/assets/brand/og-image.png",
        "telephone": "+98" + C.PHONE_RAW.lstrip("0"),
        "email": "info@sepahanfelez.ir",
        "address": [
            {"@type": "PostalAddress", "addressCountry": "IR",
             "addressLocality": "اصفهان",
             "streetAddress": "شهرک صنعتی منتظریه (ویلاشهر)، خیابان قادری، پلاک ۱۸۱"},
            {"@type": "PostalAddress", "addressCountry": "IR",
             "addressLocality": "تهران",
             "streetAddress": "بازار آهن شادآباد، بلوار شهید قربانخوانی، مجتمع پارس فلز، پلاک ۹"},
        ],
        "contactPoint": {
            "@type": "ContactPoint", "telephone": "+98" + C.PHONE_RAW.lstrip("0"),
            "contactType": "sales", "areaServed": "IR", "availableLanguage": "fa",
        },
    }


def website():
    return {
        "@type": "WebSite", "@id": SITE_ID, "url": SITE + "/",
        "name": "سپاهان فلز", "inLanguage": "fa-IR",
        "publisher": {"@id": ORG_ID},
    }


def breadcrumb(items):
    """items: [(نام, نشانی نسبی یا None برای صفحه‌ی جاری)]"""
    return {
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": i + 1, "name": name,
             **({"item": SITE + url} if url else {})}
            for i, (name, url) in enumerate(items)
        ],
    }


def offer(price, unit_name):
    """پیشنهاد قیمت. قیمت کارخانه روزانه عوض می‌شود، پس priceValidUntil
    نمی‌گذاریم و availability را InStock اعلام می‌کنیم چون کالا موجود است."""
    if not price:
        return None
    return {
        "@type": "Offer", "price": str(price), "priceCurrency": "IRR",
        "availability": "https://schema.org/InStock",
        "itemCondition": "https://schema.org/NewCondition",
        "seller": {"@id": ORG_ID},
        "priceSpecification": {
            "@type": "UnitPriceSpecification",
            "price": str(price), "priceCurrency": "IRR",
            "unitText": unit_name,
        },
    }


def product(name, url, desc, price, unit_name, image=None, brand="طلوع سپاهان",
            props=None):
    p = {
        "@type": "Product",
        "@id": SITE + url + "#product",
        "name": name, "url": SITE + url,
        "description": desc,
        "brand": {"@type": "Brand", "name": brand},
        "manufacturer": {"@id": ORG_ID},
    }
    if image:
        p["image"] = SITE + image if image.startswith("/") else image
    o = offer(price, unit_name)
    if o:
        p["offers"] = o
    if props:
        p["additionalProperty"] = [
            {"@type": "PropertyValue", "name": k, "value": str(v)}
            for k, v in props if v not in (None, "", "-")
        ]
    return p


def item_list(products, list_name):
    """فهرست کالاهای یک دسته. گوگل از همین برای «نتایج غنی فهرست» استفاده
    می‌کند و در صفحه‌ی دسته مهم‌ترین بلوک است."""
    return {
        "@type": "ItemList", "name": list_name,
        "numberOfItems": len(products),
        "itemListElement": [
            {"@type": "ListItem", "position": i + 1, "item": p}
            for i, p in enumerate(products)
        ],
    }


def faq(pairs):
    """pairs: [(پرسش, پاسخ متنی)]. فقط وقتی صدا می‌شود که پرسش‌ها روی
    خود صفحه دیده شوند."""
    if not pairs:
        return None
    return {
        "@type": "FAQPage",
        "mainEntity": [
            {"@type": "Question", "name": q,
             "acceptedAnswer": {"@type": "Answer", "text": a}}
            for q, a in pairs
        ],
    }


def _strip(o):
    """کلیدهای خالی را دور بینداز. مقدار null در JSON-LD هشدار می‌سازد."""
    if isinstance(o, dict):
        return {k: _strip(v) for k, v in o.items()
                if v is not None and v != "" and v != []}
    if isinstance(o, list):
        return [_strip(x) for x in o if x is not None]
    return o


def graph(*nodes):
    """همه‌ی گره‌ها در یک @graph. یک اسکریپت، نه چند تا."""
    clean = [_strip(n) for n in nodes if n]
    return _j({"@context": "https://schema.org", "@graph": clean})
