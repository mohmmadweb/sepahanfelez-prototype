# نسخه‌ی آزمایشی (staging) با پنل مدیریت و داده‌های واقعی

یک کپی کامل از Ahanamn روی همین سرور با کیت قالب جدید. داده‌ها از پنل زنده خوانده شده‌اند: دسته‌ها، کالاها، ویژگی‌ها، تاریخچه‌ی قیمت، مقاله‌ها، تنظیمات، اسلایدر و برچسب‌ها.

**هیچ کاری که اینجا انجام می‌دهید روی sepahanfelez.ir اثر ندارد.**

| | |
|---|---|
| مسیر | `~/projects/sepahanfelez-staging/app` (خارج از هر دو مخزن) |
| اجرا | `127.0.0.1:8796`؛ عمداً روی IP عمومی باز نیست |
| ورود مدیر | `/_staging/login` با رمز `STAGING_PASSWORD` در `.env` همان پوشه |
| پیامک | ارسال نمی‌شود؛ فقط در لاگ ثبت می‌شود |
| موتور جست‌وجو | همه‌ی پاسخ‌ها `X-Robots-Tag: noindex` دارند |

## باز کردن از کامپیوتر خودتان

```bash
ssh -L 8796:127.0.0.1:8796 ubuntu@185.204.168.61
```

تا وقتی این پنجره باز است، در مرورگر آدرس `http://localhost:8796` را باز کنید. پنل مدیریت در `http://localhost:8796/_staging/login` است.

## ساختن دوباره از داده‌ی تازه‌ی سایت زنده

```bash
# ۱. نشست مدیر سایت زنده (کد پیامکی می‌خواهد)
python3 tools/apply_pack.py login --mobile 09… --state migration/live/state.json
# ۲. فقط خواندن: هیچ فرمی ارسال نمی‌شود، اطلاعات شخصی خوانده نمی‌شود
python3 tools/staging/live_export.py migration/live
python3 tools/staging/live_dataset.py migration/live/export migration/live/dataset.json
# ۳. دیتابیس و عکس‌ها
cd ~/projects/sepahanfelez-staging/app
php ~/projects/sepahanfelez-prototype/tools/staging/seed_live.php ~/projects/sepahanfelez-prototype/migration/live/dataset.json
python3 ~/projects/sepahanfelez-prototype/tools/staging/fetch_files.py ~/projects/sepahanfelez-prototype/migration/live/dataset.json public_html
php artisan cache:clear && php artisan view:clear
# ۴. (اختیاری) تمرین لایو: بسته‌ی محتوا از طریق فرم‌های پنل همین نسخه
python3 ~/projects/sepahanfelez-prototype/tools/apply_pack.py apply --base http://127.0.0.1:8796 --state <نشست staging>
```

`migration/live/` در gitignore است. داده‌ی خام پنل هرگز commit نمی‌شود.

## فایل‌هایی که فقط مال staging هستند

پوشه‌ی `overlay/` روی کپی staging کپی می‌شود و **هرگز** نباید به Ahanamn یا هاست برسد:

- `app/Providers/StagingServiceProvider.php`: ورود با رمز و noindex. در `config/app.php` همان کپی ثبت شده است.
- `app/Facades/SMS.php` و `app/Sms/StagingLog.php`: جلوی ارسال پیامک را می‌گیرد.
- `app/Http/Middleware/StagingNoIndex.php` و `staging/login.blade.php`.
