<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TunjanganPendidikanController extends Controller
{
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
        if ($pegawaiId && is_string($pegawaiId) && strlen($pegawaiId) === 36) {
            return $pegawaiId;
        }

        return DB::table('pegawai')->where('nik', $nik)->value('id');
    }

    public function index(Request $request)
    {
        $bulanLabel = $request->query('bulan_label', 'JULI ' . now()->year);
        $search = strtolower(trim((string) $request->query('q', '')));

        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || $request->has('my');
        $myNik = $userLogin['nik'] ?? '';

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

            if (! empty($bulanLabel) && $bulanLabel !== 'all') {
                $query->where('tunjangan_pendidikan.bulan_label', $bulanLabel);
            }

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
            'bulanLabel'      => $bulanLabel,
            'search'          => $search,
            'totalPendapatan' => $totalPendapatan,
            'totalPotongan'   => $totalPotongan,
            'totalDiterima'   => $totalDiterima,
        ]);
    }

    public function create()
    {
        $pegawaiList = $this->pegawaiList();
        $defaultBulanLabel = 'JULI ' . now()->year;

        return view('tunjangan-pendidikan.create', [
            'pegawaiList'       => $pegawaiList,
            'defaultBulanLabel' => $defaultBulanLabel,
        ]);
    }

    public function hitungKeluargaJson(string $nik)
    {
        $pegawai = collect($this->pegawaiList())->first(fn($p) => (string)$p['nik'] === $nik || (string)($p['id'] ?? '') === $nik);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $calc = app(GajiProsesController::class)->hitungKeluarga((int)($pegawai['id'] ?? 0));
        $gapok = (float) ($pegawai['gapok'] ?? 0);

        if ($gapok <= 0) {
            // Ambil dari tbl_gapok jika ada
            try {
                $kodegol = $pegawai['kodegol'] ?? ($pegawai['golongan'] ?? '');
                $thnGol = (int) ($pegawai['tahun_golongan'] ?? 0);
                $rowGapok = DB::table('tbl_gapok')->where('id_golongan', $kodegol)->where('tahun_golongan', $thnGol)->value('nominal_gapok');
                if ($rowGapok) {
                    $gapok = (float) $rowGapok;
                }
            } catch (\Throwable $e) {}
        }

        $tunjIstri = $calc['kawin'] ? round($gapok * 0.1) : 0;
        $jmlAnak = min((int)($calc['jml_anak'] ?? 0), 2);
        $tunjAnak = round($gapok * 0.05 * $jmlAnak);

        return response()->json([
            'success'         => true,
            'pegawai'         => $pegawai,
            'gapok'           => $gapok,
            'kawin'           => $calc['kawin'],
            'jml_istri'       => $calc['jml_istri'] ?? ($calc['kawin'] ? 1 : 0),
            'jml_anak'        => $jmlAnak,
            'tunjangan_istri' => $tunjIstri,
            'tunjangan_anak'  => $tunjAnak,
            'total_keluarga'  => $gapok + $tunjIstri + $tunjAnak,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pegawai_nik'       => 'required|string',
            'bulan_label'       => 'required|string',
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

        $bulanLabel = strtoupper(trim($request->input('bulan_label')));
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

        return view('tunjangan-pendidikan.show', compact('item'));
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
