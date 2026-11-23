@extends('layouts.app')

@section('title', 'SET Absensi')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / SET Absensi</div>
    <h1>SET Absensi</h1>
</div>

<div class="toolbar">
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <form method="GET" action="{{ route('absensi.index') }}" style="display:flex; gap:10px;">
            <select name="bulan" class="form-group" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                @foreach ($bulanList as $val => $label)
                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                @endfor
            </select>
        </form>
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" class="table-search-input" placeholder="Cari NIK, nama, unit kerja...">
        </div>
    </div>

    @if ($myRole === '1')
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <a href="{{ route('absensi.template', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-outline" style="border-color:#10B981; color:#059669; font-weight:600;" title="Unduh template Excel dengan daftar pegawai aktif">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Unduh Template
            </a>
            <button type="button" class="btn" style="background:#059669; color:#fff; font-weight:600;" onclick="openImportModal('modal-import-absensi')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Unggah Excel
            </button>
            <a href="{{ route('absensi.create', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Input Absensi
            </a>
        </div>
    @endif
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Unit Kerja</th>
                <th>Hari Kerja</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpha</th>
                @if ($myRole === '1')
                    <th style="width:1%"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($absensi as $a)
                <tr>
                    <td class="cell-nik">{{ $a['nik'] }}</td>
                    <td class="cell-name">{{ $a['nama'] }}</td>
                    <td>{{ $a['unit_kerja'] }}</td>
                    <td>{{ $a['hari_kerja'] }}</td>
                    <td>{{ $a['hadir'] }}</td>
                    <td>{{ $a['sakit'] }}</td>
                    <td>{{ $a['izin'] }}</td>
                    <td>{{ $a['alpha'] }}</td>
                    @if ($myRole === '1')
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('absensi.edit', $a['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('absensi.destroy', ['id' => $a['id'], 'bulan' => $bulan, 'tahun' => $tahun]) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus data absensi ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="table-empty">Belum ada data absensi untuk {{ $bulanList[$bulan] }} {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Unggah File Excel Absensi --}}
<div id="modal-import-absensi" class="pk-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:100%; max-width:520px; border-radius:18px; box-shadow:0 20px 40px rgba(0,0,0,0.25); overflow:hidden;">
        <div style="padding:18px 24px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:16px; font-weight:700; color:#0F2A3D;">Unggah File Excel Absensi Pegawai</div>
            <button type="button" onclick="closeImportModal('modal-import-absensi')" style="background:none; border:none; font-size:20px; color:#64748B; cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('absensi.import') }}" method="POST" enctype="multipart/form-data" style="padding:24px;">
            @csrf
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Target Periode:</label>
                <div style="display:flex; gap:10px;">
                    <select name="bulan" class="form-control" style="flex:1; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                        @foreach ($bulanList as $val => $label)
                            <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="tahun" class="form-control" style="width:110px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                        @for ($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Mode Pengunggahan:</label>
                <div style="display:flex; gap:12px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="radio" name="mode" value="update" checked>
                        <span>Perbarui jika sudah ada</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="radio" name="mode" value="skip">
                        <span>Lewati jika sudah ada</span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Pilih File Excel (.xlsx / .csv):</label>
                <input type="file" name="file_excel" accept=".xlsx,.csv,.txt" required style="display:block; width:100%; padding:10px; border:2px dashed #CBD5E1; border-radius:10px; background:#F8FAFC; font-size:13px;">
                <p style="font-size:11.5px; color:#64748B; margin-top:6px;">Format kolom template: NIK, Nama, Hari Kerja, Hadir, Sakit, Izin, Alpha, Keterangan.</p>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid #E2E8F0;">
                <button type="button" class="btn btn-outline" onclick="closeImportModal('modal-import-absensi')">Batal</button>
                <button type="submit" class="btn" style="background:#059669; color:#fff; font-weight:600;">Impor Data Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
window.openImportModal = function(id) {
    var modal = document.getElementById(id);
    if (modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        modal.style.display = 'flex';
    }
};
window.closeImportModal = function(id) {
    var modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
};
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modal-import-absensi');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeImportModal('modal-import-absensi');
        });
    }
});
</script>
@endsection
