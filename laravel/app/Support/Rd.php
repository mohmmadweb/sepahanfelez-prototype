<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * The redesign's view helpers — a PHP port of the prototype's build/common.py
 * and build/schema.py, so a Blade template can say exactly what the Python
 * generator said and produce the same markup.
 *
 * Registered as the alias `Rd` by RedesignServiceProvider; templates call
 * Rd::fmt(), Rd::icon(), Rd::jDate() and so on.
 *
 * PHP 7.4 on purpose: the production vendor tree is resolved for 7.4.
 */
class Rd
{
    /* ------------------------------------------------------------------
     | Contact — all from the admin panel, see App\Support\Site
     * ------------------------------------------------------------------ */

    public static function phone(): string     { return Site::phone(); }
    public static function phoneShow(): string { return Site::phoneShow(); }
    public static function wa(): string        { return Site::whatsapp(); }
    public static function waShow(): string    { return Site::whatsappShow(); }

    /** «۱۲:۳۰» — the time of day of the newest price row, not a promise in a file. */
    public static function updatedAt($at = null): string
    {
        $at = $at ?: Redesign::lastUpdate();
        $d = self::date($at);

        return $d && $d->format('H:i') !== '00:00' ? $d->format('H:i') : '';
    }

    /* ------------------------------------------------------------------
     | Numbers and text
     * ------------------------------------------------------------------ */

