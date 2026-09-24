<?php

namespace App\Support;

/**
 * Line-for-line port of the prototype's build/analysis.py plus
 * features.product_intro / _kg / _m2.
 *
 * Every category sells by a different unit — welded mesh by the roll, pressed
 * mesh by the kilogram, gabion by the square metre — so a buyer cannot put two
 * offers side by side. This turns each product's own weight and dimensions into
 * comparable numbers (per m², per kg, per piece, per tonne), which is also what
 * makes the text of seventy product pages genuinely different from each other.
 *
 * Input rows are the arrays Redesign::catalog() builds: spec title => value.
 * Output is HTML, escaped here, because the prototype produced HTML too.
 */
class RedesignAnalysis
{
    private const PIECE_UNITS = ['رول', 'کلاف', 'برگ', 'پانل', 'شاخه', 'عدد', 'بسته'];

    private static function g(array $row, string $k)
    {
        return $row[$k] ?? null;
    }

    private static function dims($v): ?array
    {
        if (! $v) {
            return null;
        }
        $parts = explode('*', (string) $v);
        if (count($parts) !== 2) {
            return null;
        }
        $a = Rd::num($parts[0]);
        $b = Rd::num($parts[1]);

        return ($a && $b) ? [$a, $b] : null;
    }

    /** [piece name, m² per piece, kg per piece, kg per m²] — nulls where the data is silent. */
    public static function geometry(string $key, array $row): array
    {
        $piece = $area = $weight = $wm2 = null;
        switch ($key) {
            case 'توری-جوشی--گالوانیزه-رول':
                $piece = 'رول';
                $area = Rd::num(self::g($row, 'متراژ رول (متر مربع)'));
                $weight = Rd::num(self::g($row, 'وزن هر رول (تقریبی)'));
                break;
            case 'توری-حصاری':
                $piece = 'رول';
                $wm2 = Rd::num(self::g($row, 'وزن هر متر مربع (کیلوگرم)'));
                $L = Rd::num(self::g($row, 'طول رول (متر)'));
                $W = Rd::num(self::g($row, 'عرض (سانتی متر)'));
                if ($L && $W) {
                    $area = $L * $W / 100.0;
                }
                break;
            case 'توری-فرنگی':
            case 'توری-مرغی':
                $piece = 'رول';
                $weight = Rd::num(self::g($row, 'وزن (کیلوگرم)'));
                $L = Rd::num(self::g($row, 'طول (متر)'));
                $W = Rd::num(self::g($row, 'عرض (سانتی متر)'));
                if ($L && $W) {
                    $area = $L * $W / 100.0;
                }
                break;
            case 'توری-پرسی':
                $piece = 'برگ';
                $weight = Rd::num(self::g($row, 'وزن هر برگ'));
                break;
            case 'توری-گابیون':
                $piece = 'پانل';
                $wg = Rd::num(self::g($row, 'وزن هر متر مربع (گرم)'));
                $wm2 = $wg ? $wg / 1000.0 : null;
                $L = Rd::num(self::g($row, 'طول (متر)'));
                $W = Rd::num(self::g($row, 'عرض (سانتی متر)'));
                if ($L && $W) {
                    $area = $L * $W / 100.0;
                }
                break;
            case 'سیم-خاردار':
                $piece = 'کلاف';
                $weight = Rd::num(self::g($row, 'وزن (کیلوگرم)'));
                break;
            case 'مش-جوشی-یا-مش-آهنی':
                $piece = 'برگ';
                $weight = Rd::num(self::g($row, 'وزن هر برگ'));
                $d = self::dims(self::g($row, 'طول*عرض (متر)'));
                if ($d) {
                    $area = $d[0] * $d[1];
                }
                break;
            case 'سیم-سیاه-و-آرماتور-بندی':
                $piece = 'کلاف';
                $weight = Rd::num(self::g($row, 'وزن (کیلوگرم)'));
                break;
        }
        // Columns the owner can fill in the database outrank any parse.
        if (! empty($row['_kg']) && ($row['واحد'] ?? '') !== 'کیلوگرم') {
            $weight = (float) $row['_kg'];
        }
        if (! empty($row['_m2']) && ($row['واحد'] ?? '') !== 'مترمربع') {
            $area = (float) $row['_m2'];
        }
        if ($weight === null && $area && $wm2) {
            $weight = $area * $wm2;
        }
        if ($wm2 === null && $area && $weight) {
            $wm2 = $weight / $area;
        }

        return [$piece, $area, $weight, $wm2];
    }

