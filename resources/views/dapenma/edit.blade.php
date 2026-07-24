@extends('layouts.app')

@section('title', 'Edit Peserta Dapenma')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET PHDP DAPENMA / Edit</div>
    <h1>Edit Peserta Dapenma</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('dapenma.update', $dapenma['id']) }}">
        @csrf
        @method('PUT')
        @include('dapenma.partials.form-field')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('dapenma.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
