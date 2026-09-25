{{--
    Category page. Controller: Site\CategoryController@index → $category (with
    features, usage, specs, tags), $products, $comments, $relatedArticles.

    Where each block comes from (admin → دسته‌بندی → ویرایش, unless noted):
      <title>, description,   meta_title, meta_description, meta_keywords,
      canonical, robots       canonical, index_by_crawler
      JSON-LD                 schema_tag when it holds a usable block, else generated
      H1 / subtitle           title / meta_description (the form has no intro field)
      numbers, table, chart   the category's products and prices
      table columns           admin → ستون‌های جدول (category_spec.sort)
      pictures                category image + product images (محصول → محتوا)
      guide / FAQ             body — each <h2> becomes a section; an <h2> about
                              «پرسش…» whose questions are <h3> becomes the FAQ
      features / usages       admin → ویژگی‌ها / کاربردها
      video                   video_embed or video
      reviews                 admin → دیدگاه‌های دسته‌بندی
      related articles        tags shared with articles (admin → برچسب‌ها)
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $slug = $category->slug;
    $cat = $R::category($slug);
    $t = $category->title;
    $st = $R::stats($slug);
    $photos = $R::photos($slug);
    [$bodyLead, $sections] = Rd::sections($category->body);
    $faq = [];
    foreach ($sections as $sec) { if ($sec['faq']) { foreach ($sec['faq'] as $qa) { $faq[] = [$qa[0], trim(strip_tags($qa[1]))]; } } }
    // The edit form has no «intro» field, so the subtitle is the meta
    // description the admin writes (admin → دسته → توضیح متا).
    $meta = trim((string) $category->meta_description);
    $intro = $meta !== '' ? '<p>' . e($meta) . '</p>' : '';
    $features = $category->features ?? collect();
    $usages = $category->usage ?? collect();
@endphp
@section('title', $category->meta_title ?: ('قیمت روز ' . $t . ' | ' . \App\Support\Brand::name()))
@section('description', $meta)
@section('robots', \App\Support\Brand::robots($category->index_by_crawler, $slug))
@section('canonical', \App\Support\Brand::canonical($category->canonical))
@section('nav', $slug)
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['قیمت لحظه‌ای', '/price'], [$t, null]]])@endsection
@section('jsonld')
@if($category->meta_keywords)<meta name="keywords" content="{{ $category->meta_keywords }}">@endif
{!! \App\Support\Schema::storedIsUsable($category->schema_tag) ? $category->schema_tag : '' !!}
@php
    $prods = [];
    foreach (array_slice($cat['rows'] ?? [], 0, 30) as $r) {
        $nm = Rd::cleanName($r['نام محصول']);
        $props = [];
        foreach (array_slice($cat['specs'], 0, 6) as $k2) { $props[] = [$k2, Rd::cleanVal($r[$k2] ?? '')]; }
        if (! $r['_slug']) { continue; }
        $prods[] = Rd::product($nm, Rd::uProd($slug, $r['_slug']), $nm . ' — ' . $t . '، قیمت روز.', $r['_price'], $r['واحد'], $r['_image'] ?: ($photos[0] ?? null), $props);
    }