    public static function economics(string $key, array $row, $price): array
    {
        $unit = trim((string) ($row['واحد'] ?? ''));
        $out = ['basis' => $unit, 'per_m2' => null, 'per_kg' => null, 'piece' => null, 'per_ton' => null];
        if (! $price) {
            return $out;
        }
        [$piece, $area, $weight, $wm2] = self::geometry($key, $row);
        if ($unit === 'کیلوگرم') {
            $out['per_kg'] = $price;
            if ($weight && $piece) {
                $out['piece'] = [$piece, $price * $weight];
            }
            if ($wm2) {
                $out['per_m2'] = $price * $wm2;
            }
        } elseif ($unit === 'مترمربع') {
            $out['per_m2'] = $price;
            if ($wm2) {
                $out['per_kg'] = $price / $wm2;
            }
            if ($area && $piece) {
                $out['piece'] = [$piece, $price * $area];
            }
        } elseif (in_array($unit, self::PIECE_UNITS, true)) {
            $out['piece'] = [$unit, $price];
            if ($area) {
                $out['per_m2'] = $price / $area;
            }
            if ($weight) {
                $out['per_kg'] = $price / $weight;
            }
        }
        if ($out['per_kg']) {
            $out['per_ton'] = $out['per_kg'] * 1000;
        }

        return $out;
    }

    /** Indices of values more than $factor away from the category median. */
    public static function outliers(array $vals, float $factor = 4.0): array
    {
        $clean = array_values(array_filter($vals));
        sort($clean);
        if (count($clean) < 4) {
            return [];
        }
        $mid = $clean[intdiv(count($clean), 2)];
        if (! $mid) {
            return [];
        }
        $bad = [];
        foreach ($vals as $i => $v) {
            if ($v && ($v > $mid * $factor || $v * $factor < $mid)) {
                $bad[$i] = true;
            }
        }

        return $bad;
    }

    private static function prodUrl(string $key, array $rows, string $name): string
    {
        foreach ($rows as $r) {
            if ($r['نام محصول'] === $name) {
                return Rd::path(Rd::uProd($key, $r['_slug']));
            }
        }

        return Rd::path(Rd::uCat($key));
    }

    private const BASIS_NOTE = [
        'کیلوگرم' => 'قیمت جدول به <b>کیلوگرم</b> است. مبلغی که در سفارش پرداخت می‌شود',
        'مترمربع' => 'قیمت جدول به <b>مترمربع</b> است. مبلغی که در سفارش پرداخت می‌شود',
        'رول'     => 'قیمت جدول به <b>رول</b> است. آنچه دو پیشنهاد را قابل مقایسه می‌سازد',
        'کلاف'    => 'قیمت جدول به <b>کلاف</b> است. آنچه دو پیشنهاد را قابل مقایسه می‌سازد',
        'برگ'     => 'قیمت جدول به <b>برگ</b> است. آنچه دو پیشنهاد را قابل مقایسه می‌سازد',
    ];

