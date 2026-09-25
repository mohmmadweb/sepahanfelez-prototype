{{-- GET /user/profile — UserPanel\ProfileController@edit → $user. PATCH to the same URL. --}}
@extends('user.layout.master', ['panel' => 'profile'])
@section('title', 'مشخصات حساب | ' . \App\Support\Brand::name())
@section('crumb', 'مشخصات حساب')
@section('head')<div><h1>مشخصات حساب</h1><div class="sub">شماره‌ی موبایل شناسه‌ی ورود شماست و تغییر نمی‌کند.</div></div>@endsection
@section('panel')
  <form class="pcard2" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf @method('PATCH')
    @if($errors->any())
      <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<ul>@foreach($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
    @endif
    <h2>اطلاعات شخصی</h2>
    <div class="fgrid2">
      <label class="ffield"><span>نام و نام خانوادگی</span><input name="full_name" type="text" maxlength="30" required value="{{ old('full_name', $user->full_name) }}" @if($errors->has('full_name')) aria-invalid="true" @endif></label>
      <label class="ffield"><span>شماره موبایل</span><input type="tel" value="{{ $user->mobile }}" disabled></label>
      <label class="ffield"><span>کد ملی</span><input name="national_code" type="text" inputmode="numeric" value="{{ old('national_code', $user->national_code) }}"></label>
      <label class="ffield"><span>تلفن ثابت</span><input name="phone" type="tel" inputmode="numeric" maxlength="12" value="{{ old('phone', $user->phone) }}"></label>
      <label class="ffield full"><span>ایمیل</span><input name="email" type="email" value="{{ old('email', $user->email) }}"></label>
      <label class="ffield full"><span>تصویر پروفایل</span><input name="avatar" type="file" accept="image/png,image/jpeg"><span class="hint">JPG یا PNG، حداکثر ۲ مگابایت</span></label>
    </div>
    @if($user->type === 'business')
      <h2 style="margin-top:var(--s6)">اطلاعات حقوقی</h2>
      <div class="fgrid2">
        <label class="ffield"><span>نام شرکت</span><input name="company" type="text" value="{{ old('company', $user->company) }}"></label>
        <label class="ffield"><span>شناسه‌ی ملی شرکت</span><input name="national_id" type="text" inputmode="numeric" value="{{ old('national_id', $user->national_id) }}"></label>
        <label class="ffield"><span>کد اقتصادی</span><input name="economic_code" type="text" inputmode="numeric" value="{{ old('economic_code', $user->economic_code) }}"></label>
        <label class="ffield"><span>مدارک شرکت</span>
          @if($user->doc_file)<span class="hint">مدارک بارگذاری شده است.</span>@endif
          <input name="doc_file" type="file" accept="image/png,image/jpeg,application/pdf" @if(! $user->doc_file) required @endif><span class="hint">تصویر یا PDF آگهی تأسیس / آخرین تغییرات</span></label>
      </div>
    @endif
    <div class="factions"><button class="btn btn-lg" type="submit">ذخیره‌ی تغییرات</button></div>
  </form>
@endsection
