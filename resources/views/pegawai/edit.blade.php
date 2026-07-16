@extends('layouts.app')

@section('title', 'Edit Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai / {{ $pegawai['nama'] }} / Edit</div>
    <h1>Edit Data Pegawai</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('pegawai.update', $pegawai['id']) }}">
        @csrf
        @method('PUT')
        @include('pegawai.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('pegawai.show', $pegawai['id']) }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
