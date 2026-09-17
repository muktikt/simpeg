<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PEGAWAI DENGAN JABATAN / UNIT TERKAIT SDM ===\n";
$sdm = DB::table('pegawai')
    ->where(function($q) {
        $q->where('jabatan', 'ILIKE', '%sdm%')
          ->orWhere('jabatan', 'ILIKE', '%sumber daya manusia%')
          ->orWhere('jabatan', 'ILIKE', '%kepegawaian%')
          ->orWhere('unit_kerja', 'ILIKE', '%sdm%')
          ->orWhere('unit_kerja', 'ILIKE', '%sumber daya manusia%');
    })
    ->select('id', 'nik', 'name', 'jabatan', 'unit_kerja', 'role')
    ->orderBy('nik')
    ->get();

foreach ($sdm as $s) {
    echo "NIK: {$s->nik} | Nama: {$s->name} | Jabatan: {$s->jabatan} | Unit: {$s->unit_kerja} | Role: {$s->role}\n";
}

echo "\n=== PEGAWAI DENGAN JABATAN / UNIT TERKAIT KEUANGAN ===\n";
$keu = DB::table('pegawai')
    ->where(function($q) {
        $q->where('jabatan', 'ILIKE', '%keuangan%')
          ->orWhere('jabatan', 'ILIKE', '%akuntansi%')
          ->orWhere('jabatan', 'ILIKE', '%kas%')
          ->orWhere('unit_kerja', 'ILIKE', '%keuangan%');
    })
    ->select('id', 'nik', 'name', 'jabatan', 'unit_kerja', 'role')
    ->orderBy('nik')
    ->get();

foreach ($keu as $k) {
    echo "NIK: {$k->nik} | Nama: {$k->name} | Jabatan: {$k->jabatan} | Unit: {$k->unit_kerja} | Role: {$k->role}\n";
}

echo "\n=== PEGAWAI DENGAN JABATAN DIREKTUR ===\n";
$dir = DB::table('pegawai')
    ->where('jabatan', 'ILIKE', '%direktur%')
    ->select('id', 'nik', 'name', 'jabatan', 'unit_kerja', 'role')
    ->orderBy('nik')
    ->get();

foreach ($dir as $d) {
    echo "NIK: {$d->nik} | Nama: {$d->name} | Jabatan: {$d->jabatan} | Unit: {$d->unit_kerja} | Role: {$d->role}\n";
}
