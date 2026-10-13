@extends('admin.layouts.app')

@section('title', 'آمار تراکنش‌های سکه')

@section('content')
    <h1>آمار تراکنش‌های سکه</h1>
    <p>{{ $stats['total_transactions'] }}</p>
@endsection
