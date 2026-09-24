{{--
    User panel shell, inside the site chrome. Every user.* view extends this
    and sets: $panel (nav key), @section('title'), @section('head'), @section('content').
--}}
@extends('rd.layout')
@php $u = auth()->user(); @endphp
@section('robots', 'noindex, nofollow')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['ناحیه‌ی کاربری', route('profile.edit')], [trim($__env->yieldContent('crumb')) ?: 'حساب', null]]])@endsection
@section('content')
  <section class="section">
    <div class="container panel">
      <nav class="panel-nav" aria-label="ناحیه‌ی کاربری">
        <div class="panel-who"><span class="av">@if($u && $u->hasAvatar())<img src="{{ $u->avatar() }}" alt="" loading="lazy">@else{{ Rd::icon('i-user') }}@endif</span>
          <div><b>{{ ($u->full_name ?? '') ?: 'کاربر سپاهان فلز' }}</b><span class="num">{{ $u->mobile ?? '' }}</span></div></div>
        @foreach(Rd::c('account.nav', []) as $n)
          <a href="{{ $n[1] }}"@if(($panel ?? '') === $n[0]) aria-current="page"@endif>{{ Rd::icon($n[2]) }}{{ $n[3] }}</a>
        @endforeach
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">{{ Rd::icon('i-arrow') }}خروج از حساب</button></form>
      </nav>
      <div class="panel-main">
        <div class="panel-head">@yield('head')</div>
        @if($u && $u->type === 'business' && $u->status === 'pending')
          <div class="alert alert-info">{{ Rd::icon('i-clock') }}<span>حساب حقوقی شما در انتظار تأیید کارشناس است. مدارک شرکت را در «مشخصات حساب» بارگذاری کنید.</span></div>
        @endif
        @yield('panel')
      </div>
    </div>
  </section>
@endsection
