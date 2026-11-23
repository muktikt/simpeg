<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Sanksi Per Pegawai - {{ $targetPegawai['nama'] ?? 'Pegawai' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #f8fafc;
            padding: 20px 10px;
            font-size: 11px;
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .btn {
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 12.5px;
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
            background: #0284c7;
            color: #ffffff;
        }
        .btn-print:hover { background: #0369a1; }
        .btn-close {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-close:hover { background: #e2e8f0; }
        .print-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 35px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header-title-box {
            text-align: center;
            margin-bottom: 20px;
        }
        .header-title-box h2 {
            font-size: 13.5px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .header-title-box h3 {
            font-size: 12.5px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .header-title-box p {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 18px;
        }
        .meta-table td {
            padding: 3px 4px;
            border: none;
        }
        table.sanksi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 16px;
        }
        table.sanksi-table th,
        table.sanksi-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
        }
        table.sanksi-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            color: #000;
        }
        table.sanksi-table td.col-num {
            text-align: center;
            white-space: nowrap;
        }
        table.sanksi-table td.col-bulan {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9.5px;
        }
        table.sanksi-table td.col-jenis {
            text-align: left;
            font-weight: 600;
        }
        table.sanksi-table td.col-ket {
            text-align: left;
        }
        table.sanksi-table td.col-pot {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-weight: bold;
        }
        .catatan-box {
            font-size: 10px;
            color: #1e293b;
            margin-bottom: 24px;
            page-break-inside: avoid;
        }

        /* Signatures block */
        .ttd-container {
            margin-top: 24px;
            width: 100%;
            page-break-inside: avoid;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .ttd-table td {
            border: none;
            text-align: center;
            font-size: 10px;
            vertical-align: top;
            padding: 3px 6px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .print-sheet {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            table.sanksi-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    {{-- Control Bar --}}
    <div class="no-print-bar">
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-weight:700; color:#0f172a; font-size:13px;">Format Rekapitulasi Sanksi Per Pegawai (A4 Portrait)</span>
            <span style="background:#fee2e2; color:#b91c1c; font-size:11px; padding:2px 8px; border-radius:4px; font-weight:700;">
                {{ count($filtered) }} Riwayat Sanksi
            </span>
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="window.print()" class="btn btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak ke Printer / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-close">
                Tutup Jendela
            </button>
        </div>
    </div>

    {{-- Main Sheet --}}
    <div class="print-sheet">
        <div class="header-title-box">
            <h2>PERUMDAM TIRTA DARMA AYU KAB. INDRAMAYU</h2>
            <h3>LAPORAN SANKSI PER PEGAWAI</h3>
            <p>PERIODE TAHUN : {{ $tahun === 'all' || empty($tahun) ? 'SEMUA TAHUN' : $tahun }}</p>
        </div>

        @php
            $pNama = $targetPegawai['nama'] ?? ($filtered->first()['nama_lengkap'] ?? 'Pegawai');
            $pNik = $targetPegawai['nik'] ?? ($filtered->first()['formatted_nik'] ?? '-');
            $pUnit = $targetPegawai['unit_kerja'] ?? ($filtered->first()['unit_kerja'] ?? '-');
            $pJabatan = $targetPegawai['jabatan'] ?? ($filtered->first()['jabatan'] ?? '-');
        @endphp

        <table class="meta-table">
            <tr>
                <td style="width: 18%; font-weight:bold;">N I K</td>
                <td style="width: 2%;">:</td>
                <td style="width: 80%; font-family:'IBM Plex Mono', monospace; font-weight:bold;">{{ $pNik }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Nama Pegawai</td>
                <td>:</td>
                <td><strong>{{ strtoupper($pNama) }}</strong></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Unit Kerja</td>
                <td>:</td>
                <td>{{ $pUnit }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Jabatan</td>
                <td>:</td>
                <td>{{ $pJabatan }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Tgl. Cetak</td>
                <td>:</td>
                <td>{{ strtoupper(now()->translatedFormat('d-m-Y')) }}</td>
            </tr>
        </table>

        <table class="sanksi-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO.</th>
                    <th style="width: 18%;">BULAN SANKSI</th>
                    <th style="width: 25%;">JENIS SANKSI</th>
                    <th style="width: 42%;">KETERANGAN SANKSI</th>
                    <th style="width: 10%;">POT %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filtered as $idx => $s)
                    <tr>
                        <td class="col-num">{{ $idx + 1 }}</td>
                        <td class="col-bulan">{{ strtoupper($s['bulan_teks']) }}</td>
                        <td class="col-jenis">
                            {{ $s['jenis_sanksi'] }}
                            <div style="font-size:8.5px; color:#64748b; font-weight:normal;">(Tingkat {{ $s['tingkat'] }})</div>
                        </td>
                        <td class="col-ket">
                            @if (!empty($s['nomor_sk']) && $s['nomor_sk'] !== '-')
                                <strong>SK: {{ $s['nomor_sk'] }}</strong> &bull; 
                            @endif
                            {{ $s['keterangan'] }}
                        </td>
                        <td class="col-pot">
                            {{ $s['potongan_persen'] > 0 ? $s['potongan_persen'] . '%' : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:18px; color:#64748b; font-style:italic;">
                            Pegawai ini tidak memiliki catatan sanksi pada periode yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="catatan-box">
            <b>Catatan:</b> &nbsp;POT % adalah jumlah besaran persentase untuk potongan pendapatan perusahaan yang dieksekusi melalui pemotongan gaji kedinasan.
        </div>

        {{-- Dynamic Signatures Block from tbl_ttd kode 'gaji' / SDM --}}
        <div class="ttd-container">
            <table class="ttd-table">
                <tr>
                    <td style="width: 33%;">
                        <div>{{ $pejabatTtd['mengetahui']['label'] ?? 'MENGETAHUI,' }}</div>
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['mengetahui']['jabatan'] ?? 'DIREKTUR UTAMA') }}</div>
                    </td>
                    <td style="width: 33%;">
                        <div>{{ $pejabatTtd['menyetujui']['label'] ?? 'DISETUJUI,' }}</div>
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['menyetujui']['jabatan'] ?? 'DIREKTUR UMUM') }}</div>
                    </td>
                    <td style="width: 34%;">
                        <div>{{ $pejabatTtd['kota'] ?? 'Indramayu' }}, {{ now()->translatedFormat('d F Y') }}</div>
                        <div>{{ $pejabatTtd['pembuat']['label'] ?? 'DIBUAT OLEH,' }}</div>
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['pembuat']['jabatan'] ?? 'MANAJER SUMBER DAYA MANUSIA') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="height: 60px;"></td>
                    <td style="height: 60px;"></td>
                    <td style="height: 60px;"></td>
                </tr>
                <tr>
                    <td>
                        <div style="font-weight:bold; text-decoration:underline;">{{ $pejabatTtd['mengetahui']['nama'] ?? '-' }}</div>
                        <div style="font-size:9px; color:#334155;">NIK. {{ $pejabatTtd['mengetahui']['nik'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div style="font-weight:bold; text-decoration:underline;">{{ $pejabatTtd['menyetujui']['nama'] ?? '-' }}</div>
                        <div style="font-size:9px; color:#334155;">NIK. {{ $pejabatTtd['menyetujui']['nik'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div style="font-weight:bold; text-decoration:underline;">{{ $pejabatTtd['pembuat']['nama'] ?? '-' }}</div>
                        <div style="font-size:9px; color:#334155;">NIK. {{ $pejabatTtd['pembuat']['nik'] ?? '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>

