@extends('layouts.app')

@section('title', 'Daftar Insentif Pendidikan')

@section('content')
@php
    $myRole = session('simpeg_user.userlevel');
    $bisaKelola = in_array($myRole, ['1', '2']);
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / SDM / Insentif Pendidikan</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px;">Daftar Insentif Pendidikan (2026)</h1>
            <p style="margin:0; font-size:13px; color:var(--text-muted);">Porsi Tunjangan di luar Pokok &amp; Potongan Lengkap sesuai Regulasi Perumdam.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            @if ($bisaKelola)
                <a href="{{ route('insentif-pendidikan.create') }}" class="btn btn-primary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
                    + Input Insentif Pendidikan
                </a>
            @endif
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:16px; margin-bottom:20px;">
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:12px; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Total Insentif</div>
        <div style="font-size:20px; font-weight:700; color:#15803D; margin-top:4px; font-family:'IBM Plex Mono',monospace;">
            Rp {{ number_format($totalInsentifSemua, 0, ',', '.') }}
        </div>
    </div>
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:12px; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Total Potongan</div>
        <div style="font-size:20px; font-weight:700; color:#DC2626; margin-top:4px; font-family:'IBM Plex Mono',monospace;">
            Rp {{ number_format($totalPotonganSemua, 0, ',', '.') }}
        </div>
    </div>
    <div style="background:#FFFFFF; border:1px solid var(--border); border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="font-size:12px; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Total Insentif Diterima</div>
        <div style="font-size:20px; font-weight:800; color:#0284C7; margin-top:4px; font-family:'IBM Plex Mono',monospace;">
            Rp {{ number_format($totalDiterimaSemua, 0, ',', '.') }}
        </div>
    </div>
</div>

<!-- Toolbar Filter -->
<div class="toolbar">
    <form method="GET" action="{{ route('insentif-pendidikan.index') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <select name="periode" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; background:#fff;">
            @for ($y = now()->year; $y >= 2024; $y--)
                <option value="JULI {{ $y }}" @selected($periode === "JULI {$y}")>JULI {{ $y }}</option>
            @endfor
            <option value="all" @selected($periode === 'all')>-- Semua Periode --</option>
        </select>
        
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari NIK atau Nama..." style="border:none; outline:none; font-size:13px; width:200px;">
        </div>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        @if (!empty($search) || ($periode !== ('JULI ' . now()->year) && $periode !== 'all'))
            <a href="{{ route('insentif-pendidikan.index') }}" class="btn btn-outline btn-sm">Reset</a>
        @endif
    </form>
</div>

<!-- Table Card -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama &amp; Jabatan</th>
                <th>Periode</th>
                <th style="text-align:right;">Jumlah Insentif</th>
                <th style="text-align:right;">Pot. Insentif</th>
                <th style="text-align:right;">Pot. Non-Insentif</th>
                <th style="text-align:right;">Total Potongan</th>
                <th style="text-align:right;">Insentif Diterima</th>
                <th style="text-align:center; width:1%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $it)
                <tr>
                    <td class="cell-nik"><strong>{{ $it['nik'] }}</strong></td>
                    <td>
                        <div style="font-weight:600; color:var(--text);">{{ $it['nama'] }}</div>
                        <div style="font-size:11.5px; color:var(--text-muted);">{{ $it['jabatan'] }} &middot; {{ $it['unit_kerja'] }}</div>
                    </td>
                    <td><span class="badge" style="background:#F3E8FF; color:#7E22CE; font-weight:600;">{{ $it['periode'] }}</span></td>
                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace; font-weight:600; color:#15803D;">Rp {{ number_format($it['total_insentif'], 0, ',', '.') }}</td>
                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace; color:#DC2626;">Rp {{ number_format($it['total_potongan_insentif'], 0, ',', '.') }}</td>
                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace; color:#B45309;">Rp {{ number_format($it['total_potongan_non_insentif'], 0, ',', '.') }}</td>
                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace; font-weight:600; color:#DC2626;">Rp {{ number_format($it['total_potongan'], 0, ',', '.') }}</td>
                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace; font-weight:800; color:#0F2A3D;">Rp {{ number_format($it['diterima'], 0, ',', '.') }}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <div class="row-actions" style="justify-content:center;">
                            <a href="{{ route('insentif-pendidikan.show', $it['id']) }}" class="btn btn-outline btn-sm" title="Lihat Slip & Cetak">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                Slip
                            </a>
                            @if ($bisaKelola)
                                <form action="{{ route('insentif-pendidikan.destroy', $it['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus data Insentif Pendidikan ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:32px;">
                        <div style="color:var(--text-muted); font-size:14px; margin-bottom:12px;">Belum ada data Insentif Pendidikan untuk periode ini.</div>
                        @if ($bisaKelola)
                            <a href="{{ route('insentif-pendidikan.create') }}" class="btn btn-primary btn-sm">+ Input Data Sekarang</a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
