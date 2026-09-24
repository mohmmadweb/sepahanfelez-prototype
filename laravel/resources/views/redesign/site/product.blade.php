{{--
    Product page (pages.build_product).
    Controller: Site\ProductController@show → $product, $category, $spec_values.
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $A = \App\Support\RedesignAnalysis::class;
    $slug = $category->slug;
    $cat = $R::category($slug);
    $c = Rd::cat($slug);
    $t = $c['title'] ?? $category->title;
    $row = null;
    foreach ($cat['rows'] ?? [] as $r) { if ($r['_id'] === (int) $product->id) { $row = $r; } }
    if (! $row) {
        // Inactive product, or one outside a leaf category: build its row on the spot.
        $row = ['نام محصول' => $product->title, 'واحد' => $product->unit, '_id' => (int) $product->id, '_slug' => $product->slug,
                '_price' => (int) $product->price, '_prev' => 0, '_at' => null, '_image' => null, '_review' => null, '_kg' => null, '_m2' => null];
        $titles = $category->specs->pluck('title', 'id');
        foreach ($spec_values as $sv) {
            if (isset($titles[$sv->spec_id])) { $row[trim($titles[$sv->spec_id])] = trim((string) $sv->title); }
        }
    }
    $rows = $cat['rows'] ?? [$row];
    $specs = $cat['specs'] ?? $category->specs->pluck('title')->map('trim')->all();
    $name = Rd::cleanName($row['نام محصول']);
    $p = $row['_price']; $d = $row['_prev']; $unit = $row['واحد'];
    $photos = $R::photos($slug);
    if (! $photos && $row['_image']) { $photos = [$row['_image']]; }
    $url = Rd::uProd($slug, $row['_slug']);
    $faq = array_slice($c['faq'] ?? [], 0, 3);
    $notes = $A::buyingNotes($slug, $row);
    $sib = $A::siblingNotes($slug, $row, $rows, $specs);
    $tips = array_merge(array_slice(array_slice($notes, 0, 3), 0, -1), array_slice($sib, 0, 1));
    $desc = 'قیمت روز ' . $name . ': ' . Rd::fmt($p) . ' ریال / ' . $unit . ' — تولید طلوع سپاهان، با مشخصات فنی و بروزرسانی روزانه.';
@endphp
@section('title', 'قیمت ' . $name)
@section('description', $desc)
@section('robots', \App\Support\Brand::robots($product->index_by_crawler ?? true, $row['_slug']))
@section('canonical', $url)
@section('nav', $slug)
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['قیمت لحظه‌ای', '/price'], [$t, Rd::uCat($slug)], [$name, null]]])@endsection
@section('jsonld')
@php
    $props = [];
    foreach ($specs as $k2) { $props[] = [$k2, Rd::cleanVal($row[$k2] ?? '')]; }