@endphp
{{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', Rd::uCat($slug), 'قیمت روز ' . $t, ['description' => $meta, 'about' => ['@id' => Rd::orgId()], 'primaryImageOfPage' => $photos ? Rd::site($photos[0]) : null]), $prods ? Rd::itemList($prods, 'کالاهای ' . $t) : null, \App\Support\Schema::storedIsUsable($category->schema_tag) ? null : Rd::faq($faq), Rd::breadcrumb([['خانه', '/'], ['قیمت لحظه‌ای', '/price'], [$t, null]])) }}
@endsection
@section('content')
  <section class="board board-cat">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>قیمت روز {{ $t }}</h1>
          @if($intro)<div class="lede">{!! $intro !!}</div>@endif
        </div>
        @include('rd.stamp', ['at' => $cat['last_update'] ?? null])
      </div>
      @if($cat)
      <div class="board-grid">
        <div class="ixgrid ix-4">
          <div class="ix"><span class="k">نوع کالای فعال</span><span class="v"><span class="num">{{ $st['n'] }}</span></span><span class="range">در این دسته</span></div>
          <div class="ix"><span class="k">کمترین قیمت</span><span class="v"><span class="num">{{ Rd::fmt($st['min']) }}</span> <span class="u">ریال</span></span><span class="range">هر {{ $st['unit'] }}</span></div>
          <div class="ix"><span class="k">بیشترین قیمت</span><span class="v"><span class="num">{{ Rd::fmt($st['max']) }}</span> <span class="u">ریال</span></span><span class="range">هر {{ $st['unit'] }}</span></div>
          <div class="ix"><span class="k">واحد فروش</span><span class="v">{{ $st['unit'] }}</span><span class="range">قیمت هر {{ $st['unit'] }}</span></div>
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
          <div class="sub">{{ Rd::fa($st['n']) }} نوع کالا با مشخصات فنی — قیمت به ریال</div></div>
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
        <div>@include('rd.chart', ['id' => 'chart-cat-' . $cat['id'], 'src' => '/rd/chart/category/' . $cat['id'], 'price' => $cur, 'prev' => $prev, 'key' => $slug, 'title' => 'نمودار میانگین قیمت ' . $t, 'sub' => 'میانگین ' . Rd::fa(count($good)) . ' نوع کالا · ریال / ' . $st['unit']])</div>
        <div>@include('rd.unit-box')</div>
      </div>
    </div>
  </section>
@include('rd.calculator', ['slug' => $slug, 'row' => null])
@endif

@if($features->count())
  <section class="section">
    <div class="container">
      <div class="section-head"><div><h2>ویژگی‌های {{ $t }}</h2></div></div>
      <div class="cards-3">
        @foreach($features as $f)
          <div class="icard">@if($f->picture)<img src="{{ $f->picture() }}" alt="{{ $f->title }}" loading="lazy">@endif<div><h3>{{ $f->title }}</h3>@if($f->description)<p>{{ $f->description }}</p>@endif</div></div>
        @endforeach
      </div>
    </div>
  </section>
@endif

@if($bodyLead || $sections)
  <section class="section alt">
    <div class="container">
      @php $open = $sections && ! $sections[0]['faq'] ? array_shift($sections) : null; @endphp
      @if($bodyLead || $open)
      <div class="prose">
        @if($open)<h2>{{ $open['title'] }}</h2>@endif
        {!! $bodyLead !!}
        @if($open){!! $open['html'] !!}@endif
      </div>
      @endif
      @php $guides = array_values(array_filter($sections, function ($s) { return ! $s['faq']; })); @endphp
      @if($guides)
      <div class="guides">
        @foreach($guides as $g)<details class="guide"><summary>{{ $g['title'] }}</summary><div class="prose">{!! $g['html'] !!}</div></details>@endforeach
      </div>
      @endif
    </div>
  </section>
@endif

@if($usages->count())
  <section class="section">
    <div class="container">
      <div class="section-head"><div><h2>کاربردهای {{ $t }}</h2></div></div>
      <div class="cards-3">
        @foreach($usages as $u)
          <div class="icard">@if($u->picture)<img src="{{ $u->picture() }}" alt="{{ $u->title }}" loading="lazy">@endif<div><h3>{{ $u->title }}</h3>@if($u->description)<p>{{ $u->description }}</p>@endif</div></div>
        @endforeach
      </div>
    </div>
  </section>
@endif

@if($category->video_embed || $category->video)
  <section class="section">
    <div class="container narrow">
      <div class="section-head"><div><h2>ویدئوی {{ $t }}</h2></div></div>
      <div class="video-box">
        @if($category->video_embed){!! $category->video_embed !!}@else<video controls preload="none" src="{{ $category->video }}"></video>@endif
      </div>
    </div>
  </section>
@endif

@if($cat)
  <section class="section">
    <div class="container">
      @include('rd.spec-table', ['slug' => $slug])
    </div>
  </section>
@endif

  @if($faq)
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>پرسش‌های پرتکرار درباره‌ی {{ $t }}</h2></div></div>
      <div class="faq">@foreach($sections as $sec)@if($sec['faq'])@foreach($sec['faq'] as $q)<details><summary>{{ $q[0] }}</summary><div class="a">{!! $q[1] !!}</div></details>@endforeach @endif @endforeach</div>
    </div>
  </section>
  @endif
@if($cat)
@include('rd.reviews', ['slug' => $slug])
@include('rd.mag-related', ['slug' => $slug])
@endif
@include('rd.callband')
@endsection
