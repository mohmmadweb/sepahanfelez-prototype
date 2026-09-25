{{--
    The cart is not part of the redesign; GET /cart normally answers 301 → /price
    (config redesign.redirect_cart). This page only renders if that redirect is
    switched off.
--}}
@extends('rd.layout')
@section('title', 'ثبت سفارش | ' . \App\Support\Brand::name())
@section('robots', 'noindex, follow')
@section('content')
  <section class="section"><div class="container errpage">
    <h1>ثبت سفارش تلفنی است</h1>
    <p>قیمت جدول مبنای روز است و قیمت قطعی با تناژ و مقصد بار در تماس اعلام می‌شود. کارشناس فروش سفارش شما را همان‌جا ثبت می‌کند.</p>
    <div class="factions"><a class="btn btn-call btn-lg2" href="tel:{{ Rd::phone() }}">{{ Rd::icon('i-phone') }} <span class="num">{{ Rd::phoneShow() }}</span></a><a class="btn btn-lg" href="/price">قیمت لحظه‌ای</a></div>
  </div></section>
@endsection
