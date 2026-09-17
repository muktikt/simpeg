<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$p = DB::table('pegawai')->where('nik', '1711254')->first();
echo "Pegawai 1711254 ID: {$p->id} (type: " . gettype($p->id) . ")\n";

$authUser = DB::table('auth.users')->where('id', $p->id)->first();
echo "Auth user exists: " . ($authUser ? "YES, email: {$authUser->email}" : "NO") . "\n";