@endphp
{{ Rd::graph(Rd::organization(), Rd::website(), Rd::product($name, $url, $name . ' از تولیدات صنایع مفتولی طلوع سپاهان. قیمت روز ' . Rd::fmt($p) . ' ریال بر ' . $unit . '، درب کارخانه‌ی اصفهان، بروزرسانی هر روز ساعت ' . Rd::updateTime() . '.', $p, $unit, $photos[0] ?? null, $props), Rd::faq($faq), Rd::breadcrumb([['خانه', '/'], ['قیمت لحظه‌ای', '/price'], [$t, Rd::uCat($slug)], [$name, null]])) }}
@endsection
@section('content')
  <section class="section product">
    <div class="container">
      <div class="pgrid">
        @if($photos)
        <div class="gallery" data-lb>
          <div class="gallery-main"><img id="gmain" src="{{ $photos[0] }}" alt="{{ $t }} — {{ $name }}" loading="eager"><span class="gzoom" aria-hidden="true">{{ Rd::icon('i-search') }}</span></div>
          <div class="gallery-thumbs">@foreach(array_slice($photos, 0, 8) as $i => $src)<button type="button" class="thumb{{ $i === 0 ? ' is-on' : '' }}" data-src="{{ $src }}" aria-label="تصویر {{ $i + 1 }}"><img src="{{ $R::thumb($src) }}" alt="" loading="lazy" width="420" height="315"></button>@endforeach</div>
          @foreach($photos as $i => $src)<a class="lb-src" data-lb-item data-full="{{ $src }}" href="{{ $src }}" aria-label="{{ $name }} — تصویر {{ $i + 1 }}"><img src="{{ $R::thumb($src) }}" alt="" loading="lazy"></a>@endforeach
          <p class="gallery-note">تصاویر نمونه‌ی محصولات این دسته از خط تولید طلوع سپاهان</p>
        </div>
        @endif
        <div class="pinfo">
          <a class="chip" href="{{ Rd::path(Rd::uCat($slug)) }}">{{ $t }}</a>
          <h1>{{ $name }}</h1>
          <p class="plede">{{ $t }} — تولید صنایع مفتولی طلوع سپاهان، امکان خرید مستقیم از کارخانه و انبار تهران.</p>
          <div class="pcard">
            <div class="pcard-price"><span class="k">قیمت روز</span>
              <span class="v"><b class="num">{{ Rd::fmt($p) }}</b> <span class="u">ریال / {{ $unit }}</span></span>
              <span class="pcard-meta">{{ Rd::delta($d, $p) }} نسبت به آخرین ثبت @if($d)<span class="pcard-prev">قیمت ثبت قبلی: <span class="num">{{ Rd::fmt($d) }}</span> ریال</span>@endif</span></div>
            <div class="pcard-upd">@include('rd.stamp', ['short' => true, 'at' => $row['_at']]) <span class="dim">· ساعت {{ Rd::updateTime() }} هر روز</span></div>
            @if($row['_review'])<p class="callout slim"><b>قیمت در حال بازبینی است.</b> {{ $row['_review'] }} پیش از سفارش، قیمت را تلفنی بگیرید.</p>@endif
            <div class="pcard-cta">
              <a class="btn btn-call btn-lg2" href="tel:{{ Rd::phone() }}" data-track="call-product">{{ Rd::icon('i-phone') }} استعلام و ثبت سفارش <span class="num">{{ Rd::phoneShow() }}</span></a>
              <a class="btn btn-ghost btn-lg2" href="https://wa.me/{{ Rd::wa() }}" data-track="wa-product">{{ Rd::icon('i-whatsapp') }} واتساپ</a>
            </div>
            <p class="pcard-note">قیمت جدول مبنای روز است؛ قیمت قطعی با تناژ و مقصد بار تلفنی اعلام می‌شود.</p>
          </div>
          <div class="kvgrid">
            <div class="kv"><span class="k">دسته</span><span class="v"><a href="{{ Rd::path(Rd::uCat($slug)) }}">{{ $t }}</a></span></div>
            <div class="kv"><span class="k">واحد فروش</span><span class="v">{{ $unit }}</span></div>
            @foreach($specs as $x)@if($x !== 'محل بارگیری' && trim((string) ($row[$x] ?? '')) !== '')<div class="kv"><span class="k">{{ $x }}</span><span class="v">{{ Rd::cleanVal($row[$x]) }}</span></div>@endif @endforeach
            <div class="kv"><span class="k">تحویل</span><span class="v">کارخانه‌ی اصفهان / انبار تهران</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="prose wide cols-2">
        <h2>درباره‌ی {{ $name }}</h2>
        {!! $A::productIntro($slug, $row, $rows, $specs, $t) !!}
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container two-col">
      <div>@include('rd.chart', ['id' => 'chart-product', 'src' => '/rd/chart/product/' . $row['_id'], 'price' => $p, 'prev' => $d, 'key' => $row['نام محصول'], 'title' => 'نمودار قیمت ' . $name, 'sub' => 'ریال / ' . $unit . ' · مبنای روز درب کارخانه'])</div>
      <div>@include('rd.experts-box', ['slug' => $slug, 'title' => 'کارشناس فروش این محصول'])</div>
    </div>
  </section>

  @php $pb = $A::priceBlock($slug, $row, $rows); @endphp
  @if($pb)
  <section class="section alt">
    <div class="container">
      {!! $pb !!}
    </div>
  </section>
  @endif
@if($cat)
@include('rd.calculator', ['slug' => $slug, 'row' => $row])
@endif

  <section class="section">
    <div class="container two-col">
      <div>
        <div class="section-head"><div><h2>نکات خرید این محصول</h2><div class="sub">از مشخصات همین کالا</div></div></div>
        <ul class="tips">@foreach($tips as $tip)<li>{!! $tip !!}</li>@endforeach</ul>
        <p><a class="guidelink" href="{{ Rd::path(Rd::uCat($slug)) }}">راهنمای کامل خرید {{ $t }} {{ Rd::icon('i-chev') }}</a></p>
      </div>
      @if($cat && count($rows) > 1)
      <div>
        <div class="section-head"><div><h2>محصولات هم‌دسته</h2><div class="sub">{{ Rd::fa(count($rows) - 1) }} نوع کالای دیگر در {{ $t }}</div></div></div>
        @include('rd.sibling-table', ['slug' => $slug, 'row' => $row])
        <p><a class="guidelink" href="{{ Rd::path(Rd::uCat($slug)) }}">جدول کامل {{ $t }} {{ Rd::icon('i-chev') }}</a></p>
      </div>
      @endif
    </div>
  </section>

  @if($faq)
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>پرسش‌های پرتکرار</h2></div></div>
      <div class="faq">@foreach($faq as $q)<details><summary>{{ $q[0] }}</summary><div class="a">{{ $q[1] }}</div></details>@endforeach</div>
    </div>
  </section>
  @endif
@if($cat)
@include('rd.reviews', ['slug' => $slug, 'subject' => $name])
@include('rd.mag-related', ['slug' => $slug, 'title' => 'مقالات مرتبط با ' . $t])
@endif
@include('rd.callband')
@endsection
