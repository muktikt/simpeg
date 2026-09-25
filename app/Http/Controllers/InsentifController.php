<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsentifController extends Controller
{
    /**
     * Mengambil dan memproses data Insentif Pegawai secara dinamis dari database.
     * Sesuai ketentuan resmi PERUMDAM Tirta Darma Ayu:
     * - Insentif = Komponen pendapatan di luar Gapok & Tunjangan Keluarga (Prestasi, Jabatan, Transport,
     *   Pangan, BPJS Kes, Perumahan, BPJSTK, Perusahaan, Lembur, Pajak, Air Minum, Komunikasi).
     * - Potongan = Sanksi, PMI, Dapenma, BPJSTK, Perumahan, Tunj. Perusahaan, Korpri, Pajak, BPJS Kes,
     *   serta Potongan Keuangan (Koperasi, Dharma Wanita, Air Minum, Kas, Bank BJB/BJBS/BTN/BPR, Asuransi, Zakat).
     * - Insentif Diterima = Total Insentif - Total Potongan.
     */
    protected function ambilData(Request $request): array
    {
        $sumber = $request->get('sumber', 'gaji_bulanan');
        $tahun = (int) $request->get('tahun', now()->year);
        $bulan = (int) $request->get('bulan', now()->month);

        $data = collect();

        if ($sumber === 'gaji_bulanan') {
            try {
                $rows = DB::table('payroll')
                    ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                    ->select(
                        'payroll.*',
                        'pegawai.nik as p_nik',
                        'pegawai.name as p_name',
                        'pegawai.jabatan as p_jabatan',
                        'pegawai.unit_kerja as p_unit_kerja',
                        'pegawai.golongan as p_golongan',
                        'pegawai.golongan_detail as p_golongan_detail'
                    )
                    ->where('payroll.tahun', $tahun)
                    ->where('payroll.bulan', $bulan)
                    ->whereIn('payroll.status', ['terbit', 'DITERBITKAN'])
                    ->orderBy('pegawai.name', 'asc')
                    ->get();

                if ($rows->isEmpty()) {
                    // Fallback cek ke tabel insentif jika ada
                    $insRows = DB::table('insentif')
                        ->leftJoin('pegawai', 'insentif.pegawai_id', '=', 'pegawai.id')
                        ->select('insentif.*', 'pegawai.nik as p_nik', 'pegawai.name as p_name', 'pegawai.jabatan as p_jabatan', 'pegawai.unit_kerja as p_unit_kerja', 'pegawai.golongan as p_golongan')
                        ->where('insentif.periode', 'like', '%' . ($bulan < 10 ? '0' . $bulan : $bulan) . '%' . $tahun . '%')
                        ->orWhere('insentif.periode', 'like', '%' . (AbsensiController::BULAN[$bulan] ?? '') . '%' . $tahun . '%')
                        ->get();
                    $rows = $insRows;
                }

                $data = $rows->map(function ($r) use ($bulan, $tahun) {
                    return $this->formatRowToInsentif($r, $bulan, $tahun);
                });
            } catch (\Throwable $e) {
                Log::error('Ambil data insentif bulanan gagal: ' . $e->getMessage());
            }
        } else {
            // Sumber Gaji 13 / Tunjangan Pendidikan
            try {
                $rows13 = DB::table('gaji_13')
                    ->leftJoin('pegawai', 'gaji_13.pegawai_id', '=', 'pegawai.id')
                    ->select(
                        'gaji_13.*',
                        'pegawai.nik as p_nik',
                        'pegawai.name as p_name',
                        'pegawai.jabatan as p_jabatan',
                        'pegawai.unit_kerja as p_unit_kerja',
                        'pegawai.golongan as p_golongan'
                    )
                    ->where('gaji_13.tahun', $tahun)
                    ->whereIn('gaji_13.status', ['terbit', 'DITERBITKAN'])
                    ->orderBy('pegawai.name', 'asc')
                    ->get();

                $data = $rows13->map(function ($r) use ($bulan, $tahun) {
                    return $this->formatRowToInsentif($r, $bulan, $tahun, 'gaji13');
                });
            } catch (\Throwable $e) {
                Log::error('Ambil data insentif gaji 13 gagal: ' . $e->getMessage());
            }
        }

        return compact('data', 'sumber', 'tahun', 'bulan');
    }

    /**
     * Format row dari database ke struktur standar insentif dengan kalkulasi lengkap.
     */
    protected function formatRowToInsentif(object $r, int $bulan, int $tahun, string $sumber = 'gaji_bulanan'): array
    {
        $bulanNama = AbsensiController::BULAN[$bulan] ?? ('Bulan ' . $bulan);
        $periodeLabel = ($sumber === 'gaji13') ? "Gaji 13 Tahun $tahun" : "$bulanNama $tahun";

        // Identitas Pegawai
        $id = $r->id ?? 0;
        $pegawaiId = $r->pegawai_id ?? '';
        $nik = $r->p_nik ?? ($r->nik ?? '-');
        $nama = $r->p_name ?? ($r->name ?? ($r->nama ?? 'Pegawai'));
        $jabatan = $r->p_jabatan ?? ($r->jabatan ?? '-');
        $unitKerja = $r->p_unit_kerja ?? ($r->unit_kerja ?? '-');
        $golongan = $r->p_golongan_detail ?? ($r->p_golongan ?? ($r->golongan ?? '-'));

        // Komponen Penerimaan Insentif
        $insentifJabatan = (int) ($r->tunjangan_jabatan ?? ($r->insentif_jabatan ?? 0));
        $insentifPrestasi = (int) ($r->tunjangan_prestasi ?? ($r->insentif_prestasi ?? 0));
        $insentifTransportasi = (int) ($r->tunjangan_transportasi ?? ($r->tunjangan_transport ?? ($r->insentif_transportasi ?? 0)));
        $insentifPangan = (int) ($r->tunjangan_pangan ?? ($r->insentif_pangan ?? 0));
        $insentifBpjsKes = (int) ($r->tunjangan_bpjs_kesehatan ?? ($r->tunjangan_bpjskes ?? ($r->insentif_bpjs_kesehatan ?? 0)));
        $insentifPerumahan = (int) ($r->tunjangan_perumahan ?? ($r->insentif_perumahan ?? 0));
        $insentifBpjstk = (int) ($r->tunjangan_bpjs_tenaga_kerja ?? ($r->tunjangan_bpjstk ?? ($r->insentif_bpjs_tenaga_kerja ?? 0)));
        $insentifPerusahaan = (int) ($r->tunjangan_perusahaan ?? ($r->insentif_perusahaan ?? 0));
        $lembur = (int) ($r->lembur ?? 0);
        $insentifPajak = (int) ($r->tunjangan_pajak ?? ($r->insentif_pajak ?? 0));
        $insentifAirMinum = (int) ($r->tunjangan_air_minum ?? ($r->tunjangan_airminum ?? ($r->insentif_air_minum ?? 0)));
        $insentifKomunikasi = (int) ($r->tunjangan_komunikasi ?? ($r->insentif_komunikasi ?? 0));

        $totalInsentif = $insentifJabatan + $insentifPrestasi + $insentifTransportasi + $insentifPangan +
            $insentifBpjsKes + $insentifPerumahan + $insentifBpjstk + $insentifPerusahaan +
            $lembur + $insentifPajak + $insentifAirMinum + $insentifKomunikasi;

        // Potongan Insentif
        $potSanksi = (int) ($r->potongan_sanksi_perusahaan ?? ($r->potongan_sanksi ?? 0));
        $potPmi = (int) ($r->potongan_trandist_pmi_lain ?? ($r->potongan_pmi_lain ?? ($r->potongan_lain ?? 0)));
        $potDapenma = (int) ($r->potongan_dapenma ?? 0);
        $potBpjstk = (int) ($r->potongan_bpjs_tenaga_kerja ?? ($r->potongan_bpjstk ?? 0));
        $potPerumahan = (int) ($r->potongan_perumahan ?? 0);
        $potTperusahaan = (int) ($r->potongan_tunjangan_perusahaan ?? ($r->potongan_insentif_perusahaan ?? ($r->potongan_tperusahaan ?? 0)));
        $potKorpri = (int) ($r->potongan_korpri ?? 0);
        $potPajak = (int) ($r->potongan_pajak ?? 0);
        $potBpjskes = (int) ($r->potongan_bpjs_kesehatan ?? ($r->potongan_bpjskes ?? 0));

        $totalPotonganInsentif = $potSanksi + $potPmi + $potDapenma + $potBpjstk + $potPerumahan +
            $potTperusahaan + $potKorpri + $potPajak + $potBpjskes;

        // Potongan Non-Insentif (Keuangan / Trandist)
        $potKoperasi = (int) ($r->potongan_koperasi ?? 0);
        $potDarmawanita = (int) ($r->potongan_darma_wanita ?? ($r->potongan_darmawanita ?? 0));
        $potAirMinum = (int) ($r->potongan_rekening_air_minum ?? ($r->potongan_ledeng ?? 0));
        $potKas = (int) ($r->potongan_kas ?? 0);
        $potBjb = (int) ($r->potongan_bank_bjb ?? ($r->potongan_bjb ?? 0));
        $potBjbs = (int) ($r->potongan_bank_bjbs ?? ($r->potongan_bjbs ?? 0));
        $potBtn = (int) ($r->potongan_bank_btn ?? ($r->potongan_btn ?? 0));
        $potBpr = (int) ($r->potongan_bank_bpr ?? ($r->potongan_bpr ?? 0));
        $potAsuransi = (int) ($r->potongan_asuransi ?? 0);
        $potZakat = (int) ($r->potongan_zakat_profesi ?? ($r->potongan_zakat ?? 0));

        $totalPotonganNonInsentif = $potKoperasi + $potDarmawanita + $potAirMinum + $potKas +
            $potBjb + $potBjbs + $potBtn + $potBpr + $potAsuransi + $potZakat;

        $totalPotongan = $totalPotonganInsentif + $totalPotonganNonInsentif;
        $insentifDiterima = max(0, $totalInsentif - $totalPotongan);

        return [
            'id' => $id,
            'pegawai_id' => $pegawaiId,
            'nik' => $nik,
            'nama' => $nama,
            'jabatan' => $jabatan,
            'unit_kerja' => $unitKerja,
            'golongan' => $golongan,
            'periode' => $periodeLabel,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'Terbit & Final',
            'disetujui_oleh' => $r->disetujui_oleh ?? 'Nurpan, S.E., M.Si.',
            // Penerimaan
            'insentif_jabatan' => $insentifJabatan,
            'insentif_prestasi' => $insentifPrestasi,
            'insentif_transportasi' => $insentifTransportasi,
            'insentif_pangan' => $insentifPangan,
            'insentif_bpjs_kesehatan' => $insentifBpjsKes,
            'insentif_perumahan' => $insentifPerumahan,
            'insentif_bpjs_tenaga_kerja' => $insentifBpjstk,
            'insentif_perusahaan' => $insentifPerusahaan,
            'lembur' => $lembur,
            'insentif_pajak' => $insentifPajak,
            'insentif_air_minum' => $insentifAirMinum,
            'insentif_komunikasi' => $insentifKomunikasi,
            'total_insentif' => $totalInsentif,
            // Potongan Insentif
            'potongan_sanksi_perusahaan' => $potSanksi,
            'potongan_pmi_lain' => $potPmi,
            'potongan_dapenma' => $potDapenma,
            'potongan_bpjs_tenaga_kerja' => $potBpjstk,
            'potongan_perumahan' => $potPerumahan,
            'potongan_insentif_perusahaan' => $potTperusahaan,
            'potongan_korpri' => $potKorpri,
            'potongan_pajak' => $potPajak,
            'potongan_bpjs_kesehatan' => $potBpjskes,
            'total_potongan_insentif' => $totalPotonganInsentif,
            // Potongan Non-Insentif
            'potongan_koperasi' => $potKoperasi,
            'potongan_darma_wanita' => $potDarmawanita,
            'potongan_rekening_air_minum' => $potAirMinum,
            'potongan_kas' => $potKas,
            'potongan_bank_bjb' => $potBjb,
            'potongan_bank_bjbs' => $potBjbs,
            'potongan_bank_btn' => $potBtn,
            'potongan_bank_bpr' => $potBpr,
            'potongan_asuransi' => $potAsuransi,
            'potongan_zakat_profesi' => $potZakat,
            'total_potongan_non_insentif' => $totalPotonganNonInsentif,
            // Total & Bersih
            'total_potongan' => $totalPotongan,
            'insentif_diterima' => $insentifDiterima,
        ];
    }

    protected function nominalKey(string $sumber): string
    {
        return 'insentif_diterima';
    }

    public function laporanSlip(Request $request)
    {
        $ctx = $this->ambilData($request);
        $userLogin = session('simpeg_user') ?? [];
        $myRole = (string) ($userLogin['userlevel'] ?? '');
        $isPegawai = ($myRole === '5') || $request->has('my');

        $data = $ctx['data'];
        $riwayatInsentif = [];
        $selectedSlip = null;

        if ($isPegawai) {
            $myNik = $userLogin['nik'] ?? '';
            // Pegawai hanya melihat datanya sendiri
            $myItems = $data->filter(fn ($r) => ($r['nik'] ?? '') === $myNik)->values();
            $selectedSlip = $myItems->first();

            // Ambil seluruh riwayat insentif yang sudah terbit untuk pegawai ini
            try {
                $allPayrolls = DB::table('payroll')
                    ->whereIn('status', ['terbit', 'DITERBITKAN'])
                    ->where('pegawai_id', function ($q) use ($myNik) {
                        $q->select('id')->from('pegawai')->where('nik', $myNik)->limit(1);
                    })
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->get();

                foreach ($allPayrolls as $p) {
                    $m = (int) ($p->bulan ?? 0);
                    $y = (int) ($p->tahun ?? now()->year);
                    $bName = AbsensiController::BULAN[$m] ?? ('Bulan ' . $m);

                    $fmt = $this->formatRowToInsentif($p, $m, $y);
                    $riwayatInsentif[] = [
                        'id' => $p->id,
                        'judul' => 'Insentif ' . $bName,
                        'periode' => "$bName $y",
                        'nominal' => $fmt['insentif_diterima'],
                        'total_insentif' => $fmt['total_insentif'],
                        'total_potongan' => $fmt['total_potongan'],
                        'bulan' => $m,
                        'tahun' => $y,
                        'icon' => '⭐',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Riwayat insentif pegawai gagal dimuat: ' . $e->getMessage());
            }

            $data = $myItems;
        } else {
            // Tampilan Admin / Keuangan / Direksi
            // Jika ada parameter NIK atau ID yang dipilih, tampilkan slip resminya
            if ($request->has('nik') || $request->has('id')) {
                $targetNik = $request->get('nik');
                $targetId = $request->get('id');
                $selectedSlip = $data->first(function ($r) use ($targetNik, $targetId) {
                    return ($targetId && ($r['id'] == $targetId)) || ($targetNik && ($r['nik'] == $targetNik));
                });
            }
        }

        $sumber = $ctx['sumber'];
        $tahun = $ctx['tahun'];
        $bulan = $ctx['bulan'];
        $nominalKey = $this->nominalKey($sumber);
        $bulanList = AbsensiController::BULAN;

        return view('insentif.laporan-slip', compact(
            'data',
            'sumber',
            'tahun',
            'bulan',
            'nominalKey',
            'bulanList',
            'riwayatInsentif',
            'selectedSlip',
            'isPegawai'
        ));
    }

    /**
     * Tampilan slip resmi cetak langsung per ID payroll/insentif.
     */
    public function show($id, Request $request)
    {
        $payroll = DB::table('payroll')
            ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
            ->select(
                'payroll.*',
                'pegawai.nik as p_nik',
                'pegawai.name as p_name',
                'pegawai.jabatan as p_jabatan',
                'pegawai.unit_kerja as p_unit_kerja',
                'pegawai.golongan as p_golongan',
                'pegawai.golongan_detail as p_golongan_detail'
            )
            ->where('payroll.id', $id)
            ->first();

        if (! $payroll) {
            abort(404, 'Data slip insentif tidak ditemukan.');
        }

        $item = $this->formatRowToInsentif($payroll, (int) $payroll->bulan, (int) $payroll->tahun);
        $userLogin = session('simpeg_user') ?? [];

        // Proteksi jika pegawai login ingin melihat slip pegawai lain
        if (($userLogin['userlevel'] ?? '') === '5' && ($userLogin['nik'] ?? '') !== $item['nik']) {
            abort(403, 'Anda tidak memiliki hak akses melihat slip pegawai lain.');
        }

        return view('insentif.show', compact('item'));
    }

    public function laporanBukuBesar(Request $request)
    {
        $ctx = $this->ambilData($request);
        $nominalKey = $this->nominalKey($ctx['sumber']);
        $ctx['data'] = $ctx['data']->sortBy('nama')->values();
        $ctx['nominalKey'] = $nominalKey;
        $ctx['totalInsentifBruto'] = $ctx['data']->sum('total_insentif');
        $ctx['totalPotongan'] = $ctx['data']->sum('total_potongan');
        $ctx['total'] = $ctx['data']->sum($nominalKey);
        $ctx['bulanList'] = AbsensiController::BULAN;

        return view('insentif.laporan-buku-besar', $ctx);
    }

    public function laporanBukuBesarPerSub(Request $request)
    {
        $ctx = $this->ambilData($request);
        $nominalKey = $this->nominalKey($ctx['sumber']);
        $ctx['nominalKey'] = $nominalKey;
        $ctx['bulanList'] = AbsensiController::BULAN;
        $ctx['totalInsentifBruto'] = $ctx['data']->sum('total_insentif');
        $ctx['totalPotongan'] = $ctx['data']->sum('total_potongan');
        $ctx['total'] = $ctx['data']->sum($nominalKey);

        $ctx['data'] = $ctx['data']->groupBy('unit_kerja')->map(function ($group) use ($nominalKey) {
            return [
                'rows' => $group->sortBy('nama')->values(),
                'total_bruto' => $group->sum('total_insentif'),
                'total_potongan' => $group->sum('total_potongan'),
                'total' => $group->sum($nominalKey),
            ];
        });

        return view('insentif.laporan-buku-besar-per-sub', $ctx);
    }
}
