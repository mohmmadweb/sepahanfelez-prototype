<?php

/*
|--------------------------------------------------------------------------
| Redesign (سپاهان فلز ۱۴۰۵)
|--------------------------------------------------------------------------
|
| The redesigned front end was built as a static prototype first
| (sepahanfelez.lenzit.ir) so the layout could be agreed before any database
| work. This file is the seam between the two.
|
| RULE
| ----
| Every value a page shows comes from the database when the database has it.
| This file only holds what the schema cannot express yet, and each of those
| entries names the table that will replace it. Nothing here duplicates
| something the admin panel already edits — that would create two sources of
| truth and guarantee they drift.
|
| See docs/REDESIGN-BACKEND.md for the per-feature mapping and the SQL that
| turns the remaining mock values into real columns.
|
*/

return [

    /*
    | Master switch.
    |
    | true  → the redesigned views render (site.redesign.*)
    | false → the previous views render, untouched
    |
    | Both sets of templates stay in the repository. Flipping this back is the
    | rollback, and it needs no other deploy.
    */
    'enabled' => env('REDESIGN_ENABLED', true),

    /*
    | Price freshness.
    |
    | The update *time* is a statement about the business, not about a row, so
    | it lives here. The update *date* beside it is always read from
    | MAX(prices.price_at) — see App\Support\Redesign::lastPriceUpdate().
    */
    'update_time' => '۱۲:۳۰',

    // Positioning line, repeated in <title>, the home hero and /about.
    'basket_claim' => 'کامل‌ترین سبد کالایی صنایع مفتولی کشور',

    /*
    | Sales experts.
    |
    | MOCK — there is no `sales_reps` table. The prototype shows a named expert
    | with a direct extension beside every price table because the two
    | strongest competitors do (شهر مفتول، آهن آنلاین) and it shortens the call.
    |
    | `categories` holds category SLUGS; a slug that does not exist is ignored,
    | so this list cannot break a page.
    |
    | TO REPLACE: create `sales_reps` + `category_sales_rep` (SQL in
    | database/sql/), then delete this key — Redesign::expertsFor() already
    | prefers the table whenever it exists.
    */
    'experts' => [
        [
            'name' => 'مهندس محمدی', 'role' => 'سرپرست فروش', 'ext' => '101',
            'photo' => '/assets/experts/expert-1.svg', 'hours' => 'شنبه تا چهارشنبه ۸ تا ۱۷',
            'categories' => ['توری-حصاری', 'توری-پرسی', 'توری-گابیون'],
        ],
        [
            'name' => 'خانم زارعی', 'role' => 'کارشناس فروش توری', 'ext' => '102',
            'photo' => '/assets/experts/expert-2.svg', 'hours' => 'شنبه تا پنجشنبه ۸ تا ۱۳',
            'categories' => ['توری-مرغی', 'توری-فرنگی', 'توری-جوشی--گالوانیزه-رول'],
        ],
        [
            'name' => 'مهندس احمدی', 'role' => 'کارشناس فروش مفتول و مش', 'ext' => '103',
            'photo' => '/assets/experts/expert-3.svg', 'hours' => 'شنبه تا چهارشنبه ۸ تا ۱۷',
            'categories' => ['مش-جوشی-یا-مش-آهنی', 'سیم-سیاه-و-آرماتور-بندی'],
        ],
        [
            'name' => 'آقای سلیمانی', 'role' => 'کارشناس فروش سیم خاردار و پروژه', 'ext' => '104',
            'photo' => '/assets/experts/expert-4.svg', 'hours' => 'شنبه تا چهارشنبه ۸ تا ۱۷',
            'categories' => ['سیم-خاردار'],
        ],
    ],

    /*
    | Freight estimate, rial per tonne.
    |
    | MOCK — nothing in the schema models shipping. The calculator states on
    | the page that this is an estimate and that the binding number comes by
    | phone, which is what the sales process actually does.
    |
    | TO REPLACE: `freight_rates` (destination, rate_per_ton, order).
    | A rate of 0 means "collected from works/warehouse".
    */
    'freight' => [
        ['name' => 'تحویل درب کارخانه (اصفهان)', 'rate' => 0],
        ['name' => 'تحویل درب انبار تهران', 'rate' => 0],
        ['name' => 'اصفهان و شهرستان‌های استان', 'rate' => 9000000],
        ['name' => 'تهران و البرز', 'rate' => 16000000],
        ['name' => 'استان‌های مرکزی (قم، اراک، یزد، چهارمحال)', 'rate' => 18000000],
        ['name' => 'شمال و شمال غرب', 'rate' => 26000000],
        ['name' => 'جنوب و جنوب شرق', 'rate' => 32000000],
    ],
    'freight_min_ton' => 1.0,

    /*
    | Price-history chart.
    |
    | NOT mock: `chart_price` and `daily_avg_price` are real tables and
    | Site\PriceController already serves both. `sample_series` only decides
    | what to draw when a product has fewer than two stored points — a new
    | product on its first day, which is otherwise an empty box. The caption
    | under the chart always says which of the two it is showing.
    */
    'chart' => [
        'sample_series' => env('REDESIGN_CHART_SAMPLE', true),
        'default_days'  => 30,
    ],

    /*
    | Reviews.
    |
    | `product_comments` is real (name, body, answer, is_approved) but it hangs
    | off `category_id`, not `product_id`, and has no rating column. So: real
    | comments render as soon as they exist; stars need one migration. Until
    | then Redesign::ratingFor() returns null and the view drops the star block
    | rather than inventing a score.
    |
    | Samples are labelled «نمونه» on the page and only appear when a category
    | has no approved comment at all. Set false before launch.
    */
    'reviews' => [
        'show_samples' => env('REDESIGN_SAMPLE_REVIEWS', true),
    ],

    /*
    | How many spec columns the compact price table shows. Ordering already
    | comes from `category_spec.sort` (admin → ستون‌های جدول); this caps it.
    | The full list appears in the «مشخصات فنی کامل» table lower down.
    |
    | TO REPLACE with `category_spec.in_price_table` (boolean).
    */
    'price_table_specs' => 4,

    /*
    | Sample reviews, keyed by category slug. Only used when a category has no
    | approved comment. Delete this key once real comments exist.
    */
    'sample_reviews' => [
        'توری-حصاری' => [
            ['پیمانکار محوطه‌سازی', 5, 'برای حصار ۴۰۰ متری باغ، چشمه ۶/۵ مفتول ۲/۷ گرفتیم. وزن مترمربع دقیقاً همان بود که در جدول نوشته بود و با باسکول تحویل داد.'],
            ['خریدار سازمانی', 4, 'قیمت کیلویی و وزن هر متر در جدول، کار مقایسه را راحت کرد. زمان تحویل تهران دو روز شد.'],
        ],
        'توری-پرسی' => [
            ['کارگاه نرده‌سازی', 5, 'چشمه ۳×۳ مفتول ۴ برای حفاظ پنجره؛ برگ‌ها صاف و یکنواخت بودند.'],
        ],
        'مش-جوشی-یا-مش-آهنی' => [
            ['مجری کف‌سازی', 5, 'چشمه ۱۵×۱۵ برای کف انبار. وزن برگ ۳۶ کیلو با باسکول یکی درآمد.'],
        ],
        'توری-مرغی' => [
            ['مرغداری', 5, 'رول ۹ کیلویی عرض ۱۲۰ برای قفس؛ گالوانیزه‌ی گرم واقعی بود و بعد از یک زمستان زنگ نزد.'],
        ],
        'توری-گابیون' => [
            ['مهندس ناظر پروژه', 5, 'برای دیوار حائل ۱/۵ متری، مفتول ۱/۷ سفارش دادیم. بافت دوتاب و وزن ۵۵۰ گرم مطابق جدول.'],
        ],
        'سیم-خاردار' => [
            ['مدیر تأسیسات', 5, 'کلاف سوزنی قطر ۹۰ برای بالای دیوار؛ خارها تیز و گالوانیزه یکدست.'],
        ],
    ],
];
