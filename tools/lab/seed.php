<?php
/**
 * Lab database for trying the Laravel kit without touching production.
 *
 * Run from the root of a *copy* of the Laravel app (with the kit copied in):
 *
 *     php /path/to/prototype/tools/lab/seed.php /path/to/prototype/build
 *
 * It migrates a fresh SQLite database, adds the columns production has but the
 * migrations lack (products.slug, users.mobile, …), and fills it from the
 * prototype's own build/catalog.json and build/articles.json — the same 70
 * products, spec values and prices the prototype shows — plus a demo user with
 * a ticket, an address and an old order so the user panel has something in it.
 *
 * Demo user: id 2, mobile 09120000000. SMS is not configured in a lab, so log
 * in through the lab-only route described in tools/lab/README.md.
 */

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$build = rtrim($argv[1] ?? '', '/');
if (! is_file("$build/catalog.json")) {
    fwrite(STDERR, "usage: php seed.php /path/to/prototype/build\n");
    exit(1);
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$db = config('database.connections.sqlite.database');
if (config('database.default') !== 'sqlite') {
    fwrite(STDERR, "refusing: DB_CONNECTION is not sqlite\n");
    exit(1);
}
@unlink($db);
touch($db);
DB::purge('sqlite');

$app->make(ConsoleKernel::class)->call('migrate', ['--force' => true]);
echo "migrated\n";

// Columns production has that no migration creates.
$drift = [
    'products'  => ['slug' => 'string', 'image' => 'string', 'meta_title' => 'string', 'meta_description' => 'text',
                    'meta_keyword' => 'string', 'index_by_crawler' => 'boolean', 'description' => 'text',
                    'canonical' => 'string', 'tax' => 'integer'],
    'users'     => ['full_name' => 'string', 'email' => 'string', 'mobile' => 'string', 'phone' => 'string',
                    'avatar' => 'string', 'remember_token' => 'string', 'password' => 'string'],
];
foreach ($drift as $table => $cols) {
    foreach ($cols as $c => $type) {
        if (! Schema::hasColumn($table, $c)) {
            Schema::table($table, function ($t) use ($c, $type) {
                $t->$type($c)->nullable();
            });
        }
    }
}

$now = date('Y-m-d H:i:s');
$ins = function (string $table, array $row) use ($now) {
    foreach (['created_at', 'updated_at'] as $ts) {
        if (Schema::hasColumn($table, $ts) && ! array_key_exists($ts, $row)) {
            $row[$ts] = $now;
        }
    }
    return (int) DB::table($table)->insertGetId($row);
};

$admin = $ins('users', ['level' => 'admin', 'type' => 'person', 'status' => 'active', 'full_name' => 'مدیر',
                        'mobile' => '09100000000', 'last_login' => $now]);
$user = $ins('users', ['level' => 'user', 'type' => 'business', 'status' => 'active', 'full_name' => 'کاربر نمونه',
                       'mobile' => '09120000000', 'company' => 'شرکت نمونه', 'last_login' => $now]);

$catalog = json_decode(file_get_contents("$build/catalog.json"), true);
$slugs = json_decode(file_get_contents("$build/slugs.json"), true) ?: [];
$root = $ins('categories', ['user_id' => $admin, 'title' => 'محصولات', 'slug' => 'products', 'status' => 1, 'order' => 0]);

function slugify($s) {
    $s = Normalizer::normalize(trim($s), Normalizer::FORM_KC) ?: trim($s);
    $s = str_replace(['*', '/', '"', '،'], ['x', '-', '', ''], $s);
    $s = preg_replace('/\s+/u', '-', $s);
    $s = preg_replace('/[^\w\-\x{0600}-\x{06FF}]/u', '', $s);
    return trim(preg_replace('/-{2,}/', '-', $s), '-');
}
function money($s) { return (int) preg_replace('/[^0-9]/', '', (string) $s); }

$specIds = [];
$valueIds = [];
$order = 0;
$prodCount = 0;
foreach ($catalog as $key => $cat) {
    if (empty($cat['rows'])) {
        // Categories without rows exist in production too (تست3, توری کششی).
        $ins('categories', ['user_id' => $admin, 'parent_id' => $root, 'title' => str_replace('-', ' ', $key), 'slug' => $key,
                            'status' => 0, 'order' => ++$order]);
        continue;
    }
    $cid = $ins('categories', ['user_id' => $admin, 'parent_id' => $root, 'title' => str_replace(['--', '-'], [' ', ' '], $key),
                               'slug' => $key, 'status' => 1, 'order' => ++$order, 'index_by_crawler' => 1,
                               'meta_description' => 'قیمت روز ' . str_replace('-', ' ', $key)]);
    foreach ($cat['specs'] as $i => $title) {
        if (! isset($specIds[$title])) {
            $specIds[$title] = $ins('specs', ['title' => $title]);
        }
        DB::table('category_spec')->insert(['category_id' => $cid, 'spec_id' => $specIds[$title], 'sort' => $i]);
    }
    foreach ($cat['rows'] as $n => $row) {
        $name = $row['نام محصول'];
        $price = 0; $prev = 0;
        foreach ($row as $k => $v) {
            if (strpos($k, 'قیمت روز') === 0) { $price = money($v); }
            if (strpos($k, 'نوسان') === 0) { $prev = money($v); }
        }
        $pid = $ins('products', ['category_id' => $cid, 'title' => $name, 'slug' => $slugs[$name] ?? slugify($name),
                                 'price' => $price, 'unit' => $row['واحد'], 'status' => 1, 'order' => $n + 1,
                                 'index_by_crawler' => 1]);
        $prodCount++;
        foreach ($cat['specs'] as $title) {
            $v = trim((string) ($row[$title] ?? ''));
            if ($v === '') { continue; }
            $vk = $specIds[$title] . "\x1f" . $v;
            if (! isset($valueIds[$vk])) {
                $valueIds[$vk] = $ins('values', ['spec_id' => $specIds[$title], 'title' => $v]);
            }
            DB::table('product_spec_value')->insert(['product_id' => $pid, 'spec_id' => $specIds[$title], 'value_id' => $valueIds[$vk]]);
        }
        // A short, plausible history: the previous price 40 days ago, one step
        // in between for every third product, today's price 3 days ago.
        if ($prev && $prev !== $price) {
            $mid = (int) round(($prev + $price) / 2 / 10000) * 10000;
            if ($n % 3 === 0) {
                $ins('prices', ['product_id' => $pid, 'first_price' => $prev, 'second_price' => $mid,
                                'price_at' => date('Y-m-d H:i:s', strtotime('-40 days'))]);
                $ins('prices', ['product_id' => $pid, 'first_price' => $mid, 'second_price' => $price,
                                'price_at' => date('Y-m-d H:i:s', strtotime('-3 days'))]);
            } else {
                $ins('prices', ['product_id' => $pid, 'first_price' => $prev, 'second_price' => $price,
                                'price_at' => date('Y-m-d H:i:s', strtotime('-' . (3 + $n) . ' days'))]);
            }
        }
    }
}
// One import "today", so the stamp says امروز.
DB::table('prices')->where('id', DB::table('prices')->max('id'))->update(['price_at' => $now]);
echo "catalogue: $prodCount products\n";

$articles = json_decode(file_get_contents("$build/articles.json"), true);
$acats = [];
foreach ($articles as $a) {
    if (! isset($acats[$a['cat_slug']])) {
        $acats[$a['cat_slug']] = $ins('article_categories', ['user_id' => $admin, 'title' => $a['cat_title'], 'slug' => $a['cat_slug']]);
    }
    $ins('articles', ['user_id' => $admin, 'category_id' => $acats[$a['cat_slug']], 'title' => $a['title'],
                      'description' => mb_substr((string) $a['description'], 0, 480), 'body' => $a['body'], 'slug' => $a['slug'],
                      'image' => 'no-picture.jpg', 'index_by_crawler' => 1,
                      'created_at' => date('Y-m-d H:i:s', strtotime($a['published'] ?: 'now')),
                      'updated_at' => date('Y-m-d H:i:s', strtotime($a['modified'] ?: 'now'))]);
}
echo 'articles: ' . count($articles) . "\n";

$ins('information', ['email' => 'info@sepahanfelez.ir', 'phone' => '02191326030', 'fax' => '-', 'work_time' => '۸ تا ۱۷']);
$ins('home_settings', ['about' => '-', 'about_pic' => 'x.jpg', 'footer_pic1' => 'x.jpg', 'footer_pic2' => 'x.jpg']);
$ins('general_settings', ['company_name' => 'طلوع سپاهان', 'favicon' => 'favicon.ico']);
$ins('abouts', ['image' => 'x.jpg', 'video' => '', 'canonical' => '', 'text' => '-']);
$sales = $ins('roles', ['name' => 'sales', 'label' => 'واحد فروش', 'ticketing' => 1]);
$ins('roles', ['name' => 'support', 'label' => 'پشتیبانی', 'ticketing' => 1]);

$isf = $ins('locations', ['region' => 'اصفهان']);
$teh = $ins('locations', ['region' => 'تهران']);
$najaf = $ins('locations', ['province_id' => $isf, 'region' => 'نجف‌آباد']);
$ins('locations', ['province_id' => $isf, 'region' => 'اصفهان']);
$ins('locations', ['province_id' => $teh, 'region' => 'تهران']);
$addr = $ins('addresses', ['user_id' => $user, 'location_id' => $najaf, 'title' => 'کارگاه منتظریه',
                           'address' => 'شهرک صنعتی منتظریه، خیابان ۱۰۱', 'postal_code' => '8513100000', 'is_default_address' => 1]);
$ticket = $ins('tickets', ['user_id' => $user, 'role_id' => $sales, 'subject' => 'پیش‌فاکتور ۳ تن توری حصاری چشمه ۶/۵', 'status' => 'answered']);
$ins('messages', ['user_id' => $user, 'ticket_id' => $ticket, 'text' => "سلام، برای ۳ تن توری حصاری چشمه ۶/۵ مفتول ۲/۷ پیش‌فاکتور می‌خواهم.\nتحویل: نجف‌آباد"]);
$ins('messages', ['user_id' => $admin, 'ticket_id' => $ticket, 'text' => 'سلام. پیش‌فاکتور پیوست شد؛ برای هماهنگی بارگیری با داخلی ۱۰۲ تماس بگیرید.']);
if (Schema::hasTable('gateways')) {
    $gw = $ins('gateways', ['title' => 'زرین‌پال', 'driver' => 'zarinpal', 'is_default' => 1, 'image' => 'x.png']);
    $order = $ins('orders', ['user_id' => $user, 'address_id' => $addr, 'gateway_id' => $gw, 'total_price' => 45000000, 'status' => 'sent',
                             'paid_at' => $now]);
    $firstProduct = DB::table('products')->value('id');
    $ins('order_products', ['order_id' => $order, 'product_id' => $firstProduct, 'quantity' => 30, 'price' => 1500000]);
}
$firstCat = DB::table('categories')->where('slug', 'توری-حصاری')->value('id');
$ins('product_comments', ['category_id' => $firstCat, 'name' => 'پیمانکار محوطه‌سازی', 'phone' => '09120000001',
                          'body' => 'وزن هر مترمربع با جدول یکی بود و بار با باسکول تحویل شد.', 'answer' => 'سپاس از اعتماد شما.', 'is_approved' => 1]);

echo "demo user: 09120000000 (id $user)\n";
