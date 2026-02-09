@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Bypass Maintenance</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('maintenance.bypass') }}">
        @csrf
        <div class="mb-3">
            <label for="code" class="form-label">Kode Bypass</label>
            <input type="text" class="form-control" id="code" name="code" required>
        </div>
        <button type="submit" class="btn btn-primary">Bypass</button>
    </form>
</div>
@endsection
