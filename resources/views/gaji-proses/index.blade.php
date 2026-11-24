@extends('layouts.app')

@section('title', $pageTitle ?? 'Proses Gaji Bulanan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / {{ $pageTitle ?? 'Proses Gaji Bulanan' }}</div>
    <h1>{{ $pageTitle ?? 'Proses Gaji Bulanan' }}</h1>
</div>

@if (($kategori === 'satuan' || empty($kategori)) && !empty($auditCounts))
    <!-- INFORMASI DATA BELUM DI ENTRI (PERSIS SIMPEG LAMA) -->
    <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:12px;">
        <div class="stat-card" style="border-top:3px solid #EF4444; background:#FFFFFF;">
            <div class="label" style="font-size:11px; font-weight:700; color:#DC2626;">PRESTASI BELUM MASUK</div>
            <div class="value" style="font-size:22px; font-weight:800; color:#DC2626; font-family:'IBM Plex Mono',monospace;">
                {{ $auditCounts['prestasi'] }} <small style="font-size:12px; font-weight:500; color:#64748B;">Pegawai</small>
            </div>
        </div>

        <div class="stat-card" style="border-top:3px solid #F59E0B; background:#FFFFFF;">
            <div class="label" style="font-size:11px; font-weight:700; color:#D97706;">ABSENSI BELUM MASUK</div>
            <div class="value" style="font-size:22px; font-weight:800; color:#D97706; font-family:'IBM Plex Mono',monospace;">
                {{ $auditCounts['absensi'] }} <small style="font-size:12px; font-weight:500; color:#64748B;">Pegawai</small>
            </div>
        </div>

        <div class="stat-card" style="border-top:3px solid #10B981; background:#FFFFFF;">
            <div class="label" style="font-size:11px; font-weight:700; color:#059669;">KEUANGAN BELUM MASUK</div>
            <div class="value" style="font-size:22px; font-weight:800; color:#059669; font-family:'IBM Plex Mono',monospace;">
                {{ $auditCounts['potongan'] }} <small style="font-size:12px; font-weight:500; color:#64748B;">Pegawai</small>
            </div>
        </div>
    </div>

    @if ($auditCounts['prestasi'] > 0 || $auditCounts['absensi'] > 0 || $auditCounts['potongan'] > 0)
        <div style="background:#FFFBEB; border-left:4px solid #F59E0B; padding:10px 14px; border-radius:6px; margin-bottom:16px; font-size:12px; color:#92400E;">
            <strong>Perhatian:</strong> Data di atas adalah data yang belum di-entri. Jika data di atas nilainya = 0 (nol) artinya sudah di-entri semua dan siap proses cek gaji. Jika belum, jangan di-cek dulu!
        </div>
    @endif
@endif

<div class="toolbar">
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <form method="GET" action="{{ route('gaji-proses.index') }}" style="display:flex; gap:10px;">
            @if (!empty($kategori))
                <input type="hidden" name="kategori" value="{{ $kategori }}">
            @endif
            @if (!empty($status))
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                @foreach ($bulanList as $val => $label)
                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                @endfor
            </select>
            @if(!empty($kategori))
                <input type="hidden" name="kategori" value="{{ $kategori }}">
            @endif
            <select name="status" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                <option value="">Semua Status</option>
                <option value="draft" @selected($status === 'draft')>Draft Kepegawaian</option>
                <option value="dirum" @selected($status === 'dirum')>Persetujuan Dirum</option>
                <option value="dirut" @selected($status === 'dirut')>Persetujuan Dirut</option>
                <option value="terbit" @selected($status === 'terbit')>Terbit (Final)</option>
                <option value="minus" @selected($status === 'minus')>Gaji Minus</option>
            </select>
            @php
                $hasCustomGajiProses = (
                    $bulan !== (int) now()->month ||
                    $tahun !== (int) now()->year ||
                    !empty($status)
                );
                $resetGajiUrl = route('gaji-proses.index', !empty($kategori) ? ['kategori' => $kategori] : []);
            @endphp
            @if ($hasCustomGajiProses)
                <a href="{{ $resetGajiUrl }}" class="btn btn-outline btn-sm" style="height:36px; display:inline-flex; align-items:center; color:#DC2626; border-color:#FECACA; padding:0 10px;" title="Reset Filter">Reset</a>
            @endif
        </form>
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" class="table-search-input" placeholder="Cari NIK, nama pegawai, status...">
        </div>
    </div>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        @if ($gaji->contains('bisa_approve', true))
            @php
                $userNik = (string) session('simpeg_user.nik', '');
                $isDirut = ($userNik === (string) config('simpeg_approval.dirut', '1711001'));
                $btnMassText = $isDirut ? 'TERBITKAN FINAL SEMUA' : 'SETUJUI SEMUA (KE ' . ($userNik === (string) config('simpeg_approval.dirum', '1711002') ? 'DIRUT' : 'DIRUM') . ')';
            @endphp
            <form action="{{ route('gaji-proses.terbitkan-semua') }}" method="POST" onsubmit="return confirmSubmit(event, 'Apakah Anda yakin ingin menyetujui semua gaji periode ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'info', 'Ya, Setujui Semua');" style="margin:0;">
                @csrf
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <button type="submit" class="btn btn-success" style="padding:9px 18px; font-weight:700; font-size:13px; box-shadow:0 4px 12px rgba(39, 174, 96, 0.25);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px;"><path d="M20 6L9 17l-5-5"/></svg>
                    {{ $btnMassText }}
                </button>
            </form>
        @endif

        @if ($status !== 'terbit')
            <a href="{{ route('gaji-proses.create', !empty($kategori) ? ['kategori' => $kategori] : []) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                {{ !empty($kategori) && isset(\App\Http\Controllers\GajiProsesController::KATEGORI[$kategori]) ? 'Proses ' . \App\Http\Controllers\GajiProsesController::KATEGORI[$kategori] : 'Proses Gaji Pegawai' }}
            </a>
        @endif
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Total Pendapatan</th>
                <th>Total Potongan</th>
                <th>Gaji Bersih</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gaji as $g)
                <tr>
                    <td class="cell-nik">{{ $g['nik'] }}</td>
                    <td class="cell-name">{{ $g['nama'] }}</td>
                    <td>{{ \App\Http\Controllers\GajiProsesController::KATEGORI[$g['kategori']] ?? $g['kategori'] }}</td>
                    <td>Rp {{ number_format($g['total_pendapatan'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($g['total_potongan'], 0, ',', '.') }}</td>
                    <td>
                        @if ($g['gaji_bersih'] < 0)
                            <strong style="color: #dc2626;">Rp {{ number_format($g['gaji_bersih'], 0, ',', '.') }}</strong>
                            <span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-size:10px; margin-left:4px;">MINUS</span>
                        @else
                            <strong>Rp {{ number_format($g['gaji_bersih'], 0, ',', '.') }}</strong>
                        @endif
                    </td>
                    <td>
                        @if ($g['status'] === 'terbit')
                            <span class="badge badge-PT">Terbit (Final)</span>
                        @else
                            <span class="badge badge-PH">{{ \App\Http\Controllers\GajiProsesController::approvalStatusLabel($g['status']) }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('gaji-proses.show', $g['id']) }}" class="btn btn-outline btn-sm">Lihat</a>
                            @if ($g['status'] !== 'terbit')
                                @if ($g['bisa_approve'])
                                    <form action="{{ route('gaji-proses.terbitkan', $g['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Setujui gaji ini ke tahap berikutnya?', 'Konfirmasi Persetujuan', 'warning', 'Ya, Setujui');" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Setujui</button>
                                    </form>
                                @endif
                                <form action="{{ route('gaji-proses.destroy', $g['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus draft ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="table-empty">Belum ada proses gaji untuk {{ $bulanList[$bulan] }} {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
