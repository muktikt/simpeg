@extends('layouts.app')

@section('title', 'Approval')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Approval</div>
    <h1>Kotak Masuk Approval</h1>
</div>

<p style="font-size:12.5px; color:var(--text-muted); margin:-8px 0 20px;">
    Daftar gaji, THR, dan Gaji 13 yang sedang menunggu persetujuan kamu, sesuai tahapan berjenjang: Kepegawaian &rarr; Direktur Umum &rarr; Direktur Utama.
</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Jenis</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Periode</th>
                <th>Nominal</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pending as $p)
                <tr>
                    <td><span class="badge badge-CP">{{ $p['jenis'] }}</span></td>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>
                        @if ($p['jenis'] === 'Gaji Bulanan')
                            {{ \App\Http\Controllers\AbsensiController::BULAN[$p['bulan']] }} {{ $p['tahun'] }}
                        @else
                            {{ $p['tahun'] }}
                        @endif
                    </td>
                    <td>
                        Rp {{ number_format($p['gaji_bersih'] ?? $p['thr_diterima'] ?? $p['gaji13_diterima'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td><span class="badge badge-PH">Menunggu Persetujuanmu</span></td>
                    <td><a href="{{ $p['route'] }}" class="btn btn-primary btn-sm">Tinjau</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="table-empty">Tidak ada item yang menunggu persetujuan kamu saat ini.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
