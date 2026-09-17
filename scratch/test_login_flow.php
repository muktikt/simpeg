<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\HasApprovalChain;

class TestApprover {
    use HasApprovalChain;
    public function testCanApprove($status, $nik) {
        session()->put('simpeg_user.nik', $nik);
        return $this->canUserApprove($status);
    }
}

echo "=== TEST LOGIN & APPROVAL CHAIN ===\n\n";

// 1. Heddy Kelana (SDM)
$heddy = DB::table('pegawai')->where('nik', '1711254')->first();
echo "Pegawai 1711254: {$heddy->name} | Role: {$heddy->role} | Jabatan: {$heddy->jabatan}\n";

$tester = new TestApprover();

// Test tahap draft (Menunggu Kepegawaian)
$canHeddyApproveDraft = $tester->testCanApprove('draft', '1711254');
$canOtherApproveDraft = $tester->testCanApprove('draft', '1711157');
echo "Bisa approve tahap 'draft' (Menunggu Kepegawaian)?\n";
echo "  - NIK 1711254 (Heddy Kelana): " . ($canHeddyApproveDraft ? "YA (BERHASIL!)" : "TIDAK") . "\n";
echo "  - NIK 1711157 (Cahrudin): " . ($canOtherApproveDraft ? "YA" : "TIDAK (Benar, hanya Heddy)") . "\n\n";

// Test tahap kepegawaian (Menunggu Dirum)
$canDirumApprove = $tester->testCanApprove('kepegawaian', '1711002');
echo "Bisa approve tahap 'kepegawaian' (Menunggu Dirum)?\n";
echo "  - NIK 1711002 (Dr. Sunaryo - Dirum): " . ($canDirumApprove ? "YA (BERHASIL!)" : "TIDAK") . "\n\n";

// Test tahap dirum (Menunggu Dirut)
$canDirutApprove = $tester->testCanApprove('dirum', '1711001');
echo "Bisa approve tahap 'dirum' (Menunggu Dirut)?\n";
echo "  - NIK 1711001 (Nurpan - Dirut): " . ($canDirutApprove ? "YA (BERHASIL!)" : "TIDAK") . "\n\n";

echo "=== TEST SELESAI ===\n";
