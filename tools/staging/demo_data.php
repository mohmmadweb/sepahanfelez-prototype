<?php
/**
 * STAGING ONLY: one sample row in each section the live export leaves empty
 * (customers, orders, tickets, messages, comments, news), so every screen of
 * the admin panel can be opened and tried. Every row says «نمونه».
 *
 *     php /path/to/prototype/tools/staging/demo_data.php      (from the staging app root)
 *
 * Safe to run again: it removes its own rows first (mobile 09120000001,
 * slug/subject markers below).
 */

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();
if (config('database.default') !== 'sqlite') {
    fwrite(STDERR, "refusing: not the staging sqlite database\n");
    exit(1);
}

$now = date('Y-m-d H:i:s');
$ago = function ($days) { return date('Y-m-d H:i:s', strtotime("-$days days")); };
$ins = function ($table, $row) use ($now) {
    $cols = array_flip(\Schema::getColumnListing($table));
    foreach (['created_at', 'updated_at'] as $ts) {
        if (isset($cols[$ts]) && ! array_key_exists($ts, $row)) {
            $row[$ts] = $now;
        }
    }
    return (int) DB::table($table)->insertGetId(array_intersect_key($row, $cols));
};

// Remove a previous run.
$old = DB::table('users')->where('mobile', '09120000001')->value('id');
if ($old) {
    DB::table('order_products')->whereIn('order_id', DB::table('orders')->where('user_id', $old)->pluck('id'))->delete();
    DB::table('orders')->where('user_id', $old)->delete();
    DB::table('messages')->whereIn('ticket_id', DB::table('tickets')->where('user_id', $old)->pluck('id'))->delete();
    DB::table('tickets')->where('user_id', $old)->delete();
    DB::table('addresses')->where('user_id', $old)->delete();
    DB::table('users')->where('id', $old)->delete();
}
DB::table('news_comments')->whereIn('news_id', DB::table('news')->where('slug', 'نمونه-خبر')->pluck('id'))->delete();
DB::table('news')->where('slug', 'نمونه-خبر')->delete();
foreach (['product_comments', 'article_comments'] as $t) {
    DB::table($t)->where('name', 'like', '%(نمونه)%')->delete();
}
foreach (['news_comments'] as $t) {
    DB::table($t)->where('name', 'like', '%(نمونه)%')->delete();
}
DB::table('contacts')->where('subject', 'like', '%(نمونه)%')->delete();
DB::table('collaborations')->where('last_name', 'like', '%(نمونه)%')->delete();
DB::table('newsletter_members')->where('mobile', '09120000001')->delete();

$user = $ins('users', ['level' => 'user', 'type' => 'business', 'status' => 'active', 'full_name' => 'مشتری نمونه',
                       'mobile' => '09120000001', 'company' => 'شرکت نمونه', 'last_login' => $ago(1), 'created_at' => $ago(30)]);
$isf = DB::table('locations')->whereNull('province_id')->where('region', 'اصفهان')->value('id');
$city = DB::table('locations')->where('province_id', $isf)->where('region', 'like', '%نجف%')->value('id')
     ?: DB::table('locations')->where('province_id', $isf)->value('id');
$addr = $ins('addresses', ['user_id' => $user, 'location_id' => $city, 'title' => 'کارگاه (نمونه)',
                           'address' => 'شهرک صنعتی منتظریه، خیابان ۱۰۱ (نشانی نمونه)', 'postal_code' => '8513100000',
                           'is_default_address' => 1]);

$sales = DB::table('roles')->where('name', 'sales')->value('id') ?: DB::table('roles')->value('id');
$ticket = $ins('tickets', ['user_id' => $user, 'role_id' => $sales, 'subject' => 'پیش‌فاکتور ۳ تن توری حصاری (نمونه)',
                           'status' => 'answered', 'created_at' => $ago(3)]);
$ins('messages', ['user_id' => $user, 'ticket_id' => $ticket, 'created_at' => $ago(3),
                  'text' => "سلام، برای ۳ تن توری حصاری چشمه ۶/۵ مفتول ۲/۷ پیش‌فاکتور می‌خواهم.\nتحویل: نجف‌آباد"]);
$ins('messages', ['user_id' => 1, 'ticket_id' => $ticket, 'created_at' => $ago(2),
                  'text' => 'سلام. پیش‌فاکتور آماده است؛ برای هماهنگی بارگیری با دفتر فروش تماس بگیرید.']);

$gw = DB::table('gateways')->value('id') ?: $ins('gateways', ['title' => 'زرین‌پال', 'driver' => 'zarinpal', 'is_default' => 1,
                                                              'image' => 'zarinpal.png', 'is_active' => 1]);
$items = DB::table('products')->where('status', 1)->where('category_id', 20)->orderBy('order')->limit(2)->get(['id', 'price']);
$total = 0;
foreach ($items as $p) {
    $total += $p->price * 500;
}
$order = $ins('orders', ['user_id' => $user, 'address_id' => $addr, 'gateway_id' => $gw, 'total_price' => $total,
                         'status' => 'pending', 'created_at' => $ago(5)]);
foreach ($items as $p) {
    $ins('order_products', ['order_id' => $order, 'product_id' => $p->id, 'quantity' => 500, 'price' => $p->price]);
}

$ins('product_comments', ['user_id' => $user, 'category_id' => 20, 'name' => 'پیمانکار محوطه‌سازی (نمونه)', 'phone' => '09120000001',
                          'body' => 'وزن هر مترمربع با جدول یکی بود و بار با باسکول تحویل شد.',
                          'answer' => 'سپاس از اعتماد شما.', 'is_approved' => 1, 'created_at' => $ago(6)]);
$art = DB::table('articles')->orderByDesc('id')->value('id');
$ins('article_comments', ['user_id' => $user, 'article_id' => $art, 'name' => 'خواننده (نمونه)', 'phone' => '09120000001',
                          'body' => 'مطلب کاربردی بود؛ جدول وزن را هم اضافه کنید.', 'is_approved' => 0, 'created_at' => $ago(1)]);
$news = $ins('news', ['user_id' => 1, 'title' => 'خبر نمونه', 'slug' => 'نمونه-خبر', 'description' => 'این یک خبر نمونه است.',
                      'body' => '<p>متن خبر نمونه برای دیدن فرم‌های بخش اخبار.</p>', 'type' => 'local']);
$ins('news_comments', ['user_id' => $user, 'news_id' => $news, 'name' => 'بازدیدکننده (نمونه)', 'phone' => '09120000001',
                       'body' => 'دیدگاه نمونه روی خبر.', 'is_approved' => 0]);
$ins('contacts', ['first_name' => 'بازدیدکننده', 'last_name' => 'نمونه', 'phone' => '09120000001',
                  'email' => 'sample@example.com', 'subject' => 'استعلام قیمت مش جوشی (نمونه)',
                  'body' => 'قیمت ۲۰۰ برگ مش جوشی ۱۵×۱۵ مفتول ۵ را می‌خواهم.', 'created_at' => $ago(2)]);
$ins('collaborations', ['first_name' => 'فروشگاه', 'last_name' => 'نمونه (نمونه)', 'phone' => '09120000001',
                        'email' => 'sample@example.com', 'address' => 'اصفهان (نشانی نمونه)', 'created_at' => $ago(4)]);
$ins('newsletter_members', ['mobile' => '09120000001', 'subscribing' => 1]);

echo "sample data: customer $user, order $order, ticket $ticket, news $news\n";
