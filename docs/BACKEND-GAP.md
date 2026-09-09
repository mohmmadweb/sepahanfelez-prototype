# پروتوتایپ در برابر بک‌اند فعلی (Ahanamn / لاراول ۸)

**تاریخ:** ۱۸ شهریور ۱۴۰۵ · **مرجع بک‌اند:** `github.com/mohammad-kasiri/Ahanamn` (کلون در `~/projects/Ahanamn`)
**مرجع پروتوتایپ:** همین مخزن، خروجی `python3 build/gen.py` (۱۲۲ صفحه) · نمایش زنده: sepahanfelez.lenzit.ir

این سند دو سؤال را جواب می‌دهد: **کدام بخش‌های پروتوتایپ همین حالا در بک‌اند و پنل ادمین
وجود دارد و فقط ظاهرش عوض شده؟** و **کدام بخش‌ها کد بک‌اند اضافه می‌خواهند؟**

---

## ۱. آنچه بک‌اند دارد و پروتوتایپ فقط بازطراحی کرده (بدون تغییر دیتابیس)

| بخش پروتوتایپ | منبع در بک‌اند | جدول / کنترلر | ادمین |
|---|---|---|---|
| منوی دسته‌ها، صفحه‌ی دسته (`/category/{slug}`) | `Site\CategoryController@index` | `categories` (title, intro, body, image, icon, video, meta, order, our_product) | `/admin/category` |
| جدول قیمت هر دسته با ستون‌های مشخصات | `category_spec` + `product_spec_value` | `specs`, `values` | `/admin/spec`, `/admin/category/{id}/columns` (ترتیب ستون‌ها) |
| صفحه‌ی محصول (`/category/{cat}/{product}`) | `Site\ProductController@show` | `products` (title, price, unit, slug, image, description, meta, tax) | `/admin/category/{id}/product` + «محتوا» |
| ستون «نوسان» و کارت «آخرین تغییرات قیمت» | `prices` (first_price, second_price, price_at) | `Price::status()/percentage()/difference()` | خودکار با ایمپورت اکسل |
| صفحه‌ی «قیمت لحظه‌ای» (`/price`) | `Site\PriceListController@index` (فیلتر دسته، صفحه‌بندی ۲۰تایی) | `prices` join `products` | — |
| بروزرسانی روزانه‌ی قیمت | `/admin/excel` (اکسپورت → ویرایش → ایمپورت) | `App\Excel\ProductsImport` قیمت جدید را در `prices` ثبت و `products.price` را به‌روز می‌کند | `/admin/excel` |
| نمودار قیمت محصول و میانگین دسته | `/product/{id}/{week\|month}` و `/category/chart/{id}/{tf}` | `chart_price`, `daily_avg_price` | دستور `price:update` |
| مجله (`/blog`, `/blog/{cat}`, `/blog/{cat}/{article}`) | `Site\BlogController` | `article_categories`, `articles` (title, description, body, image, meta, view_count), `taggables` | `/admin/article`, `/admin/article-category`, `/admin/tag` |
| مقالات مرتبط زیر صفحه‌ی دسته | `CategoryController::relatedArticles()` — برچسب مشترک دسته و مقاله | `tags` + `taggables` | `/admin/tag` و انتخاب برچسب در فرم دسته/مقاله |
| دیدگاه‌ها (دسته، مقاله) | `Site\CommentController` | `product_comments` (به دسته وصل است، نه محصول), `article_comments` | `/admin/category_comment`, `/admin/article_comments` |
| اسلایدر صفحه‌ی اصلی | `HomeController` → `$slides` | `sliders` (image, link, alt) | `/admin/setting/slider` |
| متن «درباره» صفحه‌ی اصلی و عکس‌ها | `home_settings` (about, about_pic, footer_pic1/2, عنوان و توضیح متای خانه و قیمت) | — | `/admin/home_setting` |
| تلفن، ایمیل، ساعت کاری، سه نشانی | `information` (main_address, factory_address_1..3, email, phone, work_time, about) | — | `/admin/informarion` |
| آیکون شبکه‌های اجتماعی | `socials` | — | `/admin/social` |
| صفحه‌ی درباره | `abouts` (image, video, text) + فهرست ادمین‌ها | `Site\AboutController` | `/admin/about` |
| فرم تماس | `POST /contact` (نام، نام‌خانوادگی، تلفن، ایمیل، موضوع، متن ≥۱۰) | `contacts` | `/admin/contact` |
| جست‌وجوی سرصفحه | `/api/category-search/{key}` | `categories` | — |
| «لیست سفارش» و «ناحیه کاربری» در نوار بالا | `/cart`, `/login`, پنل کاربر (`/user/*`) | `carts`, `orders`, `users`, `tickets`, `addresses` | `/admin/order`, `/admin/user`, `/admin/ticket` |
| خبرنامه، همکاری، کد تخفیف، ریدایرکت، سایت‌مپ، نقش‌ها | همه موجود | `newsletter_members`, `collaborations`, `discounts`, `redirects`, `roles/permissions` | زیر `/admin` |
| چاپ/PDF جدول دسته | `POST /category/{cat}/print` | `site/PDF/category.blade.php` | — |
| اسکیمای Product/Article/Breadcrumb | `App\Support\Schema` | — | — |

