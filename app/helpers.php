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
