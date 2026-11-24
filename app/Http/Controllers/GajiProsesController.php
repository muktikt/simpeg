<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasApprovalChain;
use App\Http\Controllers\AuditLogController;
use Illuminate\Http\Request;

class GajiProsesController extends Controller
{
    use HasApprovalChain;
    /**
     * Modul Penggajian / Payroll.
     *
     * Disamakan dengan sistem lama (proses_cek_gaji_satuan.php dkk, ~1390 baris
     * per file x 8 kategori pegawai). Sesuai keputusan konsolidasi, 8 kategori
     * (satuan/dirut/dirum/dirtek/capeg/honor/kontrak/tt) digabung jadi 1 form
     * dengan dropdown kategori, bukan 8 file terpisah.
     *
     * FORMULA YANG DITEMUKAN & DIPAKAI (dari kode asli):
     * - Status kawin dicek dari tbl_keluarga (status_keluarga='Istri/Suami')
     * - Tunjangan Istri = 10% x Gapok, HANYA jika berstatus kawin
     * - Jumlah anak (untuk tunjangan) dibatasi maksimal 2
     * - Jumlah anak (untuk kategori pajak/PTKP) dibatasi maksimal 3
     * - Kategori PTKP: TK (belum kawin, 0 tanggungan) / K / K1 / K2 / K3
     *   dihitung dari (status kawin + jumlah anak pajak)
     * - Jumlah Pendapatan, Jumlah Potongan, Gaji Bersih dihitung OTOMATIS
     *   (sistem lama punya baris kalkulasi ini tapi di-comment dan diganti
     *   ambil dari $_POST - di versi ini kita pakai hasil hitung otomatis
     *   yang lebih benar dan konsisten)
     *
     * Komponen lain (Prestasi, Jabatan, Transport, Pangan, BPJS, dst) di
     * sistem lama nilainya diinput manual oleh Admin tiap proses gaji -
     * tidak ada formula otomatis yang ditemukan di kode, jadi tetap input
     * manual di sini juga (paling apa adanya / paling jujur ke sistem asli).
     */
    public const KATEGORI = [
        'satuan' => 'Pegawai (Satuan)',
        'dirut' => 'Direktur Utama',
        'dirum' => 'Direktur Umum',
        'dirtek' => 'Direktur Teknik',
        'capeg' => 'Calon Pegawai',
        'honor' => 'Honorer',
        'kontrak' => 'Tenaga Kontrak',
        'tt' => 'Tenaga Tidak Tetap',
    ];

    public const KOMPONEN_PENDAPATAN = [
        'gapok' => 'Gaji Pokok',
        'tunjangan_istri' => 'Tunjangan Istri/Suami',
        'tunjangan_anak' => 'Tunjangan Anak',
        'tunjangan_prestasi' => 'Tunjangan Prestasi',
        'tunjangan_jabatan' => 'Tunjangan Jabatan',
        'tunjangan_transport' => 'Tunjangan Transport',
        'tunjangan_pangan' => 'Tunjangan Pangan',
        'tunjangan_bpjstk' => 'Tunjangan BPJS-TK',
        'tunjangan_perumahan' => 'Tunjangan Perumahan',
        'tunjangan_perusahaan' => 'Tunjangan Perusahaan',
        'tunjangan_airminum' => 'Tunjangan Air Minum',
        'tunjangan_bpjskes' => 'Tunjangan BPJS Kesehatan',
        'tunjangan_komunikasi' => 'Tunjangan Komunikasi',
        'tunjangan_pajak' => 'Tunjangan Pajak',
        'lembur' => 'Uang Lembur',
    ];

    public const KOMPONEN_POTONGAN = [
        'potongan_sanksi' => 'Potongan Sanksi',
        'potongan_dapenma' => 'Potongan Dapenma',
        'potongan_bpjstk' => 'Potongan BPJS-TK',
        'potongan_bpjskes' => 'Potongan BPJS Kesehatan',
        'potongan_perumahan' => 'Potongan Perumahan',
        'potongan_pajak' => 'Potongan Pajak (PPh 21)',
        'potongan_korpri' => 'Potongan Korpri',
        'potongan_tperusahaan' => 'Potongan T. Perusahaan',
        'potongan_lain' => 'Potongan Lain-lain',
        'potongan_koperasi' => 'Potongan Koperasi',
        'potongan_darmawanita' => 'Potongan Darma Wanita',
        'potongan_ledeng' => 'Potongan Ledeng',
        'potongan_kas' => 'Potongan Kas',
        'potongan_bjb' => 'Potongan BJB',
        'potongan_bjbs' => 'Potongan BJBS',
        'potongan_asuransi' => 'Potongan Asuransi',
        'potongan_btn' => 'Potongan BTN',
        'potongan_bpr' => 'Potongan BPR',
        'potongan_zakat' => 'Potongan Zakat',
    ];

    protected function storageFile(): string
    {
        return storage_path('app/gaji_proses.json');
    }

    protected function getLocalData(): array
    {
        $file = $this->storageFile();
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    public function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('payroll')
                ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'payroll.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan'
                )
                ->orderBy('payroll.id', 'desc')
                ->get();

