{{--
    Shell of the four SMS-login screens (Auth\\LoginController / RegisterController),
    inside the normal site chrome. A screen sets $heading, $lede, $foot and
    @section('form').
--}}
@extends('rd.layout')
@section('robots', 'noindex, nofollow')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], [$crumb ?? $heading, null]]])@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="auth-wrap auth-solo">
        <div class="auth-card">
          <h1>{{ $heading }}</h1>
          <p class="lede">{{ $lede }}</p>
          @yield('form')
          <p class="auth-foot">{{ $foot[0] }} <a href="{{ $foot[2] }}">{{ $foot[1] }}</a></p>
        </div>
      </div>
    </div>
  </section>
@endsection
