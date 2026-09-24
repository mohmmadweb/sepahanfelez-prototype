{{-- GET /user/tickets/create — UserPanel\TicketController@create → $departments. --}}
@extends('user.layout.master', ['panel' => 'tickets'])
@section('title', 'تیکت تازه | سپاهان فلز')
@section('crumb', 'تیکت تازه')
@section('head')<div><h1>تیکت تازه</h1><div class="sub">پاسخ کارشناس در همین صفحه و با پیامک اطلاع داده می‌شود</div></div>@endsection
@section('panel')
  <form class="pcard2" method="post" action="{{ route('ticket.store') }}" enctype="multipart/form-data">
    @csrf
    @if($errors->any())
      <div class="alert alert-err" role="alert">{{ Rd::icon('i-flat') }}<ul>@foreach($errors->all() as $er)<li>{{ $er }}</li>@endforeach</ul></div>
    @endif
    <div class="fgrid2">
      <label class="ffield full"><span>موضوع</span><input name="subject" type="text" minlength="5" maxlength="150" required value="{{ old('subject') }}"></label>
      <label class="ffield"><span>واحد</span><select name="department" required><option value="" disabled @if(! old('department')) selected @endif>انتخاب واحد</option>@foreach($departments as $dp)<option value="{{ $dp->id }}"@if((int) old('department') === (int) $dp->id) selected @endif>{{ $dp->label ?: $dp->name }}</option>@endforeach</select></label>
      <label class="ffield"><span>پیوست (اختیاری)</span><input name="attached" type="file" accept="image/png,image/jpeg,application/pdf"><span class="hint">تصویر یا PDF تا ۲ مگابایت — مثلاً نقشه یا لیست بار</span></label>
      <label class="ffield full"><span>متن درخواست</span><textarea name="message" rows="6" minlength="10" maxlength="1000" required>{{ old('message') }}</textarea></label>
    </div>
    <div class="factions"><button class="btn btn-lg" type="submit">ثبت تیکت</button><a class="btn btn-ghost btn-lg2" href="{{ route('ticket.index') }}">انصراف</a></div>
  </form>
@endsection
