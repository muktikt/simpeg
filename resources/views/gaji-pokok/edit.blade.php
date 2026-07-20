@extends('layouts.app')

@section('title', 'Edit Gaji Pokok')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Gaji Pokok / Edit</div>
    <h1>Edit Gaji Pokok</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('gaji-pokok.update', $gapok['id']) }}">
        @csrf
        @method('PUT')
        @include('gaji-pokok.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('gaji-pokok.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
