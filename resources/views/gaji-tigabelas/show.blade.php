@extends('layouts.app')

@section('title', 'Detail Slip Gaji 13 / Tunjangan Pendidikan')

@section('content')
@php 
    $myRole = session('simpeg_user.userlevel'); 
    $bisaKelola = in_array($myRole, ['1', '2']);
    $tpendidikan = $pemecahan['tunjangan_pendidikan'];
    $insentif = $pemecahan['insentif'];
    $utuh = $pemecahan['gaji_13_utuh'];
    $rasio = $pemecahan['rasio'];
    $tahunAjaran = ($gaji13['tahun'] - 1) . '/' . $gaji13['tahun'];
@endphp

<div class="page-head no-print">
    <div class="breadcrumb">Home / Gaji 13 & Tunj. Pendidikan / {{ $gaji13['nama'] }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px;">Slip Penggajian — {{ $gaji13['nama'] }}</h1>
            <p style="margin:0; font-size:13px; color:var(--text-muted);">
                NIK: <strong>{{ $gaji13['nik'] }}</strong> &middot; {{ $gaji13['jabatan'] ?? 'Pegawai' }} &middot; Unit: {{ $gaji13['unit_kerja'] ?? '-' }}
            </p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip Aktif
            </button>
            <a href="{{ in_array(session('simpeg_user.userlevel'), ['1', '2', '7']) ? route('gaji-tigabelas.index') : route('gaji-tigabelas.laporan-slip') }}" class="btn btn-outline">Kembali</a>
        </div>
    </div>
</div>

<!-- FORMAT SWITCHER TABS (NO PRINT) -->
<div class="no-print" style="margin-bottom:20px;">
    <div style="display:flex; gap:8px; flex-wrap:wrap; background:#F1F5F9; padding:6px; border-radius:12px; border:1px solid #E2E8F0; width:fit-content;">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'tpendidikan']) }}" 
           class="btn btn-sm {{ $format === 'tpendidikan' ? 'btn-primary' : 'btn-outline' }}" 
           style="border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; {{ $format !== 'tpendidikan' ? 'background:transparent; color:#475569;' : '' }}">
            <span>📄</span> Format 1: Tunjangan Pendidikan
        </a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'insentif']) }}" 
           class="btn btn-sm {{ $format === 'insentif' ? 'btn-primary' : 'btn-outline' }}" 
           style="border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; {{ $format !== 'insentif' ? 'background:transparent; color:#475569;' : '' }}">
            <span>📄</span> Format 2: Insentif Pendidikan
        </a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'utuh']) }}" 
           class="btn btn-sm {{ $format === 'utuh' ? 'btn-primary' : 'btn-outline' }}" 
           style="border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; {{ $format !== 'utuh' ? 'background:transparent; color:#475569;' : '' }}">
            <span>📄</span> Format 3: Gaji 13 Utuh
        </a>
    </div>

    <!-- REKAPITULASI PEMBAGIAN GAJI 13 -->
    <div style="margin-top:14px; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:14px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="display:flex; gap:24px; flex-wrap:wrap; align-items:center;">
            <div>
                <div style="font-size:11.5px; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Slip 1: Tunj. Pendidikan</div>
                <div style="font-size:17px; font-weight:700; color:#0369A1; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tpendidikan['diterima'], 0, ',', '.') }}</div>
            </div>
            <div style="font-size:18px; color:#CBD5E1; font-weight:300;">+</div>
            <div>
                <div style="font-size:11.5px; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Slip 2: Insentif 13</div>
                <div style="font-size:17px; font-weight:700; color:#7C3AED; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($insentif['diterima'], 0, ',', '.') }}</div>
            </div>
            <div style="font-size:18px; color:#CBD5E1; font-weight:300;">=</div>
            <div>
                <div style="font-size:11.5px; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Total Gaji 13 Bersih (100%)</div>
                <div style="font-size:19px; font-weight:800; color:#0F2A3D; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($utuh['diterima'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div style="font-size:12px; color:#475569; background:#F8FAFC; border:1px solid #E2E8F0; padding:6px 12px; border-radius:8px;">
            Rasio Potongan: <strong>{{ $rasio['persen_tpendidikan'] }}%</strong> (Tunj. Pend.) : <strong>{{ $rasio['persen_insentif'] }}%</strong> (Insentif)
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 1. FORMAT 1: SLIP TUNJANGAN PENDIDIKAN                                     --}}
{{-- ========================================================================= --}}
@if ($format === 'tpendidikan')
    @php
        $tpenPendapatan = [
            ['label' => 'GAPOK', 'value' => $tpendidikan['gapok'] ?? 0],
            ['label' => 'TUNJANGAN ISTRI', 'value' => $tpendidikan['tunjangan_istri'] ?? 0],
            ['label' => 'TUNJANGAN ANAK', 'value' => $tpendidikan['tunjangan_anak'] ?? 0],
        ];
        $tpenPotonganNon = [
            ['label' => 'POTONGAN KOPERASI (' . $rasio['persen_tpendidikan'] . '%)', 'value' => $tpendidikan['potongan_koperasi'] ?? 0],
            ['label' => 'POTONGAN KAS (' . $rasio['persen_tpendidikan'] . '%)', 'value' => $tpendidikan['potongan_kas'] ?? 0],
            ['label' => 'POTONGAN ZAKAT', 'value' => $tpendidikan['potongan_zakat'] ?? 0],
        ];
    @endphp
    @include('partials.official-slip', [
        'judul' => 'DAFTAR TUNJANGAN PENDIDIKAN & POTONGAN BULAN : JULI ' . $gaji13['tahun'],
        'data' => $gaji13,
        'pendapatanRows' => $tpenPendapatan,
        'potonganPendapatanRows' => [],
        'potonganNonPendapatanRows' => $tpenPotonganNon,
        'totalPendapatan' => $tpendidikan['total_pendapatan'],
        'totalPotonganPendapatan' => 0,
        'totalPotonganNonPendapatan' => $tpendidikan['total_potongan'],
        'pendapatanDiterima' => $tpendidikan['diterima'],
        'labelDiterima' => 'JUMLAH PENDAPATAN DITERIMA',
    ])