            return $rows->map(fn ($r) => $this->mapPayrollRowToGajiArray($r))->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB payroll read failed, fallback to local: ' . $e->getMessage());
        }

        return [];
    }

    public function save(array $data): void
    {
        $file = $this->storageFile();
        $clean = array_values($data);
        @file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT));
    }

    protected function mapPayrollRowToGajiArray(object $r): array
    {
        $status = strtolower($r->status ?? 'draft');
        if ($status === 'diterbitkan') {
            $status = 'terbit';
        }

        $nik = (string) ($r->p_nik ?? $r->nik ?? '');
        $nama = (string) ($r->p_name ?? $r->nama ?? '');

        if (empty($nik) || empty($nama)) {
            $p = $this->pegawaiById($r->pegawai_id);
            if ($p) {
                $nik = $p['nik'] ?? $nik;
                $nama = $p['nama'] ?? $nama;
            }
        }

        $totalPendapatan = (float) ($r->total_pendapatan ?? 0);
        $totalPotongan = (float) ($r->total_potongan ?? 0);
        $gajiBersih = (float) ($r->gaji_bersih ?? 0);

        $lemburPayroll = (float) ($r->lembur ?? 0);
        $jamLembur = 0;
        $lemburFinal = $lemburPayroll;

        if (!empty($r->pegawai_id)) {
            $tahunRow = (int) ($r->tahun ?? now()->year);
            $bulanRow = (int) ($r->bulan ?? now()->month);
            $prefix = sprintf('%04d-%02d', $tahunRow, $bulanRow);
            $monthPadded = str_pad($bulanRow, 2, '0', STR_PAD_LEFT);

            try {
                $pRow = \Illuminate\Support\Facades\DB::table('prestasi')
                    ->where('pegawai_id', $r->pegawai_id)
                    ->where(function ($q) use ($prefix, $tahunRow, $bulanRow, $monthPadded) {
                        $q->where('tanggal', 'like', "$prefix%")
                          ->orWhere('tanggal', 'like', "%$tahunRow-$monthPadded-%")
                          ->orWhere('tanggal', 'like', "%$tahunRow-$bulanRow-%");
                    })
                    ->orderByDesc('id')
                    ->first();

                if ($pRow) {
                    $meta = json_decode($pRow->keterangan ?? '{}', true) ?: [];
                    $jamLembur = (float) ($meta['jam_lembur'] ?? ($pRow->jam_lembur ?? 0));
                    $lemburFinal = (float) ($meta['nominal_lembur'] ?? round($jamLembur * PrestasiController::RATE_LEMBUR_PER_JAM));
                } else {
                    $bulanNama = AbsensiController::BULAN[$bulanRow] ?? '';
                    $lRow = \Illuminate\Support\Facades\DB::table('lembur')
                        ->where('pegawai_id', $r->pegawai_id)
                        ->where(function ($q) use ($bulanNama, $tahunRow) {
                            $q->where('bulan', 'ilike', "%$bulanNama $tahunRow%");
                        })
                        ->first();
                    if ($lRow) {
                        $jamLembur = (float) ($lRow->jam_lembur ?? 0);
                        $lemburFinal = (float) ($lRow->uang_lembur ?? 0);
                    }
                }
            } catch (\Throwable $e) {}
        }

        if ($totalPendapatan <= 0) {
            $totalPendapatan = (float) ($r->gapok ?? 0)
                + (float) ($r->tunjangan_istri ?? 0)
                + (float) ($r->tunjangan_anak ?? 0)
                + (float) ($r->tunjangan_prestasi ?? 0)
                + (float) ($r->tunjangan_jabatan ?? 0)
                + (float) ($r->tunjangan_transportasi ?? 0)
                + (float) ($r->tunjangan_pangan ?? 0)
                + (float) ($r->tunjangan_bpjs_tenaga_kerja ?? 0)
                + (float) ($r->tunjangan_perumahan ?? 0)
                + (float) ($r->tunjangan_perusahaan ?? 0)
                + (float) ($r->tunjangan_air_minum ?? 0)
                + (float) ($r->tunjangan_bpjs_kesehatan ?? 0)
                + (float) ($r->tunjangan_komunikasi ?? 0)
                + (float) ($r->tunjangan_pajak ?? 0)
                + $lemburFinal;
        } else {
            // Sesuaikan total jika ada selisih lembur dari Prestasi
            if ($lemburFinal !== $lemburPayroll) {
                $totalPendapatan += ($lemburFinal - $lemburPayroll);
            }
        }

        if ($totalPotongan <= 0) {
            $totalPotongan = (float) ($r->potongan_sanksi_perusahaan ?? 0)
                + (float) ($r->potongan_dapenma ?? 0)
                + (float) ($r->potongan_bpjs_tenaga_kerja ?? 0)
                + (float) ($r->potongan_bpjs_kesehatan ?? 0)
                + (float) ($r->potongan_perumahan ?? 0)
                + (float) ($r->potongan_pajak ?? 0)
                + (float) ($r->potongan_korpri ?? 0)
                + (float) ($r->potongan_tunjangan_perusahaan ?? 0)
                + (float) ($r->potongan_trandist_pmi_lain ?? 0)
                + (float) ($r->potongan_koperasi ?? 0)
                + (float) ($r->potongan_darma_wanita ?? 0)
                + (float) ($r->potongan_rekening_air_minum ?? 0)
                + (float) ($r->potongan_kas ?? 0)
                + (float) ($r->potongan_bank_bjb ?? 0)
                + (float) ($r->potongan_bank_bjbs ?? 0)
                + (float) ($r->potongan_asuransi ?? 0)
                + (float) ($r->potongan_bank_btn ?? 0)
                + (float) ($r->potongan_bank_bpr ?? 0)
                + (float) ($r->potongan_zakat_profesi ?? 0);
        }

        $gajiBersih = $totalPendapatan - $totalPotongan;

        return [
            'id' => $r->id,
            'pegawai_id' => $r->pegawai_id,
            'nik' => $nik,
            'nama' => $nama,
            'jabatan' => (string) ($r->p_jabatan ?? $r->jabatan ?? 'Pegawai'),
            'unit_kerja' => (string) ($r->p_unit_kerja ?? $r->unit_kerja ?? 'Kantor Pusat'),
            'golongan' => (string) ($r->p_golongan ?? $r->golongan ?? 'III/a'),
            'kategori' => (string) ($r->kategori ?? 'satuan'),
            'kode_ptkp' => (string) ($r->kode_ptkp ?? 'K1'),
            'bulan' => (int) ($r->bulan ?? now()->month),
            'tahun' => (int) ($r->tahun ?? now()->year),
            'status' => $status,
            'disetujui_oleh' => (string) ($r->disetujui_oleh ?? ''),

            // Komponen Pendapatan
            'gapok' => (float) ($r->gapok ?? 0),
            'tunjangan_istri' => (float) ($r->tunjangan_istri ?? 0),
            'tunjangan_anak' => (float) ($r->tunjangan_anak ?? 0),
            'tunjangan_prestasi' => (float) ($r->tunjangan_prestasi ?? 0),
            'tunjangan_jabatan' => (float) ($r->tunjangan_jabatan ?? 0),
            'tunjangan_transport' => (float) ($r->tunjangan_transportasi ?? $r->tunjangan_transport ?? 0),
            'tunjangan_pangan' => (float) ($r->tunjangan_pangan ?? 0),
            'tunjangan_bpjstk' => (float) ($r->tunjangan_bpjs_tenaga_kerja ?? $r->tunjangan_bpjstk ?? 0),
            'tunjangan_perumahan' => (float) ($r->tunjangan_perumahan ?? 0),
            'tunjangan_perusahaan' => (float) ($r->tunjangan_perusahaan ?? 0),
            'tunjangan_airminum' => (float) ($r->tunjangan_air_minum ?? $r->tunjangan_airminum ?? 0),
            'tunjangan_bpjskes' => (float) ($r->tunjangan_bpjs_kesehatan ?? $r->tunjangan_bpjskes ?? 0),
            'tunjangan_komunikasi' => (float) ($r->tunjangan_komunikasi ?? 0),
            'tunjangan_pajak' => (float) ($r->tunjangan_pajak ?? 0),
            'lembur' => $lemburFinal,
            'jam_lembur' => $jamLembur,

            // Komponen Potongan
            'potongan_sanksi' => (float) ($r->potongan_sanksi_perusahaan ?? $r->potongan_sanksi ?? 0),
            'potongan_dapenma' => (float) ($r->potongan_dapenma ?? 0),
            'potongan_bpjstk' => (float) ($r->potongan_bpjs_tenaga_kerja ?? $r->potongan_bpjstk ?? 0),
            'potongan_bpjskes' => (float) ($r->potongan_bpjs_kesehatan ?? $r->potongan_bpjskes ?? 0),
            'potongan_perumahan' => (float) ($r->potongan_perumahan ?? 0),
            'potongan_pajak' => (float) ($r->potongan_pajak ?? 0),
            'potongan_korpri' => (float) ($r->potongan_korpri ?? 0),
            'potongan_tperusahaan' => (float) ($r->potongan_tunjangan_perusahaan ?? $r->potongan_tperusahaan ?? 0),
            'potongan_lain' => (float) ($r->potongan_trandist_pmi_lain ?? $r->potongan_lain ?? 0),
            'potongan_koperasi' => (float) ($r->potongan_koperasi ?? 0),
            'potongan_darmawanita' => (float) ($r->potongan_darma_wanita ?? $r->potongan_darmawanita ?? 0),
            'potongan_ledeng' => (float) ($r->potongan_rekening_air_minum ?? $r->potongan_ledeng ?? 0),
            'potongan_kas' => (float) ($r->potongan_kas ?? 0),
            'potongan_bjb' => (float) ($r->potongan_bank_bjb ?? $r->potongan_bjb ?? 0),
            'potongan_bjbs' => (float) ($r->potongan_bank_bjbs ?? $r->potongan_bjbs ?? 0),
            'potongan_asuransi' => (float) ($r->potongan_asuransi ?? 0),
            'potongan_btn' => (float) ($r->potongan_bank_btn ?? $r->potongan_btn ?? 0),
            'potongan_bpr' => (float) ($r->potongan_bank_bpr ?? $r->potongan_bpr ?? 0),
            'potongan_zakat' => (float) ($r->potongan_zakat_profesi ?? $r->potongan_zakat ?? 0),

            'total_pendapatan' => $totalPendapatan,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $gajiBersih,
            'is_minus' => ($gajiBersih < 0),
        ];
    }

    public function findItem(mixed $id): ?array
    {
        try {
            $row = \Illuminate\Support\Facades\DB::table('payroll')
                ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'payroll.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan'
                )
                ->where('payroll.id', $id)
                ->first();

            if ($row) {
                return $this->mapPayrollRowToGajiArray($row);
            }
        } catch (\Throwable $e) {}

        return null;
    }

    protected function pegawaiList(?string $kategori = null): array
    {
        $all = collect(app(PegawaiController::class)->all())->where('status_peg', '!=', 'PN');
        $direksiNiks = ['1711001', '1711002', '1711003'];

        if ($kategori === 'dirut') {
            return $all->filter(fn ($p) => ($p['nik'] ?? '') === '1711001' || str_contains(strtolower($p['jabatan'] ?? ''), 'direktur utama'))->values()->all();
        }

        if ($kategori === 'dirum') {
            return $all->filter(fn ($p) => ($p['nik'] ?? '') === '1711002' || str_contains(strtolower($p['jabatan'] ?? ''), 'direktur umum'))->values()->all();
        }

        if ($kategori === 'dirtek') {
            return $all->filter(fn ($p) => ($p['nik'] ?? '') === '1711003' || str_contains(strtolower($p['jabatan'] ?? ''), 'direktur teknik'))->values()->all();
        }

        if ($kategori === 'all') {
            return $all->values()->all();
        }

        // Default / satuan: Jangan satukan direksi dengan pegawai biasa
        return $all->filter(fn ($p) => !in_array($p['nik'] ?? '', $direksiNiks))->values()->all();
    }

    protected function pegawaiById(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        $pegawai = collect($this->pegawaiList('all'))->first(function ($p) use ($id) {
            return (string) ($p['id'] ?? '') === (string) $id
                || (string) ($p['db_id'] ?? '') === (string) $id
                || (string) ($p['nik'] ?? '') === (string) $id;
        });

        if ($pegawai) {
            $dbId = $pegawai['db_id'] ?? null;
            if (! $dbId && ! empty($pegawai['nik'])) {
                try {
                    $dbId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $pegawai['nik'])->value('id');
                    $pegawai['db_id'] = $dbId;
                } catch (\Throwable $e) {}
            }

            if ($dbId && empty($pegawai['keluarga'])) {
                try {
                    $pegawai['keluarga'] = \Illuminate\Support\Facades\DB::table('keluarga')
                        ->where('pegawai_id', $dbId)
                        ->get()
                        ->map(fn ($r) => (array) $r)
                        ->toArray();
                } catch (\Throwable $e) {}
            }
        }

        return $pegawai;
    }

    /**
     * Hitung tunjangan keluarga & kategori PTKP berdasarkan data keluarga pegawai,
     * mengikuti formula yang ditemukan di sistem lama.
     */
    public function hitungKeluarga(int $pegawaiId, ?Request $request = null): array
    {
        $pegawai = $this->pegawaiById($pegawaiId);
        $keluarga = $pegawai['keluarga'] ?? [];

        $kawin = collect($keluarga)->contains(function ($k) {
            $hub = strtolower(trim((string) ($k['hubungan'] ?? '')));
            return in_array($hub, ['istri/suami', 'istri', 'suami']);
        });
        // Filter anggota keluarga dengan hubungan "Anak"
        $anakList = collect($keluarga)->filter(function ($k) {
            $hub = strtolower(trim((string) ($k['hubungan'] ?? '')));
            return $hub === 'anak';
        });

        $totalAnak = $anakList->count();

        // Hitung anak yang memenuhi syarat tunjangan (Usia < 21 th, atau < 25 th jika status kuliah)
        $refBulan = (int) ($request?->input('bulan') ?: now()->month);
        $refTahun = (int) ($request?->input('tahun') ?: now()->year);
        $refDate = new \DateTime(sprintf('%04d-%02d-01', $refTahun, $refBulan));

        $anakEligibleCount = $anakList->filter(function ($k) use ($refDate) {
            $tglLahirStr = $k['tanggal_lahir'] ?? $k['tgl_lahir'] ?? null;
            if (! $tglLahirStr) {
                return true;
            }
            try {
                $bDate = new \DateTime($tglLahirStr);
                $age = $bDate->diff($refDate)->y;
                $isKuliah = in_array(strtolower(trim((string) ($k['pekerjaan'] ?? $k['status_kuliah'] ?? ''))), ['kuliah']);
                return ($age < 21) || ($age < 25 && $isKuliah);
            } catch (\Throwable $e) {
                return true;
            }
        })->count();

        // Tunjangan anak di payroll dibatasi maksimal 2 anak
        $jmlAnakTunjangan = min($anakEligibleCount, 2);

        // Tanggungan anak untuk PTKP pajak maksimal 3 anak
        $jmlAnakPajak = min($totalAnak, 3);

        $jmlIstri = $kawin ? 1 : 0;
        if ($kawin) {
            $kodePtkp = match ($jmlAnakPajak) {
                0 => 'K',
                1 => 'K1',
                2 => 'K2',
                default => 'K3',
            };
        } else {
            $kodePtkp = match ($jmlAnakPajak) {
                0 => 'TK',
                1 => 'TK1',
                2 => 'TK2',
                default => 'TK3',
            };
        }

        $bulan = (int) ($request?->input('bulan') ?: now()->month);
        $tahun = (int) ($request?->input('tahun') ?: now()->year);
        $prefix = sprintf('%04d-%02d', $tahun, $bulan);
        $monthPadded = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        $potonganKeu = null;
        if (! empty($pegawai['nik'])) {
            try {
                $potonganKeu = \Illuminate\Support\Facades\DB::table('potongan_keu')
                    ->where('tipe', 'gaji')
                    ->where('nik', $pegawai['nik'])
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->orderByDesc('id')
                    ->first();

                if (! $potonganKeu) {
                    $potonganKeu = \Illuminate\Support\Facades\DB::table('potongan_keu')
                        ->where('tipe', 'gaji')
                        ->where('nik', $pegawai['nik'])
                        ->orderByDesc('id')
                        ->first();
                }
            } catch (\Throwable $e) {}
        }

        $lemburNominal = 0;
        $jamLembur = 0;

        $dbPegId = $pegawai['db_id'] ?? null;
        if (! $dbPegId && ! empty($pegawai['nik'])) {
            $dbPegId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $pegawai['nik'])->value('id');
        }

        if ($dbPegId) {
            // 1. Cek tabel prestasi (input manual SDM)
            try {
                $pRow = \Illuminate\Support\Facades\DB::table('prestasi')
                    ->where('pegawai_id', $dbPegId)
                    ->where(function ($q) use ($prefix, $tahun, $bulan, $monthPadded) {
                        $q->where('tanggal', 'like', "$prefix%")
                          ->orWhere('tanggal', 'like', "%$tahun-$monthPadded-%")
                          ->orWhere('tanggal', 'like', "%$tahun-$bulan-%");
                    })
                    ->orderByDesc('id')
                    ->first();

                if ($pRow) {
                    $meta = json_decode($pRow->keterangan ?? '{}', true) ?: [];
                    $jamLembur = (float) ($meta['jam_lembur'] ?? ($pRow->jam_lembur ?? 0));
                    $lemburNominal = (int) ($meta['nominal_lembur'] ?? round($jamLembur * PrestasiController::RATE_LEMBUR_PER_JAM));
                }
            } catch (\Throwable $e) {}

            // 2. Jika belum ada di prestasi, cek tabel lembur
            if ($lemburNominal === 0) {
                try {
                    $bulanNama = AbsensiController::BULAN[$bulan] ?? '';
                    $lRow = \Illuminate\Support\Facades\DB::table('lembur')
                        ->where('pegawai_id', $dbPegId)
                        ->where('bulan', 'ilike', "%$bulanNama $tahun%")
                        ->first();
                    if ($lRow) {
                        $lemburNominal = (int) ($lRow->uang_lembur ?? 0);
                        $jamLembur = (float) ($lRow->jam_lembur ?? 0);
                    }
                } catch (\Throwable $e) {}
            }
        }

        $kategoriReq = $request?->input('kategori', 'satuan');
        $nik = $pegawai['nik'] ?? '';

        $isDirut = ($kategoriReq === 'dirut' || $nik === '1711001');
        $isDirum = ($kategoriReq === 'dirum' || $nik === '1711002');
        $isDirtek = ($kategoriReq === 'dirtek' || $nik === '1711003');
        $isDireksi = ($isDirut || $isDirum || $isDirtek);

        // Ambil baseline payroll terakhir pegawai (jika ada) untuk acuan komponen
        $latestPayroll = null;
        if ($dbPegId) {
            try {
                $latestPayroll = \Illuminate\Support\Facades\DB::table('payroll')
                    ->where('pegawai_id', $dbPegId)
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();
            } catch (\Throwable $e) {}
        }

        $hariKerja = (int) cache()->get('simpeg_hari_kerja', 25);
        $baseGapok = (int) ($latestPayroll->gapok ?? 3000000);
        $baseJabatan = (int) ($latestPayroll->tunjangan_jabatan ?? 0);
        $baseTransport = (int) (($latestPayroll && (int) $latestPayroll->tunjangan_transportasi > 0) ? $latestPayroll->tunjangan_transportasi : ($hariKerja * 12000));
        $basePerumahan = (int) ($latestPayroll->tunjangan_perumahan ?? 400000);
        $basePrestasi = (int) (($latestPayroll && (int) $latestPayroll->tunjangan_prestasi > 0) ? $latestPayroll->tunjangan_prestasi : 200000);
        $baseDapenma = (int) ($latestPayroll->potongan_dapenma ?? 0);
        $baseKorpri = (int) ($latestPayroll->potongan_korpri ?? 0);

        // 1. Auto-tarik Potongan DAPENMA dari master Dapenma (beban 5% dari PHDP)
        $dapenmaParticipant = null;
        try {
            $dapenmaAll = app(DapenmaController::class)->all();
            $dapenmaParticipant = collect($dapenmaAll)->first(function ($d) use ($pegawai, $nik, $dbPegId) {
                return (!empty($d['nik']) && (string) $d['nik'] === (string) $nik)
                    || (!empty($d['pegawai_id']) && (string) $d['pegawai_id'] === (string) ($pegawai['id'] ?? ''))
                    || (!empty($d['pegawai_id']) && (string) $d['pegawai_id'] === (string) $dbPegId);
            });
        } catch (\Throwable $e) {}

        $nominalDapenmaMaster = null;
        if ($dapenmaParticipant) {
            $phdp = (float) ($dapenmaParticipant['nominal_phdp'] ?? 0);
            $nominalDapenmaMaster = (int) round($dapenmaParticipant['nominal_beban'] ?? ($phdp * 0.05));
        }

        if ($nominalDapenmaMaster !== null) {
            $baseDapenma = $nominalDapenmaMaster;
        }

        // 2. Auto-tarik Potongan SANKSI dari modul Sanksi & SK Sanksi
        $sanksiInfo = null;
        $potonganSanksiNominal = 0;
        $sanksiPersen = 0;

        // Cek SK Sanksi Resmi
        try {
            $skRow = \Illuminate\Support\Facades\DB::table('sk_sanksi')
                ->where(function ($q) use ($dbPegId, $nik) {
                    if ($dbPegId) $q->where('pegawai_id', $dbPegId);
                    if ($nik) $q->orWhere('nik', $nik);
                })
                ->where(function ($q) use ($bulan, $tahun, $prefix, $monthPadded) {
                    $q->where('tanggal_berlaku', 'like', "$prefix%")
                      ->orWhere('tanggal_berlaku', 'like', "%$tahun-$monthPadded-%")
                      ->orWhere('tanggal_berlaku', 'like', "%$tahun-$bulan-%");
                })
                ->orderByDesc('id')
                ->first();

            if ($skRow) {
                $sanksiInfo = [
                    'tipe' => 'sk_sanksi',
                    'nomor_sk' => $skRow->nomor_sk ?? '-',
                    'jenis_sanksi' => $skRow->jenis_sanksi ?? 'Sanksi Disiplin',
                    'tingkat' => $skRow->tingkat ?? 'Sedang',
                    'nominal_penurunan_gaji' => (int) ($skRow->nominal_penurunan_gaji ?? 0),
                    'ringkasan' => $skRow->ringkasan_pelanggaran ?? '',
                ];
                if (!empty($skRow->nominal_penurunan_gaji) && (int) $skRow->nominal_penurunan_gaji > 0) {
                    $potonganSanksiNominal += (int) $skRow->nominal_penurunan_gaji;
                }
            }
        } catch (\Throwable $e) {}

        // Cek data sanksi di tabel sanksi (SanksiController)
        try {
            $sRows = \Illuminate\Support\Facades\DB::table('sanksi')
                ->where(function ($q) use ($dbPegId) {
                    if ($dbPegId) $q->where('pegawai_id', $dbPegId);
                })
                ->orderByDesc('id')
                ->get();

            foreach ($sRows as $sr) {
                $tglStr = $sr->tanggal ?? ($sr->created_at ?? '');
                $matchedDate = false;
                try {
                    $parsedDate = \Illuminate\Support\Carbon::parse($tglStr);
                    if ($parsedDate->month === $bulan && $parsedDate->year === $tahun) {
                        $matchedDate = true;
                    }
                } catch (\Throwable $e) {}

                if ($matchedDate) {
                    if (!$sanksiInfo) {
                        $sanksiInfo = [
                            'tipe' => 'sanksi',
                            'jenis_sanksi' => $sr->jenis_sanksi ?? 'Sanksi Pegawai',
                            'tingkat' => $sr->tingkat ?? 'Sedang',
                            'ringkasan' => $sr->keterangan ?? '',
                        ];
                    }
                    $potPersen = (float) ($sr->pot_persen ?? 0);
                    if ($potPersen > 0) {
                        $sanksiPersen = max($sanksiPersen, $potPersen);
                    }
                }
            }
        } catch (\Throwable $e) {}

        $potonganSanksiFinal = $potonganSanksiNominal;
        if ($sanksiPersen > 0) {
            $potonganSanksiFinal += (int) round(($baseGapok / 100) * $sanksiPersen);
        }

        // Data Penalti Kehadiran (modul SDM: prestasi) & Keterlambatan (modul SDM: attendance)
        $alpha = 0;
        $izinKet = 0;
        $izinNonKet = 0;
        $sakitKet = 0;
        $sakitNonKet = 0;
        $karyaScore = 50;
        $absensiScore = 50;

        if (isset($pRow) && $pRow) {
            $pMeta = json_decode($pRow->keterangan ?? '{}', true) ?: [];
            $alpha = (int) ($pMeta['alpha'] ?? 0);
            $izinKet = (int) ($pMeta['izin_ket'] ?? 0);
            $izinNonKet = (int) ($pMeta['izin_non_ket'] ?? 0);
            $sakitKet = (int) ($pMeta['sakit_ket'] ?? 0);
            $sakitNonKet = (int) ($pMeta['sakit_non_ket'] ?? 0);
            $karyaScore = is_numeric($pMeta['karya'] ?? null) ? (float) $pMeta['karya'] : 50;
            $absensiScore = is_numeric($pMeta['absensi'] ?? null) ? (float) $pMeta['absensi'] : 50;
        }

        // Cek keterlambatan di tabel attendance
        $telat = 0;
        if ($dbPegId) {
            try {
                $attRow = \Illuminate\Support\Facades\DB::table('attendance')
                    ->where('pegawai_id', $dbPegId)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();
                if ($attRow) {
                    $telat = (int) ($attRow->telat ?? 0);
                }
            } catch (\Throwable $e) {}
        }

        // Rumus Simpeg Lama (proses_cek_gaji_satuan.php:804-844):
        // 1. Penalti Kehadiran (Alpha: 10%, Izin Ket: 5%, Izin Non Ket: 8%, Sakit Ket: 1%, Sakit Non Ket: 3%)
        $persenPenaltiKehadiran = ($alpha * 0.10) + ($izinKet * 0.05) + ($izinNonKet * 0.08) + ($sakitKet * 0.01) + ($sakitNonKet * 0.03);

        // 2. Penalti Keterlambatan (Telat: 3% per kali)
        $persenPenaltiTelat = ($telat * 0.03);

        // 3. Potongan Tunjangan Transportasi:
        $potTransport = (int) round($baseTransport * $persenPenaltiKehadiran);
        $calcTransport = max(0, $baseTransport - $potTransport);

        // 4. Kalkulasi Tunjangan Prestasi:
        $ubahPersen = ($karyaScore + $absensiScore) / 100;
        $skorPrestasi = $ubahPersen - ($persenPenaltiTelat + $persenPenaltiKehadiran);

        // Aturan Ambang Batas 30%:
        // Jika total skor prestasi <= 0.3 (30%), tunjangan prestasi hangus = Rp 0!
        if ($skorPrestasi > 0.3) {
            $calcPrestasi = (int) round($basePrestasi * $skorPrestasi);
        } else {
            $calcPrestasi = 0;
        }
        $potPrestasi = max(0, $basePrestasi - $calcPrestasi);

        $gapokDireksi = null;
        $tunjTransportDireksi = null;
        $tunjIstriDireksi = null;
        $tunjAnakDireksi = null;
        $tunjBpjstkDireksi = null;
        $tunjBpjskesDireksi = null;
        $potBpjstkDireksi = null;
        $potBpjskesDireksi = null;
        $autoKomponen = [];

        if ($isDireksi) {
            $baseNonDireksi = $this->getBasePendapatanNonDireksi($bulan, $tahun);
            $gapokDirut = floor(($baseNonDireksi + 4000000) * 4);

            if ($isDirum || $isDirtek) {
                // Cek apakah Dirut sudah punya gapok di payroll periode ini
                $dirutGapokCurrent = \Illuminate\Support\Facades\DB::table('payroll')
                    ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                    ->where('pegawai.nik', '1711001')
                    ->where('payroll.bulan', $bulan)
                    ->where('payroll.tahun', $tahun)
                    ->value('payroll.gapok');
                if ($dirutGapokCurrent && (float) $dirutGapokCurrent > 0) {
                    $gapokDirut = (float) $dirutGapokCurrent;
                }
                $gapokDireksi = floor($gapokDirut * 0.9);
            } else {
                $gapokDireksi = $gapokDirut;
            }

            $tunjIstriDireksi = $kawin ? floor($gapokDireksi * 0.10) : 0;
            $tunjAnakDireksi = floor($gapokDireksi * 0.05 * $jmlAnakTunjangan);
            $tunjTransportDireksi = floor($gapokDireksi * 0.25);
            $tunjBpjstkDireksi = round($gapokDireksi * 0.0689);

            $laporGajiBpjs = $gapokDireksi + $tunjIstriDireksi + $tunjAnakDireksi;
            $laporBpjsnya = min($laporGajiBpjs, 12000000);
            $tunjBpjskesDireksi = round($laporBpjsnya * 0.04);

            $potBpjstkDireksi = round($gapokDireksi * 0.0689 + $gapokDireksi * 0.03);
            $potBpjskesDireksi = round($laporBpjsnya * 0.05);

            $autoKomponen = [
                'gapok' => $gapokDireksi,
                'tunjangan_transport' => $tunjTransportDireksi,
                'tunjangan_istri' => $tunjIstriDireksi,
                'tunjangan_anak' => $tunjAnakDireksi,
                'tunjangan_pangan' => 0,
                'tunjangan_perumahan' => 0,
                'tunjangan_jabatan' => 0,
                'tunjangan_prestasi' => 0,
                'tunjangan_airminum' => 0,
                'tunjangan_komunikasi' => 0,
                'lembur' => 0,
                'tunjangan_bpjstk' => $tunjBpjstkDireksi,
                'tunjangan_bpjskes' => $tunjBpjskesDireksi,
                'potongan_bpjstk' => $potBpjstkDireksi,
                'potongan_bpjskes' => $potBpjskesDireksi,
                'potongan_dapenma' => 0,
                'potongan_korpri' => 0,
                'potongan_sanksi' => $potonganSanksiFinal,
            ];
        } elseif ($kategoriReq === 'capeg') {
            // Calon Pegawai: Gapok 80%, Tunj. Pangan 80%, Perumahan 0
            $gapokCapeg = floor($baseGapok * 0.8);
            $istriCapeg = $kawin ? floor($gapokCapeg * 0.10) : 0;
            $anakCapeg = floor($gapokCapeg * 0.05 * $jmlAnakTunjangan);
            $panganCapeg = floor(120000 * ($jmlAnakTunjangan + $jmlIstri + 1) * 0.8);

            // BPJS-TK Capeg (Dasar upah min UMK)
            $gapokTkCapeg = max($gapokCapeg, 2794237);
            $tunjBpjstkCapeg = (int) round($gapokTkCapeg * 0.0689);
            $potBpjstkCapeg = (int) round($gapokTkCapeg * (0.0689 + 0.03));

            // BPJS-Kes Capeg (Dasar upah max 12jt, faktor 80%)
            $upahLaporKesCapeg = $gapokCapeg + $istriCapeg + $anakCapeg + $panganCapeg;
            $laporBpjsKesCapeg = min($upahLaporKesCapeg, 12000000);
            $tunjBpjskesCapeg = (int) round(($laporBpjsKesCapeg * 0.04) * 0.8);
            $potBpjskesCapeg = (int) round(($laporBpjsKesCapeg * 0.05) * 0.8);

            $autoKomponen = [
                'gapok' => $gapokCapeg,
                'tunjangan_istri' => $istriCapeg,
                'tunjangan_anak' => $anakCapeg,
                'tunjangan_pangan' => $panganCapeg,
                'tunjangan_perumahan' => 0,
                'tunjangan_jabatan' => 0,
                'tunjangan_prestasi' => 0,
                'tunjangan_transport' => $calcTransport,
                'tunjangan_bpjstk' => $tunjBpjstkCapeg,
                'tunjangan_bpjskes' => $tunjBpjskesCapeg,
                'lembur' => $lemburNominal,
                'potongan_bpjstk' => $potBpjstkCapeg,
                'potongan_bpjskes' => $potBpjskesCapeg,
                'potongan_dapenma' => 0,
                'potongan_korpri' => $baseKorpri,
                'potongan_sanksi' => $potonganSanksiFinal,
            ];
        } elseif ($kategoriReq === 'honor') {
            // Honorer: Tidak ada tunjangan keluarga, pangan, perumahan, prestasi, jabatan, komunikasi
            $gapokTkHonor = max($baseGapok, 2794237);
            $tunjBpjstkHonor = (int) round($gapokTkHonor * 0.0689);
            $potBpjstkHonor = (int) round($gapokTkHonor * (0.0689 + 0.03));

            $laporBpjsKesHonor = min($baseGapok, 12000000);
            $tunjBpjskesHonor = (int) round($laporBpjsKesHonor * 0.04);
            $potBpjskesHonor = (int) round($laporBpjsKesHonor * 0.05);

            $autoKomponen = [
                'gapok' => $baseGapok,
                'tunjangan_istri' => 0,
                'tunjangan_anak' => 0,
                'tunjangan_pangan' => 0,
                'tunjangan_perumahan' => 0,
                'tunjangan_jabatan' => 0,
                'tunjangan_prestasi' => 0,
                'tunjangan_komunikasi' => 0,
                'tunjangan_transport' => $calcTransport,
                'tunjangan_bpjstk' => $tunjBpjstkHonor,
                'tunjangan_bpjskes' => $tunjBpjskesHonor,
                'lembur' => $lemburNominal,
                'potongan_bpjstk' => $potBpjstkHonor,
                'potongan_bpjskes' => $potBpjskesHonor,
                'potongan_dapenma' => 0,
                'potongan_korpri' => 0,
                'potongan_sanksi' => $potonganSanksiFinal,
            ];
        } elseif (in_array($kategoriReq, ['kontrak', 'tt'])) {
            // Tenaga Kontrak (PKWT) & TT: Tidak ada tunjangan keluarga, pangan, perumahan, prestasi, jabatan
            $gapokTkKontrak = max($baseGapok, 2794237);
            $tunjBpjstkKontrak = (int) round($gapokTkKontrak * 0.0689);
            $potBpjstkKontrak = (int) round($gapokTkKontrak * (0.0689 + 0.03));

            $laporBpjsKesKontrak = min($baseGapok, 12000000);
            $tunjBpjskesKontrak = (int) round($laporBpjsKesKontrak * 0.04);
            $potBpjskesKontrak = (int) round($laporBpjsKesKontrak * 0.05);

            $autoKomponen = [
                'gapok' => $baseGapok,
                'tunjangan_istri' => 0,
                'tunjangan_anak' => 0,
                'tunjangan_pangan' => 0,
                'tunjangan_perumahan' => 0,
                'tunjangan_jabatan' => 0,
                'tunjangan_prestasi' => 0,
                'tunjangan_komunikasi' => 0,
                'tunjangan_transport' => $calcTransport,
                'tunjangan_bpjstk' => $tunjBpjstkKontrak,
                'tunjangan_bpjskes' => $tunjBpjskesKontrak,
                'lembur' => $lemburNominal,
                'potongan_bpjstk' => $potBpjstkKontrak,
                'potongan_bpjskes' => $potBpjskesKontrak,
                'potongan_dapenma' => 0,
                'potongan_korpri' => 0,
                'potongan_sanksi' => $potonganSanksiFinal,
            ];
        } else {
            // Pegawai Tetap (Satuan)
            $istriSatuan = $kawin ? round($baseGapok * 0.10) : 0;
            $anakSatuan = round($baseGapok * 0.02 * $jmlAnakTunjangan);
            // Pangan Rp 120.000 per jiwa (pegawai + istri + anak maks 2)
            $panganSatuan = 120000 * ($jmlAnakTunjangan + $jmlIstri + 1);

            // BPJS-TK Satuan (Dasar upah min UMK Indramayu)
            $gapokTkSatuan = max($baseGapok, 2794237);
            $tunjBpjstkSatuan = (int) round($gapokTkSatuan * 0.0689);
            $potBpjstkSatuan = (int) round($gapokTkSatuan * (0.0689 + 0.03));

            // BPJS-Kes Satuan (Dasar upah max 12.000.000)
            $upahLaporKesSatuan = $baseGapok + $istriSatuan + $anakSatuan + $panganSatuan + $baseJabatan + (int) ($latestPayroll->tunjangan_perusahaan ?? 300000);
            $laporBpjsKesSatuan = min($upahLaporKesSatuan, 12000000);
            $tunjBpjskesSatuan = (int) round($laporBpjsKesSatuan * 0.04);
            $potBpjskesSatuan = (int) round($laporBpjsKesSatuan * 0.05);

            $autoKomponen = [
                'gapok' => $baseGapok,
                'tunjangan_istri' => $istriSatuan,
                'tunjangan_anak' => $anakSatuan,
                'tunjangan_pangan' => $panganSatuan,
                'tunjangan_perumahan' => $basePerumahan,
                'tunjangan_jabatan' => $baseJabatan,
                'tunjangan_prestasi' => $calcPrestasi,
                'tunjangan_transport' => $calcTransport,
                'tunjangan_bpjstk' => $tunjBpjstkSatuan,
                'tunjangan_bpjskes' => $tunjBpjskesSatuan,
                'lembur' => $lemburNominal,
                'potongan_bpjstk' => $potBpjstkSatuan,
                'potongan_bpjskes' => $potBpjskesSatuan,
                'potongan_dapenma' => $baseDapenma,
                'potongan_korpri' => $baseKorpri,
                'potongan_sanksi' => $potonganSanksiFinal,
            ];
        }

        // Hitung Pajak PPh 21 Otomatis (Metode TER & Gross-Up sesuai PP 58/2023 & PMK 168/2023)
        $brutoSebelumPajak = (float) ($autoKomponen['gapok'] ?? 0)
            + (float) ($autoKomponen['tunjangan_istri'] ?? 0)
            + (float) ($autoKomponen['tunjangan_anak'] ?? 0)
            + (float) ($autoKomponen['tunjangan_pangan'] ?? 0)
            + (float) ($autoKomponen['tunjangan_perumahan'] ?? 0)
            + (float) ($autoKomponen['tunjangan_jabatan'] ?? 0)
            + (float) ($autoKomponen['tunjangan_prestasi'] ?? 0)
            + (float) ($autoKomponen['tunjangan_transport'] ?? 0)
            + (float) ($autoKomponen['lembur'] ?? 0)
            + (float) ($autoKomponen['tunjangan_bpjstk'] ?? 0)
            + (float) ($autoKomponen['tunjangan_bpjskes'] ?? 0)
            + (float) ($autoKomponen['tunjangan_airminum'] ?? 0)
            + (float) ($autoKomponen['tunjangan_komunikasi'] ?? 0);

        $calcPajak = \App\Services\Pph21Calculator::hitungGrossUp($kodePtkp, $brutoSebelumPajak);

        $autoKomponen['tunjangan_pajak'] = $calcPajak['tunjangan_pajak'];
        $autoKomponen['potongan_pajak'] = $calcPajak['potongan_pajak'];

        return [
            'kawin' => $kawin,
            'jml_istri' => $jmlIstri,
            'total_anak' => $totalAnak,
            'jml_anak' => $jmlAnakTunjangan,
            'jml_anak_tunjangan' => $jmlAnakTunjangan,
            'jml_anak_pajak' => $jmlAnakPajak,
            'kode_ptkp' => $kodePtkp,
            'potongan_keu' => $potonganKeu,
            'lembur' => $isDireksi ? 0 : $lemburNominal,
            'jam_lembur' => $isDireksi ? 0 : $jamLembur,
            'is_direksi' => $isDireksi,
            'direksi_role' => $isDirut ? 'dirut' : ($isDirum ? 'dirum' : ($isDirtek ? 'dirtek' : null)),
            'gapok_direksi' => $gapokDireksi,
            'tunj_transport_direksi' => $tunjTransportDireksi,
            'tunj_istri_direksi' => $tunjIstriDireksi,
            'tunj_anak_direksi' => $tunjAnakDireksi,
            'tunj_bpjstk_direksi' => $tunjBpjstkDireksi,
            'tunj_bpjskes_direksi' => $tunjBpjskesDireksi,
            'pot_bpjstk_direksi' => $potBpjstkDireksi,
            'pot_bpjskes_direksi' => $potBpjskesDireksi,
            'kategori' => $kategoriReq,
            'auto_komponen' => $autoKomponen,
            'info_pajak' => $calcPajak,
            'info_penalti' => [
                'has_penalti' => ($persenPenaltiKehadiran > 0 || $persenPenaltiTelat > 0),
                'alpha' => $alpha,
                'izin_ket' => $izinKet,
                'izin_non_ket' => $izinNonKet,
                'sakit_ket' => $sakitKet,
                'sakit_non_ket' => $sakitNonKet,
                'telat' => $telat,
                'persen_kehadiran' => round($persenPenaltiKehadiran * 100, 1),
                'persen_telat' => round($persenPenaltiTelat * 100, 1),
                'skor_prestasi' => round($skorPrestasi * 100, 1),
                'base_transport' => $baseTransport,
                'pot_transport' => $potTransport,
                'calc_transport' => $calcTransport,
                'base_prestasi' => $basePrestasi,
                'pot_prestasi' => $potPrestasi,
                'calc_prestasi' => $calcPrestasi,
                'prestasi_hangus' => ($skorPrestasi <= 0.3 && $basePrestasi > 0),
            ],
            'info_sanksi' => [
                'has_sanksi' => ($sanksiInfo !== null || $potonganSanksiFinal > 0),
                'detail' => $sanksiInfo,
                'persen' => $sanksiPersen,
                'potongan' => $potonganSanksiFinal,
            ],
            'info_dapenma' => [
                'is_peserta' => ($dapenmaParticipant !== null),
                'nomor_peserta' => $dapenmaParticipant['nomor_peserta'] ?? null,
                'nominal_phdp' => (float) ($dapenmaParticipant['nominal_phdp'] ?? 0),
                'nominal_beban' => $nominalDapenmaMaster ?? $baseDapenma,
            ],
        ];
    }

    /**
     * Mengambil dasar pendapatan tertinggi pegawai non-direksi untuk perhitungan gaji Dirut,
     * sesuai formula sistem lama: (max_pendapatan_pegawai + 4.000.000) * 4.
     */
    protected function getBasePendapatanNonDireksi(int $bulan, int $tahun): float
    {
        // 1. Cek payroll KSPI (1711161) pada bulan & tahun tsb
        $kspi = \Illuminate\Support\Facades\DB::table('payroll')
            ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
            ->where('pegawai.nik', '1711161')
            ->where('payroll.bulan', $bulan)
            ->where('payroll.tahun', $tahun)
            ->value('payroll.total_pendapatan');

        if ($kspi && (float) $kspi > 0) {
            return (float) $kspi;
        }

        // 2. Cek payroll KSPI periode terbaru
        $kspiLatest = \Illuminate\Support\Facades\DB::table('payroll')
            ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
            ->where('pegawai.nik', '1711161')
            ->orderByDesc('payroll.tahun')
            ->orderByDesc('payroll.bulan')
            ->value('payroll.total_pendapatan');

        if ($kspiLatest && (float) $kspiLatest > 0) {
            return (float) $kspiLatest;
        }

        // 3. Cek max total_pendapatan pegawai non-direksi pada bulan & tahun tsb
        $maxCurrent = \Illuminate\Support\Facades\DB::table('payroll')
            ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
            ->whereNotIn('pegawai.nik', ['1711001', '1711002', '1711003'])
            ->where('payroll.bulan', $bulan)
            ->where('payroll.tahun', $tahun)
            ->max('payroll.total_pendapatan');

        if ($maxCurrent && (float) $maxCurrent > 0) {
            return (float) $maxCurrent;
        }

        // 4. Fallback: max total_pendapatan non-direksi secara keseluruhan di payroll
        $maxAny = \Illuminate\Support\Facades\DB::table('payroll')
            ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
            ->whereNotIn('pegawai.nik', ['1711001', '1711002', '1711003'])
            ->max('payroll.total_pendapatan');

        return (float) ($maxAny ?: 12400000);
    }

    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $kategori = $request->get('kategori');
        $status = $request->get('status');

        $gaji = collect($this->all())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when(!empty($kategori), function ($collection) use ($kategori) {
                if ($kategori === 'satuan') {
                    return $collection->where('kategori', 'satuan')
                        ->whereNotIn('nik', ['1711001', '1711002', '1711003']);
                }
                if ($kategori === 'dirut') {
                    return $collection->filter(fn ($r) => ($r['kategori'] ?? '') === 'dirut' || ($r['nik'] ?? '') === '1711001');
                }
                if ($kategori === 'dirum') {
                    return $collection->filter(fn ($r) => ($r['kategori'] ?? '') === 'dirum' || ($r['nik'] ?? '') === '1711002');
                }
                if ($kategori === 'dirtek') {
                    return $collection->filter(fn ($r) => ($r['kategori'] ?? '') === 'dirtek' || ($r['nik'] ?? '') === '1711003');
                }
                return $collection->where('kategori', $kategori);
            })
            ->when(!empty($status), function ($collection) use ($status) {
                if ($status === 'minus') {
                    return $collection->where('gaji_bersih', '<', 0);
                }
                return $collection->where('status', $status);
            })
            ->map(function ($row) {
                $row['bisa_approve'] = $this->canUserApprove($row['status']);

                return $row;
            })
            ->sortBy('nama')
            ->values();

        $pageTitle = 'Proses Gaji Bulanan';
        if ($status === 'terbit') {
            $pageTitle = 'Proses Penerbitan Gaji';
        } elseif ($kategori === 'satuan') {
            $pageTitle = 'Proses Cek Gaji Pegawai';
        } elseif ($kategori === 'dirut') {
            $pageTitle = 'Proses Cek Gaji Dirut';
        } elseif ($kategori === 'dirum') {
            $pageTitle = 'Proses Cek Gaji Dirum';
        } elseif ($kategori === 'dirtek') {
            $pageTitle = 'Proses Cek Gaji Dirtek';
        }

        $auditData = $this->getAuditData($bulan, $tahun);

        return view('gaji-proses.index', [
            'gaji' => $gaji,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'kategori' => $kategori,
            'status' => $status,
            'pageTitle' => $pageTitle,
            'bulanList' => AbsensiController::BULAN,
            'auditCounts' => [
                'absensi' => count($auditData['belumAbsensi']),
                'potongan' => count($auditData['belumPotongan']),
                'prestasi' => count($auditData['belumPrestasi']),
                'rekening' => count($auditData['belumRekening']),
                'minus' => count($auditData['gajiMinus']),
            ],
        ]);
    }

    public function audit(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $tab = $request->get('tab', 'absensi');

        $auditData = $this->getAuditData($bulan, $tahun);

        return view('gaji-proses.audit', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'tab' => $tab,
            'bulanList' => AbsensiController::BULAN,
            'auditData' => $auditData,
            'counts' => [
                'absensi' => count($auditData['belumAbsensi']),
                'potongan' => count($auditData['belumPotongan']),
                'prestasi' => count($auditData['belumPrestasi']),
                'rekening' => count($auditData['belumRekening']),
                'minus' => count($auditData['gajiMinus']),
            ],
        ]);
    }

    public function getAuditData(int $bulan, int $tahun): array
    {
        $allPegawai = collect(app(PegawaiController::class)->all())
            ->where('status_peg', '!=', 'PN')
            ->values();

        // 1. Absensi Belum Masuk
        $niksAbsensi = [];
        try {
            $niksAbsensi = \Illuminate\Support\Facades\DB::table('attendance')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->leftJoin('pegawai', 'attendance.pegawai_id', '=', 'pegawai.id')
                ->pluck('pegawai.nik')
                ->filter()
                ->all();
        } catch (\Throwable $e) {}
        $belumAbsensi = $allPegawai->filter(fn ($p) => !in_array($p['nik'] ?? '', $niksAbsensi))->values();

        // 2. Potongan Belum Masuk (tipe = gaji)
        $niksPotongan = [];
        try {
            $niksPotongan = \Illuminate\Support\Facades\DB::table('potongan_keu')
                ->where('tipe', 'gaji')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->pluck('nik')
                ->filter()
                ->all();
        } catch (\Throwable $e) {}
        $belumPotongan = $allPegawai->filter(fn ($p) => !in_array($p['nik'] ?? '', $niksPotongan))->values();

        // 3. Prestasi Belum Masuk
        $niksPrestasi = [];
        try {
            $niksPrestasi = \Illuminate\Support\Facades\DB::table('prestasi')
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->leftJoin('pegawai', 'prestasi.pegawai_id', '=', 'pegawai.id')
                ->pluck('pegawai.nik')
                ->filter()
                ->all();
        } catch (\Throwable $e) {}
        $belumPrestasi = $allPegawai->filter(fn ($p) => !in_array($p['nik'] ?? '', $niksPrestasi))->values();

        // 4. Rekening Belum Masuk
        $belumRekening = $allPegawai->filter(fn ($p) => empty($p['rekening']) && empty($p['no_rekening']))->values();

        // 5. Gaji Minus pada periode terpilih
        $gajiMinus = collect($this->all())
            ->filter(fn ($r) => ($r['bulan'] ?? 0) === $bulan && ($r['tahun'] ?? 0) === $tahun && ($r['gaji_bersih'] ?? 0) < 0)
            ->values();

        return [
            'belumAbsensi' => $belumAbsensi,
            'belumPotongan' => $belumPotongan,
            'belumPrestasi' => $belumPrestasi,
            'belumRekening' => $belumRekening,
            'gajiMinus' => $gajiMinus,
        ];
    }

    public function create(Request $request)
    {
        $kategori = $request->get('kategori', 'satuan');

        $pegawaiList = $this->pegawaiList($kategori);
        $kategoriList = self::KATEGORI;

        if ($kategori === 'satuan' || empty($kategori)) {
            $kategoriList = collect($kategoriList)
                ->except(['dirut', 'dirum', 'dirtek'])
                ->all();
        } elseif (in_array($kategori, ['dirut', 'dirum', 'dirtek'])) {
            $kategoriList = [
                $kategori => self::KATEGORI[$kategori] ?? ucfirst($kategori),
            ];
        }

        $selectedPegawaiId = null;
        if (count($pegawaiList) === 1) {
            $selectedPegawaiId = $pegawaiList[0]['id'];
        }

        $pageTitle = 'Proses Gaji Pegawai';
        if ($kategori === 'dirut') {
            $pageTitle = 'Proses Gaji Direktur Utama';
        } elseif ($kategori === 'dirum') {
            $pageTitle = 'Proses Gaji Direktur Umum';
        } elseif ($kategori === 'dirtek') {
            $pageTitle = 'Proses Gaji Direktur Teknik';
        }

        return view('gaji-proses.create', [
            'pegawaiList' => $pegawaiList,
            'kategoriList' => $kategoriList,
            'selectedKategori' => $kategori,
            'selectedPegawaiId' => $selectedPegawaiId,
            'pageTitle' => $pageTitle,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'komponenPotongan' => self::KOMPONEN_POTONGAN,
            'gapokList' => app(GajiPokokController::class)->all(),
        ]);
    }

    /**
     * Endpoint kecil dipanggil via fetch() dari form create - mengembalikan
     * hitungan tunjangan keluarga & kategori PTKP untuk pegawai yang dipilih.
     */
    public function hitungKeluargaJson(Request $request, int $pegawaiId)
    {
        return response()->json($this->hitungKeluarga($pegawaiId, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $keluargaCalc = $this->hitungKeluarga($validated['pegawai_id'], $request);

        // Ambil nominal lembur resmi dari modul Prestasi SDM (bukan dari input manual), Direksi tidak ada lembur
        $validated['lembur'] = !empty($keluargaCalc['is_direksi']) ? 0 : (int) ($keluargaCalc['lembur'] ?? 0);

        // Pastikan kategori direksi tersimpan dengan tepat
        if (($pegawai['nik'] ?? '') === '1711001') {
            $validated['kategori'] = 'dirut';
        } elseif (($pegawai['nik'] ?? '') === '1711002') {
            $validated['kategori'] = 'dirum';
        } elseif (($pegawai['nik'] ?? '') === '1711003') {
            $validated['kategori'] = 'dirtek';
        }

        $totalPendapatan = collect(array_keys(self::KOMPONEN_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $totalPotongan = collect(array_keys(self::KOMPONEN_POTONGAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $validated['nik'] = $pegawai['nik'] ?? '-';
        $validated['nama'] = $pegawai['nama'] ?? '-';
        $validated['kode_ptkp'] = $keluargaCalc['kode_ptkp'];
        $validated['total_pendapatan'] = $totalPendapatan;
        $validated['total_potongan'] = $totalPotongan;
        $validated['gaji_bersih'] = $totalPendapatan - $totalPotongan;
        $validated['status'] = 'draft';

        // Cari UUID pegawai di DB Supabase
        $dbPegawaiId = $pegawai['db_id'] ?? null;
        if (! $dbPegawaiId && ! empty($validated['nik'])) {
            $dbPegawaiId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $validated['nik'])->value('id');
        }

        $bulanNama = AbsensiController::BULAN[$validated['bulan']] ?? 'Bulan ' . $validated['bulan'];
        $periode = $bulanNama . ' ' . $validated['tahun'];

        $insertedToDb = false;
        if ($dbPegawaiId) {
            try {
                $payload = [
                    'pegawai_id' => $dbPegawaiId,
                    'periode' => $periode,
                    'tahun' => (int) $validated['tahun'],
                    'bulan' => (int) $validated['bulan'],
                    'status' => 'draft',
                    'disetujui_oleh' => null,
                    'kategori' => $validated['kategori'],
                    'kode_ptkp' => $validated['kode_ptkp'],
                    'total_pendapatan' => (int) $totalPendapatan,
                    'total_potongan' => (int) $totalPotongan,
                    'gaji_bersih' => (int) ($totalPendapatan - $totalPotongan),

                    // Komponen Pendapatan
                    'gapok' => (int) ($validated['gapok'] ?? 0),
                    'tunjangan_istri' => (int) ($validated['tunjangan_istri'] ?? 0),
                    'tunjangan_anak' => (int) ($validated['tunjangan_anak'] ?? 0),
                    'tunjangan_prestasi' => (int) ($validated['tunjangan_prestasi'] ?? 0),
                    'tunjangan_jabatan' => (int) ($validated['tunjangan_jabatan'] ?? 0),
                    'tunjangan_transportasi' => (int) ($validated['tunjangan_transport'] ?? 0),
                    'tunjangan_pangan' => (int) ($validated['tunjangan_pangan'] ?? 0),
                    'tunjangan_bpjs_tenaga_kerja' => (int) ($validated['tunjangan_bpjstk'] ?? 0),
                    'tunjangan_perumahan' => (int) ($validated['tunjangan_perumahan'] ?? 0),
                    'tunjangan_perusahaan' => (int) ($validated['tunjangan_perusahaan'] ?? 0),
                    'tunjangan_air_minum' => (int) ($validated['tunjangan_airminum'] ?? 0),
                    'tunjangan_bpjs_kesehatan' => (int) ($validated['tunjangan_bpjskes'] ?? 0),
                    'tunjangan_komunikasi' => (int) ($validated['tunjangan_komunikasi'] ?? 0),
                    'tunjangan_pajak' => (int) ($validated['tunjangan_pajak'] ?? 0),
                    'lembur' => (int) ($validated['lembur'] ?? 0),

                    // Komponen Potongan
                    'potongan_sanksi_perusahaan' => (int) ($validated['potongan_sanksi'] ?? 0),
                    'potongan_dapenma' => (int) ($validated['potongan_dapenma'] ?? 0),
                    'potongan_bpjs_tenaga_kerja' => (int) ($validated['potongan_bpjstk'] ?? 0),
                    'potongan_bpjs_kesehatan' => (int) ($validated['potongan_bpjskes'] ?? 0),
                    'potongan_perumahan' => (int) ($validated['potongan_perumahan'] ?? 0),
                    'potongan_pajak' => (int) ($validated['potongan_pajak'] ?? 0),
                    'potongan_korpri' => (int) ($validated['potongan_korpri'] ?? 0),
                    'potongan_tunjangan_perusahaan' => (int) ($validated['potongan_tperusahaan'] ?? 0),
                    'potongan_trandist_pmi_lain' => (int) ($validated['potongan_lain'] ?? 0),
                    'potongan_koperasi' => (int) ($validated['potongan_koperasi'] ?? 0),
                    'potongan_darma_wanita' => (int) ($validated['potongan_darmawanita'] ?? 0),
                    'potongan_rekening_air_minum' => (int) ($validated['potongan_ledeng'] ?? 0),
                    'potongan_kas' => (int) ($validated['potongan_kas'] ?? 0),
                    'potongan_bank_bjb' => (int) ($validated['potongan_bjb'] ?? 0),
                    'potongan_bank_bjbs' => (int) ($validated['potongan_bjbs'] ?? 0),
                    'potongan_asuransi' => (int) ($validated['potongan_asuransi'] ?? 0),
                    'potongan_bank_btn' => (int) ($validated['potongan_btn'] ?? 0),
                    'potongan_bank_bpr' => (int) ($validated['potongan_bpr'] ?? 0),
                    'potongan_zakat_profesi' => (int) ($validated['potongan_zakat'] ?? 0),
                    'updated_at' => now(),
                ];

                $existingPayroll = \Illuminate\Support\Facades\DB::table('payroll')
                    ->where('pegawai_id', $dbPegawaiId)
                    ->where('tahun', (int) $validated['tahun'])
                    ->where('bulan', (int) $validated['bulan'])
                    ->first();

                if ($existingPayroll) {
                    \Illuminate\Support\Facades\DB::table('payroll')
                        ->where('id', $existingPayroll->id)
                        ->update($payload);
                    $newId = $existingPayroll->id;
                } else {
                    $payload['created_at'] = now();
                    $newId = \Illuminate\Support\Facades\DB::table('payroll')->insertGetId($payload);
                }

                $insertedToDb = true;
                $validated['id'] = $newId;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB payroll insert/update failed: ' . $e->getMessage());
            }
        }

        // Simpan juga ke file lokal sebagai fallback
        $localData = $this->getLocalData();
        $foundLocal = false;
        foreach ($localData as &$item) {
            if (($insertedToDb && ($item['id'] ?? null) === $newId) ||
                (($item['nik'] ?? '') === $validated['nik'] && (int)($item['bulan'] ?? 0) === (int)$validated['bulan'] && (int)($item['tahun'] ?? 0) === (int)$validated['tahun'])) {
                $validated['id'] = $insertedToDb ? $newId : ($item['id'] ?? ($localData ? max(array_column($localData, 'id')) + 1 : 1));
                $item = $validated;
                $foundLocal = true;
                break;
            }
        }
        unset($item);
        if (! $foundLocal) {
            if (! $insertedToDb) {
                $newId = $localData ? max(array_column($localData, 'id')) + 1 : 1;
                $validated['id'] = $newId;
            }
            $localData[] = $validated;
        }
        $this->save($localData);

        AuditLogController::record(
            'CREATE',
            'Proses Gaji',
            "Membuat draf proses gaji pegawai {$validated['nama']} ({$validated['nik']}) periode {$validated['bulan']}/{$validated['tahun']}",
            session('simpeg_user.nik'),
            session('simpeg_user.nama_peg'),
            match ((string) (session('simpeg_user.userlevel') ?? '1')) {
                '1' => 'SDM',
                '2' => 'Keuangan',
                '7' => 'Direktur (DIRUT)',
                default => 'User Web',
            }
        );

        // Hapus cache statistik dashboard agar selalu akurat & sinkron
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_exec_stats');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        $redirectParams = [
            'bulan' => $validated['bulan'],
            'tahun' => $validated['tahun'],
        ];
        if (!empty($validated['kategori'])) {
            $redirectParams['kategori'] = $validated['kategori'];
        }

        return redirect()->route('gaji-proses.index', $redirectParams)
            ->with('success', 'Proses gaji untuk '.$validated['nama'].' berhasil disimpan dan masuk ke antrean approval.');
    }

    public function show(mixed $id)
    {
        $gaji = $this->findItem($id);
        abort_if(! $gaji, 404);

        $userLevel = (string) session('simpeg_user.userlevel', '');
        $userNik = (string) session('simpeg_user.nik', '');
        if ($userLevel === '5') {
            abort_if(($gaji['nik'] ?? '') !== $userNik, 403, 'Anda tidak berhak melihat slip gaji pegawai lain.');
            abort_unless(in_array(strtolower($gaji['status'] ?? ''), ['terbit', 'diterbitkan', 'y', 'final']), 403, 'Slip gaji periode ini belum diterbitkan dan masih dalam proses persetujuan.');
        }

        $gaji['bisa_approve'] = $this->canUserApprove($gaji['status'] ?? 'draft');

        return view('gaji-proses.show', [
            'gaji' => $gaji,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'komponenPotongan' => self::KOMPONEN_POTONGAN,
        ]);
    }

    /**
     * Approval berjenjang: Kepegawaian -> Dirum -> Dirut (final = terbit).
     * Menggantikan proses_terbit_gaji_dirum.php dkk - lihat trait
     * HasApprovalChain untuk detail alurnya.
     */
    public function terbitkanDirect(int $id): string
    {
        $row = $this->findItem($id);
        abort_if(! $row, 404);
        abort_unless($this->canUserApprove($row['status']), 403, 'Kamu tidak berhak menyetujui tahap ini.');

        $stage = $this->nextStageFor($row['status']);
        $nextStatus = ($stage === 'dirut') ? 'terbit' : $stage;
        $approverNama = session('simpeg_user.nama_peg', 'Admin');

        // Update langsung di tabel payroll di database Supabase
        try {
            $dbStatus = ($nextStatus === 'terbit') ? 'DITERBITKAN' : $nextStatus;
            \Illuminate\Support\Facades\DB::table('payroll')
                ->where('id', $id)
                ->update([
                    'status' => $dbStatus,
                    'disetujui_oleh' => $approverNama,
                    'updated_at' => now(),
                ]);

            if ($nextStatus === 'terbit') {
                $dbPayroll = \Illuminate\Support\Facades\DB::table('payroll')->where('id', $id)->first();
                if ($dbPayroll && !empty($dbPayroll->pegawai_id)) {
                    // 1. Sinkron ke tabel lembur & payroll dari data resmi Prestasi SDM
                    try {
                        $rateLembur = PrestasiController::RATE_LEMBUR_PER_JAM;
                        $prefix = sprintf('%04d-%02d', $dbPayroll->tahun, $dbPayroll->bulan);
                        $monthPadded = str_pad($dbPayroll->bulan, 2, '0', STR_PAD_LEFT);

                        // Cek apakah ada record manual dari Set Prestasi SDM untuk pegawai dan periode ini
                        $existingPrestasi = \Illuminate\Support\Facades\DB::table('prestasi')
                            ->where('pegawai_id', $dbPayroll->pegawai_id)
                            ->where(function ($q) use ($prefix, $dbPayroll, $monthPadded) {
                                $q->where('tanggal', 'like', "$prefix%")
                                  ->orWhere('tanggal', 'like', "%{$dbPayroll->tahun}-{$monthPadded}-%");
                            })
                            ->orderByDesc('id')
                            ->first();

                        $jamLembur = 0;
                        $uangLembur = 0;

                        if ($existingPrestasi) {
                            $pMeta = json_decode($existingPrestasi->keterangan ?? '{}', true) ?: [];
                            $jamLembur = (float) ($pMeta['jam_lembur'] ?? ($existingPrestasi->jam_lembur ?? 0));
                            $uangLembur = (int) ($pMeta['nominal_lembur'] ?? round($jamLembur * $rateLembur));
                        } else {
                            $bulanNama = AbsensiController::BULAN[$dbPayroll->bulan] ?? '';
                            $lRow = \Illuminate\Support\Facades\DB::table('lembur')
                                ->where('pegawai_id', $dbPayroll->pegawai_id)
                                ->where(function ($q) use ($bulanNama, $dbPayroll) {
                                    $q->where('bulan', 'ilike', "%$bulanNama {$dbPayroll->tahun}%");
                                })
                                ->first();
                            if ($lRow) {
                                $jamLembur = (float) ($lRow->jam_lembur ?? 0);
                                $uangLembur = (int) ($lRow->uang_lembur ?? 0);
                            }
                        }

                        // Jika nominal lembur dari Prestasi berbeda dengan payroll, update payroll agar sinkron
                        if ($uangLembur !== (int) ($dbPayroll->lembur ?? 0)) {
                            $selisih = $uangLembur - (int) ($dbPayroll->lembur ?? 0);
                            \Illuminate\Support\Facades\DB::table('payroll')
                                ->where('id', $id)
                                ->update([
                                    'lembur' => $uangLembur,
                                    'total_pendapatan' => \Illuminate\Support\Facades\DB::raw('total_pendapatan + ' . $selisih),
                                    'gaji_bersih' => \Illuminate\Support\Facades\DB::raw('gaji_bersih + ' . $selisih),
                                    'updated_at' => now(),
                                ]);
                            $dbPayroll->lembur = $uangLembur;
                        }

                        if ($jamLembur > 0 || $uangLembur > 0) {
                            \Illuminate\Support\Facades\DB::table('lembur')->updateOrInsert(
                                ['pegawai_id' => $dbPayroll->pegawai_id, 'bulan' => $dbPayroll->periode],
                                [
                                    'jam_lembur' => $jamLembur,
                                    'uang_lembur' => $uangLembur,
                                    'created_at' => now(),
                                ]
                            );
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Sync to lembur table failed: ' . $e->getMessage());
                    }

                    // 2. Sinkron ke tabel insentif
                    try {
                        \Illuminate\Support\Facades\DB::table('insentif')->updateOrInsert(
                            ['pegawai_id' => $dbPayroll->pegawai_id, 'periode' => $dbPayroll->periode],
                            [
                                'judul' => 'Slip Insentif ' . $dbPayroll->periode,
                                'insentif_jabatan' => $dbPayroll->tunjangan_jabatan ?? 0,
                                'insentif_prestasi' => $dbPayroll->tunjangan_prestasi ?? 0,
                                'insentif_transportasi' => $dbPayroll->tunjangan_transportasi ?? 0,
                                'insentif_pangan' => $dbPayroll->tunjangan_pangan ?? 0,
                                'insentif_bpjs_kesehatan' => $dbPayroll->tunjangan_bpjs_kesehatan ?? 0,
                                'insentif_perumahan' => $dbPayroll->tunjangan_perumahan ?? 0,
                                'insentif_bpjs_tenaga_kerja' => $dbPayroll->tunjangan_bpjs_tenaga_kerja ?? 0,
                                'insentif_perusahaan' => $dbPayroll->tunjangan_perusahaan ?? 0,
                                'lembur' => $dbPayroll->lembur ?? 0,
                                'insentif_pajak' => $dbPayroll->tunjangan_pajak ?? 0,
                                'insentif_air_minum' => $dbPayroll->tunjangan_air_minum ?? 0,
                                'insentif_komunikasi' => $dbPayroll->tunjangan_komunikasi ?? 0,
                                'potongan_sanksi_perusahaan' => $dbPayroll->potongan_sanksi_perusahaan ?? 0,
                                'potongan_dapenma' => $dbPayroll->potongan_dapenma ?? 0,
                                'potongan_bpjs_tenaga_kerja' => $dbPayroll->potongan_bpjs_tenaga_kerja ?? 0,
                                'potongan_bpjs_kesehatan' => $dbPayroll->potongan_bpjs_kesehatan ?? 0,
                                'potongan_perumahan' => $dbPayroll->potongan_perumahan ?? 0,
                                'potongan_pajak' => $dbPayroll->potongan_pajak ?? 0,
                                'potongan_korpri' => $dbPayroll->potongan_korpri ?? 0,
                                'potongan_koperasi' => $dbPayroll->potongan_koperasi ?? 0,
                                'potongan_darma_wanita' => $dbPayroll->potongan_darma_wanita ?? ($dbPayroll->potongan_darmawanita ?? 0),
                                'potongan_rekening_air_minum' => $dbPayroll->potongan_rekening_air_minum ?? ($dbPayroll->potongan_ledeng ?? 0),
                                'potongan_kas' => $dbPayroll->potongan_kas ?? 0,
                                'potongan_bank_bjb' => $dbPayroll->potongan_bank_bjb ?? ($dbPayroll->potongan_bjb ?? 0),
                                'potongan_bank_bjbs' => $dbPayroll->potongan_bank_bjbs ?? ($dbPayroll->potongan_bjbs ?? 0),
                                'potongan_bank_btn' => $dbPayroll->potongan_bank_btn ?? ($dbPayroll->potongan_btn ?? 0),
                                'potongan_bank_bpr' => $dbPayroll->potongan_bank_bpr ?? ($dbPayroll->potongan_bpr ?? 0),
                                'potongan_asuransi' => $dbPayroll->potongan_asuransi ?? 0,
                                'potongan_zakat_profesi' => $dbPayroll->potongan_zakat_profesi ?? ($dbPayroll->potongan_zakat ?? 0),
                                'created_at' => now(),
                            ]
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Sync to insentif table failed: ' . $e->getMessage());
                    }

                    // 3. Kirim Push Notification ke HP Pegawai via OneSignal saat Gaji Terbit
                    try {
                        $targetNik = $row['nik'] ?? null;
                        if (! $targetNik && ! empty($dbPayroll->pegawai_id)) {
                            $targetNik = \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbPayroll->pegawai_id)->value('nik');
                        }
                        if ($targetNik) {
                            $periodeLabel = $dbPayroll->periode ?? (($row['bulan'] ?? '') . ' ' . ($row['tahun'] ?? ''));
                            \App\Services\OneSignalService::kirimNotifikasiPegawai(
                                $targetNik,
                                'Gaji Masuk (' . $periodeLabel . ')',
                                'Gaji bulan ' . $periodeLabel . ' telah diterbitkan. Silakan periksa slip gaji Anda di aplikasi SIMPEG.',
                                ['type' => 'gaji', 'periode' => $periodeLabel]
                            );
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('OneSignal push gaji failed: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB payroll update status failed: ' . $e->getMessage());
        }

        // Update juga di file lokal
        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $updated = collect($localData)->map(function ($r) use ($id, $nextStatus, $approverNama) {
                if ((int) ($r['id'] ?? 0) === $id) {
                    $r['status'] = $nextStatus;
                    $r['disetujui_oleh'] = $approverNama;
                    if ($nextStatus === 'terbit') {
                        $r['tgl_terbit'] = now()->toDateString();
                    }
                }
                return $r;
            })->all();
            $this->save($updated);
        }

        // Catat Audit Trail ke tabel activity_logs di database PostgreSQL
        $label = self::approvalStatusLabel($nextStatus);
        $targetNik = $row['nik'] ?? '-';
        $targetNama = $row['nama'] ?? ($row['nama_peg'] ?? '-');
        AuditLogController::record(
            'APPROVE',
            'Proses Gaji',
            "Menyetujui proses gaji pegawai {$targetNama} ({$targetNik}) ke tahap: {$label}",
            session('simpeg_user.nik'),
            session('simpeg_user.nama_peg'),
            match ((string) (session('simpeg_user.userlevel') ?? '1')) {
                '1' => 'SDM',
                '2' => 'Keuangan',
                '7' => 'Direktur (DIRUT)',
                default => 'User Web',
            }
        );

        // Hapus cache statistik dashboard agar langsung update saat disetujui / terbit
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_exec_stats');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return $nextStatus;
    }

    public function terbitkan(int $id)
    {
        $nextStatus = $this->terbitkanDirect($id);
        $label = self::approvalStatusLabel($nextStatus);
        return redirect()->back()->with('success', "Gaji berhasil disetujui ({$label}) dan tersimpan di database.");
    }

    /**
     * Batch Approval ("TERBITKAN SEMUA") sesuai alur SIMPEG Lama.
     * Mengesahkan seluruh baris gaji pada periode aktif yang siap disetujui oleh akun login.
     */
    public function terbitkanSemua(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $items = collect($this->all())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $this->canUserApprove($row['status']))
            ->values();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data gaji yang memerlukan persetujuan dari akun Anda saat ini.');
        }

        $count = 0;
        $lastStatus = 'terbit';
        foreach ($items as $item) {
            $lastStatus = $this->terbitkanDirect((int) $item['id']);
            $count++;
        }

        $label = self::approvalStatusLabel($lastStatus);
        return redirect()->back()->with('success', "Berhasil Menerbitkan / Menyetujui {$count} data gaji sekaligus ({$label}).");
    }

    public function destroy(Request $request, int $id)
    {
        $row = $this->findItem($id);
        abort_if(! $row, 404);
        abort_if($row['status'] === 'terbit', 400, 'Gaji yang sudah terbit tidak bisa dihapus.');

        $bulan = $request->input('bulan', $request->query('bulan', $row['bulan'] ?? null));
        $tahun = $request->input('tahun', $request->query('tahun', $row['tahun'] ?? null));

        // Hapus dari tabel payroll Supabase
        try {
            \Illuminate\Support\Facades\DB::table('payroll')->where('id', $id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB payroll delete failed: ' . $e->getMessage());
        }

        // Hapus dari file lokal
        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $filtered = collect($localData)->reject(fn ($r) => (int)($r['id'] ?? 0) === $id)->values()->all();
            $this->save($filtered);
        }

        AuditLogController::record(
            'DELETE',
            'Proses Gaji',
            "Menghapus draf proses gaji pegawai " . ($row['nama'] ?? '') . " (" . ($row['nik'] ?? '') . ")",
            session('simpeg_user.nik'),
            session('simpeg_user.nama_peg'),
            match ((string) (session('simpeg_user.userlevel') ?? '1')) {
                '1' => 'SDM',
                '2' => 'Keuangan',
                '7' => 'Direktur (DIRUT)',
                default => 'User Web',
            }
        );

        // Hapus cache statistik dashboard
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_exec_stats');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        $kategori = $request->input('kategori', $row['kategori'] ?? null);
        $params = array_filter(['bulan' => $bulan, 'tahun' => $tahun, 'kategori' => $kategori]);

        return redirect()->route('gaji-proses.index', $params)->with('success', 'Draft proses gaji berhasil dihapus.');
    }

    protected function validateData(Request $request): array
    {
        $rules = [
            'pegawai_id' => 'required|integer',
            'kategori' => 'required|string|in:'.implode(',', array_keys(self::KATEGORI)),
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ];

        foreach (array_keys(self::KOMPONEN_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        foreach (array_keys(self::KOMPONEN_POTONGAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        return $request->validate($rules);
    }
}
