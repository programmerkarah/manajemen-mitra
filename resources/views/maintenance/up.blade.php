@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Keluar dari Maintenance</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('maintenance.up') }}">
        @csrf
        <button type="submit" class="btn btn-danger">Hapus Bypass Maintenance</button>
    </form>
</div>
@endsection
