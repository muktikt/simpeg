<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Audit Trail & Log Aktivitas - PERUMDAM Tirta Darma Ayu</title>
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
            font-size: 9.5px;
            line-height: 1.35;
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
        .btn-print:hover { background: #0369a1; }
        .btn-close {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-close:hover { background: #e2e8f0; }
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
            font-size: 13.5px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .header-title-box h3 {
            font-size: 12.5px;
            font-weight: bold;
            color: #0369a1;
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
            font-size: 9.5px;
            font-weight: bold;
            color: #1e293b;
        }
        table.audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 16px;
        }
        table.audit-table th,
        table.audit-table td {
            border: 1px solid #000000;
            padding: 4px 5px;
        }
        table.audit-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            color: #000;
        }
        table.audit-table td.col-num {
            text-align: center;
            white-space: nowrap;
        }
        table.audit-table td.col-waktu {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 8px;
        }
        table.audit-table td.col-nik {
            text-align: center;
            white-space: nowrap;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 8px;
        }
        table.audit-table td.col-nama {
            text-align: left;
            font-weight: 600;
        }
        table.audit-table td.col-role {
            text-align: center;
            white-space: nowrap;
        }
        table.audit-table td.col-action {
            text-align: center;
            white-space: nowrap;
            font-weight: bold;
        }
        table.audit-table td.col-module {
            text-align: center;
            white-space: nowrap;
        }
        table.audit-table td.col-desc {
            text-align: left;
            font-size: 8px;
        }
        table.audit-table td.col-device {
            text-align: left;
            white-space: nowrap;
            font-size: 7.5px;
            color: #475569;
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
            table.audit-table th {
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
            <span style="font-weight:700; color:#0f172a; font-size:13.5px;">Cetak Dokumen Kedinasan (Format Landscape A4)</span>
            <span style="background:#e0f2fe; color:#0369a1; font-size:11px; padding:2px 8px; border-radius:4px; font-weight:700;">
                {{ count($logs) }} Rekaman Aktivitas
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
            <h3>LAPORAN AUDIT TRAIL & LOG AKTIVITAS PENGGUNA SISTEM</h3>
            <p>REKAM JEJAK AUTENTIKASI DAN KEAMANAN SISTEM INFORMASI MANAJEMEN KEPEGAWAIAN</p>
        </div>

        <div class="sub-header">
            <div>
                KRITERIA FILTER : 
                @php
                    $filterDesc = [];
                    if (!empty($action) && $action !== 'all') $filterDesc[] = "Aksi: " . strtoupper($action);
                    if (!empty($module) && $module !== 'all') $filterDesc[] = "Modul: " . $module;
                    if (!empty($role) && $role !== 'all') $filterDesc[] = "Role: " . $role;
                    if (!empty($dateStart)) $filterDesc[] = "Dari: " . $dateStart;
                    if (!empty($dateEnd)) $filterDesc[] = "Sampai: " . $dateEnd;
                @endphp
                <strong>{{ count($filterDesc) > 0 ? implode(' | ', $filterDesc) : 'SEMUA AKTIVITAS TERBARU' }}</strong>
            </div>
            <div style="font-size:9px; font-weight:normal; color:#64748b;">
                Tanggal Unduh : &nbsp; {{ strtoupper(now()->translatedFormat('d F Y H:i:s')) }} WIB
            </div>
        </div>

        <table class="audit-table">
            <thead>
                <tr>
                    <th style="width: 3%;">NO.</th>
                    <th style="width: 12%;">WAKTU (WIB)</th>
                    <th style="width: 8%;">NIK</th>
                    <th style="width: 15%;">NAMA PENGGUNA</th>
                    <th style="width: 10%;">ROLE</th>
                    <th style="width: 7%;">AKSI</th>
                    <th style="width: 10%;">MODUL</th>
                    <th style="width: 25%;">DESKRIPSI AKTIVITAS</th>
                    <th style="width: 10%;">PERANGKAT</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $idx => $log)
                    @php
                        $waktu = \Illuminate\Support\Carbon::parse($log->created_at)->timezone('Asia/Jakarta');
                    @endphp
                    <tr>
                        <td class="col-num">{{ $idx + 1 }}</td>
                        <td class="col-waktu">{{ $waktu->format('d/m/Y H:i:s') }}</td>
                        <td class="col-nik">{{ $log->user_nik ?? '-' }}</td>
                        <td class="col-nama">{{ $log->user_name ?? '-' }}</td>
                        <td class="col-role">{{ $log->role ?? '-' }}</td>
                        <td class="col-action">{{ strtoupper($log->action ?? '-') }}</td>
                        <td class="col-module">{{ $log->module ?? '-' }}</td>
                        <td class="col-desc">{{ $log->description ?? '-' }}</td>
                        <td class="col-device">{{ Str::limit($log->device_info ?? '-', 22) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:20px; color:#64748b; font-style:italic;">
                            Tidak ada rekaman log aktivitas pengguna yang sesuai dengan kriteria filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

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

