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
    background: rgba(15, 42, 61, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9999;
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
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Keuangan / SET {{ $tipeLabel }}</div>
    <div class="pk-header">
        <div>
            <h1 class="pk-title">SET {{ strtoupper($tipeLabel) }}</h1>
            <p class="pk-subtitle">Kelola nominal potongan pegawai bulanan secara manual atau impor otomatis dari berkas Excel/CSV.</p>
        </div>
        <div class="pk-btn-group">
            <a href="{{ route('potongan-keu.template', $tipe) }}" class="pk-btn-template" title="Download Template Excel Berisi Daftar NIK Pegawai">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download Format Template
            </a>
            <button type="button" class="pk-btn-excel" onclick="openImportModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                Import File Excel
            </button>
        </div>
    </div>
</div>

@if ($belumMasuk > 0)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        Ada pegawai yang belum dibuat {{ strtolower($tipeLabel) }} sebanyak <strong>{{ $belumMasuk }}</strong> Pegawai.
        <a href="{{ route('potongan-keu.belum-masuk', $tipe) }}" style="font-weight:600; text-decoration:underline; margin-left:6px;">Lihat daftar pegawai belum masuk</a>
    </div>
@endif

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
    <form method="GET" action="{{ route('potongan-keu.index', $tipe) }}" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; flex:1; max-width:480px;">
        <div style="position:relative; flex:1; min-width:240px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748B;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari NIK, nama pegawai, jabatan..." class="form-control" style="padding-left:36px; height:40px; border-radius:8px; width:100%; border:1px solid #CBD5E1; font-size:13px;">
        </div>
        <button type="submit" class="btn btn-outline" style="height:40px; padding:0 16px; font-weight:600; font-size:13px;">Cari</button>
        @if ($keyword !== '')
            <a href="{{ route('potongan-keu.index', $tipe) }}" class="btn btn-outline" style="height:40px; padding:0 12px; color:#DC2626; border-color:#FECACA; font-size:13px; display:inline-flex; align-items:center;" title="Reset Pencarian">Reset</a>
        @endif
    </form>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <a href="{{ route('potongan-keu.create', $tipe) }}" class="btn btn-primary" style="padding:9px 16px; font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
            Input Manual 1 Pegawai
        </a>

        <a href="{{ route('potongan-keu.terbit', $tipe) }}" class="btn btn-outline" style="font-weight:600; padding:9px 16px; font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px; color:#2E86AB;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Lihat Halaman Penerbitan Realtime
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
                        <td>{{ $loop->iteration }}</td>
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
                                    <div style="font-weight:700; color:#334155; font-size:15px; margin-bottom:4px;">Belum ada data {{ strtolower($tipeLabel) }} periode ini.</div>
                                    <div style="font-size:13px; color:#64748B; margin-bottom:14px;">Gunakan fitur <strong>Import File Excel</strong> untuk mengisi otomatis seluruh potongan pegawai sekaligus.</div>
                                    <button type="button" class="pk-btn-excel" onclick="openImportModal()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                        Unggah File Excel Sekarang
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Import Excel Potongan -->
<div id="pk-modal-import" class="pk-modal-overlay">
    <div class="pk-modal-box">
        <div class="pk-modal-header">
            <h3 class="pk-modal-title">Import File Excel {{ $tipeLabel }}</h3>
            <button type="button" onclick="closeImportModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748B;">&times;</button>
        </div>
        <form method="POST" action="{{ route('potongan-keu.import', $tipe) }}" enctype="multipart/form-data" class="pk-modal-body">
            @csrf
            <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; padding:12px 14px; margin-bottom:16px; font-size:12.5px; color:#166534;">
                <strong>Petunjuk:</strong> Unduh format template terlebih dahulu agar kolom NIK dan nama pegawai sudah terisi rapi, lalu tinggal isi nominalnya.
                <div style="margin-top:6px;">
                    <a href="{{ route('potongan-keu.template', $tipe) }}" style="color:#059669; font-weight:700; text-decoration:underline;">
                        Unduh Template Spreadsheet (.CSV)
                    </a>
                </div>
            </div>

            <div class="pk-dropzone" onclick="document.getElementById('import-file-input').click()">
                <div style="width:50px; height:50px; border-radius:12px; background:#DCFCE7; color:#16A34A; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <div style="font-weight:700; color:#0F2A3D; font-size:14px;" id="dropzone-label">Pilih Berkas Excel / CSV</div>
                <div style="font-size:12px; color:#64748B; margin-top:3px;">Format didukung: .xlsx, .xls, .csv (Maksimal 10MB)</div>
            </div>
            <input type="file" name="file_excel" id="import-file-input" accept=".xlsx,.xls,.csv,.txt" required style="display:none;" onchange="handleImportFile(this)">

            <div style="margin-top:16px;">
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
function openImportModal() {
    document.getElementById('pk-modal-import').style.display = 'flex';
}
function closeImportModal() {
    document.getElementById('pk-modal-import').style.display = 'none';
}
function handleImportFile(input) {
    if (input.files && input.files[0]) {
        document.getElementById('dropzone-label').innerHTML = 'File Terpilih: <span style="color:#059669; font-weight:600;">' + input.files[0].name + '</span> (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
    }
}

document.getElementById('pk-modal-import').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});
</script>
@endsection
