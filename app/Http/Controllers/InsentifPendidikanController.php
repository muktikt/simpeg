<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsentifPendidikanController extends Controller
{
    /**
     * Fitur Input & Cetak Slip Insentif Pendidikan (Sesuai Regulasi 2026).
     * Format: Seluruh Tunjangan di luar Pokok/Keluarga & Potongan Lengkap.
     * Terhubung langsung dengan tabel insentif di database PostgreSQL.
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
        $periode = $request->query('periode', 'JULI ' . now()->year);
        $search = strtolower(trim((string) $request->query('q', '')));

        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || $request->has('my');
        $myNik = $userLogin['nik'] ?? '';

        try {
            $query = DB::table('insentif')
                ->leftJoin('pegawai', 'insentif.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'insentif.*',
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

            if (! empty($periode) && $periode !== 'all') {
                $query->where('insentif.periode', $periode);
            }

            $items = $query->orderByDesc('insentif.id')->get();
        } catch (\Throwable $e) {
            Log::warning('DB insentif read failed: ' . $e->getMessage());
            $items = collect();
        }

        $items = $items->map(function ($it) {
            $itArr = (array) $it;
            $pendapatanKeys = [
                'insentif_jabatan', 'insentif_prestasi', 'insentif_transportasi',
                'insentif_pangan', 'insentif_bpjs_kesehatan', 'insentif_perumahan',
                'insentif_bpjs_tenaga_kerja', 'insentif_perusahaan', 'lembur',
                'insentif_pajak', 'insentif_air_minum', 'insentif_komunikasi',
            ];
            $totalInsentif = 0;
            foreach ($pendapatanKeys as $k) {
                $totalInsentif += (float) ($itArr[$k] ?? 0);
            }

            $potInsentifKeys = [
                'potongan_sanksi_perusahaan', 'potongan_pmi_lain', 'potongan_dapenma',
                'potongan_bpjs_tenaga_kerja', 'potongan_perumahan', 'potongan_insentif_perusahaan',
                'potongan_korpri', 'potongan_pajak', 'potongan_bpjs_kesehatan',
            ];
            $totalPotInsentif = 0;
            foreach ($potInsentifKeys as $k) {
                $totalPotInsentif += (float) ($itArr[$k] ?? 0);
            }

            $potNonInsentifKeys = [
                'potongan_koperasi', 'potongan_darma_wanita', 'potongan_rekening_air_minum',
                'potongan_kas', 'potongan_bank_bjb', 'potongan_bank_bjbs',
                'potongan_bank_btn', 'potongan_bank_bpr', 'potongan_asuransi',
                'potongan_zakat_profesi',
            ];
            $totalPotNonInsentif = 0;
            foreach ($potNonInsentifKeys as $k) {
                $totalPotNonInsentif += (float) ($itArr[$k] ?? 0);
            }

            $totalPotongan = $totalPotInsentif + $totalPotNonInsentif;
            $diterima = max(0, $totalInsentif - $totalPotongan);

            return array_merge($itArr, [
                'nik'                         => $it->p_nik ?? '-',
                'nama'                        => $it->p_name ?? '-',
                'jabatan'                     => $it->p_jabatan ?? '-',
                'unit_kerja'                  => $it->p_unit_kerja ?? '-',
                'golongan'                    => $it->p_golongan ?? '-',
                'golongan_detail'             => $it->p_golongan_detail ?? ($it->p_golongan ?? '-'),
                'total_insentif'              => $totalInsentif,
                'total_potongan_insentif'     => $totalPotInsentif,
                'total_potongan_non_insentif' => $totalPotNonInsentif,
                'total_potongan'              => $totalPotongan,
                'diterima'                    => $diterima,
            ]);
        });

        if ($search !== '') {
            $items = $items->filter(function ($r) use ($search) {
                return str_contains(strtolower($r['nik']), $search) || str_contains(strtolower($r['nama']), $search);
            })->values();
        }

        $totalInsentifSemua = $items->sum('total_insentif');
        $totalPotonganSemua = $items->sum('total_potongan');
        $totalDiterimaSemua = $items->sum('diterima');

        return view('insentif-pendidikan.index', [
            'items'              => $items,
            'periode'            => $periode,
            'search'             => $search,
            'totalInsentifSemua' => $totalInsentifSemua,
            'totalPotonganSemua' => $totalPotonganSemua,
            'totalDiterimaSemua' => $totalDiterimaSemua,
        ]);
    }

    public function create()
    {
        $pegawaiList = $this->pegawaiList();
        $defaultPeriode = 'JULI ' . now()->year;

        return view('insentif-pendidikan.create', [
            'pegawaiList'    => $pegawaiList,
            'defaultPeriode' => $defaultPeriode,
        ]);
    }

    public function hitungInsentifJson(string $nik)
    {
        $pegawai = collect($this->pegawaiList())->first(fn($p) => (string)$p['nik'] === $nik || (string)($p['id'] ?? '') === $nik);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $dbPegId = $this->getPegawaiDbId($nik, $pegawai['id'] ?? null);

        $latestGaji = null;
        if ($dbPegId) {
            try {
                $latestGaji = DB::table('gaji_proses')
                    ->where('pegawai_id', $dbPegId)
                    ->orderByDesc('id')
                    ->first();
            } catch (\Throwable $e) {}
        }

        $data = [
            'insentif_jabatan'            => (int) ($latestGaji->tunjangan_jabatan ?? 0),
            'insentif_prestasi'           => (int) ($latestGaji->tunjangan_prestasi ?? 0),
            'insentif_transportasi'       => (int) ($latestGaji->tunjangan_transportasi ?? 0),
            'insentif_pangan'             => (int) ($latestGaji->tunjangan_pangan ?? 0),
            'insentif_bpjs_kesehatan'     => (int) ($latestGaji->tunjangan_bpjs_kesehatan ?? 0),
            'insentif_perumahan'          => (int) ($latestGaji->tunjangan_perumahan ?? 0),
            'insentif_bpjs_tenaga_kerja'  => (int) ($latestGaji->tunjangan_bpjs_tenaga_kerja ?? 0),
            'insentif_perusahaan'         => (int) ($latestGaji->tunjangan_perusahaan ?? 0),
            'lembur'                      => (int) ($latestGaji->lembur ?? 0),
            'insentif_pajak'              => (int) ($latestGaji->tunjangan_pajak ?? 0),
            'insentif_air_minum'          => (int) ($latestGaji->tunjangan_air_minum ?? 0),
            'insentif_komunikasi'         => (int) ($latestGaji->tunjangan_komunikasi ?? 0),

            'potongan_sanksi_perusahaan'  => (int) ($latestGaji->potongan_sanksi_perusahaan ?? 0),
            'potongan_pmi_lain'           => (int) ($latestGaji->potongan_trandist_pmi_lain ?? ($latestGaji->potongan_lain ?? 0)),
            'potongan_dapenma'            => (int) ($latestGaji->potongan_dapenma ?? 0),
            'potongan_bpjs_tenaga_kerja'  => (int) ($latestGaji->potongan_bpjs_tenaga_kerja ?? 0),
            'potongan_perumahan'          => (int) ($latestGaji->potongan_perumahan ?? 0),
            'potongan_insentif_perusahaan'=> (int) ($latestGaji->potongan_tunjangan_perusahaan ?? 0),
            'potongan_korpri'             => (int) ($latestGaji->potongan_korpri ?? 0),
            'potongan_pajak'              => (int) ($latestGaji->potongan_pajak ?? 0),
            'potongan_bpjs_kesehatan'     => (int) ($latestGaji->potongan_bpjs_kesehatan ?? 0),

            'potongan_koperasi'           => (int) ($latestGaji->potongan_koperasi ?? 0),
            'potongan_darma_wanita'       => (int) ($latestGaji->potongan_darma_wanita ?? ($latestGaji->potongan_darmawanita ?? 0)),
            'potongan_rekening_air_minum' => (int) ($latestGaji->potongan_rekening_air_minum ?? ($latestGaji->potongan_ledeng ?? 0)),
            'potongan_kas'                => (int) ($latestGaji->potongan_kas ?? 0),
            'potongan_bank_bjb'           => (int) ($latestGaji->potongan_bank_bjb ?? ($latestGaji->potongan_bjb ?? 0)),
            'potongan_bank_bjbs'          => (int) ($latestGaji->potongan_bank_bjbs ?? ($latestGaji->potongan_bjbs ?? 0)),
            'potongan_bank_btn'           => (int) ($latestGaji->potongan_bank_btn ?? ($latestGaji->potongan_btn ?? 0)),
            'potongan_bank_bpr'           => (int) ($latestGaji->potongan_bank_bpr ?? ($latestGaji->potongan_bpr ?? 0)),
            'potongan_asuransi'           => (int) ($latestGaji->potongan_asuransi ?? 0),
            'potongan_zakat_profesi'      => (int) ($latestGaji->potongan_zakat_profesi ?? ($latestGaji->potongan_zakat ?? 0)),
        ];

        return response()->json([
            'success' => true,
            'pegawai' => $pegawai,
            'values'  => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pegawai_nik' => 'required|string',
            'periode'     => 'required|string',
        ]);

        $nik = trim($request->input('pegawai_nik'));
        $dbPegId = $this->getPegawaiDbId($nik, $request->input('pegawai_id'));

        if (! $dbPegId) {
            return back()->withInput()->with('error', "Pegawai dengan NIK {$nik} tidak ditemukan di database.");
        }

        $periode = strtoupper(trim($request->input('periode')));
        $judul = "DAFTAR INSENTIF & POTONGAN BULAN : " . $periode;

        $fields = [
            'insentif_jabatan'            => (int) $request->input('insentif_jabatan', 0),
            'insentif_prestasi'           => (int) $request->input('insentif_prestasi', 0),
            'insentif_transportasi'       => (int) $request->input('insentif_transportasi', 0),
            'insentif_pangan'             => (int) $request->input('insentif_pangan', 0),
            'insentif_bpjs_kesehatan'     => (int) $request->input('insentif_bpjs_kesehatan', 0),
            'insentif_perumahan'          => (int) $request->input('insentif_perumahan', 0),
            'insentif_bpjs_tenaga_kerja'  => (int) $request->input('insentif_bpjs_tenaga_kerja', 0),
            'insentif_perusahaan'         => (int) $request->input('insentif_perusahaan', 0),
            'lembur'                      => (int) $request->input('lembur', 0),
            'insentif_pajak'              => (int) $request->input('insentif_pajak', 0),
            'insentif_air_minum'          => (int) $request->input('insentif_air_minum', 0),
            'insentif_komunikasi'         => (int) $request->input('insentif_komunikasi', 0),

            'potongan_sanksi_perusahaan'  => (int) $request->input('potongan_sanksi_perusahaan', 0),
            'potongan_pmi_lain'           => (int) $request->input('potongan_pmi_lain', 0),
            'potongan_dapenma'            => (int) $request->input('potongan_dapenma', 0),
            'potongan_bpjs_tenaga_kerja'  => (int) $request->input('potongan_bpjs_tenaga_kerja', 0),
            'potongan_perumahan'          => (int) $request->input('potongan_perumahan', 0),
            'potongan_insentif_perusahaan'=> (int) $request->input('potongan_insentif_perusahaan', 0),
            'potongan_korpri'             => (int) $request->input('potongan_korpri', 0),
            'potongan_pajak'              => (int) $request->input('potongan_pajak', 0),
            'potongan_bpjs_kesehatan'     => (int) $request->input('potongan_bpjs_kesehatan', 0),

            'potongan_koperasi'           => (int) $request->input('potongan_koperasi', 0),
            'potongan_darma_wanita'       => (int) $request->input('potongan_darma_wanita', 0),
            'potongan_rekening_air_minum' => (int) $request->input('potongan_rekening_air_minum', 0),
            'potongan_kas'                => (int) $request->input('potongan_kas', 0),
            'potongan_bank_bjb'           => (int) $request->input('potongan_bank_bjb', 0),
            'potongan_bank_bjbs'          => (int) $request->input('potongan_bank_bjbs', 0),
            'potongan_bank_btn'           => (int) $request->input('potongan_bank_btn', 0),
            'potongan_bank_bpr'           => (int) $request->input('potongan_bank_bpr', 0),
            'potongan_asuransi'           => (int) $request->input('potongan_asuransi', 0),
            'potongan_zakat_profesi'      => (int) $request->input('potongan_zakat_profesi', 0),
        ];

        try {
            $insertedId = DB::table('insentif')->insertGetId(array_merge($fields, [
                'pegawai_id' => $dbPegId,
                'judul'      => $judul,
                'periode'    => $periode,
                'created_at' => now(),
            ]));

            return redirect()->route('insentif-pendidikan.show', $insertedId)
                ->with('success', 'Data Insentif Pendidikan berhasil disimpan dan slip siap dicetak.');
        } catch (\Throwable $e) {
            Log::error('InsentifPendidikan store error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $row = DB::table('insentif')
            ->leftJoin('pegawai', 'insentif.pegawai_id', '=', 'pegawai.id')
            ->select(
                'insentif.*',
                'pegawai.nik as p_nik',
                'pegawai.name as p_name',
                'pegawai.jabatan as p_jabatan',
                'pegawai.unit_kerja as p_unit_kerja',
                'pegawai.golongan as p_golongan',
                'pegawai.golongan_detail as p_golongan_detail'
            )
            ->where('insentif.id', $id)
            ->first();

        abort_if(! $row, 404, 'Data Insentif Pendidikan tidak ditemukan.');

        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || request('my');
        if ($isPegawai && ! empty($userLogin['nik']) && (string) $row->p_nik !== (string) $userLogin['nik']) {
            abort(403, 'Anda tidak berhak melihat slip pegawai lain.');
        }

        $itArr = (array) $row;
        $pendapatanKeys = [
            'insentif_jabatan', 'insentif_prestasi', 'insentif_transportasi',
            'insentif_pangan', 'insentif_bpjs_kesehatan', 'insentif_perumahan',
            'insentif_bpjs_tenaga_kerja', 'insentif_perusahaan', 'lembur',
            'insentif_pajak', 'insentif_air_minum', 'insentif_komunikasi',
        ];
        $totalInsentif = 0;
        foreach ($pendapatanKeys as $k) {
            $totalInsentif += (float) ($itArr[$k] ?? 0);
        }

        $potInsentifKeys = [
            'potongan_sanksi_perusahaan', 'potongan_pmi_lain', 'potongan_dapenma',
            'potongan_bpjs_tenaga_kerja', 'potongan_perumahan', 'potongan_insentif_perusahaan',
            'potongan_korpri', 'potongan_pajak', 'potongan_bpjs_kesehatan',
        ];
        $totalPotInsentif = 0;
        foreach ($potInsentifKeys as $k) {
            $totalPotInsentif += (float) ($itArr[$k] ?? 0);
        }

        $potNonInsentifKeys = [
            'potongan_koperasi', 'potongan_darma_wanita', 'potongan_rekening_air_minum',
            'potongan_kas', 'potongan_bank_bjb', 'potongan_bank_bjbs',
            'potongan_bank_btn', 'potongan_bank_bpr', 'potongan_asuransi',
            'potongan_zakat_profesi',
        ];
        $totalPotNonInsentif = 0;
        foreach ($potNonInsentifKeys as $k) {
            $totalPotNonInsentif += (float) ($itArr[$k] ?? 0);
        }

        $totalPotongan = $totalPotInsentif + $totalPotNonInsentif;
        $diterima = max(0, $totalInsentif - $totalPotongan);

        $item = array_merge($itArr, [
            'nik'                         => $row->p_nik ?? '-',
            'nama'                        => $row->p_name ?? '-',
            'jabatan'                     => $row->p_jabatan ?? '-',
            'unit_kerja'                  => $row->p_unit_kerja ?? '-',
            'golongan'                    => $row->p_golongan ?? '-',
            'golongan_detail'             => $row->p_golongan_detail ?? ($row->p_golongan ?? '-'),
            'total_insentif'              => $totalInsentif,
            'total_potongan_insentif'     => $totalPotInsentif,
            'total_potongan_non_insentif' => $totalPotNonInsentif,
            'total_potongan'              => $totalPotongan,
            'diterima'                    => $diterima,
        ]);

        return view('insentif-pendidikan.show', compact('item'));
    }

    public function destroy(int $id)
    {
        try {
            DB::table('insentif')->where('id', $id)->delete();
            return redirect()->route('insentif-pendidikan.index')->with('success', 'Data Insentif Pendidikan berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
