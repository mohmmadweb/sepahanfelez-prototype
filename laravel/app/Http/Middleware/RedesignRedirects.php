<?php

namespace App\Http\Middleware;

use App\Functions\Redirecting;
use App\Models\Article;
use App\Models\Product;
use Closure;

/**
 * Permanent (301) redirects, run before routing.
 *
 * The panel's own redirect table (Admin → ریدایرکت) is read by routes/web.php,
 * which never actually fires on this site, for three reasons measured on stage:
 *
 *   - it compares the full URL including the host, and every row was written
 *     for ahanamn.com, so nothing matches on sepahanfelez.ir;
 *   - it registers the stored, percent-encoded URI as a route, while Laravel
 *     matches routes against the decoded path, so a Persian address never
 *     matches even with the right host;
 *   - Route::redirect answers 302, which search engines treat as temporary.
 *
 * This reads the same table, compares decoded paths only (any host in a row is
 * ignored, so old ahanamn.com rows work too) and answers 301. The table stays
 * the single place to manage redirects from the panel.
 *
 * It also sends /blog/{category}/{article} and /category/{category}/{product}
 * to the item's real category when the address names another one. Both
 * controllers accept any category in the URL, so every article and product was
 * reachable at many addresses; and when a category's slug is renamed in the
 * panel, the old addresses of everything in it land on the new ones without a
 * row per item.
 *
 * Global middleware (registered by RedesignServiceProvider) so that addresses
 * no route matches any more — old .html pages — are covered as well.
 */
class RedesignRedirects
{
    /** Hosts whose rows are this site's own addresses; a target on one of them is sent to the current host. */
    private const OWN_HOSTS = ['sepahanfelez.ir', 'www.sepahanfelez.ir', 'ahanamn.com', 'www.ahanamn.com'];

    public function handle($request, Closure $next)
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }
        $path = self::norm($request->getPathInfo());
        if (strpos($path, '/admin') === 0 || strpos($path, '/_') === 0) {
            return $next($request);
        }

        $to = $this->fromTable($path) ?? $this->articleCanonical($path) ?? $this->productCanonical($path);
        if ($to !== null) {
            $qs = $request->getQueryString();
            if ($qs !== null && strpos($to, '?') === false) {
                $to .= '?' . $qs;
            }
            return redirect()->to($to, 301)->header('Cache-Control', 'public, max-age=3600');
        }

        return $next($request);
    }

    /** Decoded path, no trailing slash ("/" stays "/"). */
    private static function norm(string $path): string
    {
        $p = rawurldecode($path);
        return $p === '/' ? '/' : (rtrim($p, '/') ?: '/');
    }

    private function fromTable(string $path): ?string
    {
        foreach (Redirecting::getRouts() as $row) {
            if (! $row->is_active) {
                continue;
            }
            if (self::norm((string) parse_url($row->from, PHP_URL_PATH)) !== $path) {
                continue;
            }
            $target = $this->target((string) $row->to);
            if ($target !== null && self::norm((string) parse_url($target, PHP_URL_PATH)) !== $path) {
                return $target;
            }
        }
        return null;
    }

    /** A row's target as an address on this site, or unchanged when it points elsewhere. */
    private function target(string $to): ?string
    {
        $to = trim($to);
        if ($to === '') {
            return null;
        }
        $host = parse_url($to, PHP_URL_HOST);
        if ($host !== null && $host !== false && ! in_array(strtolower($host), self::OWN_HOSTS, true)) {
            return $to;
        }
        $path = (string) (parse_url($to, PHP_URL_PATH) ?: '/');
        $query = parse_url($to, PHP_URL_QUERY);
        return url(self::encodePath(rawurldecode($path))) . ($query ? '?' . $query : '');
    }

    private function articleCanonical(string $path): ?string
    {
        if (! preg_match('#^/blog/([^/]+)/([^/]+)$#u', $path, $m)) {
            return null;
        }
        $article = Article::query()->where('slug', $m[2])->with('category')->first();
        if (! $article || ! $article->category || $article->category->slug === $m[1]) {
            return null;
        }
        return url(self::encodePath('/blog/' . $article->category->slug . '/' . $article->slug));
    }

    private function productCanonical(string $path): ?string
    {
        if (! preg_match('#^/category/([^/]+)/([^/]+)$#u', $path, $m) || $m[1] === 'chart') {
            return null;
        }
        $product = Product::query()->where('slug', $m[2])->with('category')->first();
        if (! $product || ! $product->category || $product->category->slug === $m[1]) {
            return null;
        }
        return url(self::encodePath('/category/' . $product->category->slug . '/' . $product->slug));
    }

    private static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
