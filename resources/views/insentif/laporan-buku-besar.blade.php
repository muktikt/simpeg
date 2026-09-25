@extends('layouts.app')

@section('title', 'Laporan Buku Besar Insentif')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Insentif / Lap. Buku Besar Insentif</div>
    <h1>Laporan Buku Besar Insentif</h1>
</div>

<div class="toolbar no-print">
    <form method="GET" action="{{ route('insentif.laporan-buku-besar') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <select name="sumber" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; background:var(--surface); color:var(--text);">
            <option value="gaji_bulanan" @selected($sumber === 'gaji_bulanan')>Dari Gaji Bulanan (Permen)</option>
            <option value="gaji13" @selected($sumber === 'gaji13')>Dari Gaji 13</option>
        </select>
        @if ($sumber === 'gaji_bulanan')
            <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; background:var(--surface); color:var(--text);">
                @foreach ($bulanList as $val => $label)
                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; background:var(--surface); color:var(--text);">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:5px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Cetak Buku Besar
    </button>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:16px;">
    <div class="stat-card">
        <div class="label">Jumlah Penerima</div>
        <div class="value">{{ $data->count() }} <span style="font-size:13px; font-weight:normal; color:var(--text-muted);">Pegawai</span></div>
    </div>
    <div class="stat-card">
        <div class="label">Total Insentif Bruto</div>
        <div class="value" style="color:var(--primary, #0284c7);">Rp {{ number_format($totalInsentifBruto ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Potongan</div>
        <div class="value" style="color:#ef4444;">Rp {{ number_format($totalPotongan ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Total Insentif Bersih</div>
        <div class="value" style="color:#10b981;">Rp {{ number_format($total, 0, ',', '.') }}</div>
    </div>
</div>

<div class="table-card">
    <div style="padding:14px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:600; font-size:14px;">Daftar Rekapitulasi Insentif ({{ $sumber === 'gaji_bulanan' ? ($bulanList[$bulan] ?? 'Bulan '.$bulan).' ' : '' }}{{ $tahun }})</span>
        <span class="badge badge-success">Terbit & Final</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:40px; text-align:center;">No</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Unit Kerja / Jabatan</th>
                <th style="text-align:right;">Insentif Bruto</th>
                <th style="text-align:right;">Potongan</th>
                <th style="text-align:right;">Insentif Bersih</th>
                <th class="no-print" style="text-align:center; width:90px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $idx => $d)
                <tr>
                    <td style="text-align:center; color:var(--text-muted);">{{ $idx + 1 }}</td>
                    <td class="cell-nik"><code>{{ $d['nik'] }}</code></td>
                    <td class="cell-name font-medium">{{ $d['nama'] }}</td>
                    <td style="font-size:12px; color:var(--text-muted);">
                        {{ $d['unit_kerja'] ?? '-' }}<br>
                        <small>{{ $d['jabatan'] ?? '-' }}</small>
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums;">
                        Rp {{ number_format($d['total_insentif'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; color:#ef4444;">
                        Rp {{ number_format($d['total_potongan'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#10b981;">
                        Rp {{ number_format($d[$nominalKey] ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="no-print" style="text-align:center;">
                        <a href="{{ route('insentif.show', $d['id']) }}" target="_blank" class="btn btn-outline" style="padding:4px 8px; font-size:11px;" title="Cetak Slip">
                            Slip
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="table-empty">Belum ada data insentif yang terbit untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
        @if ($data->isNotEmpty())
            <tfoot>
                <tr style="font-weight:700; background:rgba(0,0,0,0.03);">
                    <td colspan="4" style="text-align:center;">TOTAL</td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; color:var(--primary, #0284c7);">
                        Rp {{ number_format($totalInsentifBruto ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; color:#ef4444;">
                        Rp {{ number_format($totalPotongan ?? 0, 0, ',', '.') }}
                    </td>
                    <td style="text-align:right; font-variant-numeric:tabular-nums; color:#10b981;">
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<style>
@media print {
    .no-print, .sidebar, .topbar, .breadcrumb, .toolbar { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .table-card { border: none !important; box-shadow: none !important; }
    .data-table { font-size: 11px !important; width: 100% !important; border-collapse: collapse !important; }
    .data-table th, .data-table td { border: 1px solid #ddd !important; padding: 4px 6px !important; }
}
</style>
@endsection
