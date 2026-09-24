<?php

/*
|--------------------------------------------------------------------------
| Redesign (سپاهان فلز ۱۴۰۵)
|--------------------------------------------------------------------------
|
| The redesigned front end was agreed as a static prototype first
| (sepahanfelez.lenzit.ir). This kit is the same design running on the real
| database. Three places hold what it needs:
|
|   database                         every number: categories, products, prices,
|                                    specs, articles, comments, users
|   resources/redesign/content.php   every piece of editorial copy, GENERATED
|                                    from the prototype by tools/build_kit.py
|   this file                        switches only
|
| Nothing here duplicates something the admin panel edits.
|
*/

return [

    /*
    | Master switch.
    |
    | true  → RedesignServiceProvider puts resources/views/redesign in front of
    |         the view finder, so every public page, the login screens and the
    |         user panel render the new templates.
    | false → the provider does nothing at all; the previous templates render,
    |         untouched. That is the rollback — one line in .env.
    |
    | The admin panel (/admin) is never affected either way.
    */
    'enabled' => env('REDESIGN_ENABLED', false),

    // Absolute origin used for canonical URLs and structured data.
    'site_url' => env('REDESIGN_SITE_URL', 'https://sepahanfelez.ir'),

    /*
    | Persian digits in the rendered HTML.
    |
    | The prototype writes every digit in text as Persian. On the live site the
    | database holds Latin digits (article bodies, spec values), so the same
    | conversion is done on the way out by App\Http\Middleware\RedesignFaDigits.
    | Scripts, styles, textareas and attribute values are never touched.
    */
    'fa_digits' => env('REDESIGN_FA_DIGITS', true),

    /*
    | Price-history chart.
    |
    | Real data: the `prices` table that every Excel import writes to, so it
    | needs no cron. `sample_series` only decides what to draw for a product
    | with fewer than two recorded days — a clearly labelled demo series
    | (true) or the sentence «not enough history yet» (false).
    */
    'chart' => [
        'sample_series' => env('REDESIGN_CHART_SAMPLE', false),
        'max_days'      => 365,
    ],

    /*
    | Reviews.
    |
    | Approved rows of `product_comments` render as soon as they exist. The
    | prototype's sample reviews are shown, badged «نمونه», only for a category
    | with no approved comment and only while this is true. Keep false live.
    */
    'reviews' => [
        'show_samples' => env('REDESIGN_SAMPLE_REVIEWS', false),
    ],

    /*
    | The cart is gone from the design: orders are placed by phone. With this
    | on, GET /cart answers 301 → /price, so old links and bookmarks land
    | somewhere useful. The cart POST routes are left alone (nothing links to
    | them any more).
    */
    'redirect_cart' => true,

    // How long computed data (catalogue, stats, movers, search index) is cached.
    'cache_ttl' => 600,
];
