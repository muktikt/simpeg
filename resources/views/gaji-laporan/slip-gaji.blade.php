@extends('layouts.app')

@section('title', 'Slip Gaji (Payroll)')

@section('content')
<div class="page-head">
    @if(session('simpeg_user.userlevel') === '5' || request('my'))
        <div class="breadcrumb">Home / Pendapatan Saya / Slip Gaji</div>
        <h1>Slip Gaji (Payroll)</h1>
    @else
        <div class="breadcrumb">Home / Laporan Penggajian / Lap. Slip Gaji</div>
        <h1>Laporan Slip Gaji</h1>
    @endif
</div>

<!-- Toolbar Filter Bulan & Tahun (Tetap Ada untuk Lihat Periode Sebelum/Sesudahnya) -->
@include('gaji-laporan.partials.filter-toolbar')

@if (session('simpeg_user.userlevel') === '5' || request('my'))
    @php
        $item = $data->first();
        $bulanNama = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Bulan Ini';
    @endphp

    @if ($item)
        @php
            $potonganWajib = ($item['potongan_bpjskes'] ?? 0) + ($item['potongan_bpjstk'] ?? 0) + ($item['potongan_dapenma'] ?? 0);
            $jumlahPotongan = $item['total_potongan'] ?? 0;
            $gajiBersih = $item['gaji_bersih'] ?? 0;
        @endphp

        @include('partials.official-slip', [
            'judul' => 'DAFTAR GAJI BULAN : ' . strtoupper($bulanNama) . ' ' . $tahun,
            'data' => $item,
        ])

        @if (!empty($riwayatGaji) && count($riwayatGaji) > 0)
            <div class="panel" style="max-width:840px; margin:0 auto 32px;">
                <h3 style="margin-bottom:14px; font-size:15px;">Riwayat Gaji Bulan Lainnya</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px;">
                    @foreach ($riwayatGaji as $rw)
                        @php
                            $rwBulan = \App\Http\Controllers\AbsensiController::BULAN[$rw['bulan']] ?? $rw['bulan'];
                        @endphp
                        <a href="{{ route('gaji-proses.show', $rw['id']) }}" style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; text-decoration:none;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='#F8FAFC'">
                            <span style="font-weight:600; color:#334155; font-size:13px;">{{ $rwBulan }} {{ $rw['tahun'] }}</span>
                            <span style="font-weight:700; color:#059669; font-size:13.5px;">Rp {{ number_format($rw['gaji_bersih'], 0, ',', '.') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada data slip gaji yang terbit untuk Anda pada {{ $bulanNama }} {{ $tahun }}. Silakan pilih bulan/tahun lain di atas.</div>
        </div>
    @endif

@else
    <!-- Tampilan Admin / Keuangan -->
    <p class="report-note">Menampilkan daftar slip gaji pegawai yang sudah terbit. Klik "Cetak Slip Resmi" pada pegawai yang ingin dicetak.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th>Kategori Pegawai</th>
                    <th style="text-align:right;">Gaji Bersih</th>
                    <th style="width:1%; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>{{ \App\Http\Controllers\GajiProsesController::KATEGORI[$d['kategori'] ?? 'pegawai'] ?? ($d['kategori'] ?? 'Pegawai Tetap') }}</td>
                        <td style="text-align:right; font-weight:700; color:#0F172A;">Rp {{ number_format($d['gaji_bersih'], 0, ',', '.') }}</td>
                        <td style="white-space:nowrap; text-align:center;">
                            <a href="{{ route('gaji-proses.show', $d['id']) }}" class="btn btn-outline btn-sm" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="14" height="14"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                                Cetak Slip Resmi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
