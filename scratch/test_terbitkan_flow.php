<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GajiProsesController;

echo "=== TEST APPROVAL STEP 1: KEPEGAWAIAN (NIK 1711254) ===\n";
session()->put('simpeg_user', [
    'nik' => '1711254',
    'nama_peg' => 'Heddy Kelana, S.H.',
    'userlevel' => '1',
]);

$ctrl = app(GajiProsesController::class);
$ctrl->terbitkan(1);

$afterStep1 = collect($ctrl->all())->firstWhere('id', 1);
echo "Status setelah disetujui Heddy Kelana: '{$afterStep1['status']}' (Harusnya 'kepegawaian' / Menunggu Dirum)\n\n";

echo "=== TEST APPROVAL STEP 2: DIRUM (NIK 1711002) ===\n";
session()->put('simpeg_user', [
    'nik' => '1711002',
    'nama_peg' => 'Dr. Sunaryo, S.T., MT.',
    'userlevel' => '7',
]);

$ctrl->terbitkan(1);

$afterStep2 = collect($ctrl->all())->firstWhere('id', 1);
echo "Status setelah disetujui Dirum: '{$afterStep2['status']}' (Harusnya 'dirum' / Menunggu Dirut)\n\n";

echo "=== TEST APPROVAL STEP 3: DIRUT (NIK 1711001) - FINAL ===\n";
session()->put('simpeg_user', [
    'nik' => '1711001',
    'nama_peg' => 'Nurpan, S.E., M.Si.',
    'userlevel' => '7',
]);

$ctrl->terbitkan(1);

$afterStep3 = collect($ctrl->all())->firstWhere('id', 1);
echo "Status setelah disetujui Dirut: '{$afterStep3['status']}' (Harusnya 'terbit' / Final)\n";

// Cek di database Supabase tabel payroll
$syncDb = \Illuminate\Support\Facades\DB::table('payroll')->where('periode', 'September 2026')->first();
echo "Data di Supabase payroll: " . ($syncDb ? "ADA! (ID: {$syncDb->id}, Status: {$syncDb->status}, Periode: {$syncDb->periode})" : "TIDAK ADA") . "\n";
