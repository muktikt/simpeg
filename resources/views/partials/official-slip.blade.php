{{-- 
    OFFICIAL PERUMDAM SALARY SLIP COMPONENT (MATCHING GAMBAR 2)
    Used across: Gaji Bulanan, THR, Gaji 13 / Tunj. Pendidikan, Insentif
    Compatible with Screen Display & Print (A4 Portrait)
--}}
@php
    $nik = $nik ?? ($data['nik'] ?? $data['p_nik'] ?? '-');
    $nama = strtoupper($nama ?? ($data['nama'] ?? $data['p_name'] ?? $data['name'] ?? '-'));
    $unitKerja = strtoupper($unitKerja ?? ($data['unit_kerja'] ?? $data['p_unit_kerja'] ?? '-'));
    $jabatan = strtoupper($jabatan ?? ($data['jabatan'] ?? $data['p_jabatan'] ?? 'Pegawai'));
    
    // Format Golongan, Masa Kerja, dan Status Keluarga seperti di Gambar 2
    if (!isset($golonganText)) {
        $rawGol = $data['golongan'] ?? $data['p_golongan'] ?? '';
        $golCode = '';
        if (preg_match('/([A-Da-d][\.\/\-_]?[1-4]|[I|V|X]+([\.\/\-_][a-e1-4])?)/i', $rawGol, $m)) {
            $cleaned = strtoupper(str_replace(['-', '_', '/'], '.', $m[0]));
            $golCode = 'GOL. ' . $cleaned;
        } elseif (!empty($rawGol)) {
            $golCode = strtoupper($rawGol);
            if (!str_starts_with($golCode, 'GOL')) {
                $golCode = 'GOL. ' . $golCode;
            }
        } else {
            $golCode = 'GOL. -';
        }

        $masaKerja = '';
        if (!empty($data['masa_kerja'])) {
            $mk = strtoupper(trim($data['masa_kerja']));
            if (!str_starts_with($mk, 'MASA KERJA')) {
                $mk = 'MASA KERJA ' . $mk;
            }
            $masaKerja = $mk;
        } elseif (!empty($data['tgl_masuk']) && $data['tgl_masuk'] !== date('Y-m-d')) {
            try {
                $diff = \Carbon\Carbon::parse($data['tgl_masuk'])->diff(\Carbon\Carbon::now());
                $masaKerja = "MASA KERJA {$diff->y} THN, {$diff->m} BLN";
            } catch (\Throwable $e) {}
        }

        $ptkp = strtoupper(trim($data['kode_ptkp'] ?? 'TK'));
        $keluarga = '';
        if (isset($data['istri']) || isset($data['anak'])) {
            $istri = (int) ($data['istri'] ?? 0);
            $anak = (int) ($data['anak'] ?? 0);
            if ($istri > 0) {
                $keluarga = "ISTRI {$istri} ANAK {$anak}";
            } elseif ($anak > 0) {
                $keluarga = "TK ANAK {$anak}";
            } else {
                $keluarga = "TK";
            }
        } elseif (str_starts_with($ptkp, 'K/')) {
            $anak = substr($ptkp, 2);
            $keluarga = "ISTRI 1 ANAK " . ($anak !== '' ? $anak : '0');
        } elseif (str_starts_with($ptkp, 'K')) {
            $anak = substr($ptkp, 1);
            $keluarga = "ISTRI 1 ANAK " . ($anak !== '' ? $anak : '0');
        } elseif (str_starts_with($ptkp, 'TK/')) {
            $anak = substr($ptkp, 3);
            $keluarga = ($anak > 0) ? "TK ANAK {$anak}" : "TK";
        } elseif (str_starts_with($ptkp, 'TK')) {
            $anak = substr($ptkp, 2);
            $keluarga = ($anak > 0) ? "TK ANAK {$anak}" : "TK";
        } else {
            $keluarga = $ptkp ?: 'TK';
        }

        $golonganText = $golCode;
        if (!empty($masaKerja)) {
            $golonganText .= ' - ' . $masaKerja;
        }
        if (!empty($keluarga)) {
            $golonganText .= ' / ' . $keluarga;
        }
    }

    if (!isset($pendapatanRows)) {
        $pendapatanRows = [
            ['label' => 'GAPOK', 'value' => $data['gapok'] ?? 0],
            ['label' => 'TUNJANGAN ISTRI', 'value' => $data['tunjangan_istri'] ?? 0],
            ['label' => 'TUNJANGAN ANAK', 'value' => $data['tunjangan_anak'] ?? 0],
            ['label' => 'TUNJANGAN JABATAN', 'value' => $data['tunjangan_jabatan'] ?? 0],
            ['label' => 'TUNJANGAN PRESTASI', 'value' => $data['tunjangan_prestasi'] ?? 0],
            ['label' => 'TUNJANGAN TRANSPORTASI', 'value' => $data['tunjangan_transport'] ?? $data['tunjangan_transportasi'] ?? 0],
            ['label' => 'TUNJANGAN PANGAN', 'value' => $data['tunjangan_pangan'] ?? 0],
            ['label' => 'TUNJANGAN BPJS KESEHATAN', 'value' => $data['tunjangan_bpjskes'] ?? $data['tunjangan_bpjs_kesehatan'] ?? 0],
            ['label' => 'TUNJANGAN PERUMAHAN', 'value' => $data['tunjangan_perumahan'] ?? 0],
            ['label' => 'TUNJANGAN BPJS TENAGA KERJA', 'value' => $data['tunjangan_bpjstk'] ?? $data['tunjangan_bpjs_tenaga_kerja'] ?? 0],
            ['label' => 'TUNJANGAN PERUSAHAAN', 'value' => $data['tunjangan_perusahaan'] ?? 0],
            ['label' => 'LEMBUR', 'value' => $data['lembur'] ?? 0],
            ['label' => 'TUNJANGAN PAJAK', 'value' => $data['tunjangan_pajak'] ?? 0],
            ['label' => 'TUNJANGAN AIR MINUM', 'value' => $data['tunjangan_airminum'] ?? $data['tunjangan_air_minum'] ?? 0],
            ['label' => 'TUNJANGAN KOMUNIKASI', 'value' => $data['tunjangan_komunikasi'] ?? 0],
        ];
    }

    if (!isset($potonganPendapatanRows)) {
        $potonganPendapatanRows = [
            ['label' => 'POTONGAN PMI / LAIN-LAIN', 'value' => $data['potongan_lain'] ?? $data['potongan_trandist_pmi_lain'] ?? $data['potongan_sanksi'] ?? 0],
            ['label' => 'POTONGAN DAPENMA', 'value' => $data['potongan_dapenma'] ?? 0],
            ['label' => 'POTONGAN BPJS TENAGA KERJA', 'value' => $data['potongan_bpjstk'] ?? $data['potongan_bpjs_tenaga_kerja'] ?? 0],
            ['label' => 'POTONGAN PERUMAHAN', 'value' => $data['potongan_perumahan'] ?? 0],
            ['label' => 'POTONGAN TUNJANGAN PERUSAHAAN', 'value' => $data['potongan_tperusahaan'] ?? $data['potongan_tunjangan_perusahaan'] ?? 0],
            ['label' => 'POTONGAN KORPRI', 'value' => $data['potongan_korpri'] ?? 0],
            ['label' => 'POTONGAN PAJAK', 'value' => $data['potongan_pajak'] ?? 0],
            ['label' => 'POTONGAN BPJS KESEHATAN', 'value' => $data['potongan_bpjskes'] ?? $data['potongan_bpjs_kesehatan'] ?? 0],
        ];
    }

    if (!isset($potonganNonPendapatanRows)) {
        $potonganNonPendapatanRows = [
            ['label' => 'POTONGAN KOPERASI', 'value' => $data['potongan_koperasi'] ?? 0],
            ['label' => 'POTONGAN DARMA WANITA', 'value' => $data['potongan_darmawanita'] ?? $data['potongan_darma_wanita'] ?? 0],
            ['label' => 'POTONGAN REKENING AIR MINUM', 'value' => $data['potongan_ledeng'] ?? $data['potongan_rekening_air_minum'] ?? 0],
            ['label' => 'POTONGAN KAS', 'value' => $data['potongan_kas'] ?? 0],
            ['label' => 'POTONGAN BANK BJB', 'value' => $data['potongan_bjb'] ?? $data['potongan_bank_bjb'] ?? 0],
            ['label' => 'POTONGAN BANK BJBS', 'value' => $data['potongan_bjbs'] ?? $data['potongan_bank_bjbs'] ?? 0],
            ['label' => 'POTONGAN BANK BTN', 'value' => $data['potongan_btn'] ?? $data['potongan_bank_btn'] ?? 0],
            ['label' => 'POTONGAN BANK BPR', 'value' => $data['potongan_bpr'] ?? $data['potongan_bank_bpr'] ?? 0],
            ['label' => 'POTONGAN ASURANSI/LAINNYA', 'value' => $data['potongan_asuransi'] ?? 0],
            ['label' => 'POTONGAN ZAKAT RAMADHAN', 'value' => $data['potongan_zakat'] ?? $data['potongan_zakat_profesi'] ?? 0],
        ];
    }

    $totalPendapatan = $totalPendapatan ?? ($data['total_pendapatan'] ?? array_sum(array_column($pendapatanRows, 'value')));
    $totalPotonganPendapatan = $totalPotonganPendapatan ?? array_sum(array_column($potonganPendapatanRows, 'value'));
    $totalPotonganNonPendapatan = $totalPotonganNonPendapatan ?? array_sum(array_column($potonganNonPendapatanRows, 'value'));
    $pendapatanDiterima = $pendapatanDiterima ?? ($data['gaji_bersih'] ?? ($data['thr_diterima'] ?? ($data['insentif_diterima'] ?? ($totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan)))));
