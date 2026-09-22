{{-- داده‌ی ساختاریافته‌ی صفحه‌ی دسته — عیناً همان منطق نسخه‌ی قبلی، چون
     درست کار می‌کرد و دلیلی برای بازنویسی‌اش نبود. --}}
{!! \App\Support\Schema::render($category->schema_tag, \App\Support\Schema::collectionPage(
        $category->meta_title ?: $category->title,
        $category->meta_description,
        \App\Support\Brand::selfUrl()
)) !!}
{!! \App\Support\Schema::script(\App\Support\Schema::breadcrumb([
        ['خانه', \App\Support\Brand::url()],
        ['دسته بندی ها', \App\Support\Brand::url() . '/category'],
        [$category->title, \App\Support\Brand::selfUrl()],
])) !!}
{!! \App\Support\Schema::safely(function () use ($products, $category) {
    $items = [];
    foreach ($products as $product) {
        if (! $product->slug) { continue; }
        $items[] = [
            'name'     => trim($product->title),
            'url'      => \App\Support\Brand::url() . '/category/' . $category->slug . '/' . $product->slug,
            'price'    => $product->price,
            'image'    => $product->image ? \App\Support\Brand::url() . $product->original_image() : null,
            'sku'      => $product->id,
            'category' => $category->title,
            'inStock'  => (bool) $product->status,
        ];
    }
    return \App\Support\Schema::productList($items, \App\Support\Brand::selfUrl(), $category->title);
}) !!}
