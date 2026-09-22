<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductComment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The data layer the redesigned views talk to.
 *
 * Every method answers one question a template asks, and every one prefers the
 * database. Where the schema cannot answer yet, the method degrades to
 * something honest — a null the template hides, or a config value the page
 * labels as an estimate — never to an invented number.
 *
 * The methods that touch columns which may not exist on a given database
 * (ratingFor, weightOf, areaOf, categoryStats) check with Schema::hasColumn
 * and memoise the answer, so this file works unchanged before and after the
 * SQL in database/sql/ is applied.
 */
class Redesign
{
    /** Prices move once a day; ten minutes is plenty. */
    private const TTL = 600;

    public static function enabled(): bool
    {
        return (bool) config('redesign.enabled', true);
    }

    /* ---------------------------------------------------------------
     | Price freshness
     * --------------------------------------------------------------- */

    /**
     * Timestamp of the newest price row, for the «بروزرسانی» stamp.
     * The static prototype printed its build date; this reads the data.
     */
    public static function lastPriceUpdate(?int $categoryId = null)
    {
        return Cache::remember('redesign.last_price.' . ($categoryId ?: 'all'), self::TTL,
            function () use ($categoryId) {
                return Price::query()
                    ->whereHas('product', function ($p) use ($categoryId) {
                        $p->where('status', '=', '1');
                        if ($categoryId) {
                            $p->where('category_id', '=', $categoryId);
                        }
                    })
                    ->max('price_at');
            });
    }

    public static function updateTime(): string
    {
        return (string) config('redesign.update_time', '۱۲:۳۰');
    }

    public static function basketClaim(): string
    {
        return (string) config('redesign.basket_claim', '');
    }

    /* ---------------------------------------------------------------
     | Category aggregates — the numbers on the category hero
     * --------------------------------------------------------------- */

    /**
     * count / min / max / unit for one category, computed in SQL.
     *
     * Rows flagged `needs_review` are excluded from min and max: a price with
     * a digit missing would otherwise become the headline "from" figure. They
     * still appear in the table, carrying their own warning.
     */
    public static function categoryStats(Category $category): array
    {
        return Cache::remember('redesign.stats.' . $category->id, self::TTL, function () use ($category) {
            $base = Product::query()->where('category_id', $category->id)->where('status', '1');

            $sane = (clone $base);
            if (self::hasColumn('products', 'needs_review')) {
                $sane->where(function ($w) {
                    $w->where('needs_review', '=', 0)->orWhereNull('needs_review');
                });
            }

            $row  = $sane->selectRaw('MIN(price) AS mn, MAX(price) AS mx')->first();
            $unit = (clone $base)->value('unit');

            return [
                'count' => (int) (clone $base)->count(),
                'min'   => (int) ($row->mn ?? 0),
                'max'   => (int) ($row->mx ?? 0),
                'unit'  => $unit ?: '',
            ];
        });
    }

    /* ---------------------------------------------------------------
     | Latest price movements — the «آخرین تغییرات قیمت» table
     * --------------------------------------------------------------- */

    /**
     * Products whose last recorded change was largest by percentage.
     *
     * The old /price listed the most recent rows, which on a day when one
     * category was re-priced showed ten near-identical lines.
     */
    public static function biggestMovers(int $limit = 10)
    {
        return Cache::remember('redesign.movers.' . $limit, self::TTL, function () use ($limit) {
            $rows = Price::query()
                ->whereHas('product', function ($q) {
                    $q->where('status', '=', '1');
                })
                ->with(['product' => function ($q) {
                    $q->select('id', 'title', 'slug', 'unit', 'price', 'category_id', 'updated_at')
                      ->with(['category' => function ($c) {
                          $c->select('id', 'title', 'slug');
                      }]);
                }])
                ->orderBy('price_at', 'DESC')
                ->take(150)                       // a day or two of movements
                ->get();

            $newestPerProduct = [];
            foreach ($rows as $row) {
                if (! $row->product || ! $row->product->category) {
                    continue;
                }
                if (isset($newestPerProduct[$row->product_id])) {
                    continue;                      // already have this product's newest
                }
                if (! $row->first_price || $row->first_price == $row->second_price) {
                    continue;                      // no movement to report
                }
                $newestPerProduct[$row->product_id] = $row;
            }

            return collect($newestPerProduct)
                ->sortByDesc(function ($row) {
                    return abs(($row->second_price - $row->first_price) / max($row->first_price, 1));
                })
                ->take($limit)
                ->values();
        });
    }

