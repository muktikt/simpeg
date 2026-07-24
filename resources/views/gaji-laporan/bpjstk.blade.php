@extends('layouts.app')

@section('title', 'Laporan BPJS-TK')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. BPJSTK</div>
    <h1>Laporan BPJS Ketenagakerjaan</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom:16px;">
    <div class="stat-card">
        <div class="label">Jumlah Pegawai</div>
        <div class="value">{{ $data->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Tunjangan + Potongan BPJS-TK</div>
        <div class="value">Rp {{ number_format($totalBpjstk, 0, ',', '.') }}</div>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Tunjangan BPJS-TK</th>
                <th>Potongan BPJS-TK</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d['tunjangan_bpjstk'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['potongan_bpjstk'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
