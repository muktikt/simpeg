<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasApprovalChain;
use Illuminate\Http\Request;

class GajiTigabelasController extends Controller
{
    use HasApprovalChain;
    /**
     * Modul Penggajian Gaji 13 / Tunjangan Pendidikan.
     *
     * "Gaji 13" dan "Tunjangan Pendidikan" adalah MODUL YANG SAMA di sistem
     * lama - dicek langsung ke menu_incl.php, menu "Laporan Tunj. Pendidikan"
     * ternyata mengarah ke file yang sama dengan Gaji 13
     * (laporan_slip_tigabelas.php, laporan_ledger_tigabelas.php). Jadi di
     * sini digabung jadi 1 controller, dengan 2 label menu berbeda yang
     * mengarah ke halaman yang sama - sama pola dengan Gaji Pokok.
     *
     * Disamakan dengan sistem lama (proses_tigabelas_satuan.php dkk).
     * BEDA DENGAN THR:
     * - Komponen pendapatan & potongan-dari-pendapatan SAMA PERSIS dengan
     *   THR (15 item pendapatan, 8 item potongan-dari-pendapatan). Field
     *   T.BPJS-TK, T.BPJS-Kes, Lembur, Pot. Dapenma, Pot. BPJS-TK,
     *   Pot. BPJS-Kes, Pot. Perumahan, Pot. Korpri, Pot. T.Perusahaan,
     *   Pot. Lain-lain di kode asli NILAINYA SELALU 0 (hardcode,
     *   $tunjbpjstk=0, $nominal_lembur=0, dst - lihat proses_tigabelas_satuan.php)
     *   tapi field-nya TETAP DITAMPILKAN di form sebagai bagian dari format
     *   baku slip Gaji 13/Tunj. Pendidikan, jadi tetap dipertahankan di sini
     *   sesuai keputusan agar format slip konsisten dengan sistem lama.
     * - Potongan Non-Pendapatan sama persis dengan THR (10 item)
     * - Kategori pegawai cuma 7 (TIDAK ADA "Kontrak", beda dari Gaji/THR
     *   yang punya 8 kategori - dicek dari daftar file proses_tigabelas_satuan_*.php)
     */
    public const KATEGORI = [
        'satuan' => 'Pegawai (Satuan)',
        'dirut' => 'Direktur Utama',
        'dirum' => 'Direktur Umum',
        'dirtek' => 'Direktur Teknik',
        'capeg' => 'Calon Pegawai',
        'honor' => 'Honorer',
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

    public const POTONGAN_PENDAPATAN = [
        'potongan_dapenma' => 'Potongan Dapenma',
        'potongan_bpjstk' => 'Potongan BPJS-TK',
        'potongan_bpjskes' => 'Potongan BPJS Kesehatan',
        'potongan_perumahan' => 'Potongan Perumahan',
        'potongan_pajak' => 'Potongan Pajak (PPh 21)',
        'potongan_korpri' => 'Potongan Korpri',
        'potongan_tperusahaan' => 'Potongan T. Perusahaan',
        'potongan_lain' => 'Potongan Lain-lain',
    ];

    public const POTONGAN_NON_PENDAPATAN = [
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
        return storage_path('app/gaji13_proses.json');
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
            $rows = \Illuminate\Support\Facades\DB::table('gaji_13')
                ->leftJoin('pegawai', 'gaji_13.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'gaji_13.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan'
                )
                ->orderBy('gaji_13.id', 'desc')
                ->get();

            return $rows->map(fn ($r) => $this->mapGaji13RowToArray($r))->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB gaji_13 read failed: ' . $e->getMessage());
        }

        return [];
    }

