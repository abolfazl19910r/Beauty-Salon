@extends('layouts.admin')

@section('title', 'پنل مدیریت')

@section('content')
    <div id="admin-dashboard"></div>
@endsection

@push('scripts')
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
@endpush
