@extends("site.layout.master")
@section('title' , $homeSetting->price_title ?: 'قیمت لحظه‌ای صنایع مفتولی | سپاهان فلز')
@section('description' , $homeSetting->price_description)
@section("canonical" , $homeSetting->price_canonical)
@section('og_type', 'website')
@section('og_title', $homeSetting->price_title)
@section('og_description', $homeSetting->price_description)
@section('google_index', 'index')

@section("content")
    {!! \App\Support\Schema::safely(function () use ($prices) {
        $items = [];
        foreach ($prices as $price) {
            $p = $price->product;
            if (! $p || ! $p->category || ! $p->slug || ! $p->category->slug) { continue; }
            $items[] = [
                'name'     => trim($p->title),
                'url'      => \App\Support\Brand::url() . '/category/' . $p->category->slug . '/' . $p->slug,
                'price'    => $p->price,
                'image'    => $p->image ? \App\Support\Brand::url() . $p->original_image() : null,
                'sku'      => $p->id,
                'category' => $p->category->title,
                'inStock'  => (bool) $p->status,
            ];
        }
        $start = method_exists($prices, 'firstItem') && $prices->firstItem() ? $prices->firstItem() : 1;
        return \App\Support\Schema::productList($items, \App\Support\Brand::url() . '/price',
            'جدول قیمت لحظه ای محصولات ' . \App\Support\Brand::name(), $start);
    }) !!}

    @php $updated = \App\Support\Redesign::lastPriceUpdate(); @endphp

    <section class="board board-price">
        <div class="container">
            <div class="board-head">
                <div>
                    <h1>قیمت لحظه‌ای<span>صنایع مفتولی طلوع سپاهان</span></h1>
                    <p class="lede">{{ \App\Support\Redesign::basketClaim() }} — قیمت به ریال، مبنای روز درب کارخانه؛
                        هر روز ساعت {{ \App\Support\Redesign::updateTime() }} بروزرسانی می‌شود.</p>
                </div>
                @if($updated)
                    <span class="stamp"><span class="dot"></span>
                        بروزرسانی: {{ \Morilog\Jalali\Jalalian::forge($updated)->format('%d %B %Y') }}</span>
                @endif
            </div>

            <div class="price-tools">
                <label class="gsearch"><i class="bi bi-search" aria-hidden="true"></i>
                    <span class="visually-hidden">جست‌وجو در همه‌ی جدول‌ها</span>
                    <input type="search" data-global-search value="{{ request('q') }}"
                           placeholder="جست‌وجوی نام کالا در همه‌ی جدول‌ها — مثلاً: چشمه ۵/۵ یا مفتول ۳" autocomplete="off">
                    <output data-global-count aria-live="polite"></output></label>
                <div class="chiprow">
                    @foreach($tableCategories as $c)
                        <a class="chip" href="#pt-{{ $c->slug }}">{{ $c->title }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section price-section">
        <div class="container price-full">
            @if(count($movers))
                <section class="pt-card ch-card" id="changes">
                    <header class="pt-head">
                        <div class="pt-title"><h2>آخرین تغییرات قیمت</h2>
                            <span class="pt-meta">{{ count($movers) }} کالا با بیشترین تغییر نسبت به آخرین قیمت ثبت‌شده</span></div>
                    </header>
                    <div class="pt-wrap">
                        <table class="pt ch">
                            <thead><tr>
                                <th scope="col"><span class="visually-hidden">جهت</span></th>
                                <th scope="col">نام کالا</th><th scope="col">قیمت لحظه‌ای</th>
                                <th scope="col">نوسان</th><th scope="col">مقدار تغییر</th>
                                <th scope="col"><span class="visually-hidden">نمودار</span></th>
                            </tr></thead>
                            <tbody>
                            @foreach($movers as $m)
                                @php
                                    $p = $m->product;
                                    $up = $m->second_price >= $m->first_price;
                                    $pct = $m->first_price ? ($m->second_price - $m->first_price) / $m->first_price * 100 : 0;
                                @endphp
                                <tr data-name="{{ $p->title }}" data-price="{{ $p->price }}" data-prev="{{ $m->first_price }}"
                                    data-endpoint="{{ url('/product/' . $p->id . '/month') }}">
                                    <td class="ch-dir {{ $up ? 'up' : 'down' }}">
                                        <i class="bi bi-caret-{{ $up ? 'up' : 'down' }}-fill" aria-hidden="true"></i></td>
                                    <td class="ch-name">
                                        @if($p->slug)
                                            <a href="{{ route('product.show', ['category' => $p->category->slug, 'product' => $p->slug]) }}">{{ $p->title }}</a>
                                        @else
                                            {{ $p->title }}
                                        @endif
                                        <span class="ch-cat">{{ $p->category->title }} · {{ $p->unit }}</span></td>
                                    <td class="ch-price" data-label="قیمت لحظه‌ای">
                                        <b class="num">{{ number_format($p->price) }}</b> <span class="riyal">ریال</span></td>
                                    <td class="ch-pct" data-label="نوسان">
                                        <span class="delta {{ $up ? 'up' : 'down' }}">
                                            <span class="num">{{ ($up ? '+' : '') . number_format($pct, 1) }}٪</span></span></td>
                                    <td class="ch-diff" data-label="مقدار تغییر">
                                        <span class="num">{{ number_format(abs($m->second_price - $m->first_price)) }}</span> ریال
                                        <span class="{{ $up ? 'up' : 'down' }}">{{ $up ? 'افزایش' : 'کاهش' }}</span></td>
                                    <td class="pt-act">
                                        <button type="button" class="pt-chart" data-chart-row title="نمودار قیمت">
                                            <i class="bi bi-graph-up" aria-hidden="true"></i>
                                            <span class="visually-hidden">نمودار</span></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @foreach($tableCategories as $c)
                @include('site.redesign.partials.price-table', [
                    'category'   => $c,
                    'products'   => $c->products,
                    'specValues' => $specValues,
                ])
            @endforeach

            <p class="tnote">ستون «نوسان» تغییر نسبت به آخرین قیمت ثبت‌شده است. آیکون نمودار در هر ردیف،
                روند قیمت همان کالا را باز می‌کند. قیمت قطعی سفارش به تناژ و مقصد بار بستگی دارد.</p>
        </div>
    </section>

    <section class="section alt" id="experts">
        <div class="container">
            <div class="section-head">
                <div><h2>کارشناسان فروش — با داخلی مستقیم</h2>
                    <div class="sub">هر دسته یک کارشناس مشخص دارد؛ تماس با داخلی، بی‌واسطه به همان کارشناس وصل می‌شود.</div></div>
            </div>
            <div class="experts-grid">
                @foreach(\App\Support\Redesign::expertsFor() as $e)
                    @include('site.redesign.partials.expert-card', ['e' => $e])
                @endforeach
            </div>
        </div>
    </section>

    @include('site.redesign.partials.callband')
@endsection

@section("script")
    <script defer src="{{ \App\Support\Asset::url('files/js/redesign.js') }}"></script>
    <script defer src="{{ \App\Support\Asset::url('files/js/print.js') }}"></script>
@endsection
