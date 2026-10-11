<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * STAGING ONLY. Never copy to the Ahanamn repository or the live host.
 *
 *  - /_staging/login: password login as the staging admin (SMS does not
 *    leave this server, so the OTP login cannot work here); with `as=<id>`,
 *    as that user instead (the sample customer, for the static demo export).
 *  - Every response carries X-Robots-Tag: noindex.
 *  - STAGING_GATE=true puts the whole site behind STAGING_PASSWORD (StagingGate).
 */
class StagingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Behind the Cloudflare tunnel the app sees plain HTTP; keep links on the public https URL.
        // Only for requests that came through the tunnel on the public host: there the app sees
        // plain HTTP and must still link to https. Requests on another host (an SSH tunnel to
        // localhost) keep their own host, so links stay on it.
        $appUrl = (string) config('app.url');
        $req = $this->app->bound('request') ? $this->app['request'] : null;
        if (strpos($appUrl, 'https://') === 0 && $req && $req->getHost() === parse_url($appUrl, PHP_URL_HOST)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }
        // Site-wide password (STAGING_GATE=true): the stage holds a copy of real customer data.
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->prependMiddleware(\App\Http\Middleware\StagingGate::class);

        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\StagingNoIndex::class);
        $this->app['router']->pushMiddlewareToGroup('api', \App\Http\Middleware\StagingNoIndex::class);

        $this->app->booted(function () {
            Route::middleware('web')->group(function () {
                Route::get('/_staging/login', function () {
                    return response(view()->file(base_path('staging/login.blade.php'), ['error' => session('error')]));
                });
                Route::post('/_staging/login', function (Request $r) {
                    $pass = (string) env('STAGING_PASSWORD', '');
                    if ($pass === '' || ! hash_equals($pass, (string) $r->input('password'))) {
                        sleep(2);
                        return redirect('/_staging/login')->with('error', 'رمز درست نیست');
                    }
                    // Optional «as»: sign in as the sample customer instead (tools/staging/demo_data.php),
                    // used to export the customer panel for the static demo. Same password.
                    $as = (int) $r->input('as', 0);
                    $id = $as > 0 ? $as : (int) env('STAGING_ADMIN_ID', 1);
                    Auth::loginUsingId($id);   // no "remember": the live users table has no remember_token column
                    $r->session()->regenerate();
                    return redirect($as > 0 ? '/user/profile' : '/admin');
                })->middleware('throttle:10,1');
            });
        });
    }
}
