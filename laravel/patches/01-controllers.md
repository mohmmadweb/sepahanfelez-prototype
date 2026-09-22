# پچ ۱ — کنترلرها

سه کنترلر باید چند متغیر تازه به ویو بدهند. هیچ خط موجودی حذف نمی‌شود؛
فقط `return view(...)` به `RedesignView::pick()` عوض می‌شود و چند `with`
اضافه می‌گردد. اگر `REDESIGN_ENABLED=false` باشد، همین کنترلرها ویوی قبلی را
می‌دهند و متغیرهای اضافه بی‌استفاده می‌مانند (هزینه‌ای ندارند چون lazy اند).

---

## ۱. `app/Http/Controllers/Site/CategoryController.php`

در `index()`، انتهای متد را چنین کنید:

```php
        $comments = ProductComment::where("is_approved" , true)->where("category_id" , $category->id)->get()->all();

        $relatedArticles = \App\Support\Redesign::relatedArticles($category, 3);

        return view(\App\Support\RedesignView::pick('category'))->with([
            "category"          =>  $category ,
            "comments"          =>  $comments ,
            "spec_values"       =>  $spec_values,
            "products"          =>  $products,
            "lastUpdate"        =>  $lastUpdate,
            "relatedArticles"   =>  $relatedArticles,
        ]);
```

تنها تغییر واقعی: `relatedArticles` حالا از `Redesign::relatedArticles()`
می‌آید (همان منطق برچسب مشترک، فقط یک‌جا) و نام ویو از `RedesignView` گرفته
می‌شود.

---

## ۲. `app/Http/Controllers/Site/ProductController.php`

متد `show()` امروز فقط سه متغیر می‌دهد. سه تای دیگر لازم است:

```php
    public function show($category, $product)
    {
        $category = Category::query()->where("slug", "=", $category)->with("specs")->firstOrFail();
        $product  = Product::query()->where("slug", "=", $product)->firstOrFail();

        $spec_values = DB::table('product_spec_value')
            ->where('product_id', $product->id)
            ->leftJoin("values", "value_id", "=", "id")
            ->get();

        // کالاهای هم‌دسته برای جدول پایین صفحه
        $siblings = Product::query()
            ->where('category_id', $category->id)
            ->where('status', '1')
            ->where('id', '!=', $product->id)
            ->orderBy('order')
            ->take(8)
            ->get();

        $relatedArticles = \App\Support\Redesign::relatedArticles($category, 3);

        return view(\App\Support\RedesignView::pick('product'))
            ->with(compact('product', 'spec_values', 'category', 'siblings', 'relatedArticles'));
    }
```

> `$product->latestPrice` در ویو استفاده می‌شود و رابطه‌اش از قبل روی مدل
> هست، پس کوئری اضافه‌ای لازم ندارد.

---

## ۳. `app/Http/Controllers/Site/PriceListController.php`

صفحه‌ی قیمت حالا یک جدول به ازای هر دسته‌ی برگ می‌دهد، نه یک فهرست
صفحه‌بندی‌شده. بخش `$all_prices` دست‌نخورده می‌ماند (اسکیمای ItemList و
نسخه‌ی قبلی به آن نیاز دارند)؛ این‌ها اضافه می‌شوند:

```php
        $parentCategories = Category::where("parent_id" , null)->get();
        $homeSetting      = HomeSetting::query()->first();

        // دسته‌های برگ با محصولات و آخرین قیمتشان — یک جدول برای هرکدام
        $tableCategories = Category::query()
            ->where('status', true)
            ->whereDoesntHave('child')
            ->with(['specs', 'products' => function ($q) {
                $q->where('status', '1')->orderBy('order')->with('latestPrice');
            }])
            ->orderBy('order')
            ->get()
            ->filter(function ($c) { return $c->products->count(); })
            ->values();

        // مقادیر مشخصات همه‌ی این محصول‌ها، یک کوئری
        $productIds = $tableCategories->pluck('products')->flatten()->pluck('id');
        $specValues = DB::table('product_spec_value')
            ->whereIn('product_id', $productIds)
            ->leftJoin('values', 'value_id', '=', 'id')
            ->get();

        $movers = \App\Support\Redesign::biggestMovers(10);

        return view(\App\Support\RedesignView::pick('price'))->with([
            "parentCategories" => $parentCategories,
            "prices"           => $all_prices,
            "homeSetting"      => $homeSetting,
            "tableCategories"  => $tableCategories,
            "specValues"       => $specValues,
            "movers"           => $movers,
        ]);
```

بالای فایل `use Illuminate\Support\Facades\DB;` اضافه شود.

---

## ۴. `app/Http/Controllers/Site/CommentController.php`

برای ذخیره‌ی امتیاز ستاره‌ای، در متد `category()` این دو تغییر:

```php
        $rules = [
            "body" => ["required", "min:3", "max:500"],
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('product_comments', 'rating')) {
            $rules['rating'] = ['nullable', 'integer', 'between:1,5'];
        }
        $this->validate($request, $rules);
```

و هنگام ساخت رکورد، `'rating' => $request->input('rating')` را فقط وقتی
اضافه کنید که ستون وجود دارد. اگر SQL را اجرا نکرده باشید، فرم ستاره اصلاً
رندر نمی‌شود و این شرط هیچ‌وقت درست نمی‌شود.
