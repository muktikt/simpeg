@extends('layouts.app')

@section('title', 'Proses Terbit ' . $tipeLabel)

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / Proses Terbit Potongan / {{ $tipeLabel }}</div>
    <h1>PROSES PENERBITAN {{ strtoupper($tipeLabel) }}</h1>
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:20px;">
    <div class="stat-card" style="background:#fff; padding:18px; border-radius:12px; border:1px solid #E1E7E9;">
        <div style="font-size:13px; color:#6B7789; margin-bottom:4px;">Siap Diterbitkan</div>
        <div style="font-size:24px; font-weight:700; color:#D35400;">{{ $items->count() }} <span style="font-size:14px; font-weight:500; color:#6B7789;">Pegawai</span></div>
    </div>
    <div class="stat-card" style="background:#fff; padding:18px; border-radius:12px; border:1px solid #E1E7E9;">
        <div style="font-size:13px; color:#6B7789; margin-bottom:4px;">Sudah Disetujui / Terbit</div>
        <div style="font-size:24px; font-weight:700; color:#27AE60;">{{ $sudahDiterbitkan }} <span style="font-size:14px; font-weight:500; color:#6B7789;">Pegawai</span></div>
    </div>
    <div class="stat-card" style="background:#fff; padding:18px; border-radius:12px; border:1px solid #E1E7E9;">
        <div style="font-size:13px; color:#6B7789; margin-bottom:4px;">Total Nominal Potongan</div>
        <div style="font-size:22px; font-weight:700; color:#0D2C6E;">Rp {{ number_format($totals['grand_total'] ?? 0, 0, ',', '.') }}</div>
    </div>
</div>

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div style="font-size:15px; font-weight:600; color:#16233A;">
        <i class="fa fa-list"></i> Daftar Potongan Berstatus Menunggu Persetujuan
    </div>

    @if ($items->count() > 0)
        <form action="{{ route('potongan-keu.terbitkan', $tipe) }}" method="POST" onsubmit="return confirmSubmit(event, 'Apakah Anda yakin ingin menerbitkan dan menyetujui seluruh {{ strtolower($tipeLabel) }} ini?', 'Konfirmasi Terbit', 'info', 'Ya, Terbitkan Sekarang');">
            @csrf
            <button type="submit" class="btn btn-success" style="padding:10px 20px; font-weight:600; font-size:14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="margin-right:6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                TERBITKAN {{ strtoupper($tipeLabel) }}
            </button>
        </form>
    @else
        <span class="badge badge-success" style="padding:8px 14px; font-size:13px;">Semua Potongan Sudah Diterbitkan</span>
    @endif
</div>

<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Entry</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="text-align:right;">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ formatTglIndo($item['tgl_potongan'], 'd/m/Y') }}</td>
                        <td class="cell-nik">{{ $item['nik'] }}</td>
                        <td class="cell-name">{{ $item['nama'] }}</td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($item[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; font-weight:600; color:#0D2C6E;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td>
                            <span class="badge badge-warning">Menunggu</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 6 }}">
                            <div class="table-empty" style="padding:32px 16px; text-align:center;">
                                <div style="font-size:16px; font-weight:600; color:#27AE60; margin-bottom:6px;">Tidak ada potongan yang menunggu penerbitan.</div>
                                <div style="font-size:13px; color:#6B7789;">Seluruh entri potongan {{ strtolower($tipeLabel) }} periode ini telah berstatus Disetujui / Diterbitkan.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($items->count() > 0)
                <tfoot style="background:#F6F8FC; font-weight:700;">
                    <tr>
                        <td colspan="4" style="text-align:center;">TOTAL KESELURUHAN</td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($totals[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; color:#0D2C6E;">Rp {{ number_format($totals['grand_total'] ?? 0, 0, ',', '.') }}</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
