@extends('layouts.app')

@section('title', 'Laporan Buku Besar Tunj. Pendidikan Per Sub')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Lap. Buku Besar Per Sub</div>
    <h1>Laporan Buku Besar Per Sub (Unit Kerja)</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-buku-besar-per-sub') }}" style="display:flex; gap:10px;">
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
    <button type="button" class="btn btn-outline" onclick="window.print()">Cetak</button>
</div>

@forelse ($data as $unitKerja => $group)
    <div class="panel" style="margin-bottom:16px;">
        <h3>{{ $unitKerja }} <span style="font-weight:400; color:var(--text-muted);">- Rp {{ number_format($group['total'], 0, ',', '.') }}</span></h3>
        <table class="data-table">
            <thead><tr><th>NIK</th><th>Nama</th><th>Diterima</th></tr></thead>
            <tbody>
                @foreach ($group['rows'] as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>Rp {{ number_format($d['gaji13_diterima'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="table-card"><div class="table-empty">Belum ada Gaji 13 yang terbit untuk tahun ini.</div></div>
@endforelse
@endsection
