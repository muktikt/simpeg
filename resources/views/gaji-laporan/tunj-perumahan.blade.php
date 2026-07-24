@extends('layouts.app')

@section('title', 'Laporan Tunjangan Perumahan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Tunj. Perumahan</div>
    <h1>Laporan Tunjangan Perumahan</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom:16px;">
    <div class="stat-card">
        <div class="label">Jumlah Pegawai</div>
        <div class="value">{{ $data->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Tunjangan Perumahan</div>
        <div class="value">Rp {{ number_format($totalPerumahan, 0, ',', '.') }}</div>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Tunjangan Perumahan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d['tunjangan_perumahan'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
