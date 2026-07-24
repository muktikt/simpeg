@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
@php
    $segments = [
        ['key' => 'pegawai_tetap', 'label' => 'Pegawai tetap', 'color' => 'var(--teal-mid)'],
        ['key' => 'honorer', 'label' => 'Honorer', 'color' => 'var(--amber-mid)'],
        ['key' => 'tenaga_kontrak', 'label' => 'Tenaga kontrak', 'color' => 'var(--coral-mid)'],
        ['key' => 'calon_pegawai', 'label' => 'Calon pegawai', 'color' => 'var(--blue-mid)'],
        ['key' => 'pensiun', 'label' => 'Pensiun', 'color' => 'var(--gray-mid)'],
        ['key' => 'direksi', 'label' => 'Direksi', 'color' => 'var(--purple-mid)'],
    ];
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Dashboard</div>
    <h1>Selamat datang kembali, {{ session('simpeg_user.nama_peg', 'Pengguna') }}</h1>
</div>

<div class="ribbon-card">
    <div class="ribbon-head">
        <h2>Komposisi pegawai</h2>
        <div class="total">Total <b>{{ number_format($total, 0, ',', '.') }}</b> pegawai</div>
    </div>
    <div class="ribbon-bar">
        @foreach ($segments as $seg)
            <div class="ribbon-seg" style="width:{{ $total > 0 ? round(($komposisi[$seg['key']] / $total) * 100, 1) : 0 }}%; background:{{ $seg['color'] }};"></div>
        @endforeach
    </div>
    <div class="ribbon-legend">
        @foreach ($segments as $seg)
            <div class="legend-item">
                <div class="legend-dot"><span class="sw" style="background:{{ $seg['color'] }};"></span>{{ $seg['label'] }}</div>
                <div class="legend-num">{{ number_format($komposisi[$seg['key']], 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="label">Pengajuan cuti pending</div>
        <div class="value">{{ $stats['cuti_pending'] }}</div>
        <div class="delta">Menunggu persetujuan</div>
    </div>
    <div class="stat-card">
        <div class="label">Absensi hari ini</div>
        <div class="value">{{ number_format($stats['absensi_hari_ini'], 0, ',', '.') }}</div>
        <div class="delta">{{ round(($stats['absensi_hari_ini'] / $total) * 100, 1) }}% hadir</div>
    </div>
    <div class="stat-card">
        <div class="label">Proses gaji berjalan</div>
        <div class="value">{{ $stats['periode_gaji_berjalan'] }}</div>
        <div class="delta">Tahap verifikasi</div>
    </div>
    <div class="stat-card">
        <div class="label">Unit kerja</div>
        <div class="value">{{ $stats['jumlah_unit_kerja'] }}</div>
        <div class="delta">Tersebar beberapa wilayah</div>
    </div>
</div>

<div class="split">
    <div class="panel">
        <h3>Aktivitas masuk terbaru</h3>
        @foreach ($aktivitas as $a)
            <div class="activity-row">
                <div class="activity-dot"></div>
                <div class="activity-text">{{ $a['nama'] }} masuk ke sistem</div>
                <div class="activity-nik">{{ $a['nik'] }}</div>
                <div class="activity-time">{{ $a['jam'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <h3>Tautan cepat</h3>
        <a href="{{ route('pegawai.index') }}" class="quick-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Data pegawai
        </a>
        <a href="{{ route('absensi.laporan') }}" class="quick-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg>
            Laporan absensi
        </a>
        <a href="{{ route('gaji-laporan.slip-gaji') }}" class="quick-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Laporan slip gaji
        </a>
        <a href="{{ route('cuti.index') }}" class="quick-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            Laporan cuti pegawai
        </a>
    </div>
</div>
@endsection
