{{-- GET /verify-phone — Auth\LoginController@VerifyForm. --}}
@extends('auth.layout', ['crumb' => 'تأیید کد', 'heading' => 'کد تأیید را وارد کنید', 'lede' => 'یک پیامک حاوی کد تأیید شش‌رقمی برای شما ارسال شد. کد تا دو دقیقه معتبر است.', 'foot' => ['شماره را اشتباه وارد کرده‌اید؟', 'بازگشت', '/login']])
@section('title', 'تأیید کد ورود | ' . \App\Support\Brand::name())
@section('form')
  @include('auth.partials.code-form', ['action' => route('login.verify'), 'resendUrl' => route('login.resend-code')])
@endsection
