@extends('layouts.app')

@section('title', 'Laporan Rekapitulasi Sanksi Disiplin Pegawai')

@push('styles')
<style>
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.kpi-card.danger  { border-left: 4px solid #dc2626; }
.kpi-card.warning { border-left: 4px solid #f59e0b; }
.kpi-card.info    { border-left: 4px solid #0284c7; }
.kpi-card.primary { border-left: 4px solid #4f46e5; }
.kpi-title {
    font-size: 11.5px;
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
.kpi-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 20px;
}
.filter-form {
    display: flex;
    gap: 14px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.filter-group label {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
}
.filter-group select,
.filter-group input {
    height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 12px;
    font-size: 13px;
    background: #fff;
    color: #0f172a;
}
.filter-group select:focus,
.filter-group input:focus {
    border-color: #0284c7;
    outline: none;
}
.btn-filter {
    height: 38px;
    background: #0284c7;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 0 18px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-filter:hover { background: #0369a1; }
.btn-action-cetak {
    background: #0f172a;
    color: #ffffff;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-action-cetak:hover { background: #1e293b; color: #fff; }
.btn-action-cetak-blue {
    background: #0369a1;
    color: #ffffff;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-action-cetak-blue:hover { background: #0284c7; color: #fff; }
.badge-tingkat {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}
.badge-tingkat-ringan { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-tingkat-sedang { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-tingkat-berat  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Kepegawaian / Lap. Sanksi Disiplin Pegawai</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px 0;">LAPORAN REKAPITULASI SANKSI DISIPLIN PEGAWAI</h1>
            <p style="margin:0; font-size:13.5px; color:#64748b;">
                Monitoring penegakan disiplin, SK sanksi kedinasan, dan potongan pendapatan perusahaan PERUMDAM Tirta Darma Ayu
            </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('sanksi.cetak-all', ['tahun' => $tahun, 'bulan' => $bulan, 'unit_kerja' => $unitKerja, 'nik' => $nik]) }}" target="_blank" class="btn-action-cetak">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Rekapitulasi Keseluruhan (A4)
            </a>
            @if ($nik !== 'all' && !empty($nik))
                <a href="{{ route('sanksi.cetak-pegawai', ['nik' => $nik, 'tahun' => $tahun]) }}" target="_blank" class="btn-action-cetak-blue">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Cetak Riwayat Pegawai Ini
                </a>
            @endif
        </div>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-title">Total Kasus Sanksi</div>
        <div class="kpi-value" style="color:#4f46e5;">{{ number_format($totalSanksi, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Kasus</span></div>
        <div class="kpi-sub">Periode Tahun {{ $tahun === 'all' || empty($tahun) ? 'Semua Tahun' : $tahun }}</div>
    </div>

    <div class="kpi-card info">
        <div class="kpi-title">Sanksi Ringan</div>
        <div class="kpi-value" style="color:#0284c7;">{{ number_format($totalRingan, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Pegawai</span></div>
        <div class="kpi-sub">Teguran Lisan / Peringatan I / Tertulis</div>
    </div>

    <div class="kpi-card warning">
        <div class="kpi-title">Sanksi Sedang</div>
        <div class="kpi-value" style="color:#d97706;">{{ number_format($totalSedang, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Pegawai</span></div>
        <div class="kpi-sub">Penurunan Jabatan, Tunjangan, atau Pangkat</div>
    </div>

    <div class="kpi-card danger">
        <div class="kpi-title">Sanksi Berat</div>
        <div class="kpi-value" style="color:#dc2626;">{{ number_format($totalBerat, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Pegawai</span></div>
        <div class="kpi-sub">Skorsing / PTDH / Dikeluarkan</div>
    </div>
</div>

{{-- Filter Card --}}
<div class="filter-card">
    <form method="GET" action="{{ route('sanksi.laporan') }}" class="filter-form">
        <div class="filter-group">
            <label for="tahun">Tahun</label>
            <select name="tahun" id="tahun" style="width:110px;">
                <option value="all" @selected($tahun === 'all')>Semua</option>
                @foreach (range(now()->year, now()->year - 5) as $y)
                    <option value="{{ $y }}" @selected((string)$tahun === (string)$y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="bulan">Bulan</label>
            <select name="bulan" id="bulan" style="width:140px;">
                <option value="all" @selected($bulan === 'all')>Semua Bulan</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected((string)$bulan === (string)$m)>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="unit_kerja">Unit Kerja / Cabang</label>
            <select name="unit_kerja" id="unit_kerja" style="min-width: 200px;">
                <option value="all" @selected($unitKerja === 'all' || empty($unitKerja))>-- SEMUA UNIT KERJA --</option>
                @foreach ($unitKerjaList as $u)
                    <option value="{{ $u }}" @selected($unitKerja === $u)>{{ $u }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="nik">Pegawai Tertentu</label>
            <select name="nik" id="nik" style="min-width: 220px;">
                <option value="all" @selected($nik === 'all' || empty($nik))>-- SEMUA PEGAWAI --</option>
                @foreach ($pegawaiList as $p)
                    <option value="{{ $p['nik'] }}" @selected($nik === $p['nik'])>
                        {{ $p['nik'] }} - {{ $p['nama'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-filter">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Terapkan Filter
        </button>
        @php
            $hasCustomSanksi = (
                (int)$tahun !== (int)now()->year ||
                (string)$bulan !== 'all' ||
                $unitKerja !== 'all' ||
                (!empty($nik) && $nik !== 'all')
            );
        @endphp
        <a id="btn-reset-sanksi" href="{{ route('sanksi.laporan') }}" class="btn btn-outline" style="height:38px; padding:0 12px; font-size:13px; color:#DC2626; border-color:#FECACA; display:{{ $hasCustomSanksi ? 'inline-flex' : 'none' }}; align-items:center;">Reset</a>
    </form>
</div>

{{-- Data Table --}}
<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th>Tgl / Bulan</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th>Unit Kerja</th>
                    <th>Jabatan</th>
                    <th>Tingkat / Jenis Sanksi</th>
                    <th>Keterangan / Nomor SK</th>
                    <th style="text-align:center;">Pot (%)</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filtered as $idx => $s)
                    @php
                        $tingkatClass = match($s['tingkat']) {
                            'Ringan' => 'badge-tingkat-ringan',
                            'Sedang' => 'badge-tingkat-sedang',
                            'Berat'  => 'badge-tingkat-berat',
                            default  => 'badge-tingkat-sedang',
                        };
                    @endphp
                    <tr>
                        <td style="text-align:center; color:#64748b; font-weight:600;">{{ $idx + 1 }}</td>
                        <td style="white-space:nowrap;">
                            <strong>{{ $s['tanggal_formatted'] }}</strong>
                            <div style="font-size:11px; color:#64748b;">{{ $s['bulan_teks'] }}</div>
                        </td>
                        <td class="cell-nik">{{ $s['formatted_nik'] }}</td>
                        <td class="cell-name">
                            <strong>{{ $s['nama_lengkap'] }}</strong>
                        </td>
                        <td>{{ $s['unit_kerja'] }}</td>
                        <td>{{ $s['jabatan'] }}</td>
                        <td>
                            <span class="badge-tingkat {{ $tingkatClass }}">{{ $s['tingkat'] }}</span>
                            <div style="font-weight:600; margin-top:2px;">{{ $s['jenis_sanksi'] }}</div>
                        </td>
                        <td>
                            @if (!empty($s['nomor_sk']) && $s['nomor_sk'] !== '-')
                                <div style="font-size:11.5px; font-weight:700; color:#0284c7;">No. SK: {{ $s['nomor_sk'] }}</div>
                            @endif
                            <div style="font-size:12px; color:#334155;">{{ $s['keterangan'] }}</div>
                        </td>
                        <td style="text-align:center; font-family:'Space Grotesk', monospace; font-weight:700; color:{{ $s['potongan_persen'] > 0 ? '#dc2626' : '#64748b' }};">
                            {{ $s['potongan_persen'] > 0 ? $s['potongan_persen'] . '%' : '-' }}
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="{{ route('sanksi.cetak-pegawai', ['nik' => $s['nik'], 'tahun' => $tahun]) }}" target="_blank" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size:11.5px;">
                                Cetak Kartu
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="table-empty">Tidak ada data sanksi disiplin pegawai untuk filter ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('partials.signature-block', ['kodeTtd' => 'gaji'])

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('.filter-form');
    var btnReset = document.getElementById('btn-reset-sanksi');
    if (form && btnReset) {
        var defTahun = '{{ now()->year }}';

        function checkSanksiFilter() {
            var th = form.querySelector('[name="tahun"]')?.value || defTahun;
            var bl = form.querySelector('[name="bulan"]')?.value || 'all';
            var uk = form.querySelector('[name="unit_kerja"]')?.value || 'all';
            var nk = form.querySelector('[name="nik"]')?.value || 'all';

            var isChanged = (th != defTahun) || (bl !== 'all') || (uk !== 'all') || (nk !== 'all' && nk !== '');
            btnReset.style.display = isChanged ? 'inline-flex' : 'none';
        }

        form.querySelectorAll('select, input').forEach(function(el) {
            el.addEventListener('change', checkSanksiFilter);
            el.addEventListener('input', checkSanksiFilter);
        });
    }
});
</script>
@endsection
