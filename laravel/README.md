# کیت اتصال به بک‌اند لاراول

این پوشه، همان پروتوتایپ است که به شکل قالب‌های لاراول نوشته شده تا روی
مخزن بک‌اند (`github.com/mohammad-kasiri/Ahanamn`) بنشیند.

**مخزن بک‌اند در این مرحله دست‌نخورده می‌ماند.** هر وقت خواستید لایو کنید،
محتوای این پوشه را با همین ساختار داخل ریشه‌ی لاراول کپی می‌کنید.

---

## چه چیزی کجا می‌رود

| از اینجا | به آنجا |
|---|---|
| `config/redesign.php` | `config/redesign.php` |
| `app/Support/Redesign.php` | `app/Support/Redesign.php` |
| `app/Support/RedesignView.php` | `app/Support/RedesignView.php` |
| `resources/views/site/redesign/**` | `resources/views/site/redesign/**` |
| `resources/site/js/redesign.js` | `resources/site/js/redesign.js` |
| `resources/site/scss/_redesign.scss` | `resources/site/scss/_redesign.scss` |
| `database/sql/*.sql` | `database/sql/*.sql` |
| `patches/*.md` | راهنمای تغییر چند فایل موجود (کنترلرها، mix، master layout) |

هیچ فایل موجودی بازنویسی نمی‌شود مگر آن‌هایی که در `patches/` صریحاً آمده‌اند،
و همهٔ آن‌ها افزودنی‌اند نه حذفی.

## اصل کار

۱. **داده همیشه از دیتابیس می‌آید.** هر چیزی که پنل `/admin` امروز ویرایش
   می‌کند، فردا هم همان‌جا ویرایش می‌شود: دسته، محصول، قیمت، مقاله، اسلایدر،
   اطلاعات تماس، ویژگی‌ها، کاربردها، دیدگاه‌ها.

۲. **آنچه اسکیما هنوز ندارد، در `config/redesign.php` است** و هر کدام
   می‌نویسد جای واقعی‌اش کدام جدول است. سه مورد: کارشناسان فروش، نرخ کرایه،
   و دیدگاه‌های نمونه.

۳. **کلید برگشت:** `REDESIGN_ENABLED=false` در `.env` → قالب‌های قبلی
   برمی‌گردند، بدون هیچ دیپلوی دیگری.

## ترتیب اجرا

1. فایل‌ها را کپی کنید.
2. سه پچ کنترلر در `patches/` را اعمال کنید.
3. `npm run prod` (فایل‌های `files/js/redesign.js` و css ساخته می‌شوند).
4. `database/sql/2026_09_22_redesign_columns.sql` را در phpMyAdmin اجرا کنید
   (اختیاری ولی توصیه‌شده — بدون آن هم سایت کار می‌کند، فقط ستاره‌ی امتیاز و
   ماشین‌حساب دقیق‌تر غیرفعال می‌مانند).
5. `REDESIGN_ENABLED=true`.

جزئیات کامل در `docs/REDESIGN-BACKEND.md`.
