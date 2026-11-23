@extends('layouts.app')

@section('title', 'SET ' . $tipeLabel)

@section('content')
<style>
.pk-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}
.pk-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 4px 0;
}
.pk-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
}
.pk-btn-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.pk-btn-excel {
    background: #059669;
    color: #ffffff;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
    transition: all 0.2s;
}
.pk-btn-excel:hover {
    background: #047857;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
    transform: translateY(-1px);
}
.pk-btn-template {
    background: #ffffff;
    border: 1px solid #CBD5E1;
    color: #334155;
    padding: 9px 15px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
}
.pk-btn-template:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

/* Modal Import Styling */
.pk-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 42, 61, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 999999 !important;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.pk-modal-box {
    background: #ffffff;
    width: 100%;
    max-width: 520px;
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
    animation: modalIn 0.2s ease-out;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.pk-modal-header {
    padding: 18px 24px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pk-modal-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 17px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0;
}
.pk-modal-body {
    padding: 22px 24px;
}
.pk-dropzone {
    border: 2px dashed #CBD5E1;
    background: #F8FAFC;
    border-radius: 14px;
    padding: 24px 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.pk-dropzone:hover {
    border-color: #059669;
    background: #ECFDF5;
}

/* Pagination Buttons */
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
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / SET {{ $tipeLabel }}</div>
    <div class="pk-header">
        <div>
            <h1 class="pk-title">SET {{ strtoupper($tipeLabel) }}</h1>
            <p class="pk-subtitle">Kelola nominal potongan pegawai bulanan secara manual atau impor otomatis dari berkas Excel/CSV.</p>
        </div>
        <div class="pk-btn-group">
            <div style="display:inline-flex; border:1px solid #CBD5E1; border-radius:10px; overflow:hidden; background:#ffffff;">
                <a href="{{ route('potongan-keu.template', [$tipe, 'sample' => 1]) }}" class="pk-btn-template" style="border:none; border-right:1px solid #E2E8F0;" title="Download Template CSV 1 Baris Contoh (Khusus 1 Pegawai)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Template 1 Pegawai
                </a>
                <a href="{{ route('potongan-keu.template', $tipe) }}" class="pk-btn-template" style="border:none;" title="Download Template CSV Berisi Seluruh Pegawai">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Template Semua Pegawai
                </a>
            </div>
            <button type="button" class="pk-btn-excel" onclick="openImportModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                Import File Excel / CSV
            </button>
        </div>
    </div>
</div>

@php
    $bulanNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
@endphp

@if ($belumMasuk > 0)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        Ada pegawai yang belum dibuat {{ strtolower($tipeLabel) }} pada periode {{ $bulan === 'all' ? 'semua bulan' : ($bulanNames[(int)$bulan] ?? 'Bulan ' . $bulan) . ' ' . $tahun }} sebanyak <strong>{{ $belumMasuk }}</strong> Pegawai.
        <a href="{{ route('potongan-keu.belum-masuk', [$tipe, 'bulan' => $bulan, 'tahun' => $tahun]) }}" style="font-weight:600; text-decoration:underline; margin-left:6px;">Lihat daftar pegawai belum masuk</a>
    </div>
@endif

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
    <form method="GET" action="{{ route('potongan-keu.index', $tipe) }}" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; flex:1; max-width:860px;">
        <select name="bulan" class="form-control" style="height:40px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:auto; min-width:130px; background:#ffffff; color:#0F2A3D;" onchange="this.form.submit()">
            <option value="all" {{ (string)$bulan === 'all' ? 'selected' : '' }}>Semua Bulan</option>
            @foreach ($bulanNames as $bNum => $bNama)
                <option value="{{ $bNum }}" {{ (string)$bulan === (string)$bNum ? 'selected' : '' }}>{{ $bNama }}</option>
            @endforeach
        </select>

        <select name="tahun" class="form-control" style="height:40px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:auto; min-width:90px; background:#ffffff; color:#0F2A3D;" onchange="this.form.submit()">
            @for ($y = now()->year + 1; $y >= 2024; $y--)
                <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>

        <!-- Pilihan Urutan (Sort Ascending / Descending) -->
        <select name="sort" class="form-control" style="height:40px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:auto; min-width:160px; background:#ffffff; color:#0F2A3D;" onchange="this.form.submit()">
            <option value="terlama" {{ ($sort ?? '') === 'terlama' ? 'selected' : '' }}>Urutan File (Awal - Akhir)</option>
            <option value="terbaru" {{ ($sort ?? '') === 'terbaru' ? 'selected' : '' }}>Entry Terbaru</option>
            <option value="nik_asc" {{ ($sort ?? '') === 'nik_asc' ? 'selected' : '' }}>NIK (Terkecil - Terbesar)</option>
            <option value="nik_desc" {{ ($sort ?? '') === 'nik_desc' ? 'selected' : '' }}>NIK (Terbesar - Terkecil)</option>
            <option value="nama_asc" {{ ($sort ?? '') === 'nama_asc' ? 'selected' : '' }}>Nama Pegawai (A - Z)</option>
            <option value="nama_desc" {{ ($sort ?? '') === 'nama_desc' ? 'selected' : '' }}>Nama Pegawai (Z - A)</option>
        </select>

        <div style="position:relative; flex:1; min-width:180px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748B;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari NIK, nama pegawai, jabatan..." class="form-control table-search-input" style="padding-left:36px; height:40px; border-radius:8px; width:100%; border:1px solid #CBD5E1; font-size:13px;">
        </div>
        <button type="submit" class="btn btn-outline" style="height:40px; padding:0 16px; font-weight:600; font-size:13px;">Cari</button>
        @if ($keyword !== '' || (string)$bulan === 'all' || (int)$bulan !== (int)now()->month || (int)$tahun !== (int)now()->year || ($sort ?? 'terbaru') !== 'terbaru')
            <a href="{{ route('potongan-keu.index', $tipe) }}" class="btn btn-outline" style="height:40px; padding:0 12px; color:#DC2626; border-color:#FECACA; font-size:13px; display:inline-flex; align-items:center;" title="Reset Filter">Reset</a>
        @endif
    </form>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <a href="{{ route('potongan-keu.create', $tipe) }}" class="btn btn-primary" style="padding:9px 16px; font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
            Input Manual 1 Pegawai
        </a>

        <a href="{{ route('approval.index', ['jenis' => 'potongan_' . $tipe]) }}" class="btn btn-outline" style="font-weight:600; padding:9px 16px; font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px; color:#2E86AB;"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Persetujuan & Approval ({{ strtoupper($tipe) }})
        </a>
    </div>
</div>

<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Entry</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="text-align:right;">Total Potongan</th>
                    <th>Status Persetujuan</th>
                    <th style="width:1%; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ isset($paginator) ? (($paginator->currentPage() - 1) * $paginator->perPage() + $loop->iteration) : $loop->iteration }}</td>
                        <td>{{ formatTglIndo($item['tgl_potongan'], 'd/m/Y') }}</td>
                        <td class="cell-nik">{{ $item['nik'] }}</td>
                        <td class="cell-name">{{ $item['nama'] }}</td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($item[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; font-weight:700; color:#0D2C6E;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td>
                            @if ($item['status'] === 'Y' || $item['status'] === 'terbit')
                                <span class="badge badge-success" style="background:#ECFDF5; color:#047857; border:1px solid #A7F3D0; font-weight:700;">
                                    Diterbitkan Final
                                </span>
                            @elseif ($item['status'] === 'kepegawaian')
                                <span class="badge badge-info" style="background:#E0F2FE; color:#0369A1; border:1px solid #BAE6FD; font-weight:700;">
                                    Disetujui Kepegawaian
                                </span>
                            @else
                                <span class="badge badge-warning" style="background:#FEF3C7; color:#B45309; border:1px solid #FDE68A; font-weight:700;">
                                    Menunggu Kepegawaian
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions" style="justify-content:center;">
                                <a href="{{ route('potongan-keu.edit', [$tipe, $item['id']]) }}" class="btn btn-outline btn-sm" title="Edit Potongan">Edit</a>
                                <form action="{{ route('potongan-keu.destroy', [$tipe, $item['id']]) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus potongan pegawai ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 7 }}">
                            <div class="table-empty" style="padding:40px 20px; text-align:center;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="44" height="44" style="color:#94A3B8; margin-bottom:8px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                @if ($keyword !== '')
                                    <div style="font-weight:700; color:#334155; font-size:15px; margin-bottom:4px;">Tidak ditemukan data potongan untuk pencarian "{{ $keyword }}".</div>
                                    <div style="font-size:13px; color:#64748B; margin-bottom:14px;">Coba gunakan kata kunci NIK atau nama pegawai yang lain.</div>
                                    <a href="{{ route('potongan-keu.index', $tipe) }}" class="btn btn-outline" style="font-size:13px;">Tampilkan Semua Potongan</a>
                                @else
                                    <div style="font-weight:700; color:#334155; font-size:15px; margin-bottom:4px;">Belum ada data {{ strtolower($tipeLabel) }} periode {{ $bulan === 'all' ? 'ini' : ($bulanNames[(int)$bulan] ?? 'Bulan ' . $bulan) . ' ' . $tahun }}.</div>
                                    <div style="font-size:13px; color:#64748B; margin-bottom:16px;">Anda dapat menyalin data potongan dari bulan sebelumnya, atau mengimpor berkas Excel baru.</div>
                                    <div style="display:flex; gap:10px; justify-content:center; align-items:center; flex-wrap:wrap;">
                                        <form action="{{ route('potongan-keu.salin-bulan-lalu', $tipe) }}" method="POST" style="margin:0;" onsubmit="return confirmSubmit(event, 'Salin data potongan dari riwayat bulan sebelumnya ke periode {{ $bulan === 'all' ? 'ini' : ($bulanNames[(int)$bulan] ?? 'Bulan ' . $bulan) . ' ' . $tahun }}?', 'Konfirmasi Salin Potongan', 'info', 'Ya, Salin');">
                                            @csrf
                                            <input type="hidden" name="target_bulan" value="{{ $bulan === 'all' ? now()->month : $bulan }}">
                                            <input type="hidden" name="target_tahun" value="{{ $tahun }}">
                                            <button type="submit" class="btn btn-outline" style="background:#ffffff; border-color:#0D2C6E; color:#0D2C6E; font-weight:700; padding:9px 18px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                Salin dari Bulan Lalu
                                            </button>
                                        </form>
                                        <button type="button" class="pk-btn-excel" onclick="openImportModal()">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                            Unggah File Excel / CSV
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination Navigasi (20 Per Halaman) -->
@if (isset($paginator) && $paginator->hasPages())
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:18px; padding:12px 18px; background:#ffffff; border:1px solid #E2E8F0; border-radius:12px; box-shadow:0 2px 6px rgba(15, 42, 61, 0.02);">
        <div style="font-size:13px; color:#64748B;">
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong> - <strong>{{ $paginator->lastItem() }}</strong> dari <strong>{{ $paginator->total() }}</strong> data
            <span style="margin:0 4px; color:#CBD5E1;">&bull;</span>
            Halaman <strong>{{ $paginator->currentPage() }}</strong> dari <strong>{{ $paginator->lastPage() }}</strong>
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

<!-- Modal Import Excel / CSV Potongan -->
<div id="pk-modal-import" class="pk-modal-overlay">
    <div class="pk-modal-box">
        <div class="pk-modal-header">
            <h3 class="pk-modal-title">Import File Excel / CSV {{ $tipeLabel }}</h3>
            <button type="button" onclick="closeImportModal()" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:#64748B; padding:0 4px;" title="Tutup">&times;</button>
        </div>
        <form method="POST" action="{{ route('potongan-keu.import', $tipe) }}" enctype="multipart/form-data" class="pk-modal-body">
            @csrf
            <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; padding:12px 14px; margin-bottom:16px; font-size:12.5px; color:#166534;">
                <div style="font-weight:700; margin-bottom:4px;">💡 Petunjuk Format Berkas:</div>
                <div>File CSV/Excel dapat berisi <strong>1 pegawai saja</strong> atau <strong>banyak pegawai sekaligus</strong>. Berkas yang diimpor akan tersimpan sebagai <strong>DRAFT</strong> dan menunggu persetujuan (approval) sebelum diterbitkan final.</div>
                <div style="margin-top:8px; display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="{{ route('potongan-keu.template', [$tipe, 'sample' => 1]) }}" style="color:#059669; font-weight:700; text-decoration:underline;">
                        📥 Template 1 Pegawai (Contoh)
                    </a>
                    <a href="{{ route('potongan-keu.template', $tipe) }}" style="color:#059669; font-weight:700; text-decoration:underline;">
                        📥 Template Semua Pegawai (Massal)
                    </a>
                </div>
            </div>

            <label for="import-file-input" class="pk-dropzone" style="display:block; cursor:pointer;">
                <div style="width:52px; height:52px; border-radius:12px; background:#DCFCE7; color:#16A34A; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <div style="font-weight:700; color:#0F2A3D; font-size:14.5px;" id="dropzone-label">Pilih Berkas Excel / CSV</div>
                <div style="font-size:12px; color:#64748B; margin-top:4px;">Klik untuk telusuri berkas dari komputer (.csv, .xlsx, .xls)</div>
                <div style="margin-top:12px;">
                    <span class="btn btn-outline btn-sm" style="pointer-events:none; font-size:12px; font-weight:600; padding:6px 14px;">
                        Pilih File dari Komputer
                    </span>
                </div>
            </label>
            <input type="file" name="file_excel" id="import-file-input" accept=".csv,.xlsx,.xls,.txt,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;" onchange="handleImportFile(this)">

            <div style="margin-top:16px; display:flex; gap:12px;">
                <div style="flex:1;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">Target Periode Bulan:</label>
                    <select name="bulan" class="form-control" style="height:38px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:100%; background:#ffffff;">
                        @foreach ($bulanNames as $bNum => $bNama)
                            <option value="{{ $bNum }}" {{ (string)($bulan === 'all' ? now()->month : $bulan) === (string)$bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="width:120px;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">Tahun:</label>
                    <select name="tahun" class="form-control" style="height:38px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:100%; background:#ffffff;">
                        @for ($y = now()->year + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div style="margin-top:14px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Opsi Data Duplikat:</label>
                <div style="display:flex; gap:16px; font-size:13px; color:#334155;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="mode" value="update" checked>
                        <span>Perbarui nilai jika NIK sudah ada</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="mode" value="skip">
                        <span>Lewati jika NIK sudah ada</span>
                    </label>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px; padding-top:16px; border-top:1px solid #E2E8F0;">
                <button type="button" class="btn btn-outline" style="padding:9px 16px;" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="pk-btn-excel" style="padding:9px 20px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Impor Data Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.openImportModal = function() {
    var modal = document.getElementById('pk-modal-import');
    if (modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        modal.style.display = 'flex';
    }
};

window.closeImportModal = function() {
    var modal = document.getElementById('pk-modal-import');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.handleImportFile = function(input) {
    if (input.files && input.files[0]) {
        var f = input.files[0];
        var sizeKb = (f.size / 1024).toFixed(1);
        var label = document.getElementById('dropzone-label');
        if (label) {
            label.innerHTML = 'File Terpilih: <span style="color:#059669; font-weight:700;">' + f.name + '</span> (' + sizeKb + ' KB)';
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('pk-modal-import');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeImportModal();
        });
    }
});
</script>
@endsection
