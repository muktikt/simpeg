@extends('layouts.app')

@section('title', 'Detail Slip Insentif')

@section('content')
@php 
    $myRole = session('simpeg_user.userlevel'); 
    $backRoute = ($myRole === '5') ? route('insentif.laporan-slip', ['my' => 1]) : route('insentif.laporan-slip', ['tahun' => $item['tahun'], 'bulan' => $item['bulan']]);
@endphp

<div class="page-head no-print">
    <div class="breadcrumb">Home / Laporan Insentif / {{ $item['nama'] }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h1 style="margin:0;">Slip Insentif - {{ $item['nama'] }}</h1>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip Resmi
            </button>
            <a href="{{ $backRoute }}" class="btn btn-outline">Kembali</a>
        </div>
    </div>
</div>

@php
    $insPendapatan = [
        ['label' => 'TUNJANGAN JABATAN', 'value' => $item['insentif_jabatan'] ?? 0],
        ['label' => 'TUNJANGAN PRESTASI', 'value' => $item['insentif_prestasi'] ?? 0],
        ['label' => 'TUNJANGAN TRANSPORTASI', 'value' => $item['insentif_transportasi'] ?? 0],
        ['label' => 'TUNJANGAN PANGAN', 'value' => $item['insentif_pangan'] ?? 0],
        ['label' => 'TUNJANGAN BPJS KESEHATAN', 'value' => $item['insentif_bpjs_kesehatan'] ?? 0],
        ['label' => 'TUNJANGAN PERUMAHAN', 'value' => $item['insentif_perumahan'] ?? 0],
        ['label' => 'TUNJANGAN BPJS TENAGA KERJA', 'value' => $item['insentif_bpjs_tenaga_kerja'] ?? 0],
        ['label' => 'TUNJANGAN PERUSAHAAN', 'value' => $item['insentif_perusahaan'] ?? 0],
        ['label' => 'LEMBUR', 'value' => $item['lembur'] ?? 0],
        ['label' => 'TUNJANGAN PAJAK', 'value' => $item['insentif_pajak'] ?? 0],
        ['label' => 'TUNJANGAN AIR MINUM', 'value' => $item['insentif_air_minum'] ?? 0],
        ['label' => 'TUNJANGAN KOMUNIKASI', 'value' => $item['insentif_komunikasi'] ?? 0],
    ];
    $insPotonganPend = [
        ['label' => 'POTONGAN SANKSI', 'value' => $item['potongan_sanksi_perusahaan'] ?? 0],
        ['label' => 'POTONGAN PMI / LAIN-LAIN', 'value' => $item['potongan_pmi_lain'] ?? 0],
        ['label' => 'POTONGAN DAPENMA', 'value' => $item['potongan_dapenma'] ?? 0],
        ['label' => 'POTONGAN BPJS TENAGA KERJA', 'value' => $item['potongan_bpjs_tenaga_kerja'] ?? 0],
        ['label' => 'POTONGAN PERUMAHAN', 'value' => $item['potongan_perumahan'] ?? 0],
        ['label' => 'POTONGAN TUNJANGAN PERUSAHAAN', 'value' => $item['potongan_insentif_perusahaan'] ?? 0],
        ['label' => 'POTONGAN KORPRI', 'value' => $item['potongan_korpri'] ?? 0],
        ['label' => 'POTONGAN PAJAK', 'value' => $item['potongan_pajak'] ?? 0],
        ['label' => 'POTONGAN BPJS KESEHATAN', 'value' => $item['potongan_bpjs_kesehatan'] ?? 0],
    ];
    $insPotonganNon = [
        ['label' => 'POTONGAN KOPERASI', 'value' => $item['potongan_koperasi'] ?? 0],
        ['label' => 'POTONGAN DARMA WANITA', 'value' => $item['potongan_darma_wanita'] ?? 0],
        ['label' => 'POTONGAN REKENING AIR MINUM', 'value' => $item['potongan_rekening_air_minum'] ?? 0],
        ['label' => 'POTONGAN KAS', 'value' => $item['potongan_kas'] ?? 0],
        ['label' => 'POTONGAN BANK BJB', 'value' => $item['potongan_bank_bjb'] ?? 0],
        ['label' => 'POTONGAN BANK BJBS', 'value' => $item['potongan_bank_bjbs'] ?? 0],
        ['label' => 'POTONGAN BANK BTN', 'value' => $item['potongan_bank_btn'] ?? 0],
        ['label' => 'POTONGAN BANK BPR', 'value' => $item['potongan_bank_bpr'] ?? 0],
        ['label' => 'POTONGAN ASURANSI/LAINNYA', 'value' => $item['potongan_asuransi'] ?? 0],
        ['label' => 'POTONGAN ZAKAT', 'value' => $item['potongan_zakat_profesi'] ?? 0],
    ];
@endphp

@include('partials.official-slip', [
    'judul' => 'DAFTAR INSENTIF & POTONGAN PERIODE : ' . strtoupper($item['periode'] ?? ''),
    'data' => $item,
    'pendapatanRows' => $insPendapatan,
    'potonganPendapatanRows' => $insPotonganPend,
    'potonganNonPendapatanRows' => $insPotonganNon,
    'totalPendapatan' => $item['total_insentif'] ?? null,
    'pendapatanDiterima' => $item['insentif_diterima'] ?? null,
    'labelDiterima' => 'JUMLAH INSENTIF DITERIMA',
])
@endsection
