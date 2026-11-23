@extends('layouts.app')

@section('title', 'Laporan Deteksi Potongan & Gaji Minus')

@push('styles')
<style>
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 18px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.kpi-card.danger {
    border-left: 4px solid #ef4444;
    background: #fffdfd;
}
.kpi-card.warning {
    border-left: 4px solid #f59e0b;
}
.kpi-card.info {
    border-left: 4px solid #0284c7;
}
.kpi-title {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 6px;
}
.kpi-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.kpi-value.danger {
    color: #dc2626;
}
.kpi-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}
.alert-box-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    color: #92400e;
    font-size: 13.5px;
    line-height: 1.5;
}
.badge-minus {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan / Potongan Keuangan / Deteksi Gaji Minus</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px 0;">LAPORAN KHUSUS POTONGAN & GAJI MINUS</h1>
            <p style="margin:0; font-size:13.5px; color:#64748b;">
                Monitoring dan deteksi pegawai dengan total potongan keuangan melebihi pendapatan (kurang potong / defisit)
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('laporan-potongan.cetak-minus', ['bulan' => $bulan, 'tahun' => $tahun, 'unit_kerja' => $unitKerja]) }}" target="_blank" class="btn" style="background:#dc2626; color:#ffffff; font-weight:600; font-size:13px; padding:8px 16px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Laporan Potongan Kas Minus (A4)
            </a>
            <a href="{{ route('laporan-potongan.cetak-payroll-minus', ['bulan' => $bulan, 'tahun' => $tahun, 'unit_kerja' => $unitKerja]) }}" target="_blank" class="btn" style="background:#0284c7; color:#ffffff; font-weight:600; font-size:13px; padding:8px 16px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Cetak Format Bank BJB Minus
            </a>
        </div>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="kpi-grid">
    <div class="kpi-card danger">
        <div class="kpi-title">Pegawai Terdeteksi Gaji Minus</div>
        <div class="kpi-value danger">{{ number_format($totalPegawaiMinus, 0, ',', '.') }} <span style="font-size:15px; font-weight:500; color:#64748b;">Orang</span></div>
        <div class="kpi-sub">Total potongan &ge; pendapatan diterima</div>
    </div>

    <div class="kpi-card warning">
        <div class="kpi-title">Total Potongan Keuangan Pegawai Minus</div>
        <div class="kpi-value" style="color:#d97706;">Rp {{ number_format($totalPotonganMinus, 0, ',', '.') }}</div>
        <div class="kpi-sub">Akumulasi 11 komponen potongan kas</div>
    </div>

    <div class="kpi-card danger">
        <div class="kpi-title">Total Defisit Kas (Kurang Potong)</div>
        <div class="kpi-value danger">Rp {{ number_format($totalDefisit, 0, ',', '.') }}</div>
        <div class="kpi-sub">Kekurangan kas yang belum tertagih</div>
    </div>
</div>

@if ($totalPegawaiMinus > 0)
    <div class="alert-box-warning">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" style="flex-shrink:0; margin-top:2px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <strong>Perhatian Khusus Bagian Keuangan:</strong> Terdapat <strong>{{ $totalPegawaiMinus }} pegawai</strong> dengan total potongan yang melebihi pendapatan (take home pay negatif / gaji minus). 
            Bank BJB <em>tidak dapat</em> mentransfer nominal minus melalui rekening payroll. Segera koordinasikan dengan Koperasi Pegawai, KAS, atau lakukan penyesuaian nominal angsuran pinjaman sebelum daftar payroll ditransfer ke Bank.
        </div>
    </div>
@endif

