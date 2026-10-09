<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Detail Pengaduan - {{ $pengaduan->nomor_pengaduan ?? ('PGD-' . $pengaduan->id) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 16mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #0f172a;
            background: #f1f5f9;
            padding: 24px 12px;
            font-size: 11.5pt;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Action Bar (Non-Printable) */
        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .btn {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .btn-print {
            background: #0d2c6e;
            color: #ffffff;
            border-color: #0d2c6e;
            box-shadow: 0 2px 6px rgba(13,44,110,0.25);
        }
        .btn-print:hover {
            background: #091f4e;
        }
        .btn-back {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #334155;
        }
        .btn-back:hover {
            background: #e2e8f0;
        }

        /* Document Sheet (A4 Dimensions) */
        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 34px 44px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            position: relative;
            min-height: 1050px;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 64pt;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            color: rgba(13, 44, 110, 0.035);
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
            z-index: 0;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }
        .kop-logo {
            width: 80px;
            text-align: center;
            vertical-align: middle;
        }
        .kop-logo-box {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 2.5px solid #0d2c6e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 16px;
            color: #0d2c6e;
            background: #f0f7ff;
            margin: 0 auto;
            letter-spacing: 0.5px;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }
        .kop-text h3 {
            font-size: 12.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1px;
            color: #1e293b;
        }
        .kop-text h2 {
            font-size: 15pt;
            font-weight: bold;
            color: #0d2c6e;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .kop-text p {
            font-size: 8.5pt;
            font-family: Arial, sans-serif;
            color: #334155;
            line-height: 1.35;
        }
        .kop-divider {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 3px;
            margin: 6px 0 18px 0;
            position: relative;
            z-index: 1;
        }

        /* Document Header */
        .doc-title-box {
            text-align: center;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }
        .doc-title {
            font-size: 13.5pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .doc-number {
            font-size: 10.5pt;
            margin-top: 4px;
            font-family: 'IBM Plex Mono', monospace;
            color: #1e40af;
            font-weight: 600;
        }

        /* Section Styling */
        .section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 10pt;
            font-weight: 800;
            color: #0d2c6e;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 14px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 3px;
            page-break-after: avoid;
        }
        .section-title span.num {
            background: #0d2c6e;
            color: #fff;
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 8.5pt;
        }

        /* Meta Table & Info Boxes */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10pt;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #cbd5e1;
            padding: 6px 9px;
            vertical-align: top;
        }
        .table-custom th {
            background: #f8fafc;
            color: #0d2c6e;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 9.5pt;
            text-align: left;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 10pt;
            line-height: 1.4;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-lbl {
            width: 170px;
            color: #475569;
            font-weight: 600;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 9.5pt;
        }
        .info-sep {
            width: 15px;
            text-align: center;
        }
        .info-val {
            flex: 1;
            color: #0f172a;
            font-weight: 600;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8.5pt;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            border: 1px solid transparent;
        }

        /* Content Text Area */
        .narrative-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 10.5pt;
            line-height: 1.55;
            text-align: justify;
            white-space: pre-line;
            margin-bottom: 12px;
        }

        /* Timeline Audit Trail Table */
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 12px;
        }
        .audit-table th {
            background: #f1f5f9;
            color: #0d2c6e;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .audit-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        /* Attachments Section */
        .attach-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        .attach-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            background: #f8fafc;
            page-break-inside: avoid;
        }
        .attach-thumb {
            width: 100%;
            max-height: 180px;
            object-fit: contain;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-top: 6px;
            display: block;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 26px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            page-break-inside: avoid;
            font-family: 'Times New Roman', Times, serif;
        }
        .signature-box {
            text-align: center;
        }
        .signature-box.right {
            text-align: center;
        }
        .sig-city-date {
            margin-bottom: 6px;
            font-size: 10pt;
        }
        .sig-role {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 60px;
            text-transform: uppercase;
        }
        .sig-name {
            font-weight: bold;
            font-size: 10.5pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .sig-nik {
            font-size: 9pt;
            font-family: Arial, sans-serif;
            color: #475569;
            margin-top: 2px;
        }

        /* Print Media Rules */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet {
                box-shadow: none;
                border: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
                min-height: auto;
            }
            .attach-card {
                page-break-inside: avoid;
            }
            .table-custom, .audit-table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    @php
        $statusLabels = [
            'menungguKadiv' => 'Menunggu Verifikasi Kadiv',
            'menungguVerifikasiKadiv' => 'Menunggu Verifikasi Kadiv',
            'reviewKspi' => 'Review KSPI (Awal)',
            'menungguReviewKspi' => 'Menunggu Review KSPI',
            'menungguDirutTahap1' => 'Menunggu Persetujuan Dirut (Tahap 1)',
            'menungguPilihEksekutor' => 'Menunggu Pemilihan Tim Eksekutor',
            'investigasiBerjalan' => 'Investigasi Sedang Berjalan',
            'revisiInvestigasi' => 'Revisi Investigasi Diminta KSPI',
            'menungguDirutTahap2' => 'Menunggu Persetujuan Dirut (Tahap 2)',
            'menungguPilihEksekutorTindakLanjut' => 'Menunggu Pilih Eksekutor Tindak Lanjut',
            'tindakLanjutBerjalan' => 'Tindak Lanjut Sedang Berjalan',
            'menungguSdm' => 'Menunggu Eksekusi Administratif SDM',
            'selesai' => 'Selesai',
            'arsip' => 'Diarsipkan',
            'ditolakDirektur' => 'Ditolak Direktur',
        ];
        $stKey = $pengaduan->status ?? 'menungguKadiv';
        $statusLabel = $statusLabels[$stKey] ?? $stKey;

        $badgeStyle = match($stKey) {
            'selesai' => 'background:#dcfce7; color:#15803d; border-color:#86efac;',
            'arsip' => 'background:#f1f5f9; color:#475569; border-color:#cbd5e1;',
            'ditolakDirektur' => 'background:#fee2e2; color:#b91c1c; border-color:#fca5a5;',
            'menungguDirutTahap1', 'menungguDirutTahap2', 'menungguSdm' => 'background:#ffedd5; color:#c2410c; border-color:#fed7aa;',
            default => 'background:#e0f2fe; color:#0369a1; border-color:#bae6fd;',
        };

        // Kumpulkan semua lampiran menjadi list terpadu
        $lampiranList = [];
        $lCount = 1;

        if (!empty($fotoBukti)) {
            foreach ($fotoBukti as $i => $u) {
                $lampiranList[] = [
                    'no' => $lCount++,
                    'label' => 'Foto Bukti Pelapor #' . ($i + 1),
                    'folder' => 'Bukti Pelapor',
                    'url' => $u,
                    'is_image' => true,
                    'type' => 'Foto',
                ];
            }
        }
        if (!empty($dokumenPendukung)) {
            foreach ($dokumenPendukung as $i => $u) {
                $ext = strtolower(pathinfo(parse_url($u, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                $lampiranList[] = [
                    'no' => $lCount++,
                    'label' => 'Dokumen Pendukung #' . ($i + 1),
                    'folder' => 'Dokumen Pelapor',
                    'url' => $u,
                    'is_image' => $isImg,
                    'type' => $isImg ? 'Foto' : 'Dokumen',
                ];
            }
        }
        if (!empty($videoBukti)) {
            foreach ($videoBukti as $i => $u) {
                $lampiranList[] = [
                    'no' => $lCount++,
                    'label' => 'Video Bukti Pelapor #' . ($i + 1),
                    'folder' => 'Video Pelapor',
                    'url' => $u,
                    'is_image' => false,
                    'type' => 'Video',
                ];
            }
        }
        if (!empty($voiceNote)) {
            foreach ($voiceNote as $i => $u) {
                $lampiranList[] = [
                    'no' => $lCount++,
                    'label' => 'Voice Note Pelapor #' . ($i + 1),
                    'folder' => 'Audio Pelapor',
                    'url' => $u,
                    'is_image' => false,
                    'type' => 'Audio',
                ];
            }
        }
        if (!empty($investigasiFoto)) {
            foreach ($investigasiFoto as $i => $u) {
                $lampiranList[] = [
                    'no' => $lCount++,
                    'label' => 'Foto Hasil Pemeriksaan #' . ($i + 1),
                    'folder' => 'Hasil Investigasi',
                    'url' => $u,
                    'is_image' => true,
                    'type' => 'Foto Investigasi',
                ];
            }
        }
        if (!empty($investigasiDokumen)) {
            foreach ($investigasiDokumen as $i => $u) {
                $ext = strtolower(pathinfo(parse_url($u, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                $lampiranList[] = [
                    'no' => $lCount++,
                    'label' => 'Dokumen Pemeriksaan #' . ($i + 1),
                    'folder' => 'Hasil Investigasi',
                    'url' => $u,
                    'is_image' => $isImg,
                    'type' => $isImg ? 'Foto Investigasi' : 'Dokumen Investigasi',
                ];
            }
        }
    @endphp

    {{-- BAR AKSI DI LAYAR WEB (TIDAK TERCETAK DI PDF) --}}
    <div class="no-print-bar">
        <div>
            <strong style="color:#0d2c6e;">📄 Surat Detail Pengaduan Pegawai</strong> &bull;
            <span style="color:#475569; font-weight:600;">{{ $pengaduan->nomor_pengaduan }}</span>
            <span class="badge-status" style="{{ $badgeStyle }} margin-left: 8px;">
                {{ $statusLabel }}
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('pengaduan.detail', $pengaduan->id) }}" class="btn btn-back">
                &larr; Kembali ke Detail
            </a>
            <button type="button" class="btn btn-print" onclick="window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- LEMBAR DOKUMEN SURAT RESMI (A4) --}}
    <div class="sheet">
        <div class="watermark">TIRTA DARMA AYU</div>

        <!-- KOP SURAT RESMI KEDINASAN -->
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <div class="kop-logo-box">PDAM</div>
                </td>
                <td class="kop-text">
                    <h3>PEMERINTAH KABUPATEN INDRAMAYU</h3>
                    <h2>PERUMDA AIR MINUM TIRTA DARMA AYU</h2>
                    <p>Jalan Ki Bagus Rangin No. 01, Kelurahan Karanganyar, Kec. Indramayu, Kabupaten Indramayu, Jawa Barat 45214</p>
                    <p>Telepon: (0234) 274294 &bull; Faksimile: (0234) 274294 &bull; Pos-el: sekretariat@pdamtirtadarmaayu.co.id</p>
                </td>
            </tr>
        </table>
        <div class="kop-divider"></div>

        <!-- JUDUL & NOMOR SURAT -->
        <div class="doc-title-box">
            <div class="doc-title">SURAT DETAIL LAPORAN PENGADUAN PEGAWAI</div>
            <div class="doc-number">Nomor: {{ $pengaduan->nomor_pengaduan ?? ('PGD-' . $pengaduan->id) }}</div>
        </div>

        <!-- BAGIAN 1: INFORMASI POKOK PENGADUAN -->
        <div class="section-title">
            <span class="num">I</span> INFORMASI POKOK PENGADUAN
        </div>
        <div class="info-card">
            <div class="info-row">
                <span class="info-lbl">Nomor Registrasi</span>
                <span class="info-sep">:</span>
                <span class="info-val" style="font-family:'IBM Plex Mono', monospace; color:#0d2c6e;">
                    {{ $pengaduan->nomor_pengaduan }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Tanggal Pengaduan</span>
                <span class="info-sep">:</span>
                <span class="info-val">
                    {{ date('d F Y', strtotime($pengaduan->created_at ?? now())) }} Pukul {{ date('H:i', strtotime($pengaduan->created_at ?? now())) }} WIB
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Kategori Pelanggaran</span>
                <span class="info-sep">:</span>
                <span class="info-val">
                    <strong>{{ $pengaduan->kategori ?? 'Umum' }}</strong>
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Status Penanganan</span>
                <span class="info-sep">:</span>
                <span class="info-val">
                    <span class="badge-status" style="{{ $badgeStyle }}">
                        {{ $statusLabel }}
                    </span>
                </span>
            </div>
        </div>

        <!-- BAGIAN 2: IDENTITAS PELAPOR & TERLAPOR -->
        <div class="section-title">
            <span class="num">II</span> IDENTITAS PELAPOR & PIHAK TERLAPOR
        </div>
        <table class="table-custom" style="margin-bottom: 14px;">
            <thead>
                <tr>
                    <th style="width: 50%;">Identitas Pelapor</th>
                    <th style="width: 50%;">Identitas Pihak Terlapor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @if (!empty($pengaduan->anonim))
                            <div style="color:#b91c1c; font-weight:bold; margin-bottom:4px;">
                                🔒 RAHASIA (PELAPOR ANONIM)
                            </div>
                            <div style="font-size:9pt; color:#475569; font-style:italic;">
                                Identitas pelapor dilindungi penuh sesuai Peraturan Direksi & Sistem Pelaporan Pelanggaran (Whistleblowing System) Perumdam Tirta Darma Ayu.
                            </div>
                        @else
                            <div style="font-weight:bold; font-size:10.5pt; color:#0d2c6e; margin-bottom:2px;">
                                {{ $pengaduan->nama_pegawai ?? '-' }}
                            </div>
                            <div style="font-size:9.5pt; color:#334155; line-height:1.45;">
                                <div><strong>NIK:</strong> {{ $pengaduan->nik ?? '-' }}</div>
                                <div><strong>Golongan:</strong> {{ !empty($pengaduan->golongan) ? $pengaduan->golongan : '-' }}</div>
                                <div><strong>Unit Kerja/Cabang:</strong> {{ $pengaduan->cabang ?? '-' }}</div>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:bold; font-size:10.5pt; color:#0f172a; margin-bottom:2px;">
                            {{ $pengaduan->pihak_terlapor ?? '-' }}
                        </div>
                        <div style="font-size:9.5pt; color:#334155; line-height:1.45;">
                            <div><strong>NIK:</strong> {{ !empty($pengaduan->nik_pelaku) ? $pengaduan->nik_pelaku : '-' }}</div>
                            <div><strong>Jabatan:</strong> {{ !empty($pengaduan->jabatan_pelaku) ? $pengaduan->jabatan_pelaku : '-' }}</div>
                            <div><strong>Instansi:</strong> Perumda Air Minum Tirta Darma Ayu</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- BAGIAN 3: POKOK MASALAH & URAIAN KRONOLOGI -->
        <div class="section-title">
            <span class="num">III</span> POKOK PENGADUAN & URAIAN KRONOLOGI
        </div>
        <div class="narrative-box">
            <div style="font-family:'Plus Jakarta Sans', sans-serif; font-size:11pt; font-weight:800; color:#0d2c6e; margin-bottom:6px;">
                {{ $pengaduan->judul }}
            </div>
            <div style="font-size:10.5pt; color:#1e293b; line-height:1.6;">
                {{ $pengaduan->deskripsi }}
            </div>
        </div>

        <!-- BAGIAN 4: HASIL INVESTIGASI & PUTUSAN (JIKA ADA) -->
        @if (!empty($pengaduan->hasil_investigasi) || !empty($pengaduan->kesimpulan_investigasi) || !empty($pengaduan->nomor_surat_putusan))
            <div class="section-title">
                <span class="num">IV</span> HASIL PEMERIKSAAN / INVESTIGASI & SANKSI
            </div>
            <div class="info-card" style="border-left: 4px solid #0d2c6e; background:#f0f7ff;">
                @if (!empty($pengaduan->kesimpulan_investigasi))
                    <div class="info-row">
                        <span class="info-lbl">Kesimpulan Investigasi</span>
                        <span class="info-sep">:</span>
                        <span class="info-val">
                            @if (strtolower($pengaduan->kesimpulan_investigasi) === 'terbukti')
                                <span style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:2px 8px; border-radius:6px; font-weight:bold; font-size:9pt;">
                                    ⚖️ TERBUKTI
                                </span>
                            @else
                                <span style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; padding:2px 8px; border-radius:6px; font-weight:bold; font-size:9pt;">
                                    🛡️ TIDAK TERBUKTI
                                </span>
                            @endif
                        </span>
                    </div>
                @endif
                @if (!empty($pengaduan->petugas_investigasi))
                    <div class="info-row">
                        <span class="info-lbl">Tim / Petugas Investigator</span>
                        <span class="info-sep">:</span>
                        <span class="info-val">{{ $pengaduan->petugas_investigasi }}</span>
                    </div>
                @endif
                @if (!empty($pengaduan->hasil_investigasi))
                    <div class="info-row" style="margin-top:6px; display:block;">
                        <span class="info-lbl" style="display:block; margin-bottom:3px;">Fakta Temuan Investigasi:</span>
                        <div style="background:#fff; border:1px solid #cbd5e1; padding:8px 10px; border-radius:4px; font-size:9.5pt; white-space:pre-line;">
                            {{ $pengaduan->hasil_investigasi }}
                        </div>
                    </div>
                @endif
                @if (!empty($pengaduan->surat_rekomendasi))
                    <div class="info-row" style="margin-top:6px; display:block;">
                        <span class="info-lbl" style="display:block; margin-bottom:3px;">Rekomendasi Tindak Lanjut:</span>
                        <div style="background:#fff; border:1px solid #cbd5e1; padding:8px 10px; border-radius:4px; font-size:9.5pt; white-space:pre-line;">
                            {{ $pengaduan->surat_rekomendasi }}
                        </div>
                    </div>
                @endif
                @if (!empty($pengaduan->nomor_surat_putusan))
                    <div class="info-row" style="margin-top:8px;">
                        <span class="info-lbl">Surat Putusan Sanksi</span>
                        <span class="info-sep">:</span>
                        <span class="info-val">
                            Nomor: <strong>{{ $pengaduan->nomor_surat_putusan }}</strong> 
                            &bull; Jenis: <strong>{{ $pengaduan->jenis_sanksi ?? '-' }}</strong>
                            @if (!empty($pengaduan->tanggal_surat_putusan))
                                (Tanggal: {{ date('d F Y', strtotime($pengaduan->tanggal_surat_putusan)) }})
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        @endif

        <!-- BAGIAN 5: RIWAYAT DISPOSISI & AUDIT TRAIL -->
        <div class="section-title">
            <span class="num">{{ (!empty($pengaduan->hasil_investigasi) || !empty($pengaduan->kesimpulan_investigasi)) ? 'V' : 'IV' }}</span> RIWAYAT DISPOSISI & KRONOLOGI ALUR
        </div>
        <table class="audit-table">
            <thead>
                <tr>
                    <th style="width: 25px; text-align:center;">No</th>
                    <th style="width: 140px;">Waktu & Tanggal</th>
                    <th style="width: 150px;">Aksi / Tahapan</th>
                    <th style="width: 150px;">Oleh (Pejabat)</th>
                    <th>Catatan Disposisi / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayat as $idx => $r)
                    <tr>
                        <td style="text-align:center; font-weight:600;">{{ $idx + 1 }}</td>
                        <td style="font-family:'IBM Plex Mono', monospace; font-size:8.5pt;">
                            {{ date('d/m/Y H:i', strtotime($r->tanggal)) }} WIB
                        </td>
                        <td>
                            <strong style="color:#0d2c6e;">{{ $r->aksi }}</strong>
                        </td>
                        <td>
                            <strong>{{ $r->oleh }}</strong>
                            @if (!empty($r->role))
                                <span style="font-size:8pt; color:#64748b; display:block;">({{ strtoupper($r->role) }})</span>
                            @endif
                        </td>
                        <td style="line-height:1.35; color:#334155;">
                            {{ !empty($r->keterangan) ? $r->keterangan : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; color:#94a3b8; padding:10px;">Belum ada catatan riwayat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- BAGIAN 6: LAMPIRAN BERKAS & BUKTI -->
        <div class="section-title">
            <span class="num">{{ (!empty($pengaduan->hasil_investigasi) || !empty($pengaduan->kesimpulan_investigasi)) ? 'VI' : 'V' }}</span> DAFTAR LAMPIRAN BUKTI TERDAFTAR
        </div>
        @if (empty($lampiranList))
            <div style="font-size:9.5pt; color:#64748b; font-style:italic; padding:6px 0 10px 0;">
                Tidak ada berkas bukti atau foto yang dilampirkan pada laporan ini.
            </div>
        @else
            <div class="attach-grid">
                @foreach ($lampiranList as $item)
                    <div class="attach-card">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong style="font-family:'Plus Jakarta Sans', sans-serif; font-size:9pt; color:#0d2c6e;">
                                #{{ $item['no'] }}. {{ $item['label'] }}
                            </strong>
                            <span style="font-size:8pt; background:#e2e8f0; color:#334155; padding:1px 6px; border-radius:4px; font-weight:bold;">
                                {{ $item['type'] }}
                            </span>
                        </div>
                        <div style="font-size:8pt; color:#64748b; margin-top:2px;">
                            Kategori Berkas: <strong>{{ $item['folder'] }}</strong>
                        </div>
                        @if ($item['is_image'])
                            <img src="{{ $item['url'] }}" alt="{{ $item['label'] }}" class="attach-thumb" onerror="this.style.display='none';">
                        @else
                            <div style="margin-top:6px; font-size:8.5pt;">
                                📎 Berkas dokumen: <a href="{{ $item['url'] }}" target="_blank" style="color:#0284c7; word-break:break-all;">{{ basename($item['url']) }}</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- BAGIAN 7: LEMBAR PENGESAHAN & TANDA TANGAN -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="sig-city-date">&nbsp;</div>
                <div class="sig-role">
                    @if (!empty($pengaduan->anonim))
                        Tim Penanganan Pengaduan (KSPI)
                    @else
                        Pelapor / Pengadu
                    @endif
                </div>
                <div class="sig-name">
                    @if (!empty($pengaduan->anonim))
                        KOMITE SUBSTANSI PENGAWASAN
                    @else
                        {{ $pengaduan->nama_pegawai ?? '-' }}
                    @endif
                </div>
                <div class="sig-nik">
                    @if (!empty($pengaduan->anonim))
                        PDAM Tirta Darma Ayu
                    @else
                        NIK. {{ $pengaduan->nik ?? '-' }}
                    @endif
                </div>
            </div>

            <div class="signature-box right">
                <div class="sig-city-date">Indramayu, {{ date('d F Y') }}</div>
                <div class="sig-role">
                    Mengetahui,<br>
                    Direktur Utama
                </div>
                <div class="sig-name">
                    {{ $dirut->name ?? 'Dr. ADY SETIAWAN, S.H., M.H.' }}
                </div>
                <div class="sig-nik">
                    {{ !empty($dirut->nik) ? 'NIK. ' . $dirut->nik : 'PERUMDA AIR MINUM TIRTA DARMA AYU' }}
                </div>
            </div>
        </div>
    </div>

</body>
</html>
