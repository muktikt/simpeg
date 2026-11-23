@extends('layouts.app')

@section('title', 'Audit Trail & Log Aktivitas Pengguna')

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
.kpi-card.primary { border-left: 4px solid #0284c7; }
.kpi-card.success { border-left: 4px solid #10b981; }
.kpi-card.warning { border-left: 4px solid #f59e0b; }
.kpi-card.danger  { border-left: 4px solid #ef4444; }
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
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-group label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
.btn-reset {
    height: 38px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    font-weight: 600;
    font-size: 13px;
    padding: 0 14px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-reset:hover { background: #e2e8f0; }
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

/* Badge Actions */
.badge-action {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.badge-action-login   { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-action-logout  { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.badge-action-create  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-action-update  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-action-delete  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

/* Badge Roles */
.badge-role {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.badge-role-direksi { background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; }
.badge-role-sdm     { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.badge-role-kspi    { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
.badge-role-pegawai { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }

.module-tag {
    display: inline-block;
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 600;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 4px;
}
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Sistem / Audit Trail & Log Aktivitas</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px 0;">AUDIT TRAIL & LOG AKTIVITAS PENGGUNA</h1>
            <p style="margin:0; font-size:13.5px; color:#64748b;">
                Rekam jejak komprehensif autentikasi login, aktivitas pengguna, dan modifikasi data SIMPEG
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('audit-log.cetak', request()->query()) }}" target="_blank" class="btn-action-cetak">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Log Audit (A4 Landscape)
            </a>
        </div>
    </div>
</div>

{{-- KPI Summary Cards --}}
<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-title">Total Log Terdata</div>
        <div class="kpi-value" style="color:#0284c7;">{{ number_format($totalLogs, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Aktivitas</span></div>
        <div class="kpi-sub">Sesuai filter pencarian aktif</div>
    </div>

    <div class="kpi-card success">
        <div class="kpi-title">Sesi Login Terverifikasi</div>
        <div class="kpi-value" style="color:#059669;">{{ number_format($totalLogin, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Kali</span></div>
        <div class="kpi-sub">Login pengguna web & mobile</div>
    </div>

    <div class="kpi-card warning">
        <div class="kpi-title">Modifikasi Data (C/U)</div>
        <div class="kpi-value" style="color:#d97706;">{{ number_format($totalModif, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Operasi</span></div>
        <div class="kpi-sub">Penambahan & pembaruan data</div>
    </div>

    <div class="kpi-card danger">
        <div class="kpi-title">Penghapusan Data (Delete)</div>
        <div class="kpi-value" style="color:#dc2626;">{{ number_format($totalDelete, 0, ',', '.') }} <span style="font-size:14px; font-weight:500; color:#64748b;">Operasi</span></div>
        <div class="kpi-sub">Aktivitas penghapusan data penting</div>
    </div>
</div>

{{-- Filter Card --}}
<div class="filter-card">
    <form method="GET" action="{{ route('audit-log.index') }}" class="filter-form">
        <div class="filter-group" style="flex: 2; min-width: 200px;">
            <label for="q">Pencarian Cepat</label>
            <input type="text" name="q" id="q" value="{{ $q }}" placeholder="Cari nama, NIK, deskripsi aktivitas...">
        </div>

        <div class="filter-group">
            <label for="action">Jenis Aksi</label>
            <select name="action" id="action">
                <option value="all">-- Semua Aksi --</option>
                @foreach ($actionsList as $a)
                    <option value="{{ $a }}" @selected(strtoupper($action) === strtoupper($a))>{{ $a }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="module">Modul Sistem</label>
            <select name="module" id="module">
                <option value="all">-- Semua Modul --</option>
                @foreach ($modulesList as $m)
                    <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="role">Hak Akses / Role</label>
            <select name="role" id="role">
                <option value="all">-- Semua Role --</option>
                @foreach ($rolesList as $r)
                    <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="date_start">Dari Tanggal</label>
            <input type="date" name="date_start" id="date_start" value="{{ $dateStart }}">
        </div>

        <div class="filter-group">
            <label for="date_end">Sampai Tanggal</label>
            <input type="date" name="date_end" id="date_end" value="{{ $dateEnd }}">
        </div>

        <button type="submit" class="btn-filter">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Filter
        </button>

        <a href="{{ route('audit-log.index') }}" class="btn-reset">
            Reset
        </a>
    </form>
</div>

{{-- Data Table Card --}}
<div class="table-card">
    <div style="padding:14px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:#0f172a;">
            Rekaman Audit Trail Terkini (Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }})
        </div>
        <div style="font-size:12.5px; color:#64748b;">
            Menampilkan <strong>{{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}</strong> dari <strong>{{ $logs->total() }}</strong> log aktivitas
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:140px;">Waktu (WIB)</th>
                    <th style="width:170px;">Pengguna (NIK)</th>
                    <th style="width:120px;">Role</th>
                    <th style="width:90px;">Aksi</th>
                    <th style="width:120px;">Modul</th>
                    <th>Deskripsi Aktivitas</th>
                    <th style="width:150px;">Perangkat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $act = strtoupper($log->action ?? '');
                        $actBadge = match($act) {
                            'LOGIN'  => 'badge-action-login',
                            'LOGOUT' => 'badge-action-logout',
                            'CREATE' => 'badge-action-create',
                            'UPDATE' => 'badge-action-update',
                            'DELETE' => 'badge-action-delete',
                            default  => 'badge-action-login',
                        };

                        $r = strtoupper($log->role ?? '');
                        $roleBadge = match(true) {
                            str_contains($r, 'DIR')    => 'badge-role-direksi',
                            str_contains($r, 'SDM')    => 'badge-role-sdm',
                            str_contains($r, 'SPI') || str_contains($r, 'KSPI') => 'badge-role-kspi',
                            default                    => 'badge-role-pegawai',
                        };

                        $waktu = \Illuminate\Support\Carbon::parse($log->created_at)->timezone('Asia/Jakarta');
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:700; color:#0f172a;">{{ $waktu->translatedFormat('d M Y') }}</div>
                            <div style="font-size:11.5px; color:#64748b; font-family:'Space Grotesk', monospace;">{{ $waktu->format('H:i:s') }} WIB</div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:#0f172a;">{{ $log->user_name ?? '-' }}</div>
                            <div style="font-size:11.5px; font-family:'Space Grotesk', monospace; color:#0369a1;">NIK: {{ $log->user_nik ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge-role {{ $roleBadge }}">{{ $log->role ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge-action {{ $actBadge }}">{{ $act }}</span>
                        </td>
                        <td>
                            <span class="module-tag">{{ $log->module ?? '-' }}</span>
                        </td>
                        <td style="font-size:12.5px; color:#334155; line-height:1.4;">
                            {{ $log->description ?? '-' }}
                        </td>
                        <td style="font-size:11px; color:#64748b; white-space:nowrap;" title="{{ $log->device_info }}">
                            {{ Str::limit($log->device_info ?? '-', 24) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="table-empty">Tidak ada log aktivitas yang cocok dengan kriteria filter.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div style="padding:16px 20px; border-top:1px solid #e2e8f0; display:flex; justify-content:center;">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection

