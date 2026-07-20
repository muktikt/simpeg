@extends('layouts.app')

@section('title', 'Tambah Gaji Pokok')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Gaji Pokok / Tambah</div>
    <h1>Tambah Gaji Pokok</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('gaji-pokok.store') }}">
        @csrf
        @include('gaji-pokok.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('gaji-pokok.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
