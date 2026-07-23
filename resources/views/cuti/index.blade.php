@extends('layouts.app')

@section('title', 'Laporan Cuti Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Kepegawaian / Lap. Cuti Pegawai</div>
    <h1>Laporan Cuti Pegawai</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('cuti.index') }}" style="display:flex; gap:10px;">
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
    Laporan ini menampilkan data cuti yang tercatat lewat modul Prestasi -
    Cuti tidak punya data input sendiri, murni laporan saring dari sana.
</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Unit Kerja</th>
                <th>Tanggal Prestasi</th>
                <th>Jumlah Cuti</th>
                <th>Keterangan Cuti</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cuti as $c)
                <tr>
                    <td class="cell-nik">{{ $c['nik'] }}</td>
                    <td class="cell-name">{{ $c['nama'] }}</td>
                    <td>{{ $c['unit_kerja'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($c['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td>{{ $c['cuti'] }} hari</td>
                    <td>{{ $c['alasan_cuti'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">Belum ada data cuti untuk tahun {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
