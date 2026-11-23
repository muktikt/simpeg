@extends('layouts.app')

@php
    $formatTitles = [
        'tpendidikan' => 'Daftar Buku Besar Tunjangan Pendidikan',
        'insentif' => 'Daftar Buku Besar Insentif Pendidikan',
        'utuh' => 'Rekapitulasi Buku Besar Gaji 13 Utuh',
    ];
    $currentTitle = $formatTitles[$format] ?? 'Laporan Buku Besar Gaji 13';
@endphp

@section('title', $currentTitle)

@section('content')
<div class="page-head no-print">
    <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / {{ $currentTitle }}</div>
    <h1>{{ $currentTitle }}</h1>
</div>

<div class="toolbar no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-buku-besar') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="format" value="{{ $format }}">
        
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:600;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>Tahun {{ $y }}</option>
            @endfor
        </select>

        <select name="kategori" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:500;">
            <option value="all" @selected(($kategori ?? 'all') === 'all')>Semua Pegawai & Direksi</option>
            <option value="pegawai" @selected(($kategori ?? '') === 'pegawai')>Khusus Pegawai (Non-Direksi)</option>
            <option value="direksi" @selected(($kategori ?? '') === 'direksi')>Khusus Direksi</option>
        </select>
    </form>

    <div style="display:flex; gap:8px; align-items:center;">
        <button type="button" class="btn btn-outline" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
            Cetak Ledger
        </button>
    </div>
</div>

<!-- FORMAT SWITCHER TABS (NO PRINT) -->
<div class="no-print" style="display:flex; gap:8px; margin-bottom:16px; background:#F8FAFC; padding:6px; border-radius:10px; border:1px solid var(--border); width:fit-content; flex-wrap:wrap;">
    <a href="{{ request()->fullUrlWithQuery(['format' => 'tpendidikan']) }}" 
       class="btn btn-sm {{ $format === 'tpendidikan' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $format !== 'tpendidikan' ? 'background:transparent; color:#475569;' : '' }}">
        Format 1: Tunjangan Pendidikan
    </a>
    <a href="{{ request()->fullUrlWithQuery(['format' => 'insentif']) }}" 
       class="btn btn-sm {{ $format === 'insentif' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $format !== 'insentif' ? 'background:transparent; color:#475569;' : '' }}">
        Format 2: Insentif Pendidikan
    </a>
    <a href="{{ request()->fullUrlWithQuery(['format' => 'utuh']) }}" 
       class="btn btn-sm {{ $format === 'utuh' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $format !== 'utuh' ? 'background:transparent; color:#475569;' : '' }}">
        Format 3: Gaji 13 Utuh
    </a>
</div>

<!-- STAT KPI CARDS -->
<div class="stat-grid no-print" style="grid-template-columns: repeat(3, 1fr); gap:14px; margin-bottom:20px;">
    <div class="stat-card" style="border-left:4px solid #0284C7;">
        <div class="label" style="font-size:12px; font-weight:600; color:#0369A1;">TOTAL TUNJANGAN PENDIDIKAN</div>
        <div class="value" style="font-size:20px; font-weight:700; color:#0F172A; font-family:'IBM Plex Mono',monospace;">
            Rp {{ number_format($totalTpendidikan, 0, ',', '.') }}
        </div>
        <div style="font-size:11.5px; color:#64748B; margin-top:4px;">Gapok + Tunjangan Keluarga</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #7C3AED;">
        <div class="label" style="font-size:12px; font-weight:600; color:#6D28D9;">TOTAL INSENTIF PENDIDIKAN</div>
        <div class="value" style="font-size:20px; font-weight:700; color:#0F172A; font-family:'IBM Plex Mono',monospace;">
            Rp {{ number_format($totalInsentif, 0, ',', '.') }}
        </div>
        <div style="font-size:11.5px; color:#64748B; margin-top:4px;">Tunj. Jabatan, Transport, Prestasi, dll</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #10B981;">
        <div class="label" style="font-size:12px; font-weight:600; color:#047857;">GRAND TOTAL GAJI 13 (UTUH)</div>
        <div class="value" style="font-size:20px; font-weight:700; color:#0F172A; font-family:'IBM Plex Mono',monospace;">
            Rp {{ number_format($totalUtuh, 0, ',', '.') }}
        </div>
        <div style="font-size:11.5px; color:#64748B; margin-top:4px;">Total Bersih Diterima Semua Pegawai</div>
    </div>
</div>

