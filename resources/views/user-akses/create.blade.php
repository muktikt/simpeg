@extends('layouts.app')

@section('title', 'Tambah Akun Pengguna')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Akun Pengguna / Tambah</div>
    <h1>Tambah Akun Pengguna</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('user-akses.store') }}">
        @csrf
        @include('user-akses.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('user-akses.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
