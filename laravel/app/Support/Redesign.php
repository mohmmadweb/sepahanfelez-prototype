<?php

namespace App\Support;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Category;
use App\Models\ProductComment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The data layer the redesigned templates talk to.
 *
 * The prototype was generated from build/catalog.json — one entry per
 * category, each with its spec columns and one row per product keyed by the
 * spec titles («ضخامت مفتول (mm)» …). Those titles *are* the `specs.title`
 * values in this database, because the catalogue was read off the live site.
 * So catalog() rebuilds exactly that structure from the database, and the
 * logic ported from the prototype (RedesignAnalysis) runs on it unchanged.
 *
 * Five queries build the whole catalogue; the result is cached for
 * config('redesign.cache_ttl') seconds and dropped whenever a product, price or
 * category is saved (see RedesignServiceProvider).
 *
 * Every method that touches a column or table the stock schema lacks checks
 * for it first, so this works before and after database/sql/*.sql is applied.
 */
class Redesign
{
    public const CACHE_KEYS = ['redesign.catalog', 'redesign.articles', 'redesign.search', Site::CACHE_KEY];

    public static function enabled(): bool
    {
        return (bool) config('redesign.enabled', false);
    }

    private static function ttl(): int
    {
        return (int) config('redesign.cache_ttl', 600);
    }

    public static function flush(): void
    {
        foreach (self::CACHE_KEYS as $k) {
            Cache::forget($k);
        }
    }

    /* ==================================================================
     | Catalogue
     * ================================================================== */

    /**
     * Ordered leaf categories that have at least one active product:
     *
     *   slug => [id, slug, title, unit, specs[], rows[], last_update]
     *
     * Each row: ['نام محصول' => title, 'واحد' => unit, <spec title> => value,
     *            '_id', '_slug', '_price', '_prev', '_at', '_image', '_review']
     *
     * `_prev` is the last recorded *different* price (prices.first_price of
     * the newest row), the same thing the prototype's «نوسان» column held.
     */
    public static function catalog(): array
    {
        return Cache::remember('redesign.catalog', self::ttl(), function () {
            return self::buildCatalog();
        });
    }

