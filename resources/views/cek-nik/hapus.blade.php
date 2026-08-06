@extends('layouts.app')

@section('title', 'Hapus Kesalahan NIK')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Set Potongan / Hapus Kesalahan NIK</div>
    <h1>HAPUS KESALAHAN NIK POTONGAN</h1>
</div>

<div class="form-card" style="max-width:600px;">
    <p style="margin-bottom:20px; color:var(--text-muted);">
        Masukkan NIK yang salah dan Tanggal Periode Potongan untuk menghapus data potongan tersebut.
    </p>

    <form method="POST" action="{{ route('cek-nik.hapus.proses') }}" onsubmit="return confirmSubmit(event, 'Yakin menghapus data potongan NIK ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');">
        @csrf

        <div class="form-group" style="margin-bottom:16px;">
            <label for="nik">NIK <span style="color:var(--danger);">*</span></label>
            <input type="text" id="nik" name="nik" value="{{ old('nik') }}" placeholder="Masukkan NIK" required>
            @error('nik') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap:16px;">
            <div class="form-group">
                <label for="bulan">Bulan Potongan <span style="color:var(--danger);">*</span></label>
                <select id="bulan" name="bulan" required>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected((int) old('bulan', date('n')) === $m)>
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
                @error('bulan') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="tahun">Tahun Potongan <span style="color:var(--danger);">*</span></label>
                <input type="number" id="tahun" name="tahun" value="{{ old('tahun', date('Y')) }}" min="2000" max="2100" required>
                @error('tahun') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-actions" style="margin-top:24px;">
            <button type="submit" class="btn btn-danger">Hapus Data Potongan</button>
            <a href="{{ route('potongan-keu.index', 'gaji') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection
