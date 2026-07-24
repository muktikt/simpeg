<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\HasApprovalChain;

class ApprovalController extends Controller
{
    use HasApprovalChain;

    public function index()
    {
        // Pastikan dummy data ter-seed jika kosong dengan memanggil controller masing-masing
        if (! session()->has('dummy_gaji_proses')) {
            app(GajiProsesController::class)->index(new Request());
        }
        if (! session()->has('dummy_thr')) {
            app(ThrController::class)->index(new Request());
        }
        if (! session()->has('dummy_gaji13')) {
            app(GajiTigabelasController::class)->index(new Request());
        }

        $userNik = session('simpeg_user.nik');
        $pending = [];

        // 1. Gaji Bulanan
        $gajiProses = session('dummy_gaji_proses', []);
        foreach ($gajiProses as $item) {
            $nextStage = $this->nextStageFor($item['status'] ?? '');
            if ($nextStage && $userNik === $this->approverNikFor($nextStage)) {
                $item['jenis'] = 'Gaji Bulanan';
                $item['route'] = route('gaji-proses.show', $item['id']);
                $pending[] = $item;
            }
        }

        // 2. THR
        $thr = session('dummy_thr', []);
        foreach ($thr as $item) {
            $nextStage = $this->nextStageFor($item['status'] ?? '');
            if ($nextStage && $userNik === $this->approverNikFor($nextStage)) {
                $item['jenis'] = 'THR';
                $item['route'] = route('thr.show', $item['id']);
                $pending[] = $item;
            }
        }

        // 3. Gaji 13
        $gaji13 = session('dummy_gaji13', []);
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
