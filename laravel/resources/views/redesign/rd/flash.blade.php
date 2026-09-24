{{--
    Messages the controllers flash. They call alert()->success(...) from
    uxweb/sweet-alert, which stores sweet_alert.{title,text,type} in the
    session; the old layout rendered them with the SweetAlert library. Here
    they are a plain dismissible notice, no library.
--}}
@php
    $flashText = session('sweet_alert.text') ?: session('sweet_alert.title');
    $flashType = session('sweet_alert.type');
@endphp
@if($flashText)
  <div class="toast alert {{ in_array($flashType, ['error', 'warning'], true) ? 'alert-err' : 'alert-ok' }}" role="status" data-toast>
    {{ Rd::icon('i-check') }}<span>{{ $flashText }}</span>
    <button type="button" class="x" aria-label="بستن" onclick="this.parentNode.remove()">×</button>
  </div>
  <script>setTimeout(function(){var t=document.querySelector('[data-toast]');if(t)t.remove();},8000);</script>
@endif
