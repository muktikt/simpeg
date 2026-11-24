@extends('layouts.app')

@section('title', 'Data NIK Bulan Ini (Berjalan)')

@section('content')
@php
    $bulanNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
@endphp

<style>
.bi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}
.bi-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 4px 0;
}
.bi-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
}

.bi-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.bi-kpi-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 18px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
    position: relative;
    border-top: 4px solid #0284C7;
}
.bi-kpi-card.theme-total { border-top-color: #0284C7; }
.bi-kpi-card.theme-nominal { border-top-color: #10B981; }
.bi-kpi-card.theme-status { border-top-color: #8B5CF6; }

.bi-kpi-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748B;
    margin-bottom: 6px;
}
.bi-kpi-val {
    font-size: 20px;
    font-weight: 700;
    color: #0F2A3D;
}
.bi-kpi-sub {
    font-size: 12px;
    color: #64748B;
    margin-top: 4px;
}

.bi-filter-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
}

/* Modal Detail */
.bi-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 42, 61, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.bi-modal-box {
    background: #ffffff;
    width: 100%;
    max-width: 520px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.bi-modal-header {
    padding: 16px 20px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.bi-modal-body {
    padding: 20px;
    overflow-y: auto;
}
.bi-modal-footer {
    padding: 12px 20px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    display: flex;
    justify-content: flex-end;
}
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Set Potongan / Cek NIK Bulan Ini</div>
    <div class="bi-header">
        <div>
            <h1 class="bi-title">DATA NIK & POTONGAN BULAN BERJALAN</h1>
            <p class="bi-subtitle">
                Periode Pembanding: {{ $bulanNames[$bulan] ?? $bulan }} {{ $tahun }} ({{ $tipeLabels[$tipe] ?? strtoupper($tipe) }})
            </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @php
                $targetLalu = \Carbon\Carbon::create($tahun, $bulan, 1)->subMonth();
            @endphp
            <a href="{{ route('cek-nik.komparasi', ['tipe' => $tipe, 'bulan_a' => $targetLalu->month, 'tahun_a' => $targetLalu->year, 'bulan_b' => $bulan, 'tahun_b' => $tahun]) }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Bandingkan dengan Bulan Kemarin &rarr;
            </a>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="bi-filter-card">
    <form method="GET" action="{{ route('cek-nik.bulan-ini') }}">
        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px;">
            <div style="display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase;">Jenis Potongan</label>
                <select name="tipe" style="height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                    @foreach ($tipeLabels as $tKey => $tLabel)
                        <option value="{{ $tKey }}" @selected($tipe === $tKey)>{{ $tLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase;">Bulan Berjalan</label>
                <select name="bulan" style="height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
                    @foreach ($bulanNames as $bNum => $bTxt)
                        <option value="{{ $bNum }}" @selected($bulan === $bNum)>{{ $bTxt }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase;">Tahun</label>
                <input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2100" style="width:90px; height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
            </div>

            <div style="flex:1; min-width:200px; display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase;">Cari NIK / Nama / Unit</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Ketik NIK atau Nama Pegawai..." style="height:38px; padding:6px 12px; border:1px solid #CBD5E1; border-radius:8px;">
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary" style="height:38px;">Terapkan</button>
                @php
                    $hasCustomFilter = (
                        !empty($search) ||
                        (int)$bulan !== (int)now()->month ||
                        (int)$tahun !== (int)now()->year
                    );
                @endphp
                <a id="btn-reset-bulan-ini" href="{{ route('cek-nik.bulan-ini') }}" class="btn btn-outline" style="height:38px; display:{{ $hasCustomFilter ? 'inline-flex' : 'none' }}; align-items:center; color:#DC2626; border-color:#FECACA;">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- KPI Cards -->
<div class="bi-kpi-grid">
    <div class="bi-kpi-card theme-total">
        <div class="bi-kpi-label">Total Pegawai Terdaftar</div>
        <div class="bi-kpi-val">{{ number_format($total, 0, ',', '.') }} <span style="font-size:13px; font-weight:normal; color:#64748B;">Pegawai</span></div>
        <div class="bi-kpi-sub">Periode {{ $bulanNames[$bulan] ?? $bulan }} {{ $tahun }}</div>
    </div>

    <div class="bi-kpi-card theme-nominal">
        <div class="bi-kpi-label">Total Potongan Terdata</div>
        <div class="bi-kpi-val" style="color:#10B981;">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
        <div class="bi-kpi-sub">
            Rata-rata: Rp {{ $total > 0 ? number_format($totalNominal / $total, 0, ',', '.') : 0 }} / orang
        </div>
    </div>

    <div class="bi-kpi-card theme-status">
        <div class="bi-kpi-label">Status Penerbitan</div>
        <div class="bi-kpi-val" style="font-size:18px;">
            <span style="color:#10B981;">{{ $countTerbit }} Terbit Final</span>
            @if ($countDraft > 0)
                <span style="color:#F59E0B; font-size:14px; margin-left:6px;">({{ $countDraft }} Draft)</span>
            @endif
        </div>
        <div class="bi-kpi-sub">{{ $countDraft === 0 ? 'Semua data telah disahkan final' : 'Terdapat data berstatus draft' }}</div>
    </div>
</div>

<!-- Data Table -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px; text-align:center;">No</th>
                <th style="width:120px;">NIK</th>
                <th>Nama Pegawai</th>
                <th>Unit Kerja & Jabatan</th>
                <th style="width:140px; text-align:center;">Status Potongan</th>
                <th style="width:150px; text-align:right;">Total Potongan</th>
                <th style="width:90px; text-align:center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($paginator as $row)
                <tr>
                    <td style="text-align:center; color:#64748B;">
                        {{ ($paginator->currentPage() - 1) * $paginator->perPage() + $loop->iteration }}
                    </td>
                    <td>
                        <span class="cell-nik" style="font-family:monospace; font-weight:700; background:#F1F5F9; padding:2px 8px; border-radius:4px;">
                            {{ $row['nik'] }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:#0F2A3D;">{{ $row['nama_lengkap'] }}</div>
                    </td>
                    <td>
                        <div style="color:#334155; font-size:13px;">{{ $row['unit_kerja'] }}</div>
                        <div style="color:#94A3B8; font-size:12px;">{{ $row['jabatan'] }}</div>
                    </td>
                    <td style="text-align:center;">
                        @if ($row['status'] === 'Y')
                            <span class="badge" style="background:#DCFCE7; color:#15803D; border:1px solid #86EFAC; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:700;">
                                Terbit Final
                            </span>
                        @else
                            <span class="badge" style="background:#FEF3C7; color:#B45309; border:1px solid #FCD34D; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:700;">
                                Draft
                            </span>
                        @endif
                    </td>
                    <td style="text-align:right; font-family:monospace; font-weight:700; color:#0F2A3D;">
                        Rp {{ number_format($row['total_potongan'], 0, ',', '.') }}
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="btn btn-sm btn-outline"
                                onclick="openRincianModal({{ json_encode($row) }})"
                                style="padding:4px 9px; font-size:11.5px;">
                            Rincian
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:36px 20px;">
                        <div style="color:#64748B; font-weight:600; font-size:14px;">Tidak ada data NIK potongan untuk periode ini.</div>
                        <p style="color:#94A3B8; font-size:12px; margin-top:4px;">Silakan pilih bulan atau tahun lain pada filter di atas.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if ($paginator->hasPages())
    <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:13px; color:#64748B;">
            Menampilkan {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data
        </div>
        <div>
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    </div>
@endif

<!-- Modal Rincian 11 Kolom -->
<div id="rincianModal" class="bi-modal-overlay">
    <div class="bi-modal-box">
        <div class="bi-modal-header">
            <div>
                <h3 style="font-size:15px; font-weight:700; margin:0; color:#0F2A3D;" id="rincianNama">Rincian Potongan Pegawai</h3>
                <div style="font-size:12px; color:#64748B; margin-top:2px;" id="rincianSub">NIK: -</div>
            </div>
            <button type="button" onclick="closeRincianModal()" style="border:none; background:transparent; font-size:20px; color:#94A3B8; cursor:pointer;">&times;</button>
        </div>
        <div class="bi-modal-body">
            <table class="data-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Komponen Potongan</th>
                        <th style="text-align:right;">Nominal</th>
                    </tr>
                </thead>
                <tbody id="rincianTbody">
                </tbody>
                <tfoot>
                    <tr style="font-weight:700; background:#F8FAFC;">
                        <td>TOTAL POTONGAN</td>
                        <td style="text-align:right; font-family:monospace;" id="rincianTotal">Rp 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="bi-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeRincianModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
const kolomLabels = @json($kolomLabels);

function formatRp(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function openRincianModal(row) {
    document.getElementById('rincianNama').innerText = row.nama_lengkap;
    document.getElementById('rincianSub').innerText = 'NIK: ' + row.nik + ' • ' + row.unit_kerja;

    const tbody = document.getElementById('rincianTbody');
    tbody.innerHTML = '';

    const detail = row.detail || {};
    let total = 0;

    for (const [colKey, colName] of Object.entries(kolomLabels)) {
        const val = parseInt(detail[colKey] || 0);
        total += val;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${colName}</td>
            <td style="text-align:right; font-family:monospace; color:${val > 0 ? '#0F2A3D' : '#94A3B8'}; font-weight:${val > 0 ? '600' : 'normal'};">${formatRp(val)}</td>
        `;
        tbody.appendChild(tr);
    }

    document.getElementById('rincianTotal').innerText = formatRp(total);
    document.getElementById('rincianModal').style.display = 'flex';
}

function closeRincianModal() {
    document.getElementById('rincianModal').style.display = 'none';
}

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeRincianModal();
});

document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[action*="cek-nik/bulan-ini"]');
    var btnReset = document.getElementById('btn-reset-bulan-ini');
    if (form && btnReset) {
        var defBulan = '{{ now()->month }}';
        var defTahun = '{{ now()->year }}';

        function checkBulanIniFilter() {
            var b = form.querySelector('[name="bulan"]')?.value || defBulan;
            var t = form.querySelector('[name="tahun"]')?.value || defTahun;
            var s = form.querySelector('[name="search"]')?.value?.trim() || '';

            var isChanged = (b != defBulan) || (t != defTahun) || (s !== '');
            btnReset.style.display = isChanged ? 'inline-flex' : 'none';
        }

        form.querySelectorAll('select, input').forEach(function(el) {
            el.addEventListener('change', checkBulanIniFilter);
            el.addEventListener('input', checkBulanIniFilter);
        });
    }
});
</script>
@endsection
