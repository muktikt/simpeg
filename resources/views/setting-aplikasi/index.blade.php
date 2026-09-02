@extends('layouts.app')

@section('title', 'SET Aplikasi - Pengaturan Umum')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pengaturan Aplikasi</h1>
        <p class="page-subtitle">Kelola konfigurasi umum instansi, jam operasional presensi, dan periode aktif SIMPEG.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px; padding: 14px 18px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-weight: 500;">
        {{ session('success') }}
    </div>
@endif

<div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <form action="{{ route('setting-aplikasi.update') }}" method="POST">
        @csrf
        @method('PUT')

        <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 18px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
            Identitas Instansi / Perusahaan
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Instansi</label>
                <input type="text" name="nama_instansi" value="{{ old('nama_instansi', $settings['nama_instansi']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Singkatan / Brand</label>
                <input type="text" name="singkatan_instansi" value="{{ old('singkatan_instansi', $settings['singkatan_instansi']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Email Resmi</label>
                <input type="email" name="email_instansi" value="{{ old('email_instansi', $settings['email_instansi']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Nomor Telepon</label>
                <input type="text" name="telepon_instansi" value="{{ old('telepon_instansi', $settings['telepon_instansi']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Alamat Kantor Pusat</label>
            <textarea name="alamat_instansi" rows="2" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;">{{ old('alamat_instansi', $settings['alamat_instansi']) }}</textarea>
        </div>

        <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-top: 32px; margin-bottom: 18px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
            Pengaturan Presensi & Periode Kerja
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Jam Masuk Kantor</label>
                <input type="time" name="jam_masuk_kantor" value="{{ old('jam_masuk_kantor', $settings['jam_masuk_kantor']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Jam Pulang Kantor</label>
                <input type="time" name="jam_pulang_kantor" value="{{ old('jam_pulang_kantor', $settings['jam_pulang_kantor']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Toleransi Keterlambatan (Menit)</label>
                <input type="number" name="toleransi_terlambat_menit" value="{{ old('toleransi_terlambat_menit', $settings['toleransi_terlambat_menit']) }}" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Periode Penggajian Aktif</label>
                <div style="display: flex; gap: 8px;">
                    <select name="periode_aktif_bulan" class="form-control" style="width: 60%; padding: 10px 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ old('periode_aktif_bulan', $settings['periode_aktif_bulan']) == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                            </option>
                        @endfor
                    </select>
                    <input type="number" name="periode_aktif_tahun" value="{{ old('periode_aktif_tahun', $settings['periode_aktif_tahun']) }}" class="form-control" style="width: 40%; padding: 10px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="background: #1e3a8a; color: #fff; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(30,58,138,0.2);">
                Simpan Perubahan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