    private static function buildCatalog(): array
    {
        $cats = DB::table('categories')->select('id', 'parent_id', 'title', 'slug', 'order', 'status', 'image', 'icon', 'intro')->get();
        $parents = [];
        foreach ($cats as $c) {
            if ($c->parent_id) {
                $parents[$c->parent_id] = true;
            }
        }
        $leaves = $cats->filter(function ($c) use ($parents) {
            return (int) $c->status === 1 && ! isset($parents[$c->id]);
        })->keyBy('id');

        if ($leaves->isEmpty()) {
            return [];
        }

        $cols = ['id', 'category_id', 'title', 'unit', 'price', 'order'];
        foreach (['slug', 'image', 'weight_per_unit', 'area_per_unit', 'needs_review'] as $opt) {
            if (self::hasColumn('products', $opt)) {
                $cols[] = $opt;
            }
        }
        $products = DB::table('products')->select($cols)
            ->whereIn('category_id', $leaves->keys())
            ->where('status', 1)
            ->orderByRaw('CASE WHEN `order` IS NULL THEN 1 ELSE 0 END, `order`, id')
            ->get();

        if ($products->isEmpty()) {
            return [];
        }
        $ids = $products->pluck('id')->all();

        // Newest price row per product: previous price and when it was recorded.
        $latest = DB::table('prices')
            ->whereIn('id', function ($q) use ($ids) {
                $q->from('prices')->selectRaw('MAX(id)')->whereIn('product_id', $ids)->groupBy('product_id');
            })
            ->get(['product_id', 'first_price', 'second_price', 'price_at'])
            ->keyBy('product_id');

        $catSpecs = DB::table('category_spec')
            ->join('specs', 'specs.id', '=', 'category_spec.spec_id')
            ->whereIn('category_spec.category_id', $leaves->keys())
            ->orderBy('category_spec.sort')
            ->get(['category_spec.category_id', 'specs.id', 'specs.title']);

        $values = DB::table('product_spec_value')
            ->join('specs', 'specs.id', '=', 'product_spec_value.spec_id')
            ->leftJoin('values', 'values.id', '=', 'product_spec_value.value_id')
            ->whereIn('product_spec_value.product_id', $ids)
            ->get(['product_spec_value.product_id', 'specs.title as spec', 'values.title as value']);
        $valuesBy = [];
        foreach ($values as $v) {
            $valuesBy[$v->product_id][trim((string) $v->spec)] = trim((string) $v->value);
        }

        $out = [];
        foreach ($leaves as $cat) {
            $rows = [];
            foreach ($products as $p) {
                if ((int) $p->category_id !== (int) $cat->id) {
                    continue;
                }
                $l = $latest->get($p->id);
                $price = (int) $p->price;
                $prev = 0;
                if ($l) {
                    $prev = (int) $l->second_price === $price ? (int) $l->first_price : (int) $l->second_price;
                }
                $row = ['نام محصول' => (string) $p->title, 'واحد' => trim((string) $p->unit)];
                foreach ($valuesBy[$p->id] ?? [] as $k => $v) {
                    $row[$k] = $v;
                }
                $row['_id'] = (int) $p->id;
                // Only the admin's slug; no slug means no product page (see Rd::prodUrl).
                $row['_slug'] = isset($p->slug) && trim((string) $p->slug) !== '' ? (string) $p->slug : null;
                $row['_price'] = $price;
                $row['_prev'] = $prev === $price ? 0 : $prev;
                $row['_at'] = $l ? (string) $l->price_at : null;
                $row['_image'] = isset($p->image) && $p->image ? '/images/products/main/' . $p->image : null;
                $row['_kg'] = isset($p->weight_per_unit) && $p->weight_per_unit ? (float) $p->weight_per_unit : null;
                $row['_m2'] = isset($p->area_per_unit) && $p->area_per_unit ? (float) $p->area_per_unit : null;
                // A price the database flags for review (optional column) — never a list in a file.
                $row['_review'] = ! empty($p->needs_review) ? 'قیمت این ردیف در حال بازبینی است.' : null;
                $rows[] = $row;
            }
            if (! $rows) {
                continue;
            }
            $specs = [];
            foreach ($catSpecs as $s) {
                if ((int) $s->category_id === (int) $cat->id) {
                    $specs[] = trim((string) $s->title);
                }
            }
            // A spec that no product in the category fills is noise in a table.
            $specs = array_values(array_filter(array_unique($specs), function ($t) use ($rows) {
                foreach ($rows as $r) {
                    if (isset($r[$t]) && $r[$t] !== '') {
                        return true;
                    }
                }
                return false;
            }));
            $ats = array_filter(array_column($rows, '_at'));
            $out[$cat->slug] = [
                'id'          => (int) $cat->id,
                'slug'        => (string) $cat->slug,
                'title'       => (string) $cat->title,
                'order'       => $cat->order,
                'unit'        => $rows[0]['واحد'],
                'image'       => $cat->image ? '/images/category/main/' . $cat->image : null,
                'icon'        => $cat->icon ? '/images/category/icon/' . $cat->icon : null,
                'intro'       => (string) $cat->intro,
                'specs'       => $specs,
                'rows'        => $rows,
                'last_update' => $ats ? max($ats) : null,
            ];
        }

        // The order the admin set on the categories (admin → دسته‌بندی → ترتیب).
        uasort($out, function ($a, $b) {
            $oa = $a['order'] === null ? PHP_INT_MAX : (int) $a['order'];
            $ob = $b['order'] === null ? PHP_INT_MAX : (int) $b['order'];
            return $oa <=> $ob ?: $a['id'] <=> $b['id'];
        });

        return $out;
    }

    public static function category(string $slug): ?array
    {
        return self::catalog()[$slug] ?? null;
    }

    /** Find a product row (and its category slug) by product id. */
    public static function row(int $productId): ?array
    {
        foreach (self::catalog() as $slug => $cat) {
            foreach ($cat['rows'] as $i => $r) {
                if ($r['_id'] === $productId) {
                    return ['cat' => $slug, 'row' => $r, 'index' => $i];
                }
            }
        }

        return null;
    }

    public static function totalSkus(): int
    {
        return array_sum(array_map(function ($c) { return count($c['rows']); }, self::catalog()));
    }

    public static function nCats(): int
    {
        return count(self::catalog());
    }

    /** Newest price_at across the catalogue — the «بروزرسانی» date. */
    public static function lastUpdate(?string $slug = null)
    {
        if ($slug) {
            return self::catalog()[$slug]['last_update'] ?? null;
        }
        $ats = array_filter(array_column(self::catalog(), 'last_update'));

        return $ats ? max($ats) : null;
    }

