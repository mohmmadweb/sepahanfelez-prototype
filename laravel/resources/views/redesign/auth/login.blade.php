{{-- GET /login — Auth\LoginController@loginForm. Posts to /submit-phone. --}}
@extends('auth.layout', ['crumb' => 'ورود', 'heading' => 'ورود به حساب کاربری', 'lede' => 'شماره موبایل خود را وارد کنید تا کد تأیید شش‌رقمی برایتان پیامک شود.', 'foot' => ['حساب کاربری ندارید؟', 'ثبت‌نام کنید', '/register']])
@section('title', 'ورود به حساب کاربری | ' . \App\Support\Brand::name())
@section('description', 'ورود به حساب کاربری با شماره موبایل و کد تأیید پیامکی.')
@section('form')
  @if($errors->any())
    <div class="alert alert-err" role="alert" id="mobile-error">{{ Rd::icon('i-flat') }}<span>{{ $errors->first() }}</span></div>
  @endif
  <form method="post" action="{{ route('login.submit.phone') }}">
    @csrf
    <label class="ffield"><span>شماره موبایل</span>
      <input id="mobile" name="mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹"
             value="{{ old('mobile') }}" required autofocus @if($errors->any()) aria-invalid="true" aria-describedby="mobile-error" @endif></label>
    <button class="btn btn-lg btn-block" type="submit">ارسال کد تأیید</button>
  </form>
@endsection
