@extends('layouts.app')

@section('title', 'Edit ' . $tipeLabel)

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET {{ $tipeLabel }} / Edit</div>
    <h1>Edit {{ $tipeLabel }}</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('potongan-keu.update', [$tipe, $item['id']]) }}">
        @csrf
        @method('PUT')
        @include('potongan-keu.partials.form-field')

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('potongan-keu.index', $tipe) }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
