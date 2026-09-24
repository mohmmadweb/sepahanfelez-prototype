<div class="utilbar">
  <div class="container">
    <ul class="util-left">
      <li>{{ Rd::icon('i-clock') }} {{ Rd::c('hours') }}</li>
      <li><a href="mailto:{{ Rd::c('email') }}">{{ Rd::icon('i-mail') }} {{ Rd::c('email') }}</a></li>
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