    public static function fa($s): string
    {
        return strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
                                   '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /** 1490000 → "1,490,000" (digits become Persian on the way out). */
    public static function fmt($n): string
    {
        return number_format((float) $n, 0, '.', ',');
    }

    /** Python's "{x:g}" for the small decimals the prototype prints: 2.5 → "2.5", 3.0 → "3". */
    public static function g($x): string
    {
        $s = rtrim(rtrim(sprintf('%.6F', (float) $x), '0'), '.');
        return $s === '-0' ? '0' : $s;
    }

    /** ۲٫۵ in the industry's own style: "2/5". */
    public static function faNum($x): string
    {
        return self::fa(str_replace('.', '/', self::g($x)));
    }

    /** Digits of any script to Latin. */
    public static function latin($s): string
    {
        return strtr((string) $s, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /** 3/0 → 3 ، 2/20 → 2/2 ، 1/200 → 1/2 */
    public static function tidyDecimals($s): string
    {
        return preg_replace_callback('~(?<![\d/])(\d+)/(\d+)(?![\d/])~u', function ($m) {
            $b = rtrim($m[2], '0');
            return $b !== '' ? $m[1] . '/' . $b : $m[1];
        }, (string) $s);
    }

    /** Product name for display. Slugs and URLs keep the raw name. */
    public static function cleanName($s): string
    {
        $s = trim((string) $s);
        $s = str_replace(['سانتیمترعرض', ' امتر '], ['سانتیمتر عرض', ' ۱ متر '], $s);
        $s = str_replace(['سانتیمتر', 'سانتی متر'], 'سانتی‌متر', $s);
        $s = preg_replace('/(\d)([آ-ی])/u', '$1 $2', $s);
        $s = preg_replace('/([آ-ی])(\d)/u', '$1 $2', $s);
        $s = str_replace(['*', '1.5'], ['×', '1/5'], $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        // «توری چشمه چشمه ۷/۵» — a word repeated back to back in the raw data.
        $s = preg_replace('/(?<!\S)(\S+)(?: \1)+(?!\S)/u', '$1', $s);

        return self::tidyDecimals($s);
    }

    /** Spec value for display. */
    public static function cleanVal($v): string
    {
        $s = trim((string) $v);
        if ($s === '') {
            return '—';
        }
        $s = str_replace(['"', '*', 'کیلو گرم'], ['', '×', 'کیلوگرم'], $s);

        return self::tidyDecimals($s);
    }

    /** '22 کیلوگرم' → 22.0 ، '1/200' → 1.2 ، 'فله بار' → null */
    public static function num($v): ?float
    {
        if ($v === null) {
            return null;
        }
        $s = trim(self::latin($v));
        if ($s === '' || $s === '—') {
            return null;
        }
        $s = str_replace(['٫', '،'], ['/', ''], $s);
        if (! preg_match('~\d+(?:[/.]\d+)?~', $s, $m)) {
            return null;
        }

        return (float) str_replace('/', '.', $m[0]);
    }

    /** Sort key for spec values: first number, then text. */
    public static function numKey($v): float
    {
        $n = self::num($v);
        return $n === null ? 9e9 : $n;
    }

    public static function pct($price, $prev): float
    {
        return ($price && $prev) ? ($price - $prev) / $prev * 100 : 0.0;
    }

    /** Human-rounded rial amount (analysis.money_fa). */
    public static function moneyFa($n): string
    {
        $n = (float) $n;
        if ($n >= 10000000) {
            $n = round($n / 100000) * 100000;
        } elseif ($n >= 1000000) {
            $n = round($n / 10000) * 10000;
        } elseif ($n >= 100000) {
            $n = round($n / 1000) * 1000;
        } else {
            $n = round($n / 100) * 100;
        }

        return number_format($n, 0, '.', ',');
    }

    /**
     * Article summaries in the database end in hashtag strings copied from
     * social posts («#تولید_توری_آهنی»). They are noise in a lede and in a
     * meta description, so they are dropped for display.
     */
    public static function plainDesc(?string $s): string
    {
        $s = preg_replace('/(^|\s)#[^\s#]+/u', ' ', (string) $s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /** Truncate on a word boundary, like the prototype's meta descriptions. */
    public static function cut(?string $s, int $limit = 158): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $s)));
        if (mb_strlen($s) <= $limit) {
            return $s;
        }
        $cut = mb_substr($s, 0, $limit);
        $sp = mb_strrpos($cut, ' ');
        if ($sp !== false && $sp > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $sp);
        }

        return rtrim($cut, '،. ') . '…';
    }

    /* ------------------------------------------------------------------
     | Admin rich text (CKEditor) → the design
     * ------------------------------------------------------------------ */

    /**
     * Strip what the editor adds on paste — inline colours, fonts, sizes,
     * dir="LTR" spans, empty paragraphs — so the page keeps one typography.
     * Structure, links, images, tables and lists stay exactly as written.
     */
    public static function cleanHtml(?string $html): string
    {
        $h = (string) $html;
        if (trim(strip_tags($h, '<img><iframe><video>')) === '') {
            return '';
        }
        $h = preg_replace('~\s(style|class|dir|lang|face|color|size|align)\s*=\s*("[^"]*"|\'[^\']*\')~iu', '', $h);
        $h = preg_replace('~</?(span|font|o:p)\b[^>]*>~iu', '', $h);
        $h = preg_replace('~<p>(\s|&nbsp;|<br\s*/?>)*</p>~iu', '', $h);
        $h = preg_replace('~<(h[1-6])>\s*<br\s*/?>~iu', '<$1>', $h);
        $h = preg_replace('~<h1\b~iu', '<h2', preg_replace('~</h1>~iu', '</h2>', $h));

        return trim($h);
    }

    /**
     * Split admin HTML at its <h2> headings.
     *
     * Returns [lead, sections]: lead is whatever comes before the first
     * heading; each section is ['title', 'html', 'faq'] where faq holds
     * [question, answer-html] pairs when the heading is a FAQ («پرسش…»,
     * «سؤال…», «سوال…») and its questions are written as <h3>.
     */
    public static function sections(?string $html): array
    {
        $h = self::cleanHtml($html);
        if ($h === '') {
            return ['', []];
        }
        $parts = preg_split('~<h2[^>]*>(.*?)</h2>~isu', $h, -1, PREG_SPLIT_DELIM_CAPTURE);
        $lead = trim($parts[0]);
        $out = [];
        for ($i = 1; $i < count($parts); $i += 2) {
            $title = trim(html_entity_decode(strip_tags($parts[$i]), ENT_QUOTES, 'UTF-8'));
            $body = trim($parts[$i + 1] ?? '');
            $faq = null;
            if (preg_match('~پرسش|سؤال|سوال~u', $title) && preg_match('~<h3~i', $body)) {
                $faq = [];
                $qs = preg_split('~<h3[^>]*>(.*?)</h3>~isu', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
                for ($j = 1; $j < count($qs); $j += 2) {
                    $q = trim(html_entity_decode(strip_tags($qs[$j]), ENT_QUOTES, 'UTF-8'));
                    if ($q !== '') {
                        $faq[] = [$q, trim($qs[$j + 1] ?? '')];
                    }
                }
            }
            $out[] = ['title' => $title, 'html' => $body, 'faq' => $faq];
        }

        return [$lead, $out];
    }

    /* ------------------------------------------------------------------
     | Markup fragments
     * ------------------------------------------------------------------ */

    public static function icon(string $name, string $cls = ''): HtmlString
    {
        $c = $cls !== '' ? ' class="' . e($cls) . '"' : '';
        return new HtmlString('<svg' . $c . ' aria-hidden="true"><use href="#' . e($name) . '"/></svg>');
    }

    /** Change against the last recorded different price (common.delta_badge). */
    public static function delta($prev, $price): HtmlString
    {
        $flat = '<span class="delta flat">' . self::icon('i-flat') . '<span class="num">۰</span></span>';
        if (! $prev || ! $price) {
            return new HtmlString($flat);
        }
        $p = self::pct($price, $prev);
        if ($p > 0.05) {
            return new HtmlString('<span class="delta up">' . self::icon('i-up') . '<span class="num">+'
                . number_format($p, 1) . '٪</span></span>');
        }
        if ($p < -0.05) {
            return new HtmlString('<span class="delta down">' . self::icon('i-down') . '<span class="num">'
                . number_format($p, 1) . '٪</span></span>');
        }

        return new HtmlString($flat);
    }

    public static function stars(int $n, string $size = ''): HtmlString
    {
        $out = '';
        for ($i = 0; $i < 5; $i++) {
            $out .= '<i class="' . ($i < $n ? 'on' : '') . '">★</i>';
        }

        return new HtmlString('<span class="stars ' . e($size) . '" aria-label="' . self::fa($n) . ' از ۵">' . $out . '</span>');
    }

    /** Static file under public_html with a cache-busting stamp. */
    public static function asset(string $path): string
    {
        static $memo = [];
        $path = '/' . ltrim($path, '/');
        if (! isset($memo[$path])) {
            $file = public_path(ltrim($path, '/'));
            $memo[$path] = is_file($file) ? $path . '?v=' . filemtime($file) : $path;
        }

        return $memo[$path];
    }

    /* ------------------------------------------------------------------
     | URLs — the backend's own routes (routes/web.php)
     * ------------------------------------------------------------------ */

    public static function uCat(string $slug): string                { return '/category/' . $slug; }
    public static function uProd(string $cat, string $prod): string   { return '/category/' . $cat . '/' . $prod; }
    /**
     * A product's page, or null when it has none. A product gets a page only
     * when the admin gives it a slug (admin → محصول → محتوا → اسلاگ); until
     * then it is listed in its category's table without a link — exactly as
     * the previous theme, which never linked a slug-less product.
     */
    public static function prodUrl(string $cat, ?string $prod): ?string
    {
        return ($prod !== null && trim($prod) !== '') ? self::uProd($cat, $prod) : null;
    }

    /** <a> to $url, or just the text when there is no page to go to. */
    public static function link(?string $url, string $text, string $attrs = ''): HtmlString
    {
        return new HtmlString($url
            ? '<a href="' . e(self::path($url)) . '"' . ($attrs ? ' ' . $attrs : '') . '>' . e($text) . '</a>'
            : '<span>' . e($text) . '</span>');
    }

    public static function uBlogCat(string $slug): string            { return '/blog/' . $slug; }
    public static function uArticle(string $cat, string $slug): string{ return '/blog/' . $cat . '/' . $slug; }

    public static function site(string $path = '/'): string
    {
        return rtrim((string) config('redesign.site_url', 'https://sepahanfelez.ir'), '/') . $path;
    }

    /** Percent-encode a path for use inside href/src without touching the slashes. */
    public static function path(string $p): string
    {
        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $p)) {
            $u = parse_url($p);
            $tail = isset($u['query']) ? '?' . $u['query'] : '';
            return $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '')
                . self::path($u['path'] ?? '') . $tail;
        }

