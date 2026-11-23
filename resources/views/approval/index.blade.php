@extends('layouts.app')

@section('title', 'Kotak Masuk Approval')

@section('content')
@php
    $bulanNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
@endphp

<style>
.badge-jenis {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.badge-jenis-gaji {
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #BFDBFE;
}
.badge-jenis-thr {
    background: #FAF5FF;
    color: #7E22CE;
    border: 1px solid #E9D5FF;
}
.badge-jenis-gaji13 {
    background: #F0FDFA;
    color: #0F766E;
    border: 1px solid #99F6E4;
}
.badge-jenis-potongan {
    background: #EEF2FF;
    color: #4338CA;
    border: 1px solid #C7D2FE;
}
.badge-jenis-cuti {
    background: #FFFBEB;
    color: #B45309;
    border: 1px solid #FDE68A;
}

.approval-filter-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #CBD5E1;
    color: #334155;
    background: #ffffff;
    transition: all 0.15s ease;
}
.pagination-btn:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
    color: #0F2A3D;
}
.pagination-btn.active {
    background: #0D2C6E;
    color: #ffffff;
    border-color: #0D2C6E;
    font-weight: 700;
}
.pagination-btn.disabled {
    opacity: 0.45;
    cursor: not-allowed;
    pointer-events: none;
    background: #F8FAFC;
}

/* Modal Detail Approval */
@keyframes modalApprovalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.96) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
.modal-approval-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
    z-index: 999999 !important;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}
