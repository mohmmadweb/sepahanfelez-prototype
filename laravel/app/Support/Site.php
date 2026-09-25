<?php

namespace App\Support;

use App\Models\About;
use App\Models\GeneralSetting;
use App\Models\HomeSetting;
use App\Models\Information;
use App\Models\Slider;
use App\Models\Social;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Site-wide settings, read from the tables the admin panel edits.
 *
 *   admin «اطلاعات تماس»   (information)      phone, e-mail, addresses, hours, footer text
 *   admin «تنظیمات صفحه اصلی» (home_settings) home/price SEO, home intro and pictures
 *   admin «درباره ما»      (abouts)           about text, image, video
 *   admin «تنظیمات عمومی»  (general_settings) company name, favicon
 *   admin «شبکه‌های اجتماعی» (socials)         links and on/off
 *   admin «اسلایدر»        (sliders)          home slides
 *   admin «ویدئوها»        (videos)           factory videos
 *
 * Nothing here has a fallback copy of its own: an empty field renders
 * nothing, so what the page shows is always what the panel holds. The only
 * fallbacks are to config/brand.php (the existing backend config) for the
 * phone and e-mail, so a missing Information row cannot empty the header.
 *
 * Cached briefly; every model here is edited through the panel, and the
 * provider drops the cache whenever one of them is saved.
 */
class Site
{
    public const CACHE_KEY = 'redesign.site';

    private static $memo;

    public static function data(): array
    {
        if (self::$memo === null) {
            self::$memo = Cache::remember(self::CACHE_KEY, (int) config('redesign.cache_ttl', 600), function () {
                $one = function ($class) {
                    try {
                        $m = $class::query()->first();
                        return $m ? $m->toArray() : [];
                    } catch (\Throwable $e) {
                        return [];
                    }
                };
                $list = function ($build) {
                    try {
                        return $build();
                    } catch (\Throwable $e) {
                        return [];
                    }
                };

                return [
                    'info'    => $one(Information::class),
                    'home'    => $one(HomeSetting::class),
                    'about'   => $one(About::class),
                    'general' => $one(GeneralSetting::class),
                    'socials' => $list(function () {
                        return Social::query()->where('is_active', true)->get()->map(function ($s) {
                            return ['title' => $s->title, 'url' => Brand::socialUrl($s->url), 'icon' => (string) $s->icon];
                        })->filter(function ($s) { return ! empty($s['url']); })->values()->all();
                    }),
                    'slides'  => $list(function () {
                        return Slider::query()->orderBy('id')->get()->map(function ($s) {
                            return ['image' => $s->image(), 'link' => $s->link, 'alt' => (string) $s->alt];
                        })->all();
                    }),
                    'videos'  => $list(function () {
                        return Video::query()->orderBy('id')->pluck('video')->map(function ($v) {
                            return strpos($v, '/') === 0 || preg_match('~^https?://~', $v) ? $v : '/videos/' . $v;
                        })->all();
                    }),
                ];
            });
        }

        return self::$memo;
    }

