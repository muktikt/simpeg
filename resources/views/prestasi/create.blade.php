@extends('layouts.app')

@section('title', 'Tambah Prestasi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Prestasi Pegawai / Tambah</div>
    <h1>Tambah Prestasi Pegawai</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('prestasi.store') }}">
        @csrf
        @include('prestasi.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('prestasi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
