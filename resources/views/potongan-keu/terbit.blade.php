@extends('layouts.app')

@section('title', 'Proses Terbit ' . $tipeLabel)

@section('content')
<style>
/* Realtime pulsating indicator */
.live-pulse-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #065F46;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulseDot 1.8s infinite;
}
@keyframes pulseDot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Stat Cards */
.terbit-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}
.terbit-stat-card {
    background: #ffffff;
    padding: 18px 20px;
    border-radius: 14px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 2px 8px rgba(15, 42, 61, 0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}
.terbit-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 42, 61, 0.08);
}
.terbit-stat-num {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
}

/* Status Badges */
.badge-status-menunggu {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FDE68A;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.badge-status-kepegawaian {
    background: #E0F2FE;
    color: #0369A1;
    border: 1px solid #BAE6FD;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.badge-status-terbit {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #BBF7D0;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Table Quick Action Button */
.btn-sm-action {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-sdm-approve {
    background: #E0F2FE;
    color: #0284C7;
    border: 1px solid #BAE6FD;
}
.btn-sdm-approve:hover {
    background: #0284C7;
    color: #ffffff;
}
.btn-keu-terbit {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #BBF7D0;
}
.btn-keu-terbit:hover {
    background: #16A34A;
    color: #ffffff;
}
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / Proses Terbit Potongan / {{ $tipeLabel }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
        <h1 style="margin:0;">PROSES PENERBITAN {{ strtoupper($tipeLabel) }}</h1>
        <div class="live-pulse-badge">
            <span class="live-dot"></span>
            <span>REALTIME SYNC AKTIF</span>
            <span id="last-sync-time" style="font-weight:500; opacity:0.8; font-size:11px; margin-left:4px;">({{ date('H:i:s') }})</span>
        </div>
    </div>
</div>

<!-- Kartu Statistik Realtime -->
<div class="terbit-stats-grid">
    <div class="terbit-stat-card">
        <div style="font-size:12.5px; font-weight:600; color:#B45309; margin-bottom:4px; text-transform:uppercase;">Menunggu SDM</div>
        <div class="terbit-stat-num" style="color:#D97706;" id="stat-menunggu-kepegawaian">
            {{ $menungguKepegawaian }} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>
        </div>
    </div>
    <div class="terbit-stat-card">
        <div style="font-size:12.5px; font-weight:600; color:#0369A1; margin-bottom:4px; text-transform:uppercase;">Disetujui SDM / Siap Terbit</div>
        <div class="terbit-stat-num" style="color:#0284C7;" id="stat-disetujui-kepegawaian">
            {{ $disetujuiKepegawaian }} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>
        </div>
    </div>
    <div class="terbit-stat-card">
        <div style="font-size:12.5px; font-weight:600; color:#15803D; margin-bottom:4px; text-transform:uppercase;">Diterbitkan Final</div>
        <div class="terbit-stat-num" style="color:#16A34A;" id="stat-sudah-diterbitkan">
            {{ $sudahDiterbitkan }} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>
        </div>
    </div>
    <div class="terbit-stat-card">
        <div style="font-size:12.5px; font-weight:600; color:#0D2C6E; margin-bottom:4px; text-transform:uppercase;">Total Nominal Potongan</div>
        <div class="terbit-stat-num" style="color:#0D2C6E; font-size:20px;" id="stat-grand-total">
            Rp {{ number_format($totals['grand_total'] ?? 0, 0, ',', '.') }}
        </div>
    </div>
</div>

<!-- Toolbar Aksi Persetujuan & Penerbitan -->
<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
    <div style="font-size:14.5px; font-weight:700; color:#16233A; display:flex; align-items:center; gap:8px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:#2E86AB;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Daftar Status Persetujuan Potongan Pegawai
    </div>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <!-- Tombol Setujui Kepegawaian (SDM) -->
        <form action="{{ route('potongan-keu.setujui-kepegawaian', $tipe) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui semua potongan ini sebagai Kepegawaian / SDM agar siap diterbitkan?', 'Persetujuan Kepegawaian', 'info', 'Ya, Setujui Semua');">
            @csrf
            <button type="submit" class="btn btn-outline" style="padding:9px 16px; font-weight:600; font-size:13px; color:#0284C7; border-color:#0284C7;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:5px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Setujui Semua (Kepegawaian)
            </button>
        </form>

        <!-- Tombol Terbitkan Final (Keuangan) -->
        <form action="{{ route('potongan-keu.terbitkan', $tipe) }}" method="POST" onsubmit="return confirmSubmit(event, 'Apakah Anda yakin ingin menerbitkan dan mengesahkan seluruh {{ strtolower($tipeLabel) }} ini secara final?', 'Konfirmasi Terbit Final', 'info', 'Ya, Terbitkan Final');">
            @csrf
            <button type="submit" class="btn btn-success" style="padding:9px 18px; font-weight:700; font-size:13px; box-shadow:0 4px 12px rgba(39, 174, 96, 0.25);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:5px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                TERBITKAN SEMUA (FINAL)
            </button>
        </form>
    </div>
</div>

<!-- Tabel Daftar Potongan Realtime -->
<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table" id="table-terbit-potongan">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th>Tgl Entry</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="text-align:right;">Total Potongan</th>
                    <th>Status Realtime</th>
                    <th style="width:120px; text-align:center;">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr id="row-potongan-{{ $item['id'] }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ formatTglIndo($item['tgl_potongan'], 'd/m/Y') }}</td>
                        <td class="cell-nik">{{ $item['nik'] }}</td>
                        <td class="cell-name">
                            <div style="font-weight:600; color:#0F2A3D;">{{ $item['nama'] }}</div>
                            <div style="font-size:11.5px; color:#64748B;">{{ $item['jabatan'] ?? '' }}</div>
                        </td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($item[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; font-weight:700; color:#0D2C6E;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td id="status-cell-{{ $item['id'] }}">
                            @if ($item['status'] === 'kepegawaian')
                                <span class="badge-status-kepegawaian">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    Disetujui SDM
                                </span>
                                @if(!empty($item['tgl_setuju_kepegawaian']))
                                    <div style="font-size:10.5px; color:#64748B; margin-top:2px;">{{ $item['tgl_setuju_kepegawaian'] }}</div>
                                @endif
                            @elseif ($item['status'] === 'Y' || $item['status'] === 'terbit')
                                <span class="badge-status-terbit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M20 6L9 17l-5-5"/></svg>
                                    Diterbitkan Final
                                </span>
                            @else
                                <span class="badge-status-menunggu">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Menunggu SDM
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if ($item['status'] === 'N' || empty($item['status']))
                                <button type="button" class="btn-sm-action btn-sdm-approve" onclick="ajaxApproveSdm({{ $item['id'] }})">
                                    Setujui SDM
                                </button>
                            @elseif ($item['status'] === 'kepegawaian')
                                <button type="button" class="btn-sm-action btn-keu-terbit" onclick="ajaxTerbitkan({{ $item['id'] }})">
                                    Terbitkan
                                </button>
                            @else
                                <span style="font-size:12px; color:#16A34A; font-weight:600;">Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 7 }}">
                            <div class="table-empty" style="padding:36px 16px; text-align:center;">
                                <div style="font-size:16px; font-weight:700; color:#15803D; margin-bottom:4px;">Seluruh Potongan Telah Selesai Diterbitkan.</div>
                                <div style="font-size:13px; color:#64748B;">Semua entri potongan {{ strtolower($tipeLabel) }} periode ini telah berstatus Disetujui & Diterbitkan Final.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($items->count() > 0)
                <tfoot style="background:#F6F8FC; font-weight:700;">
                    <tr>
                        <td colspan="4" style="text-align:center;">TOTAL KESELURUHAN</td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($totals[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; color:#0D2C6E;" id="tfoot-grand-total">Rp {{ number_format($totals['grand_total'] ?? 0, 0, ',', '.') }}</td>
                        <td colspan="2">-</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<script>
// Realtime Polling Status Terbit Potongan
const realtimeUrl = "{{ route('potongan-keu.realtime-status', $tipe) }}";
const sdmApproveUrl = "{{ route('potongan-keu.setujui-kepegawaian', $tipe) }}";
const terbitkanUrl = "{{ route('potongan-keu.terbitkan', $tipe) }}";
const csrfToken = "{{ csrf_token() }}";

function fetchRealtimeStatus() {
    fetch(realtimeUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) return;

        // Update statistics counter
        const statMenunggu = document.getElementById('stat-menunggu-kepegawaian');
        const statDisetujui = document.getElementById('stat-disetujui-kepegawaian');
        const statTerbit = document.getElementById('stat-sudah-diterbitkan');
        const statTotal = document.getElementById('stat-grand-total');
        const lastSync = document.getElementById('last-sync-time');

        if (statMenunggu) statMenunggu.innerHTML = `${data.menungguKepegawaian} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>`;
        if (statDisetujui) statDisetujui.innerHTML = `${data.disetujuiKepegawaian} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>`;
        if (statTerbit) statTerbit.innerHTML = `${data.sudahDiterbitkan} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>`;
        if (statTotal) statTotal.textContent = data.grandTotalFormatted;
        if (lastSync) lastSync.textContent = `(${data.timestamp})`;

        // Update per-row badges dynamically
        if (data.items) {
            data.items.forEach(it => {
                const cell = document.getElementById(`status-cell-${it.id}`);
                if (cell) {
                    if (it.status === 'kepegawaian') {
                        cell.innerHTML = `
                            <span class="badge-status-kepegawaian">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                Disetujui SDM
                            </span>
                            ${it.tgl_setuju ? `<div style="font-size:10.5px; color:#64748B; margin-top:2px;">${it.tgl_setuju}</div>` : ''}
                        `;
                    } else if (it.status === 'Y' || it.status === 'terbit') {
                        cell.innerHTML = `
                            <span class="badge-status-terbit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M20 6L9 17l-5-5"/></svg>
                                Diterbitkan Final
                            </span>
                        `;
                    } else {
                        cell.innerHTML = `
                            <span class="badge-status-menunggu">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Menunggu SDM
                            </span>
                        `;
                    }
                }
            });
        }
    })
    .catch(err => {
        console.warn('Realtime sync status error:', err);
    });
}

// Auto-poll every 6 seconds
const pollInterval = setInterval(fetchRealtimeStatus, 6000);

// Single row action helpers via AJAX with instant UI feedback
function ajaxApproveSdm(rowId) {
    fetch(sdmApproveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ id: rowId })
    })
    .then(r => r.json())
    .then(data => {
        fetchRealtimeStatus();
        showCustomAlert('Potongan pegawai berhasil disetujui oleh SDM / Kepegawaian.', 'Disetujui SDM', 'success');
    });
}

function ajaxTerbitkan(rowId) {
    fetch(terbitkanUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ id: rowId })
    })
    .then(r => r.json())
    .then(data => {
        fetchRealtimeStatus();
        showCustomAlert('Potongan pegawai berhasil diterbitkan secara final.', 'Diterbitkan Final', 'success');
    });
}
</script>
@endsection
