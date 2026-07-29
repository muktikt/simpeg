@extends('layouts.app')

@section('title', 'Laporan Prestasi Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Prestasi</div>
    <h1>Laporan Prestasi Pegawai</h1>
</div>

<div class="toolbar">
    <div></div>
    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

<div class="table-card" style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Tgl. Prestasi</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Karya</th>
                <th>Absensi</th>
                <th>Jam Lembur</th>
                <th>Total Uang Lembur</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prestasi as $p)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($p['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>{{ $p['karya'] }}</td>
                    <td>{{ $p['absensi'] }}</td>
                    <td>{{ $p['jam_lembur'] }} jam</td>
                    <td>Rp {{ number_format($p['nominal_lembur'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="table-empty">Belum ada data prestasi pegawai.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
