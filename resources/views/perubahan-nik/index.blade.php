@extends('layouts.app')

@section('title', 'Perubahan NIK')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Umum / Perubahan NIK</div>
    <h1>Perubahan NIK Pegawai</h1>
</div>

<p style="font-size:12.5px; color:var(--text-muted); margin:-8px 0 20px;">
    Untuk mengubah NIK pegawai (misal saat pengangkatan Honorer ke Calon Pegawai). Perubahan akan otomatis diterapkan ke seluruh riwayat pegawai terkait (Absensi, Sanksi, Prestasi, Gaji, THR, Gaji 13).
</p>

<div class="form-card">
    <form method="POST" action="{{ route('perubahan-nik.update') }}" onsubmit="return confirm('Yakin mau ganti NIK pegawai ini? Perubahan akan diterapkan ke semua riwayat terkait.');">
        @csrf
        <div class="form-grid">
            <div class="form-group span-2">
                <label for="pegawai_id">Pegawai</label>
                <select id="pegawai_id" name="pegawai_id" required onchange="tampilkanNikLama()">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach ($pegawaiList as $p)
                        <option value="{{ $p['id'] }}" data-nik="{{ $p['nik'] }}" @selected((string) old('pegawai_id') === (string) $p['id'])>{{ $p['nik'] }} - {{ $p['nama'] }}</option>
                    @endforeach
                </select>
                @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>NIK Lama</label>
                <input type="text" id="nik-lama" value="" disabled placeholder="Pilih pegawai dulu">
            </div>
            <div class="form-group">
                <label for="nik_baru">NIK Baru</label>
                <input type="text" id="nik_baru" name="nik_baru" value="{{ old('nik_baru') }}" required>
                @error('nik_baru') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ubah NIK</button>
        </div>
    </form>
</div>

<script>
function tampilkanNikLama() {
    const select = document.getElementById('pegawai_id');
    const nikLama = document.getElementById('nik-lama');
    const selected = select.options[select.selectedIndex];
    nikLama.value = selected ? (selected.dataset.nik || '') : '';
}
</script>
@endsection
