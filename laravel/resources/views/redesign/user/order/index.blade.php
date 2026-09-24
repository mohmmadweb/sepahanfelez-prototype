{{-- GET /user/orders — UserPanel\OrderController@index → $orders. Read-only history; new orders are placed by phone. --}}
@extends('user.layout.master', ['panel' => 'orders'])
@section('title', 'سفارش‌های پیشین | سپاهان فلز')
@section('crumb', 'سفارش‌ها')
@section('head')<div><h1>سفارش‌های پیشین</h1><div class="sub">{{ Rd::c('account.order_note') }}</div></div><a class="btn btn-call btn-lg2" href="tel:{{ Rd::phone() }}">{{ Rd::icon('i-phone') }} ثبت سفارش تلفنی</a>@endsection
@section('panel')
  @if(count($orders))
  <div class="pcard2"><div class="pt-wrap"><table class="dtable">
    <thead><tr><th>شماره</th><th>تاریخ</th><th>مبلغ (ریال)</th><th>وضعیت</th><th>پرداخت</th><th><span class="vh">جزئیات</span></th></tr></thead>
    <tbody>
    @foreach($orders as $o)
      @php $cls = ['rejected' => 'badge', 'pending' => 'badge-warn', 'pend_for_pay' => 'badge-warn', 'processing' => 'badge-info', 'sent' => 'badge-ok'][$o->status] ?? ''; @endphp
      <tr>
        <td data-label="شماره" class="num">{{ $o->id }}</td>
        <td data-label="تاریخ">{{ Rd::jDate($o->created_at) }}</td>
        <td data-label="مبلغ (ریال)" class="num">{{ Rd::fmt($o->total_price) }}</td>
        <td data-label="وضعیت"><span class="badge {{ $cls }}">{{ $o->orderStatus[$o->status] ?? $o->status }}</span></td>
        <td data-label="پرداخت"><span class="badge {{ $o->paid_at ? 'badge-ok' : 'badge-err' }}">{{ $o->paymentStatus() }}</span></td>
        <td class="act"><a href="{{ route('order.show', $o->id) }}">جزئیات</a></td>
      </tr>
    @endforeach
    </tbody></table></div></div>
  @else
    <div class="empty">{{ Rd::icon('i-box') }}<p>سفارش ثبت‌شده‌ای در حساب شما نیست. سفارش و قیمت قطعی تلفنی ثبت می‌شود.</p>
      <a class="btn btn-lg" href="/price">قیمت لحظه‌ای</a></div>
  @endif
@endsection
