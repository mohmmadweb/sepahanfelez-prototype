<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Persian digits in the text of every public HTML page.
 *
 * The prototype's generator (build/gen.py → fa_digits) runs this exact pass
 * over each page it writes. Here the numbers come from the database with Latin
 * digits — prices, spec values, article bodies — so the same pass runs on the
 * response instead.
 *
 * Only text between tags changes. Attribute values (data-price="1490000",
 * hrefs, input values), <script>, <style> and <textarea> are left exactly as
 * they are, so JSON-LD, chart data and a form's old input stay machine-readable.
 * The admin panel is skipped entirely.
 */
class RedesignFaDigits
{
    private const FA = ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
                        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->is('admin', 'admin/*')) {
            return $response;
        }
        $type = (string) $response->headers->get('Content-Type');
        if ($type !== '' && stripos($type, 'text/html') === false) {
            return $response;
        }
        if (! method_exists($response, 'getContent')) {
            return $response;
        }
        $html = $response->getContent();
        if (! is_string($html) || $html === '' || stripos($html, '<html') === false) {
            return $response;
        }

        $out = self::convert($html);
        if ($out !== $html) {
            $response->setContent($out);
            if ($response->headers->has('Content-Length')) {
                $response->headers->set('Content-Length', (string) strlen($out));
            }
        }

        return $response;
    }

    public static function convert(string $html): string
    {
        $parts = preg_split('~(<(script|style|textarea)\b.*?</\2\s*>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        $out = '';
        // With DELIM_CAPTURE the array is: text, whole-block, tag-name, text, …
        for ($i = 0, $n = count($parts); $i < $n; $i++) {
            if ($i % 3 === 0) {
                $out .= self::convertText($parts[$i]);
            } elseif ($i % 3 === 1) {
                $out .= $parts[$i];
            }
        }

        return $out;
    }

    private static function convertText(string $seg): string
    {
        return (string) preg_replace_callback('~>([^<>]+)<~u', function ($m) {
            $txt = preg_replace_callback('~(&[#A-Za-z0-9]+;)|([0-9]+)~', function ($t) {
                return $t[1] !== '' ? $t[1] : strtr($t[2], self::FA);
            }, $m[1]);

            return '>' . $txt . '<';
        }, $seg);
    }
}
