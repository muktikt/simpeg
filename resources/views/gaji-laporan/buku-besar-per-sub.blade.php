@extends('layouts.app')

@section('title', 'Laporan Buku Besar Per Sub')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Penggajian / Lap. Buku Besar Per Sub</div>
    <h1>Laporan Buku Besar Per Sub (Unit Kerja)</h1>
</div>

@include('gaji-laporan.partials.filter-toolbar')

@forelse ($data as $unitKerja => $group)
    <div class="panel" style="margin-bottom:16px;">
        <h3>{{ $unitKerja }} <span style="font-weight:400; color:var(--text-muted);">- Rp {{ number_format($group['total'], 0, ',', '.') }}</span></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Gaji Bersih</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>Rp {{ number_format($d['gaji_bersih'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="table-card"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></div>
@endforelse
@endsection