    /** «قیمت قابل‌مقایسه‌ی این محصول» block (analysis.price_block). */
    public static function priceBlock(string $key, array $row, array $rows): string
    {
        $p = $row['_price'];
        $e = self::economics($key, $row, $p);
        if (! $p || ! ($e['per_m2'] || $e['per_kg'] || $e['piece'])) {
            return '';
        }
        $eco = array_map(function ($r) use ($key) { return self::economics($key, $r, $r['_price']); }, $rows);

        if ($e['per_m2']) {
            $thisVal = $e['per_m2'];
            $vals = array_column($eco, 'per_m2');
            $basisLabel = 'هر متر مربع';
            $basisUnit = 'متر مربع';
        } elseif ($e['piece']) {
            $thisVal = $e['piece'][1];
            $vals = array_map(function ($x) { return $x['piece'] ? $x['piece'][1] : null; }, $eco);
            $basisLabel = 'هر ' . $e['piece'][0];
            $basisUnit = $e['piece'][0];
        } else {
            $thisVal = $e['per_kg'];
            $vals = array_column($eco, 'per_kg');
            $basisLabel = 'هر کیلوگرم';
            $basisUnit = 'کیلوگرم';
        }

        $cards = [];
        if ($e['piece']) {
            $cards[] = ['قیمت هر ' . $e['piece'][0], Rd::moneyFa($e['piece'][1]), 'ریال / ' . $e['piece'][0]];
        }
        if ($e['per_m2']) {
            $cards[] = ['قیمت هر متر مربع', Rd::moneyFa($e['per_m2']), 'ریال / m²'];
        }
        if ($e['per_kg']) {
            $cards[] = ['قیمت هر کیلوگرم', Rd::moneyFa($e['per_kg']), 'ریال / kg'];
        }
        if ($e['per_ton'] && count($cards) < 4) {
            $cards[] = ['قیمت هر تن', Rd::moneyFa($e['per_ton']), 'ریال / تن'];
        }
        $cards = array_slice($cards, 0, 4);
        $grid = '';
        foreach ($cards as $c) {
            $grid .= '<div class="ix"><span class="k">' . e($c[0]) . '</span><span class="v"><span class="num">'
                . $c[1] . '</span></span><span class="range">' . e($c[2]) . '</span></div>';
        }

        $bad = self::outliers($vals);
        $me = null;
        foreach ($rows as $i => $r) {
            if ($r['نام محصول'] === $row['نام محصول']) {
                $me = $i;
                break;
            }
        }
        $body = [];
        $units = [];
        foreach ($rows as $r) {
            $units[trim((string) ($r['واحد'] ?? ''))] = true;
        }
        $sameBasis = count($units) === 1;

        if ($sameBasis && $me !== null && ! isset($bad[$me])) {
            $good = [];
            foreach ($vals as $i => $v) {
                if ($v && ! isset($bad[$i])) {
                    $good[] = [$i, $v];
                }
            }
            if (count($good) >= 3) {
                usort($good, function ($a, $b) { return $a[1] <=> $b[1]; });
                $pos = 1;
                foreach ($good as $n => $t) {
                    if ($t[0] === $me) {
                        $pos = $n + 1;
                        break;
                    }
                }
                $n = count($good);
                if ($pos === 1) {
                    $body[] = '<p>روی مبنای <b>' . e($basisLabel) . '</b>، این محصول <b>مقرون‌به‌صرفه‌ترین</b> گزینه‌ی این دسته در میان '
                        . Rd::fa($n) . ' محصول سنجیده‌شده است.</p>';
                } else {
                    [$ci, $cv] = $good[0];
                    $gap = $cv ? ($thisVal - $cv) / $cv * 100 : 0;
                    $cheap = $rows[$ci]['نام محصول'];
                    $body[] = '<p>روی مبنای <b>' . e($basisLabel) . '</b>، این محصول در جایگاه <b>' . Rd::fa($pos) . 'م از '
                        . Rd::fa($n) . '</b> محصول این دسته قرار دارد.</p>';
                    if ($gap >= 5) {
                        $body[] = '<div class="callout"><div class="t">چنانچه مشخصات فنی قابل تغییر باشد</div>'
                            . '<p>کم‌هزینه‌ترین محصول این دسته روی همین مبنا <a href="' . self::prodUrl($key, $rows, $cheap) . '">'
                            . e($cheap) . '</a> است با <span class="num">' . Rd::moneyFa($cv) . '</span> ریال برای هر '
                            . e($basisUnit) . ' — یعنی <b>' . Rd::fa((int) round($gap)) . '٪</b> کمتر. '
                            . 'چنانچه ضخامت مفتول و چشمه‌ی آن با کاربرد شما تناسب دارد، صرفه‌جویی حاصل را در تناژ سفارش لحاظ بفرمایید.</p></div>';
                    }
                }
            }
        } elseif ($me !== null && isset($bad[$me])) {
            $body[] = '<p class="tnote">عدد این ردیف با بقیه‌ی محصولات دسته هم‌خوان نیست و در حال بازبینی است. پیش از سفارش، قیمت را تلفنی بگیرید.</p>';
        }

        if ($e['per_m2']) {
            $body[] = '<p>برآورد اولیه: پوشش <b>۱۰۰ متر مربع</b> با این محصول حدود <span class="num">'
                . Rd::moneyFa($e['per_m2'] * 100) . '</span> ریال خواهد بود؛ پیش از احتساب هزینه‌ی حمل.</p>';
        } elseif ($e['per_kg'] && $e['piece']) {
            $each = $e['piece'][1] / $e['per_kg'];
            if ($each) {
                $body[] = '<p>هر تن از این محصول تقریباً <b>' . Rd::fa((int) round(1000 / $each)) . ' '
                    . e($e['piece'][0]) . '</b> می‌شود.</p>';
            }
        }
        $body[] = '<p>این اعداد از وزن و ابعاد ثبت‌شده‌ی همین محصول حساب شده‌اند و مبنای روزند. برای <b>قیمت قطعی</b> '
            . 'تناژ و مقصد بار لازم است — <span class="num">' . e(Rd::phoneShow()) . '</span>.</p>';

        $lede = self::BASIS_NOTE[$e['basis']] ?? 'قیمت جدول مبنای روز است. آنچه قابل‌مقایسه است';

        return '<div class="prose"><h2>قیمت قابل‌مقایسه‌ی این محصول</h2><p>' . $lede
            . ' چیز دیگری است. این بخش همان تبدیل را انجام می‌دهد تا امکان مقایسه‌ی این محصول با هر پیشنهاد دیگری فراهم باشد.</p></div>'
            . '<div class="ixgrid ix-' . count($cards) . ' ix-tight">' . $grid . '</div>'
            . '<div class="prose">' . implode('', $body) . '</div>';
    }

