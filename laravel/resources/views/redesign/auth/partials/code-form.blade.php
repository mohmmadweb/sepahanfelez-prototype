{{--
    The code step, shared by login and register. $action, $resendUrl.
    The resend call and its two-minute countdown are the same vanilla script
    the previous template had; only the markup changed.
--}}
@if($errors->any())
  <div class="alert alert-err" role="alert" id="code-error">{{ Rd::icon('i-flat') }}<span>{{ $errors->first() }}</span></div>
@endif
<form method="post" action="{{ $action }}">
  @csrf
  <label class="ffield code"><span>کد تأیید شش‌رقمی</span>
    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9۰-۹]*" maxlength="6" autocomplete="one-time-code"
           required autofocus aria-describedby="code-countdown"></label>
  <button class="btn btn-lg btn-block" type="submit">{{ Rd::c('account.verify.button') }}</button>
</form>
<div class="auth-resend">
  <span id="code-countdown" aria-live="polite">ارسال دوباره‌ی کد تا <b class="num" id="countdown-value">۰۲:۰۰</b> دیگر</span>
  <button type="button" id="resend-button" hidden>ارسال دوباره‌ی کد</button>
  <span class="alert alert-err" id="resend-error" role="alert" hidden></span>
</div>
<script>
(function () {
  var SECONDS = 120, FA = '۰۱۲۳۴۵۶۷۸۹';
  var label = document.getElementById('code-countdown'), value = document.getElementById('countdown-value');
  var button = document.getElementById('resend-button'), errorBox = document.getElementById('resend-error');
  var input = document.getElementById('code'), timer = null;
  function fa(n) { return String(n).replace(/[0-9]/g, function (d) { return FA[+d]; }); }
  function render(r) { var m = Math.floor(r / 60), s = r % 60; value.textContent = fa((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s); }
  function start(sec) {
    var r = sec; label.hidden = false; button.hidden = true; render(r); clearInterval(timer);
    timer = setInterval(function () { r -= 1; if (r <= 0) { clearInterval(timer); label.hidden = true; button.hidden = false; return; } render(r); }, 1000);
  }
  // A code typed on a Persian keyboard arrives in Persian digits; the server wants Latin.
  if (input) input.addEventListener('input', function () {
    input.value = input.value.replace(/[۰-۹]/g, function (c) { return FA.indexOf(c); }).replace(/[^0-9]/g, '').slice(0, 6);
  });
  button.addEventListener('click', function () {
    button.disabled = true; errorBox.hidden = true;
    fetch({!! json_encode($resendUrl) !!}, { method: 'POST', headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
    .then(function (res) {
      if (!res.ok) return res.json().catch(function () { return {}; }).then(function (b) {
        throw new Error(b.error || 'ارسال دوباره‌ی کد ممکن نشد؛ کمی بعد دوباره تلاش کنید.'); });
      start(SECONDS);
    }).catch(function (e) { errorBox.textContent = e.message; errorBox.hidden = false; button.hidden = false; })
    .finally(function () { button.disabled = false; });
  });
  start(SECONDS);
})();
</script>
