@extends('layouts.app')

@section('title', 'Edit Sanksi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Sanksi Pegawai / Edit</div>
    <h1>Edit Sanksi Pegawai</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('sanksi.update', $sanksi['id']) }}">
        @csrf
        @method('PUT')
        @include('sanksi.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('sanksi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
