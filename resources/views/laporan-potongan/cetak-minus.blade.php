<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Potongan Kas Minus - {{ $namaBulan }} {{ $tahun }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
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
            font-size: 10px;
            line-height: 1.3;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print-bar {
            max-width: 1100px;
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
        .btn-print:hover {
            background: #0369a1;
        }
        .btn-close {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-close:hover {
            background: #e2e8f0;
        }
        .print-sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px 28px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header-title-box {
            text-align: center;
            margin-bottom: 14px;
        }
        .header-title-box h2 {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .header-title-box h3 {
            font-size: 12px;
            font-weight: bold;
            color: #b91c1c;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .header-title-box p {
            font-size: 10px;
            font-weight: bold;
            color: #334155;
        }
        .sub-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 8px;
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 18px;
        }
        table.report-table th,
        table.report-table td {
            border: 1px solid #000000;
            padding: 4px 3px;
        }
        table.report-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            color: #000;
        }
        table.report-table td.col-num {
            text-align: center;
            white-space: nowrap;
        }
        table.report-table td.col-nik {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 8px;
        }
        table.report-table td.col-nama {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }
        table.report-table td.col-cur {
            text-align: right;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 8px;
        }
        table.report-table tr.total-row td {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .text-minus {
            color: #b91c1c !important;
            font-weight: bold;
        }

        /* Signatures block */
        .ttd-container {
            margin-top: 18px;
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
            font-size: 9.5px;
            vertical-align: top;
            padding: 2px 4px;
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
            table.report-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.report-table tr.total-row td {
                background-color: #f1f5f9 !important;
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
            <span style="font-weight:700; color:#0f172a; font-size:13.5px;">Cetak Dokumen Kedinasan (Format Landscape A4)</span>
            <span style="background:#fee2e2; color:#b91c1c; font-size:11px; padding:2px 8px; border-radius:4px; font-weight:700;">
                {{ count($items) }} Pegawai Terdeteksi Minus
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
            <h3>LAPORAN POTONGAN KAS (MINUS)</h3>
            <p>PERIODE : {{ strtoupper($namaBulan) }} {{ $tahun }}</p>
        </div>

        <div class="sub-header">
            <div>UNIT KERJA : {{ strtoupper($unitKerja === 'all' || empty($unitKerja) ? 'SEMUA UNIT KERJA (REKAP KESELURUHAN)' : $unitKerja) }}</div>
            <div style="font-size:9px; font-weight:normal; color:#64748b;">Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</div>
        </div>

        @php
            $sumPendapatan = 0;
            $sumTotals = array_fill_keys($kolom, 0);
            $sumPotKeu = 0;
            $sumDefisit = 0;
        @endphp

        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 2%;">NO.</th>
                    <th style="width: 5.5%;">NIK</th>
                    <th style="width: 14%;">NAMA PEGAWAI</th>
                    <th style="width: 7.5%;">PEND. DITERIMA</th>
                    <th style="width: 5.8%;">KOPERASI</th>
                    <th style="width: 5.5%;">DARMA WANITA</th>
                    <th style="width: 5%;">AIR</th>
                    <th style="width: 5%;">KAS</th>
                    <th style="width: 6.5%;">BJB</th>
                    <th style="width: 5.8%;">BJBS</th>
                    <th style="width: 5.5%;">ASURANSI</th>
                    <th style="width: 5.5%;">BTN</th>
                    <th style="width: 5.5%;">ZAKAT PROFESI</th>
                    <th style="width: 5.8%;">BPR</th>
                    <th style="width: 7.5%;">JML. POT. KEU</th>
                    <th style="width: 7.5%;">GAJI DITERIMA</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $r)
                    @php
                        $sumPendapatan += (float) ($r['pendapatan_diterima'] ?? 0);
                        foreach ($kolom as $k) {
                            $sumTotals[$k] += (float) ($r[$k] ?? 0);
                        }
                        $sumPotKeu += (float) ($r['total_potongan_keu'] ?? 0);
                        $sumDefisit += (float) ($r['gaji_diterima'] ?? 0);
                    @endphp
                    <tr>
                        <td class="col-num">{{ $idx + 1 }}</td>
                        <td class="col-nik">{{ $r['nik'] }}</td>
                        <td class="col-nama" title="{{ $r['nama'] }}">{{ $r['nama'] }}</td>
                        <td class="col-cur">{{ number_format($r['pendapatan_diterima'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_koperasi'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_darmawanita'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_air'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_kas'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_bjb'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_bjbs'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_asuransi'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_btn'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_zakat_profesi'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur">{{ number_format($r['pot_bpr'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur" style="font-weight:600;">{{ number_format($r['total_potongan_keu'] ?? 0, 0, ',', '.') }}</td>
                        <td class="col-cur text-minus">
                            ({{ number_format(abs($r['gaji_diterima'] ?? 0), 0, ',', '.') }})
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" style="text-align:center; padding:18px; color:#64748b; font-style:italic;">
                            Tidak ada pegawai yang mengalami potongan/gaji minus pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align:center;">TOTAL</td>
                    <td class="col-cur">{{ number_format($sumPendapatan, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_koperasi'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_darmawanita'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_air'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_kas'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_bjb'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_bjbs'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_asuransi'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_btn'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_zakat_profesi'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur">{{ number_format($sumTotals['pot_bpr'] ?? 0, 0, ',', '.') }}</td>
                    <td class="col-cur" style="font-weight:bold;">{{ number_format($sumPotKeu, 0, ',', '.') }}</td>
                    <td class="col-cur text-minus" style="font-weight:bold;">
                        ({{ number_format(abs($sumDefisit), 0, ',', '.') }})
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Pejabat Penandatangan Resmi (Dinamis dari tbl_ttd) --}}
        <div class="ttd-container">
            <table class="ttd-table">
                <tr>
                    <td style="width: 32%;">
                        <div>{{ $pejabatTtd['mengetahui']['label'] ?? 'MENGETAHUI,' }}</div>
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['mengetahui']['jabatan'] ?? 'DIREKTUR UTAMA') }}</div>
                    </td>
                    <td style="width: 34%;">
                        <div>{{ $pejabatTtd['menyetujui']['label'] ?? 'DISETUJUI,' }}</div>
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['menyetujui']['jabatan'] ?? 'DIREKTUR UMUM') }}</div>
                    </td>
                    <td style="width: 34%;">
                        <div>{{ $pejabatTtd['kota'] ?? 'Indramayu' }}, {{ now()->translatedFormat('d F Y') }}</div>
                        <div>{{ $pejabatTtd['pembuat']['label'] ?? 'DIBUAT OLEH,' }}</div>
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['pembuat']['jabatan'] ?? 'MANAJER KEUANGAN') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="height: 55px;"></td>
                    <td style="height: 55px;"></td>
                    <td style="height: 55px;"></td>
                </tr>
                <tr>
                    <td>
                        <div style="font-weight:bold; text-decoration:underline;">{{ $pejabatTtd['mengetahui']['nama'] ?? '-' }}</div>
                        <div style="font-size:8.5px; color:#334155;">NIK. {{ $pejabatTtd['mengetahui']['nik'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div style="font-weight:bold; text-decoration:underline;">{{ $pejabatTtd['menyetujui']['nama'] ?? '-' }}</div>
                        <div style="font-size:8.5px; color:#334155;">NIK. {{ $pejabatTtd['menyetujui']['nik'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div style="font-weight:bold; text-decoration:underline;">{{ $pejabatTtd['pembuat']['nama'] ?? '-' }}</div>
                        <div style="font-size:8.5px; color:#334155;">NIK. {{ $pejabatTtd['pembuat']['nik'] ?? '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>

