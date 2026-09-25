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
 */
class StagingServiceProvider extends ServiceProvider
{
    public function boot()
    {
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
                    Auth::loginUsingId($id, true);
                    $r->session()->regenerate();
                    return redirect($as > 0 ? '/user/profile' : '/admin');
                })->middleware('throttle:10,1');
            });
        });
    }
}
