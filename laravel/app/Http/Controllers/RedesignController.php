<?php

namespace App\Http\Controllers;

use App\Support\Redesign;

/**
 * The few endpoints the redesigned pages call. All read-only, all cacheable.
 *
 * They are registered by RedesignServiceProvider without the `web` middleware
 * group: no session is started and no cookie is set, so a CDN or the browser
 * can cache them and they cost the host almost nothing.
 */
class RedesignController extends Controller
{
    /** The same records the prototype's search-index.json had; assets/search.js reads it. */
    public function search()
    {
        return response()->json(Redesign::searchIndex(), 200, [
            'Cache-Control' => 'public, max-age=600',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Recorded price changes of one product, for assets/chart.js (data-src). */
    public function productChart(int $id)
    {
        return $this->points(Redesign::productSeries($id));
    }

    /** Mean price of a category over time. */
    public function categoryChart(int $id)
    {
        return $this->points(Redesign::categorySeries($id));
    }

    /** The cart is not part of the new design: orders are taken by phone. */
    public function cart()
    {
        return redirect('/price', 301);
    }

    private function points(array $points)
    {
        return response()->json(['points' => $points], 200, [
            'Cache-Control' => 'public, max-age=600',
        ]);
    }
}