    /* ---------------------------------------------------------------
     | Sales experts
     * --------------------------------------------------------------- */

    /**
     * Experts for a category slug, or all of them when $slug is null.
     * Prefers a `sales_reps` table the moment one exists.
     */
    public static function expertsFor(?string $slug = null): array
    {
        if (Schema::hasTable('sales_reps')) {
            $q = DB::table('sales_reps')->where('is_active', 1)->orderBy('order');

            if ($slug && Schema::hasTable('category_sales_rep')) {
                $ids = DB::table('category_sales_rep')
                    ->join('categories', 'categories.id', '=', 'category_sales_rep.category_id')
                    ->where('categories.slug', $slug)
                    ->pluck('category_sales_rep.sales_rep_id');

                if ($ids->isNotEmpty()) {
                    $q->whereIn('id', $ids);
                }
            }

            $rows = $q->get()->map(function ($r) {
                return (array) $r;
            })->all();

            if ($rows) {
                return $rows;
            }
        }

        $all = config('redesign.experts', []);

        if (! $slug) {
            return $all;
        }

        $matched = array_values(array_filter($all, function ($e) use ($slug) {
            return in_array($slug, $e['categories'] ?? [], true);
        }));

        // A category with nobody assigned still needs a name on the page.
        return $matched ?: array_slice($all, 0, 1);
    }

    /* ---------------------------------------------------------------
     | Reviews and ratings
     * --------------------------------------------------------------- */

    /** Approved comments for a category. Real rows only — no fallback. */
    public static function reviewsFor(Category $category)
    {
        return ProductComment::query()
            ->where('category_id', $category->id)
            ->where('is_approved', true)
            ->latest()
            ->take(6)
            ->get();
    }

    /**
     * Average rating and count, or null when the column does not exist yet.
     *
     * Returning null rather than 0 is what lets the view hide the stars —
     * an aggregateRating of zero in structured data is worse than none.
     */
    public static function ratingFor(Category $category): ?array
    {
        if (! self::hasColumn('product_comments', 'rating')) {
            return null;
        }

        $row = ProductComment::query()
            ->where('category_id', $category->id)
            ->where('is_approved', true)
            ->whereNotNull('rating')
            ->selectRaw('AVG(rating) AS avg_rating, COUNT(*) AS c')
            ->first();

        if (! $row || ! $row->c) {
            return null;
        }

        return ['average' => round((float) $row->avg_rating, 1), 'count' => (int) $row->c];
    }

    public static function hasRatingColumn(): bool
    {
        return self::hasColumn('product_comments', 'rating');
    }

    public static function sampleReviewsAllowed(): bool
    {
        return (bool) config('redesign.reviews.show_samples', false);
    }

    /* ---------------------------------------------------------------
     | Weight and area — what the calculator multiplies by
     * --------------------------------------------------------------- */

    /**
     * Kilograms per selling unit.
     *
     * Reads `products.weight_per_unit` when it exists; otherwise parses the
     * spec value that carries a weight. That parse is exactly why the numeric
     * column is worth adding — a column cannot be wrong the way a string can.
     */
    public static function weightOf(Product $product, $specValues = null): ?float
    {
        if (self::hasColumn('products', 'weight_per_unit') && $product->weight_per_unit) {
            return (float) $product->weight_per_unit;
        }

        if ($product->unit === 'کیلوگرم') {
            return 1.0;
        }

        return self::numberFromSpec($specValues, ['وزن']);
    }

