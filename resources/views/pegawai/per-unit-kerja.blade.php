@extends('layouts.app')

@section('title', 'Daftar Pegawai Per Unit Kerja')

@push('styles')
<style>
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.kpi-card.primary { border-left: 4px solid #0284c7; }
.kpi-card.success { border-left: 4px solid #10b981; }
.kpi-card.warning { border-left: 4px solid #f59e0b; }
.kpi-card.info    { border-left: 4px solid #6366f1; }
.kpi-title {
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 6px;
}
.kpi-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.kpi-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.filter-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
}
.filter-select {
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 14px;
    font-size: 13.5px;
    background: #fff;
    min-width: 280px;
}
.filter-select:focus {
    border-color: #0284c7;
    outline: none;
}
.btn-filter {
    height: 40px;
    background: #0284c7;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 0 18px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-filter:hover {
    background: #0369a1;
}
.btn-print-action {
    height: 40px;
    background: #0f172a;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 0 18px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}
.btn-print-action:hover {
    background: #1e293b;
    color: #fff;
}
.rekap-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
}
.rekap-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-top: 10px;
}
.rekap-table th, .rekap-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #e2e8f0;
}
.rekap-table th {
    background: #f1f5f9;
    font-weight: 700;
    color: #475569;
    text-align: left;
}
.badge-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}
.badge-status-tetap { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-status-pkwt  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-status-capeg { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-status-honorer { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
.badge-status-direksi { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai / Daftar Pegawai Per Unit Kerja</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px 0;">DAFTAR PEGAWAI PER UNIT KERJA / CABANG</h1>
            <p style="margin:0; font-size:13.5px; color:#64748b;">
                Rekapitulasi resmi penempatan, status kepegawaian, dan masa kerja pegawai PERUMDAM Tirta Darma Ayu
            </p>
        </div>
        <div>
            <a href="{{ route('pegawai.per-unit-kerja.cetak', ['unit_kerja' => $selected]) }}" target="_blank" class="btn-print-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Fisik Resmi A4 Landscape
            </a>
        </div>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-title">Total Pegawai</div>
        <div class="kpi-value" style="color:#0284c7;">{{ number_format($totalPegawai, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Orang</span></div>
        <div class="kpi-sub">{{ $selected === 'all' || empty($selected) ? 'Seluruh Unit Kerja / Cabang' : $selected }}</div>
    </div>

    <div class="kpi-card success">
        <div class="kpi-title">Pegawai Tetap</div>
        <div class="kpi-value" style="color:#059669;">{{ number_format($totalTetap, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Orang</span></div>
        <div class="kpi-sub">{{ $totalPegawai > 0 ? round(($totalTetap / $totalPegawai) * 100, 1) : 0 }}% dari total pegawai</div>
    </div>

    <div class="kpi-card warning">
        <div class="kpi-title">PKWT / Kontrak</div>
        <div class="kpi-value" style="color:#d97706;">{{ number_format($totalPkwt, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Orang</span></div>
        <div class="kpi-sub">Tenaga kontrak kerja waktu tertentu</div>
    </div>

    <div class="kpi-card info">
        <div class="kpi-title">Capeg / Honorer / Direksi</div>
        <div class="kpi-value" style="color:#4f46e5;">{{ number_format($totalCapeg + $totalHonorer + $totalDireksi, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Orang</span></div>
        <div class="kpi-sub">Calon Pegawai, Honorer, & Direksi</div>
    </div>
</div>

{{-- Filter Card --}}
<div class="filter-card">
    <form method="GET" action="{{ route('pegawai.per-unit-kerja') }}" class="filter-form">
        <label for="unit_kerja" style="font-weight:700; font-size:13px; color:#334155;">PILIH UNIT KERJA:</label>
        <select id="unit_kerja" name="unit_kerja" class="filter-select">
            <option value="all" @selected($selected === 'all' || empty($selected))>-- SEMUA UNIT KERJA (REKAP KESELURUHAN) --</option>
            @foreach ($unitKerjaList as $unit)
                <option value="{{ $unit }}" @selected($selected === $unit)>{{ $unit }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-filter">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Tampilkan Data
        </button>
    </form>
    <div style="font-size:12.5px; color:#64748b;">
        Menampilkan <strong>{{ $filtered->count() }}</strong> pegawai
    </div>
</div>

{{-- Ringkasan Per Cabang jika Semua Unit Dipilih --}}
@if (($selected === 'all' || empty($selected)) && count($rekapPerUnit) > 1)
    <div class="rekap-card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:700; font-size:13px; color:#1e293b;">REKAPITULASI JUMLAH PEGAWAI PER UNIT KERJA / CABANG</div>
            <span style="font-size:12px; color:#64748b;">Total {{ count($rekapPerUnit) }} Cabang & Unit Pelayanan</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="rekap-table">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Unit Kerja / Cabang Pelayanan</th>
                        <th style="text-align:right;">Pegawai Tetap</th>
                        <th style="text-align:right;">PKWT / Kontrak</th>
                        <th style="text-align:right;">Capeg / Lainnya</th>
                        <th style="text-align:right;">Total Pegawai</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekapPerUnit as $idx => $r)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><strong>{{ $r['unit_kerja'] }}</strong></td>
                            <td style="text-align:right;">{{ number_format($r['tetap'], 0, ',', '.') }}</td>
                            <td style="text-align:right;">{{ number_format($r['pkwt'], 0, ',', '.') }}</td>
                            <td style="text-align:right;">{{ number_format($r['capeg'] + $r['honorer'], 0, ',', '.') }}</td>
                            <td style="text-align:right; font-weight:700; color:#0284c7;">{{ number_format($r['total'], 0, ',', '.') }}</td>
                            <td style="text-align:center;">
                                <a href="{{ route('pegawai.per-unit-kerja', ['unit_kerja' => $r['unit_kerja']]) }}" style="color:#0284c7; font-weight:600; text-decoration:none; font-size:12px;">
                                    Filter Cabang &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Data Table --}}
<div class="table-card">
    <div class="table-card-header" style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h3 style="margin:0; font-size:15px; color:#0f172a; font-weight:700;">
            Daftar Rincian Pegawai: 
            <span style="color:#0284c7;">{{ $selected === 'all' || empty($selected) ? 'Seluruh Cabang (Rekap Keseluruhan)' : $selected }}</span>
        </h3>
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" class="table-search-input" placeholder="Cari NIK, nama, jabatan...">
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th>NIK</th>
                    <th>Nama Lengkap</th>
                    <th>Jabatan</th>
                    <th>Unit Kerja / Cabang</th>
                    <th>Golongan</th>
                    <th>Masa Kerja</th>
                    <th>Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filtered as $p)
                    @php
                        $badgeClass = match($p['status_label']) {
                            'Pegawai Tetap'       => 'badge-status-tetap',
                            'PKWT / Kontrak'      => 'badge-status-pkwt',
                            'Calon Pegawai'       => 'badge-status-capeg',
                            'Pegawai Honorer'     => 'badge-status-honorer',
                            'Direksi / Eksekutif' => 'badge-status-direksi',
                            default               => 'badge-status-tetap',
                        };
                    @endphp
                    <tr>
                        <td style="text-align:center; color:#64748b; font-weight:600;">{{ $loop->iteration }}</td>
                        <td class="cell-nik">{{ $p['formatted_nik'] }}</td>
                        <td class="cell-name">
                            <a href="{{ route('pegawai.show', $p['id'] ?? $p['nik']) }}" style="color:#0f172a; text-decoration:none; font-weight:700;">
                                {{ $p['nama_lengkap'] }}
                            </a>
                        </td>
                        <td>{{ $p['jabatan'] }}</td>
                        <td><span style="font-weight:600; color:#334155;">{{ $p['unit_kerja'] }}</span></td>
                        <td>{{ $p['golongan_teks'] }}</td>
                        <td>{{ $p['masa_kerja'] }}</td>
                        <td>
                            <span class="badge-status {{ $badgeClass }}">{{ $p['status_label'] }}</span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('pegawai.show', $p['id'] ?? $p['nik']) }}" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size:12px;">
                                Profil
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="table-empty">Tidak ada data pegawai pada unit kerja ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('partials.signature-block', ['kodeTtd' => 'gaji'])

@endsection
