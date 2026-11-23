@extends('layouts.app')

@section('title', 'Rekonsiliasi & Audit Pra-Payroll')

@section('content')
<div class="page-head no-print">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / Audit Pra-Payroll</div>
    <h1>Rekonsiliasi & Audit Pra-Payroll</h1>
</div>

<div class="toolbar no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
    <form method="GET" action="{{ route('gaji-proses.audit') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        
        <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:600;">
            @foreach ($bulanList as $val => $label)
                <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; font-weight:600;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <div style="display:flex; gap:8px;">
        <a href="{{ route('gaji-proses.index') }}" class="btn btn-outline" style="font-weight:600;">
            Kembali ke Proses Gaji
        </a>
    </div>
</div>

<!-- INFO BOXES (PERSIS SIMPEG LAMA) -->
<div class="stat-grid no-print" style="grid-template-columns: repeat(5, 1fr); gap:12px; margin-bottom:16px;">
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'prestasi']) }}" style="text-decoration:none;">
        <div class="stat-card" style="border-top:3px solid #EF4444; background: {{ $tab === 'prestasi' ? '#FEF2F2' : '#FFFFFF' }};">
            <div class="label" style="font-size:11px; font-weight:700; color:#DC2626;">PRESTASI BELUM MASUK</div>
            <div class="value" style="font-size:24px; font-weight:800; color:#DC2626; font-family:'IBM Plex Mono',monospace;">
                {{ $counts['prestasi'] }}
            </div>
            <div style="font-size:11px; color:#991B1B; margin-top:2px;">Pegawai</div>
        </div>
    </a>

    <a href="{{ request()->fullUrlWithQuery(['tab' => 'absensi']) }}" style="text-decoration:none;">
        <div class="stat-card" style="border-top:3px solid #F59E0B; background: {{ $tab === 'absensi' ? '#FFFBEB' : '#FFFFFF' }};">
            <div class="label" style="font-size:11px; font-weight:700; color:#D97706;">ABSENSI BELUM MASUK</div>
            <div class="value" style="font-size:24px; font-weight:800; color:#D97706; font-family:'IBM Plex Mono',monospace;">
                {{ $counts['absensi'] }}
            </div>
            <div style="font-size:11px; color:#92400E; margin-top:2px;">Pegawai</div>
        </div>
    </a>

    <a href="{{ request()->fullUrlWithQuery(['tab' => 'potongan']) }}" style="text-decoration:none;">
        <div class="stat-card" style="border-top:3px solid #10B981; background: {{ $tab === 'potongan' ? '#ECFDF5' : '#FFFFFF' }};">
            <div class="label" style="font-size:11px; font-weight:700; color:#059669;">KEUANGAN BELUM MASUK</div>
            <div class="value" style="font-size:24px; font-weight:800; color:#059669; font-family:'IBM Plex Mono',monospace;">
                {{ $counts['potongan'] }}
            </div>
            <div style="font-size:11px; color:#065F46; margin-top:2px;">Pegawai</div>
        </div>
    </a>

    <a href="{{ request()->fullUrlWithQuery(['tab' => 'rekening']) }}" style="text-decoration:none;">
        <div class="stat-card" style="border-top:3px solid #6366F1; background: {{ $tab === 'rekening' ? '#EEF2FF' : '#FFFFFF' }};">
            <div class="label" style="font-size:11px; font-weight:700; color:#4F46E5;">REKENING BELUM DIISI</div>
            <div class="value" style="font-size:24px; font-weight:800; color:#4F46E5; font-family:'IBM Plex Mono',monospace;">
                {{ $counts['rekening'] }}
            </div>
            <div style="font-size:11px; color:#3730A3; margin-top:2px;">Pegawai</div>
        </div>
    </a>

    <a href="{{ request()->fullUrlWithQuery(['tab' => 'minus']) }}" style="text-decoration:none;">
        <div class="stat-card" style="border-top:3px solid #B91C1C; background: {{ $tab === 'minus' ? '#FEE2E2' : '#FFFFFF' }};">
            <div class="label" style="font-size:11px; font-weight:700; color:#991B1B;">GAJI MINUS (< 0)</div>
            <div class="value" style="font-size:24px; font-weight:800; color:#991B1B; font-family:'IBM Plex Mono',monospace;">
                {{ $counts['minus'] }}
            </div>
            <div style="font-size:11px; color:#7F1D1D; margin-top:2px;">Data Payroll</div>
        </div>
    </a>
</div>

<!-- WARNING NOTICE PERSIS SIMPEG LAMA -->
<div class="no-print" style="background:#FFFBEB; border-left:4px solid #F59E0B; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px; color:#92400E; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
    <div>
        <strong>Perhatian Penting:</strong> Data di atas adalah data pra-payroll yang belum dientri. Jika nilai indikator di atas bernilai <strong>0 (nol)</strong>, artinya seluruh data telah lengkap dan sistem siap memproses cek gaji bulanan. Jika masih ada data yang belum dientri, mohon lengkapi terlebih dahulu untuk mencegah kekeliruan slip gaji pegawai.
    </div>
