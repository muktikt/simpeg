<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\AbsensiController;
use Illuminate\Http\Request;

trait HasBulanTahunFilter
{
    /**
     * Parse nama bulan & tahun dari string periode seperti "JULI 2026" atau "November 2026".
     */
    protected function parseBulanTahunFromLabel(?string $label): array
    {
        $bulan = null;
        $tahun = null;
        $text = trim((string) $label);

        if ($text !== '') {
            foreach (AbsensiController::BULAN as $num => $nama) {
                if (stripos($text, $nama) !== false) {
                    $bulan = (string) $num;
                    break;
                }
            }
            if (preg_match('/\b(20\d{2})\b/', $text, $m)) {
                $tahun = $m[1];
            }
        }

        return [$bulan, $tahun];
    }

    /**
     * Daftar opsi tahun standar untuk dropdown periode.
     */
    protected function tahunFilterOptions(): array
    {
        $maxYear = (int) now()->year + 1;
        $minYear = min(2024, (int) now()->year - 3);

        return range($maxYear, $minYear);
    }

    /**
     * Resolve parameter filter bulan & tahun dari request, termasuk kompatibilitas parameter lama.
     */
    protected function resolveBulanTahunFilter(Request $request, bool $isPegawai, string $legacyParam = 'bulan_label'): array
    {
        $bulanList = AbsensiController::BULAN;
        $tahunOptions = $this->tahunFilterOptions();

        $defaultBulan = $isPegawai ? 'all' : (string) now()->month;
        $defaultTahun = $isPegawai ? 'all' : (string) now()->year;

        $bulan = null;
        $tahun = null;

        if ($request->has('bulan') || $request->has('tahun')) {
            $rawBulan = trim((string) $request->query('bulan', $defaultBulan));
            $rawTahun = trim((string) $request->query('tahun', $defaultTahun));
            $bulan = ($rawBulan === 'all' || isset($bulanList[(int) $rawBulan])) ? ($rawBulan === 'all' ? 'all' : (string) (int) $rawBulan) : $defaultBulan;
            $tahun = ($rawTahun === 'all' || ctype_digit($rawTahun)) ? $rawTahun : $defaultTahun;
        } elseif ($request->filled($legacyParam)) {
            $legacy = trim((string) $request->query($legacyParam));
            if (strtolower($legacy) === 'all') {
                $bulan = 'all';
                $tahun = 'all';
            } else {
                [$parsedBulan, $parsedTahun] = $this->parseBulanTahunFromLabel($legacy);
                $bulan = $parsedBulan ?? $defaultBulan;
                $tahun = $parsedTahun ?? $defaultTahun;
            }
        } else {
            $bulan = $defaultBulan;
            $tahun = $defaultTahun;
        }

        return [$bulan, $tahun, $defaultBulan, $defaultTahun, $bulanList, $tahunOptions];
    }

    /**
     * Terapkan filter bulan & tahun ke query builder berdasarkan kolom teks periode/bulan_label.
     */
    protected function applyBulanTahunColumnFilter($query, string $column, string $bulan, string $tahun): void
    {
        $bulanNama = ($bulan !== 'all') ? (AbsensiController::BULAN[(int) $bulan] ?? '') : '';

        if ($bulan !== 'all' && $tahun !== 'all' && $bulanNama !== '') {
            $target = strtolower(trim("{$bulanNama} {$tahun}"));
            $query->whereRaw("LOWER(TRIM({$column})) = ?", [$target]);
        } elseif ($bulan !== 'all' && $bulanNama !== '') {
            $query->where($column, 'ilike', "%{$bulanNama}%");
        } elseif ($tahun !== 'all') {
            $query->where($column, 'ilike', "%{$tahun}%");
        }
    }

    /**
     * Bentuk string periode standar (misal "NOVEMBER 2026") dari input dropdown bulan & tahun.
     */
    protected function resolveBulanTahunInput(Request $request, string $legacyField = 'bulan_label'): string
    {
        if ($request->filled('bulan') && $request->filled('tahun')) {
            $bulanNama = AbsensiController::BULAN[(int) $request->input('bulan')] ?? 'JULI';
            $tahun = (int) $request->input('tahun');

            return strtoupper(trim("{$bulanNama} {$tahun}"));
        }

        return strtoupper(trim((string) $request->input($legacyField)));
    }
}
