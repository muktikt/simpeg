<?php

use Illuminate\Support\Carbon;

if (! function_exists('formatTglIndo')) {
    /**
     * Format tanggal secara aman dengan dukungan teks tanggal Indonesia yang sudah terformat.
     */
    function formatTglIndo(mixed $date, string $format = 'd M Y'): string
    {
        if (empty($date)) {
            return '-';
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->translatedFormat($format);
        }

        $str = trim((string) $date);

        // Jika string sudah berformat teks Indonesia (misal: "Jum'at, 21 Agustus 2026"), kembalikan langsung
        if (preg_match('/(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)/i', $str)) {
            return $str;
        }

        try {
            return Carbon::parse($str)->translatedFormat($format);
        } catch (\Throwable $e) {
            return $str;
        }
    }
}

if (! function_exists('terbilang')) {
    /**
     * Mengubah nominal angka menjadi format terbilang dalam bahasa Indonesia.
     */
    function terbilang(mixed $angka): string
    {
        $angka = abs((float) $angka);
        $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = ' ' . $baca[(int) $angka];
        } elseif ($angka < 20) {
            $terbilang = terbilang($angka - 10) . ' Belas';
        } elseif ($angka < 100) {
            $terbilang = terbilang($angka / 10) . ' Puluh' . terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = ' Seratus' . terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = terbilang($angka / 100) . ' Ratus' . terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = ' Seribu' . terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = terbilang($angka / 1000) . ' Ribu' . terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = terbilang($angka / 1000000) . ' Juta' . terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $terbilang = terbilang($angka / 1000000000) . ' Miliar' . terbilang(fmod($angka, 1000000000));
        } elseif ($angka < 1000000000000000) {
            $terbilang = terbilang($angka / 1000000000000) . ' Triliun' . terbilang(fmod($angka, 1000000000000));
        }

        return trim($terbilang);
    }
}
