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
            'gapok' => $jumlah > 0 ? $jumlah : 0,
            'total_pendapatan' => $totalPendapatan > 0 ? $totalPendapatan : $jumlah,
            'total_potongan_pendapatan' => $totalPotongan,
            'total_potongan_non_pendapatan' => 0,
            'gaji13_diterima' => $jumlah > 0 ? $jumlah : ($totalPendapatan - $totalPotongan),
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

        $data = $this->gaji13Terbit($tahun);
        $rincianAnak = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $data = $data->where('nik', $userLogin['nik']);

            // Ambil data rincian anak dinamis dari database Supabase (tabel keluarga)
            try {
                $pegawai = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $userLogin['nik'])->first();
                if ($pegawai) {
                    $anakList = \Illuminate\Support\Facades\DB::table('keluarga')
                        ->where('pegawai_id', $pegawai->id)
                        ->where(function ($q) {
                            $q->where('hubungan', 'ilike', '%anak%')
                              ->orWhere('status_keluarga', 'ilike', '%anak%')
                              ->orWhere('hubungan', 'Anak');
                        })
                        ->get();

                    foreach ($anakList as $anak) {
                        $namaAnak = $anak->nama ?? 'Anak';
                        $words = explode(' ', trim($namaAnak));
                        $inisial = '';
                        foreach (array_slice($words, 0, 2) as $w) {
                            $inisial .= strtoupper(substr($w, 0, 1));
                        }

                        $rincianAnak[] = [
                            'nama' => $namaAnak,
                            'inisial' => $inisial ?: 'AN',
                            'jenjang_singkat' => $anak->pekerjaan ?? $anak->jenjang ?? 'Pelajar',
                            'jenjang_detail' => $anak->keterangan ?? 'Anak Kandung',
                            'status' => 'Sudah Cair',
                            'status_bg' => '#dcfce7',
                            'status_color' => '#15803d',
                            'nominal' => 1218400,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Fallback jika database connection offline
            }
        }

        $data = $data->sortBy('nama')->values();

        return view('gaji-tigabelas.laporan-slip', compact('data', 'tahun', 'rincianAnak'));
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
                    'total_potongan' => (int) $totalPotongan,
                    'tanggal_cair' => date('Y-m-d'),
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

    public function show(mixed $id)
    {
        $gaji13 = collect($this->all())->first(fn ($r) => (string)($r['id'] ?? '') === (string)$id);

        abort_if(! $gaji13, 404);

        $gaji13['bisa_approve'] = $this->canUserApprove($gaji13['status'] ?? 'draft');

        return view('gaji-tigabelas.show', [
            'gaji13' => $gaji13,
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
