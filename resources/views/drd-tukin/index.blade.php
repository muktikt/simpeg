@extends('layouts.app')

@section('title', 'SET DRD Tukin')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / SET DRD Tukin</div>
    <h1>SET DRD Tukin</h1>
</div>

<div class="toolbar">
    <div></div>
    <a href="{{ route('drd-tukin.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Tambah DRD Tukin
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl. DRD</th>
                <th>Nominal DRD</th>
                <th>Nominal Penerimaan</th>
                <th>Efisiensi (%)</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($drd as $d)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($d['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td>Rp {{ number_format($d['nominal_drd'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['nominal_penerimaan'], 0, ',', '.') }}</td>
                    <td>{{ $d['efisiensi_persen'] }}%</td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('drd-tukin.edit', $d['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('drd-tukin.destroy', $d['id']) }}" method="POST" onsubmit="return confirm('Hapus data DRD Tukin ini?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">Belum ada data DRD Tukin.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