@endphp

<style>
/* ===================================================
   OFFICIAL PERUMDAM SLIP GAJI FORMAT (GAMBAR 2)
   =================================================== */
.official-slip-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    margin: 16px 0 32px;
}
.official-slip-card {
    background: #ffffff;
    color: #000000;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.45;
    padding: 28px 36px;
    width: 100%;
    max-width: 820px;
    box-sizing: border-box;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
    border: 1px solid #D1D5DB;
    border-radius: 6px;
}
.official-slip-card * {
    box-sizing: border-box;
}
.official-slip-header {
    text-align: center;
    margin-bottom: 20px;
    font-weight: 700;
}
.official-slip-header .company-title {
    font-size: 12.5px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.official-slip-header .doc-title {
    font-size: 12.5px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 3px;
}
.official-slip-meta {
    margin-bottom: 0;
}
.slip-meta-row {
    display: flex;
    align-items: flex-start;
    min-height: 19px;
    line-height: 19px;
    font-size: 11px;
    text-transform: uppercase;
}
.official-slip-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 28px;
    align-items: start;
}
.slip-row {
    display: flex;
    align-items: flex-start;
    min-height: 19px;
    line-height: 19px;
    font-size: 11px;
    text-transform: uppercase;
}
.slip-meta-row .slip-label,
.slip-col-left .slip-label {
    width: 205px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.slip-col-right .slip-label {
    width: 235px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.slip-colon {
    width: 14px;
    text-align: center;
    flex-shrink: 0;
}
.slip-val-num {
    width: 90px;
    text-align: right;
    flex-shrink: 0;
    font-variant-numeric: tabular-nums;
    font-family: Arial, Helvetica, sans-serif;
    letter-spacing: 0.2px;
}
.slip-val-meta {
    flex: 1;
    min-width: 0;
    white-space: normal;
    word-break: break-word;
}
.slip-row-bold {
    font-weight: 700;
}
.slip-row-net {
    font-weight: 700;
    margin-top: 2px;
}
.slip-disclaimer {
    margin-top: 22px;
    font-size: 9.5px;
    font-style: italic;
    line-height: 1.45;
    color: #111827;
    text-transform: uppercase;
}

/* ===================================================
   CETAK / PRINT MEDIA (A4 PORTRAIT)
   =================================================== */
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 15mm 10mm 15mm;
    }
    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .sidebar, .topbar, .page-head, .breadcrumb, .toolbar, .form-actions, .no-print, button, .btn,
    .main-sidebar, .main-header, .navbar, nav, header, aside, .app-sidebar, .app-header,
    .flash-success, .flash-error, .modal-overlay, #global-custom-modal {
        display: none !important;
    }
    .layout, .main, .main-content, .content, .container, main, .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .official-slip-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
    }
    .official-slip-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="official-slip-wrapper">
    <div class="official-slip-card">
        <!-- HEADER KOP RESMI PERUMDAM (CENTERED) -->
        <div class="official-slip-header">
            <div class="company-title">PERUMDAM TIRTA DARMA AYU KAB. INDRAMAYU</div>
            <div class="doc-title">{{ $judul }}</div>
        </div>

        <!-- METADATA PEGAWAI (FULL WIDTH CONTAINER: NIK, NAMA, GOLONGAN, UNIT KERJA) -->
        <div class="official-slip-meta">
            <div class="slip-meta-row">
                <span class="slip-label">NIK</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ $nik }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">NAMA</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ $nama }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">GOLONGAN</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ $golonganText }}</span>
            </div>
            <div class="slip-meta-row">
                <span class="slip-label">UNIT KERJA</span>
                <span class="slip-colon">:</span>
                <span class="slip-val-meta">{{ $unitKerja }}</span>
            </div>
        </div>

        <!-- 2 KOLOM KONTINU (SEPERTI GAMBAR 2) -->
        <div class="official-slip-body">
            <!-- KOLOM KIRI (JABATAN + PENDAPATAN + DISCLAIMER) -->
            <div class="slip-col-left">
                <!-- JABATAN (dimulai tepat di baris pertama sejajar dengan POTONGAN PMI) -->
                <div class="slip-row">
                    <span class="slip-label">JABATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-meta">{{ $jabatan }}</span>
                </div>

                <!-- Rincian Komponen Pendapatan -->
                @foreach ($pendapatanRows as $item)
                    <div class="slip-row">
                        <span class="slip-label">{{ $item['label'] }}</span>
                        <span class="slip-colon">:</span>
                        <span class="slip-val-num">{{ number_format($item['value'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                @endforeach

                <!-- Subtotal Pendapatan -->
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH PENDAPATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($totalPendapatan, 0, ',', '.') }}</span>
                </div>

                <!-- Bottom Disclaimer (Italic, Uppercase) -->
                <div class="slip-disclaimer">
                    SLIP GAJI INI SUDAH DISETUJUI OLEH DIREKSI<br>
                    NAMUN SEGALA BENTUK PENYALAH GUNAAN SLIP<br>
                    BUKAN MENJADI TANGGUNG JAWAB PERUMDAM.
                </div>
            </div>

            <!-- KOLOM KANAN (POTONGAN PENDAPATAN + NON-PENDAPATAN + THP) -->
            <div class="slip-col-right">
                <!-- Komponen Potongan Pendapatan (langsung sejajar JABATAN di kiri) -->
                @foreach ($potonganPendapatanRows as $item)
                    <div class="slip-row">
                        <span class="slip-label">{{ $item['label'] }}</span>
                        <span class="slip-colon">:</span>
                        <span class="slip-val-num">{{ number_format($item['value'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                @endforeach

                <!-- Subtotal Potongan Pendapatan -->
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH POTONGAN PENDAPATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($totalPotonganPendapatan, 0, ',', '.') }}</span>
                </div>

                <!-- Komponen Potongan Non-Pendapatan -->
                @foreach ($potonganNonPendapatanRows as $item)
                    <div class="slip-row">
                        <span class="slip-label">{{ $item['label'] }}</span>
                        <span class="slip-colon">:</span>
                        <span class="slip-val-num">{{ number_format($item['value'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                @endforeach

                <!-- Subtotal Potongan Non-Pendapatan -->
                <div class="slip-row slip-row-bold">
                    <span class="slip-label">JUMLAH POTONGAN NON-PENDAPATAN</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($totalPotonganNonPendapatan, 0, ',', '.') }}</span>
                </div>

                <!-- Total Akhir Diterima (Take Home Pay) -->
                <div class="slip-row slip-row-bold slip-row-net">
                    <span class="slip-label">{{ $labelDiterima ?? 'JUMLAH PENDAPATAN DITERIMA' }}</span>
                    <span class="slip-colon">:</span>
                    <span class="slip-val-num">{{ number_format($pendapatanDiterima, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
