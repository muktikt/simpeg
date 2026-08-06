@extends('layouts.app')

@section('title', 'Tambah ' . $tipeLabel)

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET {{ $tipeLabel }} / Tambah</div>
    <h1>Tambah {{ $tipeLabel }}</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('potongan-keu.store', $tipe) }}">
        @csrf
        @include('potongan-keu.partials.form-field')

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('potongan-keu.index', $tipe) }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
