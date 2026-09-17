<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GajiProsesController;
use Illuminate\Http\Request;

session()->put('simpeg_user', [
    'nik' => '1711254',
    'nama_peg' => 'Heddy Kelana, S.H.',
    'userlevel' => '1',
]);

$ctrl = app(GajiProsesController::class);
$req = new Request(['bulan' => 9, 'tahun' => 2026]);
$view = $ctrl->index($req);
$gaji = $view->getData()['gaji'] ?? [];

echo "=== GAJI PROSES INDEX UNTUK NIK 1711254 ===\n";
foreach ($gaji as $g) {
    echo "ID: {$g['id']} | NIK: {$g['nik']} | Nama: {$g['nama']} | Status: {$g['status']} | Bisa Approve: " . ($g['bisa_approve'] ? "YA (TOMBOL SETUJUI MUNCUL!)" : "TIDAK") . "\n";
}
