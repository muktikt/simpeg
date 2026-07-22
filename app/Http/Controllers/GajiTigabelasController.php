<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GajiTigabelasController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
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
     * - Komponen pendapatan TIDAK ADA BPJS-TK, BPJS-Kesehatan, dan Lembur
     *   (12 item, bukan 15 - karena Gaji 13 bukan gaji bulan kerja biasa)
     * - Potongan dari Pendapatan CUMA "Pajak" (1 item, bukan 8)
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
        'tunjangan_perumahan' => 'Tunjangan Perumahan',
        'tunjangan_perusahaan' => 'Tunjangan Perusahaan',
        'tunjangan_airminum' => 'Tunjangan Air Minum',
        'tunjangan_komunikasi' => 'Tunjangan Komunikasi',
        'tunjangan_pajak' => 'Tunjangan Pajak',
    ];

    public const POTONGAN_PENDAPATAN = [
        'potongan_pajak' => 'Potongan Pajak (PPh 21)',
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

    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_gaji13')) {
            session()->put('dummy_gaji13', []);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_gaji13', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_gaji13', $data);
    }

    protected function pegawaiList(): array
    {
        return session('dummy_pegawai', []);
    }

    protected function pegawaiById(int $id): ?array
    {
        return collect($this->pegawaiList())->firstWhere('id', $id);
    }

    public function hitungKeluarga(int $pegawaiId): array
    {
        return app(GajiProsesController::class)->hitungKeluarga($pegawaiId);
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);

        $gaji13 = collect($this->all())
            ->where('tahun', $tahun)
            ->sortBy('nama')
            ->values();

        return view('gaji-tigabelas.index', compact('gaji13', 'tahun'));
    }

    public function create()
    {
        return view('gaji-tigabelas.create', [
            'pegawaiList' => $this->pegawaiList(),
            'kategoriList' => self::KATEGORI,
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

        $validated['nik'] = $pegawai['nik'] ?? '-';
        $validated['nama'] = $pegawai['nama'] ?? '-';
        $validated['kode_ptkp'] = $keluargaCalc['kode_ptkp'];
        $validated['total_pendapatan'] = $totalPendapatan;
        $validated['total_potongan_pendapatan'] = $totalPotonganPendapatan;
        $validated['total_potongan_non_pendapatan'] = $totalPotonganNonPendapatan;
        $validated['gaji13_diterima'] = $totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan);
        $validated['status'] = 'draft';
        $validated['disetujui_oleh'] = 'Proses';

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('gaji-tigabelas.index', ['tahun' => $validated['tahun']])
            ->with('success', 'Proses Gaji 13 untuk '.$validated['nama'].' berhasil disimpan sebagai draft.');
    }

    public function show(int $id)
    {
        $gaji13 = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji13, 404);

        return view('gaji-tigabelas.show', [
            'gaji13' => $gaji13,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'potonganPendapatan' => self::POTONGAN_PENDAPATAN,
            'potonganNonPendapatan' => self::POTONGAN_NON_PENDAPATAN,
        ]);
    }

    public function terbitkan(int $id)
    {
        $data = collect($this->all())->map(function ($row) use ($id) {
            if ($row['id'] === $id) {
                $row['status'] = 'terbit';
                $row['disetujui_oleh'] = session('simpeg_user.nama_peg', 'Admin');
                $row['tgl_terbit'] = now()->toDateString();
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('gaji-tigabelas.index')->with('success', 'Gaji 13 berhasil diterbitkan.');
    }

    public function destroy(int $id)
    {
        $gaji13 = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji13, 404);
        abort_if($gaji13['status'] === 'terbit', 400, 'Gaji 13 yang sudah terbit tidak bisa dihapus.');

        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('gaji-tigabelas.index')->with('success', 'Draft Gaji 13 berhasil dihapus.');
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
