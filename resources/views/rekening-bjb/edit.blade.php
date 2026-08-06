@extends('layouts.app')

@section('title', 'Edit Rekening BJB')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Rekening BJB / Edit</div>
    <h1>Edit Rekening BJB</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('rekening-bjb.update', $item['id']) }}">
        @csrf
        @method('PUT')
        @include('rekening-bjb.partials.form-field')

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('rekening-bjb.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
