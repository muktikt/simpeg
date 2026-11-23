@extends('layouts.app')

@section('title', 'Ubah Pejabat Penandatangan - ' . $ttd->judul)

@section('content')
<style>
.form-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #0F2A3D;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #E2E8F0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pejabat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.pejabat-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px;
}
.live-preview-box {
    background: #FFFFFF;
    border: 2px dashed #94A3B8;
    border-radius: 14px;
    padding: 24px 20px;
    margin-top: 24px;
    margin-bottom: 24px;
}
.live-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    text-align: center;
}
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan / Pejabat Penandatangan / Edit</div>
    <h1>UBAH PEJABAT PENANDATANGAN LAPORAN</h1>
    <p style="color:#64748B; font-size:13.5px; margin:4px 0 0 0;">
        Konfigurasi format tanda tangan resmi untuk: <strong>{{ $ttd->judul }}</strong> (Kode: <code>{{ $ttd->kode }}</code>)
    </p>
</div>

<form method="POST" action="{{ route('pejabat-ttd.update', $ttd->id) }}" onsubmit="return confirmSubmit(event, 'Simpan perubahan data pejabat penandatangan laporan ini?', 'Konfirmasi Perubahan TTD', 'primary', 'Ya, Simpan');">
    @csrf

    <div class="form-card" style="margin-bottom:24px;">
        <div class="form-section-title">
            <svg width="18" height="18" fill="none" stroke="#0284C7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Informasi Umum Dokumen Laporan
        </div>

        <div class="form-grid" style="grid-template-columns: 2fr 1fr; gap:16px;">
            <div class="form-group">
                <label for="judul">Judul Konfigurasi Laporan <span style="color:var(--danger);">*</span></label>
                <input type="text" id="judul" name="judul" value="{{ old('judul', $ttd->judul) }}" required>
                @error('judul') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="kota">Kota Tempat Pengesahan <span style="color:var(--danger);">*</span></label>
                <input type="text" id="kota" name="kota" value="{{ old('kota', $ttd->kota ?? 'Indramayu') }}" oninput="updateLivePreview()" required>
                @error('kota') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <!-- 3 Pejabat Penandatangan -->
    <div class="pejabat-grid">
        <!-- Pejabat 1: Mengetahui -->
        <div class="pejabat-box">
            <div class="form-section-title" style="color:#0284C7;">
                <span>1. Pejabat Kiri (Mengetahui)</span>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Pilih dari Master Pegawai (Otomatis Isi)</label>
                <select onchange="autoFillPejabat(this, 1)" style="font-size:12.5px;">
                    <option value="">-- Pilih Pegawai untuk Auto-Fill --</option>
                    @foreach ($pegawaiList as $p)
                        @php
                            $namaFull = trim(($p['nama'] ?? '') . ($p['gelar'] ? ', ' . $p['gelar'] : ''));
                        @endphp
                        <option value="{{ $p['nik'] }}" data-nama="{{ $namaFull }}" data-jabatan="{{ $p['jabatan'] ?? '' }}">
                            {{ $p['nik'] }} - {{ $namaFull }} ({{ $p['jabatan'] ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="label1">Label Teks Pengesahan</label>
                <input type="text" id="label1" name="label1" value="{{ old('label1', $ttd->label1 ?? 'MENGETAHUI') }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="jabatan1">Jabatan Resmi</label>
                <input type="text" id="jabatan1" name="jabatan1" value="{{ old('jabatan1', $ttd->jabatan1) }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="nama1">Nama Lengkap & Gelar</label>
                <input type="text" id="nama1" name="nama1" value="{{ old('nama1', $ttd->nama1) }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group">
                <label for="nik1">Nomor Induk Karyawan (NIK)</label>
                <input type="text" id="nik1" name="nik1" value="{{ old('nik1', $ttd->nik1) }}" oninput="updateLivePreview()" required>
            </div>
        </div>

        <!-- Pejabat 2: Menyetujui -->
        <div class="pejabat-box">
            <div class="form-section-title" style="color:#0284C7;">
                <span>2. Pejabat Tengah (Menyetujui)</span>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Pilih dari Master Pegawai (Otomatis Isi)</label>
                <select onchange="autoFillPejabat(this, 2)" style="font-size:12.5px;">
                    <option value="">-- Pilih Pegawai untuk Auto-Fill --</option>
                    @foreach ($pegawaiList as $p)
                        @php
                            $namaFull = trim(($p['nama'] ?? '') . ($p['gelar'] ? ', ' . $p['gelar'] : ''));
                        @endphp
                        <option value="{{ $p['nik'] }}" data-nama="{{ $namaFull }}" data-jabatan="{{ $p['jabatan'] ?? '' }}">
                            {{ $p['nik'] }} - {{ $namaFull }} ({{ $p['jabatan'] ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="label2">Label Teks Pengesahan</label>
                <input type="text" id="label2" name="label2" value="{{ old('label2', $ttd->label2 ?? 'DISETUJUI') }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="jabatan2">Jabatan Resmi</label>
                <input type="text" id="jabatan2" name="jabatan2" value="{{ old('jabatan2', $ttd->jabatan2) }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="nama2">Nama Lengkap & Gelar</label>
                <input type="text" id="nama2" name="nama2" value="{{ old('nama2', $ttd->nama2) }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group">
                <label for="nik2">Nomor Induk Karyawan (NIK)</label>
                <input type="text" id="nik2" name="nik2" value="{{ old('nik2', $ttd->nik2) }}" oninput="updateLivePreview()" required>
            </div>
        </div>

        <!-- Pejabat 3: Pembuat / Manajer -->
        <div class="pejabat-box">
            <div class="form-section-title" style="color:#0284C7;">
                <span>3. Pejabat Kanan (Dibuat Oleh)</span>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Pilih dari Master Pegawai (Otomatis Isi)</label>
                <select onchange="autoFillPejabat(this, 3)" style="font-size:12.5px;">
                    <option value="">-- Pilih Pegawai untuk Auto-Fill --</option>
                    @foreach ($pegawaiList as $p)
                        @php
                            $namaFull = trim(($p['nama'] ?? '') . ($p['gelar'] ? ', ' . $p['gelar'] : ''));
                        @endphp
                        <option value="{{ $p['nik'] }}" data-nama="{{ $namaFull }}" data-jabatan="{{ $p['jabatan'] ?? '' }}">
                            {{ $p['nik'] }} - {{ $namaFull }} ({{ $p['jabatan'] ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="label3">Label Teks Pengesahan</label>
                <input type="text" id="label3" name="label3" value="{{ old('label3', $ttd->label3 ?? 'DIBUAT OLEH') }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="jabatan3">Jabatan Resmi</label>
                <input type="text" id="jabatan3" name="jabatan3" value="{{ old('jabatan3', $ttd->jabatan3) }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="nama3">Nama Lengkap & Gelar</label>
                <input type="text" id="nama3" name="nama3" value="{{ old('nama3', $ttd->nama3) }}" oninput="updateLivePreview()" required>
            </div>

            <div class="form-group">
                <label for="nik3">Nomor Induk Karyawan (NIK)</label>
                <input type="text" id="nik3" name="nik3" value="{{ old('nik3', $ttd->nik3) }}" oninput="updateLivePreview()" required>
            </div>
        </div>
    </div>

    <!-- Live Preview Card -->
    <div class="live-preview-box">
        <div style="font-size:13px; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:14px; display:flex; align-items:center; gap:6px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Live Preview Lembar Pengesahan (Real-Time)
        </div>

        <div id="prevKota" style="text-align:right; font-size:12.5px; color:#334155; margin-bottom:20px; font-weight:600;">
            {{ $ttd->kota ?? 'Indramayu' }}, {{ date('d F Y') }}
        </div>

        <div class="live-preview-grid">
            <!-- 1 -->
            <div>
                <div id="prevLabel1" style="font-size:11.5px; font-weight:700; color:#64748B;">MENGETAHUI</div>
                <div id="prevJabatan1" style="font-size:12.5px; font-weight:700; color:#0F2A3D; margin-top:2px;">DIREKTUR UTAMA</div>
                <div style="height:65px;"></div>
                <div id="prevNama1" style="font-size:13px; font-weight:700; text-decoration:underline; color:#0F2A3D;">{{ $ttd->nama1 }}</div>
                <div id="prevNik1" style="font-size:11.5px; color:#64748B; margin-top:2px;">NIK. {{ $ttd->nik1 }}</div>
            </div>

            <!-- 2 -->
            <div>
                <div id="prevLabel2" style="font-size:11.5px; font-weight:700; color:#64748B;">DISETUJUI</div>
                <div id="prevJabatan2" style="font-size:12.5px; font-weight:700; color:#0F2A3D; margin-top:2px;">DIREKTUR UMUM</div>
                <div style="height:65px;"></div>
                <div id="prevNama2" style="font-size:13px; font-weight:700; text-decoration:underline; color:#0F2A3D;">{{ $ttd->nama2 }}</div>
                <div id="prevNik2" style="font-size:11.5px; color:#64748B; margin-top:2px;">NIK. {{ $ttd->nik2 }}</div>
            </div>

            <!-- 3 -->
            <div>
                <div id="prevLabel3" style="font-size:11.5px; font-weight:700; color:#64748B;">DIBUAT OLEH</div>
                <div id="prevJabatan3" style="font-size:12.5px; font-weight:700; color:#0F2A3D; margin-top:2px;">MANAJER BIDANG KEUANGAN</div>
                <div style="height:65px;"></div>
                <div id="prevNama3" style="font-size:13px; font-weight:700; text-decoration:underline; color:#0F2A3D;">{{ $ttd->nama3 }}</div>
                <div id="prevNik3" style="font-size:11.5px; color:#64748B; margin-top:2px;">NIK. {{ $ttd->nik3 }}</div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-size:14px;">Simpan Perubahan TTD</button>
        <a href="{{ route('pejabat-ttd.index') }}" class="btn btn-outline" style="padding:10px 18px;">Batal</a>
    </div>
</form>

<script>
function autoFillPejabat(selectEl, num) {
    const selected = selectEl.options[selectEl.selectedIndex];
    if (!selected || !selected.value) return;

    const nik = selected.value;
    const nama = selected.getAttribute('data-nama') || '';
    const jabatan = selected.getAttribute('data-jabatan') || '';

    document.getElementById('nama' + num).value = nama;
    document.getElementById('nik' + num).value = nik;

    // Suggest default jabatan if input is empty
    const jInput = document.getElementById('jabatan' + num);
    if (!jInput.value.trim() && jabatan) {
        jInput.value = jabatan.toUpperCase();
    }

    updateLivePreview();
}

function updateLivePreview() {
    const kota = document.getElementById('kota').value.trim() || 'Indramayu';
    const today = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
    document.getElementById('prevKota').innerText = kota + ', ' + today;

    for (let i = 1; i <= 3; i++) {
        document.getElementById('prevLabel' + i).innerText = document.getElementById('label' + i).value.trim() || '-';
        document.getElementById('prevJabatan' + i).innerText = document.getElementById('jabatan' + i).value.trim() || '-';
        document.getElementById('prevNama' + i).innerText = document.getElementById('nama' + i).value.trim() || '-';
        document.getElementById('prevNik' + i).innerText = 'NIK. ' + (document.getElementById('nik' + i).value.trim() || '-');
    }
}

// Initial preview setup on page load
document.addEventListener('DOMContentLoaded', updateLivePreview);
</script>
@endsection

