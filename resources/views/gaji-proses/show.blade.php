@extends('layouts.app')

@section('title', 'Detail Proses Gaji')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Proses Gaji Bulanan / {{ $gaji['nama'] }}</div>
    <h1>Detail Proses Gaji - {{ $gaji['nama'] }}</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper(collect(explode(' ', $gaji['nama']))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}</div>
    <div class="info">
        <h2>{{ $gaji['nama'] }}</h2>
        <p>{{ \App\Http\Controllers\GajiProsesController::KATEGORI[$gaji['kategori']] ?? $gaji['kategori'] }} &middot; PTKP {{ $gaji['kode_ptkp'] }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $gaji['nik'] }}</p><p>NIK</p></div>
        <div><p>{{ \App\Http\Controllers\AbsensiController::BULAN[$gaji['bulan']] }} {{ $gaji['tahun'] }}</p><p>Periode</p></div>
        <div><p>{{ $gaji['status'] === 'terbit' ? 'Terbit (Final)' : \App\Http\Controllers\GajiProsesController::approvalStatusLabel($gaji['status']) }}</p><p>Status</p></div>
    </div>
</div>

<div class="split">
    <div class="panel">
        <h3>Komponen Pendapatan</h3>
        @foreach ($komponenPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($gaji[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="activity-row" style="font-weight:600;">
            <div class="activity-text">Total Pendapatan</div>
            <div class="activity-time">Rp {{ number_format($gaji['total_pendapatan'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="panel">
        <h3>Komponen Potongan</h3>
        @foreach ($komponenPotongan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($gaji[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="activity-row" style="font-weight:600;">
            <div class="activity-text">Total Potongan</div>
            <div class="activity-time">Rp {{ number_format($gaji['total_potongan'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="form-card" style="max-width:100%; margin-top:16px; background:var(--teal-soft);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;">Gaji Bersih (Take Home Pay)</div>
        <div style="font-family:'IBM Plex Mono',monospace; font-size:22px; font-weight:600; color:var(--teal-dark);">Rp {{ number_format($gaji['gaji_bersih'], 0, ',', '.') }}</div>
    </div>
</div>

<div class="form-actions" style="max-width:100%; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div style="display:flex; gap:10px; align-items:center;">
        @if ($gaji['status'] !== 'terbit' && ($gaji['bisa_approve'] ?? false))
            <form action="{{ route('gaji-proses.terbitkan', $gaji['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui gaji ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'warning', 'Ya, Setujui');">
                @csrf
                <button type="submit" class="btn btn-primary">Setujui ke Tahap Berikutnya</button>
            </form>
        @endif
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" style="margin-right:6px;"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
            Cetak Slip
        </button>
    </div>
    <a href="{{ in_array(session('simpeg_user.userlevel'), ['1', '2', '7']) ? route('gaji-proses.index') : route('gaji-laporan.slip-gaji') }}" class="btn btn-outline">Kembali</a>
</div>
@endsection
