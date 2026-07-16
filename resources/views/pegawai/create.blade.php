@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai / Tambah</div>
    <h1>Tambah Pegawai</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('pegawai.store') }}">
        @csrf
        @include('pegawai.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('pegawai.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