نتیجه: **ساختار داده‌ی همه‌ی صفحات پروتوتایپ (اصلی، قیمت لحظه‌ای، دسته، محصول، مجله، درباره، تماس) در بک‌اند
هست.** پیاده‌سازی نسخه‌ی جدید یعنی بازنویسی Blade و SCSS در `resources/views/site/*` و `resources/sass/*`،
نه مهاجرت دیتابیس — به‌جز موارد بخش ۲.

> نکته: پروتوتایپ عمداً «افزودن به سبد» را از جدول قیمت حذف کرده و به‌جایش تماس گذاشته
> (استراتژی زنگ‌خور). سبد و سفارش در بک‌اند سالم‌اند و اگر بخواهید برمی‌گردند.

---

## ۲. آنچه پروتوتایپ دارد و بک‌اند برایش کد می‌خواهد

به ترتیب اولویت. ستون «اندازه» برآورد کار است: **S** چند ساعت · **M** یک تا دو روز · **L** بیش از دو روز.

| # | قابلیت در پروتوتایپ | وضعیت در بک‌اند | کار لازم | اندازه |
|---|---|---|---|---|
| ۱ | **گالری عکس محصول** (سه عکس هر دسته، بندانگشتی و تصویر اصلی) | `categories.image` و `products.image` هر کدام **یک** عکس. جدول `photos(name)` هست ولی به هیچ‌چیز وصل نیست | ستون `imageable_type/imageable_id` روی `photos` (یا جدول `category_photos`)، آپلود چندتایی در فرم دسته/محصول، خروجی در ویو | M |
| ۲ | **ستون‌های کلیدی جدول قیمت** (حداکثر ۴ ستون هر دسته؛ بقیه در جدول مشخصات کامل) | `category_spec.sort` هست، ولی نشانه‌ی «در جدول قیمت نمایش بده» نیست | ستون `category_spec.in_price_table boolean` + چک‌باکس در `/admin/category/{id}/columns` | S |
| ۳ | **قیمت قابل‌مقایسه** (هر متر مربع / هر کیلو / هر رول / هر تن) و مقایسه با محصول هم‌دسته | وزن و ابعاد، متن آزاد در `values.title` («22 کیلوگرم»، «30*1/20») — قابل ضرب نیست | ستون‌های عددی `products.weight_per_unit decimal`, `products.area_per_unit decimal` (یا نوع عددی برای spec) + یک `PriceEconomics` service که همان منطق `build/analysis.py` را پیاده کند | M |
| ۴ | **نشان «بازبینی»** روی ردیف‌های پرت (قیمت ناهم‌خوان با دسته) و کنارگذاشتن آن‌ها از کمترین/بیشترین | ندارد | ستون `products.needs_review boolean` که ایمپورت اکسل با قاعده‌ی «بیش از ۴ برابر میانه‌ی دسته» پر می‌کند + هشدار در پنل | S |
| ۵ | **کارت «آخرین تغییرات قیمت»** (۱۰ کالا با بیشترین درصد تغییر) | `/price` فهرست ساده‌ی آخرین رکوردهاست | یک کوئری در `PriceListController` مرتب بر `abs((second_price-first_price)/first_price)` | S |
| ۶ | **بروزرسانی هر روز ساعت ۱۲:۳۰** و مهر «آخرین بروزرسانی» | `price:update` تعریف شده ولی در `Kernel::schedule` **ثبت نشده** و هاست cron ندارد (DEPLOYMENT.md) | ثبت `->dailyAt('12:30')` + اجرای cron از cPanel یا GitHub Actions به یک مسیر امن؛ مهر «بروزرسانی» از `max(prices.price_at)` خوانده شود، نه از ساعت سیستم | S–M |
| ۷ | **محصول مرتبط زیر مقاله** (کارت دسته با بازه‌ی قیمت) | `Tag` به دسته و مقاله وصل است (`morphedByMany`) ولی متد `Article::relatedCategories()` و بلوک ویو نیست | متد رابطه + بلوک در `blog/show.blade.php`؛ ادمین باید مقاله‌ها را برچسب بزند. (پروتوتایپ فعلاً با کلیدواژه ربط می‌دهد) | S |
| ۸ | **مقالات مرتبط زیر محصول** | فقط زیر **دسته** هست (`relatedArticles`) — `Product` برچسب ندارد | در `ProductController@show` همان `category->relatedArticles()` را پاس بدهید؛ یا `taggable` روی محصول | S |
| ۹ | **زمان مطالعه و تاریخ شمسی روی کارت‌ها** | `view_count` هست؛ زمان مطالعه ندارد | اکسسور `Article::readMinutes()` = `str_word_count(strip_tags(body))/180` | S |
| ۱۰ | **چیدمان مجله (شاخص + شبکه + چیپ دسته‌ها با شمارنده)** | `BlogController@index` داده‌ی «۴ تازه / ۴ پربازدید / تصادفی» می‌دهد | تغییر کوئری‌ها به «همه به ترتیب تاریخ» + `withCount('articles')` روی دسته‌ها؛ ویو جدید | S |
| ۱۱ | **عنوان روی اسلاید** («سالن کشش مفتول») | `sliders` فقط image/link/alt | ستون `sliders.title nullable` + فیلد در فرم؛ یا عنوان داخل خود عکس | S |
| ۱۲ | **سه ویدئوی هوایی (دو واحد + دفتر تهران) و پوسترها** | ایستا | فایل‌ها در `public_html/assets/factory/` (پایپ‌لاین استقرار `assets/` را آپلود می‌کند). ویدئوی دفتر تهران از تصویر ماهواره‌ای ساخته شده؛ اگر فیلم واقعی هلی‌شات دارید جایگزین کنید | S |
| ۱۳ | **جست‌وجوی سراسری و فهرست پرش دسته‌ها در `/price`** | — | فقط جاوااسکریپت سمت کاربر (`assets/site.js`)؛ بک‌اند نمی‌خواهد | — |
| ۱۴ | **نام‌های تمیز محصول** («سانتیمترعرض امتر» → «سانتی‌متر عرض ۱ متر») | عنوان‌ها در `products.title` با غلط تایپی | اصلاح در ادمین یا یک `Product::displayTitle()` با همان قواعد `common.clean_name` | S |
| ۱۵ | **اخبار** | مسیرهای `/news` عمداً `410 Gone` برمی‌گردانند؛ مدل و ادمین هستند | پروتوتایپ «مجله» را جای اخبار گذاشته. اگر اخبار جدا می‌خواهید: حذف `abort(410)` در `NewsController` + ویو جدید به سبک مجله | S–M |
| ۱۶ | فرم درخواست تماس سبک (فقط نام و تلفن) | `POST /contact` شش فیلد اجباری دارد | اختیاری: `POST /callback` با دو فیلد + جدول `callbacks` | S |
| ۱۷ | «تست۳» و «توری کششی» بدون محصول | در سایت زنده ایندکس می‌شوند | در ادمین `status=0` کنید؛ کد نمی‌خواهد | — |

