@extends('layouts.app')

@section('title', 'Edit Prestasi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Prestasi Pegawai / Edit</div>
    <h1>Edit Prestasi Pegawai</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('prestasi.update', $prestasi['id']) }}">
        @csrf
        @method('PUT')
        @include('prestasi.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('prestasi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
