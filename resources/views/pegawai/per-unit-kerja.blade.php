@extends('layouts.app')

@section('title', 'Data Per Unit Kerja')

@section('content')
@php
    $badgeLabel = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai / Data Per Unit Kerja</div>
    <h1>Data Pegawai Per Unit Kerja</h1>
</div>

@forelse ($data as $unitKerja => $pegawaiUnit)
    <div class="panel" style="margin-bottom:16px;">
        <h3>{{ $unitKerja }} <span style="font-weight:400; color:var(--text-muted);">({{ $pegawaiUnit->count() }} pegawai)</span></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pegawaiUnit->sortBy('nama') as $p)
                    <tr>
                        <td class="cell-nik">{{ $p['nik'] }}</td>
                        <td class="cell-name">{{ $p['nama'] }}</td>
                        <td>{{ $p['jabatan'] }}</td>
                        <td><span class="badge badge-{{ $p['status_peg'] }}">{{ $badgeLabel[$p['status_peg']] ?? $p['status_peg'] }}</span></td>
                        <td><a href="{{ route('pegawai.show', $p['id']) }}" class="btn btn-outline btn-sm">Lihat</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="table-card"><div class="table-empty">Belum ada data pegawai.</div></div>
@endforelse
@endsection
