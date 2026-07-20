@extends('layouts.app')

@section('title', 'Input Absensi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Absensi / Input</div>
    <h1>Input Absensi</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('absensi.store') }}">
        @csrf
        @include('absensi.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('absensi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
