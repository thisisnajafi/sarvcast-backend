@extends('admin.layouts.app')

@section('title', 'جزئیات کد تخفیف')

@section('content')
    <h1>جزئیات کد تخفیف</h1>
    <p>{{ $coupon->code }}</p>
@endsection
