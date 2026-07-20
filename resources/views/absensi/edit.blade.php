@extends('layouts.app')

@section('title', 'Edit Absensi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Absensi / Edit</div>
    <h1>Edit Absensi</h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('absensi.update', $absensi['id']) }}">
        @csrf
        @method('PUT')
        @php $bulan = $absensi['bulan']; $tahun = $absensi['tahun']; @endphp
        @include('absensi.partials.form-fields')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('absensi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
