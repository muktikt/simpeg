@extends('layouts.app')

@section('title', 'Laporan Buku Besar Tunj. Pendidikan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Lap. Buku Besar</div>
    <h1>Laporan Buku Besar Tunjangan Pendidikan</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-buku-besar') }}" style="display:flex; gap:10px;">
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
    <button type="button" class="btn btn-outline" onclick="window.print()">Cetak</button>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom:16px;">
    <div class="stat-card"><div class="label">Jumlah Pegawai</div><div class="value">{{ $data->count() }}</div></div>
    <div class="stat-card"><div class="label">Total Dibayarkan</div><div class="value">Rp {{ number_format($total, 0, ',', '.') }}</div></div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead><tr><th>NIK</th><th>Nama</th><th>Total Pendapatan</th><th>Total Potongan</th><th>Diterima</th></tr></thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d['total_pendapatan'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['total_potongan_pendapatan'] + $d['total_potongan_non_pendapatan'], 0, ',', '.') }}</td>
                    <td><strong>Rp {{ number_format($d['gaji13_diterima'], 0, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="table-empty">Belum ada Gaji 13 yang terbit untuk tahun ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
