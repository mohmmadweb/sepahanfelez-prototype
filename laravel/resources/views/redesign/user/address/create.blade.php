@extends('user.layout.master', ['panel' => 'address'])
@section('title', 'نشانی تازه | سپاهان فلز')
@section('crumb', 'نشانی تازه')
@section('head')<div><h1>نشانی تازه</h1><div class="sub">نشانی محل تحویل بار</div></div>@endsection
@section('panel')
  @include('user.address.form', ['action' => route('address.store'), 'method' => 'POST', 'address' => null])
@endsection