{{-- Filter Card --}}
<div class="filter-card" style="background:#fff; padding:16px 20px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
        <div class="form-group" style="margin:0;">
            <label for="bulan" style="font-size:12.5px; font-weight:600; color:#475569; margin-bottom:6px; display:block;">Bulan Potongan</label>
            <select id="bulan" name="bulan" class="form-control" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:13.5px; min-width:140px;">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($bulan === $m)>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin:0;">
            <label for="tahun" style="font-size:12.5px; font-weight:600; color:#475569; margin-bottom:6px; display:block;">Tahun</label>
            <input type="number" id="tahun" name="tahun" value="{{ $tahun }}" min="2000" max="2100" class="form-control" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:13.5px; width:100px;">
        </div>

        <div class="form-group" style="margin:0;">
            <label for="unit_kerja" style="font-size:12.5px; font-weight:600; color:#475569; margin-bottom:6px; display:block;">Unit Kerja / Cabang</label>
            <select id="unit_kerja" name="unit_kerja" class="form-control" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:13.5px; min-width:220px;">
                <option value="all" @selected($unitKerja === 'all')>-- Semua Unit Kerja (Rekap Keseluruhan) --</option>
                @foreach ($unitKerjaList as $uk)
                    <option value="{{ $uk }}" @selected($unitKerja === $uk)>{{ $uk }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:9px 18px; border-radius:8px; font-size:13.5px; font-weight:600;">
            Terapkan Filter
        </button>

        <a href="{{ route('laporan-potongan.potongan-keu', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 16px; border-radius:8px; font-size:13px; text-decoration:none;">
            Lihat Semua Potongan
        </a>

        <a href="{{ route('laporan-potongan.potongan-keu-non-minus', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:8px 16px; border-radius:8px; font-size:13px; text-decoration:none;">
            Lihat Pegawai Non-Minus
        </a>
    </form>
</div>

{{-- Data Table Card --}}
<div class="table-card" style="background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12.5px;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569;">
                    <th style="padding:12px 10px; text-align:center; width:40px;">No</th>
                    <th style="padding:12px 10px;">NIK</th>
                    <th style="padding:12px 10px;">Nama Pegawai</th>
                    <th style="padding:12px 10px;">Unit Kerja</th>
                    <th style="padding:12px 10px; text-align:right;">Pend. Diterima</th>
                    @foreach ($kolom as $k)
                        <th style="padding:12px 8px; text-align:right; font-size:11.5px;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="padding:12px 10px; text-align:right; background:#fef2f2; color:#991b1b;">Total Potongan</th>
                    <th style="padding:12px 10px; text-align:right; background:#fee2e2; color:#b91c1c;">Gaji Diterima (Defisit)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr style="border-bottom:1px solid #f1f5f9; transition:background 0.15s;" onmouseover="this.style.background='#fff7f7'" onmouseout="this.style.background='transparent'">
                        <td style="padding:10px; text-align:center; color:#64748b;">{{ $loop->iteration }}</td>
                        <td style="padding:10px; font-family:'IBM Plex Mono',monospace; font-weight:600;">{{ $item['nik'] }}</td>
                        <td style="padding:10px; font-weight:600; color:#0f172a;">
                            {{ $item['nama'] }}
                            @if (!empty($item['no_rek']) && $item['no_rek'] !== '-')
                                <div style="font-size:11px; font-weight:400; color:#64748b;">BJB: {{ $item['no_rek'] }}</div>
                            @endif
                        </td>
                        <td style="padding:10px; color:#475569;">{{ $item['unit_kerja'] }}</td>
                        <td style="padding:10px; text-align:right; font-weight:600; color:#0f766e;">
                            Rp {{ number_format($item['pendapatan_diterima'] ?? 0, 0, ',', '.') }}
                        </td>
                        @foreach ($kolom as $k)
                            <td style="padding:10px 8px; text-align:right; color:#475569;">
                                {{ !empty($item[$k]) && $item[$k] > 0 ? number_format($item[$k], 0, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td style="padding:10px; text-align:right; font-weight:700; background:#fff5f5; color:#b91c1c;">
                            Rp {{ number_format($item['total_potongan_keu'], 0, ',', '.') }}
                        </td>
                        <td style="padding:10px; text-align:right; font-weight:800; background:#fee2e2; color:#dc2626; white-space:nowrap;">
                            - Rp {{ number_format($item['defisit'], 0, ',', '.') }}
                            <div style="font-size:10px; font-weight:700; color:#991b1b; text-transform:uppercase;">Kurang Potong</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 6 }}" style="padding:30px; text-align:center; color:#64748b;">
                            <div style="font-size:15px; font-weight:600; color:#0f766e; margin-bottom:4px;">
                                ✓ Tidak Ada Pegawai yang Mengalami Gaji Minus
                            </div>
                            <div style="font-size:13px;">
                                Seluruh pegawai pada periode {{ \Carbon\Carbon::create(null, $bulan)->translatedFormat('F') }} {{ $tahun }} memiliki sisa pendapatan yang mencukupi untuk seluruh potongan kas.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($items) > 0)
                <tfoot>
                    <tr style="background:#fef2f2; font-weight:700; border-top:2px solid #fecaca; color:#0f172a;">
                        <td colspan="4" style="padding:12px 10px; text-align:center;">TOTAL KESELURUHAN PEGAWAI MINUS</td>
                        <td style="padding:12px 10px; text-align:right; color:#0f766e;">
                            Rp {{ number_format($items->sum('pendapatan_diterima'), 0, ',', '.') }}
                        </td>
                        @foreach ($kolom as $k)
                            <td style="padding:12px 8px; text-align:right; font-size:11.5px;">
                                {{ number_format($items->sum($k), 0, ',', '.') }}
                            </td>
                        @endforeach
                        <td style="padding:12px 10px; text-align:right; color:#b91c1c; font-size:13px;">
                            Rp {{ number_format($totalPotonganMinus, 0, ',', '.') }}
                        </td>
                        <td style="padding:12px 10px; text-align:right; color:#dc2626; font-size:13px; font-weight:800;">
                            - Rp {{ number_format($totalDefisit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

@include('partials.signature-block', ['kodeTtd' => 'keuangan'])
@endsection

