@extends('layouts.app')

@section('title', 'SET ' . $tipeLabel)

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / SET {{ $tipeLabel }}</div>
    <h1>SET {{ strtoupper($tipeLabel) }}</h1>
</div>

@if ($belumMasuk > 0)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        Ada pegawai yang belum dibuat {{ strtolower($tipeLabel) }} sebanyak <strong>{{ $belumMasuk }}</strong> Pegawai.
        <a href="{{ route('potongan-keu.belum-masuk', $tipe) }}" style="font-weight:600; text-decoration:underline;">Klik disini untuk melihat data.</a>
    </div>
@endif

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <a href="{{ route('potongan-keu.create', $tipe) }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
        Tambah Potongan Pegawai
    </a>

    <a href="{{ route('potongan-keu.terbit', $tipe) }}" class="btn btn-outline" style="font-weight:600;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:4px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Lihat Halaman Penerbitan
    </a>
</div>

<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Entry</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="text-align:right;">Total</th>
                    <th>Status</th>
                    <th style="width:1%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ formatTglIndo($item['tgl_potongan'], 'd/m/Y') }}</td>
                        <td class="cell-nik">{{ $item['nik'] }}</td>
                        <td class="cell-name">{{ $item['nama'] }}</td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($item[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td>
                            @if ($item['status'] === 'Y')
                                <span class="badge badge-success">Disetujui</span>
                            @else
                                <span class="badge badge-warning">Menunggu</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('potongan-keu.edit', [$tipe, $item['id']]) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('potongan-keu.destroy', [$tipe, $item['id']]) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus potongan pegawai ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 7 }}">
                            <div class="table-empty">Belum ada data {{ strtolower($tipeLabel) }} bulan ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
