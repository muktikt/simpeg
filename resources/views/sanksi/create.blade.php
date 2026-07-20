@extends('layouts.app')

@section('title', 'Tambah Sanksi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Sanksi Pegawai / Tambah</div>
    <h1>Tambah Sanksi Pegawai</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('sanksi.store') }}">
        @csrf
        @include('sanksi.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('sanksi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
