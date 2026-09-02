@extends('layouts.app')

@section('title', 'Detail THR')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

@php
    $totalPotongan = ($thr['total_potongan_pendapatan'] ?? 0) + ($thr['total_potongan_non_pendapatan'] ?? 0);
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Proses THR / {{ $thr['nama'] }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h1 style="margin:0;">Slip Tunjangan Hari Raya (THR) - {{ $thr['nama'] }}</h1>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip Resmi
            </button>
            <a href="{{ in_array(session('simpeg_user.userlevel'), ['1', '2', '7']) ? route('thr.index') : route('thr.laporan-slip') }}" class="btn btn-outline">Kembali</a>
        </div>
    </div>
</div>

<!-- OFFICIAL SLIP DOCUMENT CONTAINER (PRINT & SCREEN) -->
<div class="slip-doc-container">
    <!-- Header KOP Resmi -->
    <div class="slip-header-kop">
        <div class="slip-kop-brand">
            <div class="slip-kop-company">PERUMDAM TIRTA MUKTI</div>
            <div class="slip-kop-sub">KABUPATEN CIANJUR &middot; JAWA BARAT</div>
            <div class="slip-kop-address">Jl. Pangeran Hidayatullah No. 123 Cianjur | Telp. (0263) 261158</div>
        </div>
        <div class="slip-kop-title-box">
            <div class="slip-title-text">SLIP TUNJANGAN HARI RAYA (THR)</div>
            <div class="slip-badge-periode">TAHUN ANGGARAN: {{ $thr['tahun'] }}</div>
        </div>
    </div>

    <!-- Info Pegawai -->
    <div class="slip-emp-box">
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">NIK</span><span class="sep">:</span><span class="v">{{ $thr['nik'] }}</span></div>
            <div class="slip-emp-item"><span class="k">Nama Pegawai</span><span class="sep">:</span><span class="v" style="font-size:14px; color:#0F2A3D;">{{ $thr['nama'] }}</span></div>
            <div class="slip-emp-item"><span class="k">Jabatan</span><span class="sep">:</span><span class="v">{{ $thr['jabatan'] ?? 'Pegawai' }}</span></div>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div class="slip-emp-item"><span class="k">Unit Kerja</span><span class="sep">:</span><span class="v">{{ $thr['unit_kerja'] ?? '-' }}</span></div>
            <div class="slip-emp-item"><span class="k">Kategori / PTKP</span><span class="sep">:</span><span class="v">{{ \App\Http\Controllers\ThrController::KATEGORI[$thr['kategori']] ?? $thr['kategori'] }} (PTKP: {{ $thr['kode_ptkp'] }})</span></div>
            <div class="slip-emp-item"><span class="k">Status Slip</span><span class="sep">:</span><span class="v" style="color:{{ $thr['status'] === 'terbit' ? '#16A34A' : '#D97706' }};">{{ $thr['status'] === 'terbit' ? 'Terbit & Disetujui Final' : \App\Http\Controllers\ThrController::approvalStatusLabel($thr['status']) }}</span></div>
        </div>
    </div>

    <!-- Rincian 2 Kolom Vertikal (Pendapatan & Potongan) -->
    <div class="slip-columns-wrap">
        <!-- Kolom Penerimaan -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#0369A1; background:#F0F9FF; border-color:#BAE6FD;">
                I. PENERIMAAN THR
            </div>
            <div class="slip-items-body">
                @foreach ($komponenPendapatan as $key => $label)
                    @if (($thr[$key] ?? 0) > 0)
                        <div class="slip-row-item">
                            <span class="item-label">{{ $label }}</span>
                            <span class="item-val">Rp {{ number_format($thr[$key] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="slip-col-total" style="background:#F0F9FF; border-color:#BAE6FD;">
                <span>TOTAL PENDAPATAN (A)</span>
                <span class="tot-val" style="color:#0369A1;">Rp {{ number_format($thr['total_pendapatan'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Kolom Potongan -->
        <div class="slip-col-card">
            <div class="slip-col-head" style="color:#B91C1C; background:#FEF2F2; border-color:#FECACA;">
                II. POTONGAN THR
            </div>
            <div class="slip-items-body">
                @foreach ($potonganPendapatan as $key => $label)
                    @if (($thr[$key] ?? 0) > 0)
                        <div class="slip-row-item">
                            <span class="item-label">{{ $label }}</span>
                            <span class="item-val" style="color:#DC2626;">Rp {{ number_format($thr[$key] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
                @foreach ($potonganNonPendapatan as $key => $label)
                    @if (($thr[$key] ?? 0) > 0)
                        <div class="slip-row-item">
                            <span class="item-label">{{ $label }}</span>
                            <span class="item-val" style="color:#DC2626;">Rp {{ number_format($thr[$key] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="slip-col-total" style="background:#FEF2F2; border-color:#FECACA;">
                <span>TOTAL POTONGAN (B)</span>
                <span class="tot-val" style="color:#DC2626;">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Box Take Home Pay (THR Bersih) -->
    <div class="slip-thp-wrapper">
        <div>
            <div class="slip-thp-title">TOTAL THR DITERIMA (BERSIH = A - B)</div>
            <div class="slip-thp-terbilang">Terbilang: # {{ terbilang($thr['thr_diterima']) }} Rupiah #</div>
        </div>
        <div class="slip-thp-nominal">
            Rp {{ number_format($thr['thr_diterima'], 0, ',', '.') }}
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan Resmi -->
    <div class="slip-signatures-grid">
        <div class="slip-sig-box">
            <div class="slip-sig-role">Penerima / Pegawai,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">{{ $thr['nama'] }}</div>
            <div class="slip-sig-nip">NIK. {{ $thr['nik'] }}</div>
        </div>
        <div class="slip-sig-box">
            <div class="slip-sig-role">Cianjur, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Bagian Keuangan & Penggajian,</div>
            <div class="slip-sig-spacer"></div>
            <div class="slip-sig-name">PERUMDAM TIRTA MUKTI</div>
            <div class="slip-sig-nip">Kasubag / Staf Keuangan</div>
        </div>
    </div>
</div>

@if ($thr['status'] !== 'terbit' && ($thr['bisa_approve'] ?? false))
    <div class="form-actions" style="max-width:840px; margin:0 auto; padding:16px; background:#fff; border-radius:12px; border:1px solid #E2E8F0; display:flex; justify-content:flex-end;">
        <form action="{{ route('thr.terbitkan', $thr['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui THR ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'warning', 'Ya, Setujui');">
            @csrf
            <button type="submit" class="btn btn-primary">Setujui ke Tahap Berikutnya</button>
        </form>
    </div>
@endif
@endsection
