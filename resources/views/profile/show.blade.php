@extends('layouts.app')

@section('title', 'Profile Saya')

@section('content')
@php
    $namaPeg = $pegawai['nama'] ?? $userLogin['nama_peg'];
    $inisial = collect(explode(' ', $namaPeg))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $badgeLabel = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
    $tabLabels = [
        'keluarga' => 'Keluarga',
        'golongan' => 'Golongan',
        'jabatan_riwayat' => 'Jabatan',
        'pendidikan' => 'Pendidikan',
        'prestasi' => 'Prestasi',
        'dok_surat' => 'Dokumen Surat (SDM)',
        'pengaturan' => 'Pengaturan Akun',
    ];
    $defaultTab = $errors->any() ? 'pengaturan' : 'keluarga';
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Profile Saya</div>
    <h1>Profile Saya</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper($inisial) }}</div>
    <div class="info">
        <h2>{{ $namaPeg }}</h2>
        <p>{{ $pegawai['jabatan'] ?? $userLogin['jabatan'] }} &middot; {{ $pegawai['unit_kerja'] ?? 'Unit Kerja' }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $pegawai['nik'] ?? $userLogin['nik'] }}</p><p>NIK</p></div>
        <div>
            <p><span class="badge badge-{{ $pegawai['status_peg'] ?? 'PT' }}">{{ $badgeLabel[$pegawai['status_peg'] ?? 'PT'] ?? ($pegawai['status_peg'] ?? 'Pegawai Tetap') }}</span></p>
            <p>Status</p>
        </div>
        <div>
            <p>{{ !empty($pegawai['tgl_masuk']) ? \Illuminate\Support\Carbon::parse($pegawai['tgl_masuk'])->translatedFormat('d M Y') : '-' }}</p>
            <p>Tgl Masuk</p>
        </div>
    </div>
</div>

<div class="tabs" id="profile-tabs">
    @foreach ($tabLabels as $type => $label)
        @php
            $count = ($type === 'pengaturan') ? null : count($pegawai[$type] ?? []);
        @endphp
        <button type="button" class="tab-btn {{ $type === $defaultTab ? 'active' : '' }}" data-tab="{{ $type }}" onclick="switchTab('{{ $type }}')">
            {{ $label }} {{ $count !== null ? "($count)" : '' }}
        </button>
    @endforeach
</div>

