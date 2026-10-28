@extends('layouts.app')

@section('title', 'Cetak Slip Insentif')

@section('content')
<div class="page-head no-print">
    @if($isPegawai)
        <div class="breadcrumb">Home / Pendapatan Saya / Insentif</div>
        <h1>Insentif Pegawai</h1>
    @else
        <div class="breadcrumb">Home / Laporan Insentif / Cetak Slip Insentif</div>
        <h1>Cetak Slip Insentif</h1>
    @endif
</div>

<div class="toolbar no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('insentif.laporan-slip') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
        
        <select name="sumber" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:500;">
            <option value="gaji_bulanan" @selected($sumber === 'gaji_bulanan')>Dari Gaji Bulanan (Permen)</option>
            <option value="insentif_pendidikan" @selected($sumber === 'insentif_pendidikan')>Insentif Pendidikan (Regulasi 2026)</option>
            <option value="gaji13" @selected($sumber === 'gaji13')>Dari Gaji 13 (Histori)</option>
        </select>

        @if ($sumber === 'gaji_bulanan')
            <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:500;">
                @foreach ($bulanList as $val => $label)
                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:500;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <div style="display:flex; gap:10px; align-items:center;">
        @if(!$isPegawai && $selectedSlip)
            <a href="{{ route('insentif.laporan-slip', ['sumber' => $sumber, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-outline" style="font-weight:600;">
                &larr; Kembali ke Daftar Pegawai
            </a>
        @endif

        <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
            Cetak
        </button>
    </div>
</div>

