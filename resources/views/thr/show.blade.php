@extends('layouts.app')

@section('title', 'Detail THR')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Proses THR / {{ $thr['nama'] }}</div>
    <h1>Detail THR - {{ $thr['nama'] }}</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper(collect(explode(' ', $thr['nama']))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}</div>
    <div class="info">
        <h2>{{ $thr['nama'] }}</h2>
        <p>{{ \App\Http\Controllers\ThrController::KATEGORI[$thr['kategori']] ?? $thr['kategori'] }} &middot; PTKP {{ $thr['kode_ptkp'] }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $thr['nik'] }}</p><p>NIK</p></div>
        <div><p>{{ $thr['tahun'] }}</p><p>Tahun THR</p></div>
        <div><p>{{ $thr['status'] === 'terbit' ? 'Terbit (Final)' : \App\Http\Controllers\ThrController::approvalStatusLabel($thr['status']) }}</p><p>Status</p></div>
        <div><p>{{ $thr['disetujui_oleh'] }}</p><p>Disetujui Oleh</p></div>
    </div>
</div>

<div class="split">
    <div class="panel">
        <h3>Komponen Pendapatan</h3>
        @foreach ($komponenPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($thr[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="activity-row" style="font-weight:600;">
            <div class="activity-text">Total Pendapatan</div>
            <div class="activity-time">Rp {{ number_format($thr['total_pendapatan'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="panel">
        <h3>Potongan dari Pendapatan</h3>
        @foreach ($potonganPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($thr[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach

        <h3 style="margin-top:20px;">Potongan Non-Pendapatan</h3>
        @foreach ($potonganNonPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($thr[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="activity-row" style="font-weight:600;">
            <div class="activity-text">Total Potongan</div>
            <div class="activity-time">Rp {{ number_format($thr['total_potongan_pendapatan'] + $thr['total_potongan_non_pendapatan'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="form-card" style="max-width:100%; margin-top:16px; background:var(--teal-soft);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;">THR Diterima</div>
        <div style="font-family:'IBM Plex Mono',monospace; font-size:22px; font-weight:600; color:var(--teal-dark);">Rp {{ number_format($thr['thr_diterima'], 0, ',', '.') }}</div>
    </div>
</div>

<div class="form-actions" style="max-width:100%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div style="display:flex; gap:10px; align-items:center;">
        @if ($thr['status'] !== 'terbit' && ($thr['bisa_approve'] ?? false))
            <form action="{{ route('thr.terbitkan', $thr['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui THR ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'warning', 'Ya, Setujui');">
                @csrf
                <button type="submit" class="btn btn-primary">Setujui ke Tahap Berikutnya</button>
            </form>
        @endif
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" style="margin-right:6px;"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
            Cetak Slip
        </button>
    </div>
    <a href="{{ in_array(session('simpeg_user.userlevel'), ['1', '2', '7']) ? route('thr.index') : route('thr.laporan-slip') }}" class="btn btn-outline">Kembali</a>
</div>
@endsection
