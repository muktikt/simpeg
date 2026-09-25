@extends('layouts.app')

@section('title', 'Cetak Slip Insentif')

@section('content')
<div class="page-head">
    @if($isPegawai)
        <div class="breadcrumb">Home / Pendapatan Saya / Insentif</div>
        <h1>Insentif Pegawai</h1>
    @else
        <div class="breadcrumb">Home / Laporan Insentif / Cetak Slip Insentif</div>
        <h1>Cetak Slip Insentif</h1>
    @endif
</div>

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('insentif.laporan-slip') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
        
        <select name="sumber" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:500;">
            <option value="gaji_bulanan" @selected($sumber === 'gaji_bulanan')>Dari Gaji Bulanan (Permen)</option>
            <option value="gaji13" @selected($sumber === 'gaji13')>Dari Gaji 13</option>
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
        <div style="background: linear-gradient(135deg, #0F2A3D 0%, #1A4968 50%, #0284C7 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(15, 42, 61, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
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

        <!-- OFFICIAL SLIP DOCUMENT CONTAINER -->
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
                    <div class="slip-badge-periode">BULAN : {{ strtoupper($bulanNama) }} {{ $tahun }}</div>
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

        <!-- Card Riwayat Insentif -->
        @if (!empty($riwayatInsentif))
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-top: 24px;">
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

        <div style="margin-bottom: 16px; padding: 12px 18px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13.5px; color: #1E40AF;">
                Menampilkan Slip Resmi Pegawai: <b>{{ $item['nama'] }}</b> (NIK: {{ $item['nik'] }}) &middot; Periode: <b>{{ $item['periode'] }}</b>
            </div>
            <a href="{{ route('insentif.laporan-slip', ['sumber' => $sumber, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-outline btn-sm">
                Tutup & Kembali ke Daftar
            </a>
        </div>

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
                    <div class="slip-badge-periode">BULAN : {{ strtoupper($bulanNama) }} {{ $item['tahun'] }}</div>
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