.modal-approval-overlay.active {
    display: flex !important;
}
.modal-approval-card {
    background: #ffffff;
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
    overflow: hidden;
    position: relative;
    z-index: 1000000;
    animation: modalApprovalFadeIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>

@php
    $userNik = (string) session('simpeg_user.nik', '');
    $isKeu = (session('simpeg_user.userlevel') == 2 || session('simpeg_role') == 2 || $userNik === (string)config('simpeg_approval.keuangan', '1711296'));
    $isPotonganPage = str_starts_with($selectedJenis ?? '', 'potongan_');
    $currentJenisObj = $jenisList->firstWhere('key', $selectedJenis);
    $currentJenisLabel = $currentJenisObj['label'] ?? 'Potongan Gaji';

    if ($isKeu || $isPotonganPage) {
        $breadcrumbText = 'Home / Proses Terbit Potongan / ' . $currentJenisLabel;
        $pageTitle = 'Proses Terbit ' . $currentJenisLabel;
        $pageSubtitle = 'Monitoring status alur persetujuan berjenjang dan arsip riwayat penerbitan potongan pegawai per bulan.';
    } else {
        $breadcrumbText = 'Home / Approval';
        $pageTitle = 'Kotak Masuk Approval';
        $pageSubtitle = 'Daftar pengajuan cuti, gaji, THR, gaji 13, dan potongan pegawai yang sedang menunggu persetujuan Anda sesuai kewenangan role.';
    }
@endphp

<div class="page-head">
    <div class="breadcrumb">{{ $breadcrumbText }}</div>
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px 0;">{{ $pageTitle }}</h1>
            <p style="margin:0; font-size:13px; color:#64748B;">
                {{ $pageSubtitle }}
            </p>
        </div>
        <div style="display:inline-flex; align-items:center; gap:10px; flex-wrap:wrap;">
            @if (!$isKeu && ($totalPending ?? 0) > 0)
                <div style="display:inline-flex; align-items:center; gap:8px; background:#FEF3C7; border:1px solid #FCD34D; padding:6px 14px; border-radius:20px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:#D97706; display:inline-block;"></span>
                    <span style="font-size:12.5px; font-weight:700; color:#B45309;">{{ $totalPending }} Menunggu Persetujuan</span>
                </div>
            @endif
            @if (($totalFinal ?? 0) > 0)
                <div style="display:inline-flex; align-items:center; gap:8px; background:#F0FDF4; border:1px solid #BBF7D0; padding:6px 14px; border-radius:20px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:#16A34A; display:inline-block;"></span>
                    <span style="font-size:12.5px; font-weight:700; color:#15803D;">{{ $totalFinal }} Terbit Final (Histori)</span>
                </div>
            @endif
            <div style="display:inline-flex; align-items:center; gap:8px; background:#F8FAFC; border:1px solid #E2E8F0; padding:6px 14px; border-radius:20px;">
                <span style="font-size:12.5px; font-weight:600; color:#475569;">Total: {{ $paginator->total() }} Data</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar Card -->
<div class="approval-filter-card">
    <form method="GET" action="{{ route('approval.index') }}" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <!-- Filter Jenis Approval -->
        <div style="min-width:180px; flex:1;">
            <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Jenis {{ $isKeu ? 'Potongan' : 'Approval' }}</label>
            <select name="jenis" class="form-control" style="height:38px; font-size:13px; font-weight:600; width:100%; border-radius:8px; border:1px solid #CBD5E1; background:#ffffff;" onchange="this.form.submit()">
                @if (!$isKeu)
                    <option value="all">Semua Jenis ({{ $totalAll }})</option>
                @endif
                @foreach ($jenisList as $j)
                    <option value="{{ $j['key'] }}" {{ ($selectedJenis === $j['key'] || $selectedJenis === $j['label']) ? 'selected' : '' }}>
                        {{ $j['label'] }} ({{ $j['count'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter Status Approval -->
        <div style="min-width:160px;">
            <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Status</label>
            <select name="status" class="form-control" style="height:38px; font-size:13px; font-weight:600; width:100%; border-radius:8px; border:1px solid #CBD5E1; background:#ffffff;" onchange="this.form.submit()">
                <option value="all" {{ ($selectedStatus ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                @if (!$isKeu)
                    <option value="pending" {{ ($selectedStatus ?? '') === 'pending' ? 'selected' : '' }}>Menunggu Persetujuan ({{ $totalPending ?? 0 }})</option>
                @endif
                <option value="final" {{ ($selectedStatus ?? '') === 'final' ? 'selected' : '' }}>Sudah Terbit Final ({{ $totalFinal ?? 0 }})</option>
                <option value="draft" {{ ($selectedStatus ?? '') === 'draft' ? 'selected' : '' }}>Draft (Menunggu SDM)</option>
                <option value="kepegawaian" {{ ($selectedStatus ?? '') === 'kepegawaian' ? 'selected' : '' }}>Disetujui SDM</option>
                <option value="dirum" {{ ($selectedStatus ?? '') === 'dirum' ? 'selected' : '' }}>Disetujui Dirum</option>
            </select>
        </div>

        <!-- Filter Bulan -->
        <div style="min-width:140px;">
            <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Bulan</label>
            <select name="bulan" class="form-control" style="height:38px; font-size:13px; font-weight:600; width:100%; border-radius:8px; border:1px solid #CBD5E1; background:#ffffff;" onchange="this.form.submit()">
                <option value="all">Semua Bulan</option>
                @foreach ($bulanNames as $mNum => $mLabel)
                    <option value="{{ $mNum }}" {{ (string)$selectedBulan === (string)$mNum ? 'selected' : '' }}>
                        {{ $mLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter Tahun -->
        <div style="min-width:110px;">
            <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Tahun</label>
            <select name="tahun" class="form-control" style="height:38px; font-size:13px; font-weight:600; width:100%; border-radius:8px; border:1px solid #CBD5E1; background:#ffffff;" onchange="this.form.submit()">
                <option value="all">Semua Tahun</option>
                @foreach ($tahunList as $yr)
                    <option value="{{ $yr }}" {{ (string)$selectedTahun === (string)$yr ? 'selected' : '' }}>
                        {{ $yr }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Pilihan Urutan (Sort) -->
        <div style="min-width:160px;">
            <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Urutkan</label>
            <select name="sort" class="form-control" style="height:38px; font-size:13px; font-weight:600; width:100%; border-radius:8px; border:1px solid #CBD5E1; background:#ffffff;" onchange="this.form.submit()">
                <option value="terbaru" {{ ($sort ?? '') === 'terbaru' ? 'selected' : '' }}>Entry Terbaru</option>
                <option value="terlama" {{ ($sort ?? '') === 'terlama' ? 'selected' : '' }}>Entry Terlama</option>
                <option value="nik_asc" {{ ($sort ?? '') === 'nik_asc' ? 'selected' : '' }}>NIK (Terkecil - Terbesar)</option>
                <option value="nik_desc" {{ ($sort ?? '') === 'nik_desc' ? 'selected' : '' }}>NIK (Terbesar - Terkecil)</option>
                <option value="nama_asc" {{ ($sort ?? '') === 'nama_asc' ? 'selected' : '' }}>Nama Pegawai (A - Z)</option>
                <option value="nama_desc" {{ ($sort ?? '') === 'nama_desc' ? 'selected' : '' }}>Nama Pegawai (Z - A)</option>
            </select>
        </div>

        <!-- Pencarian NIK / Nama -->
        <div style="min-width:220px; flex:1.5;">
            <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Cari Pegawai</label>
            <div style="position:relative;">
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Cari NIK atau Nama..." class="form-control" style="height:38px; font-size:13px; border-radius:8px; border:1px solid #CBD5E1; padding-left:34px; width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" style="position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94A3B8;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>

        <!-- Tombol Aksi Filter -->
        <div style="display:flex; gap:8px; align-items:flex-end; padding-top:20px;">
            <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px; font-size:13px; font-weight:600;">
                Terapkan
            </button>
            @php
                $resetUrl = ($isKeu || $isPotonganPage)
                    ? route('approval.index', ['jenis' => ($selectedJenis !== 'all' ? $selectedJenis : 'potongan_gaji')])
                    : route('approval.index');
                $hasCustomFilter = ($selectedBulan !== 'all' || $selectedTahun !== 'all' || ($selectedStatus ?? 'all') !== 'all' || !empty($searchQuery) || (!$isKeu && $selectedJenis !== 'all'));
            @endphp
            @if ($hasCustomFilter)
                <a href="{{ $resetUrl }}" class="btn btn-outline" style="height:38px; padding:0 12px; font-size:13px; color:#DC2626; border-color:#FECACA; display:inline-flex; align-items:center;" title="Reset Semua Filter">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Informasi Hasil Filter & Toolbar Aksi Massal Approval -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
    <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:#64748B; flex-wrap:wrap;">
        <span>Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }}</strong> dari <strong>{{ $paginator->total() }}</strong> data</span>
        @if ($paginator->total() !== $totalAll)
            <span style="color:#0D2C6E; font-weight:600;">(difilter dari {{ $totalAll }} total data)</span>
        @endif
        <span style="font-size:12px; background:#F1F5F9; padding:2px 8px; border-radius:12px; font-weight:600;">20 data/halaman</span>
    </div>

    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        @php
            $userNik = (string) session('simpeg_user.nik', '');
            $dirutNik = (string) config('simpeg_approval.dirut', '1711001');
            $isDirut = ($userNik === $dirutNik);
            $isKeu = (session('simpeg_user.userlevel') == 2 || session('simpeg_role') == 2 || $userNik === (string)config('simpeg_approval.keuangan', '1711296'));
            $btnActionText = ($isDirut) ? 'Terbitkan / Setujui Final' : 'Setujui';
            $pendingInFilter = $totalPendingInFilter ?? 0;
        @endphp
        @if (!$isKeu && $pendingInFilter > 0)
            <button type="button" id="btn-approve-selected" class="btn btn-outline btn-sm" onclick="approveSelected()" disabled style="display:inline-flex; align-items:center; gap:6px; font-weight:600; opacity:0.5; padding:6px 14px; border-radius:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $btnActionText }} Terpilih (<span id="count-terpilih">0</span>)
            </button>

            <button type="button" class="btn btn-sm" onclick="approveAllFiltered()" style="background:#15803D; color:#ffffff; border:1px solid #166534; display:inline-flex; align-items:center; gap:6px; font-weight:700; padding:6px 16px; border-radius:8px; box-shadow:0 2px 6px rgba(22, 101, 52, 0.25);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ $btnActionText }} Semua ({{ $pendingInFilter }} Item Pending)
            </button>
        @elseif (!$isKeu && ($totalFinal ?? 0) > 0 && $paginator->total() > 0)
            <div style="background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0; padding:6px 14px; border-radius:8px; font-weight:700; font-size:12.5px; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                Seluruh Data Sudah Disahkan / Terbit Final
            </div>
        @endif
    </div>
</div>

<!-- Alert Notifikasi Toast -->
<div id="approval-toast" style="display:none; position:fixed; top:24px; right:24px; z-index:9999; background:#15803D; color:#ffffff; padding:12px 20px; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,0.18); font-size:13.5px; font-weight:600; align-items:center; gap:10px; transition:all 0.3s ease;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toast-message">Persetujuan berhasil disimpan!</span>
</div>

<!-- Tabel Daftar Approval -->
<div class="table-card" style="box-shadow: 0 2px 8px rgba(15, 42, 61, 0.04); border-radius:12px; overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:36px; text-align:center;">
                    <input type="checkbox" id="check-all" class="custom-checkbox" onclick="toggleCheckAll(this)" title="Pilih Semua di Halaman Ini">
                </th>
                <th style="width:36px; text-align:center;">No</th>
                <th style="width:130px;">Jenis</th>
                <th style="width:105px;">NIK</th>
                <th>Nama Pegawai</th>
                <th>Periode / Keterangan</th>
                <th style="text-align:right; width:125px;">Nominal</th>
                <th style="text-align:center; width:150px;">Status</th>
                <th style="width:140px; text-align:center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $p)
                @php
                    $jKey = $p['jenis_key'] ?? '';
                    $badgeClass = 'badge-jenis-gaji';
                    if (str_starts_with($jKey, 'potongan_') || str_starts_with($p['jenis'], 'Potongan')) {
                        $badgeClass = 'badge-jenis-potongan';
                    } elseif ($jKey === 'thr' || $p['jenis'] === 'THR') {
                        $badgeClass = 'badge-jenis-thr';
                    } elseif ($jKey === 'gaji13' || $p['jenis'] === 'Gaji 13') {
                        $badgeClass = 'badge-jenis-gaji13';
                    } elseif ($jKey === 'cuti' || str_contains($p['jenis'], 'Cuti')) {
                        $badgeClass = 'badge-jenis-cuti';
                    }
                @endphp
                <tr id="row-{{ $p['id'] }}-{{ $p['jenis_key'] }}">
                    <td style="text-align:center;">
                        @if (!empty($p['can_approve']))
                            <input type="checkbox" class="row-checkbox custom-checkbox" value="{{ $p['id'] }}" data-id="{{ $p['id'] }}" data-jenis="{{ $p['jenis_key'] }}" onchange="handleRowCheckChange()">
                        @else
                            <input type="checkbox" class="custom-checkbox" disabled style="opacity:0.3; cursor:not-allowed;" title="{{ in_array($p['status'] ?? '', ['Y', 'terbit', 'disetujui', 'approved'], true) ? 'Sudah disahkan/terbit final' : 'Bukan tahap approval Anda' }}">
                        @endif
                    </td>
                    <td style="text-align:center; color:#64748B; font-weight:600;">
                        {{ ($paginator->currentPage() - 1) * $paginator->perPage() + $loop->iteration }}
                    </td>
                    <td>
                        <span class="badge-jenis {{ $badgeClass }}">
                            {{ $p['jenis'] }}
                        </span>
                    </td>
                    <td class="cell-nik" style="font-weight:700;">{{ $p['nik'] }}</td>
                    <td class="cell-name">
                        <div style="font-weight:600; color:#0F2A3D;">{{ $p['nama'] }}</div>
                        @if (!empty($p['jabatan']) && $p['jabatan'] !== '-')
                            <div style="font-size:11.5px; color:#64748B;">{{ $p['jabatan'] }}</div>
                        @endif
                    </td>
                    <td>
                        @if (!empty($p['keterangan']))
                            <span style="font-size:12.5px; color:#334155;">{{ $p['keterangan'] }}</span>
                        @elseif (!empty($p['bulan']))
                            <span style="font-size:12.5px; font-weight:500;">
                                {{ $bulanNames[(int)$p['bulan']] ?? ('Bulan ' . $p['bulan']) }} {{ $p['tahun'] ?? '' }}
                            </span>
                        @else
                            <span style="font-size:12.5px; font-weight:500;">{{ $p['tahun'] ?? '-' }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if (isset($p['nominal']) && $p['nominal'] > 0)
                            <strong style="color:#0D2C6E;">Rp {{ number_format($p['nominal'], 0, ',', '.') }}</strong>
                        @elseif ($p['jenis'] === 'Pengajuan Cuti')
                            <span style="color:#94A3B8; font-size:12px;">-</span>
                        @else
                            <span style="color:#64748B;">Rp 0</span>
                        @endif
                    </td>
                    <td style="text-align:center;" id="status-cell-{{ $p['id'] }}-{{ $p['jenis_key'] }}">
                        @php
                            $st = $p['status'] ?? '';
                            $isFin = in_array($st, ['Y', 'terbit', 'disetujui', 'approved'], true);
                            $isSdmSt = ($st === 'kepegawaian');
                            $isDirumSt = ($st === 'dirum');
                        @endphp
                        @if ($isFin)
                            <span class="badge" style="background:#DCFCE7; color:#15803D; border:1px solid #BBF7D0; font-size:11.5px; font-weight:700; padding:4px 8px; display:inline-flex; align-items:center; gap:4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $p['status_label'] ?? 'Terbit Final' }}
                            </span>
                        @elseif ($isDirumSt)
                            <span class="badge" style="background:#E0E7FF; color:#3730A3; border:1px solid #C7D2FE; font-size:11.5px; font-weight:600; padding:4px 8px;">
                                {{ $p['status_label'] ?? 'Disetujui Dirum' }}
                            </span>
                        @elseif ($isSdmSt)
                            <span class="badge" style="background:#E0F2FE; color:#0369A1; border:1px solid #BAE6FD; font-size:11.5px; font-weight:600; padding:4px 8px;">
                                {{ $p['status_label'] ?? 'Disetujui SDM' }}
                            </span>
                        @else
                            <span class="badge" style="background:#FEF3C7; color:#92400E; border:1px solid #FCD34D; font-size:11.5px; font-weight:600; padding:4px 8px;">
                                {{ $p['status_label'] ?? 'Draft (Menunggu SDM)' }}
                            </span>
                        @endif
                    </td>
                    <td style="text-align:center;" id="action-cell-{{ $p['id'] }}-{{ $p['jenis_key'] }}">
                        <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                            <button type="button" class="btn btn-outline btn-sm" style="padding:4px 12px; font-size:12px; font-weight:600; background:#ffffff; border-radius:6px; color:#0F2A3D; border:1px solid #CBD5E1;" onclick="openDetailModal('{{ $p['id'] }}', '{{ $p['jenis_key'] }}')">
                                Detail
                            </button>
                            @php
                                $uNik = (string) session('simpeg_user.nik', '');
                                $isKeuanganUser = (session('simpeg_user.userlevel') == 2 || session('simpeg_role') == 2 || $uNik === (string)config('simpeg_approval.keuangan', '1711296'));
                                $isDirutUser = ($uNik === (string)config('simpeg_approval.dirut', '1711001'));
                                $btnSingleLabel = $isDirutUser ? '✓ Terbitkan Final' : '✓ Setujui';
                            @endphp
                            @if (!$isKeuanganUser && !empty($p['can_approve']))
                                <button type="button" class="btn btn-sm btn-action-approve" style="background:#16A34A; color:#ffffff; border:1px solid #15803D; padding:4px 10px; font-size:11.5px; font-weight:700; border-radius:6px;" onclick="approveDirect('{{ $p['id'] }}', '{{ $p['jenis_key'] }}', this)">
                                    {{ $btnSingleLabel }}
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="table-empty" style="padding:40px 16px; text-align:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="40" height="40" style="margin:0 auto 12px; color:#94A3B8; display:block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <div style="font-weight:700; color:#334155; font-size:15px; margin-bottom:4px;">Tidak Ada Item yang Menunggu Approval</div>
                            <div style="font-size:13px; color:#64748B; max-width:440px; margin:0 auto;">
                                @if ($selectedJenis !== 'all' || $selectedBulan !== 'all' || $selectedTahun !== 'all' || !empty($searchQuery))
                                    Tidak ada data yang cocok dengan kriteria filter yang Anda pilih. Silakan sesuaikan filter atau reset untuk melihat semua data.
                                @else
                                    Semua pengajuan gaji, potongan, cuti, atau THR pada tahap Anda sudah diproses dan disetujui.
                                @endif
                            </div>
                            @if ($hasCustomFilter)
                                <div style="margin-top:14px;">
                                    <a href="{{ $resetUrl }}" class="btn btn-outline btn-sm">Reset Filter</a>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination Navigasi (20 Per Halaman) -->
@if ($paginator->hasPages())
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:18px; padding:12px 18px; background:#ffffff; border:1px solid #E2E8F0; border-radius:12px; box-shadow:0 2px 6px rgba(15, 42, 61, 0.02);">
        <div style="font-size:13px; color:#64748B;">
            Halaman <strong>{{ $paginator->currentPage() }}</strong> dari <strong>{{ $paginator->lastPage() }}</strong>
            <span style="margin:0 4px; color:#CBD5E1;">&bull;</span>
            Total <strong>{{ $paginator->total() }}</strong> baris
        </div>
        
        <div style="display:flex; gap:5px; align-items:center; flex-wrap:wrap;">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled">&laquo; Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn">&laquo; Sebelumnya</a>
            @endif

            @php
                $startPage = max(1, $paginator->currentPage() - 2);
                $endPage   = min($paginator->lastPage(), $paginator->currentPage() + 2);
            @endphp

            @if ($startPage > 1)
                <a href="{{ $paginator->url(1) }}" class="pagination-btn">1</a>
                @if ($startPage > 2)
                    <span style="padding:0 3px; color:#94A3B8;">...</span>
                @endif
            @endif

            @for ($i = $startPage; $i <= $endPage; $i++)
                @if ($i == $paginator->currentPage())
                    <span class="pagination-btn active">{{ $i }}</span>
                @else
                    <a href="{{ $paginator->url($i) }}" class="pagination-btn">{{ $i }}</a>
                @endif
            @endfor

            @if ($endPage < $paginator->lastPage())
                @if ($endPage < $paginator->lastPage() - 1)
                    <span style="padding:0 3px; color:#94A3B8;">...</span>
                @endif
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="pagination-btn">{{ $paginator->lastPage() }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn">Selanjutnya &raquo;</a>
            @else
                <span class="pagination-btn disabled">Selanjutnya &raquo;</span>
            @endif
        </div>
    </div>
@endif

<!-- Modal Detail Item Approval -->
<div id="modal-approval-detail" class="modal-approval-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-item-title">
    <div class="modal-approval-card">
        <!-- Modal Header -->
        <div style="padding:18px 24px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; background:#F8FAFC; flex-shrink:0;">
            <div>
                <span id="modal-jenis-badge" class="badge-jenis badge-jenis-potongan" style="margin-bottom:6px;">Jenis</span>
                <h3 id="modal-item-title" style="margin:2px 0 0 0; font-size:17px; font-weight:700; color:#0F2A3D;">Rincian Data Approval</h3>
            </div>
            <button type="button" onclick="closeDetailModal()" title="Tutup" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94A3B8; line-height:1; padding:4px 8px; border-radius:6px; transition:all 0.15s;" onmouseover="this.style.color='#0F172A'" onmouseout="this.style.color='#94A3B8'">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="padding:16px 22px; overflow-y:auto; flex:1; max-height:calc(88vh - 120px);">
            <!-- Profil Pegawai -->
            <div style="background:#F1F5F9; border-radius:12px; padding:14px 18px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div id="modal-pegawai-nama" style="font-weight:700; font-size:15px; color:#0F2A3D;">Nama Pegawai</div>
                    <div id="modal-pegawai-nik" style="font-size:12.5px; color:#64748B; margin-top:2px;">NIK: -</div>
                    <div id="modal-pegawai-jabatan" style="font-size:12.5px; color:#64748B;">Jabatan: -</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#64748B; letter-spacing:0.5px;">Periode</div>
                    <div id="modal-item-periode" style="font-weight:700; font-size:13.5px; color:#0F2A3D; margin-top:2px;">-</div>
                </div>
            </div>

            <!-- Petugas Entri Info (Potongan) -->
            <div id="modal-petugas-box" style="display:none; margin-bottom:12px; padding:8px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:12px; color:#64748B; justify-content:space-between; align-items:center;">
                <div>Petugas Entri: <strong id="modal-petugas-nama" style="color:#0F2A3D;">-</strong></div>
                <div>Tgl Entri: <span id="modal-petugas-tgl" style="color:#0F2A3D; font-weight:600;">-</span></div>
            </div>

            <!-- Total Nominal Box -->
            <div id="modal-nominal-box" style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:12px; padding:14px 18px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:13.5px; font-weight:600; color:#1E40AF;">Total Nominal:</span>
                <strong id="modal-nominal-val" style="font-size:18px; color:#1E3A8A; font-family:'Space Grotesk',sans-serif;">Rp 0</strong>
            </div>

            <!-- Rincian Item (Breakdown) -->
            <div>
                <div id="modal-breakdown-list" style="display:flex; flex-direction:column; gap:8px;">
                    <!-- Diisi via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding:14px 24px; border-top:1px solid #E2E8F0; display:flex; justify-content:flex-end; align-items:center; gap:10px; background:#F8FAFC; flex-shrink:0;">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeDetailModal()" style="padding:8px 16px; font-size:13px; font-weight:600; border-radius:8px;">
                Tutup
            </button>
            <button type="button" id="modal-btn-approve" class="btn btn-primary btn-sm" style="background:#16A34A; border-color:#15803D; font-weight:700; padding:8px 20px; font-size:13px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                ✓ Setujui Sekarang
            </button>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const BULAN_NAMES = @json($bulanNames);

// Checkbox select all
function toggleCheckAll(el) {
    const isChecked = el.checked;
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = isChecked;
    });
    handleRowCheckChange();
}

function handleRowCheckChange() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.row-checkbox');
    const checkAll = document.getElementById('check-all');
    if (checkAll && totalCheckboxes.length > 0) {
        checkAll.checked = (checked.length === totalCheckboxes.length);
    }
    const countSpan = document.getElementById('count-terpilih');
    const btnApprove = document.getElementById('btn-approve-selected');
    if (countSpan) countSpan.textContent = checked.length;
    if (btnApprove) {
        if (checked.length > 0) {
            btnApprove.disabled = false;
            btnApprove.style.opacity = '1';
        } else {
            btnApprove.disabled = true;
            btnApprove.style.opacity = '0.5';
        }
    }
}

// Show Toast
function showToast(message, isError = false) {
    const toast = document.getElementById('approval-toast');
    const msgEl = document.getElementById('toast-message');
    if (!toast || !msgEl) return;
    msgEl.textContent = message;
    toast.style.background = isError ? '#DC2626' : '#15803D';
    toast.style.display = 'inline-flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3500);
}

// Setujui Satu Item Langsung dari Tabel
function approveDirect(id, jenisKey, btnEl) {
    const item = (typeof approvalDataList !== 'undefined') ? approvalDataList.find(x => String(x.id) === String(id) && x.jenis_key === String(jenisKey)) : null;
    const itemNama = item ? item.nama : 'data ini';

    showCustomModal({
        title: 'Konfirmasi Persetujuan',
        text: `Apakah Anda yakin ingin menyetujui ${itemNama}?`,
        type: 'success',
        confirmText: 'Ya, Setujui',
        cancelText: 'Batal',
        showCancel: true,
        confirmBtnBg: '#15803D',
        onConfirm: () => {
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = 'Memproses...';
            }

            fetch('{{ route("approval.approve") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    jenis_key: jenisKey
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Persetujuan berhasil disimpan.');
                    const statusCell = document.getElementById(`status-cell-${id}-${jenisKey}`);
                    const actionCell = document.getElementById(`action-cell-${id}-${jenisKey}`);
                    if (statusCell) {
                        statusCell.innerHTML = `<span class="badge" style="background:#DCFCE7; color:#15803D; border:1px solid #86EFAC; font-size:11.5px; font-weight:700; padding:4px 8px;">✓ Disetujui</span>`;
                    }
                    if (actionCell) {
                        actionCell.innerHTML = `<span style="font-size:12px; color:#16A34A; font-weight:700;">✓ Selesai</span>`;
                    }
                } else {
                    showToast(data.message || 'Gagal menyetujui.', true);
                    if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.innerHTML = '✓ Setujui';
                    }
                }
            })
            .catch(err => {
                showToast('Terjadi kesalahan koneksi server.', true);
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = '✓ Setujui';
                }
            });
        }
    });
}

// Setujui Terpilih (Bulk Checkbox)
function approveSelected() {
    const checked = Array.from(document.querySelectorAll('.row-checkbox:checked'));
    if (checked.length === 0) return;

    showCustomModal({
        title: 'Konfirmasi Persetujuan Terpilih',
        text: `Apakah Anda yakin ingin menyetujui ${checked.length} data yang telah Anda pilih?`,
        type: 'success',
        confirmText: `Ya, Setujui (${checked.length})`,
        cancelText: 'Batal',
        showCancel: true,
        confirmBtnBg: '#15803D',
        onConfirm: () => {
            const items = checked.map(cb => ({
                id: cb.dataset.id,
                jenis_key: cb.dataset.jenis
            }));

            const btn = document.getElementById('btn-approve-selected');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = 'Memproses...';
            }

            fetch('{{ route("approval.approve-bulk") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    mode: 'selected',
                    items: items
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message || 'Gagal memproses approval massal.', true);
                    if (btn) btn.disabled = false;
                }
            })
            .catch(err => {
                showToast('Terjadi gangguan server.', true);
                if (btn) btn.disabled = false;
            });
        }
    });
}

