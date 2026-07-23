@extends('layouts.app')

@section('title', 'SET Hari Kerja')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / SET Hari Kerja</div>
    <h1>SET Hari Kerja</h1>
</div>

<div class="form-card" style="max-width:420px;">
    <form method="POST" action="{{ route('absensi.hari-kerja.update') }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="hari_kerja">Hari Kerja dalam 1 Bulan</label>
            <input type="number" id="hari_kerja" name="hari_kerja" min="1" max="31" value="{{ old('hari_kerja', $hariKerja) }}" required>
            @error('hari_kerja') <div class="form-error">{{ $message }}</div> @enderror
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:-4px 0 16px;">
            Nilai ini berlaku untuk seluruh sistem, dipakai sebagai acuan perhitungan absensi dan gaji.
        </p>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
