@extends('layouts.app')

@section('title', 'Pengaduan Pegawai (Whistleblowing)')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaduan</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0;">Pengaduan Pegawai</h1>
            <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);">
                Sistem Penanganan Pelanggaran Disiplin Pegawai & Whistleblowing
            </p>
        </div>
        <div>
            @php
                $roleLabel = match($myRole) {
                    'kadiv' => 'Kadiv (' . ucfirst($divisiKadiv ?? 'Kategori') . ')',
                    'kspi' => 'KSPI (Pengawas Internal)',
                    'dirut' => 'Direktur Utama (DIRUT)',
                    'tpdpk' => 'TPDPK (Tim Penegak Disiplin)',
                    'sdm' => 'SDM (Pelapor)',
                    default => 'Pegawai (Pelapor)',
                };
                $roleColor = match($myRole) {
                    'dirut' => '#e67e22',
                    'kspi' => '#2e86ab',
                    'tpdpk' => '#8e44ad',
                    'kadiv' => '#16a085',
                    default => '#34495e',
                };
            @endphp
            <span style="background:{{ $roleColor }}; color:#fff; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#fff;"></span>
                Role: {{ $roleLabel }}
            </span>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success" style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
        ✓ {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger" style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
        ✕ {{ session('error') }}
    </div>
@endif

@php
    $isPrivileged = in_array($myRole, ['kadiv', 'kspi', 'dirut', 'tpdpk']);
@endphp

@if ($isPrivileged)
    {{-- TAMPILAN KHUSUS ROLE STRUKTURAL (Kadiv, KSPI, Dirut, TPDPK) --}}
    <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:10px; flex-wrap:wrap;">
        <a href="{{ route('pengaduan.index', ['tab' => 'masuk']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'masuk' ? 'background:var(--primary, #0d2c6e); color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
            📥 Kotak Masuk Tindakan
            @if (count($pengaduanMasuk) > 0)
                <span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 6px; border-radius:10px;">{{ count($pengaduanMasuk) }}</span>
            @endif
        </a>
        <a href="{{ route('pengaduan.index', ['tab' => 'riwayat']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'riwayat' ? 'background:var(--primary, #0d2c6e); color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
            📋 Riwayat Terproses ({{ count($pengaduanRiwayat) }})
        </a>
        <a href="{{ route('pengaduan.index', ['tab' => 'saya']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'saya' ? 'background:var(--primary, #0d2c6e); color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
            👤 Aduan Pribadi Saya ({{ count($pengaduanSaya) }})
        </a>
        <a href="{{ route('pengaduan.index', ['tab' => 'buat']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; margin-left:auto; {{ $tab === 'buat' ? 'background:#16a34a; color:#fff;' : 'background:#dcfce7; color:#15803d;' }}">
            ➕ Buat Pengaduan Baru
        </a>
    </div>

    @if ($tab === 'masuk')
        {{-- TAB KOTAK MASUK TINDAKAN --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="font-size:16px; margin:0;">Pengaduan Menunggu Tindakan Anda</h2>
                    <p style="margin:2px 0 0; font-size:12px; color:var(--text-muted);">
                        @if ($myRole === 'kadiv')
                            Verifikasi pengaduan kategori <strong>{{ $divisiKadiv === 'teknik' ? 'Pelanggaran Teknik' : 'Pelanggaran Administrasi' }}</strong>
                        @elseif ($myRole === 'kspi')
                            Review pengaduan masuk & penunjukan eksekutor investigasi
                        @elseif ($myRole === 'dirut')
                            Approval kelayakan investigasi & persetujuan sanksi
                        @elseif ($myRole === 'tpdpk')
                            Penugasan investigasi lapangan & penyusunan berita acara
                        @endif
                    </p>
                </div>
                <span style="font-size:12px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:12px;">{{ count($pengaduanMasuk) }} Perlu Aksi</span>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanMasuk, 'action' => true])
        </div>

    @elseif ($tab === 'riwayat')
        {{-- TAB RIWAYAT TERPROSES --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
                <h2 style="font-size:16px; margin:0;">Riwayat Pengaduan yang Telah Diproses</h2>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanRiwayat, 'action' => true])
        </div>

    @elseif ($tab === 'saya')
        {{-- TAB ADUAN PRIBADI --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
                <h2 style="font-size:16px; margin:0;">Daftar Pengaduan yang Anda Kirimkan</h2>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanSaya, 'action' => false])
        </div>

    @elseif ($tab === 'buat')
        {{-- TAB FORM BUAT PENGADUAN --}}
        <div class="ribbon-card" style="max-width:750px; margin:0 auto;">
            <div class="ribbon-head" style="margin-bottom:16px;">
                <h2 style="font-size:17px;">Formulir Pengaduan Baru</h2>
            </div>
            @include('pengaduan.partials.form')
        </div>
    @endif

@else
    {{-- TAMPILAN KHUSUS PEGAWAI / SDM (PELAPOR) --}}
    <div style="display:grid; grid-template-columns: 380px 1fr; gap:20px; align-items:start;">
        <!-- Form Pengaduan Baru -->
        <div class="ribbon-card">
            <div class="ribbon-head" style="margin-bottom:12px;">
                <h2 style="font-size:16px;">Kirim Pengaduan Baru</h2>
            </div>
            <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:16px; line-height:1.4;">
                Sampaikan kendala, pertanyaan, atau laporan pelanggaran secara aman dan rahasia melalui Whistleblowing System.
            </p>
            @include('pengaduan.partials.form')
        </div>

        <!-- Tabel Daftar Pengaduan Saya -->
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size:16px; margin:0;">Daftar Pengaduan Saya</h2>
                <span style="font-size:12px; color:var(--text-muted);">Total: {{ count($pengaduanSaya) }} Pengaduan</span>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanSaya, 'action' => false])
        </div>
    </div>
@endif

@endsection
