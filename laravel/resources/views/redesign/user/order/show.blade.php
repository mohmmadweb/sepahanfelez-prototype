{{-- GET /user/orders/{order} — UserPanel\OrderController@show → $order. --}}
@extends('user.layout.master', ['panel' => 'orders'])
@section('title', 'سفارش ' . $order->id . ' | ' . \App\Support\Brand::name())
@section('crumb', 'سفارش ' . $order->id)
@section('head')<div><h1>سفارش شماره‌ی <span class="num">{{ $order->id }}</span></h1><div class="sub">ثبت‌شده در {{ Rd::jDate($order->created_at) }}</div></div><a class="btn btn-ghost btn-lg2" href="{{ route('order.index') }}">بازگشت به سفارش‌ها</a>@endsection
@section('panel')
  <div class="pcard2">
    <h2>اقلام سفارش</h2>
    <div class="pt-wrap"><table class="dtable">
      <thead><tr><th>ردیف</th><th>کالا</th><th>قیمت واحد (ریال)</th><th>مقدار</th><th>جمع (ریال)</th></tr></thead>
      <tbody>
      @foreach($order->products as $i => $item)
        <tr><td data-label="ردیف">{{ $i + 1 }}</td><td data-label="کالا">{{ optional($item->product)->title }}</td>
          <td data-label="قیمت واحد" class="num">{{ Rd::fmt($item->price) }}</td>
          <td data-label="مقدار">{{ $item->quantity }} {{ optional($item->product)->unit }}</td>
          <td data-label="جمع" class="num">{{ Rd::fmt($item->price * $item->quantity) }}</td></tr>
      @endforeach
      </tbody></table></div>
  </div>
  <div class="pcard2">
    <h2>خلاصه‌ی مبلغ</h2>
    <div class="kvlist">
      <div><span class="k">وضعیت</span><span class="v">{{ $order->orderStatus[$order->status] ?? $order->status }}</span></div>
      <div><span class="k">پرداخت</span><span class="v">{{ $order->paymentStatus() }}</span></div>
      @if($order->send_price)<div><span class="k">هزینه‌ی ارسال</span><span class="v num">{{ Rd::fmt($order->send_price) }} ریال</span></div>@endif
      @foreach(['discount_code' => 'تخفیف کد', 'business_discount' => 'تخفیف حساب حقوقی', 'min_expect_discount' => 'تخفیف حجمی', 'admin_discount' => 'تخفیف کارشناس'] as $k => $l)
        @if($order->$k)<div><span class="k">{{ $l }}</span><span class="v num">{{ Rd::fmt($order->$k) }} ریال</span></div>@endif
      @endforeach
      <div class="total"><span class="k">مبلغ کل</span><span class="v num">{{ Rd::fmt($order->total_price) }} ریال</span></div>
    </div>
    @if($order->address)<p class="dim" style="margin-top:var(--s4)">نشانی تحویل: {{ $order->address->address }}</p>@endif
  </div>
@endsection
