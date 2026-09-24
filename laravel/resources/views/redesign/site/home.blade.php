{{-- Home (pages.build_index). Controller: Site\HomeController@index — its variables are not needed. --}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $total = $R::totalSkus(); $ncats = $R::nCats(); $claim = Rd::c('basket_claim');
    $slides = Rd::c('slides', []);
    $arts = array_slice($R::articles(), 0, 4);
@endphp
@section('title', 'سپاهان فلز — قیمت روز صنایع مفتولی طلوع سپاهان')
@section('description', 'قیمت روز توری حصاری، پرسی، مش جوشی، مرغی، گابیون و سیم خاردار مستقیم از کارخانه. ' . Rd::fa($total) . ' نوع کالا، بروزرسانی هر روز ساعت ' . Rd::updateTime() . '.')
@section('canonical', '/')
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), ['@type' => 'WebPage', '@id' => Rd::site('/#page'), 'url' => Rd::site('/'), 'name' => 'سپاهان فلز — قیمت روز صنایع مفتولی طلوع سپاهان', 'inLanguage' => 'fa-IR', 'isPartOf' => ['@id' => Rd::siteId()], 'about' => ['@id' => Rd::orgId()]]) }}@endsection
@section('content')
<section class="hero" aria-label="معرفی محصولات" aria-roledescription="اسلایدر">
  <div class="hero-track">
  @foreach($slides as $i => $sl)
    @php $n = $i + 1; $href = $R::category($sl['cat']) ? Rd::path(Rd::uCat($sl['cat'])) : '/price'; @endphp
    <article class="slide{{ $sl['title'] ? ' has-title' : '' }}" id="s{{ $n }}" @if($sl['style']) style="{!! $sl['style'] !!}" @endif aria-roledescription="اسلاید" aria-label="{{ $sl['title'] ?: ($sl['alt'] ?: 'اسلاید ' . $n) }}">
      @if($sl['title'])
        <div class="container"><div class="slide-in">@if($n === 1)<h1>{{ $sl['title'] }}</h1>@else<p class="stitle">{{ $sl['title'] }}</p>@endif</div></div>
      @else
        <a class="slide-link" href="{{ $href }}" aria-label="{{ $sl['alt'] ?: 'مشاهده قیمت‌ها' }}"></a>
      @endif
    </article>
  @endforeach
  </div>
  @if(count($slides) > 1)
  <nav class="hero-dots" aria-label="انتخاب اسلاید">@foreach($slides as $i => $sl)<a href="#s{{ $i + 1 }}"><span class="vh">اسلاید {{ $i + 1 }}</span></a>@endforeach</nav>
  @endif
</section>

  <section class="section home-cats" aria-labelledby="hc-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="hc-h">قیمت روز صنایع مفتولی طلوع سپاهان</h2>
          <div class="sub">{{ $claim }} — {{ Rd::fa($total) }} نوع کالا در {{ Rd::fa($ncats) }} دسته · بروزرسانی هر روز ساعت {{ Rd::updateTime() }}</div></div>
        <a class="btn btn-call btn-cta" href="/price">{{ Rd::icon('i-chart') }} مشاهده قیمت لحظه‌ای</a>
      </div>
      <div class="homecats">
      @foreach($R::catalog() as $slug => $cat)
        @php $st = $R::stats($slug); $ph = $R::photos($slug); $t = Rd::cat($slug)['title'] ?? $cat['title']; @endphp
        <a class="hc" href="{{ Rd::path(Rd::uCat($slug)) }}">
          <span class="hc-img">@if($ph)<img src="{{ $R::thumb($ph[0]) }}" alt="{{ $t }}" loading="lazy" decoding="async">@endif</span>
          <span class="hc-t">{{ $t }}</span>
          <span class="hc-n">{{ Rd::fa($st['n']) }} نوع کالا · واحد: {{ $st['unit'] }}</span>
          <span class="hc-go">مشاهده قیمت {{ Rd::icon('i-chev') }}</span>
        </a>
      @endforeach
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="prose wide cols-2">
        @foreach(Rd::c('home_intro', []) as $x)<p>{!! $x !!}</p>@endforeach
      </div>
    </div>
  </section>

  <section class="section alt" aria-labelledby="tr-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="tr-h">چرا خرید از طلوع سپاهان فرق دارد</h2>
          <div class="sub">{{ $claim }}، مستقیم از کارخانه</div></div>
      </div>
      <div class="trustgrid">@foreach(Rd::c('trust', []) as $t)<div class="trustitem">{{ Rd::icon($t[0]) }}<div><div class="t">{{ $t[1] }}</div><div class="d">{{ $t[2] }}</div></div></div>@endforeach</div>
      <div class="factnums">@foreach(Rd::c('fact_numbers', []) as $f)<div class="factnum"><div class="v">{{ $f[0] }}</div><div class="k">{{ $f[1] }}</div></div>@endforeach</div>
    </div>
  </section>

  <section class="section" id="factory" aria-labelledby="fa-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="fa-h">کارخانه‌ی صنایع مفتولی طلوع سپاهان</h2>
          <div class="sub">دو واحد تولیدی در شهرک صنعتی منتظریه‌ی اصفهان و دفتر فروش در بازار آهن تهران</div></div>
        <a href="/about">درباره کارخانه {{ Rd::icon('i-chev') }}</a>
      </div>
      @include('rd.plants')
      @include('rd.factory-text')
    </div>
  </section>

  @if($arts)
  <section class="section alt mag-home" aria-labelledby="mag-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="mag-h">مجله سپاهان فلز</h2>
          <div class="sub">راهنمای خرید، مقایسه‌ی محصولات و نکات فنی صنایع مفتولی — {{ Rd::fa(count($R::articles())) }} مقاله</div></div>
        <a href="/blog">مشاهده همه‌ی مطالب {{ Rd::icon('i-chev') }}</a>
      </div>
      <div class="mag-home-grid">@include('rd.mag-card', ['a' => $arts[0], 'size' => 'lg'])<div class="mag-home-side">@foreach(array_slice($arts, 1) as $a)@include('rd.mag-card', ['a' => $a, 'size' => 'sm'])@endforeach</div></div>
    </div>
  </section>
  @endif
@include('rd.callband')
@endsection
