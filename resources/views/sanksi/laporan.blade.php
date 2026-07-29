@extends('layouts.app')

@section('title', 'Laporan Sanksi Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Kepegawaian / Lap. Sanksi Pegawai</div>
    <h1>Laporan Sanksi Pegawai</h1>
</div>

<div class="toolbar">
    <div></div>
    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Tgl. Sanksi</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Jenis Sanksi</th>
                <th>Keterangan</th>
                <th>Potongan (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sanksi as $s)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($s['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td class="cell-nik">{{ $s['nik'] }}</td>
                    <td class="cell-name">{{ $s['nama'] }}</td>
                    <td>{{ $s['jenis_sanksi'] }}</td>
                    <td>{{ $s['keterangan'] ?: '-' }}</td>
                    <td>{{ $s['potongan_persen'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="table-empty">Belum ada data sanksi pegawai.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