{{-- ========================================================================= --}}
{{-- 2. FORMAT 2: SLIP INSENTIF PENDIDIKAN                                     --}}
{{-- ========================================================================= --}}
@elseif ($format === 'insentif')
    @php
        $insPendapatan = [
            ['label' => 'TUNJANGAN JABATAN', 'value' => $insentif['insentif_jabatan'] ?? 0],
            ['label' => 'TUNJANGAN PRESTASI', 'value' => $insentif['insentif_prestasi'] ?? 0],
            ['label' => 'TUNJANGAN TRANSPORTASI', 'value' => $insentif['insentif_transportasi'] ?? 0],
            ['label' => 'TUNJANGAN PANGAN', 'value' => $insentif['insentif_pangan'] ?? 0],
            ['label' => 'TUNJANGAN BPJS KESEHATAN', 'value' => $insentif['insentif_bpjs_kesehatan'] ?? 0],
            ['label' => 'TUNJANGAN PERUMAHAN', 'value' => $insentif['insentif_perumahan'] ?? 0],
            ['label' => 'TUNJANGAN BPJS TENAGA KERJA', 'value' => $insentif['insentif_bpjs_tenaga_kerja'] ?? 0],
            ['label' => 'TUNJANGAN PERUSAHAAN', 'value' => $insentif['insentif_perusahaan'] ?? 0],
            ['label' => 'LEMBUR', 'value' => $insentif['lembur'] ?? 0],
            ['label' => 'TUNJANGAN PAJAK', 'value' => $insentif['insentif_pajak'] ?? 0],
            ['label' => 'TUNJANGAN AIR MINUM', 'value' => $insentif['insentif_air_minum'] ?? 0],
            ['label' => 'TUNJANGAN KOMUNIKASI', 'value' => $insentif['insentif_komunikasi'] ?? 0],
        ];
        $insPotonganPend = [
            ['label' => 'POTONGAN PAJAK', 'value' => $insentif['potongan_pajak'] ?? 0],
        ];
        $insPotonganNon = [
            ['label' => 'POTONGAN KOPERASI (' . $rasio['persen_insentif'] . '%)', 'value' => $insentif['potongan_koperasi'] ?? 0],
            ['label' => 'POTONGAN KAS (' . $rasio['persen_insentif'] . '%)', 'value' => $insentif['potongan_kas'] ?? 0],
            ['label' => 'POTONGAN DARMA WANITA', 'value' => $insentif['potongan_darma_wanita'] ?? 0],
            ['label' => 'POTONGAN REKENING AIR MINUM', 'value' => $insentif['potongan_rekening_air_minum'] ?? 0],
            ['label' => 'POTONGAN BANK BJB', 'value' => $insentif['potongan_bank_bjb'] ?? 0],
            ['label' => 'POTONGAN BANK BJBS', 'value' => $insentif['potongan_bank_bjbs'] ?? 0],
            ['label' => 'POTONGAN BANK BTN', 'value' => $insentif['potongan_bank_btn'] ?? 0],
            ['label' => 'POTONGAN BANK BPR', 'value' => $insentif['potongan_bank_bpr'] ?? 0],
            ['label' => 'POTONGAN ASURANSI/LAINNYA', 'value' => $insentif['potongan_asuransi'] ?? 0],
            ['label' => 'POTONGAN ZAKAT', 'value' => $insentif['potongan_zakat_profesi'] ?? 0],
        ];
    @endphp
    @include('partials.official-slip', [
        'judul' => 'DAFTAR INSENTIF PENDIDIKAN & POTONGAN BULAN : JULI ' . $gaji13['tahun'],
        'data' => $gaji13,
        'pendapatanRows' => $insPendapatan,
        'potonganPendapatanRows' => $insPotonganPend,
        'potonganNonPendapatanRows' => $insPotonganNon,
        'totalPendapatan' => $insentif['total_insentif'],
        'totalPotonganPendapatan' => $insentif['potongan_pajak'] ?? 0,
        'totalPotonganNonPendapatan' => ($insentif['total_potongan'] ?? 0) - ($insentif['potongan_pajak'] ?? 0),
        'pendapatanDiterima' => $insentif['diterima'],
        'labelDiterima' => 'JUMLAH INSENTIF DITERIMA',
    ])

{{-- ========================================================================= --}}
{{-- 3. FORMAT 3: SLIP GAJI 13 UTUH / AWAL (GAMBAR 2 ASLI)                     --}}
{{-- ========================================================================= --}}
@else
    @include('partials.official-slip', [
        'judul' => 'DAFTAR GAJI TIGA BELAS BULAN : JULI ' . $gaji13['tahun'],
        'data' => $gaji13,
        'labelDiterima' => 'JUMLAH PENDAPATAN DITERIMA',
    ])
@endif

@if ($gaji13['status'] !== 'terbit' && ($gaji13['bisa_approve'] ?? false))
    <div class="form-actions no-print" style="max-width:840px; margin:20px auto 0; padding:16px; background:#fff; border-radius:12px; border:1px solid #E2E8F0; display:flex; justify-content:flex-end;">
        <form action="{{ route('gaji-tigabelas.terbitkan', $gaji13['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui Gaji 13 ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'warning', 'Ya, Setujui');">
            @csrf
            <button type="submit" class="btn btn-primary">Setujui ke Tahap Berikutnya</button>
        </form>
    </div>
@endif
@endsection
