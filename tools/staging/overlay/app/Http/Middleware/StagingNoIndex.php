<?php

namespace App\Http\Middleware;

use Closure;

/** STAGING ONLY: keep search engines away from the staging copy. */
class StagingNoIndex
{
    public function handle($request, Closure $next)
    {
        $res = $next($request);
        if (method_exists($res, 'header')) {
            $res->header('X-Robots-Tag', 'noindex, nofollow');
        }
        return $res;
    }
}
