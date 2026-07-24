@extends('layouts.app')

@section('title', 'Laporan Slip Gaji')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Slip Gaji</div>
    <h1>Laporan Slip Gaji</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

<p style="font-size:12px; color:var(--text-muted); margin:-8px 0 16px;">Menampilkan gaji yang sudah terbit. Klik "Lihat Slip" untuk detail lengkap per pegawai.</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Gaji Bersih</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>{{ \App\Http\Controllers\GajiProsesController::KATEGORI[$d['kategori']] ?? $d['kategori'] }}</td>
                    <td>Rp {{ number_format($d['gaji_bersih'], 0, ',', '.') }}</td>
                    <td><a href="{{ route('gaji-proses.show', $d['id']) }}" class="btn btn-outline btn-sm">Lihat Slip</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
