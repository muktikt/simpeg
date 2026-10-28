@extends('layouts.app')

@section('title', 'Cetak Slip Gaji 13 & Tunjangan Pendidikan')

@section('content')
@php
    $isPegawai = (session('simpeg_user.userlevel') === '5') || request('my');
@endphp

<div class="page-head no-print">
    @if($isPegawai)
        <div class="breadcrumb">Home / Pendapatan Saya / Tunjangan Pendidikan</div>
        <h1>Tunjangan Pendidikan & Insentif 13</h1>
    @else
        <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Cetak Slip</div>
        <h1>Cetak Slip Tunjangan Pendidikan & Gaji 13</h1>
    @endif
</div>

<div class="toolbar no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-slip') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
        @if(request('format'))<input type="hidden" name="format" value="{{ request('format') }}">@endif
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:500;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>Tahun {{ $y }}</option>
            @endfor
        </select>
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

@if (!empty($tpData))
    <div class="no-print" style="margin-bottom:20px; background:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="color:#15803D;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div>
                <div style="font-weight:700; color:#15803D; font-size:14px;">Slip Tunjangan Pendidikan Resmi (Regulasi 2026 - Format Gambar 1) Tersedia</div>
                <div style="font-size:12.5px; color:#166534;">Periode: <strong>{{ $tpData->bulan_label }}</strong> &middot; Diterima: <strong>Rp {{ number_format(($tpData->gapok + $tpData->tunjangan_istri + $tpData->tunjangan_anak) - ($tpData->potongan_koperasi + $tpData->potongan_kas), 0, ',', '.') }}</strong></div>
            </div>
        </div>
        <a href="{{ route('tunjangan-pendidikan.show', $tpData->id) }}" class="btn btn-primary btn-sm" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
            Lihat & Cetak Slip Resmi
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>
@endif

