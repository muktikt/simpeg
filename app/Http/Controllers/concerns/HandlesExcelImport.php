<?php

namespace App\Http\Controllers\Concerns;

trait HandlesExcelImport
{
    /**
     * Parse CSV / TXT file ke format array baris data.
     */
    protected function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Deteksi delimiter (koma atau titik koma)
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
                if (count(array_filter($data, fn ($v) => trim((string)$v) !== '')) > 0) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Parse .xlsx file native menggunakan ZipArchive & SimpleXMLElement.
     */
    protected function parseXlsx(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return $this->parseCsv($path);
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return $this->parseCsv($path);
        }

        // 1. Ambil sharedStrings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = (string) $si->t;
                    if (empty($text) && isset($si->r)) {
                        $parts = [];
                        foreach ($si->r as $r) {
                            $parts[] = (string) $r->t;
                        }
                        $text = implode('', $parts);
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // 2. Ambil worksheet pertama
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $rows = [];
        if ($sheetXml) {
            $xml = simplexml_load_string($sheetXml);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $rowVals = [];
                    foreach ($row->c as $cell) {
                        $cellType = (string) $cell['t'];
                        $val = (string) $cell->v;

                        if ($cellType === 's' && isset($sharedStrings[(int) $val])) {
                            $val = $sharedStrings[(int) $val];
                        }

                        // Petakan kolom berdasarkan koordinat sel (misal: A1, B1, C1)
                        $cellRef = (string) $cell['r'];
                        $colLetters = preg_replace('/[^A-Za-z]/', '', $cellRef);
                        $colIdx = 0;
                        for ($i = 0; $i < strlen($colLetters); $i++) {
                            $colIdx = $colIdx * 26 + (ord(strtoupper($colLetters[$i])) - ord('A') + 1);
                        }
                        $colIdx = max(0, $colIdx - 1);
                        $rowVals[$colIdx] = $val;
                    }

                    if (! empty($rowVals)) {
                        ksort($rowVals);
                        $maxIdx = max(array_keys($rowVals));
                        $normalizedRow = [];
                        for ($k = 0; $k <= $maxIdx; $k++) {
                            $normalizedRow[$k] = $rowVals[$k] ?? '';
                        }

                        if (count(array_filter($normalizedRow, fn ($v) => trim((string)$v) !== '')) > 0) {
                            $rows[] = $normalizedRow;
                        }
                    }
                }
            }
        }

        $zip->close();
        return $rows;
    }

    /**
     * Baca baris-baris dari file yang diunggah (.xlsx / .csv).
     */
    protected function readUploadedRows(\Illuminate\Http\UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->parseCsv($file->getRealPath());
        } elseif ($extension === 'xlsx') {
            return $this->parseXlsx($file->getRealPath());
        }
        return $this->parseCsv($file->getRealPath());
    }

    /**
     * Cari baris header yang mengandung kolom 'NIK' dan petakan indeksnya.
     */
    protected function extractHeaderMap(array &$rows): ?array
    {
        $header = null;
        while (! empty($rows)) {
            $candidate = array_shift($rows);
            $hasNik = false;
            foreach ($candidate as $colVal) {
                if (str_contains(strtolower(trim((string) $colVal)), 'nik')) {
                    $hasNik = true;
                    break;
                }
            }
            if ($hasNik) {
                $header = $candidate;
                break;
            }
        }

        if (! $header) {
            return null;
        }

        $headerMap = [];
        foreach ($header as $idx => $colName) {
            $norm = strtolower(trim((string) $colName));
            $norm = preg_replace('/\s+/', '_', $norm);
            $headerMap[$norm] = $idx;
        }

        return $headerMap;
    }

    /**
     * Bersihkan NIK pegawai dari spasi/karakter tak lazim dan normalkan TEMP.
     */
    protected function normalizeNik(string $rawNik, array $pegawaiDbIds): string
    {
        $nik = strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', trim($rawNik)));
        if (strlen($nik) === 4 && isset($pegawaiDbIds['TEMP' . $nik])) {
            $nik = 'TEMP' . $nik;
        }
        return $nik;
    }
}
