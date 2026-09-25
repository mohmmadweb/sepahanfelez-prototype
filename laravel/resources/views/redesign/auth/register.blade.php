{{-- GET /register — Auth\RegisterController@registerForm. Posts to /submit-data. --}}
@extends('auth.layout', ['crumb' => 'ثبت‌نام', 'heading' => 'ثبت‌نام', 'lede' => 'شماره موبایل خود را وارد کنید. کد تأیید پیامک می‌شود و حساب شما با همان شماره ساخته می‌شود.', 'foot' => ['پیش‌تر ثبت‌نام کرده‌اید؟', 'وارد شوید', '/login']])
@section('title', 'ثبت‌نام | ' . \App\Support\Brand::name())
@section('description', 'ساخت حساب کاربری با شماره موبایل.')
@section('form')
  @if($errors->any())
    <div class="alert alert-err" role="alert" id="mobile-error">{{ Rd::icon('i-flat') }}<span>{{ $errors->first() }}</span></div>
  @endif
  <form method="post" action="{{ route('register.submit.phone') }}">
    @csrf
    <label class="ffield"><span>شماره موبایل</span>
      <input id="mobile" name="mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹"
             value="{{ old('mobile') }}" required autofocus @if($errors->any()) aria-invalid="true" aria-describedby="mobile-error" @endif></label>
    <div class="ffield">
      <label class="fcheck"><input type="checkbox" name="business_account" value="1" @if(old('business_account')) checked @endif> حساب حقوقی (شرکت) می‌خواهم</label>
      <span class="hint">برای حساب حقوقی، بارگذاری مدارک شرکت لازم است و حساب پس از تأیید فعال می‌شود.</span>
    </div>
    <button class="btn btn-lg btn-block" type="submit">ارسال کد تأیید</button>
  </form>
@endsection
