@extends('layouts.app')

@section('title', 'Laporan Insentif')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Insentif</div>
    <h1>Laporan Insentif</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('insentif.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
        <select name="sumber" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            <option value="gaji13" @selected($sumber === 'gaji13')>Dari Gaji 13</option>
            <option value="gaji_bulanan" @selected($sumber === 'gaji_bulanan')>Dari Gaji Bulanan (Permen)</option>
        </select>

        @if ($sumber === 'gaji_bulanan')
            <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                @foreach ($bulanList as $val => $label)
                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

<p style="font-size:12px; color:var(--text-muted); margin:-8px 0 16px;">
    Laporan ini menampilkan data yang sudah <strong>terbit</strong> dari
    {{ $sumber === 'gaji13' ? 'modul Gaji 13' : 'modul Proses Gaji Bulanan' }}
    - Insentif tidak punya data input sendiri, murni laporan gabungan.
</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Total Pendapatan</th>
                <th>Total Potongan</th>
                <th>{{ $sumber === 'gaji13' ? 'Gaji 13 Diterima' : 'Gaji Bersih' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>
                        @if ($sumber === 'gaji13')
                            {{ \App\Http\Controllers\GajiTigabelasController::KATEGORI[$d['kategori']] ?? $d['kategori'] }}
                        @else
                            {{ \App\Http\Controllers\GajiProsesController::KATEGORI[$d['kategori']] ?? $d['kategori'] }}
                        @endif
                    </td>
                    <td>Rp {{ number_format($d['total_pendapatan'], 0, ',', '.') }}</td>
                    <td>
                        @if ($sumber === 'gaji13')
                            Rp {{ number_format($d['total_potongan_pendapatan'] + $d['total_potongan_non_pendapatan'], 0, ',', '.') }}
                        @else
                            Rp {{ number_format($d['total_potongan'], 0, ',', '.') }}
                        @endif
                    </td>
                    <td><strong>Rp {{ number_format($sumber === 'gaji13' ? $d['gaji13_diterima'] : $d['gaji_bersih'], 0, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">Belum ada data yang terbit untuk periode ini.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
