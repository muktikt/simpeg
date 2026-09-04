<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $doc['judul'] ?? 'Dokumen Surat Pegawai' }} - {{ $pegawai['nama'] ?? 'Pegawai' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #111827;
            background: #F1F5F9;
            padding: 30px 15px;
            font-size: 13pt;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            border: 1px solid #CBD5E1;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
            gap: 6px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .btn-print {
            background: #0D2C6E;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #091F4E;
        }
        .btn-back {
            background: #F8FAFC;
            border-color: #CBD5E1;
            color: #334155;
        }
        .btn-back:hover {
            background: #E2E8F0;
        }
        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 50px;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: relative;
            min-height: 1050px;
        }
        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .kop-logo {
            width: 85px;
            text-align: center;
            vertical-align: middle;
        }
        .kop-logo-box {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            border: 2px solid #0D2C6E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 18px;
            color: #0D2C6E;
            background: #F0F7FF;
            margin: 0 auto;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }
        .kop-text h3 {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .kop-text h2 {
            font-size: 16pt;
            font-weight: bold;
            color: #0D2C6E;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .kop-text p {
            font-size: 9pt;
            font-family: Arial, sans-serif;
            color: #334155;
            margin-bottom: 0;
            line-height: 1.3;
        }
        .kop-divider {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 4px;
            margin: 8px 0 24px 0;
        }
        /* Document Body */
        .doc-title-box {
            text-align: center;
            margin-bottom: 24px;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-size: 11pt;
            margin-top: 4px;
            font-family: 'IBM Plex Mono', monospace;
        }
        .doc-about {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .content-para {
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 14px;
            line-height: 1.6;
        }
        .meta-table {
            width: 100%;
            margin: 14px 0 20px 0;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 8px;
            vertical-align: top;
            font-size: 12pt;
        }
        .meta-table td.label {
            width: 170px;
            font-weight: 600;
        }
        .meta-table td.sep {
            width: 15px;
            text-align: center;
        }
        .meta-table td.val {
            font-weight: bold;
        }
        .legal-list {
            margin-left: 20px;
            margin-bottom: 14px;
            text-align: justify;
            line-height: 1.5;
        }
        .legal-list li {
            margin-bottom: 6px;
            padding-left: 6px;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 280px;
            text-align: center;
        }
        .signature-date {
            margin-bottom: 8px;
        }
        .signature-role {
            font-weight: bold;
            margin-bottom: 65px;
            text-transform: uppercase;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .signature-nik {
            font-size: 10.5pt;
            font-family: Arial, sans-serif;
            color: #475569;
        }
        .stamp-mark {
            position: absolute;
            right: 170px;
            bottom: 60px;
            width: 100px;
            height: 100px;
            border: 2px dashed #DC2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #DC2626;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            transform: rotate(-15deg);
            opacity: 0.75;
            pointer-events: none;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 70pt;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            color: rgba(13, 44, 110, 0.04);
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
        }
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
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>{{ $doc['kategori'] === 'Diklat' ? 'Sertifikat Diklat' : 'Surat Keputusan (SK)' }}</strong> &bull;
            <span style="color:#64748B;">{{ $pegawai['nama'] }} (NIK: {{ $pegawai['nik'] }})</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('dokumen-surat.index') }}" class="btn btn-back">
                &larr; Kembali
            </a>
            <button type="button" class="btn btn-print" onclick="window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <div class="sheet">
        <div class="watermark">TIRTA DARMA AYU</div>

        <!-- KOP SURAT -->
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <div class="kop-logo-box">PDAM</div>
                </td>
                <td class="kop-text">
                    <h3>PEMERINTAH KABUPATEN INDRAMAYU</h3>
                    <h2>PERUMDA AIR MINUM TIRTA DARMA AYU</h2>
                    <p>Jalan Ki Bagus Rangin No. 01, Kelurahan Karanganyar, Kec. Indramayu, Kabupaten Indramayu, Jawa Barat 45214</p>
                    <p>Telepon: (0234) 272183 | Fax: (0234) 274381 | Email: sekretariat@tirtadarmaayu.co.id</p>
                </td>
            </tr>
        </table>
        <div class="kop-divider"></div>

        <!-- JUDUL & NOMOR -->
        <div class="doc-title-box">
            <div class="doc-title">
                {{ $doc['kategori'] === 'Diklat' ? 'SURAT KETERANGAN / SERTIFIKAT PELATIHAN' : 'SURAT KEPUTUSAN DIREKSI' }}
            </div>
            <div class="doc-number">Nomor : {{ $doc['nomor'] }}</div>
            <div class="doc-about">
                TENTANG :<br>
                {{ strtoupper($doc['judul']) }}
            </div>
        </div>

        <!-- ISI KEPUTUSAN / SURAT -->
        @if ($doc['kategori'] === 'Diklat')
            <p class="content-para">
                Direksi Perumda Air Minum Tirta Darma Ayu Kabupaten Indramayu, berdasarkan hasil evaluasi program Pendidikan, Pelatihan dan Pengembangan Kompetensi SDM, dengan ini menerangkan secara resmi bahwa:
            </p>

            <table class="meta-table">
                <tr>
                    <td class="label">Nama Pegawai</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['nama'] }}</td>
                </tr>
                <tr>
                    <td class="label">Nomor Induk (NIK)</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['nik'] }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan Saat Ini</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['jabatan'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Unit Kerja / Cabang</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['unit_kerja'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Program Diklat</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $doc['judul'] }}</td>
                </tr>
            </table>

            <p class="content-para">
                Telah dinyatakan <strong>LULUS DAN MEMENUHI SYARAT KOMPETENSI</strong> dalam program Pelatihan & Pengembangan Manajemen Kepegawaian yang diselenggarakan oleh Manajemen SDM Perumda Air Minum Tirta Darma Ayu.
            </p>
            <p class="content-para">
                Demikian Surat Keterangan / Sertifikat ini diterbitkan untuk dipergunakan sebagaimana mestinya sebagai bukti kelayakan portofolio dan jenjang karir kepegawaian.
            </p>
        @else
            <p class="content-para">
                Direksi Perumda Air Minum Tirta Darma Ayu Kabupaten Indramayu, menimbang kebutuhan organisasi dan peningkatan kinerja pelayanan prima kepada masyarakat, dengan ini:
            </p>

            <div style="font-weight:bold; text-align:center; margin:14px 0 10px 0; letter-spacing:1px;">MEMUTUSKAN :</div>

            <table class="meta-table">
                <tr>
                    <td class="label">Menetapkan Kepada</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['nama'] }}</td>
                </tr>
                <tr>
                    <td class="label">Nomor Induk (NIK)</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['nik'] }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan / Amanah</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['jabatan'] ?? 'Staf' }}</td>
                </tr>
                <tr>
                    <td class="label">Penempatan Kerja</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['unit_kerja'] ?? 'Kantor Pusat' }}</td>
                </tr>
                <tr>
                    <td class="label">Status Kepegawaian</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pegawai['status_peg'] ?? 'Pegawai Tetap' }}</td>
                </tr>
            </table>

            <ol class="legal-list">
                <li><strong>KESATU</strong> : Memberikan kewenangan, hak, serta kewajiban kedinasan sesuai dengan jabatan dan penempatan kerja yang telah ditetapkan.</li>
                <li><strong>KEDUA</strong> : Menugaskan kepada pegawai yang bersangkutan untuk melaksanakan tugas dengan penuh tanggung jawab, integritas, dan disiplin tinggi demi kemajuan perusahaan.</li>
                <li><strong>KETIGA</strong> : Surat Keputusan ini berlaku terhitung mulai tanggal penetapan dengan ketentuan apabila di kemudian hari terdapat kekeliruan, akan diperbaiki sebagaimana mestinya.</li>
            </ol>
        @endif

        <!-- TANDA TANGAN -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-date">
                    Ditetapkan di Indramayu<br>
                    Pada tanggal: {{ \Carbon\Carbon::parse($doc['tgl_terbit'] ?? date('Y-m-d'))->translatedFormat('d F Y') }}
                </div>
                <div class="signature-role">
                    DIREKSI PERUMDA AIR MINUM<br>
                    TIRTA DARMA AYU
                </div>
                <div class="signature-name">
                    {{ session('simpeg_user.nama_peg', 'Nurpan, S.E., M.Si.') }}
                </div>
                <div class="signature-nik">
                    Direktur Utama / Pembina SDM
                </div>
            </div>
        </div>

        <div class="stamp-mark">
            TIRTA DARMA AYU<br>VERIFIED OFFICIAL
        </div>
    </div>

</body>
</html>
