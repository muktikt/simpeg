@extends('layouts.app')

@section('title', 'Alur Persetujuan & Penerbitan ' . $tipeLabel)

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
    padding: 6px 14px;
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

/* Workflow Step Indicator */
.flow-steps-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
}
.flow-steps-list {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.flow-step-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}
.flow-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}
.step-draft .flow-step-num { background: #FEF3C7; color: #B45309; border: 1px solid #FCD34D; }
.step-sdm .flow-step-num { background: #E0E7FF; color: #4338CA; border: 1px solid #C7D2FE; }
.step-final .flow-step-num { background: #DCFCE7; color: #15803D; border: 1px solid #86EFAC; }
.flow-arrow {
    color: #94A3B8;
    font-weight: 700;
    font-size: 16px;
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
    background: #E0E7FF;
    color: #3730A3;
    border: 1px solid #C7D2FE;
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
    padding: 5px 12px;
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
    background: #EEF2FF;
    color: #4338CA;
    border: 1px solid #C7D2FE;
}
.btn-sdm-approve:hover {
    background: #4338CA;
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
.btn-batal-terbit {
    background: #FFF1F2;
    color: #E11D48;
    border: 1px solid #FECDD3;
}
.btn-batal-terbit:hover {
    background: #E11D48;
    color: #ffffff;
}

/* Floating / Sticky Selected Batch Bar */
.selection-action-bar {
    display: none;
    background: #0F2A3D;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(15, 42, 61, 0.3);
    margin-bottom: 16px;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    animation: slideDown 0.25s ease-out;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.custom-checkbox {
    width: 17px;
    height: 17px;
    cursor: pointer;
    accent-color: #059669;
}
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / Alur Terbit & Approval / {{ $tipeLabel }}</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
        <div>
            <h1 style="margin:0;">ALUR APPROVAL & PENERBITAN {{ strtoupper($tipeLabel) }}</h1>
            <p style="margin:4px 0 0 0; color:#64748B; font-size:13px;">Data yang di-upload atau di-input berstatus Draft, disetujui SDM, lalu diterbitkan Final oleh Keuangan.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="{{ route('potongan-keu.index', $tipe) }}" class="btn btn-outline" style="font-size:13px; padding:7px 14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="15 18 9 12 15 6"/></svg>
                Kembali ke Data Potongan
            </a>
            <div class="live-pulse-badge">
                <span class="live-dot"></span>
                <span>REALTIME SYNC</span>
                <span id="last-sync-time" style="font-weight:500; opacity:0.8; font-size:11px; margin-left:4px;">({{ date('H:i:s') }})</span>
            </div>
        </div>
    </div>
</div>

<!-- Penjelasan Alur / Flow Steps -->
<div class="flow-steps-card">
    <div class="flow-steps-list">
        <div class="flow-step-item step-draft">
            <span class="flow-step-num">1</span>
            <div>
                <div style="font-weight:700; color:#0F2A3D;">Input / Upload (Draft)</div>
                <div style="font-size:11px; color:#64748B;">Data masuk status 'N'</div>
            </div>
        </div>
        <span class="flow-arrow">&rarr;</span>
        <div class="flow-step-item step-sdm">
            <span class="flow-step-num">2</span>
            <div>
                <div style="font-weight:700; color:#0F2A3D;">Persetujuan SDM</div>
                <div style="font-size:11px; color:#64748B;">Verifikasi bagian Kepegawaian</div>
            </div>
        </div>
        <span class="flow-arrow">&rarr;</span>
        <div class="flow-step-item step-final">
            <span class="flow-step-num">3</span>
            <div>
                <div style="font-weight:700; color:#0F2A3D;">Terbitkan Final (Keuangan)</div>
                <div style="font-size:11px; color:#64748B;">Sah & Masuk ke Slip/Insentif</div>
            </div>
        </div>
    </div>
</div>

<!-- Kartu Statistik Realtime -->
<div class="terbit-stats-grid">
    <div class="terbit-stat-card" style="border-left: 4px solid #D97706;">
        <div style="font-size:12px; font-weight:700; color:#B45309; margin-bottom:4px; text-transform:uppercase;">1. Draf (Menunggu SDM)</div>
        <div class="terbit-stat-num" style="color:#D97706;" id="stat-count-draft">
            {{ $countDraft }} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>
        </div>
    </div>
    <div class="terbit-stat-card" style="border-left: 4px solid #4F46E5;">
        <div style="font-size:12px; font-weight:700; color:#4338CA; margin-bottom:4px; text-transform:uppercase;">2. Disetujui SDM (Siap Terbit)</div>
        <div class="terbit-stat-num" style="color:#4F46E5;" id="stat-count-sdm">
            {{ $countDisetujuiSdm }} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>
        </div>
    </div>
    <div class="terbit-stat-card" style="border-left: 4px solid #16A34A;">
        <div style="font-size:12px; font-weight:700; color:#15803D; margin-bottom:4px; text-transform:uppercase;">3. Diterbitkan Final</div>
        <div class="terbit-stat-num" style="color:#16A34A;" id="stat-count-final">
            {{ $countFinal }} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>
        </div>
    </div>
    <div class="terbit-stat-card" style="border-left: 4px solid #0D2C6E;">
        <div style="font-size:12px; font-weight:700; color:#0D2C6E; margin-bottom:4px; text-transform:uppercase;">Total Nominal Potongan</div>
        <div class="terbit-stat-num" style="color:#0D2C6E; font-size:20px;" id="stat-grand-total">
            Rp {{ number_format($totals['grand_total'] ?? 0, 0, ',', '.') }}
        </div>
    </div>
</div>

<!-- Toolbar Filter, Pencarian Cepat, dan Aksi Global -->
<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:12px;">
    <!-- Filter Pencarian Instan (NIK / Nama) & Status -->
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; flex:1; max-width:720px;">
        <div style="position:relative; flex:1; min-width:220px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748B;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="filter-search" oninput="applyFilters()" placeholder="🔍 Cari NIK atau Nama Pegawai (contoh: 1 orang yang baru di-upload)..." class="form-control" style="padding-left:36px; height:40px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; width:100%;">
        </div>

        <select id="filter-status" onchange="applyFilters()" class="form-control" style="height:40px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:auto; min-width:180px; background:#ffffff;">
            <option value="all">Semua Status ({{ $allItems->count() }})</option>
            <option value="draft">1. Draft Menunggu SDM ({{ $countDraft }})</option>
            <option value="sdm">2. Disetujui SDM ({{ $countDisetujuiSdm }})</option>
            <option value="final">3. Diterbitkan Final ({{ $countFinal }})</option>
        </select>
    </div>

    <!-- Tombol Aksi Massal (Berdasarkan Role) -->
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <!-- Tombol Setujui Semua Draf untuk SDM -->
        @if ($isSdm && $countDraft > 0)
            <form action="{{ route('potongan-keu.setujui-kepegawaian', $tipe) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui seluruh {{ $countDraft }} data draf potongan oleh Bagian Kepegawaian (SDM)?', 'Konfirmasi Persetujuan SDM', 'info', 'Ya, Setujui SDM');">
                @csrf
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <button type="submit" class="btn btn-outline" style="background:#EEF2FF; border-color:#C7D2FE; color:#4338CA; font-weight:700; height:40px; padding:0 16px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    SETUJUI SEMUA (SDM)
                </button>
            </form>
        @endif

        <!-- Tombol Terbitkan Semua Final untuk Keuangan -->
        @if ($isKeu && ($countDraft + $countDisetujuiSdm) > 0)
            <form action="{{ route('potongan-keu.terbitkan', $tipe) }}" method="POST" onsubmit="return confirmSubmit(event, 'Terbitkan secara Final semua potongan periode ini? Potongan yang diterbitkan akan sah dan langsung masuk perhitungan slip gaji.', 'Konfirmasi Terbitkan Final', 'warning', 'Ya, Terbitkan Final');">
                @csrf
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <button type="submit" class="btn btn-success" style="font-weight:700; height:40px; padding:0 18px; box-shadow:0 4px 12px rgba(22, 163, 74, 0.25);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px;"><path d="M20 6L9 17l-5-5"/></svg>
                    TERBITKAN SEMUA (FINAL)
                </button>
            </form>
        @endif

        <!-- Tombol Batal Terbit Semua untuk Kembalikan ke Draf -->
        @if ($isKeu && $countFinal > 0)
            <form action="{{ route('potongan-keu.batal-terbit', $tipe) }}" method="POST" onsubmit="return confirmSubmit(event, 'Batalkan status terbit dan kembalikan seluruh {{ $countFinal }} data potongan yang sudah diterbitkan ke status Draf (Menunggu SDM)?', 'Konfirmasi Batalkan Penerbitan', 'warning', 'Ya, Batal Terbit');">
                @csrf
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <button type="submit" class="btn btn-outline" style="background:#FFF1F2; border-color:#FECDD3; color:#E11D48; font-weight:700; height:40px; padding:0 14px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    BATALKAN SEMUA TERBIT
                </button>
            </form>
        @endif
    </div>
</div>

<!-- Floating Bar saat Checkbox Pegawai Dipilih -->
<div id="selection-action-bar" class="selection-action-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="background:#059669; color:#ffffff; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700;" id="selection-counter">
            0 Pegawai Terpilih
        </span>
        <span style="font-size:13px; color:#CBD5E1;">Pilih aksi untuk pegawai yang dicentang:</span>
    </div>

    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <button type="button" class="btn btn-sm" style="background:#4338CA; color:#ffffff; border:none; padding:8px 14px; font-weight:700;" onclick="submitBatchApproval('sdm')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
            Setujui SDM (Terpilih)
        </button>
        <button type="button" class="btn btn-sm btn-success" style="padding:8px 16px; font-weight:700;" onclick="submitBatchApproval('terbitkan')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="margin-right:4px;"><path d="M20 6L9 17l-5-5"/></svg>
            Terbitkan Final (Terpilih)
        </button>
        <button type="button" class="btn btn-sm" style="background:#FFF1F2; color:#E11D48; border:1px solid #FECDD3; padding:8px 14px; font-weight:700;" onclick="submitBatchApproval('batal')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="margin-right:4px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            Batal Terbit (Terpilih)
        </button>
        <button type="button" class="btn btn-sm btn-outline" style="color:#ffffff; border-color:rgba(255,255,255,0.3); padding:8px 12px;" onclick="clearSelection()">
            Batal
        </button>
    </div>
</div>

<!-- Tabel Daftar Potongan -->
<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table" id="table-terbit-potongan">
            <thead>
                <tr>
                    <th style="width:36px; text-align:center;">
                        <input type="checkbox" id="check-all" class="custom-checkbox" onclick="toggleSelectAll(this)" title="Pilih Semua yang Ditampilkan">
                    </th>
                    <th style="width:30px;">No</th>
                    <th>Tgl Entry</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="text-align:right;">Total Potongan</th>
                    <th style="text-align:center;">Status</th>
                    <th style="width:140px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="potongan-tbody">
                @forelse ($items as $item)
                    @php
                        $st = $item['status'] ?? 'N';
                        $isTerbit = in_array($st, ['Y', 'terbit'], true);
                        $isSdm = ($st === 'kepegawaian');
                        $statusCategory = $isTerbit ? 'final' : ($isSdm ? 'sdm' : 'draft');
                    @endphp
                    <tr id="row-potongan-{{ $item['id'] }}" class="item-row" data-id="{{ $item['id'] }}" data-status="{{ $statusCategory }}" data-nik="{{ strtolower($item['nik']) }}" data-nama="{{ strtolower($item['nama']) }}">
                        <td style="text-align:center;">
                            <input type="checkbox" class="row-checkbox custom-checkbox" value="{{ $item['id'] }}" onchange="handleRowCheckChange()">
                        </td>
                        <td class="row-index">{{ $loop->iteration }}</td>
                        <td>{{ formatTglIndo($item['tgl_potongan'], 'd/m/Y') }}</td>
                        <td class="cell-nik" style="font-weight:700;">{{ $item['nik'] }}</td>
                        <td class="cell-name">
                            <div style="font-weight:600; color:#0F2A3D;">{{ $item['nama'] }}</div>
                            <div style="font-size:11.5px; color:#64748B;">{{ $item['jabatan'] ?? '' }}</div>
                        </td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($item[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; font-weight:700; color:#0D2C6E;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        
                        <!-- Status Column -->
                        <td style="text-align:center;" id="status-cell-{{ $item['id'] }}">
                            @if ($isTerbit)
                                <span class="badge-status-terbit" title="Disahkan oleh: {{ $item['disetujui_oleh'] ?? '-' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M20 6L9 17l-5-5"/></svg>
                                    Diterbitkan Final
                                </span>
                            @elseif ($isSdm)
                                <span class="badge-status-kepegawaian" title="Disetujui SDM: {{ $item['disetujui_kepegawaian_oleh'] ?? '-' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                                    Disetujui SDM
                                </span>
                            @else
                                <span class="badge-status-menunggu" title="Menunggu verifikasi Bagian Kepegawaian (SDM)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Draft (Menunggu SDM)
                                </span>
                            @endif
                        </td>

                        <!-- Action Column -->
                        <td style="text-align:center;" id="action-cell-{{ $item['id'] }}">
                            @if ($isTerbit)
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <span style="font-size:12px; color:#16A34A; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
                                        Final
                                    </span>
                                    @if ($isKeu)
                                        <button type="button" class="btn-sm-action btn-batal-terbit" onclick="ajaxBatalTerbitSingle({{ $item['id'] }})" title="Batalkan penerbitan & kembalikan status ke Draf (Menunggu SDM)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                            Batal
                                        </button>
                                    @endif
                                </div>
                            @elseif ($isSdm)
                                <button type="button" class="btn-sm-action btn-keu-terbit" onclick="ajaxTerbitkanSingle({{ $item['id'] }})" title="Terbitkan potongan pegawai ini ke perhitungan final">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M20 6L9 17l-5-5"/></svg>
                                    Terbitkan Final
                                </button>
                            @else
                                <button type="button" class="btn-sm-action btn-sdm-approve" onclick="ajaxSetujuiSdmSingle({{ $item['id'] }})" title="Setujui potongan pegawai ini sebagai SDM">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                                    Setujui SDM
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 8 }}">
                            <div class="table-empty" style="padding:36px 16px; text-align:center;">
                                <div style="font-size:16px; font-weight:700; color:#15803D; margin-bottom:4px;">Tidak ada data potongan pada periode ini.</div>
                                <div style="font-size:13px; color:#64748B;">Silakan unggah berkas CSV/Excel atau tambahkan data potongan di menu sebelumnya.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($items->count() > 0)
                <tfoot style="background:#F6F8FC; font-weight:700;">
                    <tr>
                        <td colspan="5" style="text-align:center;">TOTAL KESELURUHAN ({{ $items->count() }} PEGAWAI)</td>
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
// Endpoints & Tokens
const realtimeUrl = "{{ route('potongan-keu.realtime-status', $tipe) }}";
const terbitkanUrl = "{{ route('potongan-keu.terbitkan', $tipe) }}";
const setujuiSdmUrl = "{{ route('potongan-keu.setujui-kepegawaian', $tipe) }}";
const batalTerbitUrl = "{{ route('potongan-keu.batal-terbit', $tipe) }}";
const csrfToken = "{{ csrf_token() }}";

// ───── Instant Live Search & Status Filtering ─────
function applyFilters() {
    const searchVal = (document.getElementById('filter-search').value || '').toLowerCase().trim();
    const statusVal = document.getElementById('filter-status').value;
    const rows = document.querySelectorAll('#potongan-tbody tr.item-row');

    let visibleCount = 0;
    rows.forEach(row => {
        const nik = row.getAttribute('data-nik') || '';
        const nama = row.getAttribute('data-nama') || '';
        const status = row.getAttribute('data-status') || '';

        const matchSearch = !searchVal || nik.includes(searchVal) || nama.includes(searchVal);
        const matchStatus = (statusVal === 'all') || (status === statusVal);

        if (matchSearch && matchStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Perbarui penomoran baris yang tampak
    let counter = 1;
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const indexCell = row.querySelector('.row-index');
            if (indexCell) indexCell.textContent = counter++;
        }
    });

    updateSelectionBar();
}

// ───── Checkbox Selection Logic ─────
function toggleSelectAll(masterCheckbox) {
    const rows = document.querySelectorAll('#potongan-tbody tr.item-row');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.row-checkbox');
            if (cb) cb.checked = masterCheckbox.checked;
        }
    });
    updateSelectionBar();
}

function handleRowCheckChange() {
    updateSelectionBar();
}

function getSelectedIds() {
    const checkedBoxes = document.querySelectorAll('#potongan-tbody .row-checkbox:checked');
    const ids = [];
    checkedBoxes.forEach(cb => ids.push(cb.value));
    return ids;
}

function updateSelectionBar() {
    const selectedIds = getSelectedIds();
    const bar = document.getElementById('selection-action-bar');
    const counterSpan = document.getElementById('selection-counter');

    if (selectedIds.length > 0) {
        bar.style.display = 'flex';
        counterSpan.textContent = selectedIds.length + ' Pegawai Terpilih';
    } else {
        bar.style.display = 'none';
        const masterCb = document.getElementById('check-all');
        if (masterCb) masterCb.checked = false;
    }
}

function clearSelection() {
    const checkedBoxes = document.querySelectorAll('#potongan-tbody .row-checkbox:checked');
    checkedBoxes.forEach(cb => cb.checked = false);
    updateSelectionBar();
}

// ───── AJAX Batch Actions (For Selected Employees) ─────
function submitBatchApproval(actionType) {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Silakan pilih minimal 1 pegawai dengan mencentang kotak di tabel.');
        return;
    }

    let url;
    let confirmMsg;

    if (actionType === 'sdm') {
        url = setujuiSdmUrl;
        confirmMsg = `Setujui ${ids.length} pegawai terpilih oleh Bagian Kepegawaian (SDM)?`;
    } else if (actionType === 'terbitkan') {
        url = terbitkanUrl;
        confirmMsg = `Terbitkan secara Final ${ids.length} pegawai terpilih oleh Keuangan? Potongan akan sah dan masuk ke slip gaji.`;
    } else if (actionType === 'batal') {
        url = batalTerbitUrl;
        confirmMsg = `Batalkan status terbit untuk ${ids.length} pegawai terpilih dan kembalikan ke status Draf (Menunggu SDM)?`;
    }

    if (!confirm(confirmMsg)) return;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(r => r.json())
    .then(data => {
        clearSelection();
        fetchRealtimeStatus();
        if (typeof showCustomAlert === 'function') {
            showCustomAlert(data.message || 'Operasi berhasil diproses.', 'Berhasil', 'success');
        } else {
            alert(data.message || 'Operasi berhasil diproses.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat memproses data.');
    });
}

// ───── AJAX Single Row Actions ─────
function ajaxSetujuiSdmSingle(rowId) {
    fetch(setujuiSdmUrl, {
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
        if (typeof showCustomAlert === 'function') {
            showCustomAlert('Potongan pegawai berhasil disetujui SDM.', 'Disetujui SDM', 'success');
        }
    });
}

function ajaxTerbitkanSingle(rowId) {
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
        if (typeof showCustomAlert === 'function') {
            showCustomAlert('Potongan pegawai berhasil diterbitkan final ke database.', 'Diterbitkan Final', 'success');
        }
    });
}

