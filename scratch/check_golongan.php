<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COLUMNS OF GOLONGAN ===\n";
$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'golongan' ORDER BY ordinal_position");
foreach ($cols as $c) {
    echo "  {$c->column_name} ({$c->data_type})\n";
}

echo "\n=== ROWS IN GOLONGAN ===\n";
$rows = DB::table('golongan')->get();
echo "Count: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== COLUMNS OF RIWAYAT_GOLONGAN ===\n";
$cols2 = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'riwayat_golongan' ORDER BY ordinal_position");
foreach ($cols2 as $c) {
    echo "  {$c->column_name} ({$c->data_type})\n";
}
