<?php

namespace App\Providers;

use App\Http\Controllers\RedesignController;
use App\Http\Middleware\RedesignFaDigits;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Category;
use App\Models\Price;
use App\Models\About;
use App\Models\GeneralSetting;
use App\Models\HomeSetting;
use App\Models\Information;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Social;
use App\Models\Video;
use App\Support\Rd;
use App\Support\Redesign;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * The one hook the redesign has into the application.
 *
 * Installed by adding a single line to config/app.php:
 *
 *     App\Providers\RedesignServiceProvider::class,
 *
 * With REDESIGN_ENABLED=false it returns before doing anything, so the site is
 * byte-for-byte what it was. With it on:
 *
 *   1. resources/views/redesign is put in front of the view finder. A view
 *      that exists there (site.home, site.category, auth.login, user.*, …)
 *      replaces the old one of the same name; anything not there — the whole
 *      admin panel — resolves exactly as before. No controller is edited: the
 *      controllers keep returning view('site.home') and get the new template.
 *   2. errors::404 and friends get the new design.
 *   3. Four small JSON/redirect routes are added (search index, chart history,
 *      /cart → /price). They are registered after routes/web.php, so the /cart
 *      one deliberately replaces the old cart page.
 *   4. Digits in the rendered HTML become Persian, as in the prototype.
 *   5. Saving anything in the panel that a page reads drops the cached data.
 *
 * Content lives only in the admin panel. The kit ships templates, CSS/JS and
 * the logo — no copy, no photos, no prices.
 */
class RedesignServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! config('redesign.enabled', false)) {
            return;
        }

        AliasLoader::getInstance()->alias('Rd', Rd::class);

        View::getFinder()->prependLocation(resource_path('views/redesign'));
        View::prependNamespace('errors', resource_path('views/redesign/errors'));
        // The exception handler rebuilds the `errors` namespace from
        // config('view.paths') at render time (replaceNamespace), which would
        // drop the line above and show Laravel's stock 404. Listing the
        // redesign directory first in view.paths makes it survive that.
        config(['view.paths' => array_values(array_unique(array_merge(
            [resource_path('views/redesign')], (array) config('view.paths', [])
        )))]);

        if (config('redesign.fa_digits', true)) {
            $this->app->make(Router::class)->pushMiddlewareToGroup('web', RedesignFaDigits::class);
        }

        // Whatever the admin saves shows at once: every model a page reads
        // drops the cached catalogue and settings when it changes.
        foreach ([Product::class, Price::class, Category::class, Article::class, ArticleCategory::class,
                  Information::class, HomeSetting::class, About::class, GeneralSetting::class,
                  Social::class, Slider::class, Video::class] as $model) {
            $model::saved(function () { Redesign::flush(); });
            $model::deleted(function () { Redesign::flush(); });
        }

        // After every provider has booted, i.e. after routes/web.php is loaded.
        $this->app->booted(function () {
            $this->routes();
        });
    }

    private function routes(): void
    {
        Route::get('/rd/search-index.json', [RedesignController::class, 'search']);
        Route::get('/rd/chart/product/{id}', [RedesignController::class, 'productChart'])->where('id', '[0-9]+');
        Route::get('/rd/chart/category/{id}', [RedesignController::class, 'categoryChart'])->where('id', '[0-9]+');

        if (config('redesign.redirect_cart', true)) {
            Route::get('/cart', [RedesignController::class, 'cart']);
        }
    }
}
