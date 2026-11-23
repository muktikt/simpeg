@extends('layouts.app')

@section('title', 'Laporan & Pengajuan Cuti Pegawai')

@section('content')
<style>
.cuti-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}
.cuti-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 4px 0;
}
.cuti-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
}

.cuti-filter-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

/* Modal Pengajuan Cuti */
.modal-overlay-cuti {
    position: fixed;
    inset: 0;
    background: rgba(15, 42, 61, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-box-cuti {
    background: #ffffff;
    width: 100%;
    max-width: 600px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.modal-header-cuti {
    padding: 18px 24px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-body-cuti {
    padding: 20px 24px;
    overflow-y: auto;
}
.modal-footer-cuti {
    padding: 14px 24px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="page-head btn-no-print">
    <div class="breadcrumb">Home / Laporan Kepegawaian / Pengajuan & Laporan Cuti</div>
    <div class="cuti-header">
        <div>
            <h1 class="cuti-title">DATA PENGAJUAN & SURAT IZIN CUTI PEGAWAI</h1>
            <p class="cuti-subtitle">
                Kelola permohonan cuti, verifikasi status persetujuan, dan cetak fisik Surat Izin Cuti Resmi Kedinasan sesuai SK Direksi PERUMDAM Tirta Darma Ayu.
            </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" onclick="openModalAjukanCuti()" style="display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Ajukan Permohonan Cuti
            </button>
            <button type="button" class="btn btn-outline" onclick="window.print()" style="display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Rekap
            </button>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success" style="background:#DCFCE7; border:1px solid #86EFAC; color:#15803D; padding:12px 18px; border-radius:10px; margin-bottom:20px; font-weight:600;">
        ✓ {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" style="background:#FEE2E2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Status Tracking Card Khusus Pegawai (Role 5) --}}
@if ($myRole === '5' && !empty($cuti))
    @php
        $latest = $cuti[0];
    @endphp
    <div class="btn-no-print" style="background:#ffffff; border-radius:14px; border:1px solid {{ $latest['status'] === 'DISETUJUI' ? '#86EFAC' : ($latest['status'] === 'DITOLAK' ? '#FCA5A5' : '#FCD34D') }}; padding:18px 20px; margin-bottom:20px; box-shadow:0 4px 12px rgba(15,42,61,0.04);">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <div style="width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:{{ $latest['status'] === 'DISETUJUI' ? '#DCFCE7' : ($latest['status'] === 'DITOLAK' ? '#FEE2E2' : '#FEF3C7') }}; color:{{ $latest['status'] === 'DISETUJUI' ? '#15803D' : ($latest['status'] === 'DITOLAK' ? '#B91C1C' : '#B45309') }}; font-size:20px;">
                    @if ($latest['status'] === 'DISETUJUI')
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif ($latest['status'] === 'DITOLAK')
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748B;">Status Pengajuan Cuti Terkini</span>
                        <span class="badge" style="background:{{ $latest['status'] === 'DISETUJUI' ? '#DCFCE7' : ($latest['status'] === 'DITOLAK' ? '#FEE2E2' : '#FEF3C7') }}; color:{{ $latest['status'] === 'DISETUJUI' ? '#15803D' : ($latest['status'] === 'DITOLAK' ? '#B91C1C' : '#B45309') }}; font-size:11.5px; font-weight:700; padding:2px 8px; border-radius:6px;">
                            {{ $latest['status'] === 'DISETUJUI' ? 'DISETUJUI' : ($latest['status'] === 'DITOLAK' ? 'DITOLAK' : 'MENUNGGU VERIFIKASI') }}
                        </span>
                    </div>
                    <h3 style="font-size:16px; font-weight:700; color:#0F2A3D; margin:4px 0 6px 0;">
                        {{ $latest['jenis'] }} — {{ $latest['durasi_hari'] }} Hari Kerja ({{ formatTglIndo($latest['tanggal_mulai']) }} s/d {{ formatTglIndo($latest['tanggal_selesai']) }})
                    </h3>
                    @if ($latest['status'] === 'DISETUJUI')
                        <p style="font-size:13px; color:#334155; margin:0 0 4px 0;">
                            Surat Keputusan Izin Cuti Resmi No: <strong style="font-family:monospace; color:#0284C7;">{{ $latest['no_surat'] }}</strong> telah diterbitkan oleh Bagian SDM.
                            @if ($latest['catatan_approver'] && $latest['catatan_approver'] !== '-')
                                <br><span style="color:#64748B;">Catatan Approver:</span> <em>"{{ $latest['catatan_approver'] }}"</em>
                            @endif
                        </p>
                    @elseif ($latest['status'] === 'PENDING')
                        <p style="font-size:13px; color:#334155; margin:0;">
                            Permohonan cuti Anda telah diterima oleh sistem dan sedang dalam antrean pemeriksaan oleh Pejabat Bagian SDM. Anda akan menerima notifikasi lonceng otomatis saat permohonan disetujui.
                        </p>
                    @else
                        <p style="font-size:13px; color:#DC2626; margin:0;">
                            Pengajuan tidak disetujui oleh Pejabat SDM. Alasan: <em>"{{ $latest['catatan_approver'] ?: 'Tidak disetujui karena pertimbangan operasional kedinasan.' }}"</em>
                        </p>
                    @endif
                </div>
            </div>

            @if ($latest['status'] === 'DISETUJUI')
                <div style="flex-shrink:0;">
                    <a href="{{ route('cuti.surat', $latest['id']) }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; font-size:13px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak Surat Izin Cuti Resmi
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif

<!-- Filter Bar -->
<div class="cuti-filter-card btn-no-print">
    <form method="GET" action="{{ route('cuti.index') }}" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase;">Tahun:</label>
            <select name="tahun" onchange="this.form.submit()" style="height:36px; padding:4px 10px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase;">Status:</label>
            <select name="status" onchange="this.form.submit()" style="height:36px; padding:4px 10px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                <option value="all" @selected($statusFilter === 'all')>Semua Status</option>
                <option value="DISETUJUI" @selected($statusFilter === 'DISETUJUI')>Disetujui</option>
                <option value="PENDING" @selected($statusFilter === 'PENDING')>Menunggu (Pending)</option>
                <option value="DITOLAK" @selected($statusFilter === 'DITOLAK')>Ditolak</option>
            </select>
        </div>
    </form>

    <div style="font-size:13px; color:#64748B;">
        Total Catatan Cuti: <strong>{{ count($cuti) }}</strong>
    </div>
</div>

<!-- Table Card -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:45px; text-align:center;">No</th>
                <th style="width:110px;">NIK</th>
                <th>Nama Pegawai & Unit Kerja</th>
                <th>Jenis & Nomor Surat Cuti</th>
                <th>Periode & Durasi</th>
                <th>Keperluan / Alasan</th>
                <th style="text-align:center; width:110px;">Status</th>
                <th style="text-align:center; width:180px;" class="btn-no-print">Aksi & Cetak Fisik</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cuti as $c)
                <tr>
                    <td style="text-align:center; color:#64748B;">{{ $loop->iteration }}</td>
                    <td>
                        <span class="cell-nik" style="font-family:monospace; font-weight:700; background:#F1F5F9; padding:2px 6px; border-radius:4px;">
                            {{ $c['nik'] }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:700; color:#0F2A3D;">{{ $c['nama'] }}</div>
                        <div style="font-size:12px; color:#64748B; margin-top:2px;">
                            {{ $c['unit_kerja'] }} &bull; <span style="color:#94A3B8;">{{ $c['jabatan'] }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600; color:#0284C7;">{{ $c['jenis'] }}</div>
                        <div style="font-size:11.5px; font-family:monospace; color:#475569; margin-top:2px;">
                            {{ $c['no_surat'] }}
                        </div>
                    </td>
                    <td>
                        <div style="font-size:12.5px; font-weight:600; color:#0F2A3D;">
                            {{ formatTglIndo($c['tanggal_mulai']) }} s/d {{ formatTglIndo($c['tanggal_selesai']) }}
                        </div>
                        <div style="font-size:11.5px; color:#10B981; font-weight:700; margin-top:2px;">
                            Lamanya: {{ $c['durasi_hari'] }} hari kerja
                        </div>
                    </td>
                    <td>
                        <div style="font-size:12px; color:#334155; max-width:240px; line-height:1.4;">
                            {{ $c['alasan'] ?: '-' }}
                        </div>
                        @if ($c['alamat_cuti'] && $c['alamat_cuti'] !== '-')
                            <div style="font-size:11px; color:#94A3B8; margin-top:2px;">
                                Lokasi: {{ $c['alamat_cuti'] }}
                            </div>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if ($c['status'] === 'DISETUJUI')
                            <span class="badge" style="background:#DCFCE7; color:#15803D; border:1px solid #86EFAC; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">
                                DISETUJUI
                            </span>
                        @elseif ($c['status'] === 'PENDING')
                            <span class="badge" style="background:#FEF3C7; color:#B45309; border:1px solid #FCD34D; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">
                                PENDING
                            </span>
                        @else
                            <span class="badge" style="background:#FEE2E2; color:#B91C1C; border:1px solid #FCA5A5; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">
                                DITOLAK
                            </span>
                        @endif
                    </td>
                    <td class="btn-no-print" style="text-align:center;">
                        <div style="display:flex; flex-direction:column; gap:6px; align-items:center;">
                            <!-- Tombol Cetak Fisik Surat Izin Cuti Resmi Dinas -->
                            <a href="{{ route('cuti.surat', $c['id']) }}" class="btn btn-sm btn-outline"
                               style="display:inline-flex; align-items:center; gap:5px; padding:5px 10px; font-size:11.5px; font-weight:600; border-color:#0284C7; color:#0284C7; text-decoration:none;"
                               title="Cetak Surat Izin Cuti Fisik Resmi (Kop Dinas)">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Cetak Surat Cuti
                            </a>

                            <!-- Aksi Approval SDM (Role 1) -->
                            @if (session('simpeg_user.userlevel') === '1' && is_numeric($c['id']))
                                @if ($c['status'] === 'PENDING')
                                    <div style="display:flex; gap:4px;">
                                        <button type="button" onclick="openModalVerifikasiCuti({{ $c['id'] }}, '{{ addslashes($c['nama']) }}', '{{ addslashes($c['jenis']) }}', 'DISETUJUI')"
                                                style="background:#16A34A; color:#fff; border:none; border-radius:5px; padding:4px 8px; font-size:11px; font-weight:600; cursor:pointer;"
                                                title="Setujui permohonan cuti">
                                            ✓ Setujui
                                        </button>
                                        <button type="button" onclick="openModalVerifikasiCuti({{ $c['id'] }}, '{{ addslashes($c['nama']) }}', '{{ addslashes($c['jenis']) }}', 'DITOLAK')"
                                                style="background:#DC2626; color:#fff; border:none; border-radius:5px; padding:4px 8px; font-size:11px; font-weight:600; cursor:pointer;"
                                                title="Tolak permohonan cuti">
                                            ✕ Tolak
                                        </button>
                                    </div>
                                @else
                                    <button type="button" onclick="openModalVerifikasiCuti({{ $c['id'] }}, '{{ addslashes($c['nama']) }}', '{{ addslashes($c['jenis']) }}', '{{ $c['status'] }}', '{{ addslashes($c['catatan_approver'] ?? '') }}')"
                                            style="background:#F1F5F9; color:#475569; border:1px solid #CBD5E1; border-radius:5px; padding:3px 7px; font-size:10.5px; cursor:pointer;"
                                            title="Ubah keputusan / catatan approval">
                                        Edit Status
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:35px 20px;">
                        <div style="color:#64748B; font-weight:600; font-size:14px;">Belum ada data cuti untuk periode tahun {{ $tahun }}.</div>
                        <p style="color:#94A3B8; font-size:12px; margin-top:4px;">Klik tombol "Ajukan Permohonan Cuti" di atas untuk membuat pengajuan baru.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Dialog Pengajuan Cuti Baru -->
<div id="modalAjukanCuti" class="modal-overlay-cuti btn-no-print">
    <div class="modal-box-cuti">
        <div class="modal-header-cuti">
            <div>
                <h3 style="font-size:16px; font-weight:700; color:#0F2A3D; margin:0;">Form Permohonan Izin Cuti Pegawai</h3>
                <div style="font-size:12px; color:#64748B; margin-top:2px;">PERUMDAM Tirta Darma Ayu Kab. Indramayu</div>
            </div>
            <button type="button" onclick="closeModalAjukanCuti()" style="border:none; background:transparent; font-size:20px; color:#94A3B8; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="{{ route('cuti.store') }}" onsubmit="return confirmSubmit(event, 'Ajukan permohonan cuti ini sekarang?', 'Konfirmasi Pengajuan Cuti', 'primary', 'Ya, Ajukan Cuti');">
            @csrf

            <div class="modal-body-cuti">
                @if (session('simpeg_user.userlevel') === '1')
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="pegawai_nik">Pilih Pegawai Pemohon <span style="color:var(--danger);">*</span></label>
                        <select id="pegawai_nik" name="pegawai_nik" required style="width:100%; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach ($pegawaiList as $p)
                                @php
                                    $pNamaFull = trim(($p['nama'] ?? '') . ($p['gelar'] ? ', ' . $p['gelar'] : ''));
                                @endphp
                                <option value="{{ $p['nik'] }}">
                                    {{ $p['nik'] }} - {{ $pNamaFull }} ({{ $p['unit_kerja'] ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="pegawai_nik" value="{{ session('simpeg_user.nik') }}">
                @endif

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="jenis">Jenis Izin Cuti <span style="color:var(--danger);">*</span></label>
                    <select id="jenis" name="jenis" required style="width:100%; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                        <option value="Cuti Tahunan">Cuti Tahunan (Maksimal 12 Hari Kerja)</option>
                        <option value="Cuti Sakit">Cuti Sakit (Disertai Surat Keterangan Dokter)</option>
                        <option value="Cuti Bersalin">Cuti Bersalin (Hamil / Melahirkan)</option>
                        <option value="Cuti Karena Alasan Penting">Cuti Karena Alasan Penting (Keluarga Sakit Keras / Meninggal / Menikah)</option>
                        <option value="Cuti Besar">Cuti Besar (Masa Kerja Lebih Dari 5 Tahun)</option>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai Cuti <span style="color:var(--danger);">*</span></label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ date('Y-m-d') }}" required style="width:100%; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai Cuti <span style="color:var(--danger);">*</span></label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ date('Y-m-d', strtotime('+2 days')) }}" required style="width:100%; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="alasan">Alasan / Keperluan Cuti <span style="color:var(--danger);">*</span></label>
                    <textarea id="alasan" name="alasan" rows="3" placeholder="Tuliskan alasan permohonan cuti secara jelas..." required style="width:100%; padding:8px 12px; border:1px solid #CBD5E1; border-radius:8px;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="alamat_cuti">Alamat Selama Menjalankan Cuti</label>
                    <input type="text" id="alamat_cuti" name="alamat_cuti" placeholder="Contoh: Jl. Diponegoro No. 12, Indramayu" style="width:100%; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                </div>

                <div class="form-group">
                    <label for="no_telp">Nomor Telepon / WhatsApp Darurat</label>
                    <input type="text" id="no_telp" name="no_telp" placeholder="Contoh: 081234567890" style="width:100%; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                </div>
            </div>

            <div class="modal-footer-cuti">
                <button type="button" class="btn btn-secondary" onclick="closeModalAjukanCuti()">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim Permohonan Cuti</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog Verifikasi Approval Cuti (Role 1 SDM) -->
@if (session('simpeg_user.userlevel') === '1')
<div id="modalVerifikasiCuti" class="modal-overlay-cuti btn-no-print">
    <div class="modal-box-cuti" style="max-width:500px;">
        <div class="modal-header-cuti">
            <div>
                <h3 id="verifModalTitle" style="font-size:16px; font-weight:700; color:#0F2A3D; margin:0;">Verifikasi Keputusan Cuti</h3>
                <div style="font-size:12px; color:#64748B; margin-top:2px;">Bagian Sumber Daya Manusia (SDM)</div>
            </div>
            <button type="button" onclick="closeModalVerifikasiCuti()" style="border:none; background:transparent; font-size:20px; color:#94A3B8; cursor:pointer;">&times;</button>
        </div>

        <form id="verifCutiForm" method="POST" action="">
            @csrf
            @method('PUT')
            <input type="hidden" id="verif_status" name="status" value="DISETUJUI">

            <div class="modal-body-cuti">
                <div style="margin-bottom:14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:12px 14px;">
                    <div style="font-size:11.5px; color:#64748B; text-transform:uppercase; font-weight:700;">Pegawai Pemohon:</div>
                    <div id="verifPegawaiNama" style="font-size:14px; font-weight:700; color:#0F2A3D; margin-top:2px;">-</div>
                    <div id="verifCutiJenis" style="font-size:12.5px; color:#0284C7; font-weight:600; margin-top:2px;">-</div>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label style="font-size:12.5px; font-weight:600; color:#0F2A3D; display:block; margin-bottom:6px;">Keputusan SDM <span style="color:var(--danger);">*</span></label>
                    <div style="display:flex; gap:16px;">
                        <label style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; cursor:pointer; color:#15803D;">
                            <input type="radio" id="radioSetujui" name="verif_radio" value="DISETUJUI" checked onchange="handleVerifRadioChange(this.value)">
                            ✓ Setujui Permohonan
                        </label>
                        <label style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; cursor:pointer; color:#B91C1C;">
                            <input type="radio" id="radioTolak" name="verif_radio" value="DITOLAK" onchange="handleVerifRadioChange(this.value)">
                            ✕ Tolak Permohonan
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="verif_catatan" style="font-size:12.5px; font-weight:600; color:#0F2A3D; display:block; margin-bottom:6px;">
                        Catatan Approver / Alasan <span style="font-size:11.5px; color:#94A3B8; font-weight:normal;">(langsung masuk ke notifikasi pegawai)</span>
                    </label>
                    <textarea id="verif_catatan" name="catatan_approver" rows="3"
                              placeholder="Contoh: Disetujui sesuai hak cuti tahunan, pekerjaan didelegasikan ke rekan tim..."
                              style="width:100%; padding:8px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px;"></textarea>
                </div>
            </div>

            <div class="modal-footer-cuti">
                <button type="button" class="btn btn-secondary" onclick="closeModalVerifikasiCuti()">Batal</button>
                <button type="submit" id="btnSubmitVerif" class="btn btn-primary" style="background:#16A34A; border-color:#16A34A;">
                    Simpan & Kirim Notifikasi
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
function openModalAjukanCuti() {
    document.getElementById('modalAjukanCuti').style.display = 'flex';
}
function closeModalAjukanCuti() {
    document.getElementById('modalAjukanCuti').style.display = 'none';
}

function openModalVerifikasiCuti(id, nama, jenis, defaultStatus = 'DISETUJUI', defaultCatatan = '') {
    const modal = document.getElementById('modalVerifikasiCuti');
    if (!modal) return;

    const form = document.getElementById('verifCutiForm');
    form.action = `/cuti/${id}/status`;

    document.getElementById('verifPegawaiNama').textContent = nama;
    document.getElementById('verifCutiJenis').textContent = jenis;
    document.getElementById('verif_catatan').value = defaultCatatan;

    const radioSetuju = document.getElementById('radioSetujui');
    const radioTolak = document.getElementById('radioTolak');

    if (defaultStatus === 'DITOLAK') {
        radioTolak.checked = true;
        handleVerifRadioChange('DITOLAK');
    } else {
        radioSetuju.checked = true;
        handleVerifRadioChange('DISETUJUI');
    }

    modal.style.display = 'flex';
}

function closeModalVerifikasiCuti() {
    const modal = document.getElementById('modalVerifikasiCuti');
    if (modal) modal.style.display = 'none';
}

function handleVerifRadioChange(val) {
    document.getElementById('verif_status').value = val;
    const btn = document.getElementById('btnSubmitVerif');
    if (val === 'DITOLAK') {
        btn.style.background = '#DC2626';
        btn.style.borderColor = '#DC2626';
        btn.textContent = 'Tolak & Kirim Notifikasi';
        if (!document.getElementById('verif_catatan').value) {
            document.getElementById('verif_catatan').placeholder = 'Tuliskan alasan kedinasan penolakan cuti...';
        }
    } else {
        btn.style.background = '#16A34A';
        btn.style.borderColor = '#16A34A';
        btn.textContent = 'Setujui & Terbitkan Notifikasi';
        if (!document.getElementById('verif_catatan').value) {
            document.getElementById('verif_catatan').placeholder = 'Disetujui sesuai hak cuti tahunan kedinasan.';
        }
    }
}

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModalAjukanCuti();
        closeModalVerifikasiCuti();
    }
});
</script>
@endsection
