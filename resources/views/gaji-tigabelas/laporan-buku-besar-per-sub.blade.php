@extends('layouts.app')

@php
    $formatTitles = [
        'tpendidikan' => 'Laporan Buku Besar Tunj. Pendidikan Per Unit Kerja',
        'insentif' => 'Laporan Buku Besar Insentif Pendidikan Per Unit Kerja',
        'utuh' => 'Laporan Buku Besar Gaji 13 Utuh Per Unit Kerja',
    ];
    $currentTitle = $formatTitles[$format] ?? 'Laporan Buku Besar Per Unit Kerja';
@endphp

@section('title', $currentTitle)

@section('content')
<div class="page-head no-print">
    <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Lap. Buku Besar Per Sub</div>
    <h1>{{ $currentTitle }}</h1>
</div>

<div class="toolbar no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-buku-besar-per-sub') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
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

    <button type="button" class="btn btn-outline" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak Ledger Per Sub
    </button>
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

@forelse ($data as $unitKerja => $group)
    @php
        $groupTotal = ($format === 'insentif') ? $group['total_insentif'] : (($format === 'utuh') ? $group['total_utuh'] : $group['total_tpendidikan']);
    @endphp
    <div class="panel" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">
            <h3 style="margin:0; font-size:15px; color:#0F172A;">{{ $unitKerja ?: 'Kantor Pusat' }}</h3>
            <span style="font-weight:700; color:#0284C7; font-family:'IBM Plex Mono',monospace;">Subtotal: Rp {{ number_format($groupTotal, 0, ',', '.') }}</span>
        </div>
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>NIK / Nama</th>
                    <th>Jabatan</th>
                    <th style="text-align:right;">Pendapatan</th>
                    <th style="text-align:right;">Potongan</th>
                    <th style="text-align:right; font-weight:700;">Diterima</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $idx => $d)
                    @php
                        $pm = $d['pemecahan'];
                        if ($format === 'insentif') {
                            $pend = $pm['insentif']['pendapatan'];
                            $pot = $pm['insentif']['total_potongan'];
                            $net = $pm['insentif']['diterima'];
                        } elseif ($format === 'utuh') {
                            $pend = $d['total_pendapatan'];
                            $pot = $pm['gaji_13_utuh']['total_potongan'];
                            $net = $pm['gaji_13_utuh']['diterima'];
                        } else {
                            $pend = $pm['tunjangan_pendidikan']['pendapatan'];
                            $pot = $pm['tunjangan_pendidikan']['total_potongan'];
                            $net = $pm['tunjangan_pendidikan']['diterima'];
                        }
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600; color:#0F172A;">{{ $d['nama'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $d['nik'] }}</div>
                        </td>
                        <td>{{ $d['jabatan'] }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($pend, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:#DC2626; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($pot, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:#0284C7; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($net, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="table-card"><div class="table-empty">Belum ada Gaji 13 yang terbit untuk tahun {{ $tahun }}.</div></div>
@endforelse

<style>
@media print {
    .no-print, .sidebar, .topbar { display: none !important; }
    .panel { page-break-inside: avoid; border: 1px solid #000 !important; margin-bottom: 20px !important; }
}
</style>
@endsection
