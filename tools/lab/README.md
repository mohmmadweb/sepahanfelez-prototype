# آزمایشگاه کیت لاراول

برای دیدن کیت `laravel/` روی لاراول واقعی، بی‌آنکه به سرور یا مخزن Ahanamn دست بخورد.

```bash
# ۱. یک کپی از بک‌اند (نه خود مخزن)
rsync -a --exclude .git ~/projects/Ahanamn/ /tmp/lab/app/ && cd /tmp/lab/app
php composer.phar install --no-scripts --ignore-platform-reqs

# ۲. کیت روی آن
rsync -a ~/projects/sepahanfelez-prototype/laravel/ ./ --exclude README.md
#    و در config/app.php بعد از RouteServiceProvider:
#    App\Providers\RedesignServiceProvider::class,

# ۳. .env آزمایشگاه
#    DB_CONNECTION=sqlite   DB_DATABASE=/lab/app/database/lab.sqlite
#    CACHE_DRIVER=file      SESSION_DRIVER=file      REDESIGN_ENABLED=true
touch database/lab.sqlite

# ۴. دیتابیس از کاتالوگ و مقاله‌های پروتوتایپ (PHP 7.4، همان نسخه‌ی سرور)
docker run --rm -v /tmp/lab:/lab -v ~/projects/sepahanfelez-prototype:/proto:ro -w /lab/app \
  php:7.4-cli php /proto/tools/lab/seed.php /proto/build

# ۵. اجرا
docker run -d --name rdlab -v /tmp/lab:/lab -w /lab/app -p 127.0.0.1:8795:8795 \
  php:7.4-cli php -S 0.0.0.0:8795 -t public_html server.php
```

**ورود بدون پیامک، فقط در آزمایشگاه:** این خط را به `routes/web.php` *همان کپی* اضافه کنید و `/_lab/login/2` را باز کنید:

```php
Route::get('/_lab/login/{id}', function ($id) { auth()->loginUsingId($id); return redirect('/user/profile'); });
```

این خط هرگز نباید به مخزن اصلی یا سرور برسد.
