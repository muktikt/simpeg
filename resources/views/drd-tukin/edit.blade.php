@extends('layouts.app')

@section('title', 'Edit DRD Tukin')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET DRD Tukin / Edit</div>
    <h1>Edit DRD Tukin</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('drd-tukin.update', $drd['id']) }}">
        @csrf
        @method('PUT')
        @include('drd-tukin.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('drd-tukin.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