<!-- PRINT HEADER (HANYA MUNCUL DI PRINT) -->
<div class="print-only" style="display:none; text-align:center; margin-bottom:16px;">
    <div style="font-weight:700; font-size:15px; text-transform:uppercase;">PERUMDAM TIRTA DARMA AYU KAB. INDRAMAYU</div>
    <div style="font-weight:800; font-size:14px; text-transform:uppercase; margin-top:4px;">{{ $currentTitle }} - TAHUN {{ $tahun }}</div>
    <div style="font-size:12px; color:#475569; margin-top:2px;">Status: {{ ($kategori ?? 'all') === 'direksi' ? 'DIREKSI' : (($kategori ?? '') === 'pegawai' ? 'PEGAWAI TETAP & LAINNYA' : 'SEMUA PEGAWAI') }}</div>
</div>

<div class="table-card">
    @if ($format === 'tpendidikan')
        {{-- TABEL FORMAT 1: TUNJANGAN PENDIDIKAN --}}
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>NIK / Nama</th>
                    <th>Jabatan & Unit Kerja</th>
                    <th style="text-align:right;">Gaji Pokok</th>
                    <th style="text-align:right;">Tunj. Istri</th>
                    <th style="text-align:right;">Tunj. Anak</th>
                    <th style="text-align:right;">Total Pendapatan</th>
                    <th style="text-align:right;">Pot. Koperasi</th>
                    <th style="text-align:right;">Pot. Kas</th>
                    <th style="text-align:right;">Total Potongan</th>
                    <th style="text-align:right; font-weight:700;">Diterima</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumGapok = 0; $sumIstri = 0; $sumAnak = 0; $sumPend = 0;
                    $sumKop = 0; $sumKas = 0; $sumPot = 0; $sumNet = 0;
                @endphp
                @forelse ($data as $idx => $d)
                    @php
                        $tp = $d['pemecahan']['tunjangan_pendidikan'];
                        $sumGapok += (int) ($d['gapok'] ?? 0);
                        $sumIstri += (int) ($d['tunjangan_istri'] ?? 0);
                        $sumAnak += (int) ($d['tunjangan_anak'] ?? 0);
                        $sumPend += (int) ($tp['pendapatan'] ?? 0);
                        $sumKop += (int) ($tp['potongan_koperasi'] ?? 0);
                        $sumKas += (int) ($tp['potongan_kas'] ?? 0);
                        $sumPot += (int) ($tp['total_potongan'] ?? 0);
                        $sumNet += (int) ($tp['diterima'] ?? 0);
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600; color:#0F172A;">{{ $d['nama'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['nik'] }}</div>
                        </td>
                        <td>
                            <div>{{ $d['jabatan'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['unit_kerja'] }}</div>
                        </td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($d['gapok'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($d['tunjangan_istri'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($d['tunjangan_anak'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tp['pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tp['potongan_koperasi'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tp['potongan_kas'], 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tp['total_potongan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:#0369A1; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($tp['diterima'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="text-align:center; padding:30px;"><div class="table-empty">Belum ada data Gaji 13 yang terbit untuk tahun {{ $tahun }}.</div></td></tr>
                @endforelse
            </tbody>
            @if ($data->count() > 0)
                <tfoot style="background:#F1F5F9; font-weight:700; border-top:2px solid #CBD5E1;">
                    <tr>
                        <td colspan="3" style="text-align:center;">TOTAL</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumGapok, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumIstri, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumAnak, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumPend, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumKop, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumKas, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumPot, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#0369A1; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumNet, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

    @elseif ($format === 'insentif')
        {{-- TABEL FORMAT 2: INSENTIF PENDIDIKAN --}}
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>NIK / Nama</th>
                    <th>Jabatan & Unit Kerja</th>
                    <th style="text-align:right;">Pendapatan Insentif</th>
                    <th style="text-align:right;">Pot. Wajib/Pajak</th>
                    <th style="text-align:right;">Pot. Koperasi</th>
                    <th style="text-align:right;">Pot. Kas</th>
                    <th style="text-align:right;">Pot. Bank/Lain</th>
                    <th style="text-align:right;">Total Potongan</th>
                    <th style="text-align:right; font-weight:700;">Diterima</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumPendIns = 0; $sumWajib = 0; $sumKopIns = 0; $sumKasIns = 0;
                    $sumBank = 0; $sumPotIns = 0; $sumNetIns = 0;
                @endphp
                @forelse ($data as $idx => $d)
                    @php
                        $ins = $d['pemecahan']['insentif'];
                        $potBankLain = (int) ($ins['potongan_non_pendapatan'] ?? 0) - (int) ($ins['potongan_koperasi'] ?? 0) - (int) ($ins['potongan_kas'] ?? 0);
                        $sumPendIns += (int) ($ins['pendapatan'] ?? 0);
                        $sumWajib += (int) ($ins['potongan_pendapatan'] ?? 0);
                        $sumKopIns += (int) ($ins['potongan_koperasi'] ?? 0);
                        $sumKasIns += (int) ($ins['potongan_kas'] ?? 0);
                        $sumBank += $potBankLain;
                        $sumPotIns += (int) ($ins['total_potongan'] ?? 0);
                        $sumNetIns += (int) ($ins['diterima'] ?? 0);
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600; color:#0F172A;">{{ $d['nama'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['nik'] }}</div>
                        </td>
                        <td>
                            <div>{{ $d['jabatan'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['unit_kerja'] }}</div>
                        </td>
                        <td style="text-align:right; font-weight:600; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($ins['pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($ins['potongan_pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($ins['potongan_koperasi'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($ins['potongan_kas'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($potBankLain, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($ins['total_potongan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:#7C3AED; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($ins['diterima'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align:center; padding:30px;"><div class="table-empty">Belum ada data Gaji 13 yang terbit untuk tahun {{ $tahun }}.</div></td></tr>
                @endforelse
            </tbody>
            @if ($data->count() > 0)
                <tfoot style="background:#F1F5F9; font-weight:700; border-top:2px solid #CBD5E1;">
                    <tr>
                        <td colspan="3" style="text-align:center;">TOTAL</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumPendIns, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumWajib, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumKopIns, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumKasIns, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumBank, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumPotIns, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#7C3AED; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumNetIns, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

    @else
        {{-- TABEL FORMAT 3: GAJI 13 UTUH --}}
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>NIK / Nama</th>
                    <th>Jabatan & Unit Kerja</th>
                    <th style="text-align:right;">Total Pendapatan</th>
                    <th style="text-align:right;">Pot. Pendapatan</th>
                    <th style="text-align:right;">Pot. Non-Pendapatan</th>
                    <th style="text-align:right;">Total Potongan</th>
                    <th style="text-align:right; font-weight:700;">Gaji 13 Diterima</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumAllPend = 0; $sumAllPotPend = 0; $sumAllPotNon = 0; $sumAllPot = 0; $sumAllNet = 0;
                @endphp
                @forelse ($data as $idx => $d)
                    @php
                        $utuh = $d['pemecahan']['gaji_13_utuh'];
                        $sumAllPend += (int) ($d['total_pendapatan'] ?? 0);
                        $sumAllPotPend += (int) ($d['total_potongan_pendapatan'] ?? 0);
                        $sumAllPotNon += (int) ($d['total_potongan_non_pendapatan'] ?? 0);
                        $sumAllPot += (int) ($utuh['total_potongan'] ?? 0);
                        $sumAllNet += (int) ($utuh['diterima'] ?? 0);
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600; color:#0F172A;">{{ $d['nama'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['nik'] }}</div>
                        </td>
                        <td>
                            <div>{{ $d['jabatan'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['unit_kerja'] }}</div>
                        </td>
                        <td style="text-align:right; font-weight:600; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($d['total_pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($d['total_potongan_pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($d['total_potongan_non_pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($utuh['total_potongan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:#0F172A; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($utuh['diterima'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; padding:30px;"><div class="table-empty">Belum ada data Gaji 13 yang terbit untuk tahun {{ $tahun }}.</div></td></tr>
                @endforelse
            </tbody>
            @if ($data->count() > 0)
                <tfoot style="background:#F1F5F9; font-weight:700; border-top:2px solid #CBD5E1;">
                    <tr>
                        <td colspan="3" style="text-align:center;">TOTAL</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumAllPend, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumAllPotPend, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumAllPotNon, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumAllPot, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#0F172A; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($sumAllNet, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif
</div>

<style>
@media print {
    .no-print, .sidebar, .topbar { display: none !important; }
    .print-only { display: block !important; }
    .page-content { padding: 0 !important; margin: 0 !important; }
    .table-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
    table { width: 100% !important; border-collapse: collapse !important; font-size: 10px !important; }
    th, td { border: 1px solid #000 !important; padding: 4px 6px !important; }
}
</style>
@endsection
