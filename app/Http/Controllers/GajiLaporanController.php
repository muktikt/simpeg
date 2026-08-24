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
     * Jadi semua method di bawah ini murni MENYARING & MERINGKAS data dari
     * GajiProsesController (dummy_gaji_proses) dan PrestasiController
     * (dummy_prestasi_gaji) yang sudah ada - tidak ada CRUD baru.
     * Hanya data yang sudah TERBIT yang ditampilkan (draft tidak dihitung),
     * mengikuti pola query asli yang selalu mengacu ke data final.
     */
    protected function gajiTerbit(int $bulan, int $tahun)
    {
        $sessionGaji = collect(session('dummy_gaji_proses', []))
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $row['status'] === 'terbit');

        try {
            $bulanNama = AbsensiController::BULAN[$bulan] ?? 'Bulan ' . $bulan;
            $periodeStr = $bulanNama . ' ' . $tahun;

            $dbPayroll = \Illuminate\Support\Facades\DB::table('payroll')
                ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                ->where('payroll.periode', 'like', "%$tahun%")
                ->select(
                    'payroll.*',
                    'pegawai.nik',
                    'pegawai.name as nama',
                    'pegawai.jabatan',
                    'pegawai.unit_kerja',
                    'pegawai.golongan'
                )
                ->get()
                ->map(function ($row) use ($bulan, $tahun) {
                    $gapok = (float) ($row->gapok ?? 0);
                    $tunjJabatan = (float) ($row->tunjangan_jabatan ?? 0);
                    $tunjIstri = (float) ($row->tunjangan_istri ?? 0);
                    $tunjAnak = (float) ($row->tunjangan_anak ?? 0);
                    $tunjPerumahan = (float) ($row->tunjangan_perumahan ?? 0);
                    $tunjBpjstk = (float) ($row->tunjangan_bpjstk ?? 0);
                    $potDapenma = (float) ($row->potongan_dapenma ?? 0);
                    $potBjbs = (float) ($row->potongan_bank_bjb ?? 0);
                    $potBpjstk = (float) ($row->potongan_bpjstk ?? 0);
                    $potPajak = (float) ($row->potongan_pajak ?? 0);

                    $totalPendapatan = $gapok + $tunjJabatan + $tunjIstri + $tunjAnak + $tunjPerumahan + $tunjBpjstk;
                    $totalPotongan = $potDapenma + $potBjbs + $potBpjstk + $potPajak;
                    $gajiBersih = (float) ($row->total_terima ?? ($totalPendapatan - $totalPotongan));

                    return [
                        'id' => $row->id,
                        'pegawai_id' => $row->pegawai_id,
                        'nik' => $row->nik,
                        'nama' => $row->nama,
                        'jabatan' => $row->jabatan,
                        'unit_kerja' => $row->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                        'golongan' => $row->golongan ?? 'III/a',
                        'kategori' => 'pegawai',
                        'kode_ptkp' => 'K1',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'status' => 'terbit',
                        'gaji_pokok' => $gapok,
                        'tunj_jabatan' => $tunjJabatan,
                        'tunj_istri' => $tunjIstri,
                        'tunj_anak' => $tunjAnak,
                        'tunjangan_perumahan' => $tunjPerumahan,
                        'tunjangan_bpjstk' => $tunjBpjstk,
                        'pot_dapenma' => $potDapenma,
                        'pot_bjbs' => $potBjbs,
                        'potongan_bpjstk' => $potBpjstk,
                        'potongan_pajak' => $potPajak,
                        'total_pendapatan' => $totalPendapatan,
                        'total_potongan' => $totalPotongan,
                        'gaji_diterima' => $gajiBersih,
                        'gaji_bersih' => $gajiBersih,
                    ];
                });

            if ($dbPayroll->isNotEmpty()) {
                return $sessionGaji->merge($dbPayroll)->unique('nik');
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return $sessionGaji;
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

        $data = collect(session('dummy_prestasi_gaji', []))
            ->filter(fn ($row) => \Illuminate\Support\Carbon::parse($row['tanggal'])->month === $bulan
                && \Illuminate\Support\Carbon::parse($row['tanggal'])->year === $tahun
                && ($row['jam_lembur'] ?? 0) > 0)
            ->map(function ($row) {
                $p = $this->pegawaiById($row['pegawai_id'] ?? null);
                $row['nik'] = $p['nik'] ?? ($row['nik'] ?? '-');
                $row['nama'] = $p['nama'] ?? ($row['nama'] ?? '-');
                $row['nominal_lembur'] = ($row['jam_lembur'] ?? 0) * PrestasiController::RATE_LEMBUR_PER_JAM;

                return $row;
            });

        $riwayatLembur = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $data = $data->where('nik', $userLogin['nik']);

            // Ambill semua riwayat lembur milik pegawai ini
            $riwayatLembur = collect(session('dummy_prestasi_gaji', []))
                ->filter(fn ($row) => ($row['jam_lembur'] ?? 0) > 0)
                ->map(function ($row) {
                    $p = $this->pegawaiById($row['pegawai_id'] ?? null);
                    $row['nik'] = $p['nik'] ?? ($row['nik'] ?? '-');
                    $row['nominal_lembur'] = ($row['jam_lembur'] ?? 0) * PrestasiController::RATE_LEMBUR_PER_JAM;
                    $row['bulan_nama'] = \Illuminate\Support\Carbon::parse($row['tanggal'])->translatedFormat('F Y');
                    return $row;
                })
                ->where('nik', $userLogin['nik'])
                ->sortByDesc('tanggal')
                ->values();
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

            // Ambill 3-6 bulan riwayat terbit milik pegawai ini
            $allGaji = collect(session('dummy_gaji_proses', []))
                ->where('nik', $userLogin['nik'])
                ->filter(fn ($row) => $row['status'] === 'terbit')
                ->sortByDesc(fn ($row) => $row['tahun'] * 100 + $row['bulan'])
                ->values();

            $riwayatGaji = $allGaji;
        }

        $data = $data->sortBy('nama')->values();

        return view('gaji-laporan.slip-gaji', compact('data', 'bulan', 'tahun', 'riwayatGaji'));
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
