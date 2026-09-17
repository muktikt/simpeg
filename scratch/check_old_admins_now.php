<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$oldAdminNiks = ['1711449', '1711389', '1711176', '1711444', '1711567', '1711590'];
$p = DB::table('pegawai')->whereIn('nik', $oldAdminNiks)->select('nik', 'name', 'jabatan', 'unit_kerja', 'role')->get();

echo "=== STATUS 6 ADMIN SDM LAMA DI DATABASE SEKARANG ===\n";
foreach ($p as $row) {
    echo "NIK: {$row->nik} | Nama: {$row->name} | Jabatan: {$row->jabatan} | Unit: {$row->unit_kerja} | Role: {$row->role}\n";
}
