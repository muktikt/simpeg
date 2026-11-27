@extends('layouts.app')

@section('title', 'Prestasi Pegawai')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Prestasi Pegawai</div>
    <h1>Prestasi Pegawai</h1>
</div>

<div class="toolbar">
    <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="text" class="table-search-input" placeholder="Cari NIK, nama pegawai, karya...">
    </div>
    @if ($bisaKelola)
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <a href="{{ route('prestasi.template') }}" class="btn btn-outline" style="border-color:#10B981; color:#059669; font-weight:600;" title="Unduh template Excel dengan daftar pegawai aktif">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Unduh Template
            </a>
            <button type="button" class="btn" style="background:#059669; color:#fff; font-weight:600;" onclick="openImportModal('modal-import-prestasi')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Unggah Excel
            </button>
            <a href="{{ route('prestasi.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Prestasi
            </a>
            @if ($prestasi->isNotEmpty())
                <form action="{{ route('prestasi.destroy-all') }}" method="POST" onsubmit="return confirmSubmit(event, 'Yakin ingin menghapus SEMUA data prestasi ({{ $prestasi->count() }} data) sekaligus?', 'Hapus Semua Prestasi', 'danger', 'Ya, Hapus Semua');" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="font-weight:600;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Hapus Semua
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

<div class="table-card" style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl. Prestasi</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th>Karya</th>
                <th>Absensi</th>
                <th title="Alpha">A</th>
                <th title="Izin Keterangan">I</th>
                <th title="Izin Tanpa Keterangan">ItK</th>
                <th title="Sakit Keterangan">S</th>
                <th title="Sakit Tanpa Keterangan">StD</th>
                <th title="Dinas Luar">DL</th>
                <th>Cuti</th>
                <th>Keterangan Cuti</th>
                <th>Jam Lembur</th>
                <th>Total Uang Lembur</th>
                @if ($bisaKelola)
                    <th style="width:1%"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($prestasi as $p)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ formatTglIndo($p['tanggal']) }}</td>
                    <td class="cell-nik">{{ $p['nik'] }}</td>
                    <td class="cell-name">{{ $p['nama'] }}</td>
                    <td>{{ $p['karya'] }}</td>
                    <td>{{ $p['absensi'] }}</td>
                    <td>{{ $p['alpha'] }}</td>
                    <td>{{ $p['izin_ket'] }}</td>
                    <td>{{ $p['izin_non_ket'] }}</td>
                    <td>{{ $p['sakit_ket'] }}</td>
                    <td>{{ $p['sakit_non_ket'] }}</td>
                    <td>{{ $p['dinas_luar'] }}</td>
                    <td>{{ $p['cuti'] }}</td>
                    <td>{{ $p['alasan_cuti'] ?: '-' }}</td>
                    <td>{{ $p['jam_lembur'] }} jam</td>
                    <td>Rp {{ number_format($p['nominal_lembur'], 0, ',', '.') }}</td>
                    @if ($bisaKelola)
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('prestasi.edit', $p['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('prestasi.destroy', $p['id']) }}" method="POST" onsubmit="return confirmSubmit(event, 'Hapus data prestasi ini?', 'Konfirmasi Hapus', 'danger', 'Ya, Hapus');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="16">
                        <div class="table-empty">Belum ada data prestasi pegawai.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Unggah File Excel Prestasi --}}
<div id="modal-import-prestasi" class="pk-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:100%; max-width:540px; border-radius:18px; box-shadow:0 20px 40px rgba(0,0,0,0.25); overflow:hidden;">
        <div style="padding:18px 24px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:16px; font-weight:700; color:#0F2A3D;">Unggah File Excel Prestasi & Lembur Pegawai</div>
            <button type="button" onclick="closeImportModal('modal-import-prestasi')" style="background:none; border:none; font-size:20px; color:#64748B; cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('prestasi.import') }}" method="POST" enctype="multipart/form-data" style="padding:24px;">
            @csrf
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Target Periode Bulan & Tahun:</label>
                <div style="display:flex; gap:10px;">
                    <select name="bulan" class="form-control" style="flex:1; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                        @foreach (\App\Http\Controllers\AbsensiController::BULAN as $val => $label)
                            <option value="{{ $val }}" @selected(now()->month === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="tahun" class="form-control" style="width:110px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                        @for ($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" @selected(now()->year === $y)>{{ $y }}</option>
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
                <p style="font-size:11.5px; color:#64748B; margin-top:6px;">Format kolom: NIK, Nama, Tanggal, Karya, Absensi, Jam Lembur, Alpha, Izin, Sakit, Dinas Luar, Cuti.</p>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid #E2E8F0;">
                <button type="button" class="btn btn-outline" onclick="closeImportModal('modal-import-prestasi')">Batal</button>
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
    var modal = document.getElementById('modal-import-prestasi');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeImportModal('modal-import-prestasi');
        });
    }
});
</script>
@endsection
