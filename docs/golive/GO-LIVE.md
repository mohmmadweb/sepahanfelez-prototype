# چک‌لیست لایو کردن قالب جدید روی sepahanfelez.ir

همه‌ی مراحل روی stage با کد و دیتابیس واقعی هاست تمرین شده‌اند (PHP 7.4، MariaDB 10.3).

## وضعیت آمادگی

| مورد | وضعیت |
|---|---|
| کیت (`laravel/`) | ۱۰۱ فایل. هیچ‌کدام فایلی از Ahanamn را بازنویسی نمی‌کنند. فقط ۱ خط به `config/app.php` اضافه می‌شود |
| سازگاری | PHP 7.4.33 و MariaDB 10.3 با دیتابیس واقعی. همه‌ی قالب‌ها روی 7.4 کامپایل می‌شوند |
| چک‌های CI خود Ahanamn روی Ahanamn + کیت | brand-scrub (۲۵۰ مورد)، frontend-check (۶۱ مورد) و price-schema همه سبز. PHPUnit دقیقاً مثل Ahanamn بدون کیت |
| مرحله‌ی «Verify the live site» در دیپلوی | فقط کد ۲۰۰ پنج صفحه را چک می‌کند و قالب جدید برای هر پنج صفحه ۲۰۰ می‌دهد |
| فایل‌های stage-only (رمز سایت، پیامک خاموش، کد ثابت) | در کیت نیستند |
| بسته‌ی محتوا (`tools/apply_pack.py`) | روی stage اجرا شد: ۸۸ مورد، بعد از آن plan = ۰. دو باگ ابزار پیدا و رفع شد |
| ۱۴۳ فایل خالی روی هاست | zip آماده (خارج از این مخزن عمومی): `~/projects/sepahanfelez-staging/golive/restore-empty-files.zip` |

## روز لایو، به ترتیب

1. **بکاپ کامل تازه** از cPanel: Backup Wizard ← Home Directory و MySQL Database. JetBackup هاست هم بکاپ خودکار دارد.
2. **کیت در Ahanamn:** محتوای `laravel/` (به‌جز README) در ریشه‌ی مخزن کپی شود، و در `config/app.php` بعد از `RouteServiceProvider` این خط اضافه شود:
   `App\Providers\RedesignServiceProvider::class,`
   بعد commit و push روی `main`. CI همان فایل‌ها را آپلود می‌کند و قبل از بازنویسی، از فایل‌های عوض‌شده snapshot می‌گیرد. **سایت هنوز قالب قبلی را نشان می‌دهد**، چون `REDESIGN_ENABLED` پیش‌فرض خاموش است.
3. **فایل‌های خالی:** در File Manager، فایل `restore-empty-files.zip` در `/home/ahanamnc` آپلود شود و Extract شود تا فایل‌ها بازنویسی شوند. سفارش‌های پنل، فهرست آدرس مشتری، آیکون‌ها و ویرایشگر درست می‌شوند.
4. **بسته‌ی محتوا** از طریق فرم‌های خود پنل (کد پیامکی مدیر لازم است):
   ```bash
   python3 tools/apply_pack.py login --mobile 09…
   APPLY_TIMEOUT_MS=120000 python3 tools/apply_pack.py plan
   APPLY_TIMEOUT_MS=120000 python3 tools/apply_pack.py apply
   ```
   تا قدم ۵، این متن‌ها و عکس‌ها در قالب قبلی دیده می‌شوند. فاصله‌ی بین قدم ۴ و ۵ را کوتاه نگه دارید.
5. در `.env` هاست (از File Manager): `REDESIGN_ENABLED=true`. قالب جدید فوراً بالا می‌آید.
6. بررسی صفحه‌ها روی سایت زنده.

## آنچه روز لایو (۷ آبان ۱۴۰۵) واقعاً پیش آمد

- کیت در دو commit به `main` رفت: `2d767c52` (قالب‌ها و کلاس‌ها) و `1bb2079f` (پوشه‌ی `public_html/rd` و favicon). `/public_html` در `.gitignore` است، پس فایل‌های آن باید با `git add -f` اضافه شوند.
- دیپلوی خودکار به هاست نرسید. cPanel به همه‌ی درخواست‌های API پاسخ `403 Access denied` داد، یعنی `CPANEL_API_TOKEN` باطل شده است:
  - فایل وضعیت خوانده نشد و اجرای اول خواست کل درخت (۱۰٬۳۸۷ فایل) را بفرستد و در سقف ۳۰ دقیقه لغو شد؛
  - اجرای دستی با `assume_last_commit=7d0416ed` فقط ۱۰۲ فایل برنامه‌ریزی کرد، ولی snapshot نتوانست فایل نمونه را بخواند و دیپلوی را متوقف کرد.

  هیچ فایلی آپلود نشد.
- جایگزین: یک zip با ۱۴۳ فایل خالی، ۱۰۲ فایل commit و `.deploy-state.json` که به `1bb2079f` اشاره می‌کند. این zip در File Manager در `/home/ahanamnc` باز می‌شود. مسیرش: `~/projects/sepahanfelez-staging/golive/sepahanfelez-golive.zip` (خارج از این مخزن).
- برای دیپلوی‌های بعدی: در cPanel ← Security ← Manage API Tokens یک توکن تازه بسازید و در Secret مخزن، `CPANEL_API_TOKEN`، بگذارید. این کار دسترسی admin مخزن می‌خواهد.

## برگشت

`REDESIGN_ENABLED=false` در `.env` هاست. قالب قبلی فوراً برمی‌گردد. متن‌های قبلی پنل در `migration/backup/<زمان>/` هستند. فایل‌های قبل از دیپلوی هم در artifact اجرای CI (pre-deploy snapshot) هستند.

## پیشنهادهای غیرضروری قبل یا بعد از لایو (از پنل)

- دسته‌ی «تست3» حذف شود، یا والدش «سر دسته» شود، تا صفحه‌ی «مفتول گالوانیزه» و ۱۳ کالایش برگردند.
- قیمت‌ها به‌روز شوند. آخرین تغییر قیمت ۲۰ خرداد ۱۴۰۵ است و قالب جدید تاریخ واقعی را نشان می‌دهد.
- متن «آموزش پنل کاربری» عوض شود (الان لورم ایپسوم است).
- ۴ عکسی که روی هاست نیستند دوباره بارگذاری شوند (فهرست در گزارش PDF، فصل ۷).
