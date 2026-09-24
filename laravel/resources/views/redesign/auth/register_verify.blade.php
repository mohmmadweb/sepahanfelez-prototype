{{-- GET /verify-register — Auth\RegisterController@VerifyForm. --}}
@extends('auth.layout', ['key' => 'verify', 'crumb' => 'تأیید کد', 'footHref' => '/register'])
@section('title', 'تأیید کد ثبت‌نام | سپاهان فلز')
@section('form')
  @include('auth.partials.code-form', ['action' => route('register.verify'), 'resendUrl' => route('register.resend-code')])
@endsection
