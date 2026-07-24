@extends('layouts.app')

@section('title', 'SET PHDP DAPENMA')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Asuransi / SET PHDP DAPENMA</div>
    <h1>SET PHDP DAPENMA</h1>
</div>

<div class="toolbar">
    <div></div>
    <a href="{{ route('dapenma.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Tambah Peserta
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Nomor Peserta</th>
                <th>Nominal PHDP</th>
                <th>Jumlah Nominal Beban</th>
                <th>Tgl. Update</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dapenma as $d)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>{{ $d['nomor_peserta'] }}</td>
                    <td>Rp {{ number_format($d['nominal_phdp'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($d['nominal_beban'], 0, ',', '.') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($d['tgl_update'])->translatedFormat('d M Y') }}</td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('dapenma.edit', $d['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('dapenma.destroy', $d['id']) }}" method="POST" onsubmit="return confirm('Hapus data peserta Dapenma ini?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="table-empty">Belum ada data peserta Dapenma.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
