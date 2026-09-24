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
    public const JENIS_SANKSI = ['Lisan', 'Tulisan', 'Dikeluarkan'];

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
        return collect($rows)->map(function ($row) {
            $p = $this->pegawaiById($row['pegawai_id'] ?? null);
            $row['nik'] = $p['nik'] ?? ($row['nik'] ?? '-');
            $row['nama'] = $p['nama'] ?? ($row['nama_pegawai'] ?? '(pegawai tidak ditemukan)');
            $row['jenis_sanksi'] = $row['jenis_sanksi'] ?? ($row['jenis'] ?? '-');
            $row['keterangan'] = $row['keterangan'] ?? ($row['ket_sanksi'] ?? '-');
            $row['potongan_persen'] = $row['potongan_persen'] ?? ($row['pot_persen'] ?? 0);
            $row['tanggal'] = $row['tanggal'] ?? ($row['tgl_sanksi'] ?? ($row['created_at'] ?? now()->toDateString()));

            return $row;
        })->all();
    }

    public function index()
    {
        $sanksi = collect($this->withPegawai($this->all()))
            ->sortByDesc('tanggal')
            ->values();

        return view('sanksi.index', compact('sanksi'));
    }

    /**
     * Halaman Laporan (read-only, format cetak) - dipisah dari index()
     * yang jadi halaman kelola/SET. Data sumbernya sama, tampilannya beda.
     */
    public function laporan()
    {
        $sanksi = collect($this->withPegawai($this->all()))
            ->sortByDesc('tanggal')
            ->values();

        return view('sanksi.laporan', compact('sanksi'));
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

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $dbPegId = $pegawai['db_id'] ?? null;
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
        $dbPegId = $pegawai['db_id'] ?? null;
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
        } catch (\Throwable $e) {}

        $data = collect($this->all())->map(function ($row) use ($id, $validated) {
            if ($row['id'] === $id) {
                $validated['id'] = $id;

                return $validated;
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        try {
            \Illuminate\Support\Facades\DB::table('sanksi')->where('id', $id)->delete();
        } catch (\Throwable $e) {}

        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil dihapus.');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'tanggal' => 'required|date',
            'jenis_sanksi' => ['required', Rule::in(self::JENIS_SANKSI)],
            'keterangan' => 'nullable|string|max:255',
            'potongan_persen' => 'required|numeric|min:0|max:100',
        ]);

        // Aturan sistem lama: 1 pegawai cuma boleh 1 sanksi per bulan+tahun.
        $bulan = \Illuminate\Support\Carbon::parse($validated['tanggal'])->month;
        $tahun = \Illuminate\Support\Carbon::parse($validated['tanggal'])->year;

        $sudahAda = collect($this->all())
            ->reject(fn ($row) => $row['id'] === $ignoreId)
            ->contains(function ($row) use ($validated, $bulan, $tahun) {
                $rowDate = \Illuminate\Support\Carbon::parse($row['tanggal']);

                return $row['pegawai_id'] == $validated['pegawai_id']
                    && $rowDate->month === $bulan
                    && $rowDate->year === $tahun;
            });

        if ($sudahAda) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pegawai_id' => 'Pegawai ini sudah punya data sanksi di bulan & tahun yang sama.',
            ]);
        }

        return $validated;
    }
}
