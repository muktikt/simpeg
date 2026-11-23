@extends('layouts.app')

@section('title', 'Hapus Kesalahan NIK Potongan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Set Potongan / Hapus Kesalahan NIK</div>
    <h1>HAPUS KESALAHAN NIK POTONGAN</h1>
</div>

<div class="form-card" style="max-width:640px;">
    <p style="margin-bottom:20px; color:var(--text-muted); font-size:13.5px; line-height:1.5;">
        Fitur ini digunakan oleh Bagian Keuangan untuk menghapus baris data potongan yang salah input / salah NIK pada periode tertentu secara aman.
    </p>

    @if ($previewData)
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:14px 16px; margin-bottom:20px;">
            <div style="font-weight:700; color:#991B1B; font-size:13px; margin-bottom:4px;">
                Data Potongan Ditemukan:
            </div>
            <div style="font-size:12.5px; color:#7F1D1D; line-height:1.5;">
                Pegawai: <strong>{{ $pegawai['nama_lengkap'] ?? ($previewData->nama_pegawai ?? 'Pegawai') }}</strong> (NIK: {{ $previewData->nik }})<br>
                Unit Kerja: {{ $pegawai['unit_kerja'] ?? '-' }} &bull; Periode: {{ $previewData->bulan }}/{{ $previewData->tahun }} ({{ strtoupper($previewData->tipe) }})<br>
                Status: <strong>{{ $previewData->status === 'Y' ? 'Terbit Final' : 'Draft' }}</strong>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('cek-nik.hapus.proses') }}" onsubmit="return confirmSubmit(event, 'Yakin menghapus data potongan NIK ini? Tindakan ini tidak dapat dibatalkan.', 'Konfirmasi Hapus NIK', 'danger', 'Ya, Hapus Data');">
        @csrf

        <div class="form-group" style="margin-bottom:16px;">
            <label for="tipe">Jenis Potongan <span style="color:var(--danger);">*</span></label>
            <select id="tipe" name="tipe" required>
                <option value="gaji" @selected(old('tipe', $tipe ?? 'gaji') === 'gaji')>Potongan Gaji</option>
                <option value="thr" @selected(old('tipe', $tipe ?? 'gaji') === 'thr')>Potongan THR</option>
                <option value="gaji13" @selected(old('tipe', $tipe ?? 'gaji') === 'gaji13')>Potongan Gaji 13</option>
            </select>
            @error('tipe') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group" style="margin-bottom:16px;">
            <label for="nik">NIK Pegawai yang Ingin Dihapus <span style="color:var(--danger);">*</span></label>
            <input type="text" id="nik" name="nik" value="{{ old('nik', $nik ?? '') }}" placeholder="Contoh: 1711235" required>
            @error('nik') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
            <div class="form-group">
                <label for="bulan">Bulan Potongan <span style="color:var(--danger);">*</span></label>
                <select id="bulan" name="bulan" required>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected((int) old('bulan', $bulan ?? date('n')) === $m)>
                            {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
                @error('bulan') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="tahun">Tahun Potongan <span style="color:var(--danger);">*</span></label>
                <input type="number" id="tahun" name="tahun" value="{{ old('tahun', $tahun ?? date('Y')) }}" min="2000" max="2100" required>
                @error('tahun') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
            <button type="submit" class="btn btn-danger">Hapus Data Potongan</button>
            <a href="{{ route('cek-nik.komparasi') }}" class="btn btn-outline">Kembali ke Komparasi</a>
        </div>
    </form>
</div>
@endsection