@foreach ($tabLabels as $type => $label)
    @if ($type === 'pengaturan')
        <div class="tab-panel {{ $type === $defaultTab ? 'active' : '' }}" data-panel="pengaturan">
            <div class="ribbon-card" style="max-width:580px; margin-top:8px;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:17px;">Ubah Password Akun</h2>
                </div>
                <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">Perbarui password akun Anda secara berkala untuk menjaga keamanan data.</p>
                <form method="POST" action="{{ route('profile.update-password') }}">
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <label for="current_password">Password Saat Ini</label>
                        <div class="input-wrap">
                            <input type="password" id="current_password" name="current_password" required placeholder="Masukkan password saat ini">
                        </div>
                        @error('current_password') <div class="error-text" style="margin-top:6px;">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="new_password">Password Baru</label>
                        <div class="input-wrap">
                            <input type="password" id="new_password" name="new_password" required placeholder="Masukkan password baru">
                        </div>
                        @error('new_password') <div class="error-text" style="margin-top:6px;">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="new_password_confirmation">Konfirmasi Password Baru</label>
                        <div class="input-wrap">
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" required placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="margin-top:8px;">Simpan Password Baru</button>
                </form>
            </div>
        </div>
    @elseif ($type === 'dok_surat')
        <div class="tab-panel {{ $type === $defaultTab ? 'active' : '' }}" data-panel="dok_surat">
            <style>
            .doc-card {
                border: 1px solid #E2E8F0;
                border-radius: 16px;
                padding: 20px;
                background: #ffffff;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
                transition: all 0.2s ease;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .doc-card:hover {
                border-color: #CBD5E1;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
                transform: translateY(-2px);
            }
            .doc-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 14px;
            }
            .doc-badge {
                font-size: 12px;
                font-weight: 600;
                padding: 5px 12px;
                border-radius: 20px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .doc-badge-sk {
                background: #ECFDF5;
                color: #047857;
                border: 1px solid #A7F3D0;
            }
            .doc-badge-diklat {
                background: #F5F3FF;
                color: #6D28D9;
                border: 1px solid #DDD6FE;
            }
            .doc-date {
                font-size: 12px;
                color: #64748B;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .doc-card-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 16px;
                font-weight: 700;
                color: #0F2A3D;
                margin-bottom: 6px;
                line-height: 1.4;
            }
            .doc-card-sub {
                font-size: 13px;
                color: #64748B;
                margin-bottom: 20px;
            }
            .doc-card-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .btn-doc-view {
                padding: 9px 16px;
                background: #F8FAFC;
                color: #334155;
                border: 1px solid #CBD5E1;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
            }
            .btn-doc-view:hover {
                background: #F1F5F9;
                border-color: #94A3B8;
                color: #0F172A;
            }
            .btn-doc-download {
                padding: 9px 18px;
                background: #0284C7;
                color: #ffffff;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
            }
            .btn-doc-download:hover {
                background: #0369A1;
                box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
                color: #ffffff;
            }
            </style>
            <div class="ribbon-card" style="margin-top:8px;">
                <div class="ribbon-head" style="margin-bottom:20px;">
                    <div>
                        <h2 style="font-size:18px; color:#0F2A3D;">Dokumen Resmi Pegawai (SDM)</h2>
                        <p style="font-size:13px; color:var(--text-muted); margin-top:4px;">Surat Kerja & Surat Diklat resmi yang diterbitkan dan diunggah oleh SDM.</p>
                    </div>
                    @if (session('simpeg_user.userlevel') === '1')
                        <button type="button" class="btn-submit" onclick="document.getElementById('modal-upload-dokumen').style.display='flex'">+ Upload Dokumen (Admin SDM)</button>
                    @endif
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:20px;">
                    <!-- Card 1: Surat Kerja -->
                    <div class="doc-card">
                        <div>
                            <div class="doc-card-header">
                                <span class="doc-badge doc-badge-sk">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    Surat Kerja (SK)
                                </span>
                                <span class="doc-date">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    {{ $pegawai['surat_kerja']['tgl_terbit'] ?? '-' }}
                                </span>
                            </div>
                            <h3 class="doc-card-title">{{ $pegawai['surat_kerja']['judul'] ?? 'Surat Kerja / SK Pegawai' }}</h3>
                            <p class="doc-card-sub">No: {{ $pegawai['surat_kerja']['nomor'] ?? '-' }}</p>
                        </div>
                        
                        <div class="doc-card-actions">
                            <button type="button" class="btn-doc-view" onclick="viewFileModal('{{ $pegawai['surat_kerja']['judul'] ?? 'Surat Kerja' }}', '{{ $pegawai['surat_kerja']['file_name'] ?? 'SK.pdf' }}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View File
                            </button>
                            <a href="#" class="btn-doc-download" onclick="showCustomAlert('Mengunduh file {{ $pegawai['surat_kerja']['file_name'] ?? 'SK.pdf' }}', 'Mengunduh File', 'download'); return false;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download
                            </a>
                        </div>
                    </div>

                    <!-- Card 2: Surat Diklat -->
                    <div class="doc-card">
                        <div>
                            <div class="doc-card-header">
                                <span class="doc-badge doc-badge-diklat">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                                    Surat Diklat / Pelatihan
                                </span>
                                <span class="doc-date">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    {{ $pegawai['surat_diklat']['tgl_terbit'] ?? '-' }}
                                </span>
                            </div>
                            <h3 class="doc-card-title">{{ $pegawai['surat_diklat']['judul'] ?? 'Sertifikat Diklat & Pelatihan' }}</h3>
                            <p class="doc-card-sub">No: {{ $pegawai['surat_diklat']['nomor'] ?? '-' }}</p>
                        </div>
                        
                        <div class="doc-card-actions">
                            <button type="button" class="btn-doc-view" onclick="viewFileModal('{{ $pegawai['surat_diklat']['judul'] ?? 'Surat Diklat' }}', '{{ $pegawai['surat_diklat']['file_name'] ?? 'Diklat.pdf' }}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View File
                            </button>
                            <a href="#" class="btn-doc-download" onclick="showCustomAlert('Mengunduh file {{ $pegawai['surat_diklat']['file_name'] ?? 'Diklat.pdf' }}', 'Mengunduh File', 'download'); return false;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="tab-panel {{ $type === $defaultTab ? 'active' : '' }}" data-panel="{{ $type }}">
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach ($detailTypes[$type]['fields'] as $field)
                                <th>{{ $field['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pegawai[$type] ?? [] as $item)
                            <tr>
                                @foreach ($detailTypes[$type]['fields'] as $field)
                                    <td>{{ $item[$field['key']] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($detailTypes[$type]['fields']) }}">
                                    <div class="table-empty">Belum ada data {{ strtolower($label) }}.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endforeach

@if (session('simpeg_user.userlevel') === '1')
<div id="modal-upload-dokumen" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; width:90%; max-width:480px; border-radius:8px; padding:24px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; font-size:18px; margin-bottom:16px;">Upload Dokumen (Admin SDM)</h3>
        <form method="POST" action="{{ route('profile.upload-dokumen') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="pegawai_id" value="{{ $pegawai['id'] }}">
            <div class="field" style="margin-bottom:12px;">
                <label>Jenis Dokumen</label>
                <select name="jenis_dokumen" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                    <option value="surat_kerja">Surat Kerja (SK)</option>
                    <option value="surat_diklat">Surat Diklat / Pelatihan</option>
                </select>
            </div>
            <div class="field" style="margin-bottom:12px;">
                <label>Nomor Surat/Sertifikat</label>
                <input type="text" name="nomor" required placeholder="Contoh: SK/SDM/2024/001" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div class="field" style="margin-bottom:12px;">
                <label>Judul / Keterangan Dokumen</label>
                <input type="text" name="judul" required placeholder="Contoh: SK Pengangkatan Pegawai" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div class="field" style="margin-bottom:12px;">
                <label>Tanggal Terbit</label>
                <input type="date" name="tgl_terbit" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div class="field" style="margin-bottom:20px;">
                <label>File Dokumen (PDF/Gambar)</label>
                <input type="file" name="file" accept=".pdf,.png,.jpg,.jpeg" style="width:100%;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn-action" style="padding:8px 16px;" onclick="document.getElementById('modal-upload-dokumen').style.display='none'">Batal</button>
                <button type="submit" class="btn-submit">Unggah Dokumen</button>
            </div>
        </form>
    </div>
</div>
@endif

<div id="modal-view-file" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; width:90%; max-width:650px; border-radius:8px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
            <h3 id="view-file-title" style="margin:0; font-size:16px;">Preview Dokumen</h3>
            <button type="button" onclick="document.getElementById('modal-view-file').style.display='none'" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <div style="background:#f8fafc; border:1px dashed #cbd5e1; padding:40px 20px; text-align:center; border-radius:6px; margin-bottom:16px;">
            <svg style="width:48px; height:48px; color:#64748b; margin-bottom:8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p style="font-weight:600; color:#334155; margin-bottom:4px;" id="view-file-name">Dokumen.pdf</p>
            <p style="font-size:12px; color:#64748b;">File PDF / Gambar telah diverifikasi oleh Admin SDM</p>
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button type="button" class="btn-submit" onclick="document.getElementById('modal-view-file').style.display='none'">Tutup Preview</button>
        </div>
    </div>
</div>

<script>
function switchTab(type) {
    document.querySelectorAll('#profile-tabs .tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === type));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === type));
}

function viewFileModal(judul, filename) {
    document.getElementById('view-file-title').innerText = 'Preview: ' + judul;
    document.getElementById('view-file-name').innerText = filename;
    document.getElementById('modal-view-file').style.display = 'flex';
}
</script>
@endsection
