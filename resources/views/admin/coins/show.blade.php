@extends('admin.layouts.app')

@section('title', 'جزئیات تراکنش سکه')

@section('content')
    <h1>جزئیات تراکنش سکه</h1>
    <p>{{ $coin->amount }}</p>
@endsection
