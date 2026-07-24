<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\HasApprovalChain;

class ThrController extends Controller
{
    use HasApprovalChain;
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Disamakan dengan sistem lama (proses_thr_satuan.php dkk, ~886 baris
     * per file x 8 kategori pegawai, digabung jadi 1 form dinamis).
     *
     * BEDA DENGAN Proses Gaji Bulanan (GajiProsesController):
     * - Komponen pendapatan SAMA PERSIS (15 item)
     * - Komponen potongan TIDAK ADA "Sanksi", dan dipecah jadi 2 kelompok
     *   sesuai kode asli:
     *     - Potongan dari Pendapatan (8 item): Dapenma, BPJS-TK, BPJS-Kes,
     *       Perumahan, Pajak, Korpri, T.Perusahaan, Lain-lain
     *     - Potongan Non-Pendapatan (10 item): Koperasi, Darma Wanita, Ledeng,
     *       Kas, BJB, BJBS, Asuransi, BTN, BPR, Zakat
     *   Kedua kelompok dijumlah terpisah lalu dikurangi dari Total Pendapatan.
     * - Status approval: Proses -> Terbit (sama pola dengan Gaji Proses).
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

    protected function seedIfEmpty(): void
    {
        // Pastikan data pegawai juga ada di session.
        if (! session()->has('dummy_pegawai')) {
            app(\App\Http\Controllers\PegawaiController::class)->seedIfEmpty();
        }

        if (! session()->has('dummy_thr')) {
            session()->put('dummy_thr', [
                // === Pegawai 1 - Mukti Kurniawan - 2026 (Terbit) ===
                [
                    'id' => 1, 'pegawai_id' => 1, 'kategori' => 'satuan', 'tahun' => 2026,
                    'nik' => '1711254', 'nama' => 'Mukti Kurniawan', 'kode_ptkp' => 'K',
                    'gapok' => 3800000, 'tunjangan_istri' => 380000, 'tunjangan_anak' => 0,
                    'tunjangan_prestasi' => 500000, 'tunjangan_jabatan' => 350000,
                    'tunjangan_transport' => 300000, 'tunjangan_pangan' => 250000,
                    'tunjangan_bpjstk' => 150000, 'tunjangan_perumahan' => 200000,
                    'tunjangan_perusahaan' => 100000, 'tunjangan_airminum' => 50000,
                    'tunjangan_bpjskes' => 120000, 'tunjangan_komunikasi' => 75000,
                    'tunjangan_pajak' => 180000, 'lembur' => 0,
                    'potongan_dapenma' => 95000, 'potongan_bpjstk' => 150000,
                    'potongan_bpjskes' => 120000, 'potongan_perumahan' => 100000,
                    'potongan_pajak' => 180000, 'potongan_korpri' => 15000,
                    'potongan_tperusahaan' => 50000, 'potongan_lain' => 0,
                    'potongan_koperasi' => 200000, 'potongan_darmawanita' => 0,
                    'potongan_ledeng' => 25000, 'potongan_kas' => 0,
                    'potongan_bjb' => 0, 'potongan_bjbs' => 0,
                    'potongan_asuransi' => 50000, 'potongan_btn' => 0,
                    'potongan_bpr' => 0, 'potongan_zakat' => 95000,
                    'total_pendapatan' => 6455000,
                    'total_potongan_pendapatan' => 710000,
                    'total_potongan_non_pendapatan' => 370000,
                    'thr_diterima' => 5375000,
                    'status' => 'terbit', 'disetujui_oleh' => 'Mukti Kurniawan',
                    'tgl_terbit' => '2026-06-15',
                ],
                // === Pegawai 2 - Dewi Anggraini - 2026 (Terbit) ===
                [
                    'id' => 2, 'pegawai_id' => 2, 'kategori' => 'satuan', 'tahun' => 2026,
                    'nik' => '1800001', 'nama' => 'Dewi Anggraini', 'kode_ptkp' => 'TK',
                    'gapok' => 3200000, 'tunjangan_istri' => 0, 'tunjangan_anak' => 0,
                    'tunjangan_prestasi' => 450000, 'tunjangan_jabatan' => 300000,
                    'tunjangan_transport' => 250000, 'tunjangan_pangan' => 200000,
                    'tunjangan_bpjstk' => 130000, 'tunjangan_perumahan' => 180000,
                    'tunjangan_perusahaan' => 80000, 'tunjangan_airminum' => 50000,
                    'tunjangan_bpjskes' => 100000, 'tunjangan_komunikasi' => 50000,
                    'tunjangan_pajak' => 140000, 'lembur' => 0,
                    'potongan_dapenma' => 80000, 'potongan_bpjstk' => 130000,
                    'potongan_bpjskes' => 100000, 'potongan_perumahan' => 90000,
                    'potongan_pajak' => 140000, 'potongan_korpri' => 12000,
                    'potongan_tperusahaan' => 40000, 'potongan_lain' => 0,
                    'potongan_koperasi' => 150000, 'potongan_darmawanita' => 10000,
                    'potongan_ledeng' => 25000, 'potongan_kas' => 0,
                    'potongan_bjb' => 0, 'potongan_bjbs' => 0,
                    'potongan_asuransi' => 0, 'potongan_btn' => 0,
                    'potongan_bpr' => 0, 'potongan_zakat' => 80000,
                    'total_pendapatan' => 5130000,
                    'total_potongan_pendapatan' => 592000,
                    'total_potongan_non_pendapatan' => 265000,
                    'thr_diterima' => 4273000,
                    'status' => 'terbit', 'disetujui_oleh' => 'Mukti Kurniawan',
                    'tgl_terbit' => '2026-06-15',
                ],
                // === Pegawai 3 - Nur Hidayah - 2026 (Draft) ===
                [
                    'id' => 3, 'pegawai_id' => 3, 'kategori' => 'capeg', 'tahun' => 2026,
                    'nik' => '1800003', 'nama' => 'Nur Hidayah', 'kode_ptkp' => 'TK',
                    'gapok' => 2800000, 'tunjangan_istri' => 0, 'tunjangan_anak' => 0,
                    'tunjangan_prestasi' => 300000, 'tunjangan_jabatan' => 0,
                    'tunjangan_transport' => 200000, 'tunjangan_pangan' => 200000,
                    'tunjangan_bpjstk' => 100000, 'tunjangan_perumahan' => 0,
                    'tunjangan_perusahaan' => 0, 'tunjangan_airminum' => 50000,
                    'tunjangan_bpjskes' => 80000, 'tunjangan_komunikasi' => 0,
                    'tunjangan_pajak' => 90000, 'lembur' => 0,
                    'potongan_dapenma' => 70000, 'potongan_bpjstk' => 100000,
                    'potongan_bpjskes' => 80000, 'potongan_perumahan' => 0,
                    'potongan_pajak' => 90000, 'potongan_korpri' => 10000,
                    'potongan_tperusahaan' => 0, 'potongan_lain' => 0,
                    'potongan_koperasi' => 0, 'potongan_darmawanita' => 0,
                    'potongan_ledeng' => 0, 'potongan_kas' => 0,
                    'potongan_bjb' => 0, 'potongan_bjbs' => 0,
                    'potongan_asuransi' => 0, 'potongan_btn' => 0,
                    'potongan_bpr' => 0, 'potongan_zakat' => 70000,
                    'total_pendapatan' => 3820000,
                    'total_potongan_pendapatan' => 350000,
                    'total_potongan_non_pendapatan' => 70000,
                    'thr_diterima' => 3400000,
                    'status' => 'draft', 'disetujui_oleh' => 'Proses',
                ],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_thr', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_thr', $data);
    }

    protected function pegawaiList(): array
    {
        return session('dummy_pegawai', []);
    }

    protected function pegawaiById(int $id): ?array
    {
        return collect($this->pegawaiList())->firstWhere('id', $id);
    }

    /**
     * Sama seperti GajiProsesController::hitungKeluarga() - dipakai lagi
     * di sini karena formula tunjangan keluarga identik untuk THR.
     */
    public function hitungKeluarga(int $pegawaiId): array
    {
        return app(GajiProsesController::class)->hitungKeluarga($pegawaiId);
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);

        $thr = collect($this->all())
            ->where('tahun', $tahun)
            ->map(function ($item) {
                $item['bisa_approve'] = $this->canUserApprove($item['status'] ?? '');
                return $item;
            })
            ->sortBy('nama')
            ->values();

        return view('thr.index', compact('thr', 'tahun'));
    }

    public function create()
    {
        return view('thr.create', [
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
        $validated['thr_diterima'] = $totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan);
        $validated['status'] = 'draft';
        $validated['disetujui_oleh'] = 'Proses';

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('thr.index', ['tahun' => $validated['tahun']])
            ->with('success', 'Proses THR untuk '.$validated['nama'].' berhasil disimpan sebagai draft.');
    }

    public function show(int $id)
    {
        $thr = collect($this->all())->firstWhere('id', $id);
        abort_if(! $thr, 404);

        $thr['bisa_approve'] = $this->canUserApprove($thr['status'] ?? '');

        return view('thr.show', [
            'thr' => $thr,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'potonganPendapatan' => self::POTONGAN_PENDAPATAN,
            'potonganNonPendapatan' => self::POTONGAN_NON_PENDAPATAN,
        ]);
    }

    public function terbitkan(int $id)
    {
        $data = collect($this->all())->map(function ($row) use ($id) {
            if ($row['id'] === $id) {
                $row = $this->applyApproval($row);
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('thr.index')->with('success', 'Persetujuan THR berhasil diproses.');
    }

    public function destroy(int $id)
    {
        $thr = collect($this->all())->firstWhere('id', $id);
        abort_if(! $thr, 404);
        abort_if($thr['status'] === 'terbit', 400, 'THR yang sudah terbit tidak bisa dihapus.');

        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('thr.index')->with('success', 'Draft THR berhasil dihapus.');
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
