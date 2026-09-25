{{-- /category/. Controller: Site\CategoryController@list. Cards: admin → دسته‌بندی (title, image/icon, intro, order). --}}
@extends('rd.layout')
@php $R = \App\Support\Redesign::class; $total = $R::totalSkus(); @endphp
@section('title', 'دسته‌های محصول | ' . \App\Support\Brand::name())
@section('description', 'همه‌ی دسته‌های محصول ' . \App\Support\Site::companyName() . ' با بازه‌ی قیمت روز و واحد فروش.')
@section('canonical', '/category')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['دسته‌های محصول', null]]])@endsection
@section('jsonld')
@php
    $li = []; $i = 0;
    foreach ($R::catalog() as $slug => $cat) { $li[] = ['@type' => 'ListItem', 'position' => ++$i, 'name' => $cat['title'], 'url' => Rd::site(Rd::uCat($slug))]; }
@endphp
{{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', '/category', 'دسته‌های محصول'), ['@type' => 'ItemList', 'name' => 'دسته‌های محصول', 'numberOfItems' => count($li), 'itemListElement' => $li], Rd::breadcrumb([['خانه', '/'], ['دسته‌های محصول', null]])) }}
@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h1>دسته‌های محصول</h1>
          <div class="sub">{{ Rd::fa($R::nCats()) }} دسته · {{ Rd::fa($total) }} نوع کالا</div></div>
        <a href="/price">قیمت لحظه‌ای همه‌ی کالاها {{ Rd::icon('i-chev') }}</a>
      </div>
      <div class="homecats">
      @foreach($R::catalog() as $slug => $cat)
        @php $st = $R::stats($slug); $img = $R::cover($slug); @endphp
        <a class="hc" href="{{ Rd::path(Rd::uCat($slug)) }}">
          <span class="hc-img">@if($img)<img src="{{ $R::thumb($img) }}" alt="{{ $cat['title'] }}" loading="lazy">@endif</span>
          <span class="hc-t">{{ $cat['title'] }}</span>
          <span class="hc-n">{{ Rd::fa($st['n']) }} نوع کالا · واحد: {{ $st['unit'] }}</span>
          @if($st['min'])<span class="hc-pr">از <b class="num">{{ Rd::fmt($st['min']) }}</b> تا <b class="num">{{ Rd::fmt($st['max']) }}</b> ریال</span>@endif
          <span class="hc-go">مشاهده قیمت {{ Rd::icon('i-chev') }}</span>
        </a>
      @endforeach
      </div>
    </div>
  </section>
@include('rd.callband')
@endsection
