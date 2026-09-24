{{-- GET /user/guide — UserPanel\TutorialController@index → $tutorial. --}}
@extends('user.layout.master', ['panel' => 'guide'])
@section('title', 'راهنمای حساب | سپاهان فلز')
@section('crumb', 'راهنما')
@section('head')<div><h1>{{ optional($tutorial)->title ?: 'راهنمای حساب کاربری' }}</h1></div>@endsection
@section('panel')
  <div class="pcard2 prose wide">
    @if($tutorial && $tutorial->video)<video controls preload="none" src="{{ $tutorial->video }}" style="width:100%;border-radius:var(--radius)"></video>@endif
    @if($tutorial && $tutorial->body){!! $tutorial->body !!}@else
      <p>از این حساب برای ثبت مشخصات حقوقی و دریافت فاکتور رسمی، نگه‌داری نشانی‌های تحویل و ثبت درخواست کتبی (تیکت) استفاده کنید.</p>
      <p>{{ Rd::c('account.order_note') }} شماره‌ی دفتر فروش: <a class="num" href="tel:{{ Rd::phone() }}">{{ Rd::phoneShow() }}</a></p>
    @endif
  </div>
@endsection
