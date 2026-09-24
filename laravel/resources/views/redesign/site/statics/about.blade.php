{{-- /about (pages.build_about). Controller: Site\AboutController@index. --}}
@extends('rd.layout')
@php $R = \App\Support\Redesign::class; $F = Rd::c('factory'); $total = $R::totalSkus(); $ncats = $R::nCats(); $claim = Rd::c('basket_claim'); @endphp
@section('title', 'درباره کارخانه‌ی طلوع سپاهان')
@section('description', 'صنایع مفتولی طلوع سپاهان، ' . $claim . ': تولیدکننده‌ی توری و محصولات مفتولی در شهرک صنعتی منتظریه‌ی اصفهان با دفتر فروش در بازار آهن تهران.')
@section('canonical', '/about')
@section('nav', 'about')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['درباره کارخانه', null]]])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('AboutPage', '/about', 'درباره کارخانه‌ی طلوع سپاهان', ['mainEntity' => ['@id' => Rd::orgId()]]), Rd::breadcrumb([['خانه', '/'], ['درباره کارخانه', null]])) }}@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="prose prose-lead">
        <h1>کارخانه‌ی صنایع مفتولی طلوع سپاهان</h1>
        <p class="lede"><strong>سپاهان فلز</strong> فروشگاه اینترنتی <strong>{{ $F['name'] }}</strong> است —
           <strong>{{ $claim }}</strong>، با {{ Rd::fa($total) }} نوع کالا در {{ Rd::fa($ncats) }} دسته، تولید کارخانه‌ی اصفهان و امکان خرید مستقیم از کارخانه و انبار تهران.</p>
      </div>
      @include('rd.plants')
      <div class="factnums">
        @foreach([['سال تأسیس', $F['year']], ['شماره ثبت', $F['reg']], ['ظرفیت سالانه', $F['capacity']], ['سالن تولید', $F['hall']], ['پرسنل تولید', $F['staff']], ['نوع کالای فعال', Rd::fa($total)]] as $f)
          <div class="fact"><span class="k">{{ $f[0] }}</span><span class="v">{{ $f[1] }}</span></div>
        @endforeach
      </div>
      @include('rd.factory-text')
      <div class="prose wide">
        <h2>چه چیزی تولید می‌شود</h2>
        <p>{{ Rd::fa($total) }} نوع کالای فعال در {{ Rd::fa($ncats) }} دسته. قیمت روز همه‌شان در <a href="/price">قیمت لحظه‌ای</a> هست و هر روز ساعت {{ Rd::updateTime() }} بروزرسانی می‌شود.</p>
        <ul class="bul cols-3">@foreach($R::catalog() as $slug => $cat)<li><a href="{{ Rd::path(Rd::uCat($slug)) }}">{{ Rd::cat($slug)['title'] ?? $cat['title'] }}</a> — {{ Rd::fa(count($cat['rows'])) }} نوع کالا، {{ $cat['unit'] }}</li>@endforeach</ul>
      </div>
      <div class="prose wide cols-2">
        @foreach(Rd::c('about_sections', []) as $sec)<h2>{{ $sec[0] }}</h2>@foreach($sec[1] as $x)<p>{!! $x !!}</p>@endforeach @endforeach
      </div>
    </div>
  </section>
@include('rd.sales-unit')
@include('rd.callband')
@endsection
