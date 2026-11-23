@extends('layouts.app')

@section('title', 'Laporan Potongan Keuangan (Non-Minus)')

@push('styles')
<style>
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 18px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.kpi-card.success {
    border-left: 4px solid #10b981;
    background: #f0fdf4;
}
.kpi-card.primary {
    border-left: 4px solid #0284c7;
}
.kpi-card.info {
    border-left: 4px solid #6366f1;
}
.kpi-title {
    font-size: 12px;
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
.kpi-value.success {
    color: #059669;
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
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 16px;
}
.filter-form {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-group label {
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
}
.filter-group select,
.filter-group input {
    height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 12px;
    font-size: 13px;
    background: #fff;
    color: #0f172a;
}
.filter-group select:focus,
.filter-group input:focus {
    border-color: #0284c7;
    outline: none;
}
.btn-filter {
    height: 38px;
    background: #0284c7;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 0 18px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-filter:hover {
    background: #0369a1;
}
.table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.table-responsive {
    overflow-x: auto;
    width: 100%;
}
table.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    text-align: left;
}
table.data-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
table.data-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}
table.data-table tr:hover td {
    background-color: #f8fafc;
}
table.data-table td.col-num {
    text-align: center;
    color: #64748b;
    font-weight: 600;
}
table.data-table td.col-nik {
    font-family: 'Space Grotesk', monospace;
    font-weight: 600;
    color: #0369a1;
    white-space: nowrap;
}
table.data-table td.col-nama {
    font-weight: 600;
    color: #0f172a;
    min-width: 170px;
}
table.data-table td.col-unit {
    color: #64748b;
    font-size: 11.5px;
    min-width: 130px;
}
table.data-table td.col-cur {
    text-align: right;
    font-family: 'Space Grotesk', monospace;
    font-size: 12px;
    white-space: nowrap;
}
table.data-table tr.total-row td {
    background: #f8fafc;
    font-weight: 700;
    border-top: 2px solid #cbd5e1;
    color: #0f172a;
}
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan / Potongan Keuangan / Non-Minus (Normal)</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px 0;">LAPORAN POTONGAN KEUANGAN (NON-MINUS)</h1>
            <p style="margin:0; font-size:13.5px; color:#64748b;">
                Daftar pegawai dengan potongan normal dan sisa gaji bersih bernilai positif (&gt; 0)
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('laporan-potongan.cetak-non-minus', ['bulan' => $bulan, 'tahun' => $tahun, 'unit_kerja' => $unitKerja]) }}" target="_blank" class="btn" style="background:#0284c7; color:#ffffff; font-weight:600; font-size:13px; padding:8px 16px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Laporan Non-Minus (A4 Landscape)
            </a>
            <a href="{{ route('laporan-potongan.potongan-keu-minus', ['bulan' => $bulan, 'tahun' => $tahun, 'unit_kerja' => $unitKerja]) }}" class="btn" style="background:#fee2e2; color:#b91c1c; font-weight:600; font-size:13px; padding:8px 16px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; border:1px solid #fca5a5;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Lihat Laporan Khusus Minus
            </a>
        </div>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="kpi-grid">
    <div class="kpi-card success">
        <div class="kpi-title">Pegawai Normal (Non-Minus)</div>
        <div class="kpi-value success">{{ number_format($totalPegawai, 0, ',', '.') }} <span style="font-size:15px; font-weight:500; color:#64748b;">Orang</span></div>
        <div class="kpi-sub">Gaji diterima bernilai positif (&gt; 0)</div>
    </div>

    <div class="kpi-card primary">
        <div class="kpi-title">Total Potongan Keuangan Non-Minus</div>
        <div class="kpi-value" style="color:#0284c7;">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</div>
        <div class="kpi-sub">Total 11 pos potongan kas pegawai</div>
    </div>

    <div class="kpi-card info">
        <div class="kpi-title">Total Gaji Bersih Diterima</div>
        <div class="kpi-value" style="color:#4f46e5;">Rp {{ number_format($totalGajiDiterima, 0, ',', '.') }}</div>
        <div class="kpi-sub">Siap diproses transfer Bank BJB</div>
    </div>
