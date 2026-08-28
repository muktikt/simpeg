@extends('layouts.app')

@section('title', 'Kelola Pengumuman')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan / Kelola Pengumuman</div>
    <h1>Kelola Pengumuman</h1>
</div>

<!-- Toolbar Navigasi & Aksi -->
<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
    <!-- Filter Tabs -->
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="{{ route('pengumuman.index', ['filter' => 'semua', 'q' => request('q')]) }}" 
           class="btn btn-sm {{ $filter === 'semua' ? 'btn-primary' : 'btn-outline' }}">
            Semua ({{ $counts['semua'] }})
        </a>
        <a href="{{ route('pengumuman.index', ['filter' => 'tayang', 'q' => request('q')]) }}" 
           class="btn btn-sm {{ $filter === 'tayang' ? 'btn-primary' : 'btn-outline' }}" style="{{ $filter === 'tayang' ? 'background:#16A34A; border-color:#16A34A;' : '' }}">
            🟢 Tayang ({{ $counts['tayang'] }})
        </a>
        <a href="{{ route('pengumuman.index', ['filter' => 'terjadwal', 'q' => request('q')]) }}" 
           class="btn btn-sm {{ $filter === 'terjadwal' ? 'btn-primary' : 'btn-outline' }}" style="{{ $filter === 'terjadwal' ? 'background:#D97706; border-color:#D97706;' : '' }}">
            🕒 Terjadwal ({{ $counts['terjadwal'] }})
        </a>
        <a href="{{ route('pengumuman.index', ['filter' => 'kedaluwarsa', 'q' => request('q')]) }}" 
           class="btn btn-sm {{ $filter === 'kedaluwarsa' ? 'btn-primary' : 'btn-outline' }}" style="{{ $filter === 'kedaluwarsa' ? 'background:#DC2626; border-color:#DC2626;' : '' }}">
            🔴 Kedaluwarsa ({{ $counts['kedaluwarsa'] }})
        </a>
        <a href="{{ route('pengumuman.index', ['filter' => 'nonaktif', 'q' => request('q')]) }}" 
           class="btn btn-sm {{ $filter === 'nonaktif' ? 'btn-primary' : 'btn-outline' }}">
            ⚪ Nonaktif ({{ $counts['nonaktif'] }})
        </a>
    </div>

    <!-- Search & Tambah -->
    <div style="display:flex; gap:10px; align-items:center;">
        <form action="{{ route('pengumuman.index') }}" method="GET" style="display:flex; gap:6px; margin:0;">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari pengumuman..." style="padding:7px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px; min-width:200px;">
            <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        </form>

        <a href="{{ route('pengumuman.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Buat Pengumuman
        </a>
    </div>
</div>

