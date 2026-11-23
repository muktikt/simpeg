@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
@php
    $myRole = session('simpeg_user.userlevel');
    $badgeLabel = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai</div>
    <h1>Data Pegawai</h1>
</div>

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <form method="GET" action="{{ route('pegawai.index') }}" style="display:flex; align-items:center; gap:8px; flex:1; max-width:680px; flex-wrap:wrap;">
        <div class="search-box" style="position:relative; flex:1; min-width:200px; margin:0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94A3B8; width:16px; height:16px;"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari NIK, nama, jabatan, atau unit kerja..." style="padding-left:36px; height:38px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; width:100%;">
        </div>

        <!-- Pilihan Urutan Pegawai -->
        <select name="sort" class="form-control" style="height:38px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px; font-weight:600; width:auto; min-width:160px; background:#ffffff; color:#0F2A3D;" onchange="this.form.submit()">
            <option value="nama_asc" {{ ($sort ?? '') === 'nama_asc' ? 'selected' : '' }}>Nama Pegawai (A - Z)</option>
            <option value="nama_desc" {{ ($sort ?? '') === 'nama_desc' ? 'selected' : '' }}>Nama Pegawai (Z - A)</option>
            <option value="nik_asc" {{ ($sort ?? '') === 'nik_asc' ? 'selected' : '' }}>NIK (Terkecil - Terbesar)</option>
            <option value="nik_desc" {{ ($sort ?? '') === 'nik_desc' ? 'selected' : '' }}>NIK (Terbesar - Terkecil)</option>
            <option value="terbaru" {{ ($sort ?? '') === 'terbaru' ? 'selected' : '' }}>Pegawai Baru Terdaftar</option>
            <option value="terlama" {{ ($sort ?? '') === 'terlama' ? 'selected' : '' }}>Pegawai Lama Terdaftar</option>
        </select>

        <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px; font-size:13px; font-weight:600; border-radius:8px;">
            Cari
        </button>
        @if ($keyword !== '' || ($sort ?? 'nama_asc') !== 'nama_asc')
            <a href="{{ route('pegawai.index') }}" class="btn btn-outline" style="height:38px; padding:0 12px; font-size:13px; color:#DC2626; border-color:#FECACA; border-radius:8px; display:inline-flex; align-items:center;" title="Reset Filter">
                Reset
            </a>
        @endif
    </form>

    @if ($myRole === '1')
        <a href="{{ route('pegawai.create') }}" class="btn btn-primary" style="height:38px; display:inline-flex; align-items:center; gap:6px; border-radius:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Pegawai
        </a>
    @endif
</div>

@if ($keyword !== '')
    <div style="margin-top:10px; margin-bottom:12px; display:flex; align-items:center; gap:8px; font-size:13px; color:#475569;">
        <span>Hasil pencarian untuk "<strong>{{ $keyword }}</strong>"</span>
        <span style="background:#E0E7FF; color:#3730A3; padding:2px 8px; border-radius:12px; font-weight:600; font-size:12px;">{{ $paginator->total() }} pegawai ditemukan</span>
        <a href="{{ route('pegawai.index') }}" style="color:#DC2626; font-size:12px; text-decoration:underline; margin-left:4px;">Tampilkan semua pegawai</a>
    </div>
@endif

<style>
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

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Unit Kerja</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pegawai as $p)
                <tr>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>{{ $p['jabatan'] }}</td>
                    <td>{{ $p['unit_kerja'] }}</td>
                    <td><span class="badge badge-{{ $p['status_peg'] }}">{{ $badgeLabel[$p['status_peg']] ?? $p['status_peg'] }}</span></td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('pegawai.show', $p['id']) }}" class="btn btn-outline btn-sm">Lihat</a>
                            @if ($myRole === '1')
                                <a href="{{ route('pegawai.edit', $p['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">
                            @if ($keyword !== '')
                                Tidak ada pegawai yang cocok dengan pencarian "{{ $keyword }}".
                            @else
                                Belum ada data pegawai.
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination Navigasi (20 Per Halaman) -->
@if (isset($paginator) && $paginator->hasPages())
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:18px; padding:12px 18px; background:#ffffff; border:1px solid #E2E8F0; border-radius:12px; box-shadow:0 2px 6px rgba(15, 42, 61, 0.02);">
        <div style="font-size:13px; color:#64748B;">
            @if ($keyword !== '')
                Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong> - <strong>{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong>{{ $paginator->total() }}</strong> hasil pencarian
            @else
                Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong> - <strong>{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong>{{ $paginator->total() }}</strong> pegawai
            @endif
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
@endsection
