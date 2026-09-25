@extends('user.layout.master', ['panel' => 'address'])
@section('title', 'ویرایش نشانی | ' . \App\Support\Brand::name())
@section('crumb', 'ویرایش نشانی')
@section('head')<div><h1>ویرایش نشانی</h1></div>@endsection
@section('panel')
  @include('user.address.form', ['action' => route('address.update', $address->id), 'method' => 'PATCH', 'address' => $address])
@endsection