<!-- Daftar Pengumuman Cards -->
<div style="display:flex; flex-direction:column; gap:16px;">
    @forelse ($pengumuman as $p)
        <div style="background:white; border-radius:14px; border:1px solid {{ ($p['disematkan'] ?? false) ? '#93C5FD' : '#E2E8F0' }}; padding:20px 24px; box-shadow:0 2px 8px rgba(0,0,0,0.03); position:relative; {{ ($p['disematkan'] ?? false) ? 'background:linear-gradient(to right, #F0F7FF, #FFFFFF);' : '' }}">
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:12px;">
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px;">
                        @if ($p['disematkan'] ?? false)
                            <span style="background:#DBEAFE; color:#1D4ED8; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                📌 Disematkan
                            </span>
                        @endif

                        @if ($p['prioritas'] ?? false)
                            <span style="background:#FEE2E2; color:#DC2626; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                ⚡ PENTING
                            </span>
                        @endif

                        @if ($p['status_kode'] === 'tayang')
                            <span style="background:#DCFCE7; color:#16A34A; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                                🟢 Sedang Tayang
                            </span>
                        @elseif ($p['status_kode'] === 'terjadwal')
                            <span style="background:#FEF3C7; color:#D97706; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                                🕒 Terjadwal ({{ formatTglIndo($p['terbit_pada'], 'd M Y, H:i') }})
                            </span>
                        @elseif ($p['status_kode'] === 'kedaluwarsa')
                            <span style="background:#FEE2E2; color:#DC2626; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                                🔴 Kedaluwarsa
                            </span>
                        @else
                            <span style="background:#F1F5F9; color:#64748B; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                                ⚪ Nonaktif
                            </span>
                        @endif

                        <span style="font-size:12px; color:#94A3B8;">
                            Dibuat: {{ formatTglIndo($p['created_at'], 'd M Y, H:i') }} oleh <strong>{{ $p['pembuat'] ?? 'SDM' }}</strong>
                        </span>
                    </div>

                    <h3 style="margin:0 0 8px 0; font-size:18px; font-weight:700; color:#0F172A;">
                        <a href="{{ route('pengumuman.show', $p['id']) }}" style="color:#0F172A; text-decoration:none;">
                            {{ $p['judul'] }}
                        </a>
                    </h3>

                    <p style="margin:0 0 12px 0; font-size:14px; color:#475569; line-height:1.5; white-space:pre-line; max-height:80px; overflow:hidden; text-overflow:ellipsis;">
                        {{ \Illuminate\Support\Str::limit($p['isi'], 280) }}
                    </p>

                    <!-- Target Roles & Metadata -->
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; font-size:12px; color:#64748B;">
                        <div>
                            <strong>Target:</strong>
                            @php
                                $rolesArr = $p['target_roles_array'] ?? [];
                                $isAll = count($rolesArr) >= count($rolesList);
                            @endphp
                            @if ($isAll)
                                <span style="background:#E2E8F0; padding:2px 6px; border-radius:4px; font-weight:600;">Semua Pegawai & Role</span>
                            @else
                                @foreach ($rolesArr as $rk)
                                    <span style="background:#E2E8F0; padding:2px 6px; border-radius:4px; font-weight:500; margin-right:3px;">
                                        {{ $rolesList[$rk] ?? $rk }}
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        @if (!empty($p['terbit_pada']))
                            <div>&bull; <strong>Jadwal Terbit:</strong> {{ formatTglIndo($p['terbit_pada']) }} {{ \Illuminate\Support\Carbon::parse($p['terbit_pada'])->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                        @endif

                        @if (!empty($p['kedaluwarsa_pada']))
                            <div>&bull; <strong>Kedaluwarsa:</strong> {{ formatTglIndo($p['kedaluwarsa_pada']) }} {{ \Illuminate\Support\Carbon::parse($p['kedaluwarsa_pada'])->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                        @endif

                        @if (!empty($p['lampiran_url']) || !empty($p['lampiran_nama']))
                            <div>
                                &bull; 📎 <a href="{{ $p['lampiran_url'] }}" target="_blank" style="color:#0284C7; font-weight:600; text-decoration:none;">{{ $p['lampiran_nama'] ?? 'Lihat Lampiran' }}</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display:flex; flex-direction:column; gap:6px; min-width:130px; align-items:flex-end;">
                    <a href="{{ route('pengumuman.show', $p['id']) }}" class="btn btn-outline btn-sm" style="width:100%; text-align:center;">
                        Detail & Preview
                    </a>

                    <!-- Toggle Pin -->
                    <form action="{{ route('pengumuman.toggle-sematkan', $p['id']) }}" method="POST" style="margin:0; width:100%;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" style="width:100%; font-size:11px;">
                            {{ ($p['disematkan'] ?? false) ? '📌 Lepas Sematan' : '📌 Sematkan' }}
                        </button>
                    </form>

                    <!-- Toggle Aktif/Nonaktif -->
                    <form action="{{ route('pengumuman.toggle-aktif', $p['id']) }}" method="POST" style="margin:0; width:100%;">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="width:100%; font-size:11px; {{ ($p['aktif'] ?? false) ? 'background:#FEF2F2; color:#DC2626; border:1px solid #FECACA;' : 'background:#F0FDF4; color:#16A34A; border:1px solid #BBF7D0;' }}">
                            {{ ($p['aktif'] ?? false) ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>

                    <!-- Edit & Hapus -->
                    <div style="display:flex; gap:6px; width:100%;">
                        <a href="{{ route('pengumuman.edit', $p['id']) }}" class="btn btn-outline btn-sm" style="flex:1; text-align:center;">
                            Edit
                        </a>
                        <form action="{{ route('pengumuman.destroy', $p['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Yakin ingin menghapus pengumuman ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0; flex:1;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div style="background:white; border-radius:14px; border:1px solid #E2E8F0; padding:48px 24px; text-align:center; color:#64748B;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" style="margin-bottom:12px; color:#94A3B8;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <h3 style="margin:0 0 6px 0; color:#334155;">Belum Ada Pengumuman</h3>
            <p style="margin:0 0 16px 0; font-size:14px;">Tidak ada pengumuman yang sesuai dengan filter atau kata kunci pencarian.</p>
            <a href="{{ route('pengumuman.create') }}" class="btn btn-primary btn-sm">Buat Pengumuman Baru</a>
        </div>
    @endforelse
</div>
@endsection
