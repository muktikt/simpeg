@extends('layouts.app')

@section('title', 'Slip Insentif Pendidikan - ' . $item['nama'])

@section('content')
<div class="page-head no-print">
    <div class="breadcrumb">Home / SDM / Insentif Pendidikan / Slip</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px;">Slip Insentif Pendidikan — {{ $item['nama'] }}</h1>
            <p style="margin:0; font-size:13px; color:var(--text-muted);">
                NIK: <strong>{{ $item['nik'] }}</strong> &middot; Periode: <strong>{{ $item['periode'] }}</strong> &middot; Unit: {{ $item['unit_kerja'] }}
            </p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip
            </button>
            <a href="{{ route('insentif-pendidikan.create') }}" class="btn btn-outline" style="font-weight:600;">+ Input Baru</a>
            <a href="{{ route('insentif-pendidikan.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
        </div>
    </div>
</div>

<style>
/* ===================================================
   OFFICIAL FORMAT GAMBAR 2: DAFTAR INSENTIF & POTONGAN
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
    margin-bottom: 20px;
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
    margin-bottom: 6px;
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
    column-gap: 28px;
    align-items: start;
}
.slip-col-left .slip-label {
    width: 205px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.slip-col-right .slip-label {
    width: 235px;
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
.slip-row-net {
    font-weight: 700;
    margin-top: 2px;
}
.slip-disclaimer {
    margin-top: 22px;
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
            <div class="doc-title">{{ strtoupper($item['judul'] ?? ('DAFTAR INSENTIF & POTONGAN BULAN : ' . $item['periode'])) }}</div>
        </div>

        <!-- METADATA PEGAWAI (NIK, NAMA, GOLONGAN, UNIT KERJA) -->
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
        </div>

        <!-- 2 KOLOM SESUAI GAMBAR 2 -->
        <div class="official-slip-body">
            <!-- KOLOM KIRI (JABATAN + 12 INSENTIF + JUMLAH INSENTIF + DISCLAIMER) -->
            <div class="slip-col-left">
                <div class="slip-row" style="margin-bottom:12px;">
                    <span class="slip-label">JABATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-meta">{{ strtoupper($item['jabatan']) }}</span>
                </div>

                <!-- 12 Komponen Insentif -->
                <div class="slip-row">
                    <span class="slip-label">INSENTIF JABATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_jabatan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF PRESTASI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_prestasi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF TRANSPORTASI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_transportasi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF PANGAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_pangan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF BPJS KESEHATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_bpjs_kesehatan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF PERUMAHAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_perumahan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF BPJS TENAGA KERJA</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_bpjs_tenaga_kerja'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF PERUSAHAAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_perusahaan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">LEMBUR</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['lembur'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF PAJAK</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_pajak'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF AIR MINUM</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_air_minum'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">INSENTIF KOMUNIKASI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['insentif_komunikasi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH INSENTIF</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['total_insentif'], 0, ',', '.') }}</span>
                </div>

                <!-- Bottom Disclaimer (Gambar 2 Asli) -->
                <div class="slip-disclaimer">
                    SLIP INSENTIF INI SUDAH DISETUJUI OLEH DIREKSI<br>
                    NAMUN SEGALA BENTUK PENYALAH GUNAAN SLIP<br>
                    BUKAN MENJADI TANGGUNG JAWAB KAMI.
                </div>
            </div>

            <!-- KOLOM KANAN (POTONGAN INSENTIF 9 ITEM + POTONGAN NON-INSENTIF 10 ITEM + DITERIMA) -->
            <div class="slip-col-right">
                <!-- 9 Item Potongan Insentif -->
                <div class="slip-row">
                    <span class="slip-label">POTONGAN SANKSI PERUSAHAAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_sanksi_perusahaan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN PMI / LAIN-LAIN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_pmi_lain'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN DAPENMA</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_dapenma'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN BPJS TENAGA KERJA</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_bpjs_tenaga_kerja'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN PERUMAHAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_perumahan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN INSENTIF PERUSAHAAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_insentif_perusahaan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN KORPRI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_korpri'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN PAJAK</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_pajak'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN BPJS KESEHATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_bpjs_kesehatan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH POTONGAN INSENTIF</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['total_potongan_insentif'], 0, ',', '.') }}</span>
                </div>

                <!-- 10 Item Potongan Non-Insentif -->
                <div class="slip-row">
                    <span class="slip-label">POTONGAN KOPERASI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_koperasi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN DARMA WANITA</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_darma_wanita'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN REKENING AIR MINUM</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_rekening_air_minum'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN KAS</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_kas'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN BANK BJB</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_bank_bjb'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN BANK BJBS</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_bank_bjbs'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN BANK BTN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_bank_btn'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN BANK BPR</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_bank_bpr'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN ASURANSI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_asuransi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row">
                    <span class="slip-label">POTONGAN ZAKAT PROFESI</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['potongan_zakat_profesi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH POTONGAN NON-INSENTIF</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['total_potongan_non_insentif'], 0, ',', '.') }}</span>
                </div>

                <!-- Total Akhir Diterima (JUMLAH INSENTIF DITERIMA) -->
                <div class="slip-row slip-row-bold slip-row-net">
                    <span class="slip-label">JUMLAH INSENTIF DITERIMA</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($item['diterima'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
