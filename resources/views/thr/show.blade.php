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
        <div><p>{{ $thr['status'] === 'terbit' ? 'Terbit' : 'Draft' }}</p><p>Status</p></div>
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

<div class="form-actions" style="max-width:100%;">
    @if ($bisaKelola && $thr['status'] !== 'terbit')
        <form action="{{ route('thr.terbitkan', $thr['id']) }}" method="POST" onsubmit="return confirm('Terbitkan THR ini? Setelah terbit tidak bisa diubah/dihapus lagi.');">
            @csrf
            <button type="submit" class="btn btn-primary">Terbitkan THR</button>
        </form>
    @endif
    <a href="{{ route('thr.index') }}" class="btn btn-outline">Kembali</a>
</div>
@endsection
