@extends('layouts.app')

@section('title', 'Pegawai Belum Ada Rekening BJB')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SET Rekening BJB / Belum Masuk</div>
    <h1>Daftar Pegawai Belum Ada Rekening BJB</h1>
</div>

<div class="toolbar">
    <a href="{{ route('rekening-bjb.index') }}" class="btn btn-outline">
        &larr; Kembali ke SET Rekening BJB
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Jabatan</th>
                <th>Unit Kerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pegawai as $p)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>{{ $p['jabatan'] }}</td>
                    <td>{{ $p['unit_kerja'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="table-empty">Semua pegawai sudah memiliki rekening BJB.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
