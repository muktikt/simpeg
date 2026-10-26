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

<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="label">Pengajuan cuti pending</div>
        <div class="value">{{ $stats['cuti_pending'] }}</div>
        <div class="delta">Menunggu persetujuan</div>
    </div>
    <div class="stat-card">
        <div class="label">Absensi hari ini</div>
        <div class="value">{{ number_format($stats['absensi_hari_ini'], 0, ',', '.') }}</div>
        <div class="delta">{{ $total > 0 ? round(($stats['absensi_hari_ini'] / $total) * 100, 1) : 0 }}% hadir</div>
    </div>
    <div class="stat-card">
        <div class="label">Pengaduan aktif</div>
        <div class="value" style="color: {{ ($stats['pengaduan_pending'] ?? 0) > 0 ? '#E11D48' : 'inherit' }}">{{ $stats['pengaduan_pending'] ?? 0 }}</div>
        <div class="delta">
            <a href="{{ route('pengaduan.index') }}" style="color:var(--primary); text-decoration:none; font-weight:600;">Lihat pengaduan &rarr;</a>
        </div>
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
        <a href="{{ route('pengaduan.index') }}" class="quick-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Pengaduan Pegawai (WBS)
        </a>
        <a href="{{ route('pengumuman.index') }}" class="quick-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Kelola Pengumuman
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
