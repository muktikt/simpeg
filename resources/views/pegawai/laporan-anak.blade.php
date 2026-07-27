@extends('layouts.app')

@section('title', 'Laporan Anak Diatas 21 Tahun')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Kepegawaian / Lap. Anak Diatas 21</div>
    <h1>Laporan Anak Diatas 21 Tahun</h1>
</div>

<p style="font-size:12.5px; color:var(--text-muted); margin:-8px 0 20px;">
    Menampilkan anak pegawai yang usianya sudah di atas 21 tahun dan berstatus "Tidak Kuliah" - biasanya jadi acuan HRD untuk meninjau ulang tunjangan keluarga yang bersangkutan.
</p>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK Pegawai</th>
                <th>Nama Pegawai</th>
                <th>Nama Anak</th>
                <th>Tanggal Lahir</th>
                <th>Usia</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik_pegawai'] }}</td>
                    <td class="cell-name">{{ $d['nama_pegawai'] }}</td>
                    <td>{{ $d['nama'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($d['tgl_lahir'])->translatedFormat('d M Y') }}</td>
                    <td>{{ $d['usia'] }} tahun</td>
                    <td>{{ $d['keterangan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">Tidak ada anak pegawai yang memenuhi kriteria (usia diatas 21 tahun & tidak kuliah).</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
