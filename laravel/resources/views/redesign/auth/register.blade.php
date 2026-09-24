{{-- GET /register — Auth\RegisterController@registerForm. Posts to /submit-data. --}}
@extends('auth.layout', ['key' => 'register', 'crumb' => 'ثبت‌نام'])
@section('title', 'ثبت‌نام | سپاهان فلز')
@section('description', 'ساخت حساب کاربری در سپاهان فلز با شماره موبایل.')
@section('form')
  @php $T = Rd::c('account.register'); @endphp
  @if($errors->any())
    <div class="alert alert-err" role="alert" id="mobile-error">{{ Rd::icon('i-flat') }}<span>{{ $errors->first() }}</span></div>
  @endif
  <form method="post" action="{{ route('register.submit.phone') }}">
    @csrf
    <label class="ffield"><span>شماره موبایل</span>
      <input id="mobile" name="mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹"
             value="{{ old('mobile') }}" required autofocus @if($errors->any()) aria-invalid="true" aria-describedby="mobile-error" @endif></label>
    <div class="ffield">
      <label class="fcheck"><input type="checkbox" name="business_account" value="1" @if(old('business_account')) checked @endif> {{ $T['business'] }}</label>
      <span class="hint">{{ $T['business_hint'] }}</span>
    </div>
    <button class="btn btn-lg btn-block" type="submit">{{ $T['button'] }}</button>
  </form>
@endsection
