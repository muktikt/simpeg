@extends('layouts.app')

@section('title', 'Detail THR')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

@php
    $totalPotongan = ($thr['total_potongan_pendapatan'] ?? 0) + ($thr['total_potongan_non_pendapatan'] ?? 0);
@endphp

<div class="page-head no-print">
    <div class="breadcrumb">Home / Proses THR / {{ $thr['nama'] }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h1 style="margin:0;">Slip Tunjangan Hari Raya (THR) - {{ $thr['nama'] }}</h1>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip Resmi
            </button>
            <a href="{{ in_array(session('simpeg_user.userlevel'), ['1', '2', '7']) ? route('thr.index') : route('thr.laporan-slip') }}" class="btn btn-outline">Kembali</a>
        </div>
    </div>
</div>

@include('partials.official-slip', [
    'judul' => 'DAFTAR TUNJANGAN HARI RAYA TAHUN : ' . $thr['tahun'],
    'data' => $thr,
    'labelDiterima' => 'JUMLAH PENDAPATAN DITERIMA',
])

@if ($thr['status'] !== 'terbit' && ($thr['bisa_approve'] ?? false))
    <div class="form-actions no-print" style="max-width:820px; margin:20px auto 0; padding:16px; background:#fff; border-radius:12px; border:1px solid #E2E8F0; display:flex; justify-content:flex-end;">
        <form action="{{ route('thr.terbitkan', $thr['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui THR ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'warning', 'Ya, Setujui');">
            @csrf
            <button type="submit" class="btn btn-primary">Setujui ke Tahap Berikutnya</button>
        </form>
    </div>
@endif
@endsection
