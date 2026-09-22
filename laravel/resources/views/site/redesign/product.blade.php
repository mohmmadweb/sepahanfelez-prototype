@extends("site.layout.master")
@section("title"       , $product->meta_title ?: 'قیمت ' . $product->title . ' | سپاهان فلز')
@section("description" , $product->meta_description)
@section("keywords"    , $product->meta_keywords)
@section("canonical"   , $product->canonical)
@section('og_type', 'product')
@section('og_title', $product->meta_title ?: $product->title)
@section('og_description', $product->meta_description)
@section('google_index', \App\Support\Brand::robots($product->index_by_crawler, $product->slug))

@section("content")
    {!! \App\Support\Schema::safely(function () use ($product, $category) {
        $image = $product->image ? \App\Support\Brand::url() . $product->original_image() : null;
        $generated = \App\Support\Schema::product(
            $product->title, $product->description,
            \App\Support\Brand::url() . '/category/' . $category->slug . '/' . $product->slug,
            $image, $product->price, $product->id, $category->title, (bool) $product->status
        );
        return \App\Support\Schema::render($product->schema_tag, $generated);
    }) !!}
    {!! \App\Support\Schema::script(\App\Support\Schema::breadcrumb([
            ['خانه', \App\Support\Brand::url()],
            ['دسته بندی ها', \App\Support\Brand::url() . '/category'],
            [$category->title, \App\Support\Brand::url() . '/category/' . $category->slug],
            [$product->title, \App\Support\Brand::selfUrl()],
    ])) !!}

    @php
        $last = $product->latestPrice;
        $prev = $last && $last->first_price ? $last->first_price : $product->price;
        $pct  = $prev ? ($product->price - $prev) / $prev * 100 : 0;
        $up   = $pct >= 0;
        $updated = \App\Support\Redesign::lastPriceUpdate($category->id);
    @endphp

    <section class="section product">
        <div class="container">
            <div class="pgrid">
                <div class="gallery">
                    <div class="gallery-main">
                        <img id="gmain"
                             src="{{ $product->image ? $product->original_image() : $category->original_image() }}"
                             alt="{{ $category->title }} — {{ $product->title }}" decoding="async">
                    </div>
                    <p class="gallery-note">
                        {{ $product->image ? 'تصویر همین کالا' : 'تصویر نمونه‌ی محصولات این دسته از خط تولید طلوع سپاهان' }}
                    </p>
                </div>

                <div class="pinfo">
                    <a class="chip" href="{{ route('category.index', ['category' => $category->slug]) }}">{{ $category->title }}</a>
                    <h1>{{ $product->title }}</h1>
                    <p class="plede">{{ $category->title }} — تولید صنایع مفتولی طلوع سپاهان،
                        امکان خرید مستقیم از کارخانه و انبار تهران.</p>

                    <div class="pcard">
                        <div class="pcard-price">
                            <span class="k">قیمت روز</span>
                            <span class="v"><b class="num">{{ number_format($product->price) }}</b>
                                <span class="u">ریال / {{ $product->unit }}</span></span>
                            @if($prev && $prev != $product->price)
                                <span class="pcard-meta">
                                    <span class="delta {{ $up ? 'up' : 'down' }}">
                                        <i class="bi bi-caret-{{ $up ? 'up' : 'down' }}-fill" aria-hidden="true"></i>
                                        <span class="num">{{ ($up ? '+' : '') . number_format($pct, 1) }}٪</span></span>
                                    نسبت به آخرین ثبت
                                    <span class="pcard-prev">قیمت ثبت قبلی: <span class="num">{{ number_format($prev) }}</span> ریال</span>
                                </span>
                            @endif
                        </div>
                        @if($updated)
                            <div class="pcard-upd">
                                <span class="stamp"><span class="dot"></span>
                                    بروزرسانی: {{ \Morilog\Jalali\Jalalian::forge($updated)->format('%d %B %Y') }}</span>
                                <span class="dim">· ساعت {{ \App\Support\Redesign::updateTime() }} هر روز</span></div>
                        @endif

                        <div class="pcard-cta">
                            <a class="btn btn-call btn-lg2" href="tel:{{ config('brand.phone') }}" data-track="call-product">
                                <i class="bi bi-telephone-fill" aria-hidden="true"></i> استعلام و ثبت سفارش
                                <span class="num">{{ config('brand.phone_display') }}</span></a>
                            @if($wa = config('brand.whatsapp'))
                                <a class="btn btn-ghost btn-lg2" href="{{ $wa }}" rel="noopener" target="_blank">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i> واتساپ</a>
                            @endif
                            {{-- سبد خرید بک‌اند دست‌نخورده است و اینجا در دسترس
                                 می‌ماند؛ استراتژی فروش تلفنی آن را کنار نمی‌زند. --}}
                            <form action="{{ route('cart.store', ['id' => $product->id]) }}" method="post" class="pcard-cart">@csrf
                                <button type="submit" class="btn btn-ghost btn-lg2">
                                    <i class="bi bi-cart-fill" aria-hidden="true"></i> افزودن به لیست سفارش</button>
                            </form>
                        </div>
                        <p class="pcard-note">قیمت جدول مبنای روز است؛ قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود.</p>
                    </div>

                    <div class="kvgrid">
                        <div class="kv"><span class="k">دسته</span>
                            <span class="v"><a href="{{ route('category.index', ['category' => $category->slug]) }}">{{ $category->title }}</a></span></div>
                        <div class="kv"><span class="k">واحد فروش</span><span class="v">{{ $product->unit }}</span></div>
                        @foreach($category->specs as $spec)
                            @php $v = optional($spec_values->where('spec_id', $spec->id)->first())->title; @endphp
                            @if($v)
                                <div class="kv"><span class="k">{{ $spec->title }}</span><span class="v">{{ $v }}</span></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section alt">
        <div class="container two-col">
            <div>
                @include('site.redesign.partials.chart', [
                    'id'       => 'chart-product-' . $product->id,
                    'endpoint' => url('/product/' . $product->id . '/month'),
                    'price'    => $product->price,
                    'prev'     => $prev,
                    'key'      => $product->title,
                    'title'    => 'نمودار قیمت ' . $product->title,
                    'sub'      => 'ریال / ' . $product->unit . ' · مبنای روز درب کارخانه',
                ])
            </div>
            <div>
                @include('site.redesign.partials.experts', [
                    'slug' => $category->slug, 'title' => 'کارشناس فروش این محصول',
                ])
            </div>
        </div>
    </section>

    @include('site.redesign.partials.calculator', ['product' => $product, 'specValues' => $spec_values])

    {{-- توضیحات محصول: پنل → محصول → محتوا --}}
    @if($product->description)
        <section class="section">
            <div class="container">
                <div class="prose wide cols-2 main-text">
                    <h2>درباره‌ی {{ $product->title }}</h2>
                    {!! $product->description !!}
                </div>
            </div>
        </section>
    @endif

    @if(isset($siblings) && count($siblings))
        <section class="section alt">
            <div class="container">
                <div class="section-head">
                    <div><h2>محصولات هم‌دسته</h2>
                        <div class="sub">{{ count($siblings) }} نوع کالای دیگر در {{ $category->title }}</div></div>
                    <a href="{{ route('category.index', ['category' => $category->slug]) }}">جدول کامل
                        <i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                </div>
                <div class="pt-wrap sib">
                    <table class="pt pt-mini">
                        <thead><tr><th scope="col">نام کالا</th><th scope="col">قیمت (ریال)</th></tr></thead>
                        <tbody>
                        @foreach($siblings as $s)
                            <tr>
                                <td class="pt-name">
                                    @if($s->slug)
                                        <a href="{{ route('product.show', ['category' => $category->slug, 'product' => $s->slug]) }}">{{ $s->title }}</a>
                                    @else
                                        {{ $s->title }}
                                    @endif
                                </td>
                                <td class="pt-price" data-label="قیمت"><b class="num">{{ number_format($s->price) }}</b></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @include('site.redesign.partials.reviews', ['category' => $category, 'subject' => $product->title])

    @if(isset($relatedArticles) && count($relatedArticles))
        <section class="section alt">
            <div class="container">
                <div class="section-head">
                    <div><h2>مقالات مرتبط با {{ $category->title }}</h2></div>
                    <a href="{{ route('blog.index') }}">همه‌ی مقالات <i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                </div>
                <div class="mag-grid mag-grid-3">
                    @foreach($relatedArticles as $article)
                        @include('site.redesign.partials.article-card', ['a' => $article])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.redesign.partials.callband')
@endsection

@section("script")
    <script defer src="{{ \App\Support\Asset::url('files/js/redesign.js') }}"></script>
@endsection