    /** Square metres per selling unit; same contract as weightOf(). */
    public static function areaOf(Product $product, $specValues = null): ?float
    {
        if (self::hasColumn('products', 'area_per_unit') && $product->area_per_unit) {
            return (float) $product->area_per_unit;
        }

        if ($product->unit === 'مترمربع') {
            return 1.0;
        }

        return self::numberFromSpec($specValues, ['متراژ', 'مساحت']);
    }

    /** First number from a spec whose title contains one of $needles. */
    private static function numberFromSpec($specValues, array $needles): ?float
    {
        if (! $specValues) {
            return null;
        }

        foreach ($specValues as $sv) {
            $title = $sv->spec_title ?? $sv->title ?? '';
            $value = $sv->value_title ?? $sv->title ?? '';

            foreach ($needles as $needle) {
                if (mb_strpos((string) $title, $needle) !== false) {
                    $n = self::toFloat($value);
                    if ($n !== null) {
                        return $n;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse a Persian-written number.
     *
     * "/" is the decimal separator in this industry's data ("1/200" is 1.2),
     * which is the single most common way a naive parse of it goes wrong.
     */
    public static function toFloat($value): ?float
    {
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $s = strtr($s, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', '،' => '', ',' => '',
        ]);
        $s = str_replace('/', '.', $s);

        if (! preg_match('/\d+(?:\.\d+)?/', $s, $m)) {
            return null;
        }

        return (float) $m[0];
    }

    /* ---------------------------------------------------------------
     | Freight
     * --------------------------------------------------------------- */

    public static function freight(): array
    {
        if (Schema::hasTable('freight_rates')) {
            $rows = DB::table('freight_rates')->orderBy('order')->get();
            if ($rows->isNotEmpty()) {
                return $rows->map(function ($r) {
                    return ['name' => $r->destination, 'rate' => (int) $r->rate_per_ton];
                })->all();
            }
        }

        return config('redesign.freight', []);
    }

    public static function freightMinTon(): float
    {
        return (float) config('redesign.freight_min_ton', 1.0);
    }

    /* ---------------------------------------------------------------
     | Tag-based relations (both directions)
     * --------------------------------------------------------------- */

    /**
     * Articles related to a category through a shared tag.
     * Category::tags() and Article::tags() are both morphToMany on
     * `taggables`, so this is the relationship the panel already maintains.
     */
    public static function relatedArticles(Category $category, int $take = 3)
    {
        $tagIds = $category->tags->pluck('id')->all();

        if (! $tagIds) {
            return collect();
        }

        return Article::query()
            ->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            })
            ->with('category')
            ->latest()
            ->take($take)
            ->get();
    }

    /** The reverse: the product category to advertise beside an article. */
    public static function relatedCategories(Article $article, int $take = 2)
    {
        $tagIds = $article->tags->pluck('id')->all();

        if (! $tagIds) {
            return collect();
        }

        return Category::query()
            ->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            })
            ->where('status', true)
            ->take($take)
            ->get();
    }

    /** Estimated reading time in minutes. */
    public static function readingMinutes(?string $html): int
    {
        $text  = trim(strip_tags((string) $html));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text));

        return max(1, (int) round($words / 180));
    }

    /**
     * Schema::hasColumn queries information_schema; memoise it so a page with
     * seventy rows does not ask seventy times.
     */
    private static function hasColumn(string $table, string $column): bool
    {
        static $memo = [];
        $key = $table . '.' . $column;

        if (! array_key_exists($key, $memo)) {
            try {
                $memo[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable $e) {
                $memo[$key] = false;        // a page must not 500 over a probe
            }
        }

        return $memo[$key];
    }
}
