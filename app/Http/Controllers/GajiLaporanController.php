<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GajiLaporanController extends Controller
{
    /**
     * MODUL INI READ-ONLY - TIDAK PUNYA DATA SENDIRI.
     *
     * Dicek ke sistem lama: semua file di grup menu "Laporan Penggajian"
     * (kecuali Absensi, Prestasi, Gapok/Golongan yang sudah dibuat
     * terpisah) ternyata cuma filter/format berbeda dari data yang sama:
     *   - Lap. Lembur          -> tbl_prestasi (field jam_lembur/nominal_lembur)
     *   - Lap. Slip Gaji        -> tbl_gaji_detail (data Gaji Proses)
     *   - Lap. Buku Besar Gaji  -> tbl_gaji_detail, semua baris jadi 1 daftar
     *   - Lap. Buku Besar Per Sub -> sama, dikelompokkan per unit kerja
     *   - Lap. Payroll          -> tbl_gaji_detail + tbl_rek_bjbs (rekening)
     *   - Lap. Pajak            -> tbl_gaji_detail (field potongan_pajak)
     *   - Lap. BPJSTK           -> tbl_gaji_detail (field tunjangan/potongan_bpjstk)
     *   - Lap. Tunj. Perumahan  -> tbl_gaji_detail (field tunjangan_perumahan)
     *
     * Jadi semua method di bawah ini menyaring & meringkas data dari
     * GajiProsesController (tabel payroll) dan PrestasiController (tabel prestasi / lembur).
     * Hanya data yang sudah TERBIT yang ditampilkan (draft tidak dihitung).
     */
    protected function gajiTerbit(int $bulan, int $tahun)
    {
        return collect(app(GajiProsesController::class)->all())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $row['status'] === 'terbit' || strtolower($row['status'] ?? '') === 'diterbitkan');
    }


    protected function pegawaiById(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        return collect(app(PegawaiController::class)->all())->first(function ($p) use ($id) {
            return (string) ($p['id'] ?? '') === (string) $id
                || (string) ($p['db_id'] ?? '') === (string) $id
                || (string) ($p['nik'] ?? '') === (string) $id;
        });
    }

    protected function periodeInput(Request $request): array
    {
        return [
            'bulan' => (int) $request->get('bulan', now()->month),
            'tahun' => (int) $request->get('tahun', now()->year),
        ];
    }

    public function lembur(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));
        $userLogin = session('simpeg_user');

        // 1. Ambil data lembur dari tabel payroll database Supabase
        $payrollLembur = collect();
        try {
            $payrollLembur = \Illuminate\Support\Facades\DB::table('payroll')
                ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                ->where('payroll.bulan', $bulan)
                ->where('payroll.tahun', $tahun)
                ->where('payroll.lembur', '>', 0)
                ->select(
                    'payroll.id',
                    'payroll.pegawai_id',
                    'pegawai.nik',
                    'pegawai.name as nama',
                    'payroll.lembur as nominal_lembur',
                    'payroll.bulan',
                    'payroll.tahun',
                    'payroll.periode'
                )
                ->get()
                ->map(function ($r) {
                    $arr = (array) $r;
                    $arr['jam_lembur'] = round($arr['nominal_lembur'] / PrestasiController::RATE_LEMBUR_PER_JAM, 1);
                    return $arr;
                });
        } catch (\Throwable $e) {}

        // 2. Ambil juga data lembur dari tabel lembur (misal dari input Set Prestasi)
        $namaBulan = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? '';
        $periodeStr = "$namaBulan $tahun";
        $dbLemburRows = collect();
        try {
            $dbLemburRows = \Illuminate\Support\Facades\DB::table('lembur')
                ->leftJoin('pegawai', 'lembur.pegawai_id', '=', 'pegawai.id')
                ->where('lembur.bulan', 'ilike', "%$periodeStr%")
                ->select(
                    'lembur.id',
                    'lembur.pegawai_id',
                    'pegawai.nik',
                    'pegawai.name as nama',
                    'lembur.uang_lembur as nominal_lembur',
                    'lembur.jam_lembur',
                    'lembur.bulan as periode'
                )
                ->get()
                ->map(function ($r) {
                    $arr = (array) $r;
                    $arr['nominal_lembur'] = (float) ($arr['nominal_lembur'] ?? 0);
                    $arr['jam_lembur'] = (float) ($arr['jam_lembur'] ?? 0);
                    return $arr;
                });
        } catch (\Throwable $e) {}

        $data = $payrollLembur->concat($dbLemburRows)->unique('nik');

        $riwayatLembur = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $myNik = $userLogin['nik'] ?? '';
            $data = $data->where('nik', $myNik);

            // Ambil semua riwayat lembur milik pegawai ini dari tabel payroll & lembur
            $dbRiwayatPayroll = collect();
            try {
                $dbRiwayatPayroll = \Illuminate\Support\Facades\DB::table('payroll')
                    ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                    ->where('pegawai.nik', $myNik)
                    ->where('payroll.lembur', '>', 0)
                    ->select(
                        'payroll.id',
                        'pegawai.nik',
                        'pegawai.name as nama',
                        'payroll.lembur as nominal_lembur',
                        'payroll.periode as bulan_nama',
                        'payroll.tahun',
                        'payroll.bulan'
                    )
                    ->orderByDesc('payroll.tahun')
                    ->orderByDesc('payroll.bulan')
                    ->get()
                    ->map(function ($r) {
                        $arr = (array) $r;
                        $arr['jam_lembur'] = round($arr['nominal_lembur'] / PrestasiController::RATE_LEMBUR_PER_JAM, 1);
                        return $arr;
                    });
            } catch (\Throwable $e) {}

            $dbRiwayatLembur = collect();
            try {
                $dbRiwayatLembur = \Illuminate\Support\Facades\DB::table('lembur')
                    ->leftJoin('pegawai', 'lembur.pegawai_id', '=', 'pegawai.id')
                    ->where('pegawai.nik', $myNik)
                    ->select(
                        'lembur.id',
                        'pegawai.nik',
                        'pegawai.name as nama',
                        'lembur.uang_lembur as nominal_lembur',
                        'lembur.bulan as bulan_nama',
                        'lembur.jam_lembur'
                    )
                    ->get()
                    ->map(fn ($r) => (array) $r);
            } catch (\Throwable $e) {}

            $riwayatLembur = $dbRiwayatPayroll->concat($dbRiwayatLembur)->unique('bulan_nama')->values();
        }

        $data = $data->sortBy('nama')->values();

        return view('gaji-laporan.lembur', compact('data', 'bulan', 'tahun', 'riwayatLembur'));
    }

    public function slipGaji(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));
        $userLogin = session('simpeg_user');

        $data = $this->gajiTerbit($bulan, $tahun);
        $riwayatGaji = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $data = $data->where('nik', $userLogin['nik']);

            // Ambil 3-6 bulan riwayat terbit milik pegawai ini
            $allGaji = collect(app(GajiProsesController::class)->all())
                ->where('nik', $userLogin['nik'])
                ->filter(fn ($row) => $row['status'] === 'terbit' || strtolower($row['status'] ?? '') === 'diterbitkan')
                ->sortByDesc(fn ($row) => $row['tahun'] * 100 + $row['bulan'])
                ->values();

            $riwayatGaji = $allGaji;
        }

        $data = $data->sortBy('nama')->values();
        $komponenPendapatan = GajiProsesController::KOMPONEN_PENDAPATAN;
        $komponenPotongan = GajiProsesController::KOMPONEN_POTONGAN;

        return view('gaji-laporan.slip-gaji', compact('data', 'bulan', 'tahun', 'riwayatGaji', 'komponenPendapatan', 'komponenPotongan'));
    }

    public function bukuBesar(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalGaji = $data->sum('gaji_bersih');

        return view('gaji-laporan.buku-besar', compact('data', 'bulan', 'tahun', 'totalGaji'));
    }

    public function bukuBesarPerSub(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)
            ->map(function ($row) {
                if (empty($row['unit_kerja']) || $row['unit_kerja'] === '-') {
                    $p = $this->pegawaiById($row['pegawai_id'] ?? null);
                    $row['unit_kerja'] = $p['unit_kerja'] ?? 'Kantor Pusat';
                }

                return $row;
            })
            ->groupBy('unit_kerja')
            ->map(fn ($group) => [
                'rows' => $group->sortBy('nama')->values(),
                'total' => $group->sum('gaji_bersih'),
            ]);

        return view('gaji-laporan.buku-besar-per-sub', compact('data', 'bulan', 'tahun'));
    }

    public function payroll(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalPayroll = $data->sum('gaji_bersih');

        return view('gaji-laporan.payroll', compact('data', 'bulan', 'tahun', 'totalPayroll'));
    }

    public function pajak(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalPajak = $data->sum('potongan_pajak');

        return view('gaji-laporan.pajak', compact('data', 'bulan', 'tahun', 'totalPajak'));
    }

    public function bpjstk(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalBpjstk = $data->sum('tunjangan_bpjstk') + $data->sum('potongan_bpjstk');

        return view('gaji-laporan.bpjstk', compact('data', 'bulan', 'tahun', 'totalBpjstk'));
    }

    public function tunjPerumahan(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalPerumahan = $data->sum('tunjangan_perumahan');

        return view('gaji-laporan.tunj-perumahan', compact('data', 'bulan', 'tahun', 'totalPerumahan'));
    }
}
