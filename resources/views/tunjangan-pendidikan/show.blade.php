@extends('layouts.app')

@section('title', 'Slip Tunjangan Pendidikan - ' . $item['nama'])

@section('content')
<div class="page-head no-print">
    <div class="breadcrumb">Home / SDM / Tunjangan Pendidikan / Slip</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px;">Slip Tunjangan Pendidikan — {{ $item['nama'] }}</h1>
            <p style="margin:0; font-size:13px; color:var(--text-muted);">
                NIK: <strong>{{ $item['nik'] }}</strong> &middot; Periode: <strong>{{ $item['bulan_label'] }}</strong> &middot; Unit: {{ $item['unit_kerja'] }}
            </p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip
            </button>
            <a href="{{ route('tunjangan-pendidikan.create') }}" class="btn btn-outline" style="font-weight:600;">+ Input Baru</a>
            <a href="{{ route('tunjangan-pendidikan.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
        </div>
    </div>
</div>

<style>
/* ===================================================
   OFFICIAL FORMAT GAMBAR 1: DAFTAR TUNJANGAN PENDIDIKAN
   =================================================== */
.official-slip-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    margin: 16px 0 32px;
}
.official-slip-card {
    background: #ffffff;
    color: #000000;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.45;
    padding: 28px 36px;
    width: 100%;
    max-width: 820px;
    box-sizing: border-box;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
    border: 1px solid #D1D5DB;
    border-radius: 6px;
}
.official-slip-card * {
    box-sizing: border-box;
}
.official-slip-header {
    text-align: center;
    margin-bottom: 22px;
    font-weight: 700;
}
.official-slip-header .company-title {
    font-size: 12.5px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.official-slip-header .doc-title {
    font-size: 12.5px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 3px;
}
.official-slip-meta {
    margin-bottom: 12px;
}
.slip-meta-row {
    display: flex;
    align-items: flex-start;
    min-height: 19px;
    line-height: 19px;
    font-size: 11px;
    text-transform: uppercase;
}
.slip-meta-row .slip-label {
    width: 130px;
    flex-shrink: 0;
    white-space: nowrap;
}
.slip-colon {
    width: 14px;
    text-align: center;
    flex-shrink: 0;
}
.slip-val-meta {
    flex: 1;
    min-width: 0;
    white-space: normal;
    word-break: break-word;
}

.official-slip-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 32px;
    align-items: start;
}
.slip-col-left .slip-label {
    width: 180px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.slip-col-right .slip-label {
    width: 230px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.slip-row {
    display: flex;
    align-items: flex-start;
    min-height: 19px;
    line-height: 19px;
    font-size: 11px;
    text-transform: uppercase;
}
.slip-val-num {
    width: 90px;
    text-align: right;
    flex-shrink: 0;
    font-variant-numeric: tabular-nums;
    font-family: Arial, Helvetica, sans-serif;
    letter-spacing: 0.2px;
}
.slip-row-bold {
    font-weight: 700;
}
.slip-disclaimer {
    margin-top: 14px;
    font-size: 9.5px;
    font-style: italic;
    line-height: 1.45;
    color: #111827;
    text-transform: uppercase;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 15mm 10mm 15mm;
    }
    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .sidebar, .topbar, .page-head, .breadcrumb, .toolbar, .form-actions, .no-print, button, .btn,
    .main-sidebar, .main-header, .navbar, nav, header, aside, .app-sidebar, .app-header,
    .flash-success, .flash-error, .modal-overlay, #global-custom-modal {
        display: none !important;
    }
    .layout, .main, .main-content, .content, .container, main, .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .official-slip-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
    }
    .official-slip-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="official-slip-wrapper">
    <div class="official-slip-card">
        <!-- HEADER KOP RESMI PERUMDAM (CENTERED) -->
        <div class="official-slip-header">
            <div class="company-title">PERUMDAM TIRTA DARMA AYU KAB. INDRAMAYU</div>
            <div class="doc-title">DAFTAR TUNJANGAN PENDIDIKAN &amp; POTONGAN BULAN : {{ strtoupper($item['bulan_label']) }}</div>
        </div>

        <!-- METADATA PEGAWAI (NIK, NAMA, GOLONGAN, UNIT KERJA, JABATAN) -->
        <div class="official-slip-meta">
            <div class="slip-meta-row">
                <span class="slip-label">NIK</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ $item['nik'] }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">NAMA</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ strtoupper($item['nama']) }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">GOLONGAN</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ strtoupper($item['golongan_detail'] ?: $item['golongan']) }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">UNIT KERJA</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ strtoupper($item['unit_kerja']) }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">JABATAN</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ strtoupper($item['jabatan']) }}</span>
            </div>
        </div>

        <!-- 2 KOLOM SESUAI GAMBAR 1 -->
        <div class="official-slip-body">
            <!-- KOLOM KIRI (PENDAPATAN) -->
            <div class="slip-col-left">
                <div class="slip-row">
                    <span class="slip-label">GAPOK</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['gapok'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">TUNJANGAN ISTRI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['tunjangan_istri'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">TUNJANGAN ANAK</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['tunjangan_anak'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH PENDAPATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['jumlah_pendapatan'], 0, ',', '.') }}</span>
                </div>

                <div class="slip-disclaimer">
                    SLIP GAJI INI SUDAH DISETUJUI OLEH DIREKSI NAMUN SEGALA BENTUK PENYALAH GUNAAN SLIP GAJI BUKAN MENJADI TANGGUNG JAWAB PERUMDAM TIRTA DARMA AYU.
                </div>
            </div>

            <!-- KOLOM KANAN (POTONGAN NON-INSENTIF & JUMLAH INSENTIF DITERIMA) -->
            <div class="slip-col-right">
                <div class="slip-row">
                    <span class="slip-label">POTONGAN KOPERASI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_koperasi'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN KAS</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_kas'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH POTONGAN NON-INSENTIF</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['jumlah_potongan'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row slip-row-bold" style="margin-top:2px;">
                    <span class="slip-label">JUMLAH INSENTIF DITERIMA</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['diterima'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
