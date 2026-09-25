{{-- GET /user/address — UserPanel\AddressController@index → $addresses. --}}
@extends('user.layout.master', ['panel' => 'address'])
@section('title', 'نشانی‌های تحویل | ' . \App\Support\Brand::name())
@section('crumb', 'نشانی‌ها')
@section('head')<div><h1>نشانی‌های تحویل</h1><div class="sub">کارگاه‌ها و انبارهایی که بار به آن‌ها ارسال می‌شود</div></div><a class="btn btn-lg" href="{{ route('address.create') }}">نشانی تازه</a>@endsection
@section('panel')
  @forelse($addresses as $ad)
    <div class="pcard2">
      <div class="panel-head"><div><h2 style="margin:0">{{ $ad->title ?: 'نشانی' }} @if($ad->is_default_address)<span class="badge badge-ok">پیش‌فرض</span>@endif</h2></div>
        <div class="factions" style="margin:0">
          <a class="btn btn-ghost btn-sm" href="{{ route('address.edit', $ad->id) }}">ویرایش</a>
          <form method="post" action="{{ route('address.destroy', $ad->id) }}" onsubmit="return confirm('این نشانی حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" type="submit">حذف</button></form>
        </div></div>
      <p style="margin-top:var(--s3)">{{ optional(optional($ad->location)->province)->region }} — {{ optional($ad->location)->region }} — {{ $ad->address }}</p>
      @if($ad->postal_code)<p class="dim">کد پستی: <span class="num">{{ $ad->postal_code }}</span></p>@endif
    </div>
  @empty
    <div class="empty">{{ Rd::icon('i-map') }}<p>هنوز نشانی‌ای ثبت نکرده‌اید.</p><a class="btn btn-lg" href="{{ route('address.create') }}">ثبت نخستین نشانی</a></div>
  @endforelse
@endsection
