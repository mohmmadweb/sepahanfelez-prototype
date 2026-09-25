{{-- /authors/{author}. Controller: AdminPanel\AuthorController@show → $user. --}}
@extends('rd.layout')
@section('title', $user->full_name . ' | ' . \App\Support\Brand::name())
@section('description', Rd::cut(strip_tags((string) $user->description)))
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['مجله', '/blog'], [$user->full_name, null]]])@endsection
@section('content')
  <section class="section">
    <div class="container narrow">
      <div class="author">
        <img src="{{ $user->avatar() }}" alt="{{ $user->full_name }}" width="120" height="120" loading="lazy">
        <div class="prose"><h1>{{ $user->full_name }}</h1>{!! $user->description !!}</div>
      </div>
    </div>
  </section>
  @php $arts = array_slice(\App\Support\Redesign::articles(), 0, 6); @endphp
  @if($arts)
  <section class="section alt"><div class="container">
    <div class="section-head"><div><h2>تازه‌ترین مقالات مجله</h2></div><a href="/blog">همه‌ی مقالات {{ Rd::icon('i-chev') }}</a></div>
    <div class="mag-grid mag-grid-3">@foreach($arts as $a)@include('rd.mag-card', ['a' => $a])@endforeach</div>
  </div></section>
  @endif
@include('rd.callband')
@endsection
