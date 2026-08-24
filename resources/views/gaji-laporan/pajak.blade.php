@extends('layouts.app')

@section('title', 'Laporan Pajak')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Pajak</div>
    <h1>Laporan Pajak (PPh 21)</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom:16px;">
    <div class="stat-card">
        <div class="label">Jumlah Pegawai</div>
        <div class="value">{{ $data->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Potongan Pajak</div>
        <div class="value">Rp {{ number_format($totalPajak, 0, ',', '.') }}</div>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>PTKP</th>
                <th>Potongan Pajak</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] ?? '-' }}</td>
                    <td class="cell-name">{{ $d['nama'] ?? '-' }}</td>
                    <td>{{ $d['kode_ptkp'] ?? 'K1' }}</td>
                    <td>Rp {{ number_format($d['potongan_pajak'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