</div>

<!-- TAB SWITCHER -->
<div class="no-print" style="display:flex; gap:8px; margin-bottom:16px; background:#F8FAFC; padding:6px; border-radius:10px; border:1px solid var(--border); width:fit-content; flex-wrap:wrap;">
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'absensi']) }}" 
       class="btn btn-sm {{ $tab === 'absensi' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $tab !== 'absensi' ? 'background:transparent; color:#475569;' : '' }}">
        Absensi Belum Masuk ({{ $counts['absensi'] }})
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'potongan']) }}" 
       class="btn btn-sm {{ $tab === 'potongan' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $tab !== 'potongan' ? 'background:transparent; color:#475569;' : '' }}">
        Potongan Keu Belum Masuk ({{ $counts['potongan'] }})
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'prestasi']) }}" 
       class="btn btn-sm {{ $tab === 'prestasi' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $tab !== 'prestasi' ? 'background:transparent; color:#475569;' : '' }}">
        Prestasi Belum Masuk ({{ $counts['prestasi'] }})
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'rekening']) }}" 
       class="btn btn-sm {{ $tab === 'rekening' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $tab !== 'rekening' ? 'background:transparent; color:#475569;' : '' }}">
        Rekening Belum Masuk ({{ $counts['rekening'] }})
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'minus']) }}" 
       class="btn btn-sm {{ $tab === 'minus' ? 'btn-primary' : 'btn-outline' }}" 
       style="border-radius:8px; font-weight:600; border:none; {{ $tab !== 'minus' ? 'background:transparent; color:#475569;' : '' }}">
        Gaji Minus ({{ $counts['minus'] }})
    </a>
</div>

<!-- TABEL DATA AUDIT -->
<div class="table-card">
    @if ($tab === 'minus')
        <table class="data-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th>Jabatan & Unit Kerja</th>
                    <th style="text-align:right;">Total Pendapatan</th>
                    <th style="text-align:right;">Total Potongan</th>
                    <th style="text-align:right; color:#DC2626; font-weight:700;">Gaji Bersih (Minus)</th>
                    <th style="text-align:center; width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditData['gajiMinus'] as $idx => $gm)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td class="cell-nik">{{ $gm['nik'] }}</td>
                        <td class="cell-name">{{ $gm['nama'] }}</td>
                        <td>
                            <div>{{ $gm['jabatan'] }}</div>
                            <div style="font-size:11px; color:#64748B;">{{ $gm['unit_kerja'] }}</div>
                        </td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($gm['total_pendapatan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-family:'IBM Plex Mono',monospace;">Rp {{ number_format($gm['total_potongan'], 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:800; color:#DC2626; font-family:'IBM Plex Mono',monospace;">
                            Rp {{ number_format($gm['gaji_bersih'], 0, ',', '.') }}
                            <span class="badge" style="background:#FEE2E2; color:#B91C1C; font-size:10px; margin-left:4px;">MINUS</span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('gaji-proses.show', $gm['id']) }}" class="btn btn-outline btn-sm" style="font-size:11px; font-weight:600;">Review Slip</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; padding:30px;"><div class="table-empty">Semua gaji pada periode ini bernilai positif. Tidak ada gaji minus.</div></td></tr>
                @endforelse
            </tbody>
        </table>

    @else
        @php
            $list = match($tab) {
                'potongan' => $auditData['belumPotongan'],
                'prestasi' => $auditData['belumPrestasi'],
                'rekening' => $auditData['belumRekening'],
                default => $auditData['belumAbsensi'],
            };
            $actionRoute = match($tab) {
                'potongan' => route('potongan-keu.index', ['tipe' => 'gaji', 'bulan' => $bulan, 'tahun' => $tahun]),
                'prestasi' => route('prestasi.index'),
                'rekening' => route('pegawai.index'),
                default => route('absensi.index', ['bulan' => $bulan, 'tahun' => $tahun]),
            };
            $actionLabel = match($tab) {
                'potongan' => 'Input Potongan Keu',
                'prestasi' => 'Input Prestasi SDM',
                'rekening' => 'Lengkapi Data Pegawai',
                default => 'Input Absensi SDM',
            };
        @endphp

        <table class="data-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th>Jabatan</th>
                    <th>Unit Kerja</th>
                    <th style="text-align:center; width:160px;">Status Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $p)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td class="cell-nik">{{ $p['nik'] }}</td>
                        <td class="cell-name">{{ $p['nama'] }}</td>
                        <td>{{ $p['jabatan'] }}</td>
                        <td>{{ $p['unit_kerja'] }}</td>
                        <td style="text-align:center;">
                            <a href="{{ $actionRoute }}" class="btn btn-outline btn-sm" style="font-size:11px; font-weight:600;">
                                {{ $actionLabel }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; padding:30px;"><div class="table-empty">Luar biasa! Semua data {{ ucfirst($tab) }} untuk periode ini sudah lengkap di-entri.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>
@endsection
