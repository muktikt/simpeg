@extends('layouts.app')

@section('title', 'Tunjangan Pendidikan')

@section('content')
<div class="page-head">
    @if(session('simpeg_user.userlevel') === '5' || request('my'))
        <div class="breadcrumb">Home / Pendapatan Saya / Tunjangan Pendidikan</div>
        <h1>Tunjangan Pendidikan</h1>
    @else
        <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Cetak Slip Tunj. Pendidikan</div>
        <h1>Cetak Slip Tunjangan Pendidikan</h1>
    @endif
</div>

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-slip') }}" style="display:flex; gap:10px;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

@if (session('simpeg_user.userlevel') === '5' || request('my'))
    @php
        $item = $data->first();
        $totalNominal = !empty($rincianAnak) ? collect($rincianAnak)->sum('nominal') : ($item['gaji13_diterima'] ?? 0);
        $tahunAjaran = ($tahun - 1) . '/' . $tahun;
        $totalPotongan = ($item['total_potongan_pendapatan'] ?? 0) + ($item['total_potongan_non_pendapatan'] ?? 0);
    @endphp

    @if ($item)
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
                    <div class="slip-title-text">SLIP TUNJANGAN PENDIDIKAN (GAJI 13)</div>
                    <div class="slip-badge-periode">TAHUN AJARAN: {{ $tahunAjaran }}</div>
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
                    <div class="slip-emp-item"><span class="k">Kategori</span><span class="sep">:</span><span class="v">{{ \App\Http\Controllers\GajiTigabelasController::KATEGORI[$item['kategori'] ?? 'pegawai'] ?? ($item['kategori'] ?? 'Pegawai Tetap') }}</span></div>
                    <div class="slip-emp-item"><span class="k">Status Slip</span><span class="sep">:</span><span class="v" style="color:#16A34A;">Terbit & Final</span></div>
                </div>
            </div>

            <!-- Rincian 2 Kolom Vertikal (Pendapatan & Potongan) -->
            <div class="slip-columns-wrap">
                <!-- Kolom Penerimaan -->
                <div class="slip-col-card">
                    <div class="slip-col-head" style="color:#0369A1; background:#F0F9FF; border-color:#BAE6FD;">
                        I. PENERIMAAN TUNJ. PENDIDIKAN
                    </div>
                    <div class="slip-items-body">
                        @if(($item['gapok'] ?? 0) > 0)
                            <div class="slip-row-item"><span class="item-label">Gaji Pokok (Dasar)</span><span class="item-val">Rp {{ number_format($item['gapok'], 0, ',', '.') }}</span></div>
                        @endif
                        @if(($item['tunjangan_jabatan'] ?? 0) > 0)
                            <div class="slip-row-item"><span class="item-label">Tunjangan Jabatan</span><span class="item-val">Rp {{ number_format($item['tunjangan_jabatan'], 0, ',', '.') }}</span></div>
                        @endif
                        @if(($item['tunjangan_transport'] ?? 0) > 0)
                            <div class="slip-row-item"><span class="item-label">Tunjangan Transport</span><span class="item-val">Rp {{ number_format($item['tunjangan_transport'], 0, ',', '.') }}</span></div>
                        @endif
                    </div>
                    <div class="slip-col-total" style="background:#F0F9FF; border-color:#BAE6FD;">
                        <span>TOTAL PENDAPATAN (A)</span>
                        <span class="tot-val" style="color:#0369A1;">Rp {{ number_format($item['total_pendapatan'] ?? $item['gapok'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Kolom Potongan -->
                <div class="slip-col-card">
                    <div class="slip-col-head" style="color:#B91C1C; background:#FEF2F2; border-color:#FECACA;">
                        II. POTONGAN
                    </div>
                    <div class="slip-items-body">
                        @if(($item['potongan_pajak'] ?? 0) > 0)
                            <div class="slip-row-item"><span class="item-label">Potongan Pajak (PPh21)</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_pajak'], 0, ',', '.') }}</span></div>
                        @endif
                        @if(($item['potongan_kas'] ?? 0) > 0)
                            <div class="slip-row-item"><span class="item-label">Potongan Kas / Pinjaman</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_kas'], 0, ',', '.') }}</span></div>
                        @endif
                        @if(($item['potongan_keu_minus'] ?? 0) > 0)
                            <div class="slip-row-item"><span class="item-label">Potongan Keuangan</span><span class="item-val" style="color:#DC2626;">Rp {{ number_format($item['potongan_keu_minus'], 0, ',', '.') }}</span></div>
                        @endif
                    </div>
                    <div class="slip-col-total" style="background:#FEF2F2; border-color:#FECACA;">
                        <span>TOTAL POTONGAN (B)</span>
                        <span class="tot-val" style="color:#DC2626;">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Box Take Home Pay (Gaji 13 Bersih) -->
            <div class="slip-thp-wrapper">
                <div>
                    <div class="slip-thp-title">TOTAL DITERIMA (BERSIH = A - B)</div>
                    <div class="slip-thp-terbilang">Terbilang: # {{ terbilang($totalNominal) }} Rupiah #</div>
                </div>
                <div class="slip-thp-nominal">
                    Rp {{ number_format($totalNominal, 0, ',', '.') }}
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
                    <div class="slip-sig-role">Cianjur, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>Bagian Keuangan & Penggajian,</div>
                    <div class="slip-sig-spacer"></div>
                    <div class="slip-sig-name">PERUMDAM TIRTA MUKTI</div>
                    <div class="slip-sig-nip">Kasubag / Staf Keuangan</div>
                </div>
            </div>
        </div>

        <!-- Card Rincian Per Anak -->
        @if (!empty($rincianAnak) && count($rincianAnak) > 0)
            <div class="panel" style="max-width:840px; margin:0 auto 32px;">
                <h3 style="margin-bottom:16px; font-size:15px;">Rincian Tanggungan Anak Sekolah</h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    @foreach ($rincianAnak as $anak)
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 18px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; flex-wrap:wrap; gap:10px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:38px; height:38px; border-radius:50%; background:#2563EB; color:#fff; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center;">
                                    {{ $anak['inisial'] }}
                                </div>
                                <div>
                                    <div style="font-weight:700; color:#0F172A; font-size:14px;">{{ $anak['nama'] }} &middot; <span style="font-weight:500; color:#64748B;">{{ $anak['jenjang_detail'] }}</span></div>
                                    <div style="font-size:12px; color:#1D4ED8; font-weight:600; margin-top:2px;">Status: {{ $anak['status'] }}</div>
                                </div>
                            </div>
                            <div style="font-weight:700; color:#0F172A; font-size:15px; font-family:'IBM Plex Mono',monospace;">
                                Rp {{ number_format($anak['nominal'], 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada data Tunjangan Pendidikan yang terbit untuk Anda pada tahun {{ $tahun }}. Silakan pilih tahun lain di atas.</div>
        </div>
    @endif

@else
    <!-- Tampilan Admin / Keuangan -->
    <p class="report-note">Menampilkan daftar slip Tunjangan Pendidikan yang sudah terbit. Klik "Cetak Slip Resmi" pada pegawai yang ingin dicetak.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th style="text-align:right;">Total Gaji 13 Bersih</th>
                    <th style="width:1%; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td style="text-align:right; font-weight:700; color:#0F172A;">Rp {{ number_format($d['gaji13_diterima'], 0, ',', '.') }}</td>
                        <td style="white-space:nowrap; text-align:center;">
                            <a href="{{ route('gaji-tigabelas.show', $d['id']) }}" class="btn btn-outline btn-sm" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="14" height="14"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                                Cetak Slip Resmi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="table-empty">Belum ada Gaji 13 yang terbit untuk tahun ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