    /* ------------------------------------------------------------------
     | Buying notes
     * ------------------------------------------------------------------ */

    private static function gaugeNote(?float $mm, string $key): ?string
    {
        if ($mm === null) {
            return null;
        }
        if ($key === 'سیم-سیاه-و-آرماتور-بندی') {
            if ($mm <= 1.6) {
                return 'مفتول ۱٫۵ و نازک‌تر برای بستن آرماتورهای سبک و شبکه‌های کم‌قطر است؛ سریع پیچ می‌خورد ولی زیر کشش زیاد باز می‌شود.';
            }
            if ($mm <= 2.6) {
                return 'این ضخامت حد وسط کار آرماتوربندی ساختمانی است: به دست می‌پیچد و در عین حال گره‌اش زیر وزن شبکه باز نمی‌شود.';
            }
            return 'مفتول ۳ به بالا برای گره‌های باربر و شبکه‌های سنگین است. پیچاندن آن به‌صورت دستی دشوارتر است و معمولاً با انبر مخصوص بسته می‌شود.';
        }
        $f = Rd::faNum($mm);
        if ($mm < 1.0) {
            return "مفتول {$f} میلی‌متر سبک است — برای حفاظ سبک، قفس، محصورسازی موقت و کاربردهای تزئینی. بار سازه‌ای روی آن حساب نکنید.";
        }
        if ($mm < 2.0) {
            return "مفتول {$f} میلی‌متر سبک تا متوسط است؛ جایی خوب جواب می‌دهد که وزن پایین و نصب سریع مهم‌تر از مقاومت ضربه باشد.";
        }
        if ($mm < 3.0) {
            return "مفتول {$f} میلی‌متر پرکاربردترین باند این خانواده است: برای حصارکشی محوطه و باغ، تعادل قیمت و دوام را دارد.";
        }
        if ($mm < 4.0) {
            return "مفتول {$f} میلی‌متر سنگین است. برای محوطه‌ی صنعتی، جایی که احتمال برخورد یا فشار دام هست، همین باند انتخاب می‌شود.";
        }

        return "مفتول {$f} میلی‌متر بالاترین باند این دسته است — سفت، سنگین و گران‌تر در هر متر مربع. فقط جایی که واقعاً لازم است.";
    }

    private static function meshNote($mesh): ?string
    {
        if (! $mesh) {
            return null;
        }
        $first = Rd::num(explode('*', (string) $mesh)[0]);
        if ($first === null) {
            return null;
        }
        $m = e($mesh);
        if ($first <= 2.5) {
            return "چشمه‌ی {$m} ریز است: در هر متر مربع مفتول بیشتری می‌رود، پس هم سنگین‌تر است و هم گران‌تر — ولی عبور را واقعاً می‌بندد.";
        }
        if ($first <= 5) {
            return "چشمه‌ی {$m} حد میانه است و بیشترین فروش این دسته را دارد؛ هم دید می‌دهد هم مانع مؤثری است.";
        }

        return "چشمه‌ی {$m} باز است: مفتول کمتری در هر متر مربع می‌رود، پس سبک‌تر و ارزان‌تر تمام می‌شود. برای مانع دیداری و محصورسازی دام مناسب است.";
    }

