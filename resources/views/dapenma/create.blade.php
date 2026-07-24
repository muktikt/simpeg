@extends('layouts.app')

@section('title', 'Tambah Peserta Dapenma')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET PHDP DAPENMA / Tambah</div>
    <h1>Tambah Peserta Dapenma</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('dapenma.store') }}">
        @csrf
        @include('dapenma.partials.form-field')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('dapenma.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
