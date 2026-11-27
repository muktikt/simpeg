<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasBulanTahunFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TunjanganPendidikanController extends Controller
{
    use HasBulanTahunFilter;
    /**
     * Fitur Input & Cetak Slip Tunjangan Pendidikan (Sesuai Regulasi 2026).
     * Format: GAPOK + Tunj. Istri + Tunj. Anak - (Pot. Koperasi + Pot. Kas).
     * Terhubung langsung dengan tabel tunjangan_pendidikan di database PostgreSQL.
     */

    protected function pegawaiList(): array
    {
        return collect(app(PegawaiController::class)->all())
            ->where('status_peg', '!=', 'PN')
            ->values()
            ->all();
    }

    protected function getPegawaiDbId(string $nik, mixed $pegawaiId = null): ?string
    {
        if ($pegawaiId && Str::isUuid((string) $pegawaiId)) {
            return (string) $pegawaiId;
        }

        return DB::table('pegawai')->where('nik', $nik)->value('id');
    }

    public function index(Request $request)
    {
        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || $request->has('my');
        $myNik = $userLogin['nik'] ?? '';

        [$bulan, $tahun, $defaultBulan, $defaultTahun, $bulanList, $tahunOptions] = $this->resolveBulanTahunFilter($request, $isPegawai, 'bulan_label');
        $search = strtolower(trim((string) $request->query('q', '')));

        try {
            $query = DB::table('tunjangan_pendidikan')
                ->leftJoin('pegawai', 'tunjangan_pendidikan.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'tunjangan_pendidikan.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan',
                    'pegawai.golongan_detail as p_golongan_detail'
                );

            if ($isPegawai && ! empty($myNik)) {
                $query->where('pegawai.nik', $myNik);
            }

            $this->applyBulanTahunColumnFilter($query, 'tunjangan_pendidikan.bulan_label', $bulan, $tahun);

            $items = $query->orderByDesc('tunjangan_pendidikan.id')->get();
        } catch (\Throwable $e) {
            Log::warning('DB tunjangan_pendidikan read failed: ' . $e->getMessage());
            $items = collect();
        }

        $items = $items->map(function ($it) {
            $gapok = (float) ($it->gapok ?? 0);
            $tIstri = (float) ($it->tunjangan_istri ?? 0);
            $tAnak = (float) ($it->tunjangan_anak ?? 0);
            $jmlPendapatan = $gapok + $tIstri + $tAnak;

            $potKoperasi = (float) ($it->potongan_koperasi ?? 0);
            $potKas = (float) ($it->potongan_kas ?? 0);
            $jmlPotongan = $potKoperasi + $potKas;
            $diterima = max(0, $jmlPendapatan - $jmlPotongan);

            return [
                'id'                 => $it->id,
                'pegawai_id'         => $it->pegawai_id,
                'nik'                => $it->p_nik ?? '-',
                'nama'               => $it->p_name ?? '-',
                'jabatan'            => $it->p_jabatan ?? '-',
                'unit_kerja'         => $it->p_unit_kerja ?? '-',
                'golongan'           => $it->p_golongan ?? '-',
                'golongan_detail'    => $it->p_golongan_detail ?? '',
                'bulan_label'        => $it->bulan_label ?? ('JULI ' . now()->year),
                'gapok'              => $gapok,
                'tunjangan_istri'    => $tIstri,
                'tunjangan_anak'     => $tAnak,
                'jumlah_pendapatan'  => $jmlPendapatan,
                'potongan_koperasi'  => $potKoperasi,
                'potongan_kas'       => $potKas,
                'jumlah_potongan'    => $jmlPotongan,
                'diterima'           => $diterima,
                'created_at'         => $it->created_at,
            ];
        });

        if ($search !== '') {
            $items = $items->filter(function ($r) use ($search) {
                return str_contains(strtolower($r['nik']), $search) || str_contains(strtolower($r['nama']), $search);
            })->values();
        }

        $totalPendapatan = $items->sum('jumlah_pendapatan');
        $totalPotongan   = $items->sum('jumlah_potongan');
        $totalDiterima   = $items->sum('diterima');

        return view('tunjangan-pendidikan.index', [
            'items'           => $items,
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'defaultBulan'    => $defaultBulan,
            'defaultTahun'    => $defaultTahun,
            'bulanList'       => $bulanList,
            'tahunOptions'    => $tahunOptions,
            'search'          => $search,
            'totalPendapatan' => $totalPendapatan,
            'totalPotongan'   => $totalPotongan,
            'totalDiterima'   => $totalDiterima,
        ]);
    }

    public function create()
    {
        $pegawaiList = $this->pegawaiList();

        return view('tunjangan-pendidikan.create', [
            'pegawaiList'  => $pegawaiList,
            'bulanList'    => AbsensiController::BULAN,
            'tahunOptions' => $this->tahunFilterOptions(),
            'defaultBulan' => (int) now()->month,
            'defaultTahun' => (int) now()->year,
        ]);
    }

    public function hitungKeluargaJson(Request $request, string $nik)
    {
        $pegawai = collect($this->pegawaiList())->first(fn($p) => (string) $p['nik'] === $nik || (string) ($p['id'] ?? '') === $nik);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $statusPegRaw = strtolower(trim((string) ($pegawai['status_peg'] ?? 'PT')));
        $kategori = match ($statusPegRaw) {
            'cp', 'capeg', 'calon pegawai' => 'capeg',
            'hk', 'honor', 'honorer'       => 'honor',
            'kt', 'kontrak', 'pkwt'        => 'kontrak',
            'tt', 'tidak tetap'            => 'tt',
            default                        => 'satuan',
        };
        if (! $request->filled('kategori')) {
            $request->merge(['kategori' => $kategori]);
        }

        $calc = app(GajiProsesController::class)->hitungKeluarga((int) ($pegawai['id'] ?? 0), $request);

        $kawin = (bool) ($calc['kawin'] ?? false);
        $dbPegId = $pegawai['db_id'] ?? $this->getPegawaiDbId((string) ($pegawai['nik'] ?? $nik));

        // Fallback cek kolom status_pernikahan di tabel pegawai jika tabel keluarga belum terisi pasangan
        if (! $kawin && $dbPegId) {
            try {
                $statusNikah = strtolower(trim((string) (DB::table('pegawai')->where('id', $dbPegId)->value('status_pernikahan') ?? '')));
                if (in_array($statusNikah, ['menikah', 'kawin', 'm', 'k'], true)) {
                    $kawin = true;
                }
            } catch (\Throwable $e) {
            }
        }

        $gapok = (float) ($pegawai['gapok'] ?? 0);

        // 1. Ambil dari hasil perhitungan otomatis GajiProsesController (payroll terakhir / direksi / capeg / default master)
        if ($gapok <= 0 && ! empty($calc['auto_komponen']['gapok'])) {
            $gapok = (float) $calc['auto_komponen']['gapok'];
        }

        // 2. Fallback cek riwayat tunjangan_pendidikan / gaji_13 / master GajiPokokController jika masih 0
        if ($gapok <= 0 && $dbPegId) {
            try {
                $prevTpGapok = DB::table('tunjangan_pendidikan')
                    ->where('pegawai_id', $dbPegId)
                    ->where('gapok', '>', 0)
                    ->orderByDesc('id')
                    ->value('gapok');
                if ($prevTpGapok) {
                    $gapok = (float) $prevTpGapok;
                }
            } catch (\Throwable $e) {
            }
        }

        if ($gapok <= 0) {
            try {
                $golStr = strtoupper(trim((string) ($pegawai['golongan'] ?? '')));
                if ($golStr !== '') {
                    $matchedMaster = collect(app(GajiPokokController::class)->all())->first(function ($row) use ($golStr) {
                        $kode = strtoupper(trim((string) ($row['golongan'] ?? '')));
                        return $kode !== '' && (str_contains($golStr, $kode) || str_contains($kode, $golStr));
                    });
                    if ($matchedMaster && ! empty($matchedMaster['nominal'])) {
                        $gapok = (float) $matchedMaster['nominal'];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        // Untuk Honorer / Kontrak / TT tidak mendapat tunjangan keluarga
        $berhakTunjanganKeluarga = ! in_array($kategori, ['honor', 'kontrak', 'tt'], true);
        $jmlAnak = $berhakTunjanganKeluarga ? min((int) ($calc['jml_anak'] ?? 0), 2) : 0;
        $kawinBerhak = $berhakTunjanganKeluarga && $kawin;

        $tunjIstri = $kawinBerhak ? (int) round($gapok * 0.10) : 0;
        $tunjAnak = $jmlAnak > 0 ? (int) round($gapok * 0.05 * $jmlAnak) : 0;

        return response()->json([
            'success'           => true,
            'pegawai'           => $pegawai,
            'kategori'          => $calc['kategori'] ?? $kategori,
            'berhak_keluarga'   => $berhakTunjanganKeluarga,
            'gapok'             => (int) round($gapok),
            'kawin'             => $kawinBerhak,
            'status_kawin_asli' => $kawin,
            'jml_istri'         => $kawinBerhak ? 1 : 0,
            'total_anak'        => (int) ($calc['total_anak'] ?? $jmlAnak),
            'jml_anak'          => $jmlAnak,
            'tunjangan_istri'   => $tunjIstri,
            'tunjangan_anak'    => $tunjAnak,
            'total_keluarga'    => (int) round($gapok) + $tunjIstri + $tunjAnak,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pegawai_nik'       => 'required|string',
            'bulan'             => 'required_without:bulan_label|integer|min:1|max:12',
            'tahun'             => 'required_without:bulan_label|integer|min:2020|max:2100',
            'bulan_label'       => 'nullable|string',
            'gapok'             => 'required|numeric|min:0',
            'tunjangan_istri'   => 'nullable|numeric|min:0',
            'tunjangan_anak'    => 'nullable|numeric|min:0',
            'potongan_koperasi' => 'nullable|numeric|min:0',
            'potongan_kas'      => 'nullable|numeric|min:0',
        ]);

        $nik = trim($request->input('pegawai_nik'));
        $dbPegId = $this->getPegawaiDbId($nik, $request->input('pegawai_id'));

        if (! $dbPegId) {
            return back()->withInput()->with('error', "Pegawai dengan NIK {$nik} tidak ditemukan di database utama.");
        }

        $bulanLabel = $this->resolveBulanTahunInput($request, 'bulan_label');
        $gapok = (int) $request->input('gapok');
        $tIstri = (int) $request->input('tunjangan_istri', 0);
        $tAnak = (int) $request->input('tunjangan_anak', 0);
        $potKoperasi = (int) $request->input('potongan_koperasi', 0);
        $potKas = (int) $request->input('potongan_kas', 0);

        try {
            $insertedId = DB::table('tunjangan_pendidikan')->insertGetId([
                'pegawai_id'        => $dbPegId,
                'bulan_label'       => $bulanLabel,
                'gapok'             => $gapok,
                'tunjangan_istri'   => $tIstri,
                'tunjangan_anak'    => $tAnak,
                'potongan_koperasi' => $potKoperasi,
                'potongan_kas'      => $potKas,
                'created_at'        => now(),
            ]);

            return redirect()->route('tunjangan-pendidikan.show', $insertedId)
                ->with('success', 'Data Tunjangan Pendidikan berhasil disimpan dan slip siap dicetak.');
        } catch (\Throwable $e) {
            Log::error('TunjanganPendidikan store error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $row = DB::table('tunjangan_pendidikan')
            ->leftJoin('pegawai', 'tunjangan_pendidikan.pegawai_id', '=', 'pegawai.id')
            ->select(
                'tunjangan_pendidikan.*',
                'pegawai.nik as p_nik',
                'pegawai.name as p_name',
                'pegawai.jabatan as p_jabatan',
                'pegawai.unit_kerja as p_unit_kerja',
                'pegawai.golongan as p_golongan',
                'pegawai.golongan_detail as p_golongan_detail'
            )
            ->where('tunjangan_pendidikan.id', $id)
            ->first();

        abort_if(! $row, 404, 'Data Tunjangan Pendidikan tidak ditemukan.');

        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || request('my');
        if ($isPegawai && ! empty($userLogin['nik']) && (string) $row->p_nik !== (string) $userLogin['nik']) {
            abort(403, 'Anda tidak berhak melihat slip pegawai lain.');
        }

        $gapok = (float) ($row->gapok ?? 0);
        $tIstri = (float) ($row->tunjangan_istri ?? 0);
        $tAnak = (float) ($row->tunjangan_anak ?? 0);
        $jmlPendapatan = $gapok + $tIstri + $tAnak;

        $potKoperasi = (float) ($row->potongan_koperasi ?? 0);
        $potKas = (float) ($row->potongan_kas ?? 0);
        $jmlPotongan = $potKoperasi + $potKas;
        $diterima = max(0, $jmlPendapatan - $jmlPotongan);

        $item = [
            'id'                 => $row->id,
            'nik'                => $row->p_nik ?? '-',
            'nama'               => $row->p_name ?? '-',
            'jabatan'            => $row->p_jabatan ?? '-',
            'unit_kerja'         => $row->p_unit_kerja ?? '-',
            'golongan'           => $row->p_golongan ?? '-',
            'golongan_detail'    => $row->p_golongan_detail ?? ($row->p_golongan ?? '-'),
            'bulan_label'        => $row->bulan_label ?? ('JULI ' . now()->year),
            'gapok'              => $gapok,
            'tunjangan_istri'    => $tIstri,
            'tunjangan_anak'     => $tAnak,
            'jumlah_pendapatan'  => $jmlPendapatan,
            'potongan_koperasi'  => $potKoperasi,
            'potongan_kas'       => $potKas,
            'jumlah_potongan'    => $jmlPotongan,
            'diterima'           => $diterima,
            'created_at'         => $row->created_at,
        ];

        [$parsedBulan, $parsedTahun] = $this->parseBulanTahunFromLabel($item['bulan_label']);
        $backParams = array_filter(['bulan' => $parsedBulan, 'tahun' => $parsedTahun]);

        return view('tunjangan-pendidikan.show', compact('item', 'backParams'));
    }

    public function destroy(int $id)
    {
        try {
            DB::table('tunjangan_pendidikan')->where('id', $id)->delete();
            return redirect()->route('tunjangan-pendidikan.index')->with('success', 'Data Tunjangan Pendidikan berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