    private static function galvNote($g): ?string
    {
        if (! $g) {
            return null;
        }
        if (mb_strpos((string) $g, 'گرم') !== false) {
            return 'گالوانیزه‌ی گرم است — لایه‌ی روی ضخیم‌تر می‌نشیند و در هوای باز و رطوبت، عمر بیشتری می‌دهد. برای نصب بیرونی همین را بخواهید.';
        }

        return 'گالوانیزه‌ی سرد است: پوشش نازک‌تر و قیمت پایین‌تر. برای فضای سرپوشیده یا کاربرد موقت منطقی است، برای محوطه‌ی باز نه.';
    }

    private static function logisticsNote(string $piece, ?float $weight): ?string
    {
        if (! $weight) {
            return null;
        }
        $w = Rd::faNum($weight);
        if ($weight < 8) {
            return "هر {$piece} حدود {$w} کیلوگرم است — سبک، و با وانت هم قابل حمل. برای سفارش خرد همین نکته هزینه‌ی حمل را تعیین می‌کند.";
        }
        if ($weight < 30) {
            return "هر {$piece} حدود {$w} کیلوگرم است. جابه‌جایی دستی دو‌نفره ممکن است، ولی برای تعداد بالا بارگیری با لیفتراک بهتر است.";
        }

        return "هر {$piece} حدود {$w} کیلوگرم است — بارگیری‌اش ماشین‌آلات می‌خواهد. زمان تحویل را با همین فرض هماهنگ کنید.";
    }

    private static function heightNote(string $key, $w): ?string
    {
        if ($key !== 'توری-گابیون') {
            return null;
        }
        $h = Rd::num($w);
        if ($h === null) {
            return null;
        }
        $m = $h / 100.0;
        if ($m <= 1.0) {
            return 'ارتفاع یک متر: ردیف پایه‌ی دیوار گابیونی، کف‌سازی مسیر آب و تثبیت شیب کم. معمولاً به‌صورت چند ردیف روی هم چیده می‌شود.';
        }
        if ($m <= 1.2) {
            return 'ارتفاع ۱۲۰ سانتی‌متر پرکاربردترین ارتفاع محوطه‌سازی است: یک ردیف، بدون نیاز به چیدن ردیف دوم، دیواره‌ی مؤثری می‌دهد.';
        }
        if ($m <= 1.5) {
            return 'ارتفاع ۱۵۰ سانتی‌متر برای مهار خاک‌ریز و ساحل رودخانه انتخاب می‌شود؛ از این ارتفاع به بالا پشت‌بند و زهکشی جدی‌تر می‌خواهد.';
        }
        if ($m <= 1.8) {
            return 'ارتفاع ۱۸۰ سانتی‌متر دیواره‌ی بلند است. فشار جانبی خاک در این ارتفاع قابل‌توجه می‌شود — طراحی مقطع را به کارشناس بسپارید.';
        }

        return 'ارتفاع دو متر بالاترین محصول این دسته است: دیوار حائل بلند و مهار شیب تند. بدون محاسبه‌ی فشار خاک و زهکشی اجرا نکنید.';
    }

    /** 2–4 notes chosen from the product's own specs (analysis.buying_notes). HTML. */
    public static function buyingNotes(string $key, array $row): array
    {
        $mm = Rd::num(self::g($row, 'ضخامت مفتول (mm)'));
        if ($mm === null) {
            $mm = Rd::num(self::g($row, 'سایز میلگرد (میلی متر)'));
        }
        $mesh = self::g($row, 'چشمه (سانتی متر)') ?: self::g($row, 'چشمه (اینچ)');
        [$piece, , $weight] = self::geometry($key, $row);
        $notes = array_values(array_filter([
            self::gaugeNote($mm, $key), self::meshNote($mesh),
            self::heightNote($key, self::g($row, 'عرض (سانتی متر)')),
            self::galvNote(self::g($row, 'نوع گالوانیزه')),
            self::logisticsNote($piece ?: 'بسته', $weight),
        ]));
        $notes[] = 'قیمت مندرج در این صفحه، قیمت مبنای روز درب کارخانه‌ی اصفهان است. تناژ و مقصد بار در مبلغ نهایی مؤثرند. جهت اعلام قیمت قطعی: '
            . e(Rd::phoneShow()) . '.';

        return $notes;
    }

