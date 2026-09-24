{{--
    Shell of the four SMS-login screens, inside the normal site chrome.
    A screen sets: $key (login|register|verify), @section('form'), and the
    usual title. The side panel says what an account is for — ordering is by
    phone, there is no cart.
--}}
@extends('rd.layout')
@php $A = Rd::c('account'); $T = $A[$key ?? 'login']; @endphp
@section('robots', 'noindex, nofollow')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], [$crumb ?? $T['title'], null]]])@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="auth-wrap">
        <div class="auth-card">
          <h1>{{ $T['title'] }}</h1>
          <p class="lede">{{ $T['lede'] }}</p>
          @yield('form')
          <p class="auth-foot">{{ $T['foot'][0] }} <a href="{{ $footHref ?? $T['foot'][2] }}">{{ $T['foot'][1] }}</a></p>
        </div>
        <aside class="auth-side">
          <h2>{{ $A['side_title'] }}</h2>
          <div class="trustgrid unit-promise">@foreach($A['benefits'] as $b)<div class="trustitem">{{ Rd::icon($b[0]) }}<div><div class="t">{{ $b[1] }}</div><div class="d">{{ $b[2] }}</div></div></div>@endforeach</div>
          <div class="callout"><div class="t">سفارش و قیمت قطعی تلفنی است</div>
            <p>{{ $A['order_note'] }} <a class="num" href="tel:{{ Rd::phone() }}">{{ Rd::phoneShow() }}</a></p></div>
        </aside>
      </div>
    </div>
  </section>
@endsection
