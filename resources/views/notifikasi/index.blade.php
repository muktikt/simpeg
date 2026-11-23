@extends('layouts.app')

@section('title', 'Pusat Notifikasi & Status Kedinasan')

@section('content')
<style>
.notif-container {
    max-width: 1050px;
    margin: 0 auto;
}
.notif-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.notif-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 4px 0;
}
.notif-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
}

/* KPI Summary Cards */
.notif-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.notif-kpi-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 2px 6px rgba(15, 42, 61, 0.03);
    display: flex;
    align-items: center;
    gap: 14px;
}
.notif-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notif-kpi-val {
    font-size: 20px;
    font-weight: 700;
    color: #0F2A3D;
    line-height: 1.2;
}
.notif-kpi-lbl {
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filter Card */
.notif-filter-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

/* Notification List */
.notif-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.notif-item {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 20px;
    transition: all 0.2s ease;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    position: relative;
}
.notif-item.unread {
    background: #F0F9FF;
    border-color: #BAE6FD;
}
.notif-item:hover {
    box-shadow: 0 4px 12px rgba(15, 42, 61, 0.06);
    transform: translateY(-1px);
}
.notif-unread-dot {
    position: absolute;
    top: 18px;
    right: 20px;
    width: 8px;
    height: 8px;
    background: #0284C7;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
}
.notif-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notif-body {
    flex: 1;
    min-width: 0;
}
.notif-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.notif-item-title {
    font-size: 15px;
    font-weight: 700;
    color: #0F2A3D;
    text-decoration: none;
}
.notif-item-title:hover {
    color: #0284C7;
}
.notif-item-msg {
    font-size: 13.5px;
    color: #334155;
    line-height: 1.5;
    margin-bottom: 8px;
}
.notif-meta-row {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: #94A3B8;
    flex-wrap: wrap;
}
.notif-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
    flex-shrink: 0;
}
</style>

