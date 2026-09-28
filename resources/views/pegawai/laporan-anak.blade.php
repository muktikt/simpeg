@extends('layouts.app')

@section('title', 'Laporan Anak Diatas 21 Tahun')

@section('content')
<div class="page-head" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
    <div>
        <div class="breadcrumb">Home / Laporan Kepegawaian / Lap. Anak Diatas 21</div>
        <h1 style="margin:4px 0 0;">Laporan Anak Diatas 21 Tahun</h1>
    </div>
    <div style="display:flex; gap:8px;">
        <button type="button" class="btn btn-outline" onclick="window.print()" style="display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak Laporan
        </button>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:20px; font-size:13px; line-height:1.5; background:#EFF6FF; border:1px solid #BFDBFE; color:#1E40AF; border-radius:8px; padding:12px 16px;">
    <strong>Informasi Aturan Tunjangan Anak:</strong>
    <ul style="margin:4px 0 0 18px; padding:0;">
        <li>Batas usia tunjangan anak standar adalah <strong>21 tahun</strong>.</li>
        <li>Bagi anak yang berstatus <strong>"Kuliah"</strong>, tunjangan masih dapat berlanjut sampai usia <strong>25 tahun</strong>.</li>
        <li>Bagi anak yang berstatus <strong>"Tidak Kuliah"</strong> atau telah melewati usia <strong>25 tahun</strong>, hak tunjangan harus dihentikan dari perhitungan payroll.</li>
    </ul>
</div>

{{-- Filter Status Tabs --}}
<div style="display:flex; gap:8px; margin-bottom:16px;">
    <a href="{{ route('pegawai.laporan-anak', ['status' => 'semua']) }}"
       class="btn {{ ($statusFilter ?? 'semua') === 'semua' ? 'btn-primary' : 'btn-outline' }}"
       style="font-size:12.5px; padding:6px 14px;">
        Semua Anak &ge; 21 Tahun
    </a>
    <a href="{{ route('pegawai.laporan-anak', ['status' => 'kuliah']) }}"
       class="btn {{ ($statusFilter ?? '') === 'kuliah' ? 'btn-primary' : 'btn-outline' }}"
       style="font-size:12.5px; padding:6px 14px;">
        Status Kuliah
    </a>
    <a href="{{ route('pegawai.laporan-anak', ['status' => 'tidak-kuliah']) }}"
       class="btn {{ ($statusFilter ?? '') === 'tidak-kuliah' ? 'btn-primary' : 'btn-outline' }}"
       style="font-size:12.5px; padding:6px 14px;">
        Status Tidak Kuliah
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px; text-align:center;">No</th>
                <th>NIK Pegawai</th>
                <th>Nama Pegawai</th>
                <th>Nama Anak</th>
                <th>Tanggal Lahir</th>
                <th>Usia</th>
                <th>Status Kuliah</th>
                <th>Keterangan Hak Tunjangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $index => $d)
                @php
                    $isKuliah = str_contains(strtolower($d['keterangan'] ?? ''), 'kuliah') && !str_contains(strtolower($d['keterangan'] ?? ''), 'tidak');
                    $isDiatas25 = ($d['usia'] ?? 0) >= 25;
                @endphp
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td class="cell-nik"><strong>{{ $d['nik_pegawai'] }}</strong></td>
                    <td class="cell-name">{{ $d['nama_pegawai'] }}</td>
                    <td><strong>{{ $d['nama'] }}</strong></td>
                    <td>{{ formatTglIndo($d['tgl_lahir'] ?? null) }}</td>
                    <td><span style="font-weight:600;">{{ $d['usia'] }} tahun</span></td>
                    <td>
                        @if ($isKuliah)
                            <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11.5px; font-weight:700; background:#ECFDF5; color:#047857; border:1px solid #A7F3D0;">
                                Kuliah
                            </span>
                        @else
                            <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11.5px; font-weight:700; background:#FEF2F2; color:#B91C1C; border:1px solid #FECACA;">
                                Tidak Kuliah
                            </span>
                        @endif
                    </td>
                    <td>
                        @if ($isDiatas25)
                            <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11.5px; font-weight:700; background:#F1F5F9; color:#475569; border:1px solid #CBD5E1;">
                                Usia &ge; 25 Thn (Kadaluarsa)
                            </span>
                        @elseif ($isKuliah)
                            <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11.5px; font-weight:700; background:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE;">
                                Berhak Tunjangan s.d 25 Thn
                            </span>
                        @else
                            <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11.5px; font-weight:700; background:#FFFBEB; color:#B45309; border:1px solid #FDE68A;">
                                Tunjangan Harus Dihentikan
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="table-empty" style="padding:32px 16px; text-align:center; color:var(--text-muted);">
                            Tidak ada anak pegawai yang memenuhi kriteria usia di atas 21 tahun untuk filter yang dipilih.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
@media print {
    .sidebar, .topbar, .page-head button, .alert, .breadcrumb, a.btn {
        display: none !important;
    }
    body, .main-content {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .table-card {
        border: none !important;
        box-shadow: none !important;
    }
    .data-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .data-table th, .data-table td {
        border: 1px solid #333 !important;
        padding: 6px 8px !important;
        font-size: 11px !important;
    }
}
</style>
@endsection
