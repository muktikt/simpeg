<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Payroll Minus Bank BJB - {{ $namaBulan }} {{ $tahun }}</title>
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
            color: #b91c1c;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .header-title-box p {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
        }
        .sub-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 10px;
            font-size: 10.5px;
            font-weight: bold;
            color: #1e293b;
        }
        table.payroll-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 24px;
        }
        table.payroll-table th,
        table.payroll-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
        }
        table.payroll-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            color: #000;
        }
        table.payroll-table td.col-num {
            text-align: center;
            white-space: nowrap;
        }
        table.payroll-table td.col-nik {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9.5px;
        }
        table.payroll-table td.col-rek {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9.5px;
        }
        table.payroll-table td.col-nama {
            text-align: left;
        }
        table.payroll-table td.col-unit {
            text-align: left;
            font-size: 9.5px;
            color: #334155;
        }
        table.payroll-table td.col-cur {
            text-align: right;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9.5px;
        }
        table.payroll-table tr.total-row td {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .text-minus {
            color: #b91c1c !important;
            font-weight: bold;
        }

        /* Signatures */
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
            table.payroll-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.payroll-table tr.total-row td {
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
            <span style="font-weight:700; color:#0f172a; font-size:13px;">Format Rekapitulasi Bank BJB (Payroll Minus)</span>
            <span style="background:#fee2e2; color:#b91c1c; font-size:11px; padding:2px 8px; border-radius:4px; font-weight:700;">
                {{ count($items) }} Pegawai Tidak Dapat Diproses Transfer Otomatis
            </span>
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="window.print()" class="btn btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Format Bank BJB
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
            <h3>LAPORAN GAJI BANK BJB (PAYROLL MINUS)</h3>
            <p>PERIODE : {{ strtoupper($namaBulan) }} {{ $tahun }}</p>
        </div>

        <div class="sub-header">
            <div>UNIT KERJA : {{ strtoupper($unitKerja === 'all' || empty($unitKerja) ? 'SEMUA UNIT KERJA' : $unitKerja) }}</div>
            <div style="font-size:9.5px; font-weight:normal; color:#64748b;">Tanggal: {{ now()->translatedFormat('d F Y') }}</div>
        </div>

        @php
            $sumDefisit = 0;
        @endphp

        <table class="payroll-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO.</th>
                    <th style="width: 15%;">NIK PEGAWAI</th>
                    <th style="width: 18%;">NO REKENING BJB</th>
                    <th style="width: 32%;">NAMA PEGAWAI</th>
                    <th style="width: 15%;">UNIT KERJA</th>
                    <th style="width: 15%;">GAJI DITERIMA</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $r)
                    @php
                        $sumDefisit += (float) ($r['gaji_diterima'] ?? 0);
                        // Format NIK berjarak (00 00 0000) seperti format Bank BJB
                        $rawNik = preg_replace('/\s+/', '', (string)($r['nik'] ?? ''));
                        $formattedNik = strlen($rawNik) >= 8 
                            ? substr($rawNik, 0, 2) . ' ' . substr($rawNik, 2, 2) . ' ' . substr($rawNik, 4) 
                            : $r['nik'];
                    @endphp
                    <tr>
                        <td class="col-num">{{ $idx + 1 }}</td>
                        <td class="col-nik">{{ $formattedNik }}</td>
                        <td class="col-rek">{{ $r['no_rek'] ?? '-' }}</td>
                        <td class="col-nama"><b>{{ $r['nama'] }}</b></td>
                        <td class="col-unit">{{ $r['unit_kerja'] ?? '-' }}</td>
                        <td class="col-cur text-minus">
                            ({{ number_format(abs($r['gaji_diterima'] ?? 0), 0, ',', '.') }})
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:#64748b; font-style:italic;">
                            Semua gaji pegawai pada periode ini bernilai positif (Tidak ada payroll minus).
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" style="text-align:center; padding:7px;">TOTAL GAJI DITERIMA (DEFISIT KAS)</td>
                    <td class="col-cur text-minus" style="font-weight:bold; font-size:10.5px;">
                        ({{ number_format(abs($sumDefisit), 0, ',', '.') }})
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- Dynamic Official Signatures --}}
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
                        <div style="font-weight:bold; margin-top:2px;">{{ strtoupper($pejabatTtd['pembuat']['jabatan'] ?? 'MANAJER KEUANGAN') }}</div>
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