        return implode('/', array_map(function ($seg) {
            return $seg === '' ? '' : rawurlencode(rawurldecode($seg));
        }, explode('/', $p)));
    }

    /* ------------------------------------------------------------------
     | Jalali calendar — same algorithm as the prototype (jdf)
     * ------------------------------------------------------------------ */

    public const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
                           'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    // PHP: 0 = Sunday
    public const DAYS = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

    public static function jalali(int $gy, int $gm, int $gd): array
    {
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
              + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + $days % 31;
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + ($days - 186) % 30;
        }

        return [$jy, $jm, $jd];
    }

    /** @param \DateTimeInterface|string|null $d */
    private static function date($d): ?\DateTimeImmutable
    {
        if ($d === null || $d === '') {
            return null;
        }
        if ($d instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromFormat('U', (string) $d->getTimestamp())
                ->setTimezone(new \DateTimeZone(config('app.timezone', 'Asia/Tehran')));
        }
        try {
            return new \DateTimeImmutable((string) $d, new \DateTimeZone(config('app.timezone', 'Asia/Tehran')));
        } catch (\Exception $e) {
            return null;
        }
    }

    /** «۱۸ شهریور ۱۴۰۵»; with $bidi each part is its own span (see common.jalali_str). */
    public static function jDate($d, bool $withDay = false, bool $bidi = false): string
    {
        $d = self::date($d);
        if (! $d) {
            return '';
        }
        [$jy, $jm, $jd] = self::jalali((int) $d->format('Y'), (int) $d->format('n'), (int) $d->format('j'));
        $s = $bidi
            ? '<span class="d-d">' . $jd . '</span><span class="d-m">' . self::MONTHS[$jm - 1] . '</span><span class="d-y">' . $jy . '</span>'
            : $jd . ' ' . self::MONTHS[$jm - 1] . ' ' . $jy;
        if (! $withDay) {
            return $s;
        }
        $day = self::DAYS[(int) $d->format('w')];

        return $bidi ? '<span class="d-w">' . $day . '</span>' . $s : $day . ' ' . $s;
    }

    /** «۱۴۰۵/۰۶/۱۸» */
    public static function jShort($d): string
    {
        $d = self::date($d);
        if (! $d) {
            return '';
        }
        [$jy, $jm, $jd] = self::jalali((int) $d->format('Y'), (int) $d->format('n'), (int) $d->format('j'));

        return sprintf('%d/%02d/%02d', $jy, $jm, $jd);
    }

    public static function iso($d): string
    {
        $d = self::date($d);
        return $d ? $d->format('Y-m-d') : '';
    }

    public static function isToday($d): bool
    {
        $d = self::date($d);
        return $d && $d->format('Y-m-d') === (new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'Asia/Tehran'))))->format('Y-m-d');
    }

    /* ------------------------------------------------------------------
     | Structured data — port of build/schema.py
     * ------------------------------------------------------------------ */

    public static function orgId(): string  { return self::site('/#organization'); }
    public static function siteId(): string { return self::site('/#website'); }

    /** The backend's own Organization node (App\Support\Schema, config/brand.php). */
    public static function organization(): array
    {
        $o = Schema::organisation();
        unset($o['@context']);
        $o['@id'] = self::orgId();
        if ($p = Site::phone()) {
            $o['telephone'] = '+98' . ltrim($p, '0');
        }

        return $o;
    }

    public static function website(): array
    {
        return ['@type' => 'WebSite', '@id' => self::siteId(), 'url' => self::site('/'),
                'name' => 'سپاهان فلز', 'inLanguage' => 'fa-IR', 'publisher' => ['@id' => self::orgId()]];
    }

    /** @param array $items [[name, relative url or null], ...] */
    public static function breadcrumb(array $items): array
    {
        $list = [];
        foreach (array_values($items) as $i => $it) {
            $li = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $it[0]];
            if (! empty($it[1])) {
                $li['item'] = self::site($it[1]);
            }
            $list[] = $li;
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    public static function offer($price, string $unit): ?array
    {
        if (! $price) {
            return null;
        }

        return ['@type' => 'Offer', 'price' => (string) $price, 'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => self::orgId()],
                'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => (string) $price,
                                         'priceCurrency' => 'IRR', 'unitText' => $unit]];
    }

    public static function product(string $name, string $url, string $desc, $price, string $unit,
                                   ?string $image = null, array $props = []): array
    {
        $p = ['@type' => 'Product', '@id' => self::site($url) . '#product', 'name' => $name,
              'url' => self::site($url), 'description' => $desc,
              'brand' => ['@type' => 'Brand', 'name' => Site::companyName()],
              'manufacturer' => ['@id' => self::orgId()]];
        if ($image) {
            $p['image'] = strpos($image, '/') === 0 ? self::site($image) : $image;
        }
        if ($o = self::offer($price, $unit)) {
            $p['offers'] = $o;
        }
        $ap = [];
        foreach ($props as $kv) {
            if (! in_array($kv[1], [null, '', '-', '—'], true)) {
                $ap[] = ['@type' => 'PropertyValue', 'name' => $kv[0], 'value' => (string) $kv[1]];
            }
        }
        if ($ap) {
            $p['additionalProperty'] = $ap;
        }

        return $p;
    }

    public static function itemList(array $products, string $name): array
    {
        $els = [];
        foreach (array_values($products) as $i => $p) {
            $els[] = ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $p];
        }

        return ['@type' => 'ItemList', 'name' => $name, 'numberOfItems' => count($products), 'itemListElement' => $els];
    }

    public static function faq(array $pairs): ?array
    {
        if (! $pairs) {
            return null;
        }
        $q = [];
        foreach ($pairs as $p) {
            $q[] = ['@type' => 'Question', 'name' => $p[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p[1]]];
        }

        return ['@type' => 'FAQPage', 'mainEntity' => $q];
    }

    public static function page(string $type, string $path, string $name, array $extra = []): array
    {
        return array_merge(['@type' => $type, '@id' => self::site($path) . '#page', 'url' => self::site($path),
                            'name' => $name, 'inLanguage' => 'fa-IR', 'isPartOf' => ['@id' => self::siteId()]], $extra);
    }

    private static function strip($o)
    {
        if (is_array($o)) {
            $isList = array_keys($o) === range(0, count($o) - 1);
            $out = [];
            foreach ($o as $k => $v) {
                if ($v === null || $v === '' || $v === []) {
                    continue;
                }
                $out[$k] = self::strip($v);
            }

            return $isList ? array_values($out) : $out;
        }

        return $o;
    }

    /** One <script> with every node in a @graph. */
    public static function graph(...$nodes): HtmlString
    {
        $clean = [];
        foreach ($nodes as $n) {
            if ($n) {
                $clean[] = self::strip($n);
            }
        }
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $clean],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        return new HtmlString('<script type="application/ld+json">' . $json . '</script>');
    }
}
