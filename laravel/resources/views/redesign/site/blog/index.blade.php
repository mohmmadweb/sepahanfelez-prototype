{{-- /blog (blog.build_blog_index). Controller: Site\BlogController@index — its variables are not needed. --}}
@extends('rd.layout')
@php
    $R = \App\Support\Redesign::class;
    $arts = $R::articles();
    $cats = $R::blogCats();
@endphp
@section('title', 'مجله ' . \App\Support\Brand::name())
@section('description', 'مقالات و راهنماهای مجله‌ی ' . \App\Support\Brand::name() . '.')
@section('canonical', '/blog')
@section('nav', 'blog')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['مجله', null]]])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('CollectionPage', '/blog', 'مجله ' . \App\Support\Brand::name()), Rd::breadcrumb([['خانه', '/'], ['مجله', null]])) }}@endsection
@section('content')
  <section class="section mag-top">
    <div class="container">
      <div class="mag-head">
        <div><h1>مجله {{ \App\Support\Brand::name() }}</h1>
          <p class="lede">{{ Rd::fa(count($arts)) }} مقاله در {{ Rd::fa(count($cats)) }} دسته</p></div>
        @include('site.blog.chips', ['current' => null, 'cats' => $cats])
      </div>
      @if($arts)
      <div class="mag-hero">@include('rd.mag-card', ['a' => $arts[0], 'size' => 'lg'])<div class="mag-hero-side">@foreach(array_slice($arts, 1, 3) as $a)@include('rd.mag-card', ['a' => $a, 'size' => 'sm'])@endforeach</div></div>
      @endif
    </div>
  </section>
  @if(count($arts) > 4)
  <section class="section alt">
    <div class="container">
      <div class="section-head"><div><h2>تازه‌ترین مطالب</h2><div class="sub">{{ Rd::fa(count($arts)) }} مقاله در {{ Rd::fa(count($cats)) }} دسته</div></div></div>
      <div class="mag-grid">@foreach(array_slice($arts, 4) as $a)@include('rd.mag-card', ['a' => $a])@endforeach</div>
    </div>
  </section>
  @endif
@include('rd.callband')
@endsection
