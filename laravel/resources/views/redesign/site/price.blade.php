{{--
    /price. Controller: Site\PriceListController@index ($homeSetting, …).
    <title>/description/canonical: admin → تنظیمات صفحه اصلی (price_title, price_description, price_canonical).
    Tables: every active product of every category (admin → دسته / محصول / قیمت).
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $total = $R::totalSkus(); $ncats = $R::nCats();
@endphp
@section('title', optional($homeSetting)->price_title ?: 'قیمت لحظه‌ای — ' . \App\Support\Brand::name())
@section('description', optional($homeSetting)->price_description ?: '')
@section('canonical', optional($homeSetting)->price_canonical ?: '/price')
@section('nav', 'price')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['قیمت لحظه‌ای', null]]])@endsection
@section('jsonld')
@php
    $items = [];
    foreach ($R::catalog() as $slug => $cat) {
        foreach ($cat['rows'] as $r) {
            if (! $r['_review'] && $r['_slug'] && count($items) < 60) {
                $nm = Rd::cleanName($r['نام محصول']);
                $items[] = Rd::product($nm, Rd::uProd($slug, $r['_slug']), $nm . ' — قیمت روز.', $r['_price'], $r['واحد']);
            }
        }
    }
@endphp
{{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', '/price', optional($homeSetting)->price_title ?: 'قیمت لحظه‌ای', ['about' => ['@id' => Rd::orgId()]]), Rd::itemList($items, 'قیمت لحظه‌ای'), Rd::breadcrumb([['خانه', '/'], ['قیمت لحظه‌ای', null]])) }}
@endsection
@section('content')
  <section class="board board-price">
    <div class="container">
      <div class="board-head">
        <div>
          <h1>قیمت لحظه‌ای<span>{{ \App\Support\Site::companyName() }}</span></h1>
          <p class="lede">{{ Rd::fa($total) }} نوع کالا در {{ Rd::fa($ncats) }} دسته با مشخصات فنی. قیمت‌ها به ریال و مبنای روز است.</p>
        </div>
        @include('rd.stamp')
      </div>
      <div class="price-tools">
        <label class="gsearch">{{ Rd::icon('i-search') }}<span class="vh">جست‌وجو در همه‌ی جدول‌ها</span>
          <input type="search" data-global-search placeholder="جست‌وجوی نام کالا در همه‌ی جدول‌ها — مثلاً: چشمه ۵/۵ یا مفتول ۳" autocomplete="off">
          <output data-global-count aria-live="polite"></output></label>
        <div class="chiprow">@foreach($R::catalog() as $slug => $cat)<a class="chip" href="#pt-c{{ $cat['id'] }}">{{ $cat['title'] }}</a>@endforeach</div>
      </div>
    </div>
  </section>

  <section class="section price-section">
    <div class="container price-full">
        @include('rd.changes-table', ['n' => 10])
        @foreach($R::catalog() as $slug => $cat)
          @include('rd.price-table', ['slug' => $slug, 'search' => false])
        @endforeach
        <p class="tnote">ستون «نوسان» تغییر نسبت به آخرین قیمت ثبت‌شده است. آیکون نمودار در هر ردیف، روند قیمت همان کالا را باز می‌کند. قیمت قطعی سفارش به تناژ و مقصد بار بستگی دارد و در تماس اعلام می‌شود.</p>
    </div>
  </section>
@include('rd.callband')
@endsection