<div class="notif-container">
    <div class="page-head btn-no-print">
        <div class="breadcrumb">Home / Pusat Notifikasi & Status Kedinasan</div>
        <div class="notif-header">
            <div>
                <h1 class="notif-title">PUSAT NOTIFIKASI & STATUS KEDINASAN</h1>
                <p class="notif-subtitle">
                    Pantau informasi langsung status persetujuan cuti dinas, penerbitan payroll, slip gaji, dan pengumuman resmi PERUMDAM Tirta Darma Ayu.
                </p>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                @if ($unreadTotal > 0)
                    <form method="POST" action="{{ route('notifikasi.read-all') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
                <a href="{{ route('cuti.index') }}" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Permohonan Cuti
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" style="background:#DCFCE7; border:1px solid #86EFAC; color:#15803D; padding:12px 18px; border-radius:10px; margin-bottom:20px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="notif-kpi-grid">
        <div class="notif-kpi-card">
            <div class="notif-kpi-icon" style="background:#E0F2FE; color:#0284C7;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div>
                <div class="notif-kpi-val">{{ number_format($totalCount, 0, ',', '.') }}</div>
                <div class="notif-kpi-lbl">Total Notifikasi</div>
            </div>
        </div>

        <div class="notif-kpi-card">
            <div class="notif-kpi-icon" style="background:#FEE2E2; color:#DC2626;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="notif-kpi-val" style="color:{{ $unreadTotal > 0 ? '#DC2626' : '#0F2A3D' }};">{{ number_format($unreadTotal, 0, ',', '.') }}</div>
                <div class="notif-kpi-lbl">Belum Dibaca</div>
            </div>
        </div>

        <div class="notif-kpi-card">
            <div class="notif-kpi-icon" style="background:#DCFCE7; color:#15803D;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="notif-kpi-val">{{ number_format(max(0, $totalCount - $unreadTotal), 0, ',', '.') }}</div>
                <div class="notif-kpi-lbl">Sudah Dibaca</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="notif-filter-card">
        <form method="GET" action="{{ route('notifikasi.index') }}" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; flex:1;">
            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase;">Status:</label>
                <select name="status" onchange="this.form.submit()" style="height:36px; padding:4px 10px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                    <option value="all" @selected($filterStatus === 'all')>Semua Notifikasi</option>
                    <option value="unread" @selected($filterStatus === 'unread')>Belum Dibaca ({{ $unreadTotal }})</option>
                    <option value="read" @selected($filterStatus === 'read')>Sudah Dibaca</option>
                </select>
            </div>

            <div style="display:flex; align-items:center; gap:8px; flex:1; max-width:380px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari cuti, gaji, SK..." style="width:100%; height:36px; padding:6px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                @if (!empty($search))
                    <a href="{{ route('notifikasi.index') }}" class="btn btn-sm btn-outline" style="padding:6px 10px; font-size:12px;">Reset</a>
                @endif
                <button type="submit" class="btn btn-sm btn-primary" style="padding:6px 12px; font-size:12px;">Cari</button>
            </div>
        </form>

        <div style="font-size:12.5px; color:#64748B;">
            Menampilkan <strong>{{ $notifikasi->count() }}</strong> dari <strong>{{ $notifikasi->total() }}</strong> notifikasi
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notif-list">
        @forelse ($notifikasi as $n)
            @php
                $meta = \App\Http\Controllers\NotifikasiController::resolveMeta($n);
                $waktuCarbon = $n->waktu ? \Carbon\Carbon::parse($n->waktu) : \Carbon\Carbon::parse($n->created_at);
            @endphp
            <div class="notif-item {{ !$n->dibaca ? 'unread' : '' }}">
                @if (!$n->dibaca)
                    <div class="notif-unread-dot" title="Belum dibaca"></div>
                @endif

                <div class="notif-icon-box" style="background:{{ $meta['badge_bg'] }}; color:{{ $meta['badge_color'] }};">
                    @if ($meta['icon'] === 'check-circle')
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif ($meta['icon'] === 'x-circle')
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif ($meta['icon'] === 'clock')
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif ($meta['icon'] === 'credit-card')
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    @elseif ($meta['icon'] === 'alert-triangle')
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @elseif ($meta['icon'] === 'volume-2')
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5L6 9H2v6h4l5 4V5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
                    @else
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @endif
                </div>

                <div class="notif-body">
                    <div class="notif-title-row">
                        <a href="{{ $meta['url'] }}" class="notif-item-title">{{ $n->judul }}</a>
                        <span class="badge" style="background:{{ $meta['badge_bg'] }}; color:{{ $meta['badge_color'] }}; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px;">
                            {{ strtoupper($meta['type']) }}
                        </span>
                        @if (!$n->dibaca)
                            <span class="badge" style="background:#0284C7; color:#fff; font-size:10.5px; font-weight:700; padding:2px 6px; border-radius:4px;">BARU</span>
                        @endif
                    </div>

                    <div class="notif-item-msg">
                        {{ $n->pesan }}
                    </div>

                    <div class="notif-meta-row">
                        <span style="display:inline-flex; align-items:center; gap:4px;">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $waktuCarbon->diffForHumans() }} ({{ $waktuCarbon->translatedFormat('d M Y H:i') }})
                        </span>
                    </div>
                </div>

                <div class="notif-actions">
                    <a href="{{ $meta['url'] }}" class="btn btn-sm btn-outline" style="padding:5px 10px; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
                        Buka &rarr;
                    </a>
                    @if (!$n->dibaca)
                        <form method="POST" action="{{ route('notifikasi.read', $n->id) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-sm" style="background:#F1F5F9; color:#475569; border:1px solid #CBD5E1; padding:5px 10px; font-size:12px;" title="Tandai sudah dibaca">
                                ✓ Dibaca
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div style="background:#ffffff; border:1px solid #E2E8F0; border-radius:14px; padding:50px 20px; text-align:center;">
                <div style="width:54px; height:54px; background:#F8FAFC; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:#94A3B8; margin-bottom:12px;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div style="font-size:15px; font-weight:700; color:#0F2A3D;">Belum Ada Notifikasi</div>
                <p style="font-size:13px; color:#64748B; margin-top:4px;">Semua informasi status pengajuan cuti dan kedinasan akan tampil secara otomatis di sini.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div style="margin-top:20px;">
        {{ $notifikasi->links() }}
    </div>
</div>
@endsection

