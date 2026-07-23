@extends('layouts.app')

@section('title', 'Detail Gaji 13')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Gaji 13 / {{ $gaji13['nama'] }}</div>
    <h1>Detail Gaji 13 - {{ $gaji13['nama'] }}</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper(collect(explode(' ', $gaji13['nama']))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}</div>
    <div class="info">
        <h2>{{ $gaji13['nama'] }}</h2>
        <p>{{ \App\Http\Controllers\GajiTigabelasController::KATEGORI[$gaji13['kategori']] ?? $gaji13['kategori'] }} &middot; PTKP {{ $gaji13['kode_ptkp'] }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $gaji13['nik'] }}</p><p>NIK</p></div>
        <div><p>{{ $gaji13['tahun'] }}</p><p>Tahun</p></div>
        <div><p>{{ $gaji13['status'] === 'terbit' ? 'Terbit' : 'Draft' }}</p><p>Status</p></div>
        <div><p>{{ $gaji13['disetujui_oleh'] }}</p><p>Disetujui Oleh</p></div>
    </div>
</div>

<div class="split">
    <div class="panel">
        <h3>Komponen Pendapatan</h3>
        @foreach ($komponenPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($gaji13[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="activity-row" style="font-weight:600;">
            <div class="activity-text">Total Pendapatan</div>
            <div class="activity-time">Rp {{ number_format($gaji13['total_pendapatan'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="panel">
        <h3>Potongan dari Pendapatan</h3>
        @foreach ($potonganPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($gaji13[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach

        <h3 style="margin-top:20px;">Potongan Non-Pendapatan</h3>
        @foreach ($potonganNonPendapatan as $key => $label)
            <div class="activity-row">
                <div class="activity-text">{{ $label }}</div>
                <div class="activity-time">Rp {{ number_format($gaji13[$key] ?? 0, 0, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="activity-row" style="font-weight:600;">
            <div class="activity-text">Total Potongan</div>
            <div class="activity-time">Rp {{ number_format($gaji13['total_potongan_pendapatan'] + $gaji13['total_potongan_non_pendapatan'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="form-card" style="max-width:100%; margin-top:16px; background:var(--teal-soft);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;">Gaji 13 Diterima</div>
        <div style="font-family:'IBM Plex Mono',monospace; font-size:22px; font-weight:600; color:var(--teal-dark);">Rp {{ number_format($gaji13['gaji13_diterima'], 0, ',', '.') }}</div>
    </div>
</div>

<div class="form-actions" style="max-width:100%;">
    @if ($bisaKelola && $gaji13['status'] !== 'terbit')
        <form action="{{ route('gaji-tigabelas.terbitkan', $gaji13['id']) }}" method="POST" onsubmit="event.preventDefault(); openConfirmModal(this, {title: 'Terbitkan Gaji 13', text: 'Terbitkan Gaji 13 ini? Setelah terbit tidak bisa diubah/dihapus lagi.', btnLabel: 'Ya, Terbitkan', theme: 'info'});">
            @csrf
            <button type="submit" class="btn btn-primary">Terbitkan Gaji 13</button>
        </form>
    @endif
    <a href="{{ route('gaji-tigabelas.index') }}" class="btn btn-outline">Kembali</a>
</div>
@endsection
