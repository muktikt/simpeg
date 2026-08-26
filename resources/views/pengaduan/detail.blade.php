@extends('layouts.app')

@section('title', 'Detail Pengaduan ' . ($pengaduan->nomor_pengaduan ?? ''))

@section('content')
<div class="page-head">
    <div class="breadcrumb">
        <a href="{{ route('pengaduan.index') }}" style="color:var(--text-muted); text-decoration:none;">Home / Pengaduan</a> / Detail
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0;">{{ $pengaduan->nomor_pengaduan ?? ('PGD-' . $pengaduan->id) }}</h1>
            <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);">
                Kategori: <strong>{{ $pengaduan->kategori ?? 'Umum' }}</strong> · Tanggal: {{ date('d F Y, H:i', strtotime($pengaduan->created_at ?? now())) }}
            </p>
        </div>
        <div>
            <a href="{{ route('pengaduan.index') }}" style="background:#f1f5f9; color:#475569; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid #cbd5e1;">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success" style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
        ✓ {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger" style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
        ✕ {{ session('error') }}
    </div>
@endif

@php
    $statusName = $pengaduan->status ?? 'menungguKadiv';
    $statusLabels = [
        'menungguKadiv' => 'Menunggu Verifikasi Kadiv',
        'menungguVerifikasiKadiv' => 'Menunggu Verifikasi Kadiv',
        'reviewKspi' => 'Review KSPI (Awal)',
        'menungguReviewKspi' => 'Menunggu Review Hasil KSPI',
        'menungguDirutTahap1' => 'Menunggu Persetujuan Dirut (Tahap 1)',
        'menungguPilihEksekutor' => 'Menunggu Penunjukan Eksekutor KSPI',
        'investigasiBerjalan' => 'Investigasi Sedang Berjalan',
        'revisiInvestigasi' => 'Revisi Investigasi Diminta KSPI',
        'menungguDirutTahap2' => 'Menunggu Persetujuan Dirut (Tahap 2)',
        'tindakLanjutBerjalan' => 'Tindak Lanjut Sedang Berjalan',
        'menungguSdm' => 'Menunggu Eksekusi Administratif SDM',
        'selesai' => 'Selesai',
        'arsip' => 'Diarsipkan / Selesai Tanpa Tindak Lanjut',
        'ditolakDirektur' => 'Ditolak Direktur',
    ];
@endphp

<div style="display:grid; grid-template-columns: 1fr 380px; gap:20px; align-items:start;">
    
    {{-- KOLOM KIRI: DETAIL LAPORAN & BUKTI --}}
    <div>
        {{-- Card Status Banner --}}
        <div style="background:#0d2c6e; color:#fff; padding:18px 20px; border-radius:12px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <small style="text-transform:uppercase; letter-spacing:0.5px; opacity:0.8; font-size:11px;">Status Alur Saat Ini</small>
                <div style="font-size:18px; font-weight:700; margin-top:2px;">{{ $statusLabels[$statusName] ?? $statusName }}</div>
            </div>
            <div style="text-align:right;">
                <span style="background:rgba(255,255,255,0.18); padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600;">
                    Nomor: {{ $pengaduan->nomor_pengaduan }}
                </span>
            </div>
        </div>

        {{-- Card Informasi Kasus --}}
        <div class="ribbon-card" style="margin-bottom:20px;">
            <div class="ribbon-head" style="margin-bottom:14px;">
                <h2 style="font-size:16px;">Informasi Pokok Pengaduan</h2>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:16px; background:#f8fafc; padding:14px; border-radius:8px; border:1px solid #e2e8f0;">
                <div>
                    <small style="color:var(--text-muted); display:block; font-size:11.5px;">Identitas Pelapor:</small>
                    @if (!empty($pengaduan->anonim))
                        <strong style="color:#dc2626;">🔒 Anonim (Dirahasiakan)</strong>
                    @else
                        <strong style="font-size:13.5px;">{{ $pengaduan->nama_pegawai ?? '-' }}</strong>
                        <div style="font-size:12px; color:#475569;">NIK: {{ $pengaduan->nik ?? '-' }} · {{ $pengaduan->cabang ?? '' }}</div>
                    @endif
                </div>
                <div>
                    <small style="color:var(--text-muted); display:block; font-size:11.5px;">Pihak yang Diadukan (Terlapor):</small>
                    <strong style="font-size:13.5px; color:#0f172a;">{{ $pengaduan->pihak_terlapor ?? '-' }}</strong>
                    <div style="font-size:12px; color:#475569;">
                        {{ !empty($pengaduan->nik_pelaku) ? 'NIK: ' . $pengaduan->nik_pelaku : '' }}
                        {{ !empty($pengaduan->jabatan_pelaku) ? '· ' . $pengaduan->jabatan_pelaku : '' }}
                    </div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Judul / Pokok Masalah:</label>
                <div style="font-size:15px; font-weight:700; color:#1e293b;">{{ $pengaduan->judul }}</div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Uraian & Kronologi Kejadian:</label>
                <div style="background:#fff; border:1px solid #e2e8f0; padding:14px; border-radius:8px; font-size:13.5px; line-height:1.6; color:#334155; white-space:pre-line;">{{ $pengaduan->deskripsi }}</div>
            </div>

            {{-- Lampiran Bukti --}}
            @php
                $fotoBukti = !empty($pengaduan->foto_bukti) ? (is_array($pengaduan->foto_bukti) ? $pengaduan->foto_bukti : json_decode($pengaduan->foto_bukti, true)) : [];
                $dokumen = !empty($pengaduan->dokumen_pendukung) ? (is_array($pengaduan->dokumen_pendukung) ? $pengaduan->dokumen_pendukung : json_decode($pengaduan->dokumen_pendukung, true)) : [];
            @endphp

            @if (!empty($fotoBukti) || !empty($dokumen))
                <div style="margin-top:16px; border-top:1px solid #e2e8f0; padding-top:14px;">
                    <label style="font-size:12.5px; font-weight:700; color:#1e293b; display:block; margin-bottom:8px;">Berkas Bukti Terlampir:</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        @foreach ($fotoBukti as $f)
                            <a href="{{ $f }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; padding:6px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                                📷 Lihat Foto Bukti
                            </a>
                        @endforeach
                        @foreach ($dokumen as $d)
                            <a href="{{ $d }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; color:#334155; padding:6px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                                📄 Unduh Dokumen
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Card Laporan Hasil Investigasi (Jika sudah ada) --}}
        @if (!empty($pengaduan->hasil_investigasi) || !empty($pengaduan->surat_rekomendasi))
            <div class="ribbon-card" style="margin-bottom:20px; border-left:4px solid #8e44ad;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:16px; color:#6b21a8;">Laporan Hasil Investigasi & Rekomendasi Sanksi</h2>
                </div>

                @if (!empty($pengaduan->petugas_investigasi))
                    <div style="font-size:12.5px; color:#475569; margin-bottom:10px;">
                        Petugas Investigator: <strong>{{ $pengaduan->petugas_investigasi }}</strong>
                        ({{ $pengaduan->eksekutor === 'kadiv' ? 'Kadiv' : 'TPDPK' }})
                    </div>
                @endif

                @if (!empty($pengaduan->hasil_investigasi))
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Hasil Temuan Fakta Investigasi:</label>
                        <div style="background:#faf5ff; border:1px solid #e9d5ff; padding:12px; border-radius:8px; font-size:13px; color:#3b0764; white-space:pre-line;">{{ $pengaduan->hasil_investigasi }}</div>
                    </div>
                @endif

                @if (!empty($pengaduan->surat_rekomendasi))
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Surat / Poin Rekomendasi Sanksi:</label>
                        <div style="background:#faf5ff; border:1px solid #e9d5ff; padding:12px; border-radius:8px; font-size:13px; color:#3b0764; white-space:pre-line;">{{ $pengaduan->surat_rekomendasi }}</div>
                    </div>
                @endif

                @php
                    $invDok = !empty($pengaduan->investigasi_dokumen) ? (is_array($pengaduan->investigasi_dokumen) ? $pengaduan->investigasi_dokumen : json_decode($pengaduan->investigasi_dokumen, true)) : [];
                @endphp
                @if (!empty($invDok))
                    <div style="margin-top:10px;">
                        <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Berkas Berita Acara:</label>
                        @foreach ($invDok as $doc)
                            <a href="{{ $doc }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f3e8ff; border:1px solid #d8b4fe; color:#6b21a8; padding:5px 10px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                                📑 Lihat Berkas BAP / Rekomendasi
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- KOLOM KANAN: PANEL AKSI & TIMELINE RIWAYAT --}}
    <div>
        {{-- ========================================================= --}}
        {{-- 1. PANEL AKSI KADIV --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kadiv' && in_array($statusName, ['menungguKadiv', 'menungguVerifikasiKadiv']))
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #16a085;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0f766e;">⚡ Tindakan Kadiv</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Verifikasi pengaduan masuk dan teruskan ke KSPI untuk peninjauan lebih lanjut.
                </p>

                <form method="POST" action="{{ route('pengaduan.kadiv-verifikasi', $pengaduan->id) }}" style="margin-bottom:14px;">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Keputusan Verifikasi:</label>
                        <select name="keputusan" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="terima">✓ Terima (Diteruskan ke KSPI)</option>
                            <option value="tolak">✕ Tolak (Catat & Teruskan ke KSPI)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Kadiv (Opsional):</label>
                        <textarea name="catatan" rows="2" placeholder="Tuliskan catatan verifikasi..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#0f766e; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Kirim Verifikasi ke KSPI
                    </button>
                </form>

                <hr style="border:none; border-top:1px solid #e2e8f0; margin:14px 0;">

                {{-- Form Alihkan Kategori --}}
                <form method="POST" action="{{ route('pengaduan.kadiv-alihkan', $pengaduan->id) }}">
                    @csrf
                    <label style="font-weight:600; font-size:12px; color:#475569; display:block; margin-bottom:4px;">Salah Kategori? Alihkan:</label>
                    <div style="display:flex; gap:6px;">
                        <select name="kategori_baru" style="flex:1; border:1px solid var(--border); border-radius:6px; padding:6px 8px; font-size:12px;">
                            <option value="Pelanggaran Administrasi" {{ $pengaduan->kategori === 'Pelanggaran Administrasi' ? 'selected' : '' }}>Pelanggaran Administrasi</option>
                            <option value="Pelanggaran Teknik" {{ $pengaduan->kategori === 'Pelanggaran Teknik' ? 'selected' : '' }}>Pelanggaran Teknik</option>
                        </select>
                        <button type="submit" style="padding:6px 12px; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer;">
                            Alihkan
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 2. PANEL AKSI KSPI (REVIEW AWAL) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kspi' && $statusName === 'reviewKspi')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #2e86ab;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0369a1;">⚡ Tindakan KSPI (Review Awal)</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Hasil verifikasi dari Kadiv telah masuk. Teruskan ke Direktur Utama atau tolak/arsipkan jika tidak berdasar.
                </p>

                <form method="POST" action="{{ route('pengaduan.kspi-teruskan-dirut', $pengaduan->id) }}" style="margin-bottom:12px;">
                    @csrf
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Pengantar ke Dirut (Opsional):</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan telaah awal..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#0284c7; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        ➔ Teruskan ke Direktur Utama
                    </button>
                </form>

                <form method="POST" action="{{ route('pengaduan.kspi-tolak', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px; color:#b91c1c;">Alasan Penolakan (Wajib jika ditolak):</label>
                        <textarea name="catatan" required rows="2" placeholder="Tuliskan alasan penolakan aduan..." style="width:100%; border:1px solid #fca5a5; border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" onclick="return confirm('Yakin ingin menolak dan mengarsipkan pengaduan ini?')" style="width:100%; padding:8px; background:#ef4444; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer;">
                        ✕ Tolak & Arsipkan
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 3. PANEL AKSI DIRUT (PERSETUJUAN TAHAP 1) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'dirut' && $statusName === 'menungguDirutTahap1')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #e67e22;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#c2410c;">⚡ Persetujuan Dirut (Tahap 1)</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Tentukan kelayakan pengaduan ini untuk dilakukan proses investigasi resmi.
                </p>

                <form method="POST" action="{{ route('pengaduan.dirut-tahap1', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Keputusan Direktur Utama:</label>
                        <select name="keputusan" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="terima">✓ Setujui (Layak Diinvestigasi)</option>
                            <option value="tolak">✕ Tolak (Arsipkan Pengaduan)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Arahan / Catatan Dirut:</label>
                        <textarea name="catatan" rows="2" placeholder="Tuliskan arahan atau pertimbangan..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#ea580c; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Simpan Keputusan Tahap 1
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 4. PANEL AKSI KSPI (PILIH EKSEKUTOR INVESTIGASI) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kspi' && $statusName === 'menungguPilihEksekutor')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #6d28d9;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#5b21b6;">⚡ Penunjukan Eksekutor Investigasi</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Dirut telah menyetujui. Tentukan tim yang akan melaksanakan investigasi lapangan.
                </p>

                <form method="POST" action="{{ route('pengaduan.kspi-pilih-eksekutor', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Tim Eksekutor:</label>
                        <select name="eksekutor" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="tpdpk">TPDPK (Tim Penegak Disiplin Pegawai)</option>
                            <option value="kadiv">Kadiv Kategori</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Divisi (Jika Memilih Kadiv):</label>
                        <select name="divisi_kadiv" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="">-- Pilih jika eksekutor Kadiv --</option>
                            <option value="administrasi">Kadiv Administrasi</option>
                            <option value="teknik">Kadiv Teknik</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Nama Petugas Investigator:</label>
                        <input type="text" name="petugas_investigasi" placeholder="Contoh: Dedi Kurniawan, S.T." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;">
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Penugasan:</label>
                        <textarea name="catatan" rows="2" placeholder="Instruksi khusus investigasi..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#7c3aed; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Tugaskan Investigator
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 5. PANEL AKSI TPDPK / KADIV (INPUT HASIL INVESTIGASI) --}}
        {{-- ========================================================= --}}
        @if (($myRole === 'tpdpk' || $myRole === 'kadiv') && in_array($statusName, ['investigasiBerjalan', 'revisiInvestigasi']))
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #8e44ad;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#6b21a8;">⚡ Laporan Hasil Investigasi</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Masukkan Berita Acara temuan pemeriksaan dan susun rekomendasi sanksi untuk KSPI.
                </p>

                <form method="POST" action="{{ route('pengaduan.tpdpk-hasil-investigasi', $pengaduan->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Hasil Temuan Fakta / Berita Acara *</label>
                        <textarea name="hasil_investigasi" rows="4" required placeholder="Tuliskan fakta temuan hasil pemeriksaan lapangan..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px;"></textarea>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Surat Rekomendasi Sanksi *</label>
                        <textarea name="surat_rekomendasi" rows="3" required placeholder="Rekomendasi tindakan atau sanksi disiplin..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px;"></textarea>
                    </div>
                    <div class="field" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:12px;">Unggah Berkas Laporan / BAP (PDF/DOC/JPG):</label>
                        <input type="file" name="dokumen_investigasi[]" multiple style="font-size:12px; width:100%;">
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#9333ea; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Kirim Hasil Investigasi ke KSPI
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 6. PANEL AKSI KSPI (REVIEW HASIL INVESTIGASI) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kspi' && $statusName === 'menungguReviewKspi')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #2e86ab;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0369a1;">⚡ Review Hasil Investigasi</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Periksa kelengkapan laporan investigasi. Jika sesuai, teruskan ke Direktur Utama untuk persetujuan akhir.
                </p>

                <form method="POST" action="{{ route('pengaduan.kspi-review-hasil', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Hasil Peninjauan KSPI:</label>
                        <select name="sesuai" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="1">✓ Laporan Sesuai & Lengkap (Teruskan ke Dirut)</option>
                            <option value="0">✕ Belum Lengkap (Kembalikan untuk Revisi)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Review KSPI:</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan kelengkapan..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#0284c7; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Kirim Hasil Review
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 7. PANEL AKSI DIRUT (PERSETUJUAN TAHAP 2) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'dirut' && $statusName === 'menungguDirutTahap2')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #27ae60;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#15803d;">⚡ Keputusan Akhir Dirut (Tahap 2)</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Tinjau rekomendasi sanksi hasil investigasi. Berikan persetujuan akhir atau minta peninjauan kembali.
                </p>

                <form method="POST" action="{{ route('pengaduan.dirut-tahap2', $pengaduan->id) }}" style="margin-bottom:12px;">
                    @csrf
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px;">Keputusan Direktur Utama:</label>
                        <select name="keputusan" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="terima">✓ Setujui Rekomendasi Sanksi (Kasus Selesai)</option>
                            <option value="tolak">✕ Tolak Hasil Investigasi (Arsipkan)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Keputusan Dirut:</label>
                        <textarea name="catatan" rows="2" placeholder="Tuliskan catatan persetujuan..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#16a34a; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Simpan Keputusan Akhir
                    </button>
                </form>

                <form method="POST" action="{{ route('pengaduan.dirut-peninjauan-kembali', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px; color:#b45309;">Minta Peninjauan Kembali (Investigasi Ulang):</label>
                        <textarea name="catatan" required rows="2" placeholder="Tuliskan poin yang perlu diinvestigasi ulang..." style="width:100%; border:1px solid #fde68a; border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:8px; background:#d97706; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer;">
                        ↺ Kembalikan untuk Investigasi Ulang
                    </button>
                </form>
            </div>
        @endif

        {{-- Card Timeline Riwayat Status Audit Trail --}}
        <div class="ribbon-card">
            <div class="ribbon-head" style="margin-bottom:14px;">
                <h2 style="font-size:15px;">Riwayat Alur Proses</h2>
            </div>

            <div style="position:relative; padding-left:20px; border-left:2px solid #e2e8f0; margin-left:10px;">
                @forelse ($riwayat as $r)
                    <div style="position:relative; margin-bottom:18px;">
                        {{-- Bullet Dot --}}
                        <div style="position:absolute; left:-27px; top:2px; width:12px; height:12px; border-radius:50%; background:#0d2c6e; border:2px solid #fff; box-shadow:0 0 0 2px #cbd5e1;"></div>
                        
                        <div style="font-size:13px; font-weight:700; color:#0f172a;">{{ $r->aksi ?? $r->status }}</div>
                        <div style="font-size:11.5px; color:var(--text-muted); margin-bottom:4px;">
                            Oleh: <strong>{{ $r->oleh }}</strong> ({{ $r->role ?? 'Sistem' }}) · {{ date('d M Y, H:i', strtotime($r->tanggal)) }}
                        </div>
                        @if (!empty($r->keterangan))
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 10px; font-size:12px; color:#475569;">
                                {{ $r->keterangan }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="font-size:12px; color:var(--text-muted);">Belum ada riwayat tercatat.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
