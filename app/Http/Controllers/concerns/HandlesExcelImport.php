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
            // Deteksi delimiter (koma, titik koma, atau tab) dan buang UTF-8 BOM di awal file
            $firstLine = (string) fgets($handle);
            rewind($handle);

            $bom = "\xEF\xBB\xBF";
            $rawPrefix = fread($handle, 3);
            if ($rawPrefix !== $bom) {
                rewind($handle);
            }

            $semiCount = substr_count($firstLine, ';');
            $commaCount = substr_count($firstLine, ',');
            $tabCount = substr_count($firstLine, "\t");

            if ($tabCount > $semiCount && $tabCount > $commaCount) {
                $delimiter = "\t";
            } elseif ($semiCount > $commaCount) {
                $delimiter = ';';
            } else {
                $delimiter = ',';
            }

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (! empty($data) && isset($data[0])) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
                }
                if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) > 0) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Parse 1 XML worksheet dari ZipArchive ke array baris.
     */
    protected function parseWorksheetXml(string $sheetXml, array $sharedStrings): array
    {
        $rows = [];
        $xml = @simplexml_load_string($sheetXml);
        if (! $xml || ! isset($xml->sheetData->row)) {
            return $rows;
        }

        foreach ($xml->sheetData->row as $row) {
            $rowVals = [];
            foreach ($row->c as $cell) {
                $cellType = (string) $cell['t'];
                $val = '';

                if ($cellType === 's') {
                    $idx = (int) ((string) $cell->v);
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    if (isset($cell->is->t)) {
                        $val = (string) $cell->is->t;
                    } elseif (isset($cell->is->r)) {
                        $parts = [];
                        foreach ($cell->is->r as $r) {
                            $parts[] = (string) $r->t;
                        }
                        $val = implode('', $parts);
                    }
                } else {
                    $val = (string) $cell->v;
                }

                // Petakan kolom berdasarkan koordinat sel (misal: A1, B1, C1)
                $cellRef = (string) $cell['r'];
                $colLetters = preg_replace('/[^A-Za-z]/', '', $cellRef);
                if ($colLetters !== '') {
                    $colIdx = 0;
                    for ($i = 0; $i < strlen($colLetters); $i++) {
                        $colIdx = $colIdx * 26 + (ord(strtoupper($colLetters[$i])) - ord('A') + 1);
                    }
                    $colIdx = max(0, $colIdx - 1);
                } else {
                    $colIdx = empty($rowVals) ? 0 : (max(array_keys($rowVals)) + 1);
                }
                $rowVals[$colIdx] = $val;
            }

            if (! empty($rowVals)) {
                ksort($rowVals);
                $maxIdx = max(array_keys($rowVals));
                $normalizedRow = [];
                for ($k = 0; $k <= $maxIdx; $k++) {
                    $normalizedRow[$k] = $rowVals[$k] ?? '';
                }

                if (count(array_filter($normalizedRow, fn ($v) => trim((string) $v) !== '')) > 0) {
                    $rows[] = $normalizedRow;
                }
            }
        }

        return $rows;
    }

    /**
     * Parse .xlsx file native menggunakan ZipArchive & SimpleXMLElement.
     * Mendukung multi-sheet (misal sheet "PRESTASI", "ABSENSI", "LEMBUR") lewat $preferredSheetHint.
     */
    protected function parseXlsx(string $path, ?string $preferredSheetHint = null): array
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
            $xml = @simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = (string) $si->t;
                    if ($text === '' && isset($si->r)) {
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

        // 2. Petakan daftar nama sheet -> file xml di xl/worksheets/
        $sheetTargets = [];
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml && $relsXml) {
            $relsMap = [];
            $relsObj = @simplexml_load_string($relsXml);
            if ($relsObj && isset($relsObj->Relationship)) {
                foreach ($relsObj->Relationship as $rel) {
                    $rId = (string) $rel['Id'];
                    $target = ltrim((string) $rel['Target'], '/');
                    if (! str_starts_with($target, 'xl/')) {
                        $target = 'xl/' . ltrim(preg_replace('#^\.\./#', '', $target), '/');
                    }
                    $relsMap[$rId] = $target;
                }
            }

            $wbObj = @simplexml_load_string($workbookXml);
            if ($wbObj && isset($wbObj->sheets->sheet)) {
                foreach ($wbObj->sheets->sheet as $sh) {
                    $name = (string) $sh['name'];
                    $rAttrs = $sh->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $rId = $rAttrs ? (string) $rAttrs['id'] : '';
                    $xmlPath = $relsMap[$rId] ?? null;
                    if ($xmlPath && $zip->locateName($xmlPath) !== false) {
                        $sheetTargets[] = ['name' => $name, 'path' => $xmlPath];
                    }
                }
            }
        }

        if (empty($sheetTargets)) {
            for ($i = 1; $i <= 10; $i++) {
                $candidatePath = "xl/worksheets/sheet{$i}.xml";
                if ($zip->locateName($candidatePath) !== false) {
                    $sheetTargets[] = ['name' => "Sheet{$i}", 'path' => $candidatePath];
                }
            }
        }

        // 3. Jika ada hint nama sheet (contoh: 'absensi', 'prestasi', 'lembur'), prioritaskan sheet yang namanya cocok
        $selectedXml = null;
        if ($preferredSheetHint !== null && $preferredSheetHint !== '') {
            $hintLower = strtolower($preferredSheetHint);
            foreach ($sheetTargets as $st) {
                if (str_contains(strtolower($st['name']), $hintLower)) {
                    $selectedXml = $zip->getFromName($st['path']);
                    break;
                }
            }
        }

        // Jika tidak ada nama sheet yang cocok tapi ada beberapa sheet, gabungkan semua sheet secara berurutan
        // agar blok tabel (Prestasi / Absensi) di sheet ke-2 tetap bisa dideteksi oleh extractHeaderMap!
        $rows = [];
        if ($selectedXml) {
            $rows = $this->parseWorksheetXml($selectedXml, $sharedStrings);
        } else {
            foreach ($sheetTargets as $st) {
                $xmlContent = $zip->getFromName($st['path']);
                if ($xmlContent) {
                    $sheetRows = $this->parseWorksheetXml($xmlContent, $sharedStrings);
                    if (! empty($sheetRows)) {
                        $rows = array_merge($rows, $sheetRows);
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
    protected function readUploadedRows(\Illuminate\Http\UploadedFile $file, ?string $preferredSheetHint = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        // Deteksi otomatis jika file sebenarnya ZIP (.xlsx) atau sebaliknya
        $magic = @file_get_contents($path, false, null, 0, 4);
        if ($magic === "PK\x03\x04" || $extension === 'xlsx') {
            return $this->parseXlsx($path, $preferredSheetHint);
        }

        return $this->parseCsv($path);
    }

    /**
     * Normalisasi nama kolom header menjadi snake_case bersih.
     */
    protected function normalizeHeaderRow(array $header): array
    {
        $headerMap = [];
        foreach ($header as $idx => $colName) {
            $clean = preg_replace('/^\xEF\xBB\xBF/', '', (string) $colName);
            $norm = strtolower(trim($clean));
            $norm = preg_replace('/\s+/', '_', $norm);
            if ($norm !== '' && ! array_key_exists($norm, $headerMap)) {
                $headerMap[$norm] = $idx;
            }
        }
        return $headerMap;
    }

    /**
     * Cari baris header yang mengandung kolom 'NIK' (dan cocok dengan $requiredAnyHeaders jika diberikan),
     * serta hentikan baris data sebelum tabel berikutnya jika dalam 1 file terdapat multi-tabel.
     */
    protected function extractHeaderMap(array &$rows, array $requiredAnyHeaders = []): ?array
    {
        $total = count($rows);
        $matchedIndex = null;
        $fallbackIndex = null;
        $matchedMap = null;
        $fallbackMap = null;

        for ($i = 0; $i < $total; $i++) {
            $candidate = $rows[$i];
            $hasNik = false;
            foreach ($candidate as $colVal) {
                $cleanVal = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $colVal)));
                if ($cleanVal === 'nik' || str_starts_with($cleanVal, 'nik ') || str_ends_with($cleanVal, ' nik')) {
                    $hasNik = true;
                    break;
                }
            }

            if (! $hasNik) {
                continue;
            }

            $map = $this->normalizeHeaderRow($candidate);
            if ($fallbackIndex === null) {
                $fallbackIndex = $i;
                $fallbackMap = $map;
            }

            if (empty($requiredAnyHeaders)) {
                $matchedIndex = $i;
                $matchedMap = $map;
                break;
            }

            foreach ($requiredAnyHeaders as $reqKey) {
                if (array_key_exists($reqKey, $map)) {
                    $matchedIndex = $i;
                    $matchedMap = $map;
                    break 2;
                }
            }
        }

        $chosenIdx = $matchedIndex ?? $fallbackIndex;
        $chosenMap = $matchedMap ?? $fallbackMap;

        if ($chosenIdx === null || ! $chosenMap) {
            $rows = [];
            return null;
        }

        // Ambil baris setelah header terpilih, dan berhenti jika bertemu baris header tabel lain (yang punya kolom NIK lagi)
        $dataRows = [];
        for ($j = $chosenIdx + 1; $j < $total; $j++) {
            $row = $rows[$j];
            $isAnotherHeader = false;
            foreach ($row as $colVal) {
                $cleanVal = strtolower(trim((string) $colVal));
                if ($cleanVal === 'nik') {
                    $isAnotherHeader = true;
                    break;
                }
            }
            if ($isAnotherHeader) {
                break;
            }
            $dataRows[] = $row;
        }

        $rows = $dataRows;
        return $chosenMap;
    }

    /**
     * Cari indeks kolom pertama yang cocok dari daftar alias nama kolom.
     */
    protected function findHeaderCol(array $headerMap, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $headerMap)) {
                return $headerMap[$alias];
            }
        }

        // Pencocokan longgar (tanpa karakter simbol / titik / strip)
        $strippedMap = [];
        foreach ($headerMap as $k => $idx) {
            $stripped = preg_replace('/[^a-z0-9]/', '', $k);
            if ($stripped !== '' && ! isset($strippedMap[$stripped])) {
                $strippedMap[$stripped] = $idx;
            }
        }

        foreach ($aliases as $alias) {
            $strippedAlias = preg_replace('/[^a-z0-9]/', '', strtolower($alias));
            if ($strippedAlias !== '' && isset($strippedMap[$strippedAlias])) {
                return $strippedMap[$strippedAlias];
            }
        }

        return null;
    }

    /**
     * Bersihkan NIK pegawai dari spasi/karakter tak lazim dan normalkan TEMP.
     */
    protected function normalizeNik(string $rawNik, array $pegawaiDbIds): string
    {
        $trimmed = trim(preg_replace('/^\xEF\xBB\xBF/', '', $rawNik));
        // Tangani format angka scientific / desimal dari Excel seperti "1711256.0"
        if (preg_match('/^(\d+)\.0+$/', $trimmed, $m)) {
            $trimmed = $m[1];
        }
        $nik = strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', $trimmed));
        if (strlen($nik) === 4 && isset($pegawaiDbIds['TEMP' . $nik])) {
            $nik = 'TEMP' . $nik;
        }
        return $nik;
    }
}
