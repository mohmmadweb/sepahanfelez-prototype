{{-- جدول قیمت فشرده — الگوی آهن آنلاین.
     $category، $products و $specValues همان متغیرهایی‌اند که
     CategoryController@index و PriceListController از قبل می‌سازند.
     ترتیب ستون‌ها از category_spec.sort می‌آید (پنل: «ستون‌های جدول»). --}}
@php
    $specs   = $category->specs->take(config('redesign.price_table_specs', 4));
    $stats   = \App\Support\Redesign::categoryStats($category);
    $updated = \App\Support\Redesign::lastPriceUpdate($category->id);
    $hasReview = \Illuminate\Support\Facades\Schema::hasColumn('products', 'needs_review');
@endphp
<section class="pt-card" id="pt-{{ $category->slug }}">
    <header class="pt-head">
        <div class="pt-title">
            <h2><a href="{{ route('category.index', ['category' => $category->slug]) }}">{{ $category->title }}</a></h2>
            <span class="pt-meta">{{ $stats['count'] }} نوع کالا · واحد فروش: {{ $stats['unit'] }}</span>
        </div>
        @if($updated)
            <div class="pt-upd"><i class="bi bi-clock" aria-hidden="true"></i>
                آخرین بروزرسانی: <b>{{ \Morilog\Jalali\Jalalian::forge($updated)->format('%d %B %Y') }}</b></div>
        @endif
    </header>

    <div class="pt-wrap">
        <table class="pt">
            <caption class="visually-hidden">قیمت روز {{ $category->title }}</caption>
            <thead>
            <tr>
                <th scope="col">نام کالا</th>
                @foreach($specs as $i => $spec)
                    <th scope="col" class="pt-spec{{ $i >= 2 ? ' pt-lo' : '' }}">{{ $spec->title }}</th>
                @endforeach
                <th scope="col" class="pt-price">قیمت (ریال)</th>
                <th scope="col" class="pt-delta">نوسان</th>
                <th scope="col" class="pt-act"><span class="visually-hidden">نمودار و استعلام</span></th>
            </tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                @php
                    $last = $product->latestPrice;
                    $prev = $last && $last->first_price ? $last->first_price : $product->price;
                    $pct  = $prev ? ($product->price - $prev) / $prev * 100 : 0;
                    $up   = $pct >= 0;
                @endphp
                <tr data-name="{{ $product->title }}"
                    data-price="{{ $product->price }}"
                    data-prev="{{ $prev }}"
                    data-endpoint="{{ url('/product/' . $product->id . '/month') }}">
                    <td class="pt-name">
                        @if($product->slug)
                            <a href="{{ route('product.show', ['category' => $category->slug, 'product' => $product->slug]) }}">{{ $product->title }}</a>
                        @else
                            {{ $product->title }}
                        @endif
                        @if($hasReview && $product->needs_review)
                            <span class="review" title="این قیمت با بقیه‌ی کالاهای دسته هم‌خوان نیست و در حال بازبینی است">بازبینی</span>
                        @endif
                    </td>
                    @foreach($specs as $i => $spec)
                        <td class="pt-spec{{ $i >= 2 ? ' pt-lo' : '' }}" data-label="{{ $spec->title }}">
                            {{ optional($specValues->where('product_id', $product->id)->where('spec_id', $spec->id)->first())->title ?: '—' }}
                        </td>
                    @endforeach
                    <td class="pt-price" data-label="قیمت">
                        <b class="num">{{ number_format($product->price) }}</b>
                        <span class="pt-u">ریال / {{ $product->unit }}</span>
                    </td>
                    <td class="pt-delta" data-label="نوسان">
                        @if($prev && $prev != $product->price)
                            <span class="delta {{ $up ? 'up' : 'down' }}">
                                <i class="bi bi-caret-{{ $up ? 'up' : 'down' }}-fill" aria-hidden="true"></i>
                                <span class="num">{{ ($up ? '+' : '') . number_format($pct, 1) }}٪</span></span>
                        @else
                            <span class="delta flat"><span class="num">۰</span></span>
                        @endif
                    </td>
                    <td class="pt-act">
                        <button type="button" class="pt-chart" data-chart-row title="نمودار قیمت">
                            <i class="bi bi-graph-up" aria-hidden="true"></i>
                            <span class="visually-hidden">نمودار قیمت {{ $product->title }}</span></button>
                        <a class="pt-call" href="tel:{{ config('brand.phone') }}" data-track="call-row" title="استعلام تلفنی">
                            <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                            <span class="visually-hidden">استعلام تلفنی {{ $product->title }}</span></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <footer class="pt-foot">
        <span>قیمت‌ها به ریال و مبنای روز درب کارخانه‌ی اصفهان است؛ قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود.</span>
        <a href="{{ route('category.index', ['category' => $category->slug]) }}">راهنمای خرید و مشخصات کامل
            <i class="bi bi-chevron-left" aria-hidden="true"></i></a>
    </footer>
</section>
