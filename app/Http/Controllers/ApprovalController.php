<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\HasApprovalChain;

class ApprovalController extends Controller
{
    use HasApprovalChain;

    public function index()
    {
        $userNik = session('simpeg_user.nik');
        $pending = [];

        // 1. Gaji Bulanan
        $gajiProses = app(GajiProsesController::class)->all();
        foreach ($gajiProses as $item) {
            $nextStage = $this->nextStageFor($item['status'] ?? '');
            if ($nextStage && $userNik === $this->approverNikFor($nextStage)) {
                $item['jenis'] = 'Gaji Bulanan';
                $item['route'] = route('gaji-proses.show', $item['id']);
                $pending[] = $item;
            }
        }

        // 2. THR
        $thr = app(ThrController::class)->all();
        foreach ($thr as $item) {
            $nextStage = $this->nextStageFor($item['status'] ?? '');
            if ($nextStage && $userNik === $this->approverNikFor($nextStage)) {
                $item['jenis'] = 'THR';
                $item['route'] = route('thr.show', $item['id']);
                $pending[] = $item;
            }
        }

        // 3. Gaji 13
        $gaji13 = app(GajiTigabelasController::class)->all();
        foreach ($gaji13 as $item) {
            $nextStage = $this->nextStageFor($item['status'] ?? '');
            if ($nextStage && $userNik === $this->approverNikFor($nextStage)) {
                $item['jenis'] = 'Gaji 13';
                $item['route'] = route('gaji-tigabelas.show', $item['id']);
                $pending[] = $item;
            }
        }

        // 4. Potongan Keuangan (Gaji, THR, Gaji 13)
        $kepegawaianNik = config('simpeg_approval.kepegawaian', '1711254');
        $keuanganNik = config('simpeg_approval.keuangan', '1711296');

        try {
            $potonganQuery = \Illuminate\Support\Facades\DB::table('potongan_keu');
            if ($userNik === $kepegawaianNik) {
                // Kepegawaian (SDM) menyetujui potongan berstatus N / draft
                $potonganQuery->where(function ($q) {
                    $q->where('status', 'N')->orWhereNull('status')->orWhere('status', 'draft');
                });
            } elseif ($userNik === $keuanganNik) {
                // Keuangan menerbitkan potongan yang sudah disetujui SDM
                $potonganQuery->where('status', 'kepegawaian');
            } else {
                $potonganQuery = null;
            }

            if ($potonganQuery) {
                $potonganItems = $potonganQuery->orderByDesc('id')->get();
                if ($potonganItems->isNotEmpty()) {
                    $pegawaiMap = collect(app(PegawaiController::class)->all())->keyBy('nik');
                    $tipeLabels = [
                        'gaji'   => 'Potongan Gaji',
                        'thr'    => 'Potongan THR',
                        'gaji13' => 'Potongan Gaji 13',
                    ];
                    $kolomPot = [
                        'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
                        'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
                        'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
                    ];

                    foreach ($potonganItems as $p) {
                        $pArr = (array) $p;
                        $total = 0;
                        foreach ($kolomPot as $k) {
                            $total += (float) ($pArr[$k] ?? 0);
                        }
                        $peg = $pegawaiMap->get($pArr['nik'] ?? '');
                        $namaPeg = $peg['nama'] ?? ($pArr['nik'] ?? '-');
                        $tipe = $pArr['tipe'] ?? 'gaji';

                        $pending[] = [
                            'id' => $pArr['id'],
                            'jenis' => $tipeLabels[$tipe] ?? ('Potongan ' . ucfirst($tipe)),
                            'nik' => $pArr['nik'],
                            'nama' => $namaPeg,
                            'bulan' => (int) ($pArr['bulan'] ?? now()->month),
                            'tahun' => (int) ($pArr['tahun'] ?? now()->year),
                            'nominal' => $total,
                            'gaji_bersih' => $total,
                            'status' => $pArr['status'] ?? 'draft',
                            'route' => route('potongan-keu.terbit', $tipe),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Approval potongan_keu query failed: ' . $e->getMessage());
        }

        return view('approval.index', compact('pending'));
    }
}
