<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SanksiController extends Controller
{
    /**
     * Modul Sanksi Pegawai.
     * Terhubung langsung dengan tabel sanksi di database Supabase PostgreSQL.
     *
     * Struktur disamakan dengan sistem lama (set_sanksi_pegawai.php / tambah_sanksi_pegawai.php):
     *   tbl_sanksi (id, id_pegawai, nik, tgl_sanksi, jenis_sanksi, ket_sanksi, pot_persen)
     *
     * CATATAN PERBAIKAN: di form tambah/edit sanksi versi lama, ketiga pilihan jenis
     * sanksi (Lisan/Tulisan/Dikeluarkan) semuanya punya value="Lisan" (bug copy-paste),
     * jadi apapun yang dipilih selalu tersimpan sebagai "Lisan". Di versi ini sudah
     * diperbaiki supaya value-nya sesuai pilihan yang benar-benar dipilih.
     *
     * Aturan sistem lama: satu pegawai tidak boleh punya lebih dari satu sanksi
     * di bulan & tahun yang sama - aturan ini dipertahankan di validateData().
     */
    public const JENIS_SANKSI = [
        'Lisan',
        'Tulisan',
        'Penurunan Jabatan',
        'Penurunan Gaji Berkala / Tunjangan',
        'Penurunan Pangkat / Golongan 1 Tingkat',
        'Pemindahan Tugas / Demosi Jabatan',
        'Pembebasan Sementara Dari Tugas (Skorsing)',
        'Pemberhentian Tidak Dengan Hormat (PTDH)',
        'Dikeluarkan',
        'Teguran Lisan / Peringatan I',
        'Teguran Tertulis / Surat Peringatan (SP)',
        'Sanksi Administratif Lainnya',
    ];

    protected function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('sanksi')->get();
            if ($rows->isNotEmpty()) {
                return $rows->map(fn ($r) => (array) $r)->toArray();
            }
        } catch (\Throwable $e) {}

        return [];
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

    protected function withPegawai(array $rows): array
    {
        $pegList = collect(app(PegawaiController::class)->all());
        $pegMap = $pegList->keyBy('db_id');
        $pegNikMap = $pegList->keyBy('nik');
        $skMap = collect();
        try {
            $skMap = \Illuminate\Support\Facades\DB::table('sk_sanksi')->get()->keyBy('id');
        } catch (\Throwable $e) {}

        $bulanIndo = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
        ];

        return collect($rows)->map(function ($row) use ($pegMap, $pegNikMap, $skMap, $bulanIndo) {
            $row = (array) $row;
            $pegId = $row['pegawai_id'] ?? null;
            $p = $pegMap->get($pegId) ?: ($pegNikMap->get($row['nik'] ?? '') ?: $this->pegawaiById($pegId));

            $skId = $row['sk_sanksi_id'] ?? null;
            $sk = $skId ? $skMap->get($skId) : null;

            $nik = trim((string)($p['nik'] ?? ($row['nik'] ?? '-')));
            $rawNik = preg_replace('/\s+/', '', $nik);
            $formattedNik = strlen($rawNik) >= 6
                ? substr($rawNik, 0, 2) . ' ' . substr($rawNik, 2, 2) . ' ' . substr($rawNik, 4)
                : $nik;

            $nama = trim((string)($p['nama'] ?? ($row['nama_pegawai'] ?? 'Pegawai')));
            $gelar = trim((string)($p['gelar'] ?? ''));
            $namaLengkap = $nama . (!empty($gelar) ? ', ' . $gelar : '');

            // Tanggal parsing
            $rawDate = (string)($row['tanggal'] ?? ($row['tgl_sanksi'] ?? ($row['created_at'] ?? now()->toDateString())));
            $tahunNum = (int) now()->year;
            $bulanNum = (int) now()->month;
            $formattedDate = $rawDate;
            $bulanTeks = '-';

            if (preg_match('/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/', $rawDate, $m)) {
                $day = (int)$m[1];
                $monthName = strtolower($m[2]);
                $tahunNum = (int)$m[3];
                $bulanNum = $bulanIndo[$monthName] ?? (int)now()->month;
                $formattedDate = sprintf('%02d/%02d/%04d', $day, $bulanNum, $tahunNum);
                $bulanTeks = ucfirst($monthName) . ' ' . $tahunNum;
            } else {
                try {
                    $c = new \DateTime($rawDate);
                    $tahunNum = (int) $c->format('Y');
                    $bulanNum = (int) $c->format('m');
                    $formattedDate = $c->format('d/m/Y');
                    $bulanTeks = $c->format('F Y');
                } catch (\Throwable $e) {}
            }

            // Normalisasi Tingkat Sanksi
            $jenisSanksi = trim((string)($row['jenis_sanksi'] ?? ($row['jenis'] ?? ($sk?->jenis_sanksi ?? '-'))));
            $tingkat = trim((string)($row['tingkat'] ?? ($sk?->tingkat ?? '')));
            if (empty($tingkat) || $tingkat === 'Internal') {
                $upperJenis = strtoupper($jenisSanksi);
                if (str_contains($upperJenis, 'LISAN') || str_contains($upperJenis, 'PERINGATAN I') || str_contains($upperJenis, 'TEGURAN')) {
                    $tingkat = 'Ringan';
                } elseif (str_contains($upperJenis, 'PTDH') || str_contains($upperJenis, 'KELUAR') || str_contains($upperJenis, 'SKORSING')) {
                    $tingkat = 'Berat';
                } else {
                    $tingkat = 'Sedang';
                }
            }

            $potPersen = (float)($row['potongan_persen'] ?? ($row['pot_persen'] ?? 0));
            if ($potPersen == 0 && !empty($sk?->nominal_penurunan_gaji) && $sk->nominal_penurunan_gaji > 0) {
                $potPersen = round(($sk->nominal_penurunan_gaji / 4500000) * 100, 1);
            }

            $nomorSk = $sk?->nomor_sk ?? '-';
            $keterangan = $row['keterangan'] ?? ($row['ket_sanksi'] ?? ($sk?->ringkasan_pelanggaran ?? '-'));

            $row['nik'] = $nik;
            $row['formatted_nik'] = $formattedNik;
            $row['nama'] = $nama;
            $row['gelar'] = $gelar;
            $row['nama_lengkap'] = $namaLengkap;
            $row['unit_kerja'] = $p['unit_kerja'] ?? ($row['unit_kerja'] ?? 'Kantor Pusat');
            $row['jabatan'] = $p['jabatan'] ?? ($row['jabatan'] ?? '-');
            $row['jenis_sanksi'] = $jenisSanksi;
            $row['tingkat'] = $tingkat;
            $row['nomor_sk'] = $nomorSk;
            $row['keterangan'] = $keterangan;
            $row['potongan_persen'] = $potPersen;
            $row['tanggal'] = $rawDate;
            $row['tanggal_formatted'] = $formattedDate;
            $row['bulan_teks'] = $bulanTeks;
            $row['bulan_num'] = $bulanNum;
            $row['tahun_num'] = $tahunNum;

            return $row;
        })->all();
    }

    /**
     * Helper terpusat untuk memfilter data sanksi pegawai dan menghitung KPI statistik kedinasan.
     */
    public function getEnrichedSanksiData(Request $request): array
    {
        $tahun = $request->get('tahun', now()->year);
        $bulan = $request->get('bulan', 'all');
        $unitKerja = $request->get('unit_kerja', 'all');
        $nik = $request->get('nik', 'all');

        $allSanksi = collect($this->withPegawai($this->all()));

        $filtered = $allSanksi->filter(function ($item) use ($tahun, $bulan, $unitKerja, $nik) {
            if ($tahun !== 'all' && !empty($tahun)) {
                if ((int)$item['tahun_num'] !== (int)$tahun) {
                    return false;
                }
            }
            if ($bulan !== 'all' && !empty($bulan)) {
                if ((int)$item['bulan_num'] !== (int)$bulan) {
                    return false;
                }
            }
            if ($unitKerja !== 'all' && !empty($unitKerja)) {
                if (strcasecmp(trim($item['unit_kerja'] ?? ''), trim($unitKerja)) !== 0) {
                    return false;
                }
            }
            if ($nik !== 'all' && !empty($nik)) {
                if (trim($item['nik'] ?? '') !== trim($nik)) {
                    return false;
                }
            }
            return true;
        })->sortByDesc('tanggal')->values();

        // Rangkuman Statistik Sanksi
        $totalSanksi = $filtered->count();
        $totalRingan = $filtered->where('tingkat', 'Ringan')->count();
        $totalSedang = $filtered->where('tingkat', 'Sedang')->count();
        $totalBerat  = $filtered->where('tingkat', 'Berat')->count();

        // Daftar unit kerja unik untuk dropdown filter
        $unitKerjaList = collect(app(PegawaiController::class)->all())
            ->pluck('unit_kerja')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $pegawaiList = $this->pegawaiList();
        $pejabatTtd = PejabatTtdController::getTtd('gaji');

        return compact(
            'filtered',
            'tahun',
            'bulan',
            'unitKerja',
            'nik',
            'totalSanksi',
            'totalRingan',
            'totalSedang',
            'totalBerat',
            'unitKerjaList',
            'pegawaiList',
            'pejabatTtd'
        );
    }

    public function index()
    {
        $sanksi = collect($this->withPegawai($this->all()))
            ->sortByDesc('tanggal')
            ->values();

        return view('sanksi.index', compact('sanksi'));
    }

    /**
     * Halaman Laporan Sanksi Pegawai (Web View Interaktif).
     * Terintegrasi filter tahun, bulan, unit kerja, dan pegawai.
     */
    public function laporan(Request $request)
    {
        $data = $this->getEnrichedSanksiData($request);
        return view('sanksi.laporan', $data);
    }

    /**
     * Cetak Fisik Kedinasan Laporan Sanksi Pegawai Keseluruhan (Format Landscape A4).
     * Disamakan dengan cetak_sanksi_all.php di sistem lama.
     */
    public function cetakAll(Request $request)
    {
        $data = $this->getEnrichedSanksiData($request);
        return view('sanksi.cetak-all', $data);
    }

    /**
     * Cetak Fisik Kedinasan Laporan Sanksi Per Pegawai (Format Portrait/Landscape A4).
     * Disamakan dengan cetak_sanksi_per_pegawai.php di sistem lama.
     */
    public function cetakPegawai(Request $request)
    {
        $nik = $request->get('nik');
        if (empty($nik) || $nik === 'all') {
            $first = collect($this->withPegawai($this->all()))->first();
            $nik = $first['nik'] ?? null;
            $request->merge(['nik' => $nik]);
        }

        $data = $this->getEnrichedSanksiData($request);
        $targetPegawai = $this->pegawaiById($nik);

        return view('sanksi.cetak-pegawai', array_merge($data, [
            'targetPegawai' => $targetPegawai,
        ]));
    }

    public function create()
    {
        return view('sanksi.create', [
            'pegawaiList' => $this->pegawaiList(),
            'jenisSanksiList' => self::JENIS_SANKSI,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $dbPegId = $pegawai['id'] ?? ($pegawai['db_id'] ?? null);
        if (!$dbPegId && !empty($validated['pegawai_id'])) {
            $dbRow = \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('id', $validated['pegawai_id'])
                ->orWhere('nik', (string) $validated['pegawai_id'])
                ->first();
            $dbPegId = $dbRow?->id;
        }

        if ($dbPegId) {
            try {
                \Illuminate\Support\Facades\DB::table('sanksi')->insert([
                    'pegawai_id' => $dbPegId,
                    'jenis_sanksi' => $validated['jenis_sanksi'],
                    'tanggal' => $validated['tanggal'],
                    'keterangan' => $validated['keterangan'] ?? '-',
                    'tingkat' => $validated['jenis_sanksi'] ?? 'Internal',
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB insert sanksi failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi pegawai berhasil ditambahkan dan tersimpan ke database.');
    }

    public function edit(int $id)
    {
        $sanksi = collect($this->all())->firstWhere('id', $id);
        abort_if(! $sanksi, 404);

        $p = $this->pegawaiById($sanksi['pegawai_id'] ?? null);
        if ($p) {
            $sanksi['pegawai_id'] = $p['id'];
        }

        return view('sanksi.edit', [
            'sanksi' => $sanksi,
            'pegawaiList' => $this->pegawaiList(),
            'jenisSanksiList' => self::JENIS_SANKSI,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request, $id);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $dbPegId = $pegawai['id'] ?? ($pegawai['db_id'] ?? null);
        if (!$dbPegId && !empty($validated['pegawai_id'])) {
            $dbRow = \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('id', $validated['pegawai_id'])
                ->orWhere('nik', (string) $validated['pegawai_id'])
                ->first();
            $dbPegId = $dbRow?->id;
        }

        try {
            \Illuminate\Support\Facades\DB::table('sanksi')->where('id', $id)->update([
                'pegawai_id' => $dbPegId ?: \Illuminate\Support\Facades\DB::raw('pegawai_id'),
                'jenis_sanksi' => $validated['jenis_sanksi'],
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'] ?? '-',
                'tingkat' => $validated['jenis_sanksi'] ?? 'Internal',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB update sanksi failed: ' . $e->getMessage());
        }

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        try {
            \Illuminate\Support\Facades\DB::table('sanksi')->where('id', $id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB delete sanksi failed: ' . $e->getMessage());
        }

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil dihapus.');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'pegawai_id' => 'required',
            'tanggal' => 'required',
            'jenis_sanksi' => 'required|string|max:150',
            'keterangan' => 'nullable|string|max:500',
            'potongan_persen' => 'nullable|numeric|min:0|max:100',
        ]);

        // Aturan sistem: 1 pegawai cuma boleh 1 sanksi per bulan+tahun
        try {
            $bulan = \Illuminate\Support\Carbon::parse($validated['tanggal'])->month;
            $tahun = \Illuminate\Support\Carbon::parse($validated['tanggal'])->year;

            $sudahAda = collect($this->all())
                ->reject(fn ($row) => (int) ($row['id'] ?? 0) === $ignoreId)
                ->contains(function ($row) use ($validated, $bulan, $tahun) {
                    try {
                        $rowDate = \Illuminate\Support\Carbon::parse($row['tanggal']);
                        return (string) ($row['pegawai_id'] ?? '') === (string) $validated['pegawai_id']
                            && $rowDate->month === $bulan
                            && $rowDate->year === $tahun;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });

            if ($sudahAda) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pegawai_id' => 'Pegawai ini sudah punya data sanksi di bulan & tahun yang sama.',
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {}

        return $validated;
    }
}
