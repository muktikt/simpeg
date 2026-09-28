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
        $userLogin = session('simpeg_user') ?? [];

        $namaBulan = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? ('Bulan ' . $bulan);
        $periodeStr = "$namaBulan $tahun";
        $prefix = sprintf('%04d-%02d', $tahun, $bulan);
        $monthPadded = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // 1. Data dari Prestasi (Prioritas Utama - Input Manual SDM)
        $prestasiLembur = collect();
        try {
            $rows = \Illuminate\Support\Facades\DB::table('prestasi')
                ->leftJoin('pegawai', 'prestasi.pegawai_id', '=', 'pegawai.id')
                ->where(function ($q) use ($prefix, $tahun, $bulan, $monthPadded) {
                    $q->where('prestasi.tanggal', 'like', "$prefix%")
                      ->orWhere('prestasi.tanggal', 'like', "%$tahun-$monthPadded-%")
                      ->orWhere('prestasi.tanggal', 'like', "%$tahun-$bulan-%");
                })
                ->select(
                    'prestasi.id',
                    'prestasi.pegawai_id',
                    'prestasi.tanggal',
                    'prestasi.keterangan',
                    'pegawai.nik',
                    'pegawai.name as nama',
                    'pegawai.jabatan',
                    'pegawai.unit_kerja'
                )
                ->get();

            foreach ($rows as $r) {
                $meta = json_decode($r->keterangan ?? '{}', true) ?: [];
                $jam = (float) ($meta['jam_lembur'] ?? ($r->jam_lembur ?? 0));
                $nominal = (float) ($meta['nominal_lembur'] ?? ($jam * PrestasiController::RATE_LEMBUR_PER_JAM));
                if ($jam > 0 || $nominal > 0) {
                    $prestasiLembur->push([
                        'id' => $r->id,
                        'pegawai_id' => $r->pegawai_id,
                        'nik' => $r->nik,
                        'nama' => $r->nama,
                        'tanggal' => $r->tanggal,
                        'jam_lembur' => $jam,
                        'nominal_lembur' => $nominal,
                        'periode' => $periodeStr,
                        'source' => 'prestasi',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Laporan lembur prestasi query failed: ' . $e->getMessage());
        }

        // 2. Data dari tabel lembur (misal sinkronisasi Set Prestasi)
        $dbLemburRows = collect();
        try {
            $lemburList = \Illuminate\Support\Facades\DB::table('lembur')
                ->leftJoin('pegawai', 'lembur.pegawai_id', '=', 'pegawai.id')
                ->where('lembur.bulan', 'ilike', "%$periodeStr%")
                ->select(
                    'lembur.id',
                    'lembur.pegawai_id',
                    'pegawai.nik',
                    'pegawai.name as nama',
                    'lembur.uang_lembur as nominal_lembur',
                    'lembur.jam_lembur',
                    'lembur.bulan as periode',
                    'lembur.created_at'
                )
                ->get();

            foreach ($lemburList as $r) {
                $jam = (float) ($r->jam_lembur ?? 0);
                $nominal = (float) ($r->nominal_lembur ?? 0);
                if ($jam > 0 || $nominal > 0) {
                    $tgl = $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null;
                    $dbLemburRows->push([
                        'id' => $r->id,
                        'pegawai_id' => $r->pegawai_id,
                        'nik' => $r->nik,
                        'nama' => $r->nama,
                        'tanggal' => $tgl,
                        'jam_lembur' => $jam,
                        'nominal_lembur' => $nominal,
                        'periode' => $r->periode ?? $periodeStr,
                        'source' => 'lembur_table',
                    ]);
                }
            }
        } catch (\Throwable $e) {}

        // 3. Ambil data lembur dari payroll (hanya sebagai fallback jika belum diinput di Prestasi)
        $payrollLembur = collect();
        try {
            $payrollRows = \Illuminate\Support\Facades\DB::table('payroll')
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
                ->get();

            foreach ($payrollRows as $r) {
                $nominal = (float) $r->nominal_lembur;
                $jam = round($nominal / PrestasiController::RATE_LEMBUR_PER_JAM, 1);
                $payrollLembur->push([
                    'id' => $r->id,
                    'pegawai_id' => $r->pegawai_id,
                    'nik' => $r->nik,
                    'nama' => $r->nama,
                    'tanggal' => null,
                    'jam_lembur' => $jam,
                    'nominal_lembur' => $nominal,
                    'periode' => $r->periode ?? $periodeStr,
                    'source' => 'payroll',
                ]);
            }
        } catch (\Throwable $e) {}

        // Prioritas data: Prestasi (Input Manual SDM) -> Tabel Lembur -> Payroll
        $data = $prestasiLembur->concat($dbLemburRows)->concat($payrollLembur)->unique('nik')->values();

        $riwayatLembur = [];

        $isPegawai = (($userLogin['userlevel'] ?? '') === '5') || $request->has('my');
        if ($isPegawai) {
            $myNik = $userLogin['nik'] ?? '';
            $data = $data->where('nik', $myNik)->values();

            // Ambil semua riwayat lembur milik pegawai ini dari tabel prestasi, lembur, & payroll
            $riwayatItems = collect();

            // Dari Prestasi
            try {
                $pRows = \Illuminate\Support\Facades\DB::table('prestasi')
                    ->leftJoin('pegawai', 'prestasi.pegawai_id', '=', 'pegawai.id')
                    ->where('pegawai.nik', $myNik)
                    ->select('prestasi.*')
                    ->orderByDesc('prestasi.tanggal')
                    ->get();

                foreach ($pRows as $r) {
                    $meta = json_decode($r->keterangan ?? '{}', true) ?: [];
                    $jam = (float) ($meta['jam_lembur'] ?? ($r->jam_lembur ?? 0));
                    $nominal = (float) ($meta['nominal_lembur'] ?? ($jam * PrestasiController::RATE_LEMBUR_PER_JAM));
                    if ($jam > 0 || $nominal > 0) {
                        $tgl = $r->tanggal ? date('F Y', strtotime($r->tanggal)) : '-';
                        $riwayatItems->push([
                            'bulan_nama' => $tgl,
                            'jam_lembur' => $jam,
                            'nominal_lembur' => $nominal,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}

            // Dari Lembur table
            try {
                $lRows = \Illuminate\Support\Facades\DB::table('lembur')
                    ->leftJoin('pegawai', 'lembur.pegawai_id', '=', 'pegawai.id')
                    ->where('pegawai.nik', $myNik)
                    ->select('lembur.*')
                    ->orderByDesc('lembur.created_at')
                    ->get();

                foreach ($lRows as $r) {
                    $riwayatItems->push([
                        'bulan_nama' => $r->bulan ?? '-',
                        'jam_lembur' => (float) ($r->jam_lembur ?? 0),
                        'nominal_lembur' => (float) ($r->uang_lembur ?? 0),
                    ]);
                }
            } catch (\Throwable $e) {}

            // Dari Payroll
            try {
                $payRows = \Illuminate\Support\Facades\DB::table('payroll')
                    ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                    ->where('pegawai.nik', $myNik)
                    ->where('payroll.lembur', '>', 0)
                    ->select('payroll.*')
                    ->orderByDesc('payroll.tahun')
                    ->orderByDesc('payroll.bulan')
                    ->get();

                foreach ($payRows as $r) {
                    $nominal = (float) ($r->lembur ?? 0);
                    $riwayatItems->push([
                        'bulan_nama' => $r->periode ?? "Bulan {$r->bulan} {$r->tahun}",
                        'jam_lembur' => round($nominal / PrestasiController::RATE_LEMBUR_PER_JAM, 1),
                        'nominal_lembur' => $nominal,
                    ]);
                }
            } catch (\Throwable $e) {}

            $riwayatLembur = $riwayatItems->unique('bulan_nama')->values();
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
