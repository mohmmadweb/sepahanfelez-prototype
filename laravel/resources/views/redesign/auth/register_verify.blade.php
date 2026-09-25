{{-- GET /verify-register — Auth\RegisterController@VerifyForm. --}}
@extends('auth.layout', ['crumb' => 'تأیید کد', 'heading' => 'کد تأیید را وارد کنید', 'lede' => 'یک پیامک حاوی کد تأیید شش‌رقمی برای شما ارسال شد. کد تا دو دقیقه معتبر است.', 'foot' => ['شماره را اشتباه وارد کرده‌اید؟', 'بازگشت', '/register']])
@section('title', 'تأیید کد ثبت‌نام | ' . \App\Support\Brand::name())
@section('form')
  @include('auth.partials.code-form', ['action' => route('register.verify'), 'resendUrl' => route('register.resend-code')])
@endsection
