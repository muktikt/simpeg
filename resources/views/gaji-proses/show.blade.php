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
        <div><p>{{ $gaji['status'] === 'terbit' ? 'Terbit' : 'Draft' }}</p><p>Status</p></div>
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

<div class="form-actions" style="max-width:100%;">
    @if ($gaji['status'] !== 'terbit')
        <form action="{{ route('gaji-proses.terbitkan', $gaji['id']) }}" method="POST" onsubmit="event.preventDefault(); openConfirmModal(this, {title: 'Terbitkan Gaji', text: 'Terbitkan gaji ini? Setelah terbit tidak bisa diubah/dihapus lagi.', btnLabel: 'Ya, Terbitkan', theme: 'info'});">
            @csrf
            <button type="submit" class="btn btn-primary">Terbitkan Gaji</button>
        </form>
    @endif
    <a href="{{ route('gaji-proses.index') }}" class="btn btn-outline">Kembali</a>
</div>
@endsection