    /** n / min / max / avg / unit of a category, flagged rows excluded (common.cat_stats). */
    public static function stats(string $slug): array
    {
        $cat = self::category($slug);
        if (! $cat) {
            return ['n' => 0, 'min' => 0, 'max' => 0, 'avg' => 0, 'unit' => ''];
        }
        $prices = [];
        foreach ($cat['rows'] as $r) {
            if ($r['_price'] > 0 && ! $r['_review']) {
                $prices[] = $r['_price'];
            }
        }

        return [
            'n'    => count($cat['rows']),
            'min'  => $prices ? min($prices) : 0,
            'max'  => $prices ? max($prices) : 0,
            'avg'  => $prices ? intdiv(array_sum($prices), count($prices)) : 0,
            'unit' => $cat['unit'],
        ];
    }

    /**
     * Spec columns for the compact price table: the first four in the order
     * the admin set (admin → دسته → ستون‌های جدول), loading place left out.
     * The full list is in the specification table lower on the page.
     */
    public static function keySpecs(string $slug): array
    {
        $cat = self::category($slug);
        if (! $cat) {
            return [];
        }

        return array_slice(array_values(array_filter($cat['specs'], function ($s) {
            return $s !== 'محل بارگیری';
        })), 0, 4);
    }

    /** Column heading: the spec title the admin gave, as it is. */
    public static function shortHead(string $spec): string
    {
        return $spec;
    }

    /** Most-moved products across the catalogue (tables.changes_table). */
    public static function movers(int $n = 10): array
    {
        $items = [];
        foreach (self::catalog() as $slug => $cat) {
            foreach ($cat['rows'] as $r) {
                if ($r['_review'] || ! $r['_price'] || ! $r['_prev']) {
                    continue;
                }
                $items[] = [abs(Rd::pct($r['_price'], $r['_prev'])), $slug, $r];
            }
        }
        usort($items, function ($a, $b) { return $b[0] <=> $a[0]; });

        return array_slice($items, 0, $n);
    }

    /**
     * Pictures of a category, all uploaded through the panel: the category's
     * own image first, then every product image in the category (admin →
     * محصول → محتوا → تصویر), no duplicates.
     */
    public static function photos(string $slug): array
    {
        $cat = self::category($slug);
        if (! $cat) {
            return [];
        }
        $list = [];
        if ($cat['image']) {
            $list[] = $cat['image'];
        }
        foreach ($cat['rows'] as $r) {
            if ($r['_image']) {
                $list[] = $r['_image'];
            }
        }

        return array_values(array_unique($list));
    }

    /** The picture for a category card: its image, else its icon, else a product's. */
    public static function cover(string $slug): ?string
    {
        $cat = self::category($slug);
        if (! $cat) {
            return null;
        }

        return $cat['image'] ?: ($cat['icon'] ?: (self::photos($slug)[0] ?? null));
    }

    /** The panel stores a thumbnail beside every uploaded image (Image::upload). */
    public static function thumb(?string $src): ?string
    {
        return $src ? str_replace('/main/', '/thumbnail/', $src) : $src;
    }

    public static function slugify(string $s): string
    {
        if (class_exists(\Normalizer::class)) {
            $s = (string) \Normalizer::normalize($s, \Normalizer::FORM_KC);
        }
        $s = trim($s);
        $s = str_replace(['*', '/', '"', '،'], ['x', '-', '', ''], $s);
        $s = preg_replace('/\s+/u', '-', $s);
        $s = preg_replace('/[^\w\-\x{0600}-\x{06FF}]/u', '', $s);

        return trim(preg_replace('/-{2,}/', '-', $s), '-');
    }

    /* ==================================================================
     | Price history — for assets/chart.js (data-src)
     * ================================================================== */

