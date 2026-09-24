{{-- GET /user/ticket/{id} — UserPanel\TicketController@show → $ticket (messages newest first). --}}
@extends('user.layout.master', ['panel' => 'tickets'])
@section('title', $ticket->subject . ' | سپاهان فلز')
@section('crumb', 'تیکت')
@section('head')<div><h1>{{ $ticket->subject }}</h1><div class="sub">{{ optional($ticket->role)->label ?: optional($ticket->role)->name }} · <span class="badge {{ ['pending' => 'badge-warn', 'answered' => 'badge-ok'][$ticket->status] ?? 'badge' }}">{{ $ticket->status() }}</span></div></div><a class="btn btn-ghost btn-lg2" href="{{ route('ticket.index') }}">بازگشت</a>@endsection
@section('panel')
  @if($ticket->status !== 'closed')
  <form class="pcard2" method="post" action="{{ route('message.store', $ticket->id) }}">
    @csrf
    @if($errors->any())
      <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<span>{{ $errors->first() }}</span></div>
    @endif
    <label class="ffield"><span>پیام تازه</span><textarea name="message" rows="3" minlength="5" maxlength="800" required>{{ old('message') }}</textarea></label>
    <div class="factions" style="margin-top:var(--s3)"><button class="btn btn-lg" type="submit">ارسال پیام</button></div>
  </form>
  @endif
  @if($ticket->attached)<p><a class="guidelink" href="{{ $ticket->file() }}" target="_blank" rel="noopener">{{ Rd::icon('i-doc') }} پیوست تیکت</a></p>@endif
  <div class="thread">
  @foreach($ticket->messages as $m)
    <div class="msg{{ (int) $m->user_id === (int) auth()->id() ? ' me' : '' }}">
      <header><b>{{ (int) $m->user_id === (int) auth()->id() ? 'شما' : (optional($m->user)->full_name ?: 'کارشناس سپاهان فلز') }}</b><span>{{ Rd::jDate($m->created_at) }} · {{ optional($m->created_at)->format('H:i') }}</span></header>
      <p>{{ $m->text }}</p>
    </div>
  @endforeach
  </div>
@endsection