@if ($isPegawai)
    {{-- ========================================================= --}}
    {{-- TAMPILAN UNTUK PEGAWAI (LEVEL 5 / PRIBADI)                --}}
    {{-- ========================================================= --}}
    @php
        $item = $data->first();
    @endphp

    @if ($item)
        @php
            $pemecahan = $item['pemecahan'];
            $tpendidikan = $pemecahan['tunjangan_pendidikan'];
            $insentif = $pemecahan['insentif'];
            $utuh = $pemecahan['gaji_13_utuh'];
            $rasio = $pemecahan['rasio'];
            $tahunAjaran = ($tahun - 1) . '/' . $tahun;
        @endphp

        <!-- FORMAT SWITCHER TABS (NO PRINT) -->
        <div class="no-print" style="margin-bottom:20px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap; background:#F1F5F9; padding:6px; border-radius:12px; border:1px solid #E2E8F0; width:fit-content;">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'tpendidikan']) }}" 
                   class="btn btn-sm {{ $format === 'tpendidikan' ? 'btn-primary' : 'btn-outline' }}" 
                   style="border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; {{ $format !== 'tpendidikan' ? 'background:transparent; color:#475569;' : '' }}">
                    Format 1: Tunjangan Pendidikan
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'insentif']) }}" 
                   class="btn btn-sm {{ $format === 'insentif' ? 'btn-primary' : 'btn-outline' }}" 
                   style="border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; {{ $format !== 'insentif' ? 'background:transparent; color:#475569;' : '' }}">
                    Format 2: Insentif Pendidikan
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'utuh']) }}" 
                   class="btn btn-sm {{ $format === 'utuh' ? 'btn-primary' : 'btn-outline' }}" 
                   style="border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; {{ $format !== 'utuh' ? 'background:transparent; color:#475569;' : '' }}">
                    Format 3: Gaji 13 Utuh
                </a>
            </div>

            <!-- REKAPITULASI DUA SLIP -->
            <div style="margin-top:14px; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:14px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">
                    <div>
                        <div style="font-size:11.5px; text-transform:uppercase; color:#64748B; font-weight:700;">Slip 1: Tunj. Pendidikan</div>
                        <div style="font-size:17px; font-weight:700; color:#0369A1; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tpendidikan['diterima'], 0, ',', '.') }}</div>
                    </div>
                    <div style="font-size:18px; color:#CBD5E1; font-weight:300;">+</div>
                    <div>
                        <div style="font-size:11.5px; text-transform:uppercase; color:#64748B; font-weight:700;">Slip 2: Insentif 13</div>
                        <div style="font-size:17px; font-weight:700; color:#7C3AED; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($insentif['diterima'], 0, ',', '.') }}</div>
                    </div>
                    <div style="font-size:18px; color:#CBD5E1; font-weight:300;">=</div>
                    <div>
                        <div style="font-size:11.5px; text-transform:uppercase; color:#64748B; font-weight:700;">Total Gaji 13 Bersih</div>
                        <div style="font-size:19px; font-weight:800; color:#0F2A3D; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($utuh['diterima'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div style="font-size:12px; color:#475569; background:#F8FAFC; border:1px solid #E2E8F0; padding:6px 12px; border-radius:8px;">
                    Rasio Potongan: <strong>{{ $rasio['persen_tpendidikan'] }}%</strong> (Tunj. Pend.) : <strong>{{ $rasio['persen_insentif'] }}%</strong> (Insentif)
                </div>
            </div>
        </div>

        {{-- FORMAT 1: TUNJANGAN PENDIDIKAN (PERSIS GAMBAR 1) --}}
        @if ($format === 'tpendidikan')
            @php
                $tpenPendapatan = [
                    ['label' => 'GAPOK', 'value' => $tpendidikan['gapok'] ?? 0],
                    ['label' => 'TUNJANGAN ISTRI', 'value' => $tpendidikan['tunjangan_istri'] ?? 0],
                    ['label' => 'TUNJANGAN ANAK', 'value' => $tpendidikan['tunjangan_anak'] ?? 0],
                ];
                $tpenPotonganNon = [
                    ['label' => 'POTONGAN KOPERASI', 'value' => $tpendidikan['potongan_koperasi'] ?? 0],
                    ['label' => 'POTONGAN KAS', 'value' => $tpendidikan['potongan_kas'] ?? 0],
                ];
            @endphp
            @include('partials.official-slip', [
                'judul' => 'DAFTAR TUNJANGAN PENDIDIKAN & POTONGAN BULAN : JULI ' . $tahun,
                'data' => $item,
                'pendapatanRows' => $tpenPendapatan,
                'potonganPendapatanRows' => [],
                'potonganNonPendapatanRows' => $tpenPotonganNon,
                'totalPendapatan' => $tpendidikan['total_pendapatan'],
                'totalPotonganPendapatan' => 0,
                'totalPotonganNonPendapatan' => $tpendidikan['total_potongan'],
                'labelSubtotalPendapatan' => 'JUMLAH PENDAPATAN',
                'labelPotonganNonPendapatan' => 'JUMLAH POTONGAN NON-INSENTIF',
                'pendapatanDiterima' => $tpendidikan['diterima'],
                'labelDiterima' => 'JUMLAH INSENTIF DITERIMA',
                'disclaimerHtml' => 'SLIP GAJI INI SUDAH DISETUJUI OLEH DIREKSI NAMUN SEGALA BENTUK PENYALAH GUNAAN SLIP GAJI BUKAN MENJADI TANGGUNG JAWAB PERUMDAM TIRTA DARMA AYU.',
            ])

        {{-- FORMAT 2: INSENTIF PENDIDIKAN (PERSIS GAMBAR 2) --}}
        @elseif ($format === 'insentif')
            @php
                $insPendapatan = [
                    ['label' => 'INSENTIF JABATAN', 'value' => $insentif['insentif_jabatan'] ?? 0],
                    ['label' => 'INSENTIF PRESTASI', 'value' => $insentif['insentif_prestasi'] ?? 0],
                    ['label' => 'INSENTIF TRANSPORTASI', 'value' => $insentif['insentif_transportasi'] ?? 0],
                    ['label' => 'INSENTIF PANGAN', 'value' => $insentif['insentif_pangan'] ?? 0],
                    ['label' => 'INSENTIF BPJS KESEHATAN', 'value' => $insentif['insentif_bpjs_kesehatan'] ?? 0],
                    ['label' => 'INSENTIF PERUMAHAN', 'value' => $insentif['insentif_perumahan'] ?? 0],
                    ['label' => 'INSENTIF BPJS TENAGA KERJA', 'value' => $insentif['insentif_bpjs_tenaga_kerja'] ?? 0],
                    ['label' => 'INSENTIF PERUSAHAAN', 'value' => $insentif['insentif_perusahaan'] ?? 0],
                    ['label' => 'LEMBUR', 'value' => $insentif['lembur'] ?? 0],
                    ['label' => 'INSENTIF PAJAK', 'value' => $insentif['insentif_pajak'] ?? 0],
                    ['label' => 'INSENTIF AIR MINUM', 'value' => $insentif['insentif_air_minum'] ?? 0],
                    ['label' => 'INSENTIF KOMUNIKASI', 'value' => $insentif['insentif_komunikasi'] ?? 0],
                ];
                $insPotonganInsentif = [
                    ['label' => 'POTONGAN SANKSI PERUSAHAAN', 'value' => $insentif['potongan_sanksi_perusahaan'] ?? 0],
                    ['label' => 'POTONGAN PMI / LAIN-LAIN', 'value' => $insentif['potongan_trandist_pmi_lain'] ?? 0],
                    ['label' => 'POTONGAN DAPENMA', 'value' => $insentif['potongan_dapenma'] ?? 0],
                    ['label' => 'POTONGAN BPJS TENAGA KERJA', 'value' => $insentif['potongan_bpjs_tenaga_kerja'] ?? 0],
                    ['label' => 'POTONGAN PERUMAHAN', 'value' => $insentif['potongan_perumahan'] ?? 0],
                    ['label' => 'POTONGAN INSENTIF PERUSAHAAN', 'value' => $insentif['potongan_tunjangan_perusahaan'] ?? 0],
                    ['label' => 'POTONGAN KORPRI', 'value' => $insentif['potongan_korpri'] ?? 0],
                    ['label' => 'POTONGAN PAJAK', 'value' => $insentif['potongan_pajak'] ?? 0],
                    ['label' => 'POTONGAN BPJS KESEHATAN', 'value' => $insentif['potongan_bpjs_kesehatan'] ?? 0],
                ];
                $insPotonganNon = [
                    ['label' => 'POTONGAN KOPERASI', 'value' => $insentif['potongan_koperasi'] ?? 0],
                    ['label' => 'POTONGAN DARMA WANITA', 'value' => $insentif['potongan_darma_wanita'] ?? 0],
                    ['label' => 'POTONGAN REKENING AIR MINUM', 'value' => $insentif['potongan_rekening_air_minum'] ?? 0],
                    ['label' => 'POTONGAN KAS', 'value' => $insentif['potongan_kas'] ?? 0],
                    ['label' => 'POTONGAN BANK BJB', 'value' => $insentif['potongan_bank_bjb'] ?? 0],
                    ['label' => 'POTONGAN BANK BJBS', 'value' => $insentif['potongan_bank_bjbs'] ?? 0],
                    ['label' => 'POTONGAN BANK BTN', 'value' => $insentif['potongan_bank_btn'] ?? 0],
                    ['label' => 'POTONGAN BANK BPR', 'value' => $insentif['potongan_bank_bpr'] ?? 0],
                    ['label' => 'POTONGAN ASURANSI', 'value' => $insentif['potongan_asuransi'] ?? 0],
                    ['label' => 'POTONGAN ZAKAT PROFESI', 'value' => $insentif['potongan_zakat_profesi'] ?? 0],
                ];
            @endphp
            @include('partials.official-slip', [
                'judul' => 'DAFTAR INSENTIF & POTONGAN BULAN : JULI ' . $tahun,
                'data' => $item,
                'pendapatanRows' => $insPendapatan,
                'potonganPendapatanRows' => $insPotonganInsentif,
                'potonganNonPendapatanRows' => $insPotonganNon,
                'totalPendapatan' => $insentif['total_insentif'],
                'totalPotonganPendapatan' => $insentif['total_potongan_insentif'] ?? array_sum(array_column($insPotonganInsentif, 'value')),
                'totalPotonganNonPendapatan' => $insentif['total_potongan_non_insentif'] ?? array_sum(array_column($insPotonganNon, 'value')),
                'labelSubtotalPendapatan' => 'JUMLAH INSENTIF',
                'labelPotonganPendapatan' => 'JUMLAH POTONGAN INSENTIF',
                'labelPotonganNonPendapatan' => 'JUMLAH POTONGAN NON-INSENTIF',
                'pendapatanDiterima' => $insentif['diterima'],
                'labelDiterima' => 'JUMLAH INSENTIF DITERIMA',
                'disclaimerHtml' => 'SLIP INSENTIF INI SUDAH DISETUJUI OLEH DIREKSI<br>NAMUN SEGALA BENTUK PENYALAH GUNAAN SLIP<br>BUKAN MENJADI TANGGUNG JAWAB KAMI.',
            ])

        {{-- FORMAT 3: GAJI 13 UTUH (GAMBAR 2 ASLI) --}}
        @else
            @include('partials.official-slip', [
                'judul' => 'DAFTAR GAJI TIGA BELAS BULAN : JULI ' . $tahun,
                'data' => $item,
                'labelDiterima' => 'JUMLAH PENDAPATAN DITERIMA',
            ])
        @endif

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada data Tunjangan Pendidikan / Gaji 13 yang terbit untuk Anda pada tahun {{ $tahun }}. Silakan pilih tahun lain di atas.</div>
        </div>
    @endif