    private const PIVOTS = [['ضخامت مفتول (mm)', 'ضخامت مفتول'], ['چشمه (سانتی متر)', 'چشمه'], ['عرض (سانتی متر)', 'عرض']];
    private const DERIVED = ['وزن', 'متراژ'];

    private static function cmpKey(array $row, array $specs, string $skip): string
    {
        $parts = [];
        foreach ($specs as $s) {
            if ($s === $skip) {
                continue;
            }
            foreach (self::DERIVED as $d) {
                if (mb_strpos($s, $d) !== false) {
                    continue 2;
                }
            }
            $parts[] = (string) ($row[$s] ?? '');
        }

        return implode("\x1f", $parts);
    }

    /** «one step over» comparisons (analysis.sibling_notes). HTML. */
    public static function siblingNotes(string $key, array $row, array $rows, array $specs): array
    {
        $out = [];
        $me = self::economics($key, $row, $row['_price']);
        $myM2 = $me['per_m2'];
        $myPiece = $me['piece'] ? $me['piece'][1] : null;
        foreach (self::PIVOTS as [$field, $label]) {
            if (! in_array($field, $specs, true) || empty($row[$field])) {
                continue;
            }
            $mine = Rd::num($row[$field]);
            if ($mine === null) {
                continue;
            }
            $best = null;
            foreach ($rows as $r) {
                if ($r['نام محصول'] === $row['نام محصول']) {
                    continue;
                }
                if (self::cmpKey($r, $specs, $field) !== self::cmpKey($row, $specs, $field)) {
                    continue;
                }
                $v = Rd::num($r[$field] ?? null);
                if ($v === null || abs($v - $mine) < 1e-9) {
                    continue;
                }
                if (! $best || abs($v - $mine) < $best[0]) {
                    $best = [abs($v - $mine), $v, $r];
                }
            }
            if (! $best) {
                continue;
            }
            [, $v, $sib] = $best;
            $se = self::economics($key, $sib, $sib['_price']);
            $a = $b = null;
            $unitTxt = '';
            if ($myM2 && $se['per_m2']) {
                [$a, $b, $unitTxt] = [$myM2, $se['per_m2'], 'هر متر مربع'];
            } elseif ($myPiece && $se['piece']) {
                [$a, $b, $unitTxt] = [$myPiece, $se['piece'][1], 'هر ' . $se['piece'][0]];
            }
            if (! $a || ! $b) {
                continue;
            }
            $diff = ($b - $a) / $a * 100;
            if (abs($diff) < 2) {
                $rel = 'تقریباً همان هزینه را دارد';
            } elseif ($diff > 0) {
                $rel = '<b>' . Rd::fa((int) round(abs($diff))) . '٪ گران‌تر</b> تمام می‌شود';
            } else {
                $rel = '<b>' . Rd::fa((int) round(abs($diff))) . '٪ ارزان‌تر</b> تمام می‌شود';
            }
            $out[] = 'با ثابت‌ نگه‌داشتن بقیه‌ی مشخصات، یک پله ' . ($v > $mine ? 'بالاتر' : 'پایین‌تر') . ' در ' . $label
                . ' می‌شود <a href="' . Rd::path(Rd::uProd($key, $sib['_slug'])) . '">' . e($sib['نام محصول']) . '</a> — که در '
                . $unitTxt . ' ' . $rel . ' (<span class="num">' . Rd::moneyFa($b) . '</span> در برابر <span class="num">'
                . Rd::moneyFa($a) . '</span> ریال).';
        }

        return array_slice($out, 0, 2);
    }

    /* ------------------------------------------------------------------
     | features.product_intro / _kg / _m2
     * ------------------------------------------------------------------ */