// Setujui SEMUA Item yang Sesuai Filter
function approveAllFiltered() {
    const total = {{ $paginator->total() }};
    const jName = '{{ $selectedJenis !== "all" ? $selectedJenis : "semua jenis" }}';

    showCustomModal({
        title: 'Konfirmasi Persetujuan Massal',
        text: `Anda akan menyetujui SEMUA ${total} data pending untuk filter [${jName}]. Tindakan ini akan mengesahkan seluruh antrean data yang menunggu approval Anda.`,
        type: 'success',
        confirmText: 'Ya, Setujui Semua',
        cancelText: 'Batal',
        showCancel: true,
        confirmBtnBg: '#15803D',
        onConfirm: () => {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.7);z-index:99999;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:700;';
            overlay.innerHTML = '<div style="text-align:center;"><div style="margin-bottom:12px;font-size:32px;">⏳</div><div>Memproses persetujuan seluruh data... Mohon tunggu...</div></div>';
            document.body.appendChild(overlay);

            fetch('{{ route("approval.approve-bulk") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    mode: 'all_filtered',
                    filter_jenis: '{{ $selectedJenis }}',
                    filter_bulan: '{{ $selectedBulan }}',
                    filter_tahun: '{{ $selectedTahun }}'
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    document.body.removeChild(overlay);
                    showCustomAlert(data.message || 'Gagal memproses persetujuan seluruh data.', 'Peringatan', 'danger');
                }
            })
            .catch(err => {
                document.body.removeChild(overlay);
                showCustomAlert('Terjadi kesalahan jaringan atau server timeout.', 'Gangguan Jaringan', 'danger');
            });
        }
    });
}

