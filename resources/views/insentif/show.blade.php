@extends('layouts.app')

@section('title', 'Detail Slip Insentif')

@section('content')
@php 
    $myRole = session('simpeg_user.userlevel'); 
    $backRoute = ($myRole === '5') ? route('insentif.laporan-slip', ['my' => 1]) : route('insentif.laporan-slip', ['tahun' => $item['tahun'], 'bulan' => $item['bulan']]);
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Laporan Insentif / {{ $item['nama'] }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h1 style="margin:0;">Slip Insentif - {{ $item['nama'] }}</h1>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip Resmi
            </button>
            <a href="{{ $backRoute }}" class="btn btn-outline">Kembali</a>
        </div>
    </div>
</div>

<!-- OFFICIAL SLIP DOCUMENT CONTAINER (PRINT & SCREEN) -->
<div class="slip-doc-container">
    <!-- Header KOP Resmi -->
    <div class="slip-header-kop">
        <div class="slip-kop-brand">
            <div class="slip-kop-company">PERUMDAM Tirta Darma Ayu</div>
            <div class="slip-kop-sub">KABUPATEN INDRAMAYU &middot; JAWA BARAT</div>
            <div class="slip-kop-address">Jl. Letjen Suprapto No25/E, Indramayu 45214 Telp (0234) 271322</div>
        </div>
        <div class="slip-kop-title-box">
            <div class="slip-title-text">DAFTAR INSENTIF & POTONGAN</div>
            <div class="slip-badge-periode">PERIODE: {{ strtoupper($item['periode']) }}</div>
        </div>
    </div>

    <!-- Info Pegawai -->
    <div class="slip-emp-box">
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">NIK</span><span class="sep">:</span><span class="v">{{ $item['nik'] }}</span></div>
            <div class="slip-emp-item"><span class="k">Nama Pegawai</span><span class="sep">:</span><span class="v" style="font-size:14px; color:#0F2A3D;">{{ $item['nama'] }}</span></div>
            <div class="slip-emp-item"><span class="k">Jabatan</span><span class="sep">:</span><span class="v">{{ $item['jabatan'] ?? 'Pegawai' }}</span></div>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">Unit Kerja</span><span class="sep">:</span><span class="v">{{ $item['unit_kerja'] ?? '-' }}</span></div>
            <div class="slip-emp-item"><span class="k">Golongan</span><span class="sep">:</span><span class="v">{{ $item['golongan'] ?? '-' }}</span></div>
            <div class="slip-emp-item"><span class="k">Status Slip</span><span class="sep">:</span><span class="v" style="color:#16A34A; font-weight:700;">Terbit & Final</span></div>
        </div>
    </div>

    <!-- Rincian 2 Kolom Vertikal (Pendapatan & Potongan) -->
    <div class="slip-columns-wrap">
        <!-- Kolom Penerimaan Insentif -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#0369A1; background:#F0F9FF; border-color:#BAE6FD;">
                I. PENERIMAAN INSENTIF
            </div>
            <div class="slip-items-body">
                @if (($item['insentif_jabatan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Jabatan</span><span class="item-val">Rp {{ number_format($item['insentif_jabatan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_prestasi'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Prestasi</span><span class="item-val">Rp {{ number_format($item['insentif_prestasi'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_transportasi'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Transportasi</span><span class="item-val">Rp {{ number_format($item['insentif_transportasi'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_pangan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Pangan</span><span class="item-val">Rp {{ number_format($item['insentif_pangan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_bpjs_kesehatan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif BPJS Kesehatan</span><span class="item-val">Rp {{ number_format($item['insentif_bpjs_kesehatan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_perumahan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Perumahan</span><span class="item-val">Rp {{ number_format($item['insentif_perumahan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_bpjs_tenaga_kerja'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif BPJS Ketenagakerjaan</span><span class="item-val">Rp {{ number_format($item['insentif_bpjs_tenaga_kerja'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_perusahaan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Perusahaan</span><span class="item-val">Rp {{ number_format($item['insentif_perusahaan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['lembur'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Uang Lembur</span><span class="item-val">Rp {{ number_format($item['lembur'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_pajak'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Pajak (PPh21)</span><span class="item-val">Rp {{ number_format($item['insentif_pajak'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_air_minum'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Air Minum</span><span class="item-val">Rp {{ number_format($item['insentif_air_minum'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['insentif_komunikasi'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Insentif Komunikasi</span><span class="item-val">Rp {{ number_format($item['insentif_komunikasi'], 0, ',', '.') }}</span></div>
                @endif
            </div>
            <div class="slip-col-total" style="background:#F0F9FF; border-color:#BAE6FD;">
                <span>TOTAL INSENTIF (A)</span>
                <span class="tot-val" style="color:#0369A1;">Rp {{ number_format($item['total_insentif'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Kolom Potongan -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#B91C1C; background:#FEF2F2; border-color:#FECACA;">
                II. POTONGAN
            </div>
            <div class="slip-items-body">
                <!-- Potongan Bagian Insentif -->
                @if (($item['potongan_sanksi_perusahaan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Sanksi Perusahaan</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_sanksi_perusahaan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_pmi_lain'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan PMI / Lain-lain</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_pmi_lain'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_dapenma'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan DAPENMA</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_dapenma'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_bpjs_tenaga_kerja'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan BPJS Ketenagakerjaan</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_bpjs_tenaga_kerja'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_perumahan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Perumahan</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_perumahan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_insentif_perusahaan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Insentif Perusahaan</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_insentif_perusahaan'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_korpri'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Korpri</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_korpri'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_pajak'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Pajak (PPh21)</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_pajak'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_bpjs_kesehatan'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan BPJS Kesehatan</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_bpjs_kesehatan'], 0, ',', '.') }}</span></div>
                @endif

                <!-- Potongan Non-Insentif / Keuangan -->
                @if (($item['potongan_koperasi'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Koperasi</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_koperasi'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_darma_wanita'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Dharma Wanita</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_darma_wanita'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_rekening_air_minum'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Rekening Air Minum</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_rekening_air_minum'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_kas'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Kas / Pinjaman</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_kas'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_bank_bjb'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BJB</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_bank_bjb'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_bank_bjbs'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BJBS</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_bank_bjbs'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_bank_btn'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BTN</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_bank_btn'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_bank_bpr'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Bank BPR</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_bank_bpr'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_asuransi'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Asuransi</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_asuransi'], 0, ',', '.') }}</span></div>
                @endif
                @if (($item['potongan_zakat_profesi'] ?? 0) > 0)
                    <div class="slip-row-item"><span class="item-label">Potongan Zakat Profesi</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_zakat_profesi'], 0, ',', '.') }}</span></div>
                @endif
            </div>
            <div class="slip-col-total" style="background:#FEF2F2; border-color:#FECACA;">
                <span>TOTAL POTONGAN (B)</span>
                <span class="tot-val" style="color:#DC2626;">Rp {{ number_format($item['total_potongan'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Box Take Home Pay (Insentif Bersih) -->
    <div class="slip-thp-wrapper">
        <div>
            <div class="slip-thp-title">TOTAL INSENTIF DITERIMA (BERSIH = A - B)</div>
            <div class="slip-thp-terbilang">Terbilang: # {{ terbilang($item['insentif_diterima']) }} Rupiah #</div>
        </div>
        <div class="slip-thp-nominal">
            Rp {{ number_format($item['insentif_diterima'], 0, ',', '.') }}
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan Resmi -->
    <div class="slip-signatures-grid">
        <div class="slip-sig-box">
            <div class="slip-sig-role">Penerima / Pegawai,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">{{ $item['nama'] }}</div>
            <div class="slip-sig-nip">NIK. {{ $item['nik'] }}</div>
        </div>
        <div class="slip-sig-box">
            <div class="slip-sig-role">Indramayu, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Bagian Keuangan & Penggajian,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">{{ $item['disetujui_oleh'] ?? 'PERUMDAM Tirta Darma Ayu' }}</div>
            <div class="slip-sig-nip">Kasubag / Staf Keuangan</div>
        </div>
    </div>
</div>
@endsection
