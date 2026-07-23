@extends('layouts.app')

@section('title', 'Edit Akun Pengguna')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Akun Pengguna / Edit</div>
    <h1>Edit Akun Pengguna</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('user-akses.update', $user['id']) }}">
        @csrf
        @method('PUT')
        @include('user-akses.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('user-akses.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