// Modal Detail Item
const approvalDataList = @json($items);
let currentDetailItem = null;

function ensureDetailModalInBody() {
    const modal = document.getElementById('modal-approval-detail');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    return modal;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureDetailModalInBody);
} else {
    ensureDetailModalInBody();
}

function openDetailModal(id, jenisKey) {
    const modal = ensureDetailModalInBody();
    if (!modal) return;

    const item = approvalDataList.find(x => String(x.id) === String(id) && x.jenis_key === String(jenisKey));
    if (!item) {
        console.warn('Item approval tidak ditemukan:', id, jenisKey);
        return;
    }
    currentDetailItem = item;

    document.getElementById('modal-item-title').textContent = item.jenis || 'Rincian Approval';
    const badge = document.getElementById('modal-jenis-badge');
    if (badge) {
        badge.textContent = item.jenis;
        let badgeClass = 'badge-jenis-gaji';
        if (item.jenis_key && item.jenis_key.startsWith('potongan')) badgeClass = 'badge-jenis-potongan';
        else if (item.jenis_key === 'thr') badgeClass = 'badge-jenis-thr';
        else if (item.jenis_key === 'gaji13') badgeClass = 'badge-jenis-gaji13';
        else if (item.jenis_key === 'cuti') badgeClass = 'badge-jenis-cuti';
        badge.className = 'badge-jenis ' + badgeClass;
    }

    document.getElementById('modal-pegawai-nama').textContent = item.nama || '-';
    document.getElementById('modal-pegawai-nik').textContent = 'NIK: ' + (item.nik || '-');
    document.getElementById('modal-pegawai-jabatan').textContent = 'Jabatan: ' + (item.jabatan || item.unit_kerja || '-');

    let periodeText = '-';
    if (item.keterangan) {
        periodeText = item.keterangan;
    } else if (item.bulan) {
        periodeText = (BULAN_NAMES[item.bulan] || ('Bulan ' + item.bulan)) + ' ' + (item.tahun || '');
    } else if (item.tahun) {
        periodeText = 'Tahun ' + item.tahun;
    }
    document.getElementById('modal-item-periode').textContent = periodeText;

    const nominalBox = document.getElementById('modal-nominal-box');
    const nominalVal = document.getElementById('modal-nominal-val');
    if (item.nominal && item.nominal > 0) {
        nominalBox.style.display = 'flex';
        nominalVal.textContent = 'Rp ' + Number(item.nominal).toLocaleString('id-ID');
    } else if (item.jenis === 'Pengajuan Cuti' || item.jenis_key === 'cuti') {
        nominalBox.style.display = 'none';
    } else {
        nominalBox.style.display = 'flex';
        nominalVal.textContent = 'Rp 0';
    }

    // Petugas Entri Box
    const petugasBox = document.getElementById('modal-petugas-box');
    if (petugasBox) {
        if (item.petugas_entri && item.petugas_entri !== '-') {
            petugasBox.style.display = 'flex';
            document.getElementById('modal-petugas-nama').textContent = item.petugas_entri;
            document.getElementById('modal-petugas-tgl').textContent = item.tgl_potongan || '-';
        } else {
            petugasBox.style.display = 'none';
        }
    }

    // Breakdown Components
    const listEl = document.getElementById('modal-breakdown-list');
    listEl.innerHTML = '';
    const breakdown = item.breakdown || {};
    const keys = Object.keys(breakdown);

    if (keys.length > 0) {
        const isPotongan = item.jenis_key && item.jenis_key.startsWith('potongan');
        const activeCount = keys.filter(k => (typeof breakdown[k] === 'number' && breakdown[k] > 0)).length;

        const headerInfo = document.createElement('div');
        headerInfo.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; font-size:12.5px;';
        if (isPotongan) {
            headerInfo.innerHTML = `
                <span style="font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:0.5px;">Daftar 11 Komponen Potongan</span>
                <span class="badge" style="background:${activeCount > 0 ? '#DCFCE7' : '#F1F5F9'}; color:${activeCount > 0 ? '#15803D' : '#64748B'}; font-size:11.5px; padding:3px 10px; border-radius:12px; font-weight:700; border:1px solid ${activeCount > 0 ? '#86EFAC' : '#CBD5E1'};">
                    ${activeCount} dari 11 terisi ${activeCount === 0 ? '(Semua Rp 0)' : ''}
                </span>
            `;
        } else {
            headerInfo.innerHTML = `
                <span style="font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:0.5px;">Komponen Rincian</span>
            `;
        }
        listEl.appendChild(headerInfo);

        const gridContainer = document.createElement('div');
        gridContainer.style.cssText = isPotongan
            ? 'display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:6px;'
            : 'display:flex; flex-direction:column; gap:6px;';

        keys.forEach(k => {
            const val = breakdown[k];
            const isMoney = (typeof val === 'number');
            const isPositive = isMoney && val > 0;
            const formattedVal = isMoney ? ('Rp ' + Number(val).toLocaleString('id-ID')) : val;

            const row = document.createElement('div');
            if (isPositive) {
                row.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; font-size:12.5px;';
                row.innerHTML = `
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="width:6px; height:6px; border-radius:50%; background:#2563EB; display:inline-block; flex-shrink:0;"></span>
                        <span style="color:#1E3A8A; font-weight:700;">${k}</span>
                    </div>
                    <strong style="color:#1D4ED8; font-family:'Space Grotesk',sans-serif; font-size:13px;">${formattedVal}</strong>
                `;
            } else {
                row.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:7px 12px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:12px;';
                row.innerHTML = `
                    <span style="color:#64748B; font-weight:500;">${k}</span>
                    <span style="color:#94A3B8; font-weight:500;">${formattedVal}</span>
                `;
            }
            gridContainer.appendChild(row);
        });

        listEl.appendChild(gridContainer);
    } else {
        const emptyMsg = (item.jenis_key && item.jenis_key.startsWith('potongan'))
            ? 'Tidak ada rincian potongan untuk pegawai ini (Rp 0).'
            : 'Tidak ada rincian komponen tambahan.';
        listEl.innerHTML = `<div style="font-size:13px; color:#64748B; text-align:center; padding:18px 12px; background:#F8FAFC; border-radius:10px; border:1px dashed #CBD5E1;">${emptyMsg}</div>`;
    }

    const btnApprove = document.getElementById('modal-btn-approve');
    if (btnApprove) {
        const actionBtn = document.querySelector(`#action-cell-${item.id}-${item.jenis_key} .btn-action-approve`);
        if (actionBtn) {
            btnApprove.style.display = 'inline-flex';
            btnApprove.innerHTML = actionBtn.innerText || '✓ Setujui Sekarang';
            btnApprove.onclick = function() {
                closeDetailModal();
                approveDirect(item.id, item.jenis_key, actionBtn);
            };
        } else {
            btnApprove.style.display = 'none';
        }
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDetailModal() {
    const modal = document.getElementById('modal-approval-detail');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close on backdrop click
window.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-approval-detail');
    if (e.target === modal) {
        closeDetailModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
    }
});
</script>
@endsection
