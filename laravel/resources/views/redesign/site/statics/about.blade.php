{{--
    /about. Controller: Site\AboutController@index → $data (abouts row), $admins.
    admin → درباره ما : text (the page), image, video, canonical.
    Below it, the product range — read from the categories, never typed.
--}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $text = Rd::cleanHtml($data->text ?? '');
    $img = \App\Support\Site::aboutImage();
    $video = \App\Support\Site::aboutVideo();
@endphp
@section('title', 'درباره ما | ' . \App\Support\Brand::name())
@section('description', Rd::cut(strip_tags($text)))
@section('canonical', \App\Support\Brand::canonical($data->canonical ?? null))
@section('nav', 'about')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['درباره ما', null]]])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('AboutPage', '/about', 'درباره ما', ['mainEntity' => ['@id' => Rd::orgId()]]), Rd::breadcrumb([['خانه', '/'], ['درباره ما', null]])) }}@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="prose prose-lead"><h1>درباره {{ \App\Support\Site::companyName() }}</h1></div>
      @if($video || $img)
      <div class="about-media">
        @if($video)<video controls preload="none" playsinline @if($img) poster="{{ $img }}" @endif src="{{ $video }}"></video>
        @elseif($img)<img src="{{ $img }}" alt="{{ \App\Support\Site::companyName() }}" loading="eager">@endif
      </div>
      @endif
      @if($text)<div class="prose wide cols-2">{!! $text !!}</div>@endif
      @if($R::catalog())
      <div class="prose wide">
        <h2>محصولات</h2>
        <ul class="bul cols-3">@foreach($R::catalog() as $slug => $cat)<li><a href="{{ Rd::path(Rd::uCat($slug)) }}">{{ $cat['title'] }}</a> — {{ Rd::fa(count($cat['rows'])) }} نوع کالا</li>@endforeach</ul>
      </div>
      @endif
    </div>
  </section>
@include('rd.callband')
@endsection
