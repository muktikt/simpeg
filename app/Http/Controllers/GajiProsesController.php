<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GajiProsesController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
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

    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_gaji_proses')) {
            session()->put('dummy_gaji_proses', []);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_gaji_proses', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_gaji_proses', $data);
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
     * Hitung tunjangan keluarga & kategori PTKP berdasarkan data keluarga pegawai,
     * mengikuti formula yang ditemukan di sistem lama.
     */
    public function hitungKeluarga(int $pegawaiId): array
    {
        $pegawai = $this->pegawaiById($pegawaiId);
        $keluarga = $pegawai['keluarga'] ?? [];

        $kawin = collect($keluarga)->contains(fn ($k) => $k['hubungan'] === 'Istri/Suami');
        $jmlAnak = min(collect($keluarga)->where('hubungan', 'Anak')->count(), 2);
        $jmlAnakPajak = min(collect($keluarga)->where('hubungan', 'Anak')->count(), 3);

        $jmlIstri = $kawin ? 1 : 0;
        $kodePtkp = match ($jmlIstri + $jmlAnakPajak + 1) {
            1 => 'TK',
            2 => 'K',
            3 => 'K1',
            4 => 'K2',
            5 => 'K3',
            default => 'K3',
        };

        return [
            'kawin' => $kawin,
            'jml_istri' => $jmlIstri,
            'jml_anak' => $jmlAnak,
            'jml_anak_pajak' => $jmlAnakPajak,
            'kode_ptkp' => $kodePtkp,
        ];
    }

    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $gaji = collect($this->all())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sortBy('nama')
            ->values();

        return view('gaji-proses.index', [
            'gaji' => $gaji,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulanList' => AbsensiController::BULAN,
        ]);
    }

    public function create()
    {
        return view('gaji-proses.create', [
            'pegawaiList' => $this->pegawaiList(),
            'kategoriList' => self::KATEGORI,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'komponenPotongan' => self::KOMPONEN_POTONGAN,
            'gapokList' => session('dummy_gapok', []),
        ]);
    }

    /**
     * Endpoint kecil dipanggil via fetch() dari form create - mengembalikan
     * hitungan tunjangan keluarga & kategori PTKP untuk pegawai yang dipilih.
     */
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

        $totalPotongan = collect(array_keys(self::KOMPONEN_POTONGAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $validated['nik'] = $pegawai['nik'] ?? '-';
        $validated['nama'] = $pegawai['nama'] ?? '-';
        $validated['kode_ptkp'] = $keluargaCalc['kode_ptkp'];
        $validated['total_pendapatan'] = $totalPendapatan;
        $validated['total_potongan'] = $totalPotongan;
        $validated['gaji_bersih'] = $totalPendapatan - $totalPotongan;
        $validated['status'] = 'draft';

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('gaji-proses.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Proses gaji untuk '.$validated['nama'].' berhasil disimpan sebagai draft.');
    }

    public function show(int $id)
    {
        $gaji = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji, 404);

        return view('gaji-proses.show', [
            'gaji' => $gaji,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'komponenPotongan' => self::KOMPONEN_POTONGAN,
        ]);
    }

    /**
     * Menerbitkan gaji (draft -> terbit). Setelah terbit, dianggap final
     * dan tidak bisa diedit lagi - mengikuti pola terbitkan_gaji_all.php.
     */
    public function terbitkan(int $id)
    {
        $data = collect($this->all())->map(function ($row) use ($id) {
            if ($row['id'] === $id) {
                $row['status'] = 'terbit';
                $row['tgl_terbit'] = now()->toDateString();
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('gaji-proses.index')->with('success', 'Gaji berhasil diterbitkan.');
    }

    public function destroy(int $id)
    {
        $gaji = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji, 404);
        abort_if($gaji['status'] === 'terbit', 400, 'Gaji yang sudah terbit tidak bisa dihapus.');

        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('gaji-proses.index')->with('success', 'Draft proses gaji berhasil dihapus.');
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
