{{-- /contact (pages.build_contact) with the live form. Controller: Site\ContactController@index / store. --}}
@extends('rd.layout')
@php $showAddr = false; @endphp
@section('title', 'تماس با سپاهان فلز — دفتر فروش کارخانه')
@section('description', 'تماس با دفتر فروش صنایع مفتولی طلوع سپاهان: ' . Rd::phoneShow() . ' با ' . Rd::c('phone.lines') . '. نشانی دو واحد کارخانه در شهرک صنعتی منتظریه‌ی اصفهان و دفتر تهران در بازار آهن شادآباد.')
@section('canonical', '/contact')
@section('nav', 'contact')
@section('crumbs')@include('rd.crumb', ['items' => [['خانه', '/'], ['تماس با ما', null]]])@endsection
@section('jsonld'){{ Rd::graph(Rd::organization(), Rd::website(), Rd::page('ContactPage', '/contact', 'تماس با سپاهان فلز', ['mainEntity' => ['@id' => Rd::orgId()]]), Rd::breadcrumb([['خانه', '/'], ['تماس با ما', null]])) }}@endsection
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
            <span><span class="l">دفتر فروش — {{ Rd::c('phone.lines') }}</span><span class="n num">{{ Rd::phoneShow() }}</span>
              <span class="h">شنبه تا چهارشنبه ۸ تا ۱۷ · پنجشنبه ۸ تا ۱۳</span></span>
          </a>
          <div class="soc-list">@foreach(Rd::c('socials', []) as $s)<a class="soc-row" href="{{ $s[2] }}" rel="noopener" target="_blank">{{ Rd::icon($s[0]) }}<span>{{ $s[1] }}</span><b class="num">{{ Rd::c('phone.mobile_show') }}</b></a>@endforeach</div>
          <a class="fmail" href="mailto:{{ Rd::c('email') }}">{{ Rd::icon('i-mail') }}{{ Rd::c('email') }}</a>
        </div>
        <div class="contact-addr">
          <h2>کارخانه و دفتر فروش</h2>
          <ul class="faddr">@foreach(Rd::c('addresses', []) as $ad)<li><span class="a-t">{{ $ad[0] }}</span>{{ $ad[1] }}</li>@endforeach</ul>
          <a class="maplink" href="{{ Rd::c('tehran_map') }}" target="_blank" rel="noopener">
            <img src="/rd/factory/tehran-office.jpg" alt="نمای هوایی دفتر تهران در بازار آهن شادآباد" loading="lazy">
            <span>{{ Rd::icon('i-map') }} دفتر تهران روی نقشه‌ی گوگل {{ Rd::icon('i-external') }}</span></a>
        </div>
      </div>
      <div class="section-head"><div><h2>واحد فروش</h2><div class="sub">شماره‌ی دفتر را بگیرید و داخلی کارشناس مربوط به محصول خود را وارد کنید</div></div></div>
      <div class="unit-rep unit-rep-wide">@include('rd.rep-card', ['e' => \App\Support\Redesign::rep(null)])</div>
      <div class="prose wide cols-2">
        <h2>راهنمای تماس</h2>
        @foreach(Rd::c('contact_help', []) as $h)<h3>{{ $h[0] }}</h3><p>{{ $h[1] }}</p>@endforeach
      </div>
      <div class="prose" id="message">
        <h2>پیام بفرستید</h2>
        <p>در صورت مراجعه خارج از ساعات اداری، مشخصات و تناژ موردنیاز خود را ثبت بفرمایید؛ در نخستین فرصت اداری با شما تماس گرفته خواهد شد.</p>
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
