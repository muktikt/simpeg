@extends('layouts.app')

@section('title', 'Laporan Lembur')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Lembur</div>
    <h1>Laporan Lembur</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

<p style="font-size:12px; color:var(--text-muted); margin:-8px 0 16px;">Data diambil dari modul Prestasi (field Jam Lembur).</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Tanggal Prestasi</th>
                <th>Jam Lembur</th>
                <th>Nominal Lembur</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($d['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td>{{ $d['jam_lembur'] }} jam</td>
                    <td>Rp {{ number_format($d['nominal_lembur'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="table-empty">Belum ada data lembur untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