    public static function flush(): void
    {
        self::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    public static function get(string $key, $default = null)
    {
        $v = data_get(self::data(), $key);

        return ($v === null || $v === '') ? $default : $v;
    }

    /* ------------------------------------------------------------------
     | Contact
     * ------------------------------------------------------------------ */

    /** Digits only, for tel: links. */
    public static function phone(): string
    {
        $p = preg_replace('/\D+/', '', Rd::latin((string) self::get('info.phone', config('brand.phone', ''))));

        return $p !== '' ? $p : (string) config('brand.phone', '');
    }

    /** 021-91326030 — the same shape PhoneComposer gives the old theme. */
    public static function phoneShow(): string
    {
        $p = self::phone();
        if (Str::startsWith($p, '021') && strlen($p) === 11) {
            return '021-' . substr($p, 3);
        }
        if (Str::startsWith($p, '0') && strlen($p) === 11) {
            return substr($p, 0, 4) . '-' . substr($p, 4);
        }

        return $p !== '' ? $p : (string) config('brand.phone_display', '');
    }

    public static function email(): string
    {
        return (string) self::get('info.email', config('brand.email', ''));
    }

    public static function hours(): string
    {
        return (string) self::get('info.work_time', '');
    }

    public static function fax(): string
    {
        $f = trim((string) self::get('info.fax', ''));

        return in_array($f, ['-', '—', '0'], true) ? '' : $f;
    }

    /**
     * Addresses as [label, text]. The panel has four plain fields; a field
     * written as «برچسب: نشانی» shows the part before the first colon as its
     * label, so the admin controls labels too without a new column.
     */
    public static function addresses(): array
    {
        $out = [];
        foreach (['main_address', 'factory_address_1', 'factory_address_2', 'factory_address_3'] as $k) {
            $v = trim(strip_tags((string) self::get('info.' . $k, '')));
            if ($v === '') {
                continue;
            }
            $parts = preg_split('/\s*[:：]\s*/u', $v, 2);
            $out[] = count($parts) === 2 && mb_strlen($parts[0]) <= 40 ? [$parts[0], $parts[1]] : ['', $v];
        }

        return $out;
    }

    /** Active social links with the sprite icon that matches each. */
    public static function socials(): array
    {
        $out = [];
        foreach ((array) self::get('socials', []) as $s) {
            $hay = strtolower($s['icon'] . ' ' . $s['url'] . ' ' . $s['title']);
            $icon = 'i-external';
            foreach (['whatsapp' => 'i-whatsapp', 'wa.me' => 'i-whatsapp', 'telegram' => 'i-telegram', 't.me' => 'i-telegram',
                      'instagram' => 'i-instagram', 'ig.me' => 'i-instagram'] as $needle => $id) {
                if (strpos($hay, $needle) !== false) {
                    $icon = $id;
                    break;
                }
            }
            $out[] = ['title' => $s['title'], 'url' => $s['url'], 'icon' => $icon];
        }

        return $out;
    }

    /** The WhatsApp link from the socials table, or ''. */
    public static function whatsapp(): string
    {
        foreach (self::socials() as $s) {
            if ($s['icon'] === 'i-whatsapp') {
                return $s['url'];
            }
        }

        return '';
    }

    /** The number inside the WhatsApp link, for display (۰۹۱۳…). */
    public static function whatsappShow(): string
    {
        $url = self::whatsapp();
        if (! preg_match('/(\d{10,13})/', $url, $m)) {
            return '';
        }
        $d = $m[1];
        if (strpos($d, '98') === 0 && strlen($d) === 12) {
            $d = '0' . substr($d, 2);
        }

        return $d;
    }

    public static function companyName(): string
    {
        return (string) self::get('general.company_name', Brand::name());
    }

    public static function footerText(): string
    {
        return (string) self::get('info.about', '');
    }

    /* ------------------------------------------------------------------
     | Home and about
     * ------------------------------------------------------------------ */

    public static function slides(): array
    {
        return (array) self::get('slides', []);
    }

    public static function videos(): array
    {
        return (array) self::get('videos', []);
    }

    /** Image path under /images/<folder>/ for a filename the panel stored. */
    public static function img(?string $file, string $folder): ?string
    {
        $file = trim((string) $file);
        if ($file === '') {
            return null;
        }
        if (strpos($file, '/') === 0 || preg_match('~^https?://~', $file)) {
            return $file;
        }

        return '/images/' . $folder . '/' . $file;
    }

    /**
     * The About picture. The panel stores an upload under
     * images/static-pages/main/ (Image::upload) but About::image() points one
     * level up, so an image uploaded through the panel never showed on the old
     * theme either. Look in both places.
     */
    public static function aboutImage(): ?string
    {
        $file = trim((string) self::get('about.image', ''));
        if ($file === '') {
            return null;
        }
        foreach (['/images/static-pages/' . $file, '/images/static-pages/main/' . $file] as $p) {
            if (is_file(public_path(ltrim($p, '/')))) {
                return $p;
            }
        }

        return null;
    }

        public static function aboutVideo(): ?string
    {
        $v = trim((string) self::get('about.video', ''));
        if ($v === '' || strlen($v) < 3) {
            return null;
        }
        if (preg_match('~^https?://~', $v) || strpos($v, '/') === 0) {
            return $v;
        }

        return '/videos/' . $v;
    }
}
