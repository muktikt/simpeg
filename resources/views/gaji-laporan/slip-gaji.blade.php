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

        <!-- Banner Gaji Bersih (Matching Theme SIMPEG: Navy & Sky Blue) -->
        <div style="background: linear-gradient(135deg, var(--navy) 0%, var(--teal-dark) 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 14px; font-weight: 500; opacity: 0.85; margin-bottom: 4px;">Gaji Bersih (Take Home Pay) &middot; {{ $bulanNama }} {{ $tahun }}</div>
                <div style="font-size: 32px; font-weight: 700; font-family: 'Space Grotesk', sans-serif;">
                    Rp {{ number_format($gajiBersih, 0, ',', '.') }}
                </div>
            </div>
            <a href="{{ route('gaji-proses.show', $item['id']) }}" class="btn" style="background: var(--surface); color: var(--teal-dark); font-weight: 700; padding: 12px 20px; border-radius: 10px; text-decoration: none; box-shadow: var(--shadow-sm);">
                📄 Cek Detail (Slip Resmi PERUMDAM)
            </a>
        </div>

        <!-- Grid Responsif Layout Desktop & Tablet -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 24px;">
            
            <!-- Card 1: Rincian Gaji & Potongan -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    RINCIAN PENDAPATAN & POTONGAN
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Gaji pokok</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['gapok'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Tunjangan jabatan</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['tunjangan_jabatan'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Tunjangan transport</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['tunjangan_transport'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Lembur</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['lembur'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Potongan pajak (PPh21)</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['potongan_pajak'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Potongan wajib</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($potonganWajib, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Potongan kas</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['potongan_kas'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Potongan Korpri</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['potongan_korpri'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Potongan BPJS</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['potongan_bpjskes'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Potongan perumahan</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['potongan_perumahan'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px dashed #e2e8f0;">
                        <span style="font-weight: 600; color: #475569;">Jumlah Potongan</span>
                        <span style="font-weight: 700; color: #dc2626;">Rp {{ number_format($jumlahPotongan, 0, ',', '.') }}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--teal-soft); border-radius: 10px; margin-top: 4px;">
                        <span style="font-weight: 700; color: var(--teal-dark);">Total Pendapatan Diterima</span>
                        <span style="font-weight: 700; color: var(--teal); font-size: 16px;">Rp {{ number_format($gajiBersih, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Riwayat Per Bulan -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    RIWAYAT PER BULAN
                </div>

                @if (!empty($riwayatGaji) && count($riwayatGaji) > 0)
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($riwayatGaji as $rw)
                            @php
                                $rwBulan = \App\Http\Controllers\AbsensiController::BULAN[$rw['bulan']] ?? $rw['bulan'];
                            @endphp
                            <a href="{{ route('gaji-proses.show', $rw['id']) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                                <span style="font-weight: 600; color: #334155; font-size: 14px;">{{ $rwBulan }} {{ $rw['tahun'] }}</span>
                                <span style="font-weight: 700; color: #059669; font-size: 14px;">Rp {{ number_format($rw['gaji_bersih'], 0, ',', '.') }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="table-empty">Belum ada riwayat gaji terbit sebelumnya.</div>
                @endif
            </div>

        </div>

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada data slip gaji yang terbit untuk Anda pada {{ $bulanNama }} {{ $tahun }}. Silakan pilih bulan/tahun lain di atas.</div>
        </div>
    @endif

@else
    <!-- Tampilan Admin / Keuangan -->
    <p style="font-size:12px; color:var(--text-muted); margin:-8px 0 16px;">Menampilkan gaji yang sudah terbit. Klik "Lihat Slip" untuk detail lengkap per pegawai.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Gaji Bersih</th>
                    <th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>{{ \App\Http\Controllers\GajiProsesController::KATEGORI[$d['kategori']] ?? $d['kategori'] }}</td>
                        <td>Rp {{ number_format($d['gaji_bersih'], 0, ',', '.') }}</td>
                        <td><a href="{{ route('gaji-proses.show', $d['id']) }}" class="btn btn-outline btn-sm">Lihat Slip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="table-empty">Belum ada gaji yang terbit untuk periode ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
