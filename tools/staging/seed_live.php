<?php
/**
 * STAGING database from the live panel's data (tools/staging/live_dataset.py).
 *
 * Run from the root of the staging copy of Ahanamn:
 *
 *     php /path/to/prototype/tools/staging/seed_live.php dataset.json
 *
 * Migrates a fresh SQLite database, adds the columns production has but the
 * migrations lack, and inserts every row with its live id. Then the admin
 * permissions (the backend's own PermissionTableSeeder) and one staging admin
 * (id 1) who logs in through /_staging/login.
 */

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$data = json_decode(file_get_contents($argv[1] ?? ''), true);
if (! $data) {
    fwrite(STDERR, "usage: php seed_live.php dataset.json\n");
    exit(1);
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

if (config('database.default') !== 'sqlite') {
    fwrite(STDERR, "refusing: DB_CONNECTION is not sqlite\n");
    exit(1);
}
$db = config('database.connections.sqlite.database');
@unlink($db);
touch($db);
DB::purge('sqlite');
$app->make(ConsoleKernel::class)->call('migrate', ['--force' => true]);
echo "migrated\n";

$drift = [
    'products' => ['slug' => 'string', 'image' => 'string', 'meta_title' => 'string', 'meta_description' => 'text',
                   'meta_keyword' => 'string', 'index_by_crawler' => 'boolean', 'description' => 'text',
                   'canonical' => 'string', 'tax' => 'integer'],
    'users'    => ['full_name' => 'string', 'email' => 'string', 'mobile' => 'string', 'phone' => 'string',
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
DB::table('users')->insert(['id' => 1, 'level' => 'admin', 'type' => 'person', 'status' => 'active',
                            'full_name' => 'مدیر (نسخه‌ی آزمایشی)', 'mobile' => '09120000000', 'last_login' => $now,
                            'created_at' => $now, 'updated_at' => $now]);

$app->make(ConsoleKernel::class)->call('db:seed', ['--class' => 'PermissionTableSeeder', '--force' => true]);
$app->make(ConsoleKernel::class)->call('db:seed', ['--class' => 'LocationTableSeeder', '--force' => true]);
$role = DB::table('roles')->insertGetId(['name' => 'manager', 'label' => 'مدیر کل', 'ticketing' => 1,
                                         'created_at' => $now, 'updated_at' => $now]);
foreach (DB::table('permissions')->pluck('id') as $p) {
    DB::table('permission_role')->insert(['role_id' => $role, 'permission_id' => $p]);
}
DB::table('role_user')->insert(['role_id' => $role, 'user_id' => 1]);
DB::table('roles')->insert(['name' => 'sales', 'label' => 'واحد فروش', 'ticketing' => 1, 'created_at' => $now, 'updated_at' => $now]);

// Parents before children, specs before values before products.
$order = ['specs', 'values', 'categories', 'category_spec', 'features', 'usages', 'products', 'product_spec_value',
          'prices', 'article_categories', 'articles', 'tags', 'taggables', 'redirects', 'sliders', 'socials',
          'information', 'home_settings', 'abouts', 'general_settings', 'tutorials', 'product_comments',
          'article_comments', 'discounts', 'videos'];
DB::statement('PRAGMA foreign_keys = OFF');
foreach ($order as $table) {
    $rows = $data['tables'][$table] ?? [];
    if (! Schema::hasTable($table)) {
        echo "skip $table (no table)\n";
        continue;
    }
    $cols = array_flip(Schema::getColumnListing($table));
    if ($table === 'categories') {
        // parent_id points at rows that may come later in the list.
        usort($rows, function ($a, $b) { return ($a['parent_id'] ? 1 : 0) - ($b['parent_id'] ? 1 : 0); });
    }
    foreach (array_chunk($rows, 200) as $chunk) {
        $chunk = array_map(function ($r) use ($cols, $now) {
            foreach (['created_at', 'updated_at'] as $ts) {
                if (isset($cols[$ts]) && empty($r[$ts])) {
                    $r[$ts] = $r['updated_at'] ?? $now;
                }
            }
            return array_intersect_key($r, $cols);
        }, $chunk);
        DB::table($table)->insert($chunk);
    }
    printf("%-20s %d\n", $table, count($rows));
}
DB::statement('PRAGMA foreign_keys = ON');
