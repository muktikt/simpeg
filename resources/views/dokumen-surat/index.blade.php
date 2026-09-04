@extends('layouts.app')

@section('title', 'Unggah Dokumen Surat - Pengaturan Umum')

@section('content')
<style>
/* Aesthetic styles for Dokumen Surat SDM */
.ds-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.ds-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 6px 0;
}
.ds-subtitle {
    font-size: 14px;
    color: #64748B;
    margin: 0;
}

/* Stats Cards */
.ds-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 18px;
    margin-bottom: 26px;
}
.ds-stat-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 4px 14px rgba(15, 42, 61, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.ds-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(15, 42, 61, 0.08);
}
.ds-stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ds-stat-icon.blue {
    background: linear-gradient(135deg, #E0F2FE, #BAE6FD);
    color: #0284C7;
}
.ds-stat-icon.green {
    background: linear-gradient(135deg, #DCFCE7, #BBF7D0);
    color: #16A34A;
}
.ds-stat-icon.purple {
    background: linear-gradient(135deg, #F3E8FF, #E9D5FF);
    color: #9333EA;
}
.ds-stat-val {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 26px;
    font-weight: 700;
    color: #0F2A3D;
    line-height: 1.1;
}
.ds-stat-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #64748B;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filter & Action Toolbar */
.ds-toolbar {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    box-shadow: 0 2px 8px rgba(15, 42, 61, 0.03);
}
.ds-search-form {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 280px;
}
.ds-search-input-wrap {
    position: relative;
    flex: 1;
    max-width: 420px;
}
.ds-search-input-wrap svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
}
.ds-search-input {
    width: 100%;
    padding: 10px 14px 10px 42px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    background: #F8FAFC;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.ds-search-input:focus {
    background: #ffffff;
    border-color: #0284C7;
    outline: none;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
.ds-select {
    padding: 10px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13px;
    background: #F8FAFC;
    color: #334155;
    font-weight: 500;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}
.ds-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #0D2C6E, #184BA0);
    color: #ffffff;
    padding: 11px 20px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(13, 44, 110, 0.25);
    transition: all 0.2s;
}
.ds-btn-primary:hover {
    background: linear-gradient(135deg, #091F4E, #133D82);
    box-shadow: 0 6px 16px rgba(13, 44, 110, 0.35);
    color: #ffffff;
    transform: translateY(-1px);
}

/* Table Card */
.ds-table-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15, 42, 61, 0.04);
}
.ds-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Inter', sans-serif;
    font-size: 13.5px;
}
.ds-table th {
    background: #F8FAFC;
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 18px;
    border-bottom: 1px solid #E2E8F0;
    text-align: left;
}
.ds-table td {
    padding: 16px 18px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: top;
    color: #1E293B;
}
.ds-table tr:hover td {
    background: #F8FAFC;
}

/* User identity cell */
.ds-user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ds-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0D2C6E, #2E86AB);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}
.ds-user-name {
    font-weight: 700;
    color: #0F2A3D;
    font-size: 14px;
}
.ds-user-nik {
    font-size: 12px;
    color: #64748B;
    font-family: 'IBM Plex Mono', monospace;
    margin-top: 2px;
}

/* Badges */
.ds-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
}
.ds-badge-sk {
    background: #ECFDF5;
    color: #047857;
    border: 1px solid #A7F3D0;
}
.ds-badge-diklat {
    background: #F5F3FF;
    color: #6D28D9;
    border: 1px solid #DDD6FE;
}
.ds-badge-empty {
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #E2E8F0;
}

