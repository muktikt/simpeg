@extends('layouts.app')

@section('title', 'Detail Pengumuman - ' . $p['judul'])

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Kelola Pengumuman / Detail</div>
    <h1>Detail Pengumuman</h1>
</div>

<div style="max-width:860px;">
    <div style="background:white; border-radius:16px; border:1px solid {{ ($p['disematkan'] ?? false) ? '#93C5FD' : '#E2E8F0' }}; padding:32px; box-shadow:0 2px 10px rgba(0,0,0,0.03); margin-bottom:20px;">
        
        <!-- Header Tags -->
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
            @if ($p['disematkan'] ?? false)
                <span style="background:#DBEAFE; color:#1D4ED8; font-size:12px; font-weight:700; padding:4px 10px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                    📌 Disematkan
                </span>
            @endif

            @if ($p['prioritas'] ?? false)
                <span style="background:#FEE2E2; color:#DC2626; font-size:12px; font-weight:700; padding:4px 10px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                    ⚡ PENTING
                </span>
            @endif

            @if ($p['status_kode'] === 'tayang')
                <span style="background:#DCFCE7; color:#16A34A; font-size:12px; font-weight:700; padding:4px 10px; border-radius:6px;">
                    🟢 Sedang Tayang
                </span>
            @elseif ($p['status_kode'] === 'terjadwal')
                <span style="background:#FEF3C7; color:#D97706; font-size:12px; font-weight:700; padding:4px 10px; border-radius:6px;">
                    🕒 Terjadwal ({{ formatTglIndo($p['terbit_pada']) }})
                </span>
            @elseif ($p['status_kode'] === 'kedaluwarsa')
                <span style="background:#FEE2E2; color:#DC2626; font-size:12px; font-weight:700; padding:4px 10px; border-radius:6px;">
                    🔴 Kedaluwarsa
                </span>
            @else
                <span style="background:#F1F5F9; color:#64748B; font-size:12px; font-weight:700; padding:4px 10px; border-radius:6px;">
                    ⚪ Nonaktif
                </span>
            @endif
        </div>

        <h2 style="font-size:24px; font-weight:700; color:#0F172A; margin:0 0 12px 0;">
            {{ $p['judul'] }}
        </h2>

        <div style="font-size:13px; color:#64748B; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid #E2E8F0; display:flex; gap:16px; flex-wrap:wrap;">
            <div>👤 Diterbitkan oleh: <strong>{{ $p['pembuat'] ?? 'SDM PERUMDAM' }}</strong></div>
            <div>📅 Dibuat: <strong>{{ formatTglIndo($p['created_at']) }}</strong></div>
            @if (!empty($p['terbit_pada']))
                <div>🕒 Tayang: <strong>{{ formatTglIndo($p['terbit_pada']) }} {{ \Illuminate\Support\Carbon::parse($p['terbit_pada'])->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</strong></div>
            @endif
            @if (!empty($p['kedaluwarsa_pada']))
                <div>⌛ Kedaluwarsa: <strong>{{ formatTglIndo($p['kedaluwarsa_pada']) }} {{ \Illuminate\Support\Carbon::parse($p['kedaluwarsa_pada'])->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</strong></div>
            @endif
        </div>

        <!-- Body Isi -->
        <div style="font-size:15px; color:#334155; line-height:1.7; white-space:pre-line; margin-bottom:30px;">
            {{ $p['isi'] }}
        </div>

        <!-- Lampiran -->
        @if (!empty($p['lampiran_url']) || !empty($p['lampiran_nama']))
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:18px 20px; border-radius:12px; margin-bottom:24px;">
                <div style="font-size:13px; font-weight:700; color:#475569; margin-bottom:8px;">BERKAS LAMPIRAN</div>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#0284C7" stroke-width="2" width="20" height="20"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span style="font-weight:600; color:#0F172A;">{{ $p['lampiran_nama'] ?? 'Dokumen Lampiran' }}</span>
                    </div>
                    <a href="{{ $p['lampiran_url'] }}" target="_blank" class="btn btn-outline btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Unduh / Buka Dokumen
                    </a>
                </div>
            </div>
        @endif

        <!-- Target Roles Tags -->
        <div style="background:#F8FAFC; padding:14px 18px; border-radius:10px; border:1px solid #E2E8F0; font-size:13px; color:#475569;">
            <strong style="margin-right:8px;">Target Penerima:</strong>
            @php
                $rolesArr = $p['target_roles_array'] ?? [];
                $isAll = count($rolesArr) >= count($rolesList);
            @endphp
            @if ($isAll)
                <span style="background:#E2E8F0; padding:3px 8px; border-radius:6px; font-weight:600;">Semua Role & Pegawai</span>
            @else
                @foreach ($rolesArr as $rk)
                    <span style="background:#E2E8F0; padding:3px 8px; border-radius:6px; font-weight:500; margin-right:4px;">
                        {{ $rolesList[$rk] ?? $rk }}
                    </span>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Tombol Aksi Bawah -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <a href="{{ route('pengumuman.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
        @if (session('simpeg_user.userlevel') === '1')
            <div style="display:flex; gap:8px;">
                <a href="{{ route('pengumuman.edit', $p['id']) }}" class="btn btn-primary">Edit Pengumuman</a>
            </div>
        @endif
    </div>
</div>
@endsection
