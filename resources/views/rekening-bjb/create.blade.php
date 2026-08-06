@extends('layouts.app')

@section('title', 'Tambah Rekening BJB')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Rekening BJB / Tambah</div>
    <h1>Tambah Rekening BJB</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('rekening-bjb.store') }}">
        @csrf
        @include('rekening-bjb.partials.form-field')

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('rekening-bjb.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