@else
    {{-- ========================================================= --}}
    {{-- TAMPILAN ADMIN / KEUANGAN / DIREKSI                       --}}
    {{-- ========================================================= --}}
    <p class="report-note no-print">Menampilkan daftar slip Tunjangan Pendidikan & Gaji 13 yang sudah terbit. Setiap pegawai dapat dicetak dalam 3 format resmi: Tunjangan Pendidikan, Insentif, atau Gaji 13 Utuh.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th>Jabatan / Unit</th>
                    <th style="text-align:right;">Slip 1: Tunj. Pendidikan</th>
                    <th style="text-align:right;">Slip 2: Insentif 13</th>
                    <th style="text-align:right;">Total Gaji 13 (Utuh)</th>
                    <th style="width:1%; text-align:center;">Pilihan Cetak</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    @php
                        $p = $d['pemecahan'];
                    @endphp
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">
                            <strong>{{ $d['nama'] }}</strong>
                            <div style="font-size:11.5px; color:#64748B;">Gol: {{ $d['golongan'] ?: '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $d['jabatan'] ?? 'Pegawai' }}</div>
                            <div style="font-size:11.5px; color:#64748B;">{{ $d['unit_kerja'] ?? '-' }}</div>
                        </td>
                        <td style="text-align:right; font-weight:600; color:#0369A1; font-family:'IBM Plex Mono',monospace;">
                            Rp {{ number_format($p['tunjangan_pendidikan']['diterima'], 0, ',', '.') }}
                        </td>
                        <td style="text-align:right; font-weight:600; color:#7C3AED; font-family:'IBM Plex Mono',monospace;">
                            Rp {{ number_format($p['insentif']['diterima'], 0, ',', '.') }}
                        </td>
                        <td style="text-align:right; font-weight:700; color:#0F2A3D; font-family:'IBM Plex Mono',monospace;">
                            Rp {{ number_format($p['gaji_13_utuh']['diterima'], 0, ',', '.') }}
                        </td>
                        <td style="white-space:nowrap; text-align:center;">
                            <div style="display:inline-flex; gap:4px;">
                                <a href="{{ route('gaji-tigabelas.show', ['id' => $d['id'], 'format' => 'tpendidikan']) }}" 
                                   class="btn btn-outline btn-sm" 
                                   title="Cetak Format 1: Tunjangan Pendidikan"
                                   style="font-size:11.5px; padding:4px 8px; font-weight:600;">
                                    Tunj. Pend
                                </a>
                                <a href="{{ route('gaji-tigabelas.show', ['id' => $d['id'], 'format' => 'insentif']) }}" 
                                   class="btn btn-outline btn-sm" 
                                   title="Cetak Format 2: Insentif Pendidikan"
                                   style="font-size:11.5px; padding:4px 8px; font-weight:600; color:#7C3AED; border-color:#DDD6FE;">
                                    Insentif
                                </a>
                                <a href="{{ route('gaji-tigabelas.show', ['id' => $d['id'], 'format' => 'utuh']) }}" 
                                   class="btn btn-primary btn-sm" 
                                   title="Cetak Format 3: Gaji 13 Utuh"
                                   style="font-size:11.5px; padding:4px 8px; font-weight:600;">
                                    Utuh
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="table-empty">Belum ada Gaji 13 / Tunjangan Pendidikan yang terbit untuk tahun {{ $tahun }}.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
