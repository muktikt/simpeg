@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
@php
    $myRole = session('simpeg_user.userlevel');
    $badgeLabel = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai</div>
    <h1>Data Pegawai</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('pegawai.index') }}" class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari NIK, nama, atau unit kerja...">
    </form>

    @if ($myRole === '1')
        <a href="{{ route('pegawai.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Pegawai
        </a>
    @endif
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Unit Kerja</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pegawai as $p)
                <tr>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>{{ $p['jabatan'] }}</td>
                    <td>{{ $p['unit_kerja'] }}</td>
                    <td><span class="badge badge-{{ $p['status_peg'] }}">{{ $badgeLabel[$p['status_peg']] ?? $p['status_peg'] }}</span></td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('pegawai.show', $p['id']) }}" class="btn btn-outline btn-sm">Lihat</a>
                            @if ($myRole === '1')
                                <a href="{{ route('pegawai.edit', $p['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">
                            @if ($keyword !== '')
                                Tidak ada pegawai yang cocok dengan pencarian "{{ $keyword }}".
                            @else
                                Belum ada data pegawai.
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
