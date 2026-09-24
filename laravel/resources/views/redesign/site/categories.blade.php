{{-- /category/ (pages.build_catlist). Controller: Site\CategoryController@list. --}}
@extends('rd.layout')
@php $R = \App\Support\Redesign::class; $total = $R::totalSkus(); @endphp
@section('title', 'همه‌ی دسته‌های محصول')
@section('description', 'فهرست کامل دسته‌های صنایع مفتولی طلوع سپاهان با بازه‌ی قیمت روز، واحد فروش و راهنمای انتخاب دسته بر پایه‌ی کاربرد، وزن و چشمه.')
@section('canonical', '/category')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['دسته‌های محصول', null]]])@endsection
@section('jsonld')
@php
    $li = []; $i = 0;
    foreach ($R::catalog() as $slug => $cat) { $li[] = ['@type' => 'ListItem', 'position' => ++$i, 'name' => Rd::cat($slug)['title'] ?? $cat['title'], 'url' => Rd::site(Rd::uCat($slug))]; }
@endphp
{{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', '/category', 'همه‌ی دسته‌های محصول'), ['@type' => 'ItemList', 'name' => 'دسته‌های صنایع مفتولی طلوع سپاهان', 'numberOfItems' => count($li), 'itemListElement' => $li], Rd::breadcrumb([['خانه', '/'], ['دسته‌های محصول', null]])) }}
@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h1>دسته‌های محصول</h1>
          <div class="sub">{{ Rd::fa($R::nCats()) }} دسته‌ی فعال — {{ Rd::c('basket_claim') }}</div></div>
        <a href="/price">قیمت لحظه‌ای {{ Rd::fa($total) }} نوع کالا {{ Rd::icon('i-chev') }}</a>
      </div>
      <div class="homecats">
      @foreach($R::catalog() as $slug => $cat)
        @php $st = $R::stats($slug); $ph = $R::photos($slug); $t = Rd::cat($slug)['title'] ?? $cat['title']; @endphp
        <a class="hc" href="{{ Rd::path(Rd::uCat($slug)) }}">
          <span class="hc-img">@if($ph)<img src="{{ $R::thumb($ph[0]) }}" alt="{{ $t }}" loading="lazy">@endif</span>
          <span class="hc-t">{{ $t }}</span>
          <span class="hc-n">{{ Rd::fa($st['n']) }} نوع کالا · واحد: {{ $st['unit'] }}</span>
          <span class="hc-pr">از <b class="num">{{ Rd::fmt($st['min']) }}</b> تا <b class="num">{{ Rd::fmt($st['max']) }}</b> ریال</span>
          <span class="hc-go">مشاهده قیمت {{ Rd::icon('i-chev') }}</span>
        </a>
      @endforeach
      </div>
    </div>
  </section>

  <section class="section alt">
    <div class="container">
      <div class="prose wide cols-2">
        <h2>کدام دسته برای کار شما درست است</h2>
        @foreach(Rd::c('catlist_intro', []) as $x)<p>{!! $x !!}</p>@endforeach
      </div>
      <div class="guides">
        @foreach(Rd::c('catlist_help', []) as $h)<details class="guide"><summary>{{ $h[0] }}</summary><div class="prose"><p>{!! $h[1] !!}</p></div></details>@endforeach
      </div>
    </div>
  </section>
@include('rd.callband')
@endsection
