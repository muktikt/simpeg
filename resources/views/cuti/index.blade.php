@extends('layouts.app')

@section('title', 'Laporan & Pengajuan Cuti Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Kepegawaian / Lap. Cuti Pegawai</div>
    <h1>Laporan & Pengajuan Cuti Pegawai</h1>
</div>

@if (session('success'))
    <div class="alert alert-success" style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
        ✓ {{ session('success') }}
    </div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('cuti.index') }}" style="display:flex; gap:10px;">
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

<div class="table-card">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <h2 style="font-size:16px; margin:0;">Data & Pengajuan Cuti Tahun {{ $tahun }}</h2>
        <span style="font-size:12px; color:var(--text-muted);">Total: {{ count($cuti) }} Catatan</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK & Nama</th>
                <th>Unit Kerja</th>
                <th>Jenis Cuti</th>
                <th>Periode / Tanggal</th>
                <th>Alasan / Keterangan</th>
                <th>Status</th>
                @if (session('simpeg_user.userlevel') === '1')
                    <th>Aksi SDM</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($cuti as $c)
                <tr>
                    <td>
                        <strong class="cell-name">{{ $c['nama'] }}</strong><br>
                        <span class="cell-nik" style="font-size:12px; color:var(--text-muted);">NIK: {{ $c['nik'] }}</span>
                    </td>
                    <td>{{ $c['unit_kerja'] }}</td>
                    <td><span style="font-weight:600; color:#1e3a8a;">{{ $c['jenis'] }}</span></td>
                    <td style="white-space:nowrap; font-size:13px;">
                        {{ \Illuminate\Support\Carbon::parse($c['tanggal_mulai'])->format('d M Y') }} s/d {{ \Illuminate\Support\Carbon::parse($c['tanggal_selesai'])->format('d M Y') }}
                    </td>
                    <td style="font-size:13px; max-width:220px;">{{ $c['alasan'] ?: '-' }}</td>
                    <td>
                        @php
                            $badgeClass = match($c['status']) {
                                'DISETUJUI', 'Setujui' => 'badge-PT',
                                'PENDING', 'Pending' => 'badge-CP',
                                'DITOLAK', 'Ditolak' => 'badge-PN',
                                default => 'badge-DI',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $c['status'] }}</span>
                    </td>
                    @if (session('simpeg_user.userlevel') === '1')
                        <td>
                            @if (is_numeric($c['id']))
                                <form method="POST" action="{{ route('cuti.update-status', $c['id']) }}" style="display:flex; gap:6px;">
                                    @csrf
                                    @method('PUT')
                                    @if ($c['status'] === 'PENDING')
                                        <button type="submit" name="status" value="DISETUJUI" style="background:#16a34a; color:#fff; border:none; border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;">Setujui</button>
                                        <button type="submit" name="status" value="DITOLAK" style="background:#dc2626; color:#fff; border:none; border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;">Tolak</button>
                                    @else
                                        <select name="status" onchange="this.form.submit()" style="padding:4px 6px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;">
                                            <option value="DISETUJUI" {{ $c['status'] === 'DISETUJUI' ? 'selected' : '' }}>Disetujui</option>
                                            <option value="DITOLAK" {{ $c['status'] === 'DITOLAK' ? 'selected' : '' }}>Ditolak</option>
                                            <option value="PENDING" {{ $c['status'] === 'PENDING' ? 'selected' : '' }}>Pending</option>
                                        </select>
                                    @endif
                                </form>
                            @else
                                <span style="font-size:12px; color:var(--text-muted);">-</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ session('simpeg_user.userlevel') === '1' ? '7' : '6' }}">
                        <div class="table-empty">Belum ada data cuti untuk tahun {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
