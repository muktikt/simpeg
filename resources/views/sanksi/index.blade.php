@extends('layouts.app')

@section('title', 'Sanksi Pegawai')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Sanksi Pegawai</div>
    <h1>Sanksi Pegawai</h1>
</div>

<div class="toolbar">
    <div></div>
    @if ($bisaKelola)
        <a href="{{ route('sanksi.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Sanksi
        </a>
    @endif
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl. Sanksi</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Jenis Sanksi</th>
                <th>Keterangan</th>
                <th>Potongan (%)</th>
                @if ($bisaKelola)
                    <th style="width:1%"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($sanksi as $s)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($s['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td class="cell-nik">{{ $s['nik'] }}</td>
                    <td class="cell-name">{{ $s['nama'] }}</td>
                    <td>{{ $s['jenis_sanksi'] }}</td>
                    <td>{{ $s['keterangan'] ?: '-' }}</td>
                    <td>{{ $s['potongan_persen'] }}%</td>
                    @if ($bisaKelola)
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('sanksi.edit', $s['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('sanksi.destroy', $s['id']) }}" method="POST" onsubmit="return confirm('Hapus data sanksi ini?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="table-empty">Belum ada data sanksi pegawai.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