### تغییرات متنی درخواستی — کجای بک‌اند اعمال می‌شود

| تغییر | محل |
|---|---|
| «کد کالا» → «نوع کالا» | ویوهای `site/category.blade.php`, `site/price.blade.php`, `site/home.blade.php` |
| حذف «ارزش افزوده جداگانه محاسبه می‌شود» | همان ویوها + `home_settings.about` در ادمین |
| ساعت بروزرسانی ۱۲:۳۰ | متن ویو + زمان‌بندی `price:update` (ردیف ۶) |
| «قیمت قطعی خود را از کارشناسان ما بگیرید» | ویوها (بلوک تماس) |
| «امکان خرید مستقیم از کارخانه و انبار تهران» به‌جای «محل بارگیری اصفهان یا تهران» | ویوها؛ مقدار spec «محل بارگیری» در `values` را می‌توان از جدول‌ها حذف کرد (ردیف ۲) |
| نشانی دفتر تهران (پارس فلز، پلاک ۹) | `information.main_address` در `/admin/informarion` |
| ۳۰٬۰۰۰ مترمربع سالن، ۲۰۰ پرسنل | `abouts.text`, `home_settings.about`, `information.about` |
| کپی‌رایت «فروشگاه اینترنتی صنایع مفتولی طلوع سپاهان» | `site/layout/footer.blade.php` (bottom-bar) |
| «صنایع مفتولی طلوع سپاهان» به‌جای «خط تولید از آنِ خودمان است» | ویو خانه (بخش مزیت‌ها) |
| «کامل‌ترین سبد کالایی کشور» | ویو خانه، `abouts.text`، متای صفحه‌ها (`home_settings.home_description`). این یک ادعای تبلیغاتی است؛ برای تبلیغات رسمی مستند نگه دارید |
| حذف نوار «سال تأسیس / ظرفیت / …» بالای اسلایدر | فقط ویو |
| بدون جدول قیمت در صفحه‌ی اصلی؛ لینک به `/price` | فقط ویو (`home.blade.php` بلوک `moment-price`) |

