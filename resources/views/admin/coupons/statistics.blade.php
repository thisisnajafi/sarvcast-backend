@extends('admin.layouts.app')

@section('title', 'آمار کدهای تخفیف')

@section('content')
    <h1>آمار کدهای تخفیف</h1>
    <p>{{ $stats['total_coupons'] }}</p>
@endsection
