{{-- GET /user/guide — UserPanel\TutorialController@index → $tutorial. --}}
@extends('user.layout.master', ['panel' => 'guide'])
@section('title', 'راهنمای حساب | ' . \App\Support\Brand::name())
@section('crumb', 'راهنما')
@section('head')<div><h1>{{ optional($tutorial)->title ?: 'راهنمای حساب کاربری' }}</h1></div>@endsection
@section('panel')
  {{-- Only a video file on this site: the panel cannot clear the field (its
       validation demands a URL), and it shipped holding a film trailer from w3.org. --}}
  @php $guideVideo = $tutorial ? Rd::local($tutorial->video) : ''; @endphp
  <div class="pcard2 prose wide">
    @if(strpos($guideVideo, '/') === 0)<video controls preload="none" src="{{ $guideVideo }}" style="width:100%;border-radius:var(--radius)"></video>@endif
    @if($tutorial && $tutorial->body){!! $tutorial->body !!}@else
      <p>از این حساب برای ثبت مشخصات حقوقی و دریافت فاکتور رسمی، نگه‌داری نشانی‌های تحویل و ثبت درخواست کتبی (تیکت) استفاده کنید.</p>
      <p>شماره‌ی دفتر فروش: <a class="num" href="tel:{{ Rd::phone() }}">{{ Rd::phoneShow() }}</a></p>
    @endif
  </div>
@endsection
