@extends('layouts.app')

@section('title', 'Cetak Slip Tunj. Pendidikan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Cetak Slip</div>
    <h1>Cetak Slip Tunjangan Pendidikan (Gaji 13)</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-slip') }}" style="display:flex; gap:10px;">
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

<p class="report-note">Menampilkan Gaji 13 yang sudah terbit. Klik "Lihat Slip" untuk detail lengkap per pegawai.</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr><th>NIK</th><th>Nama</th><th>Gaji 13 Diterima</th><th style="width:1%"></th></tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d['gaji13_diterima'], 0, ',', '.') }}</td>
                    <td><a href="{{ route('gaji-tigabelas.show', $d['id']) }}" class="btn btn-outline btn-sm">Lihat Slip</a></td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="table-empty">Belum ada Gaji 13 yang terbit untuk tahun ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
