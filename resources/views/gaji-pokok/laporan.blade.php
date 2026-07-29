@extends('layouts.app')

@section('title', 'Laporan Gaji Pokok / Golongan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Gapok / Golongan</div>
    <h1>Laporan Gaji Pokok / Golongan</h1>
</div>

<div class="toolbar">
    <div></div>
    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Golongan</th>
                <th>Masa Kerja</th>
                <th>Nominal Gaji Pokok</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gapok as $g)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-name">{{ $g['golongan'] }}</td>
                    <td>{{ $g['masa_kerja'] }}</td>
                    <td>Rp {{ number_format($g['nominal'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="table-empty">Belum ada data gaji pokok.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
