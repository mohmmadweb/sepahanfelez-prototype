{{-- GET /verify-phone — Auth\LoginController@VerifyForm. --}}
@extends('auth.layout', ['key' => 'verify', 'crumb' => 'تأیید کد'])
@section('title', 'تأیید کد ورود | سپاهان فلز')
@section('form')
  @include('auth.partials.code-form', ['action' => route('login.verify'), 'resendUrl' => route('login.resend-code')])
@endsection