function ajaxBatalTerbitSingle(rowId) {
    if (!confirm('Kembalikan potongan pegawai ini ke status Draf (Menunggu SDM)?')) return;
    fetch(batalTerbitUrl, {
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
        if (typeof showCustomAlert === 'function') {
            showCustomAlert(data.message || 'Penerbitan dibatalkan. Status dikembalikan ke Draf.', 'Batal Terbit', 'info');
        } else {
            alert(data.message || 'Penerbitan dibatalkan.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat membatalkan penerbitan.');
    });
}

// ───── Realtime Polling Sync ─────
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

        // Perbarui Kartu Statistik
        const cDraft = document.getElementById('stat-count-draft');
        const cSdm   = document.getElementById('stat-count-sdm');
        const cFinal = document.getElementById('stat-count-final');
        const cTotal = document.getElementById('stat-grand-total');
        const lastSync = document.getElementById('last-sync-time');

        if (cDraft) cDraft.innerHTML = `${data.countDraft} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>`;
        if (cSdm) cSdm.innerHTML = `${data.countDisetujuiSdm} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>`;
        if (cFinal) cFinal.innerHTML = `${data.countFinal} <span style="font-size:13px; font-weight:500; color:#6B7789;">Pegawai</span>`;
        if (cTotal) cTotal.textContent = data.grandTotalFormatted;
        if (lastSync) lastSync.textContent = `(${data.timestamp})`;

        // Perbarui Status Per-Baris
        if (data.items) {
            data.items.forEach(it => {
                const cell = document.getElementById(`status-cell-${it.id}`);
                const actionCell = document.getElementById(`action-cell-${it.id}`);
                const row = document.getElementById(`row-potongan-${it.id}`);

                if (row) {
                    const statusCat = it.isTerbit ? 'final' : (it.isSdmApproved ? 'sdm' : 'draft');
                    row.setAttribute('data-status', statusCat);
                }

                if (cell) {
                    if (it.isTerbit) {
                        cell.innerHTML = `
                            <span class="badge-status-terbit" title="Disahkan oleh: ${it.disetujui_oleh || '-'}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M20 6L9 17l-5-5"/></svg>
                                Diterbitkan Final
                            </span>
                        `;
                        if (actionCell) {
                            actionCell.innerHTML = `
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <span style="font-size:12px; color:#16A34A; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
                                        Final
                                    </span>
                                    <button type="button" class="btn-sm-action btn-batal-terbit" onclick="ajaxBatalTerbitSingle(${it.id})" title="Kembalikan status ke Draf (Menunggu SDM)">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        Batal
                                    </button>
                                </div>
                            `;
                        }
                    } else if (it.isSdmApproved) {
                        cell.innerHTML = `
                            <span class="badge-status-kepegawaian" title="Disetujui SDM: ${it.disetujui_kepegawaian_oleh || '-'}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                                Disetujui SDM
                            </span>
                        `;
                        if (actionCell) {
                            actionCell.innerHTML = `
                                <button type="button" class="btn-sm-action btn-keu-terbit" onclick="ajaxTerbitkanSingle(${it.id})" title="Terbitkan potongan pegawai ini ke perhitungan final">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M20 6L9 17l-5-5"/></svg>
                                    Terbitkan Final
                                </button>
                            `;
                        }
                    } else {
                        cell.innerHTML = `
                            <span class="badge-status-menunggu" title="Menunggu verifikasi Bagian Kepegawaian (SDM)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Draft (Menunggu SDM)
                            </span>
                        `;
                        if (actionCell) {
                            actionCell.innerHTML = `
                                <button type="button" class="btn-sm-action btn-sdm-approve" onclick="ajaxSetujuiSdmSingle(${it.id})" title="Setujui potongan pegawai ini sebagai SDM">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                                    Setujui SDM
                                </button>
                            `;
                        }
                    }
                }
            });
        }
    })
    .catch(err => {
        console.warn('Realtime sync status error:', err);
    });
}

// Auto-poll status setiap 7 detik
setInterval(fetchRealtimeStatus, 7000);
</script>
@endsection
