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
{{-- 1. FORMAT 1: SLIP TUNJANGAN PENDIDIKAN (GAMBAR 1)                          --}}
{{-- ========================================================================= --}}
@if ($format === 'tpendidikan')
<div class="slip-doc-container">
    <!-- Header KOP Resmi -->
    <div class="slip-header-kop">
        <div class="slip-kop-brand">
            <div class="slip-kop-company">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-kop-sub">KABUPATEN INDRAMAYU &middot; JAWA BARAT</div>
            <div class="slip-kop-address">Jl. Letjen Suprapto No25/E, Indramayu 45214 Telp (0234) 271322</div>
        </div>
        <div class="slip-kop-title-box">
            <div class="slip-title-text">DAFTAR TUNJANGAN PENDIDIKAN & POTONGAN</div>
            <div class="slip-badge-periode">BULAN : JULI {{ $gaji13['tahun'] }}</div>
        </div>
    </div>

    <!-- Info Pegawai -->
    <div class="slip-emp-box">
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">NIK</span><span class="sep">:</span><span class="v">{{ $gaji13['nik'] }}</span></div>
            <div class="slip-emp-item"><span class="k">NAMA</span><span class="sep">:</span><span class="v" style="font-size:13.5px; color:#0F2A3D; font-weight:700;">{{ strtoupper($gaji13['nama']) }}</span></div>
            <div class="slip-emp-item"><span class="k">GOLONGAN</span><span class="sep">:</span><span class="v">{{ $gaji13['golongan'] ?: '-' }} (PTKP: {{ $gaji13['kode_ptkp'] }})</span></div>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">UNIT KERJA</span><span class="sep">:</span><span class="v">{{ strtoupper($gaji13['unit_kerja'] ?? '-') }}</span></div>
            <div class="slip-emp-item"><span class="k">JABATAN</span><span class="sep">:</span><span class="v">{{ strtoupper($gaji13['jabatan'] ?? 'Pegawai') }}</span></div>
            <div class="slip-emp-item"><span class="k">STATUS SLIP</span><span class="sep">:</span><span class="v" style="color:{{ $gaji13['status'] === 'terbit' ? '#16A34A' : '#D97706' }}; font-weight:700;">{{ strtoupper($gaji13['status'] === 'terbit' ? 'Terbit & Final' : $gaji13['status']) }}</span></div>
        </div>
    </div>

    <!-- Rincian 2 Kolom Vertikal (Pendapatan & Potongan) -->
    <div class="slip-columns-wrap">
        <!-- Kolom Penerimaan (HANYA GAPOK + ISTRI + ANAK) -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#0369A1; background:#F0F9FF; border-color:#BAE6FD;">
                I. PENERIMAAN TUNJ. PENDIDIKAN
            </div>
            <div class="slip-items-body">
                <div class="slip-row-item">
                    <span class="item-label">Gaji Pokok</span>
                    <span class="item-val">Rp {{ number_format($tpendidikan['gapok'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row-item">
                    <span class="item-label">Tunjangan Istri / Suami</span>
                    <span class="item-val">Rp {{ number_format($tpendidikan['tunjangan_istri'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row-item">
                    <span class="item-label">Tunjangan Anak</span>
                    <span class="item-val">Rp {{ number_format($tpendidikan['tunjangan_anak'], 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="slip-col-total" style="background:#F0F9FF; border-color:#BAE6FD;">
                <span>JUMLAH PENDAPATAN (A)</span>
                <span class="tot-val" style="color:#0369A1;">Rp {{ number_format($tpendidikan['total_pendapatan'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Kolom Potongan (KOPERASI & KAS PROPORSI TPENDIDIKAN) -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#B91C1C; background:#FEF2F2; border-color:#FECACA;">
                II. POTONGAN (PROPORSIONAL)
            </div>
            <div class="slip-items-body">
                <div class="slip-row-item">
                    <span class="item-label">Potongan Koperasi ({{ $rasio['persen_tpendidikan'] }}%)</span>
                    <span class="item-val" style="color:#DC2626;">Rp {{ number_format($tpendidikan['potongan_koperasi'], 0, ',', '.') }}</span>
                </div>
                <div class="slip-row-item">
                    <span class="item-label">Potongan Kas ({{ $rasio['persen_tpendidikan'] }}%)</span>
                    <span class="item-val" style="color:#DC2626;">Rp {{ number_format($tpendidikan['potongan_kas'], 0, ',', '.') }}</span>
                </div>
                @if ($tpendidikan['potongan_zakat'] > 0)
                <div class="slip-row-item">
                    <span class="item-label">Potongan Zakat</span>
                    <span class="item-val" style="color:#DC2626;">Rp {{ number_format($tpendidikan['potongan_zakat'], 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
            <div class="slip-col-total" style="background:#FEF2F2; border-color:#FECACA;">
                <span>JUMLAH POTONGAN (B)</span>
                <span class="tot-val" style="color:#DC2626;">Rp {{ number_format($tpendidikan['total_potongan'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Box Take Home Pay (Tunjangan Pendidikan Bersih) -->
    <div class="slip-thp-wrapper">
        <div>
            <div class="slip-thp-title">JUMLAH PENDAPATAN DITERIMA (BERSIH = A - B)</div>
            <div class="slip-thp-terbilang">Terbilang: # {{ terbilang($tpendidikan['diterima']) }} Rupiah #</div>
        </div>
        <div class="slip-thp-nominal">
            Rp {{ number_format($tpendidikan['diterima'], 0, ',', '.') }}
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan Resmi -->
    <div class="slip-signatures-grid">
        <div class="slip-sig-box">
            <div class="slip-sig-role">Penerima / Pegawai,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">{{ strtoupper($gaji13['nama']) }}</div>
            <div class="slip-sig-nip">NIK. {{ $gaji13['nik'] }}</div>
        </div>
        <div class="slip-sig-box">
            <div class="slip-sig-role">Indramayu, Juli {{ $gaji13['tahun'] }}<br>Bagian Keuangan & Penggajian,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-sig-nip">Kasubag / Staf Keuangan</div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 2. FORMAT 2: SLIP INSENTIF PENDIDIKAN (GAMBAR 2)                           --}}
{{-- ========================================================================= --}}
@elseif ($format === 'insentif')
<div class="slip-doc-container">
    <!-- Header KOP Resmi -->
    <div class="slip-header-kop">
        <div class="slip-kop-brand">
            <div class="slip-kop-company">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-kop-sub">KABUPATEN INDRAMAYU &middot; JAWA BARAT</div>
            <div class="slip-kop-address">Jl. Letjen Suprapto No25/E, Indramayu 45214 Telp (0234) 271322</div>
        </div>
        <div class="slip-kop-title-box">
            <div class="slip-title-text">DAFTAR INSENTIF PENDIDIKAN & POTONGAN</div>
            <div class="slip-badge-periode">BULAN : JULI {{ $gaji13['tahun'] }}</div>
        </div>
    </div>

    <!-- Info Pegawai -->
    <div class="slip-emp-box">
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">NIK</span><span class="sep">:</span><span class="v">{{ $gaji13['nik'] }}</span></div>
            <div class="slip-emp-item"><span class="k">NAMA</span><span class="sep">:</span><span class="v" style="font-size:13.5px; color:#0F2A3D; font-weight:700;">{{ strtoupper($gaji13['nama']) }}</span></div>
            <div class="slip-emp-item"><span class="k">GOLONGAN</span><span class="sep">:</span><span class="v">{{ $gaji13['golongan'] ?: '-' }} (PTKP: {{ $gaji13['kode_ptkp'] }})</span></div>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">UNIT KERJA</span><span class="sep">:</span><span class="v">{{ strtoupper($gaji13['unit_kerja'] ?? '-') }}</span></div>
            <div class="slip-emp-item"><span class="k">JABATAN</span><span class="sep">:</span><span class="v">{{ strtoupper($gaji13['jabatan'] ?? 'Pegawai') }}</span></div>
            <div class="slip-emp-item"><span class="k">STATUS SLIP</span><span class="sep">:</span><span class="v" style="color:{{ $gaji13['status'] === 'terbit' ? '#16A34A' : '#D97706' }}; font-weight:700;">{{ strtoupper($gaji13['status'] === 'terbit' ? 'Terbit & Final' : $gaji13['status']) }}</span></div>
        </div>
    </div>

    <!-- Rincian 2 Kolom Vertikal (Insentif & Potongan) -->
    <div class="slip-columns-wrap">
        <!-- Kolom Penerimaan Insentif -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#7C3AED; background:#F5F3FF; border-color:#DDD6FE;">
                I. PENERIMAAN INSENTIF
            </div>
            <div class="slip-items-body">
                @if ($insentif['insentif_jabatan'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Jabatan</span><span class="item-val">Rp {{ number_format($insentif['insentif_jabatan'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_prestasi'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Prestasi</span><span class="item-val">Rp {{ number_format($insentif['insentif_prestasi'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_transportasi'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Transportasi</span><span class="item-val">Rp {{ number_format($insentif['insentif_transportasi'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_pangan'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Pangan</span><span class="item-val">Rp {{ number_format($insentif['insentif_pangan'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_bpjs_kesehatan'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif BPJS Kesehatan</span><span class="item-val">Rp {{ number_format($insentif['insentif_bpjs_kesehatan'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_perumahan'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Perumahan</span><span class="item-val">Rp {{ number_format($insentif['insentif_perumahan'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_bpjs_tenaga_kerja'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif BPJS Ketenagakerjaan</span><span class="item-val">Rp {{ number_format($insentif['insentif_bpjs_tenaga_kerja'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_perusahaan'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Perusahaan</span><span class="item-val">Rp {{ number_format($insentif['insentif_perusahaan'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['lembur'] > 0)
                    <div class="slip-row-item"><span class="item-label">Uang Lembur</span><span class="item-val">Rp {{ number_format($insentif['lembur'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_pajak'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Pajak (PPh21)</span><span class="item-val">Rp {{ number_format($insentif['insentif_pajak'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_air_minum'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Air Minum</span><span class="item-val">Rp {{ number_format($insentif['insentif_air_minum'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['insentif_komunikasi'] > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Komunikasi</span><span class="item-val">Rp {{ number_format($insentif['insentif_komunikasi'], 0, ',', '.') }}</span></div>
                @endif
            </div>
            <div class="slip-col-total" style="background:#F5F3FF; border-color:#DDD6FE;">
                <span>JUMLAH INSENTIF (A)</span>
                <span class="tot-val" style="color:#7C3AED;">Rp {{ number_format($insentif['total_insentif'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Kolom Potongan Insentif -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#B91C1C; background:#FEF2F2; border-color:#FECACA;">
                II. POTONGAN (PROPORSIONAL)
            </div>
            <div class="slip-items-body">
                @if ($insentif['potongan_pajak'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Pajak (PPh21)</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_pajak'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_koperasi'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Koperasi ({{ $rasio['persen_insentif'] }}%)</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_koperasi'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_kas'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Kas ({{ $rasio['persen_insentif'] }}%)</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_kas'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_darma_wanita'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Darma Wanita</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_darma_wanita'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_rekening_air_minum'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Rekening Air Minum</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_rekening_air_minum'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_bank_bjb'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BJB</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_bank_bjb'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_bank_bjbs'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BJBS</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_bank_bjbs'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_bank_btn'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BTN</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_bank_btn'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_bank_bpr'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BPR</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_bank_bpr'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_asuransi'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Asuransi</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_asuransi'], 0, ',', '.') }}</span></div>
                @endif
                @if ($insentif['potongan_zakat_profesi'] > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Zakat</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($insentif['potongan_zakat_profesi'], 0, ',', '.') }}</span></div>
                @endif
            </div>
            <div class="slip-col-total" style="background:#FEF2F2; border-color:#FECACA;">
                <span>JUMLAH POTONGAN (B)</span>
                <span class="tot-val" style="color:#DC2626;">Rp {{ number_format($insentif['total_potongan'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Box Take Home Pay (Insentif Bersih) -->
    <div class="slip-thp-wrapper">
        <div>
            <div class="slip-thp-title">JUMLAH INSENTIF DITERIMA (BERSIH = A - B)</div>
            <div class="slip-thp-terbilang">Terbilang: # {{ terbilang($insentif['diterima']) }} Rupiah #</div>
        </div>
        <div class="slip-thp-nominal" style="color:#7C3AED;">
            Rp {{ number_format($insentif['diterima'], 0, ',', '.') }}
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan Resmi -->
    <div class="slip-signatures-grid">
        <div class="slip-sig-box">
            <div class="slip-sig-role">Penerima / Pegawai,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">{{ strtoupper($gaji13['nama']) }}</div>
            <div class="slip-sig-nip">NIK. {{ $gaji13['nik'] }}</div>
        </div>
        <div class="slip-sig-box">
            <div class="slip-sig-role">Indramayu, Juli {{ $gaji13['tahun'] }}<br>Bagian Keuangan & Penggajian,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-sig-nip">Kasubag / Staf Keuangan</div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 3. FORMAT 3: SLIP GAJI 13 UTUH / AWAL (GAMBAR 3)                           --}}
{{-- ========================================================================= --}}
@else
<div class="slip-doc-container">
    <!-- Header KOP Resmi -->
    <div class="slip-header-kop">
        <div class="slip-kop-brand">
            <div class="slip-kop-company">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-kop-sub">KABUPATEN INDRAMAYU &middot; JAWA BARAT</div>
            <div class="slip-kop-address">Jl. Letjen Suprapto No25/E, Indramayu 45214 Telp (0234) 271322</div>
        </div>
        <div class="slip-kop-title-box">
            <div class="slip-title-text">SLIP GAJI TIGA BELAS</div>
            <div class="slip-badge-periode">TAHUN AJARAN: {{ $tahunAjaran }}</div>
        </div>
    </div>

    <!-- Info Pegawai -->
    <div class="slip-emp-box">
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">NIK</span><span class="sep">:</span><span class="v">{{ $gaji13['nik'] }}</span></div>
            <div class="slip-emp-item"><span class="k">NAMA</span><span class="sep">:</span><span class="v" style="font-size:13.5px; color:#0F2A3D; font-weight:700;">{{ strtoupper($gaji13['nama']) }}</span></div>
            <div class="slip-emp-item"><span class="k">GOLONGAN</span><span class="sep">:</span><span class="v">{{ $gaji13['golongan'] ?: '-' }} (PTKP: {{ $gaji13['kode_ptkp'] }})</span></div>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">UNIT KERJA</span><span class="sep">:</span><span class="v">{{ strtoupper($gaji13['unit_kerja'] ?? '-') }}</span></div>
            <div class="slip-emp-item"><span class="k">JABATAN</span><span class="sep">:</span><span class="v">{{ strtoupper($gaji13['jabatan'] ?? 'Pegawai') }}</span></div>
            <div class="slip-emp-item"><span class="k">STATUS SLIP</span><span class="sep">:</span><span class="v" style="color:{{ $gaji13['status'] === 'terbit' ? '#16A34A' : '#D97706' }}; font-weight:700;">{{ strtoupper($gaji13['status'] === 'terbit' ? 'Terbit & Final' : $gaji13['status']) }}</span></div>
        </div>
    </div>

    <!-- Rincian 2 Kolom Vertikal (Pendapatan & Potongan) -->
    <div class="slip-columns-wrap">
        <!-- Kolom Penerimaan Lengkap -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#0369A1; background:#F0F9FF; border-color:#BAE6FD;">
                I. PENERIMAAN
            </div>
            <div class="slip-items-body">
                @foreach ($komponenPendapatan as $key => $label)
                    @if (($gaji13[$key] ?? 0) > 0)
                        <div class="slip-row-item">
                            <span class="item-label">{{ $label }}</span>
                            <span class="item-val">Rp {{ number_format($gaji13[$key] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="slip-col-total" style="background:#F0F9FF; border-color:#BAE6FD;">
                <span>TOTAL PENDAPATAN (A)</span>
                <span class="tot-val" style="color:#0369A1;">Rp {{ number_format($utuh['total_pendapatan'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Kolom Potongan Lengkap -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#B91C1C; background:#FEF2F2; border-color:#FECACA;">
                II. POTONGAN
            </div>
            <div class="slip-items-body">
                @foreach ($potonganPendapatan as $key => $label)
                    @if (($gaji13[$key] ?? 0) > 0)
                        <div class="slip-row-item">
                            <span class="item-label">{{ $label }}</span>
                            <span class="item-val" style="color:#DC2626;">Rp {{ number_format($gaji13[$key] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
                @foreach ($potonganNonPendapatan as $key => $label)
                    @if (($gaji13[$key] ?? 0) > 0)
                        <div class="slip-row-item">
                            <span class="item-label">{{ $label }}</span>
                            <span class="item-val" style="color:#DC2626;">Rp {{ number_format($gaji13[$key] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="slip-col-total" style="background:#FEF2F2; border-color:#FECACA;">
                <span>TOTAL POTONGAN (B)</span>
                <span class="tot-val" style="color:#DC2626;">Rp {{ number_format($utuh['total_potongan'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Box Take Home Pay (Gaji 13 Bersih) -->
    <div class="slip-thp-wrapper">
        <div>
            <div class="slip-thp-title">TOTAL DITERIMA (BERSIH = A - B)</div>
            <div class="slip-thp-terbilang">Terbilang: # {{ terbilang($utuh['diterima']) }} Rupiah #</div>
        </div>
        <div class="slip-thp-nominal">
            Rp {{ number_format($utuh['diterima'], 0, ',', '.') }}
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan Resmi -->
    <div class="slip-signatures-grid">
        <div class="slip-sig-box">
            <div class="slip-sig-role">Penerima / Pegawai,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">{{ strtoupper($gaji13['nama']) }}</div>
            <div class="slip-sig-nip">NIK. {{ $gaji13['nik'] }}</div>
        </div>
        <div class="slip-sig-box">
            <div class="slip-sig-role">Indramayu, Juli {{ $gaji13['tahun'] }}<br>Bagian Keuangan & Penggajian,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-sig-nip">Kasubag / Staf Keuangan</div>
        </div>
    </div>
</div>
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
