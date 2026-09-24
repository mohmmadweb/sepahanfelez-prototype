{{-- Shared address form. $action, $method, $address (nullable), $provinces, $cities --}}
@php
    $cur = old('location_id', optional($address)->location_id);
    $curProv = old('province');
    if (! $curProv && $cur) { foreach ($cities as $ct) { if ((int) $ct->id === (int) $cur) { $curProv = $ct->province_id; } } }
@endphp
<form class="pcard2" method="post" action="{{ $action }}">
  @csrf @if($method !== 'POST') @method($method) @endif
  @if($errors->any())
    <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<ul>@foreach($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
  @endif
  <div class="fgrid2">
    <label class="ffield full"><span>عنوان نشانی</span><input name="title" type="text" maxlength="30" required placeholder="مثلاً: کارگاه شهرک صنعتی" value="{{ old('title', optional($address)->title) }}"></label>
    <label class="ffield"><span>استان</span><select name="province" data-province required><option value="" disabled @if(! $curProv) selected @endif>انتخاب استان</option>@foreach($provinces as $pv)<option value="{{ $pv->id }}"@if((int) $curProv === (int) $pv->id) selected @endif>{{ $pv->region }}</option>@endforeach</select></label>
    <label class="ffield"><span>شهر</span><select name="location_id" data-city required><option value="" disabled @if(! $cur) selected @endif>انتخاب شهر</option>@foreach($cities as $ct)<option value="{{ $ct->id }}" data-p="{{ $ct->province_id }}"@if((int) $cur === (int) $ct->id) selected @endif>{{ $ct->region }}</option>@endforeach</select></label>
    <label class="ffield full"><span>نشانی کامل</span><textarea name="address" rows="3" maxlength="250" required>{{ old('address', optional($address)->address) }}</textarea></label>
    <label class="ffield"><span>کد پستی</span><input name="postal_code" type="text" inputmode="numeric" minlength="10" maxlength="10" required value="{{ old('postal_code', optional($address)->postal_code) }}"></label>
    <div class="ffield"><span>&nbsp;</span><label class="fcheck"><input type="checkbox" name="is_default_address" value="1" @if(old('is_default_address', optional($address)->is_default_address)) checked @endif> نشانی پیش‌فرض باشد</label></div>
  </div>
  <div class="factions"><button class="btn btn-lg" type="submit">ذخیره‌ی نشانی</button><a class="btn btn-ghost btn-lg2" href="{{ route('address.index') }}">انصراف</a></div>
</form>
<script>
(function () {
  var p = document.querySelector('[data-province]'), c = document.querySelector('[data-city]');
  if (!p || !c) return;
  function filter() {
    var v = p.value;
    Array.prototype.forEach.call(c.options, function (o) {
      if (!o.value) return;
      var show = o.getAttribute('data-p') === v;
      o.hidden = !show; o.disabled = !show;
      if (!show && o.selected) { o.selected = false; c.value = ''; }
    });
  }
  p.addEventListener('change', filter); filter();
})();
</script>
