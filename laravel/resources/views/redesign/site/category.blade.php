{{--
    Category page (pages.build_category).
    Controller: Site\CategoryController@index → $category (model), $products, … .
    Everything numeric is read from Redesign::category($category->slug); the
    editorial copy from content.php. A category the prototype never wrote copy
    for falls back to its own database fields (meta_description, intro, body).
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $slug = $category->slug;
    $cat = $R::category($slug);
    $c = Rd::cat($slug);
    $t = $c['title'] ?? $category->title;
    $st = $R::stats($slug);
    $photos = $R::photos($slug);
    $meta = $c['meta'] ?? ($category->meta_description ?: ('قیمت روز ' . $t . ' — تولید صنایع مفتولی طلوع سپاهان، بروزرسانی هر روز.'));
    $faq = $c['faq'] ?? [];
    $crumbs = [['خانه', '/'], ['قیمت لحظه‌ای', '/price'], [$t, null]];
@endphp
@section('title', ($category->meta_title && ! $c) ? $category->meta_title : 'قیمت روز ' . $t . ' — طلوع سپاهان')
@section('description', $meta)
@section('robots', \App\Support\Brand::robots($category->index_by_crawler, $slug))
@section('canonical', Rd::uCat($slug))
@section('nav', $slug)
@section('crumbs')@include('rd.crumb', ['items' => $crumbs])@endsection
@section('jsonld')
@php
    $prods = [];
    foreach (array_slice($cat['rows'] ?? [], 0, 30) as $r) {
        $nm = Rd::cleanName($r['نام محصول']);
        $props = [];
        foreach (array_slice($cat['specs'], 0, 6) as $k2) { $props[] = [$k2, Rd::cleanVal($r[$k2] ?? '')]; }
        $prods[] = Rd::product($nm, Rd::uProd($slug, $r['_slug']), $nm . ' — تولید صنایع مفتولی طلوع سپاهان، قیمت روز درب کارخانه‌ی اصفهان.', $r['_price'], $r['واحد'], $photos[0] ?? null, $props);
    }
@endphp
{{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', Rd::uCat($slug), 'قیمت روز ' . $t, ['description' => $meta, 'about' => ['@id' => Rd::orgId()], 'primaryImageOfPage' => $photos ? Rd::site($photos[0]) : null]), $prods ? Rd::itemList($prods, 'کالاهای ' . $t) : null, Rd::faq($faq), Rd::breadcrumb([['خانه', '/'], ['قیمت لحظه‌ای', '/price'], [$t, null]])) }}
@endsection
@section('content')
  <section class="board board-cat">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>{{ $c['h1'] ?? ('قیمت روز ' . $t) }}</h1>
          <p class="lede">{{ $c['lede'] ?? $meta }}</p>
        </div>
        @include('rd.stamp', ['at' => $cat['last_update'] ?? null])
      </div>
      @if($cat)
      <div class="board-grid">
        <div class="ixgrid ix-4">
          <div class="ix"><span class="k">نوع کالای فعال</span><span class="v"><span class="num">{{ $st['n'] }}</span></span><span class="range">در این دسته</span></div>
          <div class="ix"><span class="k">کمترین قیمت</span><span class="v"><span class="num">{{ Rd::fmt($st['min']) }}</span> <span class="u">ریال</span></span><span class="range">هر {{ $st['unit'] }}</span></div>
          <div class="ix"><span class="k">بیشترین قیمت</span><span class="v"><span class="num">{{ Rd::fmt($st['max']) }}</span> <span class="u">ریال</span></span><span class="range">هر {{ $st['unit'] }}</span></div>
          <div class="ix"><span class="k">واحد فروش</span><span class="v">{{ $st['unit'] }}</span><span class="range">{{ $c['unit_note'] ?? ('قیمت بر پایه‌ی ' . $st['unit'] . ' است.') }}</span></div>
        </div>
        @if($photos)
        <div class="board-photos" data-lb>
          @foreach($photos as $i => $src)
            <a class="ph" data-lb-item data-full="{{ $src }}" href="{{ $src }}"@if($i >= 6) style="display:none"@endif><img src="{{ $R::thumb($src) }}" alt="{{ $t }} — تصویر {{ $i + 1 }}" loading="lazy" width="420" height="315">@if($i === 5 && count($photos) > 6)<span class="more">+{{ count($photos) - 6 + 1 }}</span>@endif</a>
          @endforeach
        </div>
        @endif
      </div>
      @endif
      <div class="board-call">
        <p class="say">برای {{ $t }} با تناژ پروژه‌ای یا تولید سفارشی،
          <b>قیمت قطعی خود را از کارشناسان ما بگیرید.</b></p>
        <a class="tel" href="tel:{{ Rd::phone() }}" data-track="call-board">
          {{ Rd::icon('i-phone') }}<span><span class="l">قیمت قطعی همین حالا، تلفنی</span><span class="n num">{{ Rd::phoneShow() }}</span></span></a>
      </div>
    </div>
  </section>

@if($cat)
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h2>جدول قیمت روز {{ $t }}</h2>
          <div class="sub">{{ Rd::fa($st['n']) }} نوع کالا با مشخصات فنی — قیمت به ریال، بروزرسانی هر روز ساعت {{ Rd::updateTime() }}</div></div>
      </div>
      @include('rd.price-table', ['slug' => $slug, 'search' => true, 'title' => false, 'guideLink' => false])
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>روند قیمت {{ $t }}</h2><div class="sub">میانگین قیمت دسته؛ نمودار هر کالا با آیکون نمودار در جدول باز می‌شود</div></div></div>
      <div class="chart-with-rep">
        @php
          $good = array_values(array_filter($cat['rows'], function ($r) { return ! $r['_review'] && $r['_price']; }));
          $cur = $good ? intdiv(array_sum(array_column($good, '_price')), count($good)) : 0;
          $prevs = array_values(array_filter(array_column($good, '_prev')));
          $prev = $prevs ? intdiv(array_sum($prevs), count($prevs)) : $cur;
        @endphp
        <div>@include('rd.chart', ['id' => 'chart-cat-' . ($c['slug'] ?? $cat['id']), 'src' => '/rd/chart/category/' . $cat['id'], 'price' => $cur, 'prev' => $prev, 'key' => $slug, 'title' => 'نمودار میانگین قیمت ' . $t, 'sub' => 'میانگین ' . Rd::fa(count($good)) . ' نوع کالا · ریال / ' . $st['unit']])</div>
        <div>@include('rd.experts-box', ['slug' => $slug])</div>
      </div>
    </div>
  </section>
@include('rd.calculator', ['slug' => $slug, 'row' => null])
@endif

  <section class="section alt">
    <div class="container">
      @if($c)
      <div class="prose">
        <h2>{{ $t }} چیست و کجا به کار می‌آید</h2>
        @if(!empty($c['intro']))<p>{!! $c['intro'][0] !!}</p>@endif
      </div>
      <div class="guides">
        <details class="guide" open><summary>{{ $t }} — ادامه‌ی معرفی</summary><div class="prose">@foreach(array_slice($c['intro'] ?? [], 1) as $x)<p>{!! $x !!}</p>@endforeach</div></details>
        <details class="guide"><summary>{{ $c['pricing_title'] }}</summary><div class="prose">@foreach($c['pricing'] as $x)<p>{!! $x !!}</p>@endforeach
            <div class="callout"><div class="t">قیمت‌ها به ریال است</div>
              <p>همه‌ی قیمت‌های این جدول به ریال است. اگر با سایتی که تومانی کار می‌کند مقایسه می‌فرمایید، به واحد توجه بفرمایید؛ عدد ریالی ده برابر عدد تومانی است.</p></div></div></details>
        <details class="guide"><summary>{{ $c['choose_title'] }}</summary><div class="prose">@foreach($c['choose'] as $ch)<h3>{{ $ch[0] }}</h3><p>{!! $ch[1] !!}</p>@endforeach</div></details>
        <details class="guide"><summary>چهار اشتباه رایج در خرید {{ $t }}</summary><div class="prose"><ul class="bul">@foreach($c['mistakes'] as $m)<li>{!! $m !!}</li>@endforeach</ul></div></details>
      </div>
      @else
      <div class="prose">
        <h2>{{ $t }} چیست و کجا به کار می‌آید</h2>
        {!! $category->intro !!}
        {!! $category->body !!}
      </div>
      @endif
    </div>
  </section>

  @php $deep = Rd::c('deep.' . $slug, []); @endphp
  @if($deep)
  <section class="section" id="guide">
    <div class="container">
      <div class="section-head"><div><h2>راهنمای فنی {{ $t }}</h2>
        <div class="sub">نصب، انتخاب پوشش و محاسبه‌ی هزینه — بر پایه‌ی مشخصات همین کاتالوگ</div></div></div>
      <div class="deepgrid">@foreach($deep as $d)<div class="deep"><h2>{{ $d[0] }}</h2>@foreach($d[1] as $x)<p>{!! $x !!}</p>@endforeach</div>@endforeach</div>
    </div>
  </section>
  @endif

@if($cat)
  <section class="section">
    <div class="container">
      @include('rd.spec-table', ['slug' => $slug])
      <p class="tnote">این جدول مشخصات، داده‌ی خط تولید صنایع مفتولی طلوع سپاهان است. مقادیر وزن، مبنای محاسبه‌ی هزینه‌ی هر مترمربع یا هر برگ است و هنگام تحویل با باسکول قابل بررسی است.</p>
    </div>
  </section>
@endif

  @if($faq)
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>پرسش‌های پرتکرار درباره‌ی {{ $t }}</h2></div></div>
      <div class="faq">@foreach($faq as $q)<details><summary>{{ $q[0] }}</summary><div class="a">{{ $q[1] }}</div></details>@endforeach</div>
    </div>
  </section>
  @endif
@if($cat)
@include('rd.reviews', ['slug' => $slug])
@include('rd.mag-related', ['slug' => $slug])
@endif
@include('rd.callband')
@endsection
