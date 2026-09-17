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

        return view('approval.index', compact('pending'));
    }
}