    public static function productIntro(string $key, array $row, array $rows, array $specs, string $catTitle): string
    {
        $name = Rd::cleanName($row['نام محصول']);
        $p = $row['_price'];
        $unit = $row['واحد'];
        $prices = [];
        foreach ($rows as $r) {
            if ($r['_price'] && ! $r['_review']) {
                $prices[$r['_price']] = true;
            }
        }
        $prices = array_keys($prices);
        sort($prices);
        $pos = array_search($p, $prices, true);
        $pos = $pos === false ? null : $pos + 1;
        $n = count($prices);
        $use = Rd::c('use.' . $key, ['کاربردهای متداول', 'خریداران صنعتی']);
        $sp = [];
        foreach ($specs as $s) {
            if ($s !== 'محل بارگیری' && trim((string) ($row[$s] ?? '')) !== '') {
                $sp[] = explode(' (', $s)[0] . ' ' . $row[$s];
            }
            if (count($sp) >= 3) {
                break;
            }
        }
        $specTxt = implode('، ', $sp);
        if ($pos === 1 && $n > 2) {
            $rank = 'ارزان‌ترین گزینه‌ی این دسته است';
        } elseif ($pos === $n && $n > 2) {
            $rank = 'بالاترین قیمت دسته را دارد و برای کاربرد سنگین ساخته شده';
        } elseif ($pos && $n > 2) {
            $rank = 'از نظر قیمت در جایگاه ' . Rd::fa($pos) . ' از ' . Rd::fa($n) . ' سطح قیمتی دسته قرار می‌گیرد';
        } elseif ($pos) {
            $rank = 'هم‌قیمت با بیشتر کالاهای این دسته است و تفاوت آن در مشخصات فنی است';
        } else {
            $rank = 'قیمتش در حال بازبینی است';
        }
        [, $area, , $wm2] = self::geometry($key, $row);
        $econ = '';
        if ($wm2 && $unit !== 'مترمربع') {
            $perM2 = $unit === 'کیلوگرم' ? $p * $wm2 : ($area ? $p / $area : 0);
            $econ = ' هر متر مربع آن حدود ' . Rd::fa(Rd::g($wm2)) . ' کیلوگرم فولاد دارد؛ یعنی هزینه‌ی هر متر مربع حدود <b class="num">'
                . Rd::fmt((int) $perM2) . '</b> ریال درمی‌آید.';
        } elseif ($area && in_array($unit, ['رول', 'برگ'], true)) {
            $econ = ' هر ' . e($unit) . ' ' . Rd::fa(Rd::g($area)) . ' متر مربع را می‌پوشاند، پس هزینه‌ی هر متر مربع حدود <b class="num">'
                . Rd::fmt((int) ($p / $area)) . '</b> ریال است.';
        }

        return '<p><strong>' . e($name) . '</strong> یکی از ' . Rd::fa(count($rows)) . ' نوع ' . e($catTitle)
            . ' تولید صنایع مفتولی طلوع سپاهان است' . ($specTxt !== '' ? ' با مشخصات ' . e($specTxt) : '') . '. '
            . 'این کالا ' . $rank . ' و به ' . e($unit) . ' فروخته می‌شود.' . $econ . '</p>'
            . '<p>کاربرد اصلی آن ' . e($use[0]) . ' است و بیشتر ' . e($use[1]) . ' آن را سفارش می‌دهند. '
            . 'تحویل از کارخانه‌ی اصفهان یا انبار تهران انجام می‌شود و قیمت قطعی سفارش با تناژ و مقصد بار در تماس با کارشناس فروش اعلام می‌گردد.</p>';
    }

    /** kg per selling unit for the calculator. */
    public static function kg(string $key, array $row): float
    {
        [, , $weight, $wm2] = self::geometry($key, $row);
        $unit = $row['واحد'];
        if ($unit === 'کیلوگرم') {
            return 1.0;
        }
        if ($unit === 'مترمربع') {
            return $wm2 ? round($wm2, 3) : 0;
        }

        return $weight ? round($weight, 2) : 0;
    }

    /** m² per selling unit for the calculator. */
    public static function m2(string $key, array $row): float
    {
        [, $area, , $wm2] = self::geometry($key, $row);
        $unit = $row['واحد'];
        if ($unit === 'مترمربع') {
            return 1.0;
        }
        if ($unit === 'کیلوگرم') {
            return $wm2 ? round(1 / $wm2, 3) : 0;
        }

        return $area ? round($area, 2) : 0;
    }
}