    /**
     * [[Y-m-d, price], ...] ascending, from the `prices` change log.
     *
     * Each prices row is one recorded change: first_price was the price up to
     * price_at, second_price from then on. That is exactly a step series and
     * needs no cron — which matters, because `price:update` (the only writer
     * of chart_price) is not scheduled and the host has no cron.
     */
    public static function productSeries(int $productId): array
    {
        $since = now()->subDays((int) config('redesign.chart.max_days', 365) + 30)->toDateString();
        $rows = DB::table('prices')->where('product_id', $productId)
            ->orderBy('price_at')->orderBy('id')
            ->get(['first_price', 'second_price', 'price_at']);
        $pts = [];
        foreach ($rows as $i => $r) {
            $day = substr((string) $r->price_at, 0, 10);
            if ($i === 0 && (int) $r->first_price > 0 && (int) $r->first_price !== (int) $r->second_price) {
                $pts[date('Y-m-d', strtotime($day . ' -1 day'))] = (int) $r->first_price;
            }
            $pts[$day] = (int) $r->second_price;
        }
        // chart_price holds daily snapshots when price:update has ever run; they
        // only fill days the change log does not cover.
        if (self::hasTable('chart_price')) {
            foreach (DB::table('chart_price')->where('product_id', $productId)
                         ->where('created_at', '>=', $since)->orderBy('created_at')
                         ->get(['price', 'created_at']) as $r) {
                $day = substr((string) $r->created_at, 0, 10);
                if (! isset($pts[$day]) && (int) $r->price > 0) {
                    $pts[$day] = (int) $r->price;
                }
            }
        }
        ksort($pts);
        $out = [];
        foreach ($pts as $d => $p) {
            if ($d >= $since || ! $out) {
                $out[] = [$d, $p];
            }
        }

        return $out;
    }

    /** Daily mean of the category's products, taken at every change date. */
    public static function categorySeries(int $categoryId): array
    {
        $cat = null;
        foreach (self::catalog() as $c) {
            if ($c['id'] === $categoryId) {
                $cat = $c;
            }
        }
        if (! $cat) {
            return [];
        }
        $series = [];
        $dates = [];
        foreach ($cat['rows'] as $r) {
            if ($r['_review']) {
                continue;
            }
            $s = self::productSeries($r['_id']);
            if (! $s) {
                $s = [[date('Y-m-d'), $r['_price']]];
            }
            $series[] = $s;
            foreach ($s as $p) {
                $dates[$p[0]] = true;
            }
        }
        ksort($dates);
        $out = [];
        foreach (array_keys($dates) as $d) {
            $sum = 0;
            $n = 0;
            foreach ($series as $s) {
                $v = null;
                foreach ($s as $p) {
                    if ($p[0] <= $d) {
                        $v = $p[1];
                    } else {
                        break;
                    }
                }
                if ($v) {
                    $sum += $v;
                    $n++;
                }
            }
            if ($n) {
                $out[] = [$d, (int) round($sum / $n)];
            }
        }

        return $out;
    }

    /* ==================================================================
     | Articles
     * ================================================================== */

    /**
     * Every article as a light array, newest first, with its category and a
     * reading time — the magazine pages, the related-articles blocks and the
     * search index all read from this one cached list.
     */
    public static function articles(): array
    {
        return Cache::remember('redesign.articles', self::ttl(), function () {
            $cats = ArticleCategory::query()->get(['id', 'title', 'slug'])->keyBy('id');
            $tags = [];
            if (self::hasTable('taggables') && self::hasTable('tags')) {
                foreach (DB::table('taggables')->join('tags', 'tags.id', '=', 'taggables.tag_id')
                             ->where('taggable_type', Article::class)
                             ->get(['taggables.taggable_id', 'tags.title']) as $t) {
                    $tags[$t->taggable_id][] = $t->title;
                }
            }
            $out = [];
            foreach (Article::query()->latest()->get(['id', 'category_id', 'title', 'slug', 'description', 'body',
                                                       'image', 'created_at', 'updated_at']) as $a) {
                $c = $cats->get($a->category_id);
                if (! $c) {
                    continue;
                }
                $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $a->body)));
                $out[] = [
                    'id'          => (int) $a->id,
                    'title'       => (string) $a->title,
                    'slug'        => (string) $a->slug,
                    'description' => Rd::plainDesc($a->description),
                    'image'       => $a->image && $a->image !== 'no-picture.jpg' ? $a->original_image() : null,
                    'cat_slug'    => (string) $c->slug,
                    'cat_title'   => (string) $c->title,
                    'published'   => optional($a->created_at)->toDateString(),
                    'modified'    => optional($a->updated_at)->toDateString(),
                    'read_min'    => max(1, (int) round(($plain === '' ? 0 : count(preg_split('/\s+/u', $plain))) / 180)),
                    'excerpt'     => mb_substr($plain, 0, 400),
                    'tags'        => $tags[$a->id] ?? [],
                    'url'         => Rd::uArticle($c->slug, $a->slug),
                ];
            }

