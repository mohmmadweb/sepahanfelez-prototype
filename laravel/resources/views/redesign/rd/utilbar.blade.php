{{-- Hours and e-mail: admin → اطلاعات تماس (work_time, email) --}}
@php $hours = \App\Support\Site::hours(); $email = \App\Support\Site::email(); @endphp
<div class="utilbar">
  <div class="container">
    <ul class="util-left">
      @if($hours)<li>{{ Rd::icon('i-clock') }} {{ $hours }}</li>@endif
      @if($email)<li><a href="mailto:{{ $email }}">{{ Rd::icon('i-mail') }} {{ $email }}</a></li>@endif
    </ul>
    <ul>
      @auth
        <li class="acct">
          <a href="{{ route('profile.edit') }}">{{ Rd::icon('i-user') }} {{ auth()->user()->full_name ?: 'حساب کاربری' }}</a>
          <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="linkbtn">خروج</button></form>
        </li>
      @else
        <li><a href="{{ route('login') }}">{{ Rd::icon('i-user') }} ورود / ثبت‌نام</a></li>
      @endauth
    </ul>
  </div>
</div>
