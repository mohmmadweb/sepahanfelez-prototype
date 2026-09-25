{{--
    Home. Controller: Site\HomeController@index ($slides, $homeSetting, …).
    Every block reads the panel:
      slides                admin → اسلایدر          (image, link; «alt» is shown as the caption)
      <title>, description  admin → تنظیمات صفحه اصلی (home_title, home_description, home_canonical)
      intro text + picture  admin → تنظیمات صفحه اصلی (about, about_pic, alt_about_pic, url_about_pic)
      two banners           admin → تنظیمات صفحه اصلی (footer_pic1/2 with alt and url)
      category cards        admin → دسته‌بندی (title, image/icon, order) + product counts
      factory videos        admin → ویدئوها
      magazine              admin → مقالات
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class; $S = \App\Support\Site::class;
    $hs = $homeSetting ?? \App\Models\HomeSetting::query()->first();
    $total = $R::totalSkus(); $ncats = $R::nCats();
    $slides = $S::slides();
    $arts = array_slice($R::articles(), 0, 4);
    $videos = $S::videos();
    $h1 = 'قیمت روز محصولات ' . $S::companyName();
@endphp
@section('title', optional($hs)->home_title ?: \App\Support\Brand::name())
@section('description', optional($hs)->home_description ?: '')
@section('canonical', optional($hs)->home_canonical ?: '/')
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), ['@type' => 'WebPage', '@id' => Rd::site('/#page'), 'url' => Rd::site('/'), 'name' => optional($hs)->home_title ?: \App\Support\Brand::name(), 'inLanguage' => 'fa-IR', 'isPartOf' => ['@id' => Rd::siteId()], 'about' => ['@id' => Rd::orgId()]]) }}@endsection
@section('content')
@if($slides)
<section class="hero" aria-label="معرفی محصولات" aria-roledescription="اسلایدر">
  <div class="hero-track">
  @foreach($slides as $i => $sl)
    @php $n = $i + 1; @endphp
    <article class="slide{{ $sl['alt'] ? ' has-title' : '' }}" id="s{{ $n }}" style="background-image:url('{{ $sl['image'] }}')" aria-roledescription="اسلاید" aria-label="{{ $sl['alt'] ?: 'اسلاید ' . $n }}">
      <a class="slide-link" href="{{ $sl['link'] }}" aria-label="{{ $sl['alt'] ?: 'مشاهده' }}"></a>
      @if($sl['alt'])<div class="container"><div class="slide-in"><p class="stitle">{{ $sl['alt'] }}</p></div></div>@endif
    </article>
  @endforeach
  </div>
  @if(count($slides) > 1)
  <nav class="hero-dots" aria-label="انتخاب اسلاید">@foreach($slides as $i => $sl)<a href="#s{{ $i + 1 }}"><span class="vh">اسلاید {{ $i + 1 }}</span></a>@endforeach</nav>
  @endif
</section>
@endif

  <section class="section home-cats" aria-labelledby="hc-h">
    <div class="container">
      <div class="section-head">
        <div><h1 id="hc-h">{{ $h1 }}</h1>
          <div class="sub">{{ Rd::fa($total) }} نوع کالا در {{ Rd::fa($ncats) }} دسته @if($at = $R::lastUpdate())· آخرین بروزرسانی {{ Rd::jDate($at) }}@endif</div></div>
        <a class="btn btn-call btn-cta" href="/price">{{ Rd::icon('i-chart') }} مشاهده قیمت لحظه‌ای</a>
      </div>
      <div class="homecats">
      @foreach($R::catalog() as $slug => $cat)
        @php $st = $R::stats($slug); $img = $R::cover($slug); @endphp
        <a class="hc" href="{{ Rd::path(Rd::uCat($slug)) }}">
          <span class="hc-img">@if($img)<img src="{{ $R::thumb($img) }}" alt="{{ $cat['title'] }}" loading="lazy" decoding="async">@endif</span>
          <span class="hc-t">{{ $cat['title'] }}</span>
          <span class="hc-n">{{ Rd::fa($st['n']) }} نوع کالا · واحد: {{ $st['unit'] }}</span>
          <span class="hc-go">مشاهده قیمت {{ Rd::icon('i-chev') }}</span>
        </a>
      @endforeach
      </div>
    </div>
  </section>

  @php $about = Rd::cleanHtml(optional($hs)->about); $pic = $hs && $hs->about_pic ? $hs->about_pic() : null; @endphp
  @if($about)
  <section class="section">
    <div class="container home-about{{ $pic ? ' has-pic' : '' }}">
      <div class="prose wide">{!! $about !!}</div>
      @if($pic)
        <figure class="home-about-pic">@if($hs->url_about_pic)<a href="{{ $hs->url_about_pic }}">@endif<img src="{{ $pic }}" alt="{{ $hs->alt_about_pic }}" loading="lazy">@if($hs->url_about_pic)</a>@endif</figure>
      @endif
    </div>
  </section>
  @endif

  @php
    $banners = [];
    foreach ([1, 2] as $k) {
        $f = optional($hs)->{'footer_pic' . $k};
        if ($f) { $banners[] = ['img' => $hs->{'footer_pic' . $k}(), 'alt' => $hs->{'alt_footer_pic' . $k}, 'url' => $hs->{'url_footer_pic' . $k}]; }
    }
  @endphp
  @if($banners)
  <section class="section tight">
    <div class="container home-banners">
      @foreach($banners as $b)
        @if($b['url'])<a class="home-banner" href="{{ $b['url'] }}"><img src="{{ $b['img'] }}" alt="{{ $b['alt'] }}" loading="lazy"></a>
        @else<span class="home-banner"><img src="{{ $b['img'] }}" alt="{{ $b['alt'] }}" loading="lazy"></span>@endif
      @endforeach
    </div>
  </section>
  @endif

  @if($videos)
  <section class="section alt" id="factory" aria-labelledby="fa-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="fa-h">کارخانه و دفتر فروش</h2></div>
        <a href="/about">درباره ما {{ Rd::icon('i-chev') }}</a>
      </div>
      <div class="plants plants-{{ min(3, count($videos)) }}">
        @foreach(array_slice($videos, 0, 3) as $v)
          <figure class="plant"><video controls preload="metadata" playsinline muted src="{{ $v }}#t=1"></video></figure>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  @if($arts)
  <section class="section {{ $videos ? '' : 'alt' }} mag-home" aria-labelledby="mag-h">
    <div class="container">
      <div class="section-head">
        <div><h2 id="mag-h">مجله {{ \App\Support\Brand::name() }}</h2>
          <div class="sub">{{ Rd::fa(count($R::articles())) }} مقاله</div></div>
        <a href="/blog">مشاهده همه‌ی مطالب {{ Rd::icon('i-chev') }}</a>
      </div>
      <div class="mag-home-grid">@include('rd.mag-card', ['a' => $arts[0], 'size' => 'lg'])<div class="mag-home-side">@foreach(array_slice($arts, 1) as $a)@include('rd.mag-card', ['a' => $a, 'size' => 'sm'])@endforeach</div></div>
    </div>
  </section>
  @endif
@include('rd.callband')
@endsection