            return $out;
        });
    }

    public static function articleArray(int $id): ?array
    {
        foreach (self::articles() as $a) {
            if ($a['id'] === $id) {
                return $a;
            }
        }

        return null;
    }

    /** blog slug => [title, count], most populated first. */
    public static function blogCats(): array
    {
        $out = [];
        foreach (self::articles() as $a) {
            if (! isset($out[$a['cat_slug']])) {
                $out[$a['cat_slug']] = ['title' => $a['cat_title'], 'n' => 0];
            }
            $out[$a['cat_slug']]['n']++;
        }
        uasort($out, function ($a, $b) { return $b['n'] <=> $a['n']; });

        return $out;
    }

    /** Tag ids attached to a model (admin → برچسب‌ها on the category / article form). */
    private static function tagIds(string $type, int $id): array
    {
        if (! self::hasTable('taggables')) {
            return [];
        }

        return DB::table('taggables')->where('taggable_type', $type)->where('taggable_id', $id)
            ->pluck('tag_id')->map('intval')->all();
    }

    /** Articles for a product category: those sharing a tag with it, then the newest. */
    public static function relatedArticles(string $slug, int $n = 3): array
    {
        $arts = self::articles();
        $out = [];
        $cat = self::category($slug);
        $tagIds = $cat ? self::tagIds(Category::class, $cat['id']) : [];
        if ($tagIds) {
            $artIds = DB::table('taggables')->where('taggable_type', Article::class)
                ->whereIn('tag_id', $tagIds)->pluck('taggable_id')->map('intval')->all();
            foreach ($arts as $a) {
                if (in_array($a['id'], $artIds, true)) {
                    $out[$a['id']] = $a;
                }
            }
        }
        foreach ($arts as $a) {
            if (count($out) >= $n) {
                break;
            }
            $out[$a['id']] = $a;
        }

        return array_slice(array_values($out), 0, $n);
    }

    /** Product categories beside an article: those sharing a tag with it, else the first ones. */
    public static function relatedCategories(array $article, int $n = 2): array
    {
        $cats = self::catalog();
        $tagIds = self::tagIds(Article::class, $article['id']);
        $keys = [];
        if ($tagIds) {
            foreach ($cats as $slug => $c) {
                if (array_intersect($tagIds, self::tagIds(Category::class, $c['id'])) && count($keys) < $n) {
                    $keys[] = $slug;
                }
            }
        }
        foreach (array_keys($cats) as $slug) {
            if (count($keys) >= $n) {
                break;
            }
            if (! in_array($slug, $keys, true)) {
                $keys[] = $slug;
            }
        }

        return $keys;
    }

    public static function sameCatArticles(array $article, int $n = 3): array
    {
        $out = [];
        foreach (self::articles() as $a) {
            if ($a['cat_slug'] === $article['cat_slug'] && $a['id'] !== $article['id'] && count($out) < $n) {
                $out[] = $a;
            }
        }
        foreach (self::articles() as $a) {
            if (count($out) >= $n) {
                break;
            }
            if ($a['id'] !== $article['id'] && ! in_array($a, $out, true)) {
                $out[] = $a;
            }
        }

        return $out;
    }

    /** Image for an article card: its own, else the related category's photo. */
    public static function articleImage(array $a): string
    {
        if ($a['image']) {
            return $a['image'];
        }
        $keys = self::relatedCategories($a, 1);
        $ph = $keys ? self::cover($keys[0]) : null;

        return $ph ?: "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 9'><rect width='16' height='9' fill='%23E9EFF6'/></svg>";
    }

    /** Headings of an article body → [body with ids, toc items] (blog._toc). */
    public static function toc(string $body): array
    {
        $items = [];
        $i = 0;
        $body = preg_replace_callback('~<h2([^>]*)>(.*?)</h2>~is', function ($m) use (&$items, &$i) {
            $i++;
            $items[] = ['h-' . $i, trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'))];
            $attrs = preg_replace('~\sid="[^"]*"~i', '', $m[1]);
            return '<h2 id="h-' . $i . '"' . $attrs . '>' . $m[2] . '</h2>';
        }, $body);

        return [$body, count($items) >= 2 ? $items : []];
    }

    /* ==================================================================
     | Site search — same records as the prototype's search-index.json
     * ================================================================== */

    public static function searchIndex(): array
    {
        return Cache::remember('redesign.search', self::ttl(), function () {
            $out = [];
            foreach (self::catalog() as $slug => $cat) {
                $out[] = ['t' => $cat['title'], 'u' => Rd::uCat($slug), 'k' => 'cat',
                          's' => Rd::fa(count($cat['rows'])) . ' نوع کالا',
                          'x' => mb_substr(trim(strip_tags($cat['intro'])), 0, 300), 'w' => 90];
            }
            foreach (self::catalog() as $slug => $cat) {
                foreach ($cat['rows'] as $r) {
                    if ($r['_review']) {
                        continue;
                    }
                    $out[] = ['t' => Rd::fa(Rd::cleanName($r['نام محصول'])), 'u' => Rd::prodUrl($slug, $r['_slug']) ?: Rd::uCat($slug),
                              'k' => 'prod', 's' => $cat['title'], 'x' => $r['نام محصول'],
                              'p' => $r['_price'], 'unit' => $r['واحد'], 'w' => 100];
                }
            }
            $seen = [];
            foreach (self::articles() as $a) {
                $out[] = ['t' => $a['title'], 'u' => $a['url'], 'k' => 'art', 's' => $a['cat_title'],
                          'x' => $a['description'] . ' ' . $a['excerpt'], 'w' => 60];
                $seen[$a['cat_slug']] = $a['cat_title'];
            }
            foreach ($seen as $slug => $title) {
                $out[] = ['t' => 'مجله — ' . $title, 'u' => Rd::uBlogCat($slug), 'k' => 'blogcat',
                          's' => 'دسته‌ی مقالات', 'x' => $title, 'w' => 50];
            }
            foreach ([
                ['قیمت لحظه‌ای', '/price', 'جدول قیمت همه‌ی کالاها', 'قیمت روز لیست قیمت نرخ امروز استعلام'],
                ['دسته‌های محصول', '/category/', 'همه‌ی دسته‌ها', 'محصولات لیست کالا'],
                ['مجله سپاهان فلز', '/blog', 'مقالات تخصصی', 'بلاگ اخبار مقاله وبلاگ'],
                ['درباره کارخانه', '/about', 'صنایع مفتولی طلوع سپاهان', 'درباره ما کارخانه تولید سابقه'],
                ['تماس با ما', '/contact', 'دفتر فروش و کارخانه', 'تماس شماره آدرس تلفن دفتر تهران اصفهان'],
                ['ورود / ثبت‌نام', '/login', 'حساب کاربری', 'ورود ثبت نام حساب کاربری پنل'],
            ] as $p) {
                $out[] = ['t' => $p[0], 'u' => $p[1], 'k' => 'page', 's' => $p[2], 'x' => $p[3], 'w' => 50];
            }

            return $out;
        });
    }

    /* ==================================================================
     | Reviews
     * ================================================================== */

    /** Approved comments of a category, newest first. */
    public static function reviews(int $categoryId, int $take = 8)
    {
        return ProductComment::query()->where('category_id', $categoryId)->where('is_approved', true)
            ->latest()->take($take)->get();
    }

    public static function hasRating(): bool
    {
        return self::hasColumn('product_comments', 'rating');
    }

    /* ==================================================================
     | Internals
     * ================================================================== */

    public static function hasTable(string $table): bool
    {
        static $memo = [];
        if (! array_key_exists($table, $memo)) {
            try {
                // Cached across requests: information_schema is not free on a busy host.
                $memo[$table] = (bool) Cache::remember('redesign.schema.t.' . $table, 3600, function () use ($table) {
                    return Schema::hasTable($table) ? 1 : 0;
                });
            } catch (\Throwable $e) {
                $memo[$table] = false;
            }
        }

        return $memo[$table];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        static $memo = [];
        $key = $table . '.' . $column;
        if (! array_key_exists($key, $memo)) {
            try {
                $memo[$key] = (bool) Cache::remember('redesign.schema.c.' . $key, 3600, function () use ($table, $column) {
                    return Schema::hasColumn($table, $column) ? 1 : 0;
                });
            } catch (\Throwable $e) {
                $memo[$key] = false;
            }
        }

        return $memo[$key];
    }
}