@if ($isPegawai)
    {{-- TAMPILAN UNTUK PEGAWAI --}}
    @if ($selectedSlip)
        @php
            $item = $selectedSlip;
            $bulanNama = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? ('Bulan ' . $bulan);
        @endphp

        <!-- Banner Ringkasan Insentif (Navy - Sky Blue) -->
        <div class="no-print" style="background: linear-gradient(135deg, #0F2A3D 0%, #1A4968 50%, #0284C7 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(15, 42, 61, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 13.5px; font-weight: 500; opacity: 0.85; margin-bottom: 4px;">Insentif &middot; {{ $bulanNama }} {{ $tahun }}</div>
                <div style="font-size: 32px; font-weight: 700; font-family: 'Space Grotesk', sans-serif;">
                    Rp {{ number_format($item['insentif_diterima'], 0, ',', '.') }}
                </div>
                <div style="font-size: 13px; opacity: 0.9; margin-top: 6px;">
                    Total Insentif Bruto: <b>Rp {{ number_format($item['total_insentif'], 0, ',', '.') }}</b> &middot; 
                    Total Potongan: <b style="color:#FECACA;">Rp {{ number_format($item['total_potongan'], 0, ',', '.') }}</b>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-outline" onclick="window.print()" style="background: rgba(255,255,255,0.15); color:white; border-color: rgba(255,255,255,0.3); font-weight:600;">
                    Unduh / Cetak Slip
                </button>
            </div>
        </div>

                @php
            $insPendapatan = [
                ['label' => 'TUNJANGAN JABATAN', 'value' => $item['insentif_jabatan'] ?? 0],
                ['label' => 'TUNJANGAN PRESTASI', 'value' => $item['insentif_prestasi'] ?? 0],
                ['label' => 'TUNJANGAN TRANSPORTASI', 'value' => $item['insentif_transportasi'] ?? 0],
                ['label' => 'TUNJANGAN PANGAN', 'value' => $item['insentif_pangan'] ?? 0],
                ['label' => 'TUNJANGAN BPJS KESEHATAN', 'value' => $item['insentif_bpjs_kesehatan'] ?? 0],
                ['label' => 'TUNJANGAN PERUMAHAN', 'value' => $item['insentif_perumahan'] ?? 0],
                ['label' => 'TUNJANGAN BPJS TENAGA KERJA', 'value' => $item['insentif_bpjs_tenaga_kerja'] ?? 0],
                ['label' => 'TUNJANGAN PERUSAHAAN', 'value' => $item['insentif_perusahaan'] ?? 0],
                ['label' => 'LEMBUR', 'value' => $item['lembur'] ?? 0],
                ['label' => 'TUNJANGAN PAJAK', 'value' => $item['insentif_pajak'] ?? 0],
                ['label' => 'TUNJANGAN AIR MINUM', 'value' => $item['insentif_air_minum'] ?? 0],
                ['label' => 'TUNJANGAN KOMUNIKASI', 'value' => $item['insentif_komunikasi'] ?? 0],
            ];
            $insPotonganPend = [
                ['label' => 'POTONGAN SANKSI', 'value' => $item['potongan_sanksi_perusahaan'] ?? 0],
                ['label' => 'POTONGAN PMI / LAIN-LAIN', 'value' => $item['potongan_pmi_lain'] ?? 0],
                ['label' => 'POTONGAN DAPENMA', 'value' => $item['potongan_dapenma'] ?? 0],
                ['label' => 'POTONGAN BPJS TENAGA KERJA', 'value' => $item['potongan_bpjs_tenaga_kerja'] ?? 0],
                ['label' => 'POTONGAN PERUMAHAN', 'value' => $item['potongan_perumahan'] ?? 0],
                ['label' => 'POTONGAN TUNJANGAN PERUSAHAAN', 'value' => $item['potongan_insentif_perusahaan'] ?? 0],
                ['label' => 'POTONGAN KORPRI', 'value' => $item['potongan_korpri'] ?? 0],
                ['label' => 'POTONGAN PAJAK', 'value' => $item['potongan_pajak'] ?? 0],
                ['label' => 'POTONGAN BPJS KESEHATAN', 'value' => $item['potongan_bpjs_kesehatan'] ?? 0],
            ];
            $insPotonganNon = [
                ['label' => 'POTONGAN KOPERASI', 'value' => $item['potongan_koperasi'] ?? 0],
                ['label' => 'POTONGAN DARMA WANITA', 'value' => $item['potongan_darma_wanita'] ?? 0],
                ['label' => 'POTONGAN REKENING AIR MINUM', 'value' => $item['potongan_rekening_air_minum'] ?? 0],
                ['label' => 'POTONGAN KAS', 'value' => $item['potongan_kas'] ?? 0],
                ['label' => 'POTONGAN BANK BJB', 'value' => $item['potongan_bank_bjb'] ?? 0],
                ['label' => 'POTONGAN BANK BJBS', 'value' => $item['potongan_bank_bjbs'] ?? 0],
                ['label' => 'POTONGAN BANK BTN', 'value' => $item['potongan_bank_btn'] ?? 0],
                ['label' => 'POTONGAN BANK BPR', 'value' => $item['potongan_bank_bpr'] ?? 0],
                ['label' => 'POTONGAN ASURANSI/LAINNYA', 'value' => $item['potongan_asuransi'] ?? 0],
                ['label' => 'POTONGAN ZAKAT', 'value' => $item['potongan_zakat_profesi'] ?? 0],
            ];
        @endphp

        @include('partials.official-slip', [
            'judul' => 'DAFTAR INSENTIF & POTONGAN BULAN : ' . strtoupper($bulanNama) . ' ' . ($item['tahun'] ?? $tahun),
            'data' => $item,
            'pendapatanRows' => $insPendapatan,
            'potonganPendapatanRows' => $insPotonganPend,
            'potonganNonPendapatanRows' => $insPotonganNon,
            'totalPendapatan' => $item['total_insentif'] ?? null,
            'pendapatanDiterima' => $item['insentif_diterima'] ?? null,
        ])

        <!-- Card Riwayat Insentif -->
        @if (!empty($riwayatInsentif))
            <div class="no-print" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-top: 24px;">
                <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    RIWAYAT INSENTIF SEBELUMNYA
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                    @foreach ($riwayatInsentif as $rw)
                        <a href="{{ route('insentif.laporan-slip', ['my' => 1, 'sumber' => 'gaji_bulanan', 'bulan' => $rw['bulan'], 'tahun' => $rw['tahun']]) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="font-size: 18px; width: 36px; height: 36px; background: #e0f2fe; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    {!! $rw['icon'] !!}
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 14px;">{{ $rw['judul'] }}</div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $rw['periode'] }}</div>
                                </div>
                            </div>
                            <div style="font-weight: 700; color: #0284c7; font-size: 15px;">
                                Rp {{ number_format($rw['nominal'], 0, ',', '.') }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada slip insentif yang terbit untuk Anda pada periode ini. Silakan pilih bulan/tahun lain di atas.</div>
        </div>
    @endif

@else
    {{-- TAMPILAN UNTUK ADMIN / KEUANGAN / DIREKSI --}}
    @if ($selectedSlip)
        {{-- TAMPILKAN SLIP RESMI SATUAN YANG DIPILIH ADMIN --}}
        @php
            $item = $selectedSlip;
            $bulanNama = \App\Http\Controllers\AbsensiController::BULAN[$item['bulan']] ?? ('Bulan ' . $item['bulan']);
        @endphp

        <div class="no-print" style="margin-bottom: 16px; padding: 12px 18px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13.5px; color: #1E40AF;">
                Menampilkan Slip Resmi Pegawai: <b>{{ $item['nama'] }}</b> (NIK: {{ $item['nik'] }}) &middot; Periode: <b>{{ $item['periode'] }}</b>
            </div>
            <a href="{{ route('insentif.laporan-slip', ['sumber' => $sumber, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-outline btn-sm">
                Tutup & Kembali ke Daftar
            </a>
        </div>

                @php
            $insPendapatan = [
                ['label' => 'TUNJANGAN JABATAN', 'value' => $item['insentif_jabatan'] ?? 0],
                ['label' => 'TUNJANGAN PRESTASI', 'value' => $item['insentif_prestasi'] ?? 0],
                ['label' => 'TUNJANGAN TRANSPORTASI', 'value' => $item['insentif_transportasi'] ?? 0],
                ['label' => 'TUNJANGAN PANGAN', 'value' => $item['insentif_pangan'] ?? 0],
                ['label' => 'TUNJANGAN BPJS KESEHATAN', 'value' => $item['insentif_bpjs_kesehatan'] ?? 0],
                ['label' => 'TUNJANGAN PERUMAHAN', 'value' => $item['insentif_perumahan'] ?? 0],
                ['label' => 'TUNJANGAN BPJS TENAGA KERJA', 'value' => $item['insentif_bpjs_tenaga_kerja'] ?? 0],
                ['label' => 'TUNJANGAN PERUSAHAAN', 'value' => $item['insentif_perusahaan'] ?? 0],
                ['label' => 'LEMBUR', 'value' => $item['lembur'] ?? 0],
                ['label' => 'TUNJANGAN PAJAK', 'value' => $item['insentif_pajak'] ?? 0],
                ['label' => 'TUNJANGAN AIR MINUM', 'value' => $item['insentif_air_minum'] ?? 0],
                ['label' => 'TUNJANGAN KOMUNIKASI', 'value' => $item['insentif_komunikasi'] ?? 0],
            ];
            $insPotonganPend = [
                ['label' => 'POTONGAN SANKSI', 'value' => $item['potongan_sanksi_perusahaan'] ?? 0],
                ['label' => 'POTONGAN PMI / LAIN-LAIN', 'value' => $item['potongan_pmi_lain'] ?? 0],
                ['label' => 'POTONGAN DAPENMA', 'value' => $item['potongan_dapenma'] ?? 0],
                ['label' => 'POTONGAN BPJS TENAGA KERJA', 'value' => $item['potongan_bpjs_tenaga_kerja'] ?? 0],
                ['label' => 'POTONGAN PERUMAHAN', 'value' => $item['potongan_perumahan'] ?? 0],
                ['label' => 'POTONGAN TUNJANGAN PERUSAHAAN', 'value' => $item['potongan_insentif_perusahaan'] ?? 0],
                ['label' => 'POTONGAN KORPRI', 'value' => $item['potongan_korpri'] ?? 0],
                ['label' => 'POTONGAN PAJAK', 'value' => $item['potongan_pajak'] ?? 0],
                ['label' => 'POTONGAN BPJS KESEHATAN', 'value' => $item['potongan_bpjs_kesehatan'] ?? 0],
            ];
            $insPotonganNon = [
                ['label' => 'POTONGAN KOPERASI', 'value' => $item['potongan_koperasi'] ?? 0],
                ['label' => 'POTONGAN DARMA WANITA', 'value' => $item['potongan_darma_wanita'] ?? 0],
                ['label' => 'POTONGAN REKENING AIR MINUM', 'value' => $item['potongan_rekening_air_minum'] ?? 0],
                ['label' => 'POTONGAN KAS', 'value' => $item['potongan_kas'] ?? 0],
                ['label' => 'POTONGAN BANK BJB', 'value' => $item['potongan_bank_bjb'] ?? 0],
                ['label' => 'POTONGAN BANK BJBS', 'value' => $item['potongan_bank_bjbs'] ?? 0],
                ['label' => 'POTONGAN BANK BTN', 'value' => $item['potongan_bank_btn'] ?? 0],
                ['label' => 'POTONGAN BANK BPR', 'value' => $item['potongan_bank_bpr'] ?? 0],
                ['label' => 'POTONGAN ASURANSI/LAINNYA', 'value' => $item['potongan_asuransi'] ?? 0],
                ['label' => 'POTONGAN ZAKAT', 'value' => $item['potongan_zakat_profesi'] ?? 0],
            ];
        @endphp

        @include('partials.official-slip', [
            'judul' => 'DAFTAR INSENTIF & POTONGAN BULAN : ' . strtoupper($bulanNama) . ' ' . ($item['tahun'] ?? $tahun),
            'data' => $item,
            'pendapatanRows' => $insPendapatan,
            'potonganPendapatanRows' => $insPotonganPend,
            'potonganNonPendapatanRows' => $insPotonganNon,
            'totalPendapatan' => $item['total_insentif'] ?? null,
            'pendapatanDiterima' => $item['insentif_diterima'] ?? null,
        ])

    @else
        {{-- DAFTAR TABEL INSENTIF UNTUK SEMUA PEGAWAI --}}
        @php
            $totBruto = $data->sum('total_insentif');
            $totPot = $data->sum('total_potongan');
            $totBersih = $data->sum('insentif_diterima');
        @endphp

        <!-- Stat Cards -->
        <div class="stat-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:20px;">
            <div class="stat-card" style="background:white; padding:18px 20px; border-radius:14px; border:1px solid #E2E8F0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size:12px; color:#64748B; font-weight:600; text-transform:uppercase;">Total Pegawai Penerima</div>
                <div style="font-size:24px; font-weight:700; color:#0F2A3D; font-family:'Space Grotesk', sans-serif; margin-top:4px;">{{ count($data) }} Pegawai</div>
            </div>
            <div class="stat-card" style="background:white; padding:18px 20px; border-radius:14px; border:1px solid #E2E8F0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size:12px; color:#0369A1; font-weight:600; text-transform:uppercase;">Total Insentif Bruto</div>
                <div style="font-size:24px; font-weight:700; color:#0284C7; font-family:'Space Grotesk', sans-serif; margin-top:4px;">Rp {{ number_format($totBruto, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="background:white; padding:18px 20px; border-radius:14px; border:1px solid #E2E8F0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size:12px; color:#B91C1C; font-weight:600; text-transform:uppercase;">Total Potongan</div>
                <div style="font-size:24px; font-weight:700; color:#DC2626; font-family:'Space Grotesk', sans-serif; margin-top:4px;">Rp {{ number_format($totPot, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="background:white; padding:18px 20px; border-radius:14px; border:1px solid #E2E8F0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size:12px; color:#15803D; font-weight:600; text-transform:uppercase;">Total Insentif Bersih</div>
                <div style="font-size:24px; font-weight:700; color:#16A34A; font-family:'Space Grotesk', sans-serif; margin-top:4px;">Rp {{ number_format($totBersih, 0, ',', '.') }}</div>
            </div>
        </div>

        <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <p class="report-note" style="margin:0;">
                Menampilkan daftar slip insentif pegawai periode <b>{{ $sumber === 'gaji_bulanan' ? (\App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? $bulan) . ' ' . $tahun : 'Gaji 13 ' . $tahun }}</b>. Klik <b>"Cetak Slip Resmi"</b> untuk melihat dan mencetak slip pegawai.
            </p>
            <div class="search-box" style="width: 260px;">
                <input type="text" class="table-search-input" placeholder="Cari NIK, Nama, Unit..." style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
            </div>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:1%;">No</th>
                        <th>NIK</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan & Unit Kerja</th>
                        <th style="text-align:right;">Insentif Bruto</th>
                        <th style="text-align:right;">Potongan</th>
                        <th style="text-align:right;">Insentif Bersih</th>
                        <th style="width:1%; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $idx => $d)
                        <tr>
                            <td style="text-align:center;">{{ $idx + 1 }}</td>
                            <td class="cell-nik"><b>{{ $d['nik'] }}</b></td>
                            <td class="cell-name">
                                <b>{{ $d['nama'] }}</b>
                                <div style="font-size:11px; color:#64748B;">Gol. {{ $d['golongan'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $d['jabatan'] ?? '-' }}</div>
                                <div style="font-size:11.5px; color:#64748B;">{{ $d['unit_kerja'] ?? '-' }}</div>
                            </td>
                            <td style="text-align:right; color:#0284C7; font-weight:600;">Rp {{ number_format($d['total_insentif'], 0, ',', '.') }}</td>
                            <td style="text-align:right; color:#DC2626;">Rp {{ number_format($d['total_potongan'], 0, ',', '.') }}</td>
                            <td style="text-align:right; font-weight:700; color:#0F2A3D;">Rp {{ number_format($d['insentif_diterima'], 0, ',', '.') }}</td>
                            <td style="white-space:nowrap; text-align:center;">
                                <a href="{{ route('insentif.laporan-slip', ['sumber' => $sumber, 'bulan' => $bulan, 'tahun' => $tahun, 'nik' => $d['nik']]) }}" class="btn btn-outline btn-sm" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="14" height="14"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                                    Cetak Slip Resmi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="table-empty">Belum ada insentif yang terbit untuk periode ini.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endif
@endsection
