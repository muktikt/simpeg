@extends('layouts.app')

@section('title', 'Komparasi & Selisih NIK Potongan')

@section('content')
@php
    $bulanNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
@endphp

<style>
/* Header & Title */
.diff-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}
.diff-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 4px 0;
}
.diff-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
}

/* Stat KPI Cards */
.diff-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}
.diff-kpi-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 18px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.diff-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 42, 61, 0.06);
}
.diff-kpi-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748B;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.diff-kpi-val {
    font-size: 20px;
    font-weight: 700;
    color: #0F2A3D;
    line-height: 1.2;
}
.diff-kpi-sub {
    font-size: 12px;
    color: #64748B;
    margin-top: 4px;
}

/* Specific Card Themes */
.card-theme-acuan { border-top: 4px solid #64748B; }
.card-theme-pembanding { border-top: 4px solid #0284C7; }
.card-theme-netto { border-top: 4px solid #4F46E5; }
.card-theme-baru { border-top: 4px solid #10B981; }
.card-theme-keluar { border-top: 4px solid #EF4444; }
.card-theme-berubah { border-top: 4px solid #F59E0B; }

/* Filter Card */
.diff-filter-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
}
.filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 14px;
}
.filter-col {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.filter-col label {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.filter-col select,
.filter-col input {
    height: 38px;
    padding: 6px 12px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    color: #0F2A3D;
    background: #ffffff;
}
.filter-col select:focus,
.filter-col input:focus {
    outline: none;
    border-color: #0284C7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

/* Quick Filter Pills / Tabs */
.diff-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.diff-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #CBD5E1;
    background: #ffffff;
    color: #475569;
    transition: all 0.15s ease;
}
.diff-tab-btn:hover {
    background: #F1F5F9;
    color: #0F2A3D;
    border-color: #94A3B8;
}
.diff-tab-btn.active {
    background: #0F2A3D;
    color: #ffffff;
    border-color: #0F2A3D;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.2);
}
.diff-tab-pill {
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.diff-tab-btn.active .diff-tab-pill {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}
.diff-tab-btn:not(.active) .diff-tab-pill {
    background: #E2E8F0;
    color: #334155;
}

/* Status Badges */
.badge-diff {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.badge-success {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #86EFAC;
}
.badge-danger {
    background: #FEE2E2;
    color: #B91C1C;
    border: 1px solid #FCA5A5;
}
.badge-warning {
    background: #FEF3C7;
    color: #B45309;
    border: 1px solid #FCD34D;
}
.badge-info {
    background: #E0F2FE;
    color: #0369A1;
    border: 1px solid #7DD3FC;
}
.badge-secondary {
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #CBD5E1;
}

/* Component Difference Tags */
.comp-diff-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin: 2px;
}
.comp-diff-up {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FDE68A;
}
.comp-diff-down {
    background: #E0F2FE;
    color: #075985;
    border: 1px solid #BAE6FD;
}

/* Modal Side-by-side */
.diff-modal-overlay {
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
.diff-modal-box {
    background: #ffffff;
    width: 100%;
    max-width: 640px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    animation: modalPop 0.2s ease-out;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
@keyframes modalPop {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.diff-modal-header {
    padding: 18px 24px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.diff-modal-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0;
}
.diff-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
}
.diff-modal-footer {
    padding: 14px 24px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    display: flex;
    justify-content: flex-end;
}

/* Print Styling */
@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 10pt;
    }
    .sidebar, .topbar, .diff-filter-card, .diff-tabs, .btn-no-print, .pagination, .page-head .breadcrumb {
        display: none !important;
    }
    .content-area, .page-body {
        margin: 0 !important;
        padding: 0 !important;
    }
    .print-only-header {
        display: block !important;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
        text-align: center;
    }
    .data-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .data-table th, .data-table td {
        border: 1px solid #666 !important;
        padding: 4px 6px !important;
        font-size: 9pt !important;
    }
}
.print-only-header {
    display: none;
}
</style>

<!-- Header Cetak Resmi (Hanya muncul saat Print) -->
<div class="print-only-header">
    <div style="font-size:16pt; font-weight:bold;">PERUMDAM TIRTA DARMA AYU KABUPATEN INDRAMAYU</div>
    <div style="font-size:11pt; margin-top:3px;">BIDANG KEUANGAN — LAPORAN KOMPARASI & SELISIH POTONGAN PEGAWAI</div>
    <div style="font-size:10pt; color:#444; margin-top:2px;">
        Perbandingan: {{ $bulanNames[$bulanA] ?? $bulanA }} {{ $tahunA }} vs {{ $bulanNames[$bulanB] ?? $bulanB }} {{ $tahunB }} ({{ $tipeLabels[$tipe] ?? strtoupper($tipe) }})
    </div>
</div>

<div class="page-head btn-no-print">
    <div class="breadcrumb">Home / Set Potongan / Komparasi & Selisih NIK</div>
    <div class="diff-header">
        <div>
            <h1 class="diff-title">KOMPARASI & SELISIH NIK POTONGAN</h1>
            <p class="diff-subtitle">
                Diff otomatis perbandingan potongan antar periode: deteksi Pegawai Baru, Pegawai Keluar/Pensiun, Nominal Berubah, dan rincian selisih per komponen.
            </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline btn-no-print" style="display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Ekspor CSV / Excel
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-no-print" style="display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Laporan
            </button>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="diff-filter-card btn-no-print">
    <form method="GET" action="{{ route('cek-nik.komparasi') }}">
        <div class="filter-row">
            <div class="filter-col">
                <label>Jenis Potongan</label>
                <select name="tipe">
                    @foreach ($tipeLabels as $tKey => $tLabel)
                        <option value="{{ $tKey }}" @selected($tipe === $tKey)>{{ $tLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Periode A (Acuan) -->
            <div class="filter-col" style="border-left: 2px solid #E2E8F0; padding-left: 12px;">
                <label style="color:#64748B;">Periode Acuan (A)</label>
                <div style="display:flex; gap:6px;">
                    <select name="bulan_a">
                        @foreach ($bulanNames as $bNum => $bTxt)
                            <option value="{{ $bNum }}" @selected($bulanA === $bNum)>{{ $bTxt }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="tahun_a" value="{{ $tahunA }}" style="width:80px;" min="2000" max="2100">
                </div>
            </div>

            <div style="align-self:center; font-weight:700; color:#94A3B8; padding-top:14px;">VS</div>

            <!-- Periode B (Pembanding) -->
            <div class="filter-col">
                <label style="color:#0284C7;">Periode Pembanding (B)</label>
                <div style="display:flex; gap:6px;">
                    <select name="bulan_b">
                        @foreach ($bulanNames as $bNum => $bTxt)
                            <option value="{{ $bNum }}" @selected($bulanB === $bNum)>{{ $bTxt }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="tahun_b" value="{{ $tahunB }}" style="width:80px;" min="2000" max="2100">
                </div>
            </div>

            <!-- Search Pegawai -->
            <div class="filter-col" style="flex:1; min-width:180px;">
                <label>Cari NIK / Nama / Unit</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Ketik NIK, Nama Pegawai, atau Unit Kerja...">
            </div>

            <input type="hidden" name="filter" value="{{ $filter }}">

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;">
                    Bandingkan
                </button>
                @php
                    $defBulanA = (int) now()->month;
                    $defTahunA = (int) now()->year;
                    $defBulanB = (int) now()->subMonth()->month;
                    $defTahunB = (int) now()->subMonth()->year;
                    $hasCustomFilter = (
                        !empty($search) ||
                        ($filter ?? 'all') !== 'all' ||
                        (int)$bulanA !== $defBulanA ||
                        (int)$tahunA !== $defTahunA ||
                        (int)$bulanB !== $defBulanB ||
                        (int)$tahunB !== $defTahunB
                    );
                @endphp
                <a id="btn-reset-komparasi" href="{{ route('cek-nik.komparasi') }}" class="btn btn-outline" style="height:38px; padding:0 14px; display:{{ $hasCustomFilter ? 'inline-flex' : 'none' }}; align-items:center; color:#DC2626; border-color:#FECACA;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- KPI Stat Cards -->
<div class="diff-kpi-grid">
    <!-- Acuan A -->
    <div class="diff-kpi-card card-theme-acuan">
        <div class="diff-kpi-label">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Periode Acuan (A): {{ $bulanNames[$bulanA] ?? $bulanA }} {{ $tahunA }}
        </div>
        <div class="diff-kpi-val">{{ number_format($stats['total_pegawai_a'], 0, ',', '.') }} <span style="font-size:13px; font-weight:normal; color:#64748B;">Pegawai</span></div>
        <div class="diff-kpi-sub">Total: Rp {{ number_format($stats['total_nominal_a'], 0, ',', '.') }}</div>
    </div>

    <!-- Pembanding B -->
    <div class="diff-kpi-card card-theme-pembanding">
        <div class="diff-kpi-label" style="color:#0284C7;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Periode Berjalan (B): {{ $bulanNames[$bulanB] ?? $bulanB }} {{ $tahunB }}
        </div>
        <div class="diff-kpi-val">{{ number_format($stats['total_pegawai_b'], 0, ',', '.') }} <span style="font-size:13px; font-weight:normal; color:#64748B;">Pegawai</span></div>
        <div class="diff-kpi-sub">Total: Rp {{ number_format($stats['total_nominal_b'], 0, ',', '.') }}</div>
    </div>

    <!-- Netto Selisih -->
    <div class="diff-kpi-card card-theme-netto">
        <div class="diff-kpi-label" style="color:#4F46E5;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Total Selisih Netto (B - A)
        </div>
        <div class="diff-kpi-val" style="color:{{ $stats['selisih_netto'] > 0 ? '#10B981' : ($stats['selisih_netto'] < 0 ? '#EF4444' : '#64748B') }};">
            {{ $stats['selisih_netto'] >= 0 ? '+' : '' }}Rp {{ number_format($stats['selisih_netto'], 0, ',', '.') }}
        </div>
        <div class="diff-kpi-sub">
            @if ($stats['total_nominal_a'] > 0)
                Perubahan: {{ number_format(($stats['selisih_netto'] / $stats['total_nominal_a']) * 100, 2, ',', '.') }}%
            @else
                Periode A Awal
            @endif
        </div>
    </div>

    <!-- Pegawai Baru -->
    <div class="diff-kpi-card card-theme-baru">
        <div class="diff-kpi-label" style="color:#10B981;">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10B981;"></span>
            Pegawai Baru (+B)
        </div>
        <div class="diff-kpi-val" style="color:#10B981;">+{{ $stats['count_baru'] }} <span style="font-size:13px; font-weight:normal; color:#64748B;">Orang</span></div>
        <div class="diff-kpi-sub">Potongan: +Rp {{ number_format($stats['nominal_baru'], 0, ',', '.') }}</div>
    </div>

    <!-- Pegawai Keluar -->
    <div class="diff-kpi-card card-theme-keluar">
        <div class="diff-kpi-label" style="color:#EF4444;">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#EF4444;"></span>
            Pegawai Keluar (-A)
        </div>
        <div class="diff-kpi-val" style="color:#EF4444;">-{{ $stats['count_keluar'] }} <span style="font-size:13px; font-weight:normal; color:#64748B;">Orang</span></div>
        <div class="diff-kpi-sub">Potongan: -Rp {{ number_format($stats['nominal_keluar'], 0, ',', '.') }}</div>
    </div>

    <!-- Nominal Berubah -->
    <div class="diff-kpi-card card-theme-berubah">
        <div class="diff-kpi-label" style="color:#F59E0B;">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#F59E0B;"></span>
            Nominal Berubah
        </div>
        <div class="diff-kpi-val" style="color:#D97706;">{{ $stats['count_berubah'] }} <span style="font-size:13px; font-weight:normal; color:#64748B;">Orang</span></div>
        <div class="diff-kpi-sub">Naik: {{ $stats['count_naik'] }} | Turun: {{ $stats['count_turun'] }} | Tetap: {{ $stats['count_tetap'] }}</div>
    </div>
</div>

<!-- Quick Tab Filter Pills -->
<div class="diff-tabs btn-no-print">
    <a href="{{ request()->fullUrlWithQuery(['filter' => 'semua', 'page' => 1]) }}" class="diff-tab-btn @if($filter === 'semua') active @endif">
        <span>Semua Data</span>
        <span class="diff-tab-pill">{{ $stats['total_pegawai_a'] + $stats['count_baru'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['filter' => 'baru', 'page' => 1]) }}" class="diff-tab-btn @if($filter === 'baru') active @endif">
        <span style="color:#10B981;">●</span>
        <span>Pegawai Baru</span>
        <span class="diff-tab-pill">{{ $stats['count_baru'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['filter' => 'keluar', 'page' => 1]) }}" class="diff-tab-btn @if($filter === 'keluar') active @endif">
        <span style="color:#EF4444;">●</span>
        <span>Keluar / Hilang</span>
        <span class="diff-tab-pill">{{ $stats['count_keluar'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['filter' => 'berubah', 'page' => 1]) }}" class="diff-tab-btn @if($filter === 'berubah') active @endif">
        <span style="color:#F59E0B;">●</span>
        <span>Nominal Berubah</span>
        <span class="diff-tab-pill">{{ $stats['count_berubah'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['filter' => 'tetap', 'page' => 1]) }}" class="diff-tab-btn @if($filter === 'tetap') active @endif">
        <span style="color:#64748B;">●</span>
        <span>Nominal Tetap</span>
        <span class="diff-tab-pill">{{ $stats['count_tetap'] }}</span>
    </a>
</div>

<!-- Table Card -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px; text-align:center;">No</th>
                <th style="width:110px;">NIK</th>
                <th>Nama Pegawai & Unit Kerja</th>
                <th style="width:130px; text-align:center;">Status Diff</th>
                <th style="width:130px; text-align:right;">Periode A ({{ $bulanA }}/{{ $tahunA }})</th>
                <th style="width:130px; text-align:right;">Periode B ({{ $bulanB }}/{{ $tahunB }})</th>
                <th style="width:140px; text-align:right;">Selisih (Rp)</th>
                <th>Rincian Perubahan Komponen</th>
                <th class="btn-no-print" style="width:90px; text-align:center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($paginator as $item)
                <tr>
                    <td style="text-align:center; color:#64748B;">
                        {{ ($paginator->currentPage() - 1) * $paginator->perPage() + $loop->iteration }}
                    </td>
                    <td>
                        <span class="cell-nik" style="font-family:monospace; font-weight:700; background:#F1F5F9; padding:2px 6px; border-radius:4px;">
                            {{ $item['nik'] }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:#0F2A3D;">{{ $item['nama_lengkap'] }}</div>
                        <div style="font-size:12px; color:#64748B; margin-top:2px;">
                            {{ $item['unit_kerja'] }} &bull; <span style="color:#94A3B8;">{{ $item['jabatan'] }}</span>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge-diff {{ $item['diff_class'] }}">
                            {{ $item['diff_badge'] }}
                        </span>
                    </td>
                    <td style="text-align:right; font-family:monospace; font-weight:600; color:#475569;">
                        @if ($item['total_a'] > 0)
                            Rp {{ number_format($item['total_a'], 0, ',', '.') }}
                        @else
                            <span style="color:#CBD5E1;">-</span>
                        @endif
                    </td>
                    <td style="text-align:right; font-family:monospace; font-weight:600; color:#0F2A3D;">
                        @if ($item['total_b'] > 0)
                            Rp {{ number_format($item['total_b'], 0, ',', '.') }}
                        @else
                            <span style="color:#CBD5E1;">-</span>
                        @endif
                    </td>
                    <td style="text-align:right; font-family:monospace; font-weight:700;">
                        @if ($item['selisih'] > 0)
                            <span style="color:#10B981;">+Rp {{ number_format($item['selisih'], 0, ',', '.') }}</span>
                        @elseif ($item['selisih'] < 0)
                            <span style="color:#EF4444;">-Rp {{ number_format(abs($item['selisih']), 0, ',', '.') }}</span>
                        @else
                            <span style="color:#94A3B8;">Rp 0</span>
                        @endif
                    </td>
                    <td>
                        @if (!empty($item['perubahan']))
                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                @foreach ($item['perubahan'] as $chg)
                                    <span class="comp-diff-tag {{ $chg['selisih'] > 0 ? 'comp-diff-up' : 'comp-diff-down' }}"
                                          title="Semula: Rp {{ number_format($chg['val_a'],0,',','.') }} &rarr; Kini: Rp {{ number_format($chg['val_b'],0,',','.') }}">
                                        {{ $chg['label'] }}: {{ $chg['selisih'] > 0 ? '+' : '' }}{{ number_format($chg['selisih'],0,',','.') }}
                                    </span>
                                @endforeach
                            </div>
                        @elseif ($item['diff_status'] === 'baru')
                            <span style="font-size:11.5px; color:#10B981; font-weight:600;">Semua potongan baru terdaftar</span>
                        @elseif ($item['diff_status'] === 'keluar')
                            <span style="font-size:11.5px; color:#EF4444; font-weight:600;">Potongan tidak ada di periode berjalan</span>
                        @else
                            <span style="font-size:12px; color:#94A3B8;">Sama persis (tidak ada selisih)</span>
                        @endif
                    </td>
                    <td class="btn-no-print" style="text-align:center;">
                        <button type="button" class="btn btn-sm btn-outline"
                                onclick="openDetailModal({{ json_encode($item) }})"
                                style="padding:4px 9px; font-size:11.5px;">
                            Detail
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:40px 20px;">
                        <div style="color:#64748B; font-weight:600; font-size:14px;">Tidak ada data komparasi yang sesuai dengan filter.</div>
                        <p style="color:#94A3B8; font-size:12.5px; margin-top:4px;">Coba ganti filter periode atau status diff di atas.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if ($paginator->hasPages())
    <div class="pagination-wrapper btn-no-print" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:13px; color:#64748B;">
            Menampilkan {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data komparasi
        </div>
        <div>
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    </div>
@endif

<!-- Modal Detail 11 Kolom Side-by-Side -->
<div id="diffDetailModal" class="diff-modal-overlay btn-no-print">
    <div class="diff-modal-box">
        <div class="diff-modal-header">
            <div>
                <h3 class="diff-modal-title" id="mPegawaiNama">Rincian Komparasi Komponen Potongan</h3>
                <div style="font-size:12px; color:#64748B; margin-top:2px;" id="mPegawaiSub">NIK: - &bull; Unit: -</div>
            </div>
            <button type="button" onclick="closeDetailModal()" style="border:none; background:transparent; font-size:20px; color:#94A3B8; cursor:pointer;">&times;</button>
        </div>
        <div class="diff-modal-body">
            <table class="data-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Komponen Potongan</th>
                        <th style="text-align:right;">Periode A ({{ $bulanA }}/{{ $tahunA }})</th>
                        <th style="text-align:right;">Periode B ({{ $bulanB }}/{{ $tahunB }})</th>
                        <th style="text-align:right;">Selisih</th>
                    </tr>
                </thead>
                <tbody id="mTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
                <tfoot>
                    <tr style="font-weight:700; background:#F8FAFC;">
                        <td>TOTAL POTONGAN</td>
                        <td style="text-align:right; font-family:monospace;" id="mTotalA">Rp 0</td>
                        <td style="text-align:right; font-family:monospace;" id="mTotalB">Rp 0</td>
                        <td style="text-align:right; font-family:monospace;" id="mTotalSelisih">Rp 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="diff-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
const kolomLabels = @json($kolomLabels);

function formatRp(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function openDetailModal(item) {
    document.getElementById('mPegawaiNama').innerText = item.nama_lengkap;
    document.getElementById('mPegawaiSub').innerText = 'NIK: ' + item.nik + ' • ' + item.unit_kerja + ' (' + item.jabatan + ')';

    const tbody = document.getElementById('mTableBody');
    tbody.innerHTML = '';

    const rowA = item.row_a || {};
    const rowB = item.row_b || {};

    let totalA = 0;
    let totalB = 0;

    for (const [colKey, colName] of Object.entries(kolomLabels)) {
        const valA = parseInt(rowA[colKey] || 0);
        const valB = parseInt(rowB[colKey] || 0);
        const diff = valB - valA;

        totalA += valA;
        totalB += valB;

        let diffHtml = '<span style="color:#94A3B8;">Rp 0</span>';
        if (diff > 0) {
            diffHtml = '<span style="color:#10B981; font-weight:700;">+' + formatRp(diff) + '</span>';
        } else if (diff < 0) {
            diffHtml = '<span style="color:#EF4444; font-weight:700;">-' + formatRp(Math.abs(diff)) + '</span>';
        }

        const tr = document.createElement('tr');
        if (diff !== 0) {
            tr.style.background = '#FFFBEB';
        }
        tr.innerHTML = `
            <td>
                <span style="font-weight:${diff !== 0 ? '600' : 'normal'}; color:#0F2A3D;">${colName}</span>
                ${diff !== 0 ? '<span style="font-size:10px; margin-left:6px; background:#FDE68A; color:#92400E; padding:1px 5px; border-radius:3px;">Berubah</span>' : ''}
            </td>
            <td style="text-align:right; font-family:monospace; color:#475569;">${formatRp(valA)}</td>
            <td style="text-align:right; font-family:monospace; color:#0F2A3D; font-weight:600;">${formatRp(valB)}</td>
            <td style="text-align:right; font-family:monospace;">${diffHtml}</td>
        `;
        tbody.appendChild(tr);
    }

    document.getElementById('mTotalA').innerText = formatRp(totalA);
    document.getElementById('mTotalB').innerText = formatRp(totalB);

    const totalDiff = totalB - totalA;
    const diffEl = document.getElementById('mTotalSelisih');
    if (totalDiff > 0) {
        diffEl.style.color = '#10B981';
        diffEl.innerText = '+' + formatRp(totalDiff);
    } else if (totalDiff < 0) {
        diffEl.style.color = '#EF4444';
        diffEl.innerText = '-' + formatRp(Math.abs(totalDiff));
    } else {
        diffEl.style.color = '#64748B';
        diffEl.innerText = 'Rp 0';
    }

    document.getElementById('diffDetailModal').style.display = 'flex';
}

function closeDetailModal() {
    document.getElementById('diffDetailModal').style.display = 'none';
}

// Close modal on Escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDetailModal();
});

document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[action*="cek-nik/komparasi"]');
    var btnReset = document.getElementById('btn-reset-komparasi');
    if (form && btnReset) {
        var defBulanA = '{{ now()->month }}';
        var defTahunA = '{{ now()->year }}';
        var defBulanB = '{{ now()->subMonth()->month }}';
        var defTahunB = '{{ now()->subMonth()->year }}';

        function checkKomparasiFilter() {
            var bA = form.querySelector('[name="bulan_a"]')?.value || defBulanA;
            var tA = form.querySelector('[name="tahun_a"]')?.value || defTahunA;
            var bB = form.querySelector('[name="bulan_b"]')?.value || defBulanB;
            var tB = form.querySelector('[name="tahun_b"]')?.value || defTahunB;
            var s = form.querySelector('[name="search"]')?.value?.trim() || '';

            var isChanged = (bA != defBulanA) || (tA != defTahunA) || (bB != defBulanB) || (tB != defTahunB) || (s !== '');
            btnReset.style.display = isChanged ? 'inline-flex' : 'none';
        }

        form.querySelectorAll('select, input').forEach(function(el) {
            el.addEventListener('change', checkKomparasiFilter);
            el.addEventListener('input', checkKomparasiFilter);
        });
    }
});
</script>
@endsection

