<?php

namespace App\Services;

class Pph21Calculator
{
    /**
     * Tabel Tarif Efektif Rata-Rata (TER) Bulanan
     * Mengacu pada PP No. 58 Tahun 2023 & PMK No. 168 Tahun 2023
     */
    public const TER_A = [
        [5400000, 0.0],
        [5650000, 0.0025],
        [5950000, 0.005],
        [6300000, 0.0075],
        [6750000, 0.01],
        [7500000, 0.0125],
        [8550000, 0.015],
        [9650000, 0.0175],
        [10050000, 0.02],
        [10350000, 0.0225],
        [10700000, 0.025],
        [11050000, 0.03],
        [11600000, 0.035],
        [12500000, 0.04],
        [13750000, 0.05],
        [15100000, 0.06],
        [16950000, 0.07],
        [19750000, 0.08],
        [24150000, 0.09],
        [26450000, 0.10],
        [28000000, 0.11],
        [30050000, 0.12],
        [32400000, 0.13],
        [35400000, 0.14],
        [39100000, 0.15],
        [43850000, 0.16],
        [47800000, 0.17],
        [51400000, 0.18],
        [56300000, 0.19],
        [62200000, 0.20],
        [68600000, 0.21],
        [77500000, 0.22],
        [89000000, 0.23],
        [103000000, 0.24],
        [125000000, 0.25],
        [157000000, 0.26],
        [206000000, 0.27],
        [337000000, 0.28],
        [454000000, 0.29],
        [550000000, 0.30],
        [695000000, 0.31],
        [910000000, 0.32],
        [1400000000, 0.33],
        [PHP_FLOAT_MAX, 0.34],
    ];

    public const TER_B = [
        [6200000, 0.0],
        [6500000, 0.0025],
        [6850000, 0.005],
        [7300000, 0.0075],
        [9200000, 0.01],
        [10750000, 0.015],
        [11250000, 0.02],
        [11600000, 0.025],
        [12600000, 0.03],
        [13600000, 0.04],
        [14950000, 0.05],
        [16400000, 0.06],
        [18450000, 0.07],
        [21850000, 0.08],
        [26000000, 0.09],
        [27700000, 0.10],
        [29350000, 0.11],
        [31450000, 0.12],
        [33950000, 0.13],
        [37100000, 0.14],
        [41100000, 0.15],
        [45800000, 0.16],
        [49500000, 0.17],
        [53800000, 0.18],
        [58500000, 0.19],
        [64000000, 0.20],
        [71000000, 0.21],
        [80000000, 0.22],
        [93000000, 0.23],
        [109000000, 0.24],
        [129000000, 0.25],
        [163000000, 0.26],
        [211000000, 0.27],
        [374000000, 0.28],
        [459000000, 0.29],
        [555000000, 0.30],
        [704000000, 0.31],
        [957000000, 0.32],
        [1405000000, 0.33],
        [PHP_FLOAT_MAX, 0.34],
    ];

    public const TER_C = [
        [6600000, 0.0],
        [6950000, 0.0025],
        [7350000, 0.005],
        [7800000, 0.0075],
        [8850000, 0.01],
        [10900000, 0.0125],
        [11200000, 0.015],
        [12050000, 0.02],
        [12950000, 0.03],
        [14150000, 0.04],
        [15550000, 0.05],
        [17050000, 0.06],
        [19500000, 0.07],
        [22700000, 0.08],
        [26600000, 0.09],
        [28100000, 0.10],
        [30100000, 0.11],
        [32600000, 0.12],
        [35400000, 0.13],
        [38900000, 0.14],
        [43000000, 0.15],
        [47400000, 0.16],
        [51200000, 0.17],
        [55800000, 0.18],
        [60400000, 0.19],
        [66700000, 0.20],
        [74500000, 0.21],
        [83200000, 0.22],
        [95600000, 0.23],
        [110000000, 0.24],
        [134000000, 0.25],
        [169500000, 0.26],
        [221000000, 0.27],
        [390000000, 0.28],
        [463000000, 0.29],
        [561000000, 0.30],
        [709000000, 0.31],
        [965000000, 0.32],
        [1419000000, 0.33],
        [PHP_FLOAT_MAX, 0.34],
    ];

    /**
     * Menentukan kategori TER berdasarkan kode PTKP
     */
    public static function getKategoriTer(string $kodePtkp): string
    {
        $code = strtoupper(str_replace(['/', '-', ' '], '', trim($kodePtkp)));
        return match ($code) {
            'TK', 'TK0', 'TK1', 'K0', 'K' => 'TER_A',
            'TK2', 'TK3', 'K1', 'K2' => 'TER_B',
            'K3' => 'TER_C',
            default => 'TER_A',
        };
    }

    /**
     * Mengambil tarif persentase TER berdasarkan kategori dan nominal penghasilan
     */
    public static function getRate(string $kategoriTer, float $penghasilan): float
    {
        $table = match ($kategoriTer) {
            'TER_B' => self::TER_B,
            'TER_C' => self::TER_C,
            default => self::TER_A,
        };

        foreach ($table as [$limit, $rate]) {
            if ($penghasilan <= $limit) {
                return $rate;
            }
        }

        return 0.34;
    }

    /**
     * Menghitung PPh 21 menggunakan formula Gross-Up sesuai sistem lama:
     * 1. $rate1 = TER(Penghasilan)
     * 2. $brutoEstimasi = Penghasilan + (Penghasilan * $rate1)
     * 3. $rateFinal = TER($brutoEstimasi)
     * 4. $pph = ($penghasilan * $rateFinal) / (1 - $rateFinal)
     *
     * Di sistem Perumdam, karyawan menerima subsidi tunjangan pajak penuh:
     * tunjangan_pajak = $pph
     * potongan_pajak = $pph
     */
    public static function hitungGrossUp(string $kodePtkp, float $penghasilan): array
    {
        if ($penghasilan <= 0) {
            return [
                'kode_ptkp' => $kodePtkp,
                'kategori_ter' => self::getKategoriTer($kodePtkp),
                'penghasilan' => 0,
                'rate_persen' => 0,
                'persen_label' => '0%',
                'nominal_pph' => 0,
                'tunjangan_pajak' => 0,
                'potongan_pajak' => 0,
            ];
        }

        $kategoriTer = self::getKategoriTer($kodePtkp);
        $rate1 = self::getRate($kategoriTer, $penghasilan);

        $estimasiPph = $penghasilan * $rate1;
        $brutoPph = $penghasilan + $estimasiPph;

        $rateFinal = self::getRate($kategoriTer, $brutoPph);

        $nominalPph = 0;
        if ($rateFinal > 0 && $rateFinal < 1) {
            $nominalPph = (int) round(($penghasilan * $rateFinal) / (1 - $rateFinal));
        }

        return [
            'kode_ptkp' => $kodePtkp,
            'kategori_ter' => $kategoriTer,
            'penghasilan' => $penghasilan,
            'rate_persen' => $rateFinal,
            'persen_label' => round($rateFinal * 100, 2) . '%',
            'nominal_pph' => $nominalPph,
            'tunjangan_pajak' => $nominalPph,
            'potongan_pajak' => $nominalPph,
        ];
    }
}

