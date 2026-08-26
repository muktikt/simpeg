@extends('layouts.app')

@section('title', 'Buat Pengumuman Baru')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Kelola Pengumuman / Buat Baru</div>
    <h1>Buat Pengumuman Baru</h1>
</div>

@if ($errors->any())
    <div class="alert alert-danger" style="background:#FEE2E2; color:#991B1B; padding:14px 18px; border-radius:10px; margin-bottom:20px; font-weight:500; border:1px solid #FECACA;">
        <ul style="margin:0; padding-left:20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-card" style="max-width:860px; background:white; padding:28px 32px; border-radius:16px; border:1px solid #E2E8F0; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
    <form action="{{ route('pengumuman.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Judul -->
        <div class="form-group" style="margin-bottom:20px;">
            <label for="judul" style="display:block; font-weight:600; margin-bottom:8px; color:#1E293B;">
                Judul Pengumuman <span style="color:#DC2626;">*</span>
            </label>
            <input type="text" id="judul" name="judul" value="{{ old('judul') }}" placeholder="Contoh: Jadwal Libur Nasional Idul Fitri 1447 H" required style="width:100%; padding:10px 14px; border:1px solid #CBD5E1; border-radius:8px; font-size:14px;">
        </div>

        <!-- Isi Pengumuman -->
        <div class="form-group" style="margin-bottom:20px;">
            <label for="isi" style="display:block; font-weight:600; margin-bottom:8px; color:#1E293B;">
                Isi Pengumuman <span style="color:#DC2626;">*</span>
            </label>
            <textarea id="isi" name="isi" rows="6" placeholder="Tuliskan isi rincian pengumuman yang akan disampaikan ke seluruh pegawai..." required style="width:100%; padding:12px 14px; border:1px solid #CBD5E1; border-radius:8px; font-size:14px; line-height:1.5;">{{ old('isi') }}</textarea>
        </div>

        <!-- Pengaturan Status & Prioritas -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin-bottom:20px; background:#F8FAFC; padding:16px; border-radius:10px; border:1px solid #E2E8F0;">
            <!-- Prioritas -->
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px; color:#1E293B; font-size:13px;">
                    Prioritas Pengumuman
                </label>
                <select name="prioritas" style="width:100%; padding:9px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px; background:white;">
                    <option value="normal" @selected(old('prioritas') === 'normal')>Normal (Standar)</option>
                    <option value="penting" @selected(old('prioritas') === 'penting')>⚡ Penting (High Priority)</option>
                </select>
            </div>

            <!-- Disematkan -->
            <div style="display:flex; align-items:center; gap:10px; margin-top:22px;">
                <input type="checkbox" id="disematkan" name="disematkan" value="1" @checked(old('disematkan')) style="width:18px; height:18px;">
                <label for="disematkan" style="font-weight:600; color:#1E293B; font-size:13px; cursor:pointer;">
                    📌 Sematkan ke Posisi Teratas (Pin)
                </label>
            </div>

            <!-- Langsung Aktif -->
            <div style="display:flex; align-items:center; gap:10px; margin-top:22px;">
                <input type="checkbox" id="aktif" name="aktif" value="1" @checked(old('aktif', '1') === '1') style="width:18px; height:18px;">
                <label for="aktif" style="font-weight:600; color:#1E293B; font-size:13px; cursor:pointer;">
                    🟢 Publikasikan / Langsung Aktif
                </label>
            </div>
        </div>

        <!-- Target Roles -->
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:block; font-weight:600; margin-bottom:8px; color:#1E293B;">
                Target Penerima Pengumuman
            </label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; background:#F8FAFC; padding:16px; border-radius:10px; border:1px solid #E2E8F0;">
                @foreach ($rolesList as $key => $label)
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox" name="target_roles[]" value="{{ $key }}" checked style="width:16px; height:16px;">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <small style="color:#64748B; font-size:12px; margin-top:4px; display:block;">Pilih role yang berhak melihat pengumuman ini di aplikasi mobile / web.</small>
        </div>

        <!-- Jadwal Terbit & Kedaluwarsa -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
            <div>
                <label for="terbit_pada" style="display:block; font-weight:600; margin-bottom:6px; color:#1E293B; font-size:13px;">
                    Jadwal Terbit (Otomatis Tayang)
                </label>
                <input type="datetime-local" id="terbit_pada" name="terbit_pada" value="{{ old('terbit_pada') }}" style="width:100%; padding:9px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px;">
                <small style="color:#64748B; font-size:11px;">Kosongkan jika ingin langsung tayang sekarang.</small>
            </div>

            <div>
                <label for="kedaluwarsa_pada" style="display:block; font-weight:600; margin-bottom:6px; color:#1E293B; font-size:13px;">
                    Jadwal Kedaluwarsa (Auto-Nonaktif)
                </label>
                <input type="datetime-local" id="kedaluwarsa_pada" name="kedaluwarsa_pada" value="{{ old('kedaluwarsa_pada') }}" style="width:100%; padding:9px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px;">
                <small style="color:#64748B; font-size:11px;">Kosongkan jika berlaku seterusnya tanpa batas waktu.</small>
            </div>
        </div>

        <!-- Lampiran Berkas / Dokumen -->
        <div class="form-group" style="margin-bottom:24px;">
            <label for="lampiran" style="display:block; font-weight:600; margin-bottom:8px; color:#1E293B;">
                Unggah Berkas / Dokumen Lampiran (Opsional)
            </label>
            <input type="file" id="lampiran" name="lampiran" accept=".pdf,.jpg,.jpeg,.png" style="width:100%; padding:8px 12px; border:1px dashed #94A3B8; border-radius:8px; font-size:13px; background:#F8FAFC;">
            <small style="color:#64748B; font-size:11px; margin-top:4px; display:block;">Mendukung format PDF, JPG, PNG (Maks. 10 MB).</small>
        </div>

        <!-- Tombol Simpan & Batal -->
        <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #E2E8F0; padding-top:18px;">
            <a href="{{ route('pengumuman.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-weight:600;">
                Terbitkan Pengumuman
            </button>
        </div>
    </form>
</div>
@endsection
