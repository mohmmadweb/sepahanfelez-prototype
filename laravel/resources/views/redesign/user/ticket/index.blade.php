{{-- GET /user/tickets — UserPanel\TicketController@index → $tickets. --}}
@extends('user.layout.master', ['panel' => 'tickets'])
@section('title', 'تیکت‌ها | سپاهان فلز')
@section('crumb', 'تیکت‌ها')
@section('head')<div><h1>تیکت‌ها و درخواست‌ها</h1><div class="sub">درخواست کتبی پیش‌فاکتور، نقشه یا پیگیری بار</div></div><a class="btn btn-lg" href="{{ route('ticket.create') }}">تیکت تازه</a>@endsection
@section('panel')
  <form class="tfilter slim" method="get" action="{{ route('ticket.index') }}" role="search">
    <div class="tfilter-row"><label class="ffield fsearch"><span>جست‌وجو در موضوع تیکت‌ها</span><input type="search" name="search" value="{{ request('search') }}"></label></div>
  </form>
  @if(count($tickets))
  <div class="pcard2"><div class="pt-wrap"><table class="dtable">
    <thead><tr><th>موضوع</th><th>واحد</th><th>وضعیت</th><th>آخرین بروزرسانی</th></tr></thead>
    <tbody>
    @foreach($tickets as $tk)
      @php $cls = ['pending' => 'badge-warn', 'answered' => 'badge-ok', 'closed' => 'badge'][$tk->status] ?? 'badge'; @endphp
      <tr>
        <td data-label="موضوع"><a href="{{ route('ticket.show', $tk->id) }}">{{ $tk->subject }}</a></td>
        <td data-label="واحد">{{ optional($tk->role)->label ?: optional($tk->role)->name }}</td>
        <td data-label="وضعیت"><span class="badge {{ $cls }}">{{ $tk->status() }}</span></td>
        <td data-label="آخرین بروزرسانی">{{ Rd::jDate($tk->updated_at) }}</td>
      </tr>
    @endforeach
    </tbody></table></div></div>
  @else
    <div class="empty">{{ Rd::icon('i-book') }}<p>{{ request('search') ? 'تیکتی با این موضوع پیدا نشد.' : 'هنوز تیکتی ثبت نکرده‌اید.' }}</p><a class="btn btn-lg" href="{{ route('ticket.create') }}">ثبت تیکت</a></div>
  @endif
@endsection
