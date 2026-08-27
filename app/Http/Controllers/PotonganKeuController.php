<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PotonganKeuController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION — meniru pattern DapenmaController.
     *
     * Disamakan dengan sistem lama (set_potongan_keu.php / tambah_potongan_keu.php):
     *   tbl_potongan_keu (id_potongan_keu, tgl_potongan, nik,
     *     pot_koperasi, pot_darmawanita, pot_air, pot_kas, pot_bjb, pot_bjbs,
     *     pot_asuransi, pot_btn, pot_zakat_profesi, pot_bpjs, pot_bpr,
     *     petugas_entri, disetujui_oleh, tgl_update, status)
     *
     * Tipe: 'gaji', 'thr', 'gaji13' — masing-masing disimpan di session key berbeda.
     */

    protected array $tipeLabels = [
        'gaji'   => 'Potongan Gaji',
        'thr'    => 'Potongan THR',
        'gaji13' => 'Potongan Gaji 13',
    ];

    protected array $kolom = [
        'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
        'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
        'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
    ];

    protected array $kolomLabels = [
        'pot_koperasi'      => 'Koperasi',
        'pot_darmawanita'   => 'Darmawanita',
        'pot_air'           => 'Ledeng',
        'pot_kas'           => 'KAS',
        'pot_bjb'           => 'BJB',
        'pot_bjbs'          => 'BJBS',
        'pot_asuransi'      => 'Asuransi',
        'pot_btn'           => 'BTN',
        'pot_zakat_profesi' => 'Zakat Profesi',
        'pot_bpjs'          => 'BPJS',
        'pot_bpr'           => 'BPR',
    ];

    protected function sessionKey(string $tipe): string
    {
        return "dummy_potongan_{$tipe}";
    }

    protected function seedIfEmpty(string $tipe): void
    {
        if (! session()->has($this->sessionKey($tipe))) {
            $pegawaiList = $this->pegawaiList();
            $seed = [];
            foreach (array_slice($pegawaiList, 0, 3) as $idx => $p) {
                $row = [
                    'id'            => $idx + 1,
                    'tipe'          => $tipe,
                    'tgl_potongan'  => now()->toDateString(),
                    'nik'           => $p['nik'],
                    'pegawai_id'    => $p['id'],
                    'petugas_entri' => 'Admin',
                    'tgl_update'    => now()->toDateString(),
                    'status'        => 'N',
                ];
                foreach ($this->kolom as $k) {
                    $row[$k] = rand(0, 500) * 1000;
                }
                $seed[] = $row;
            }
            session()->put($this->sessionKey($tipe), $seed);
        }
    }

    protected function all(string $tipe): array
    {
        $this->seedIfEmpty($tipe);
        return session($this->sessionKey($tipe), []);
    }

    protected function save(string $tipe, array $data): void
    {
        session()->put($this->sessionKey($tipe), $data);
    }

    protected ?array $cachedPegawaiList = null;

    protected function pegawaiList(): array
    {
        if ($this->cachedPegawaiList !== null) {
            return $this->cachedPegawaiList;
        }

        $this->cachedPegawaiList = collect(app(PegawaiController::class)->all())
            ->where('status_peg', '!=', 'PN')
            ->values()
            ->all();

        return $this->cachedPegawaiList;
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

    protected function withCalculated(array $row, ?\Illuminate\Support\Collection $pegawaiMap = null): array
    {
        if ($pegawaiMap) {
            $p = $pegawaiMap->get($row['nik']);
        } else {
            $p = collect($this->pegawaiList())->firstWhere('nik', $row['nik']);
        }

        $row['nama'] = $p['nama'] ?? '(tidak ditemukan)';
        $total = 0;
        foreach ($this->kolom as $k) {
            $total += $row[$k] ?? 0;
        }
        $row['total'] = $total;
        return $row;
    }

    protected function validateTipe(string $tipe): void
    {
        abort_if(! array_key_exists($tipe, $this->tipeLabels), 404, 'Tipe potongan tidak valid.');
    }

    // ───── CRUD ─────

    public function index(string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        $items = collect($this->all($tipe))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap))
            ->sortBy('nik')
            ->values();

        $totalPegawai = count($pegawaiList);
        $sudahMasuk   = $items->count();
        $belumMasuk   = max(0, $totalPegawai - $sudahMasuk);

        return view('potongan-keu.index', [
            'items'       => $items,
            'tipe'        => $tipe,
            'tipeLabel'   => $this->tipeLabels[$tipe],
            'kolom'       => $this->kolom,
            'kolomLabels' => $this->kolomLabels,
            'belumMasuk'  => $belumMasuk,
        ]);
    }

    public function create(string $tipe)
    {
        $this->validateTipe($tipe);
        return view('potongan-keu.create', [
            'tipe'        => $tipe,
            'tipeLabel'   => $this->tipeLabels[$tipe],
            'kolom'       => $this->kolom,
            'kolomLabels' => $this->kolomLabels,
            'pegawaiList' => $this->pegawaiList(),
        ]);
    }

    public function store(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $validated = $this->validateData($request);

        // Cek duplikat NIK + bulan + tahun
        $peg = $this->pegawaiById((int) $validated['pegawai_id']);
        abort_if(! $peg, 404, 'Pegawai tidak ditemukan.');

        $bulan = now()->month;
        $tahun = now()->year;
        $existing = collect($this->all($tipe))->first(function ($r) use ($peg, $bulan, $tahun) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan']);
            return $r['nik'] === $peg['nik'] && $d->month === $bulan && $d->year === $tahun;
        });

        if ($existing) {
            return back()->withErrors(['pegawai_id' => 'Pegawai ini sudah ada potongan untuk bulan ini.'])->withInput();
        }

        $data = $this->all($tipe);
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;

        $row = [
            'id'            => $newId,
            'tipe'          => $tipe,
            'tgl_potongan'  => now()->toDateString(),
            'nik'           => $peg['nik'],
            'pegawai_id'    => $peg['id'],
            'petugas_entri' => session('simpeg_user.nama_peg', 'Admin'),
            'tgl_update'    => now()->toDateString(),
            'status'        => 'N',
        ];
        foreach ($this->kolom as $k) {
            $row[$k] = (int) ($validated[$k] ?? 0);
        }

        $data[] = $row;
        $this->save($tipe, $data);

        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil ditambahkan.');
    }

    public function edit(string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        $item = collect($this->all($tipe))->firstWhere('id', $id);
        abort_if(! $item, 404);

        return view('potongan-keu.edit', [
            'item'        => $item,
            'tipe'        => $tipe,
            'tipeLabel'   => $this->tipeLabels[$tipe],
            'kolom'       => $this->kolom,
            'kolomLabels' => $this->kolomLabels,
            'pegawaiList' => $this->pegawaiList(),
        ]);
    }

    public function update(Request $request, string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        $validated = $this->validateData($request);
        $peg = $this->pegawaiById((int) $validated['pegawai_id']);
        abort_if(! $peg, 404);

        $data = collect($this->all($tipe))->map(function ($row) use ($id, $validated, $peg) {
            if ($row['id'] === $id) {
                foreach ($this->kolom as $k) {
                    $row[$k] = (int) ($validated[$k] ?? 0);
                }
                $row['nik'] = $peg['nik'];
                $row['pegawai_id'] = $peg['id'];
                $row['petugas_entri'] = session('simpeg_user.nama_peg', 'Admin');
                $row['tgl_update'] = now()->toDateString();
            }
            return $row;
        })->all();

        $this->save($tipe, $data);
        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil diperbarui.');
    }

    public function destroy(string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        $data = collect($this->all($tipe))->reject(fn ($r) => $r['id'] === $id)->values()->all();
        $this->save($tipe, $data);
        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil dihapus.');
    }

    // ───── PROSES TERBIT POTONGAN (SESUAI SISTEM LAMA) ─────

    public function terbitIndex(string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        // Sesuai proses_terbit_potongan.php: filter data status 'N' (Menunggu)
        $allItems = collect($this->all($tipe))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap))
            ->sortBy('nik')
            ->values();

        $items = $allItems->where('status', '!=', 'Y')->values();
        $sudahDiterbitkan = $allItems->where('status', '===', 'Y')->count();

        $totals = [];
        foreach ($this->kolom as $k) {
            $totals[$k] = $items->sum($k);
        }
        $totals['grand_total'] = $items->sum('total');

        return view('potongan-keu.terbit', [
            'items'            => $items,
            'sudahDiterbitkan' => $sudahDiterbitkan,
            'tipe'             => $tipe,
            'tipeLabel'        => $this->tipeLabels[$tipe],
            'kolom'            => $this->kolom,
            'kolomLabels'      => $this->kolomLabels,
            'totals'           => $totals,
        ]);
    }

    public function terbitkan(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);

        $data = collect($this->all($tipe))->map(function ($row) {
            $row['status'] = 'Y';
            $row['tgl_update'] = now()->toDateString();
            return $row;
        })->all();

        $this->save($tipe, $data);
        return redirect()->route('potongan-keu.terbit', $tipe)->with('success', 'Semua potongan ' . strtolower($this->tipeLabels[$tipe]) . ' berhasil diterbitkan dan disetujui.');
    }

    // ───── BELUM MASUK ─────

    public function belumMasuk(string $tipe)
    {
        $this->validateTipe($tipe);
        $nikSudah = collect($this->all($tipe))->pluck('nik')->unique()->all();
        $belum = collect($this->pegawaiList())->reject(fn ($p) => in_array($p['nik'], $nikSudah));

        return view('potongan-keu.belum-masuk', [
            'pegawai'   => $belum->values(),
            'tipe'      => $tipe,
            'tipeLabel' => $this->tipeLabels[$tipe],
        ]);
    }

    // ───── VALIDATION ─────

    protected function validateData(Request $request): array
    {
        $rules = ['pegawai_id' => 'required|integer'];
        foreach ($this->kolom as $k) {
            $rules[$k] = 'nullable|numeric|min:0';
        }
        return $request->validate($rules);
    }
}
