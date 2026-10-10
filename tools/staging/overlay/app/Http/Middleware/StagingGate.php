<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * STAGING ONLY: the whole stage sits behind one password (STAGING_PASSWORD),
 * because it holds a copy of the real database and its one-time code is a
 * fixed STAGING_OTP. Runs before routing and before Laravel's cookie
 * encryption, so it reads and writes its own signed cookie directly.
 */
class StagingGate
{
    const COOKIE = 'stage_gate';

    public function handle($request, Closure $next)
    {
        $pass = (string) env('STAGING_PASSWORD', '');
        if (! env('STAGING_GATE', false) || $pass === '') {
            return $next($request);
        }
        $sig = hash_hmac('sha256', 'stage-ok', config('app.key') . '|' . $pass);

        if (hash_equals($sig, (string) $request->cookies->get(self::COOKIE, ''))) {
            return $next($request);
        }

        if ($request->isMethod('post') && $request->path() === '_staging/gate') {
            if (hash_equals($pass, (string) $request->request->get('password', ''))) {
                $to = (string) $request->request->get('to', '/');
                $to = (strpos($to, '/') === 0 && strpos($to, '//') !== 0) ? $to : '/';
                return redirect($to)->withCookie(new Cookie(self::COOKIE, $sig, time() + 60 * 60 * 24 * 30, '/', null,
                                                             $request->isSecure() || strpos((string) config('app.url'), 'https') === 0, true, false, 'lax'));
            }
            sleep(2);
            return $this->page($request, 'رمز درست نیست', 401);
        }

        return $this->page($request, null, 401);
    }

    private function page($request, $error, $status)
    {
        $to = $request->path() === '_staging/gate' ? '/' : '/' . ltrim($request->getRequestUri(), '/');
        $html = view()->file(base_path('staging/gate.blade.php'), ['error' => $error, 'to' => $to])->render();

        return response($html, $status)->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }
}
