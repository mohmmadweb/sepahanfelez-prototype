@extends("site.layout.master")
@section("title"       , $category->meta_title ?: 'قیمت روز ' . $category->title . ' | سپاهان فلز')
@section("description" , $category->meta_description)
@section("keywords"    , $category->meta_keywords)
@section("canonical"   , $category->canonical)
@section('og_type', 'website')
@section('og_title', $category->meta_title ?: $category->title)
@section('og_description', $category->meta_description)
@section('google_index', \App\Support\Brand::robots($category->index_by_crawler, $category->slug))

@section("content")
    @include('site.redesign.partials.schema-category')

    @php
        $stats   = \App\Support\Redesign::categoryStats($category);
        $updated = \App\Support\Redesign::lastPriceUpdate($category->id);
        $first   = $products->first();
    @endphp

    <section class="board board-cat">
        <div class="container">
            <div class="board-head">
                <div>
                    <h1>{{ $category->meta_title ?: 'قیمت روز ' . $category->title }}</h1>
                    @if($category->intro)
                        <p class="lede">{{ \Illuminate\Support\Str::limit(strip_tags($category->intro), 190) }}</p>
                    @endif
                </div>
                @if($updated)
                    <span class="stamp"><span class="dot"></span>
                        بروزرسانی: {{ \Morilog\Jalali\Jalalian::forge($updated)->format('%d %B %Y') }}
                        — ساعت {{ \App\Support\Redesign::updateTime() }}</span>
                @endif
            </div>

            <div class="ixgrid ix-4">
                <div class="ix"><span class="k">نوع کالای فعال</span>
                    <span class="v"><span class="num">{{ $stats['count'] }}</span></span>
                    <span class="range">در این دسته</span></div>
                <div class="ix"><span class="k">کمترین قیمت</span>
                    <span class="v"><span class="num">{{ number_format($stats['min']) }}</span> <span class="u">ریال</span></span>
                    <span class="range">هر {{ $stats['unit'] }}</span></div>
                <div class="ix"><span class="k">بیشترین قیمت</span>
                    <span class="v"><span class="num">{{ number_format($stats['max']) }}</span> <span class="u">ریال</span></span>
                    <span class="range">هر {{ $stats['unit'] }}</span></div>
                <div class="ix"><span class="k">واحد فروش</span>
                    <span class="v">{{ $stats['unit'] }}</span>
                    <span class="range">قیمت بر همین مبنا</span></div>
            </div>

            <div class="board-call">
                <p class="say">برای {{ $category->title }} با تناژ پروژه‌ای یا تولید سفارشی،
                    <b>قیمت قطعی خود را از کارشناسان ما بگیرید.</b></p>
                <a class="tel" href="tel:{{ config('brand.phone') }}" data-track="call-board">
                    <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                    <span><span class="l">قیمت قطعی همین حالا، تلفنی</span>
                        <span class="n num">{{ config('brand.phone_display') }}</span></span></a>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div><h2>جدول قیمت روز {{ $category->title }}</h2>
                    <div class="sub">{{ $stats['count'] }} نوع کالا با مشخصات فنی — قیمت به ریال،
                        بروزرسانی هر روز ساعت {{ \App\Support\Redesign::updateTime() }}</div></div>
            </div>
            <div class="cat-layout">
                <div class="cat-main">
                    @include('site.redesign.partials.price-table', [
                        'category' => $category, 'products' => $products, 'specValues' => $spec_values,
                    ])
                </div>
                <div class="cat-side">
                    @include('site.redesign.partials.experts', [
                        'slug' => $category->slug, 'title' => 'کارشناسان فروش این دسته',
                    ])
                </div>
            </div>
        </div>
    </section>

    {{-- نمودار میانگین دسته — اندپوینت واقعی /category/chart/{id}/{tf} --}}
    <section class="section alt">
        <div class="container">
            <div class="section-head">
                <div><h2>روند قیمت {{ $category->title }}</h2>
                    <div class="sub">میانگین قیمت دسته؛ نمودار هر کالا با آیکون نمودار در جدول باز می‌شود</div></div>
            </div>
            @include('site.redesign.partials.chart', [
                'id'       => 'chart-cat-' . $category->id,
                'endpoint' => url('/category/chart/' . $category->id . '/month'),
                'price'    => $stats['max'] ?: 0,
                'prev'     => $stats['min'] ?: 0,
                'key'      => $category->slug,
                'title'    => 'نمودار میانگین قیمت ' . $category->title,
                'sub'      => 'میانگین ' . $stats['count'] . ' نوع کالا · ریال / ' . $stats['unit'],
                'days'     => 90,
            ])
        </div>
    </section>

    @if($first)
        @include('site.redesign.partials.calculator', [
            'product' => $first, 'specValues' => $spec_values->where('product_id', $first->id),
        ])
    @endif

    {{-- متن دسته: intro و body هر دو از پنل می‌آیند --}}
    @if($category->intro || $category->body)
        <section class="section">
            <div class="container">
                <div class="prose wide cols-2 main-text">
                    <h2>{{ $category->title }} چیست و کجا به کار می‌آید</h2>
                    {!! $category->intro !!}
                </div>
                @if($category->body)
                    <div class="guides">
                        <details class="guide" open>
                            <summary>راهنمای کامل خرید {{ $category->title }}</summary>
                            <div class="prose main-text">{!! $category->body !!}</div>
                        </details>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ویژگی‌ها و کاربردها — هر دو در پنل CRUD کامل دارند و تا امروز خالی بودند --}}
    @if($category->features->count())
        <section class="section alt" id="properties">
            <div class="container">
                <div class="section-head"><div><h2>ویژگی‌های {{ $category->title }}</h2></div></div>
                <div class="trustgrid">
                    @foreach($category->features as $feature)
                        <div class="trustitem">
                            @if($feature->picture)
                                <img src="{{ $feature->picture() }}" alt="" width="40" height="40" loading="lazy">
                            @endif
                            <div><div class="t">{{ $feature->title }}</div>
                                <div class="d">{{ $feature->description }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($category->usage->count())
        <section class="section" id="application">
            <div class="container">
                <div class="section-head"><div><h2>کاربردهای {{ $category->title }}</h2></div></div>
                <div class="trustgrid">
                    @foreach($category->usage as $usage)
                        <div class="trustitem">
                            @if($usage->picture)
                                <img src="{{ $usage->picture() }}" alt="" width="40" height="40" loading="lazy">
                            @endif
                            <div><div class="t">{{ $usage->title }}</div>
                                <div class="d">{{ $usage->description }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($category->specs->count() && count($products))
        <section class="section alt">
            <div class="container">
                <div class="tablescroll">
                    <table class="spectable">
                        <caption>مشخصات فنی کامل — {{ $stats['count'] }} نوع {{ $category->title }}</caption>
                        <thead><tr><th>نام کالا</th>
                            @foreach($category->specs as $spec)<th>{{ $spec->title }}</th>@endforeach
                        </tr></thead>
                        <tbody>
                        @foreach($products as $product)
                            <tr><td>{{ $product->title }}</td>
                                @foreach($category->specs as $spec)
                                    <td class="num">{{ optional($spec_values->where('product_id', $product->id)->where('spec_id', $spec->id)->first())->title ?: '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @include('site.redesign.partials.reviews', ['category' => $category])

    @if(isset($relatedArticles) && count($relatedArticles))
        <section class="section alt">
            <div class="container">
                <div class="section-head">
                    <div><h2>مقالات مرتبط با {{ $category->title }}</h2>
                        <div class="sub">از مجله‌ی سپاهان فلز</div></div>
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
