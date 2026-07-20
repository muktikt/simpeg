@extends('layouts.app')

@section('title', 'Laporan Absensi')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Laporan Absensi</div>
    <h1>Laporan Absensi</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('absensi.laporan') }}" style="display:flex; gap:10px;">
        <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @foreach ($bulanList as $val => $label)
                <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(5, 1fr);">
    <div class="stat-card">
        <div class="label">Total Pegawai</div>
        <div class="value">{{ $rekap['total_pegawai'] }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Hadir</div>
        <div class="value">{{ $rekap['total_hadir'] }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Sakit</div>
        <div class="value">{{ $rekap['total_sakit'] }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Izin</div>
        <div class="value">{{ $rekap['total_izin'] }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Alpha</div>
        <div class="value">{{ $rekap['total_alpha'] }}</div>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Unit Kerja</th>
                <th>Hari Kerja</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpha</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($absensi as $a)
                <tr>
                    <td class="cell-nik">{{ $a['nik'] }}</td>
                    <td class="cell-name">{{ $a['nama'] }}</td>
                    <td>{{ $a['unit_kerja'] }}</td>
                    <td>{{ $a['hari_kerja'] }}</td>
                    <td>{{ $a['hadir'] }}</td>
                    <td>{{ $a['sakit'] }}</td>
                    <td>{{ $a['izin'] }}</td>
                    <td>{{ $a['alpha'] }}</td>
                    <td>{{ $a['keterangan'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="table-empty">Belum ada data absensi untuk {{ $bulanList[$bulan] }} {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
