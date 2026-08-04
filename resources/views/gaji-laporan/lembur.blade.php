@extends('layouts.app')

@section('title', 'Laporan Lembur')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pendapatan Saya / Lembur</div>
    <h1>Rincian Lembur</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

@if (session('simpeg_user.userlevel') === '5')
    @php
        $bulanNama = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Bulan Ini';
    @endphp

    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
            RIWAYAT LEMBUR PEGAWAI
        </div>

        <div class="table-card" style="box-shadow: none; border: 1px solid #cbd5e1;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th>BULAN / PERIODE</th>
                        <th style="text-align: center;">JAM LEMBUR</th>
                        <th style="text-align: right;">UANG LEMBUR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatLembur as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td style="font-weight: 600; color: #334155;">{{ $item['bulan_nama'] }}</td>
                            <td style="text-align: center; font-weight: 600; color: #2563eb;">{{ $item['jam_lembur'] }} jam</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">Rp {{ number_format($item['nominal_lembur'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="table-empty">Belum ada data lembur recorded untuk Anda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@else
    <!-- Tampilan Admin / Keuangan -->
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
@endif
@endsection
