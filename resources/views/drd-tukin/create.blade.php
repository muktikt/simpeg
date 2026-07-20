@extends('layouts.app')

@section('title', 'Tambah DRD Tukin')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET DRD Tukin / Tambah</div>
    <h1>Tambah DRD Tukin</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('drd-tukin.store') }}">
        @csrf
        @include('drd-tukin.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('drd-tukin.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