---

## ۳. نکات فنی برای پیاده‌سازی

- **نشانی‌ها یکی است.** پروتوتایپ دقیقاً با `routes/web.php` ساخته شده: `/price`, `/category/`, `/category/{slug}`, `/category/{slug}/{product}`, `/blog`, `/blog/{cat}`, `/blog/{cat}/{article}`, `/about`, `/contact`. اسلاگ‌ها همان ستون `slug` است؛ دو اسلاگ دستی در `build/slugs.json` ثبت شده.
- **CSS و JS پروتوتایپ مستقل از Bootstrap است** (`assets/app.css` ~۱۸۰۰ خط، سه فایل JS کوچک بدون jQuery). می‌تواند کنار `files/css/app.css` فعلی بنشیند یا جایگزینش شود؛ Mix فعلی برای بیلد کافی است.
- **ارقام فارسی** در پروتوتایپ با پس‌پردازش روی HTML اعمال می‌شود (`gen.py::fa_digits`). در لاراول یک helper `fa()` روی خروجی `number_format` کافی است؛ اعداد داخل `href`/`data-*` لاتین بمانند.
- **داده‌ی مقاله‌ها** عیناً از سایت زنده برداشته شده (۳۱ مقاله، ۶ دسته) و در `build/articles.json` پاک‌سازی شده: استایل درون‌خطی حذف، لینک‌ها نسبی، «آهن امن» → «سپاهان فلز». همین پاک‌سازی را روی `articles.body` در دیتابیس هم اجرا کنید (یک `artisan` یک‌باره).
- **دو تصویر مقاله هنوز واترمارک «AHAN AMN» دارند** (مفتول گالوانیزه، پایه فنس). با عکس جدید جایگزین شوند.