/* Action Buttons inside Table */
.ds-btn-action {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: all 0.15s ease;
}
.ds-btn-view {
    background: #F8FAFC;
    border-color: #CBD5E1;
    color: #334155;
}
.ds-btn-view:hover {
    background: #E2E8F0;
    color: #0F172A;
}
.ds-btn-download {
    background: #E0F2FE;
    border-color: #BAE6FD;
    color: #0284C7;
}
.ds-btn-download:hover {
    background: #BAE6FD;
    color: #0369A1;
}
.ds-btn-upload-sm {
    background: #FEF3C7;
    border-color: #FDE68A;
    color: #B45309;
}
.ds-btn-upload-sm:hover {
    background: #FDE68A;
    color: #92400E;
}
.ds-btn-delete-sm {
    background: #FEE2E2;
    border-color: #FECACA;
    color: #DC2626;
}
.ds-btn-delete-sm:hover {
    background: #FECACA;
    color: #B91C1C;
}

/* Modal styling */
.ds-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.6) !important;
    backdrop-filter: blur(6px) !important;
    -webkit-backdrop-filter: blur(6px) !important;
    z-index: 999999 !important;
    display: none;
    align-items: center !important;
    justify-content: center !important;
    padding: 24px 16px !important;
    overflow-y: auto !important;
    box-sizing: border-box !important;
}
.ds-modal-box {
    background: #ffffff !important;
    width: 100% !important;
    max-width: 540px !important;
    margin: auto !important;
    border-radius: 20px !important;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35) !important;
    position: relative !important;
    overflow: visible !important;
    animation: dsModalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) both !important;
    z-index: 1000000 !important;
}
@keyframes dsModalIn {
    from { opacity: 0; transform: scale(0.95) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.ds-modal-header {
    padding: 20px 24px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ds-modal-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0;
}
.ds-modal-body {
    padding: 24px;
}
.ds-form-group {
    margin-bottom: 16px;
}
.ds-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.ds-form-input, .ds-form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    font-family: 'Inter', sans-serif;
    background: #ffffff;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.ds-form-input:focus, .ds-form-select:focus {
    border-color: #0284C7;
    outline: none;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
.ds-file-drop {
    border: 2px dashed #CBD5E1;
    background: #F8FAFC;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.ds-file-drop:hover {
    border-color: #0284C7;
    background: #F0F9FF;
}
</style>

<div class="ds-header">
    <div>
        <h1 class="ds-title">Unggah Dokumen Surat (SDM)</h1>
        <p class="ds-subtitle">Kelola penerbitan & pengunggahan Surat Kerja (SK) dan Surat Diklat/Pelatihan resmi untuk seluruh pegawai.</p>
    </div>
    <button type="button" class="ds-btn-primary" onclick="openUploadModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M12 5v14M5 12h14"/></svg>
        Unggah Dokumen Baru
    </button>
</div>

<!-- Statistik Dokumen -->
<div class="ds-stats-grid">
    <div class="ds-stat-card">
        <div class="ds-stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="ds-stat-val">{{ $totalPegawai }}</div>
            <div class="ds-stat-label">Total Pegawai Terdaftar</div>
        </div>
    </div>
    <div class="ds-stat-card">
        <div class="ds-stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div>
            <div class="ds-stat-val">{{ $totalSk }}</div>
            <div class="ds-stat-label">Surat Kerja (SK) Terbit</div>
        </div>
    </div>
    <div class="ds-stat-card">
        <div class="ds-stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        </div>
        <div>
            <div class="ds-stat-val">{{ $totalDiklat }}</div>
            <div class="ds-stat-label">Surat Diklat / Pelatihan</div>
        </div>
    </div>
</div>

<!-- Toolbar Pencarian & Filter -->
<div class="ds-toolbar">
    <form method="GET" action="{{ route('dokumen-surat.index') }}" class="ds-search-form">
        <div class="ds-search-input-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari nama pegawai, NIK, unit kerja, nomor surat..." class="ds-search-input">
        </div>
        <select name="filter" class="ds-select" onchange="this.form.submit()">
            <option value="all" {{ $filterJenis === 'all' ? 'selected' : '' }}>Semua Dokumen</option>
            <option value="sk_only" {{ $filterJenis === 'sk_only' ? 'selected' : '' }}>Hanya yang Memiliki SK</option>
            <option value="diklat_only" {{ $filterJenis === 'diklat_only' ? 'selected' : '' }}>Hanya yang Memiliki Diklat</option>
        </select>
        <button type="submit" class="ds-btn-action ds-btn-view" style="padding:10px 16px;">Cari</button>
        @if ($keyword !== '' || $filterJenis !== 'all')
            <a href="{{ route('dokumen-surat.index') }}" class="ds-btn-action ds-btn-delete-sm" style="padding:10px 14px;">Reset</a>
        @endif
    </form>
</div>

<!-- Tabel Daftar Dokumen Pegawai -->
<div class="ds-table-card">
    <table class="ds-table">
        <thead>
            <tr>
                <th style="width: 250px;">Pegawai</th>
                <th style="width: 180px;">Unit Kerja / Jabatan</th>
                <th>Surat Kerja (SK)</th>
                <th>Surat Diklat / Pelatihan</th>
                <th style="width: 110px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pegawaiList as $p)
                @php
                    $sk = $p['surat_kerja'] ?? null;
                    $diklat = $p['surat_diklat'] ?? null;
                    $inisial = collect(explode(' ', $p['nama'] ?? 'P'))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                @endphp
                <tr>
                    <td>
                        <div class="ds-user-cell">
                            <div class="ds-user-avatar">{{ strtoupper($inisial) }}</div>
                            <div>
                                <div class="ds-user-name">{{ $p['nama'] }}</div>
                                <div class="ds-user-nik">NIK: {{ $p['nik'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #334155; font-size: 13px;">{{ $p['jabatan'] ?? '-' }}</div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 2px;">{{ $p['unit_kerja'] ?? '-' }}</div>
                    </td>
                    <td>
                        @if ($sk && !empty($sk['nomor']))
                            <div style="margin-bottom: 6px;">
                                <span class="ds-badge ds-badge-sk">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    SK Terbit: {{ $sk['tgl_terbit'] ?? '-' }}
                                </span>
                            </div>
                            <div style="font-weight: 700; color: #0F2A3D; font-size: 13px;">{{ $sk['judul'] ?? 'Surat Keputusan' }}</div>
                            <div style="font-size: 12px; color: #64748B; font-family: 'IBM Plex Mono', monospace; margin: 2px 0 8px 0;">No: {{ $sk['nomor'] }}</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button type="button" class="ds-btn-action ds-btn-view" onclick="viewFileModal('{{ addslashes($sk['judul'] ?? 'Surat Kerja') }}', '{{ addslashes($sk['file_name'] ?? 'SK.pdf') }}', '{{ addslashes($sk['file_url'] ?? '#') }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View File
                                </button>
                                @if (!empty($sk['file_url']) && $sk['file_url'] !== '#')
                                    <a href="{{ $sk['file_url'] }}" target="_blank" class="ds-btn-action ds-btn-download" style="text-decoration:none;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Download
                                    </a>
                                @else
                                    <button type="button" class="ds-btn-action ds-btn-download" onclick="showCustomAlert('Dokumen tersimpan sebagai draft resmi.', 'Informasi Berkas', 'info')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Draft
                                    </button>
                                @endif
                                <button type="button" class="ds-btn-action ds-btn-upload-sm" onclick="openUploadModal({{ $p['id'] }}, 'surat_kerja', '{{ addslashes($sk['nomor']) }}', '{{ addslashes($sk['judul']) }}', '{{ $sk['tgl_terbit'] }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Ganti SK
                                </button>
                            </div>
                        @else
                            <div style="margin-bottom: 8px;">
                                <span class="ds-badge ds-badge-empty">Belum ada SK</span>
                            </div>
                            <button type="button" class="ds-btn-action ds-btn-upload-sm" onclick="openUploadModal({{ $p['id'] }}, 'surat_kerja', '', '', '')">
                                + Unggah SK
                            </button>
                        @endif
                    </td>
                    <td>
                        @if ($diklat && !empty($diklat['nomor']))
                            <div style="margin-bottom: 6px;">
                                <span class="ds-badge ds-badge-diklat">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                                    Diklat Terbit: {{ $diklat['tgl_terbit'] ?? '-' }}
                                </span>
                            </div>
                            <div style="font-weight: 700; color: #0F2A3D; font-size: 13px;">{{ $diklat['judul'] ?? 'Sertifikat Diklat' }}</div>
                            <div style="font-size: 12px; color: #64748B; font-family: 'IBM Plex Mono', monospace; margin: 2px 0 8px 0;">No: {{ $diklat['nomor'] }}</div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button type="button" class="ds-btn-action ds-btn-view" onclick="viewFileModal('{{ addslashes($diklat['judul'] ?? 'Sertifikat Diklat') }}', '{{ addslashes($diklat['file_name'] ?? 'Diklat.pdf') }}', '{{ addslashes($diklat['file_url'] ?? '#') }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View File
                                </button>
                                @if (!empty($diklat['file_url']) && $diklat['file_url'] !== '#')
                                    <a href="{{ $diklat['file_url'] }}" target="_blank" class="ds-btn-action ds-btn-download" style="text-decoration:none;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Download
                                    </a>
                                @else
                                    <button type="button" class="ds-btn-action ds-btn-download" onclick="showCustomAlert('Dokumen tersimpan sebagai draft resmi.', 'Informasi Berkas', 'info')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Draft
                                    </button>
                                @endif
                                <button type="button" class="ds-btn-action ds-btn-upload-sm" onclick="openUploadModal({{ $p['id'] }}, 'surat_diklat', '{{ addslashes($diklat['nomor']) }}', '{{ addslashes($diklat['judul']) }}', '{{ $diklat['tgl_terbit'] }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Ganti Diklat
                                </button>
                            </div>
                        @else
                            <div style="margin-bottom: 8px;">
                                <span class="ds-badge ds-badge-empty">Belum ada Diklat</span>
                            </div>
                            <button type="button" class="ds-btn-action ds-btn-upload-sm" onclick="openUploadModal({{ $p['id'] }}, 'surat_diklat', '', '', '')">
                                + Unggah Diklat
                            </button>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="ds-btn-action ds-btn-primary" style="padding: 7px 12px; font-size: 12px;" onclick="openUploadModal({{ $p['id'] }})">
                            + Unggah
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px 20px; color: #64748B;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="margin-bottom: 8px; color: #94A3B8;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <div style="font-weight: 600; color: #334155;">Tidak ada data pegawai yang cocok.</div>
                        <div style="font-size: 12.5px; margin-top: 4px;">Coba gunakan kata kunci pencarian yang lain.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@section('modals')
<!-- Modal Upload Dokumen SDM -->
<div id="ds-modal-upload" class="ds-modal-overlay">
    <div class="ds-modal-box">
        <div class="ds-modal-header">
            <h3 class="ds-modal-title" id="upload-modal-title">Unggah Dokumen Pegawai (SDM)</h3>
            <button type="button" onclick="closeUploadModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748B;">&times;</button>
        </div>
        <form method="POST" action="{{ route('dokumen-surat.store') }}" enctype="multipart/form-data" class="ds-modal-body" id="upload-dokumen-form" onsubmit="return handleDokumenSubmit(event, this)">
            @csrf
            <div class="ds-form-group">
                <label class="ds-form-label">Pilih Pegawai <span style="color:#DC2626;">*</span></label>
                <select name="pegawai_id" id="modal-pegawai-id" class="ds-form-select">
                    <option value="">-- Cari / Pilih Pegawai --</option>
                    @foreach ($allPegawaiOptions as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['nik'] }} - {{ $opt['nama'] }} ({{ $opt['jabatan'] }})</option>
                    @endforeach
                </select>
            </div>

            <div class="ds-form-group">
                <label class="ds-form-label">Jenis Dokumen <span style="color:#DC2626;">*</span></label>
                <select name="jenis_dokumen" id="modal-jenis-dokumen" required class="ds-form-select">
                    <option value="surat_kerja">Surat Kerja (SK Pegawai)</option>
                    <option value="surat_diklat">Surat Diklat / Pelatihan</option>
                </select>
            </div>

            <div class="ds-form-group">
                <label class="ds-form-label">Nomor Surat / Sertifikat <span style="color:#DC2626;">*</span></label>
                <input type="text" name="nomor" id="modal-nomor" required placeholder="Contoh: SK/SDM/2024/001" class="ds-form-input">
            </div>

            <div class="ds-form-group">
                <label class="ds-form-label">Judul / Keterangan Dokumen <span style="color:#DC2626;">*</span></label>
                <input type="text" name="judul" id="modal-judul" required placeholder="Contoh: SK Pengangkatan Pegawai Tetap" class="ds-form-input">
            </div>

            <div class="ds-form-group">
                <label class="ds-form-label">Tanggal Terbit <span style="color:#DC2626;">*</span></label>
                <input type="date" name="tgl_terbit" id="modal-tgl-terbit" required class="ds-form-input" value="{{ date('Y-m-d') }}">
            </div>

            <div class="ds-form-group">
                <label class="ds-form-label">Lampiran Berkas (PDF / Gambar) <span style="color:#64748B; font-weight:normal;">(Maks 15MB)</span></label>
                <div class="ds-file-drop" onclick="document.getElementById('modal-file-input').click()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="32" height="32" style="color:#0284C7; margin-bottom:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <div style="font-weight:600; color:#334155; font-size:13px;" id="file-drop-label">Klik untuk memilih file dokumen (PDF/JPG/PNG)</div>
                    <div style="font-size:11.5px; color:#64748B; margin-top:2px;">Format didukung: .pdf, .jpg, .jpeg, .png, .webp, .doc, .docx</div>
                </div>
                <input type="file" name="file" id="modal-file-input" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" style="display:none;" onchange="handleFileSelected(this)">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #E2E8F0;">
                <button type="button" class="ds-btn-action ds-btn-view" style="padding:10px 18px;" onclick="closeUploadModal()">Batal</button>
                <button type="submit" id="btn-submit-dokumen" class="ds-btn-primary" style="padding:10px 22px;">Simpan & Unggah</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View File Preview -->
<div id="ds-modal-view-file" class="ds-modal-overlay">
    <div class="ds-modal-box" style="max-width:600px;">
        <div class="ds-modal-header">
            <h3 class="ds-modal-title" id="view-file-title">Preview Dokumen</h3>
            <button type="button" onclick="closeViewModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748B;">&times;</button>
        </div>
        <div class="ds-modal-body" style="padding:28px 24px;">
            <div style="background:#F8FAFC; border:2px dashed #CBD5E1; padding:36px 20px; text-align:center; border-radius:14px; margin-bottom:20px;">
                <div style="width:64px; height:64px; border-radius:16px; background:#E0F2FE; color:#0284C7; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="34" height="34"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h4 style="margin:0 0 6px 0; font-size:15px; color:#0F2A3D; font-weight:700;" id="view-file-name">Dokumen_Surat.pdf</h4>
                <p style="font-size:12.5px; color:#64748B; margin:0;">File PDF / Gambar telah diverifikasi resmi oleh Admin SDM</p>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <button type="button" class="ds-btn-action ds-btn-view" style="padding:10px 18px;" onclick="closeViewModal()">Tutup</button>
                <button type="button" id="view-modal-action-btn" class="ds-btn-primary" style="padding:10px 20px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Buka / Unduh File
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentViewFileUrl = '#';

function openUploadModal(pegawaiId = null, jenis = 'surat_kerja', nomor = '', judul = '', tglTerbit = '') {
    const modal = document.getElementById('ds-modal-upload');
    const select = document.getElementById('modal-pegawai-id');
    
    if (pegawaiId) {
        select.value = pegawaiId;
    } else {
        select.value = '';
    }
    select.dispatchEvent(new Event('change', { bubbles: true }));
    
    document.getElementById('modal-jenis-dokumen').value = jenis || 'surat_kerja';
    document.getElementById('modal-nomor').value = nomor || '';
    document.getElementById('modal-judul').value = judul || (jenis === 'surat_kerja' ? 'Surat Keputusan Pengangkatan Pegawai' : 'Sertifikat Diklat Manajemen Pegawai');
    document.getElementById('modal-tgl-terbit').value = tglTerbit || '{{ date('Y-m-d') }}';
    document.getElementById('file-drop-label').textContent = 'Klik untuk memilih file dokumen (PDF/JPG/PNG)';
    document.getElementById('modal-file-input').value = '';

    const submitBtn = document.getElementById('btn-submit-dokumen');
    if (submitBtn) {
        submitBtn.innerHTML = `Simpan & Unggah`;
        submitBtn.style.pointerEvents = 'auto';
        submitBtn.style.opacity = '1';
        submitBtn.disabled = false;
    }

    modal.style.display = 'flex';
}

function closeUploadModal() {
    document.getElementById('ds-modal-upload').style.display = 'none';
}

function handleDokumenSubmit(e, form) {
    const pegawaiSelect = document.getElementById('modal-pegawai-id');
    if (!pegawaiSelect.value) {
        e.preventDefault();
        if (typeof showCustomAlert === 'function') {
            showCustomAlert('Silakan pilih pegawai terlebih dahulu sebelum mengunggah.', 'Peringatan', 'warning');
        } else {
            alert('Silakan pilih pegawai terlebih dahulu.');
        }
        return false;
    }
    const nomor = document.getElementById('modal-nomor').value.trim();
    if (!nomor) {
        e.preventDefault();
        if (typeof showCustomAlert === 'function') {
            showCustomAlert('Nomor surat / sertifikat wajib diisi.', 'Peringatan', 'warning');
        } else {
            alert('Nomor surat / sertifikat wajib diisi.');
        }
        return false;
    }
    const judul = document.getElementById('modal-judul').value.trim();
    if (!judul) {
        e.preventDefault();
        if (typeof showCustomAlert === 'function') {
            showCustomAlert('Judul / keterangan dokumen wajib diisi.', 'Peringatan', 'warning');
        } else {
            alert('Judul / keterangan dokumen wajib diisi.');
        }
        return false;
    }

    const submitBtn = document.getElementById('btn-submit-dokumen');
    if (submitBtn) {
        submitBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="display:inline-block; vertical-align:middle; margin-right:6px;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg> Mengunggah...`;
        submitBtn.style.pointerEvents = 'none';
        submitBtn.style.opacity = '0.85';
    }
    return true;
}

function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        document.getElementById('file-drop-label').textContent = 'File Terpilih: ' + input.files[0].name;
    }
}

function viewFileModal(judul, filename, fileUrl = '#') {
    document.getElementById('view-file-title').textContent = 'Preview: ' + judul;
    document.getElementById('view-file-name').textContent = filename;
    currentViewFileUrl = fileUrl;
    const downloadBtn = document.getElementById('view-modal-action-btn');
    if (fileUrl && fileUrl !== '#') {
        downloadBtn.onclick = function() { window.open(fileUrl, '_blank'); };
        downloadBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Buka / Unduh File`;
    } else {
        downloadBtn.onclick = function() { 
            if (typeof showCustomAlert === 'function') {
                showCustomAlert('Dokumen resmi tercatat di sistem.', 'Informasi Berkas', 'info');
            } else {
                alert('Dokumen resmi tercatat di sistem.');
            }
        };
        downloadBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Berkas Resmi`;
    }
    document.getElementById('ds-modal-view-file').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('ds-modal-view-file').style.display = 'none';
}

// Close modals when clicking backdrop
document.getElementById('ds-modal-upload').addEventListener('click', function(e) {
    if (e.target === this) closeUploadModal();
});
document.getElementById('ds-modal-view-file').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});
</script>
@endsection
