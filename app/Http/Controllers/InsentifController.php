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
    protected function parsePeriode(?string $periode, int $fallbackBulan, int $fallbackTahun): array
    {
        $bulan = $fallbackBulan;
        $tahun = $fallbackTahun;
        $text = trim((string) $periode);

        if ($text !== '') {
            foreach (AbsensiController::BULAN as $num => $nama) {
                if (stripos($text, $nama) !== false) {
                    $bulan = (int) $num;
                    break;
                }
            }
            if (preg_match('/\b(20\d{2})\b/', $text, $m)) {
                $tahun = (int) $m[1];
            }
        }

        return [$bulan, $tahun];
    }

    protected function buildDataQuery(string $sumber, int $tahun, int $bulan, bool $isPegawai, string $myNik): \Illuminate\Support\Collection
    {
        $data = collect();

        if ($sumber === 'gaji_bulanan') {
            try {
                $query = DB::table('payroll')
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
                    ->whereIn(DB::raw('LOWER(payroll.status)'), ['terbit', 'diterbitkan']);

                if ($isPegawai && ! empty($myNik)) {
                    $query->where('pegawai.nik', $myNik);
                }

                $rows = $query->orderBy('pegawai.name', 'asc')->get();

                if ($rows->isEmpty()) {
                    // Fallback cek ke tabel insentif jika ada
                    $insQuery = DB::table('insentif')
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
                        ->where(function ($q) use ($bulan, $tahun) {
                            $mPadded = str_pad($bulan, 2, '0', STR_PAD_LEFT);
                            $bulanNama = AbsensiController::BULAN[$bulan] ?? '';
                            $q->where('insentif.periode', 'ilike', "%{$mPadded}%{$tahun}%")
                              ->orWhere('insentif.periode', 'ilike', "%{$bulanNama}%{$tahun}%");
                        });

                    if ($isPegawai && ! empty($myNik)) {
                        $insQuery->where('pegawai.nik', $myNik);
                    }

                    $rows = $insQuery->orderByDesc('insentif.id')->get();
                }

                $data = $rows->map(function ($r) use ($bulan, $tahun) {
                    return $this->formatRowToInsentif($r, $bulan, $tahun);
                });
            } catch (\Throwable $e) {
                Log::error('Ambil data insentif bulanan gagal: ' . $e->getMessage());
            }
        } elseif ($sumber === 'insentif_pendidikan') {
            try {
                $queryIns = DB::table('insentif')
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
                    ->where(function ($q) use ($tahun) {
                        $q->where('insentif.periode', 'ilike', "%{$tahun}%")
                          ->orWhere('insentif.judul', 'ilike', "%{$tahun}%")
                          ->orWhereYear('insentif.created_at', $tahun);
                    });

                if ($isPegawai && ! empty($myNik)) {
                    $queryIns->where('pegawai.nik', $myNik);
                }

                $rowsIns = $queryIns->orderByDesc('insentif.id')->get();

                $data = $rowsIns->map(function ($r) use ($bulan, $tahun) {
                    [$rowBulan, $rowTahun] = $this->parsePeriode($r->periode ?? '', $bulan, $tahun);
                    return $this->formatRowToInsentif($r, $rowBulan, $rowTahun, 'insentif_pendidikan');
                });
            } catch (\Throwable $e) {
                Log::error('Ambil data insentif pendidikan gagal: ' . $e->getMessage());
            }
        } else {
            // Sumber Gaji 13 / Tunjangan Pendidikan
            try {
                $query13 = DB::table('gaji_13')
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
                    ->whereIn(DB::raw('LOWER(gaji_13.status)'), ['terbit', 'diterbitkan']);

                if ($isPegawai && ! empty($myNik)) {
                    $query13->where('pegawai.nik', $myNik);
                }

                $rows13 = $query13->orderBy('pegawai.name', 'asc')->get();

                $data = $rows13->map(function ($r) use ($bulan, $tahun) {
                    return $this->formatRowToInsentif($r, $bulan, $tahun, 'gaji13');
                });
            } catch (\Throwable $e) {
                Log::error('Ambil data insentif gaji 13 gagal: ' . $e->getMessage());
            }
        }

        return $data;
    }

    protected function ambilData(Request $request): array
    {
        $sumber = $request->get('sumber', 'gaji_bulanan');
        $tahun = (int) $request->get('tahun', now()->year);
        $bulan = (int) $request->get('bulan', now()->month);

        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || $request->has('my');
        $myNik = (string) ($userLogin['nik'] ?? '');

        $data = $this->buildDataQuery($sumber, $tahun, $bulan, $isPegawai, $myNik);

        return compact('data', 'sumber', 'tahun', 'bulan');
    }

    /**
     * Format row dari database ke struktur standar insentif dengan kalkulasi lengkap.
     */
    protected function formatRowToInsentif(object $r, int $bulan, int $tahun, string $sumber = 'gaji_bulanan'): array
    {
        $rowBulan = (int) ($r->bulan ?? $bulan);
        $rowTahun = (int) ($r->tahun ?? $tahun);
        if ($rowTahun <= 0 && ! empty($r->created_at)) {
            $rowTahun = (int) date('Y', strtotime((string) $r->created_at));
        }
        $bulanNama = AbsensiController::BULAN[$rowBulan] ?? ('Bulan ' . $rowBulan);

        if ($sumber === 'gaji13') {
            $periodeLabel = "Gaji 13 Tahun $rowTahun";
        } elseif ($sumber === 'insentif_pendidikan') {
            $periodeLabel = ! empty($r->periode) ? $r->periode : "Insentif Pendidikan $rowTahun";
        } else {
            $periodeLabel = ! empty($r->periode) ? $r->periode : "$bulanNama $rowTahun";
        }

        // Identitas Pegawai
        $id = $r->id ?? 0;
        $pegawaiId = $r->pegawai_id ?? '';
        $nik = $r->p_nik ?? ($r->nik ?? '-');
        $nama = $r->p_name ?? ($r->name ?? ($r->nama ?? 'Pegawai'));
        $jabatan = $r->p_jabatan ?? ($r->jabatan ?? '-');
        $unitKerja = $r->p_unit_kerja ?? ($r->unit_kerja ?? '-');
        $golongan = $r->p_golongan_detail ?? ($r->p_golongan ?? ($r->golongan ?? '-'));

        // Komponen Penerimaan Insentif
        if ($sumber === 'gaji13') {
            $pemecahan = GajiTigabelasController::hitungPemecahanGaji13((array) $r);
            $ins = $pemecahan['insentif'];

            $insentifJabatan = $ins['insentif_jabatan'];
            $insentifPrestasi = $ins['insentif_prestasi'];
            $insentifTransportasi = $ins['insentif_transportasi'];
            $insentifPangan = $ins['insentif_pangan'];
            $insentifBpjsKes = $ins['insentif_bpjs_kesehatan'];
            $insentifPerumahan = $ins['insentif_perumahan'];
            $insentifBpjstk = $ins['insentif_bpjs_tenaga_kerja'];
            $insentifPerusahaan = $ins['insentif_perusahaan'];
            $lembur = $ins['lembur'];
            $insentifPajak = $ins['insentif_pajak'];
            $insentifAirMinum = $ins['insentif_air_minum'];
            $insentifKomunikasi = $ins['insentif_komunikasi'];
            $totalInsentif = $ins['total_insentif'];

            // Potongan Insentif
            $potSanksi = $ins['potongan_sanksi_perusahaan'];
            $potPmi = $ins['potongan_trandist_pmi_lain'];
            $potDapenma = $ins['potongan_dapenma'];
            $potBpjstk = $ins['potongan_bpjs_tenaga_kerja'];
            $potPerumahan = $ins['potongan_perumahan'];
            $potTperusahaan = $ins['potongan_tunjangan_perusahaan'];
            $potKorpri = $ins['potongan_korpri'];
            $potPajak = $ins['potongan_pajak'];
            $potBpjskes = $ins['potongan_bpjs_kesehatan'];
            $totalPotonganInsentif = $ins['total_potongan_insentif'];

            // Potongan Non-Insentif (Koperasi & Kas memakai porsi pembagi proporsional)
            $potKoperasi = $ins['potongan_koperasi'];
            $potDarmawanita = $ins['potongan_darma_wanita'];
            $potAirMinum = $ins['potongan_rekening_air_minum'];
            $potKas = $ins['potongan_kas'];
            $potBjb = $ins['potongan_bank_bjb'];
            $potBjbs = $ins['potongan_bank_bjbs'];
            $potBtn = $ins['potongan_bank_btn'];
            $potBpr = $ins['potongan_bank_bpr'];
            $potAsuransi = $ins['potongan_asuransi'];
            $potZakat = $ins['potongan_zakat_profesi'];
            $totalPotonganNonInsentif = $ins['total_potongan_non_insentif'];

            $totalPotongan = $ins['total_potongan'];
            $insentifDiterima = $ins['insentif_diterima'];
            $periodeLabel = "Insentif Pendidikan 13 Tahun $tahun";
        } else {
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
        }

        return [
            'id' => $id,
            'pegawai_id' => $pegawaiId,
            'nik' => $nik,
            'nama' => $nama,
            'jabatan' => $jabatan,
            'unit_kerja' => $unitKerja,
            'golongan' => $golongan,
            'judul' => $r->judul ?? null,
            'periode' => $periodeLabel,
            'bulan' => $rowBulan,
            'tahun' => $rowTahun,
            'sumber_data' => $sumber,
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
        $sumber = $ctx['sumber'];
        $tahun = $ctx['tahun'];
        $bulan = $ctx['bulan'];
        $riwayatInsentif = [];
        $selectedSlip = null;
        $insentifPendidikanLatest = null;

        if ($isPegawai) {
            $myNik = (string) ($userLogin['nik'] ?? '');
            $targetId = $request->query('id');
            // Pegawai hanya melihat datanya sendiri
            $myItems = $data->filter(fn ($r) => ($r['nik'] ?? '') === $myNik)->values();
            $selectedSlip = $targetId
                ? ($myItems->firstWhere('id', (int) $targetId) ?? $myItems->first())
                : $myItems->first();

            // Ambil seluruh riwayat insentif (dari tabel insentif, payroll terbit, & gaji_13 terbit)
            try {
                $pegawaiId = DB::table('pegawai')->where('nik', $myNik)->value('id');
                if ($pegawaiId) {
                    $allPayrolls = DB::table('payroll')
                        ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
                        ->where('pegawai_id', $pegawaiId)
                        ->orderBy('tahun', 'desc')
                        ->orderBy('bulan', 'desc')
                        ->limit(12)
                        ->get();

                    $payrollPeriodKeys = [];
                    foreach ($allPayrolls as $p) {
                        $m = (int) ($p->bulan ?? 0);
                        $y = (int) ($p->tahun ?? now()->year);
                        $bName = AbsensiController::BULAN[$m] ?? ('Bulan ' . $m);
                        $payrollPeriodKeys[strtolower("$bName $y")] = true;
                    }

                    $allInsentifs = DB::table('insentif')
                        ->where('pegawai_id', $pegawaiId)
                        ->orderByDesc('id')
                        ->limit(24)
                        ->get();

                    foreach ($allInsentifs as $ins) {
                        $createdTs = ! empty($ins->created_at) ? strtotime((string) $ins->created_at) : time();
                        [$insBulan, $insTahun] = $this->parsePeriode(
                            $ins->periode ?? '',
                            (int) date('n', $createdTs),
                            (int) date('Y', $createdTs)
                        );
                        $periodeKey = strtolower(trim((string) ($ins->periode ?? '')));
                        $isAutoSyncDuplicate = isset($payrollPeriodKeys[$periodeKey])
                            && str_starts_with((string) ($ins->judul ?? ''), 'Slip Insentif ');

                        if ($isAutoSyncDuplicate) {
                            continue;
                        }

                        $fmt = $this->formatRowToInsentif($ins, $insBulan, $insTahun, 'insentif_pendidikan');
                        $rwItem = [
                            'id' => $ins->id,
                            'sumber' => 'insentif_pendidikan',
                            'judul' => ! empty($ins->judul) ? $ins->judul : ('Insentif Pendidikan ' . $fmt['periode']),
                            'periode' => $fmt['periode'],
                            'nominal' => $fmt['insentif_diterima'],
                            'total_insentif' => $fmt['total_insentif'],
                            'total_potongan' => $fmt['total_potongan'],
                            'bulan' => $insBulan,
                            'tahun' => $insTahun,
                            'icon' => '🎓',
                        ];
                        $riwayatInsentif[] = $rwItem;
                        if (! $insentifPendidikanLatest) {
                            $insentifPendidikanLatest = $rwItem;
                        }
                    }

                    foreach ($allPayrolls as $p) {
                        $m = (int) ($p->bulan ?? 0);
                        $y = (int) ($p->tahun ?? now()->year);
                        $bName = AbsensiController::BULAN[$m] ?? ('Bulan ' . $m);

                        $fmt = $this->formatRowToInsentif($p, $m, $y);
                        $riwayatInsentif[] = [
                            'id' => $p->id,
                            'sumber' => 'gaji_bulanan',
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

                    $allGaji13 = DB::table('gaji_13')
                        ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
                        ->where('pegawai_id', $pegawaiId)
                        ->orderByDesc('tahun')
                        ->limit(5)
                        ->get();

                    foreach ($allGaji13 as $g13) {
                        $y13 = (int) ($g13->tahun ?? now()->year);
                        $fmt13 = $this->formatRowToInsentif($g13, 7, $y13, 'gaji13');
                        $riwayatInsentif[] = [
                            'id' => $g13->id,
                            'sumber' => 'gaji13',
                            'judul' => 'Insentif Pendidikan (Gaji 13)',
                            'periode' => "Gaji 13 Tahun $y13",
                            'nominal' => $fmt13['insentif_diterima'],
                            'total_insentif' => $fmt13['total_insentif'],
                            'total_potongan' => $fmt13['total_potongan'],
                            'bulan' => 7,
                            'tahun' => $y13,
                            'icon' => '📘',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Riwayat insentif pegawai gagal dimuat: ' . $e->getMessage());
            }

            // Jika pegawai buka menu Insentif tanpa filter eksplisit dan bulan berjalan kosong, tampilkan slip terbaru yang tersedia
            $hasExplicitFilter = $request->filled('sumber') || $request->filled('bulan') || $request->filled('tahun') || $request->filled('id');
            if (! $selectedSlip && ! $hasExplicitFilter && ! empty($riwayatInsentif)) {
                $firstRw = $riwayatInsentif[0];
                $sumber = $firstRw['sumber'];
                $tahun = $firstRw['tahun'];
                $bulan = $firstRw['bulan'];
                $myItems = $this->buildDataQuery($sumber, $tahun, $bulan, true, $myNik);
                $selectedSlip = $myItems->firstWhere('id', (int) $firstRw['id']) ?? $myItems->first();
            }

            $data = $myItems;
        } else {
            // Tampilan Admin / Keuangan / Direksi
            // Jika ada parameter ID atau NIK yang dipilih, tampilkan slip resminya
            if ($request->has('id') || $request->has('nik')) {
                $targetNik = $request->get('nik');
                $targetId = $request->get('id');
                $selectedSlip = $targetId
                    ? $data->first(fn ($r) => (string) $r['id'] === (string) $targetId)
                    : null;
                if (! $selectedSlip && $targetNik) {
                    $selectedSlip = $data->first(fn ($r) => (string) $r['nik'] === (string) $targetNik);
                }
            }
        }

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
            'isPegawai',
            'insentifPendidikanLatest'
        ));
    }

    /**
     * Tampilan slip resmi cetak langsung per ID payroll/insentif.
     */
    public function show($id, Request $request)
    {
        $sumberReq = (string) $request->query('sumber', 'gaji_bulanan');
        $row = null;
        $rowSource = $sumberReq;

        if ($sumberReq === 'gaji13') {
            $row = DB::table('gaji_13')
                ->leftJoin('pegawai', 'gaji_13.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'gaji_13.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan',
                    'pegawai.golongan_detail as p_golongan_detail'
                )
                ->where('gaji_13.id', $id)
                ->first();
        } elseif ($sumberReq !== 'insentif_pendidikan') {
            $row = DB::table('payroll')
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
            if ($row) {
                $rowSource = 'gaji_bulanan';
            }
        }

        if (! $row && $sumberReq !== 'gaji13') {
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
            if ($row) {
                $rowSource = 'insentif_pendidikan';
            }
        }

        if (! $row) {
            abort(404, 'Data slip insentif tidak ditemukan.');
        }

        $userLogin = session('simpeg_user') ?? [];
        $isPegawai = ((string) ($userLogin['userlevel'] ?? '') === '5') || request('my');
        if ($isPegawai && ! empty($userLogin['nik']) && (string) ($row->p_nik ?? '') !== (string) $userLogin['nik']) {
            abort(403, 'Anda tidak berhak melihat data pegawai lain.');
        }
        if (in_array($rowSource, ['gaji_bulanan', 'gaji13'], true) && $isPegawai && ! in_array(strtolower($row->status ?? ''), ['terbit', 'diterbitkan', 'y', 'final'])) {
            abort(403, 'Slip insentif untuk periode ini belum diterbitkan.');
        }

        if ($rowSource === 'gaji13') {
            $item = $this->formatRowToInsentif($row, 7, (int) ($row->tahun ?? now()->year), 'gaji13');
        } elseif ($rowSource === 'gaji_bulanan') {
            $item = $this->formatRowToInsentif($row, (int) ($row->bulan ?? now()->month), (int) ($row->tahun ?? now()->year));
        } else {
            $createdTs = ! empty($row->created_at) ? strtotime((string) $row->created_at) : time();
            [$insBulan, $insTahun] = $this->parsePeriode($row->periode ?? '', (int) date('n', $createdTs), (int) date('Y', $createdTs));
            $item = $this->formatRowToInsentif($row, $insBulan, $insTahun, 'insentif_pendidikan');
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
