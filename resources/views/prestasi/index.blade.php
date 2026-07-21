@extends('layouts.app')

@section('title', 'Prestasi Pegawai')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Prestasi Pegawai</div>
    <h1>Prestasi Pegawai</h1>
</div>

<div class="toolbar">
    <div></div>
    @if ($bisaKelola)
        <a href="{{ route('prestasi.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Prestasi
        </a>
    @endif
</div>

<div class="table-card" style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl. Prestasi</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Karya</th>
                <th>Absensi</th>
                <th title="Alpha">A</th>
                <th title="Izin Keterangan">I</th>
                <th title="Izin Tanpa Keterangan">ItK</th>
                <th title="Sakit Keterangan">S</th>
                <th title="Sakit Tanpa Keterangan">StD</th>
                <th title="Dinas Luar">DL</th>
                <th>Cuti</th>
                <th>Keterangan Cuti</th>
                <th>Jam Lembur</th>
                <th>Total Uang Lembur</th>
                @if ($bisaKelola)
                    <th style="width:1%"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($prestasi as $p)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($p['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>{{ $p['karya'] }}</td>
                    <td>{{ $p['absensi'] }}</td>
                    <td>{{ $p['alpha'] }}</td>
                    <td>{{ $p['izin_ket'] }}</td>
                    <td>{{ $p['izin_non_ket'] }}</td>
                    <td>{{ $p['sakit_ket'] }}</td>
                    <td>{{ $p['sakit_non_ket'] }}</td>
                    <td>{{ $p['dinas_luar'] }}</td>
                    <td>{{ $p['cuti'] }}</td>
                    <td>{{ $p['alasan_cuti'] ?: '-' }}</td>
                    <td>{{ $p['jam_lembur'] }} jam</td>
                    <td>Rp {{ number_format($p['nominal_lembur'], 0, ',', '.') }}</td>
                    @if ($bisaKelola)
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('prestasi.edit', $p['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('prestasi.destroy', $p['id']) }}" method="POST" onsubmit="return confirm('Hapus data prestasi ini?');" style="margin:0;">
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
                    <td colspan="16">
                        <div class="table-empty">Belum ada data prestasi pegawai.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
