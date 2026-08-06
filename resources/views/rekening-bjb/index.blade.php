@extends('layouts.app')

@section('title', 'SET Rekening BJB')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / SET Rekening BJB</div>
    <h1>SET REKENING BJB</h1>
</div>

@if ($belumMasuk > 0)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        Ada pegawai yang belum dibuat rekening BJB sebanyak <strong>{{ $belumMasuk }}</strong> Pegawai.
        <a href="{{ route('rekening-bjb.belum-masuk') }}" style="font-weight:600; text-decoration:underline;">Klik disini untuk melihat data.</a>
    </div>
@endif

<div class="toolbar">
    <a href="{{ route('rekening-bjb.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
        Tambah Rekening BJB
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>No. Rekening BJB</th>
                <th style="width:1%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-nik">{{ $item['nik'] }}</td>
                    <td class="cell-name">{{ $item['nama'] }}</td>
                    <td style="font-weight:600;">{{ $item['no_rek'] }}</td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('rekening-bjb.edit', $item['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('rekening-bjb.destroy', $item['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus data rekening ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="table-empty">Belum ada data rekening BJB.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