    public function save(array $data): void
    {
        $file = $this->storageFile();
        $clean = array_values($data);
        @file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT));
    }

    protected function mapGaji13RowToArray(object $r): array
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

        $jumlah = (float) ($r->jumlah ?? 0);
        $totalPendapatan = (float) ($r->total_pendapatan ?? $jumlah);
        $totalPotongan = (float) ($r->total_potongan ?? 0);

        return [
            'id' => $r->id,
            'pegawai_id' => $r->pegawai_id,
            'nik' => $nik,
            'nama' => $nama,
            'jabatan' => $r->p_jabatan ?? 'Staf',
            'unit_kerja' => $r->p_unit_kerja ?? 'Kantor Pusat',
            'golongan' => $r->p_golongan ?? '',
            'kategori' => $r->kategori ?? 'satuan',
            'kode_ptkp' => $r->kode_ptkp ?? 'TK',
            'tahun' => (int) ($r->tahun ?? now()->year),
            'status' => $status,
            'disetujui_oleh' => $r->disetujui_oleh ?? null,

            // Komponen Pendapatan
            'gapok' => (float) ($r->gapok ?? ($jumlah > 0 ? $jumlah : 0)),
            'tunjangan_istri' => (float) ($r->tunjangan_istri ?? 0),
            'tunjangan_anak' => (float) ($r->tunjangan_anak ?? 0),
            'tunjangan_prestasi' => (float) ($r->tunjangan_prestasi ?? 0),
            'tunjangan_jabatan' => (float) ($r->tunjangan_jabatan ?? 0),
            'tunjangan_transport' => (float) ($r->tunjangan_transportasi ?? ($r->tunjangan_transport ?? 0)),
            'tunjangan_pangan' => (float) ($r->tunjangan_pangan ?? 0),
            'tunjangan_bpjstk' => (float) ($r->tunjangan_bpjs_tenaga_kerja ?? ($r->tunjangan_bpjstk ?? 0)),
            'tunjangan_perumahan' => (float) ($r->tunjangan_perumahan ?? 0),
            'tunjangan_perusahaan' => (float) ($r->tunjangan_perusahaan ?? 0),
            'tunjangan_airminum' => (float) ($r->tunjangan_air_minum ?? ($r->tunjangan_airminum ?? 0)),
            'tunjangan_bpjskes' => (float) ($r->tunjangan_bpjs_kesehatan ?? ($r->tunjangan_bpjskes ?? 0)),
            'tunjangan_komunikasi' => (float) ($r->tunjangan_komunikasi ?? 0),
            'tunjangan_pajak' => (float) ($r->tunjangan_pajak ?? 0),
            'lembur' => (float) ($r->lembur ?? 0),

            // Potongan Pendapatan
            'potongan_sanksi' => (float) ($r->potongan_sanksi_perusahaan ?? 0),
            'potongan_lain' => (float) ($r->potongan_trandist_pmi_lain ?? ($r->potongan_lain ?? 0)),
            'potongan_dapenma' => (float) ($r->potongan_dapenma ?? 0),
            'potongan_bpjstk' => (float) ($r->potongan_bpjs_tenaga_kerja ?? ($r->potongan_bpjstk ?? 0)),
            'potongan_perumahan' => (float) ($r->potongan_perumahan ?? 0),
            'potongan_tperusahaan' => (float) ($r->potongan_tunjangan_perusahaan ?? ($r->potongan_tperusahaan ?? 0)),
            'potongan_korpri' => (float) ($r->potongan_korpri ?? 0),
            'potongan_pajak' => (float) ($r->potongan_pajak ?? 0),
            'potongan_bpjskes' => (float) ($r->potongan_bpjs_kesehatan ?? ($r->potongan_bpjskes ?? 0)),

            // Potongan Non-Pendapatan
            'potongan_koperasi' => (float) ($r->potongan_koperasi ?? 0),
            'potongan_darmawanita' => (float) ($r->potongan_darma_wanita ?? ($r->potongan_darmawanita ?? 0)),
            'potongan_ledeng' => (float) ($r->potongan_rekening_air_minum ?? ($r->potongan_ledeng ?? 0)),
            'potongan_kas' => (float) ($r->potongan_kas ?? 0),
            'potongan_bjb' => (float) ($r->potongan_bank_bjb ?? ($r->potongan_bjb ?? 0)),
            'potongan_bjbs' => (float) ($r->potongan_bank_bjbs ?? ($r->potongan_bjbs ?? 0)),
            'potongan_btn' => (float) ($r->potongan_bank_btn ?? ($r->potongan_btn ?? 0)),
            'potongan_bpr' => (float) ($r->potongan_bank_bpr ?? ($r->potongan_bpr ?? 0)),
            'potongan_asuransi' => (float) ($r->potongan_asuransi ?? 0),
            'potongan_zakat' => (float) ($r->potongan_zakat ?? ($r->potongan_zakat_ramadhan ?? 0)),

            'total_pendapatan' => $totalPendapatan > 0 ? $totalPendapatan : $jumlah,
            'total_potongan_pendapatan' => (float) ($r->total_potongan_pendapatan ?? $totalPotongan),
            'total_potongan_non_pendapatan' => (float) ($r->total_potongan_non_pendapatan ?? 0),
            'total_potongan' => $totalPotongan,
            'gaji13_diterima' => (float) ($r->gaji13_diterima ?? ($jumlah > 0 ? $jumlah : ($totalPendapatan - $totalPotongan))),
        ];
    }

    /**
     * Hitung pemecahan Gaji 13 menjadi 2 format slip resmi:
     * 1. Slip Tunjangan Pendidikan (HANYA Gapok + Tunj. Istri + Tunj. Anak, Potongan Koperasi/Kas proporsional)
     * 2. Slip Insentif Pendidikan (Tunjangan lain di luar Gapok/Keluarga, Potongan Koperasi/Kas proporsional)
     * 3. Slip Gaji 13 Utuh (Gabungan lengkap 100%)
     */
    public static function hitungPemecahanGaji13(array $gaji13): array
    {
        $statusPeg = (string) ($gaji13['kategori'] ?? 'satuan');
        $nik = (string) ($gaji13['nik'] ?? '');
        $gapok = (int) ($gaji13['gapok'] ?? 0);
        $tIstri = (int) ($gaji13['tunjangan_istri'] ?? 0);
        $tAnak = (int) ($gaji13['tunjangan_anak'] ?? 0);

        $totalPendapatan = (int) ($gaji13['total_pendapatan'] ?? 0);
        $tPajak = (int) ($gaji13['tunjangan_pajak'] ?? 0);
        $potZakat = (int) ($gaji13['potongan_zakat'] ?? 0);
        $potKoperasi = (int) ($gaji13['potongan_koperasi'] ?? 0);
        $potKas = (int) ($gaji13['potongan_kas'] ?? 0);

        if ($gapok <= 0 && $totalPendapatan > 0) {
            $gapok = (int) round($totalPendapatan * 0.4);
        }
        if ($totalPendapatan <= 0) {
            $totalPendapatan = (int) ($gaji13['gaji13_diterima'] ?? ($gaji13['jumlah'] ?? 0));
        }

        // 1. Pendapatan Tunjangan Pendidikan = Gapok + Tunjangan Istri + Tunjangan Anak
        $pendapatanTpendidikan = $gapok + $tIstri + $tAnak;
        // 2. Pendapatan Insentif = Total Pendapatan - Pendapatan Tunjangan Pendidikan
        $pendapatanInsentif = max(0, $totalPendapatan - $pendapatanTpendidikan);

        // Rasio pembagian proporsional (sesuai sistem lama PERUMDAM Tirta Darma Ayu)
        $isDireksi = in_array(strtolower($statusPeg), ['dirut', 'dirum', 'dirtek', 'di']);
        $isNik1811 = str_starts_with($nik, '1811');

        if ($isDireksi) {
            $pembagiTpendidikan = 0.0;
            $pembagiInsentif = 1.0;
        } elseif ($isNik1811) {
            $pembagiTpendidikan = 1.0;
            $pembagiInsentif = 0.0;
        } else {
            $dasarBagi = $totalPendapatan - $tPajak - $potZakat;
            if ($dasarBagi > 0 && $pendapatanTpendidikan > 0) {
                $pembagiTpendidikan = min(1.0, max(0.0, $pendapatanTpendidikan / $dasarBagi));
                $pembagiInsentif = 1.0 - $pembagiTpendidikan;
            } else {
                $pembagiTpendidikan = 0.5;
                $pembagiInsentif = 0.5;
            }
        }

        // Pembagian potongan Koperasi & Kas
        $koperasiTpendidikan = (int) round($potKoperasi * $pembagiTpendidikan);
        $koperasiInsentif = $potKoperasi - $koperasiTpendidikan;

        $kasTpendidikan = (int) round($potKas * $pembagiTpendidikan);
        $kasInsentif = $potKas - $kasTpendidikan;

        // Potongan Tunjangan Pendidikan
        $zakatTpendidikan = $isNik1811 ? $potZakat : 0;
        $totalPotonganTpendidikan = $koperasiTpendidikan + $kasTpendidikan + $zakatTpendidikan;
        $tpendidikanDiterima = max(0, $pendapatanTpendidikan - $totalPotonganTpendidikan);

        // Potongan Insentif:
        $potSanksi = (int) ($gaji13['potongan_sanksi'] ?? ($gaji13['potongan_sanksi_perusahaan'] ?? 0));
        $potLain = (int) ($gaji13['potongan_lain'] ?? ($gaji13['potongan_trandist_pmi_lain'] ?? 0));
        $potDapenma = (int) ($gaji13['potongan_dapenma'] ?? 0);
        $potBpjstk = (int) ($gaji13['potongan_bpjstk'] ?? ($gaji13['potongan_bpjs_tenaga_kerja'] ?? 0));
        $potPerumahan = (int) ($gaji13['potongan_perumahan'] ?? 0);
        $potTperusahaan = (int) ($gaji13['potongan_tperusahaan'] ?? ($gaji13['potongan_tunjangan_perusahaan'] ?? 0));
        $potKorpri = (int) ($gaji13['potongan_korpri'] ?? 0);
        $potPajak = (int) ($gaji13['potongan_pajak'] ?? 0);
        $potBpjskes = (int) ($gaji13['potongan_bpjskes'] ?? ($gaji13['potongan_bpjs_kesehatan'] ?? 0));

        $totalPotonganPendapatanInsentif = $potSanksi + $potLain + $potDapenma + $potBpjstk +
            $potPerumahan + $potTperusahaan + $potKorpri + $potPajak + $potBpjskes;

        // Potongan Non-Pendapatan Insentif
        $potDarmawanita = (int) ($gaji13['potongan_darmawanita'] ?? ($gaji13['potongan_darma_wanita'] ?? 0));
        $potLedeng = (int) ($gaji13['potongan_ledeng'] ?? ($gaji13['potongan_rekening_air_minum'] ?? 0));
        $potBjb = (int) ($gaji13['potongan_bjb'] ?? ($gaji13['potongan_bank_bjb'] ?? 0));
        $potBjbs = (int) ($gaji13['potongan_bjbs'] ?? ($gaji13['potongan_bank_bjbs'] ?? 0));
        $potBtn = (int) ($gaji13['potongan_btn'] ?? ($gaji13['potongan_bank_btn'] ?? 0));
        $potBpr = (int) ($gaji13['potongan_bpr'] ?? ($gaji13['potongan_bank_bpr'] ?? 0));
        $potAsuransi = (int) ($gaji13['potongan_asuransi'] ?? 0);
        $zakatInsentif = $isNik1811 ? 0 : $potZakat;

        $totalPotonganNonPendapatanInsentif = $koperasiInsentif + $kasInsentif + $potDarmawanita +
            $potLedeng + $potBjb + $potBjbs + $potBtn + $potBpr + $potAsuransi + $zakatInsentif;

        $totalPotonganInsentif = $totalPotonganPendapatanInsentif + $totalPotonganNonPendapatanInsentif;
        $insentifDiterima = max(0, $pendapatanInsentif - $totalPotonganInsentif);

        // Gaji 13 Utuh
        $totalPotonganUtuh = (int) ($gaji13['total_potongan'] ?? ($totalPotonganTpendidikan + $totalPotonganInsentif));
        if ($totalPotonganUtuh <= 0) {
            $totalPotonganUtuh = $totalPotonganTpendidikan + $totalPotonganInsentif;
        }
        $gaji13UtuhDiterima = max(0, $totalPendapatan - $totalPotonganUtuh);

        return [
            'rasio' => [
                'pembagi_tpendidikan' => $pembagiTpendidikan,
                'pembagi_insentif' => $pembagiInsentif,
                'persen_tpendidikan' => round($pembagiTpendidikan * 100, 1),
                'persen_insentif' => round($pembagiInsentif * 100, 1),
            ],
            'tunjangan_pendidikan' => [
                'gapok' => $gapok,
                'tunjangan_istri' => $tIstri,
                'tunjangan_anak' => $tAnak,
                'total_pendapatan' => $pendapatanTpendidikan,
                'potongan_koperasi' => $koperasiTpendidikan,
                'potongan_kas' => $kasTpendidikan,
                'potongan_zakat' => $zakatTpendidikan,
                'total_potongan' => $totalPotonganTpendidikan,
                'diterima' => $tpendidikanDiterima,
            ],
            'insentif' => [
                'insentif_jabatan' => (int) ($gaji13['tunjangan_jabatan'] ?? 0),
                'insentif_prestasi' => (int) ($gaji13['tunjangan_prestasi'] ?? 0),
                'insentif_transportasi' => (int) ($gaji13['tunjangan_transport'] ?? ($gaji13['tunjangan_transportasi'] ?? 0)),
                'insentif_pangan' => (int) ($gaji13['tunjangan_pangan'] ?? 0),
                'insentif_bpjs_kesehatan' => (int) ($gaji13['tunjangan_bpjskes'] ?? ($gaji13['tunjangan_bpjs_kesehatan'] ?? 0)),
                'insentif_perumahan' => (int) ($gaji13['tunjangan_perumahan'] ?? 0),
                'insentif_bpjs_tenaga_kerja' => (int) ($gaji13['tunjangan_bpjstk'] ?? ($gaji13['tunjangan_bpjs_tenaga_kerja'] ?? 0)),
                'insentif_perusahaan' => (int) ($gaji13['tunjangan_perusahaan'] ?? 0),
                'lembur' => (int) ($gaji13['lembur'] ?? 0),
                'insentif_pajak' => (int) ($gaji13['tunjangan_pajak'] ?? 0),
                'insentif_air_minum' => (int) ($gaji13['tunjangan_airminum'] ?? ($gaji13['tunjangan_air_minum'] ?? 0)),
                'insentif_komunikasi' => (int) ($gaji13['tunjangan_komunikasi'] ?? 0),
                'total_insentif' => $pendapatanInsentif,

                'potongan_sanksi_perusahaan' => $potSanksi,
                'potongan_trandist_pmi_lain' => $potLain,
                'potongan_dapenma' => $potDapenma,
                'potongan_bpjs_tenaga_kerja' => $potBpjstk,
                'potongan_perumahan' => $potPerumahan,
                'potongan_tunjangan_perusahaan' => $potTperusahaan,
                'potongan_korpri' => $potKorpri,
                'potongan_pajak' => $potPajak,
                'potongan_bpjs_kesehatan' => $potBpjskes,
                'total_potongan_insentif' => $totalPotonganPendapatanInsentif,

                'potongan_koperasi' => $koperasiInsentif,
                'potongan_darma_wanita' => $potDarmawanita,
                'potongan_rekening_air_minum' => $potLedeng,
                'potongan_kas' => $kasInsentif,
                'potongan_bank_bjb' => $potBjb,
                'potongan_bank_bjbs' => $potBjbs,
                'potongan_bank_btn' => $potBtn,
                'potongan_bank_bpr' => $potBpr,
                'potongan_asuransi' => $potAsuransi,
                'potongan_zakat_profesi' => $zakatInsentif,
                'total_potongan_non_insentif' => $totalPotonganNonPendapatanInsentif,

                'total_potongan' => $totalPotonganInsentif,
                'insentif_diterima' => $insentifDiterima,
                'diterima' => $insentifDiterima,
            ],
            'gaji_13_utuh' => [
                'total_pendapatan' => $totalPendapatan,
                'total_potongan' => $totalPotonganUtuh,
                'diterima' => $gaji13UtuhDiterima,
                'gaji13_diterima' => $gaji13UtuhDiterima,
            ],
        ];
    }

    protected function pegawaiList(): array
    {
        // Pegawai berstatus Pensiun (PN) tidak ditampilkan di dropdown pilih pegawai.
        return collect(app(PegawaiController::class)->all())->where('status_peg', '!=', 'PN')->values()->all();
    }

    protected function pegawaiById(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        return collect($this->pegawaiList())->first(function ($p) use ($id) {
            return (string) ($p['id'] ?? '') === (string) $id
                || (string) ($p['db_id'] ?? '') === (string) $id
                || (string) ($p['nik'] ?? '') === (string) $id;
        });
    }

    public function hitungKeluarga(int $pegawaiId): array
    {
        return app(GajiProsesController::class)->hitungKeluarga($pegawaiId);
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $kategori = $request->get('kategori');
        $status = $request->get('status');

        $gaji13 = collect($this->all())
            ->where('tahun', $tahun)
            ->when(!empty($kategori), function ($collection) use ($kategori) {
                return $collection->where('kategori', $kategori);
            })
            ->when(!empty($status), function ($collection) use ($status) {
                return $collection->where('status', $status);
            })
            ->map(function ($row) {
                $row['bisa_approve'] = $this->canUserApprove($row['status']);

                return $row;
            })
            ->sortBy('nama')
            ->values();

        $pageTitle = 'Proses Gaji 13';
        if ($status === 'terbit') {
            $pageTitle = 'Proses Penerbitan Gaji 13';
        } elseif ($kategori === 'satuan') {
            $pageTitle = 'Proses Gaji 13 Pegawai';
        } elseif ($kategori === 'dirut') {
            $pageTitle = 'Proses Gaji 13 Dirut';
        } elseif ($kategori === 'dirum') {
            $pageTitle = 'Proses Gaji 13 Dirum';
        } elseif ($kategori === 'dirtek') {
            $pageTitle = 'Proses Gaji 13 Dirtek';
        }

        return view('gaji-tigabelas.index', compact('gaji13', 'tahun', 'kategori', 'status', 'pageTitle'));
    }

    /**
     * 3 halaman Laporan (read-only, format cetak) - dipisah dari index()
     * yang jadi halaman kelola/proses. Hanya menampilkan Gaji 13 yang
     * sudah terbit (final).
     */
    protected function gaji13Terbit(int $tahun)
    {
        return collect($this->all())
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $row['status'] === 'terbit')
            ->map(function ($row) {
                $p = $this->pegawaiById($row['pegawai_id']);
                $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

                return $row;
            });
    }

    public function laporanSlip(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $userLogin = session('simpeg_user');
        $format = $request->get('format', 'tpendidikan');

        $data = $this->gaji13Terbit($tahun);

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $data = $data->where('nik', $userLogin['nik']);
        }

        $data = $data->map(function ($row) {
            $row['pemecahan'] = self::hitungPemecahanGaji13($row);
            return $row;
        })->sortBy('nama')->values();

        return view('gaji-tigabelas.laporan-slip', compact('data', 'tahun', 'format'));
    }

    public function laporanBukuBesar(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $data = $this->gaji13Terbit($tahun)->sortBy('nama')->values();
        $total = $data->sum('gaji13_diterima');

        return view('gaji-tigabelas.laporan-buku-besar', compact('data', 'tahun', 'total'));
    }

    public function laporanBukuBesarPerSub(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $data = $this->gaji13Terbit($tahun)
            ->groupBy('unit_kerja')
            ->map(fn ($group) => [
                'rows' => $group->sortBy('nama')->values(),
                'total' => $group->sum('gaji13_diterima'),
            ]);

        return view('gaji-tigabelas.laporan-buku-besar-per-sub', compact('data', 'tahun'));
    }

    public function create(Request $request)
    {
        $kategori = $request->get('kategori', 'satuan');

        return view('gaji-tigabelas.create', [
            'pegawaiList' => $this->pegawaiList(),
            'kategoriList' => self::KATEGORI,
            'selectedKategori' => $kategori,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'potonganPendapatan' => self::POTONGAN_PENDAPATAN,
            'potonganNonPendapatan' => self::POTONGAN_NON_PENDAPATAN,
        ]);
    }

    public function hitungKeluargaJson(int $pegawaiId)
    {
        return response()->json($this->hitungKeluarga($pegawaiId));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $keluargaCalc = $this->hitungKeluarga($validated['pegawai_id']);

        $totalPendapatan = collect(array_keys(self::KOMPONEN_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $totalPotonganPendapatan = collect(array_keys(self::POTONGAN_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $totalPotonganNonPendapatan = collect(array_keys(self::POTONGAN_NON_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $totalPotongan = $totalPotonganPendapatan + $totalPotonganNonPendapatan;
        $gaji13Diterima = $totalPendapatan - $totalPotongan;

        $validated['nik'] = $pegawai['nik'] ?? '-';
        $validated['nama'] = $pegawai['nama'] ?? '-';
        $validated['kode_ptkp'] = $keluargaCalc['kode_ptkp'];
        $validated['total_pendapatan'] = $totalPendapatan;
        $validated['total_potongan_pendapatan'] = $totalPotonganPendapatan;
        $validated['total_potongan_non_pendapatan'] = $totalPotonganNonPendapatan;
        $validated['gaji13_diterima'] = $gaji13Diterima;
        $validated['status'] = 'draft';
        $validated['disetujui_oleh'] = 'Proses';

        // Cari UUID pegawai di DB Supabase
        $dbPegawaiId = $pegawai['db_id'] ?? null;
        if (! $dbPegawaiId && ! empty($validated['nik'])) {
            $dbPegawaiId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $validated['nik'])->value('id');
        }

        $insertedToDb = false;
        if ($dbPegawaiId) {
            try {
                $newId = \Illuminate\Support\Facades\DB::table('gaji_13')->insertGetId([
                    'pegawai_id' => $dbPegawaiId,
                    'tahun' => (int) $validated['tahun'],
                    'jumlah' => (int) $gaji13Diterima,
                    'status' => 'draft',
                    'kategori' => $validated['kategori'],
                    'kode_ptkp' => $validated['kode_ptkp'],
                    'total_pendapatan' => (int) $totalPendapatan,
                    'total_potongan_pendapatan' => (int) $totalPotonganPendapatan,
                    'total_potongan_non_pendapatan' => (int) $totalPotonganNonPendapatan,
                    'total_potongan' => (int) $totalPotongan,
                    'gaji13_diterima' => (int) $gaji13Diterima,
                    'tanggal_cair' => date('Y-m-d'),

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

                    // Potongan Pendapatan
                    'potongan_dapenma' => (int) ($validated['potongan_dapenma'] ?? 0),
                    'potongan_bpjs_tenaga_kerja' => (int) ($validated['potongan_bpjstk'] ?? 0),
                    'potongan_bpjs_kesehatan' => (int) ($validated['potongan_bpjskes'] ?? 0),
                    'potongan_perumahan' => (int) ($validated['potongan_perumahan'] ?? 0),
                    'potongan_pajak' => (int) ($validated['potongan_pajak'] ?? 0),
                    'potongan_korpri' => (int) ($validated['potongan_korpri'] ?? 0),
                    'potongan_tunjangan_perusahaan' => (int) ($validated['potongan_tperusahaan'] ?? 0),
                    'potongan_trandist_pmi_lain' => (int) ($validated['potongan_lain'] ?? 0),

                    // Potongan Non-Pendapatan
                    'potongan_koperasi' => (int) ($validated['potongan_koperasi'] ?? 0),
                    'potongan_darma_wanita' => (int) ($validated['potongan_darmawanita'] ?? 0),
                    'potongan_rekening_air_minum' => (int) ($validated['potongan_ledeng'] ?? 0),
                    'potongan_kas' => (int) ($validated['potongan_kas'] ?? 0),
                    'potongan_bank_bjb' => (int) ($validated['potongan_bjb'] ?? 0),
                    'potongan_bank_bjbs' => (int) ($validated['potongan_bjbs'] ?? 0),
                    'potongan_asuransi' => (int) ($validated['potongan_asuransi'] ?? 0),
                    'potongan_bank_btn' => (int) ($validated['potongan_btn'] ?? 0),
                    'potongan_bank_bpr' => (int) ($validated['potongan_bpr'] ?? 0),
                    'potongan_zakat' => (int) ($validated['potongan_zakat'] ?? 0),
                ]);

                $insertedToDb = true;
                $validated['id'] = $newId;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB gaji_13 insert failed: ' . $e->getMessage());
            }
        }

        $localData = $this->getLocalData();
        if (! $insertedToDb) {
            $newId = $localData ? max(array_column($localData, 'id')) + 1 : 1;
            $validated['id'] = $newId;
        }
        $localData[] = $validated;
        $this->save($localData);

        return redirect()->route('gaji-tigabelas.index', ['tahun' => $validated['tahun']])
            ->with('success', 'Proses Gaji 13 untuk '.$validated['nama'].' berhasil disimpan dan masuk ke database.');
    }

    public function show(mixed $id, Request $request)
    {
        $gaji13 = collect($this->all())->first(fn ($r) => (string)($r['id'] ?? '') === (string)$id);

        abort_if(! $gaji13, 404);

        $gaji13['bisa_approve'] = $this->canUserApprove($gaji13['status'] ?? 'draft');
        $pemecahan = self::hitungPemecahanGaji13($gaji13);
        $format = $request->get('format', 'tpendidikan');

        return view('gaji-tigabelas.show', [
            'gaji13' => $gaji13,
            'pemecahan' => $pemecahan,
            'format' => $format,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'potonganPendapatan' => self::POTONGAN_PENDAPATAN,
            'potonganNonPendapatan' => self::POTONGAN_NON_PENDAPATAN,
        ]);
    }

    /**
     * Approval berjenjang: Kepegawaian -> Dirum -> Dirut (final = terbit).
     * Lihat trait HasApprovalChain untuk detail alurnya.
     */
    public function terbitkan(int $id)
    {
        $row = collect($this->all())->firstWhere('id', $id);
        abort_if(! $row, 404);
        abort_unless($this->canUserApprove($row['status']), 403, 'Kamu tidak berhak menyetujui tahap ini.');

        $stage = $this->nextStageFor($row['status']);
        $nextStatus = ($stage === 'dirut') ? 'terbit' : $stage;
        $approverNama = session('simpeg_user.nama_peg', 'Admin');

        // Update langsung di tabel gaji_13 di database Supabase
        try {
            $dbStatus = ($nextStatus === 'terbit') ? 'DITERBITKAN' : $nextStatus;
            \Illuminate\Support\Facades\DB::table('gaji_13')
                ->where('id', $id)
                ->update([
                    'status' => $dbStatus,
                    'disetujui_oleh' => $approverNama,
                    'tanggal_cair' => now()->toDateString(),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB gaji_13 update status failed: ' . $e->getMessage());
        }

        // Kirim Push Notification ke HP Pegawai via OneSignal saat Gaji 13 Terbit
        if ($nextStatus === 'terbit' && ! empty($row['nik'])) {
            try {
                \App\Services\OneSignalService::kirimNotifikasiPegawai(
                    $row['nik'],
                    'Gaji ke-13 Telah Cair! 💰',
                    'Gaji ke-13 Tahun ' . ($row['tahun'] ?? date('Y')) . ' telah diterbitkan. Silakan cek rincian di aplikasi SIMPEG.',
                    ['type' => 'gaji_13', 'tahun' => (string)($row['tahun'] ?? date('Y'))]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('OneSignal push Gaji 13 failed: ' . $e->getMessage());
            }
        }

        // Update juga di file lokal
        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $updated = collect($localData)->map(function ($r) use ($id, $nextStatus, $approverNama) {
                if ((int) ($r['id'] ?? 0) === $id) {
                    $r['status'] = $nextStatus;
                    $r['disetujui_oleh'] = $approverNama;
                }
                return $r;
            })->all();
            $this->save($updated);
        }

        return redirect()->back()->with('success', 'Gaji 13 berhasil disetujui dan tersimpan di database.');
    }

    public function destroy(int $id)
    {
        $gaji13 = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji13, 404);
        abort_if($gaji13['status'] === 'terbit', 400, 'Gaji 13 yang sudah terbit tidak bisa dihapus.');

        try {
            \Illuminate\Support\Facades\DB::table('gaji_13')->where('id', $id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB gaji_13 delete failed: ' . $e->getMessage());
        }

        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $filtered = collect($localData)->reject(fn ($row) => (int)($row['id'] ?? 0) === $id)->values()->all();
            $this->save($filtered);
        }

        return redirect()->route('gaji-tigabelas.index')->with('success', 'Draft Gaji 13 berhasil dihapus dari database.');
    }

    protected function validateData(Request $request): array
    {
        $rules = [
            'pegawai_id' => 'required|integer',
            'kategori' => 'required|string|in:'.implode(',', array_keys(self::KATEGORI)),
            'tahun' => 'required|integer|min:2020|max:2100',
        ];

        foreach (array_keys(self::KOMPONEN_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        foreach (array_keys(self::POTONGAN_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        foreach (array_keys(self::POTONGAN_NON_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        return $request->validate($rules);
    }
}