</div>

{{-- Filter Card --}}
<div class="filter-card">
    <form method="GET" action="{{ route('laporan-potongan.potongan-keu-non-minus') }}" class="filter-form">
        <div class="filter-group">
            <label for="bulan">Bulan</label>
            <select name="bulan" id="bulan">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($bulan === $m)>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="tahun">Tahun</label>
            <input type="number" name="tahun" id="tahun" value="{{ $tahun }}" min="2000" max="2099" style="width:100px;">
        </div>

        <div class="filter-group">
            <label for="unit_kerja">Unit Kerja</label>
            <select name="unit_kerja" id="unit_kerja" style="min-width: 220px;">
                <option value="all" @selected($unitKerja === 'all' || empty($unitKerja))>-- SEMUA UNIT KERJA --</option>
                @foreach ($unitKerjaList as $u)
                    <option value="{{ $u }}" @selected($unitKerja === $u)>{{ $u }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-filter">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Terapkan Filter
        </button>
    </form>
</div>

{{-- Data Table --}}
<div class="table-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:30px;">NO</th>
                    <th>NIK</th>
                    <th>NAMA PEGAWAI</th>
                    <th>UNIT KERJA</th>
                    <th style="text-align:right;">PENDAPATAN DITERIMA</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] ?? strtoupper($k) }}</th>
                    @endforeach
                    <th style="text-align:right; background:#f1f5f9;">TOTAL POT. KEU</th>
                    <th style="text-align:right; background:#ecfdf5; color:#065f46;">GAJI DITERIMA</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumPendapatan = 0;
                    $sumTotals = array_fill_keys($kolom, 0);
                    $sumPotKeu = 0;
                    $sumGajiDiterima = 0;
                @endphp

                @forelse ($items as $idx => $r)
                    @php
                        $sumPendapatan += (float) ($r['pendapatan_diterima'] ?? 0);
                        foreach ($kolom as $k) {
                            $sumTotals[$k] += (float) ($r[$k] ?? 0);
                        }
                        $sumPotKeu += (float) ($r['total_potongan_keu'] ?? 0);
                        $sumGajiDiterima += (float) ($r['gaji_diterima'] ?? 0);
                    @endphp
                    <tr>
                        <td class="col-num">{{ $idx + 1 }}</td>
                        <td class="col-nik">{{ $r['nik'] }}</td>
                        <td class="col-nama">
                            {{ $r['nama'] }}
                            @if (!empty($r['jabatan']) && $r['jabatan'] !== '-')
                                <div style="font-size:11px; color:#64748b; font-weight:normal;">{{ $r['jabatan'] }}</div>
                            @endif
                        </td>
                        <td class="col-unit">{{ $r['unit_kerja'] }}</td>
                        <td class="col-cur">{{ number_format($r['pendapatan_diterima'] ?? 0, 0, ',', '.') }}</td>
                        @foreach ($kolom as $k)
                            <td class="col-cur">{{ number_format($r[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td class="col-cur" style="font-weight:700; background:#f8fafc;">
                            {{ number_format($r['total_potongan_keu'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="col-cur" style="font-weight:700; background:#f0fdf4; color:#059669;">
                            {{ number_format($r['gaji_diterima'] ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 6 }}" style="text-align:center; padding:30px; color:#64748b;">
                            Tidak ada data potongan keuangan non-minus untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($items->count() > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align:center; padding:12px;">TOTAL REKAPITULASI</td>
                        <td class="col-cur">{{ number_format($sumPendapatan, 0, ',', '.') }}</td>
                        @foreach ($kolom as $k)
                            <td class="col-cur">{{ number_format($sumTotals[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td class="col-cur" style="font-weight:700; background:#e2e8f0;">
                            {{ number_format($sumPotKeu, 0, ',', '.') }}
                        </td>
                        <td class="col-cur" style="font-weight:700; background:#d1fae5; color:#065f46;">
                            {{ number_format($sumGajiDiterima, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

@include('partials.signature-block', ['kodeTtd' => 'keuangan'])

@endsection
