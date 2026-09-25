{{--
    /contact. Controller: Site\ContactController@index / store → $information, $socials.
    admin → اطلاعات تماس  : phone, fax, email, work_time, addresses
    admin → شبکه‌های اجتماعی: the list of channels
    Messages land in admin → تماس با ما.
--}}
@extends('rd.layout')
@php
    $S = \App\Support\Site::class; $showAddr = false;
    $hours = $S::hours(); $email = $S::email(); $fax = $S::fax(); $addresses = $S::addresses(); $socials = $S::socials();
@endphp
@section('title', 'تماس با ما | ' . \App\Support\Brand::name())
@section('description', 'تماس با دفتر فروش ' . $S::companyName() . ': ' . $S::phoneShow() . ($hours ? '، ' . $hours : '') . '.')
@section('canonical', '/contact')
@section('nav', 'contact')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['تماس با ما', null]]])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('ContactPage', '/contact', 'تماس با ما', ['mainEntity' => ['@id' => Rd::orgId()]]), Rd::breadcrumb([['خانه', '/'], ['تماس با ما', null]])) }}@endsection
@section('content')
  <section class="section">
    <div class="container">
      <div class="prose prose-lead">
        <h1>تماس با دفتر فروش</h1>
        <p class="lede">قیمت جدول مبنای روز است. <strong>قیمت قطعی خود را از کارشناسان ما بگیرید</strong>؛
           به تناژ و مقصد بار بستگی دارد و در همان تماس اعلام می‌شود.</p>
      </div>
      <div class="contact-grid">
        <div class="contact-main">
          <a class="contact-tel" href="tel:{{ Rd::phone() }}" data-track="call-contact">
            {{ Rd::icon('i-phone') }}
            <span><span class="l">دفتر فروش</span><span class="n num">{{ Rd::phoneShow() }}</span>
              @if($hours)<span class="h">{{ $hours }}</span>@endif</span>
          </a>
          @if($socials)<div class="soc-list">@foreach($socials as $s)<a class="soc-row" href="{{ $s['url'] }}" rel="noopener" target="_blank">{{ Rd::icon($s['icon']) }}<span>{{ $s['title'] }}</span></a>@endforeach</div>@endif
          @if($email)<a class="fmail" href="mailto:{{ $email }}">{{ Rd::icon('i-mail') }}{{ $email }}</a>@endif
          @if($fax)<p class="dim">فکس: <span class="num">{{ $fax }}</span></p>@endif
        </div>
        @if($addresses)
        <div class="contact-addr">
          <h2>نشانی‌ها</h2>
          <ul class="faddr">@foreach($addresses as $ad)<li>@if($ad[0])<span class="a-t">{{ $ad[0] }}</span>@endif{{ $ad[1] }}</li>@endforeach</ul>
        </div>
        @endif
      </div>
      <div class="prose" id="message">
        <h2>پیام بفرستید</h2>
        <p>مشخصات و مقدار موردنیاز خود را بنویسید؛ در نخستین فرصت اداری با شما تماس گرفته می‌شود.</p>
      </div>
      <form class="cform" method="post" action="{{ route('contact.store') }}#message">
        @csrf
        @if($errors->any())
          <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<ul>@foreach($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
        @endif
        <div class="cform-grid">
          @foreach([['first_name', 'نام', 'text', 'minlength=2 maxlength=25'], ['last_name', 'نام خانوادگی', 'text', 'minlength=2 maxlength=25'], ['phone', 'شماره تماس', 'tel', 'inputmode=numeric maxlength=15'], ['email', 'ایمیل', 'email', 'maxlength=60']] as $f)
            <label class="ffield"><span>{{ $f[1] }}</span><input name="{{ $f[0] }}" type="{{ $f[2] }}" {!! $f[3] !!} required value="{{ old($f[0]) }}" @if($errors->has($f[0])) aria-invalid="true" @endif></label>
          @endforeach
          <label class="ffield fsearch"><span>موضوع</span><input name="subject" type="text" minlength="2" maxlength="150" required value="{{ old('subject') }}" @if($errors->has('subject')) aria-invalid="true" @endif></label>
          <label class="ffield fsearch"><span>متن پیام</span><textarea name="body" rows="4" minlength="10" maxlength="500" required @if($errors->has('body')) aria-invalid="true" @endif>{{ old('body') }}</textarea></label>
        </div>
        <button class="btn btn-lg" type="submit">ارسال پیام</button>
      </form>
    </div>
  </section>
@include('rd.callband')
@endsection
