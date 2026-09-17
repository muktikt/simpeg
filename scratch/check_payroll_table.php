<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COLUMNS OF PAYROLL TABLE ===\n";
$columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'payroll' ORDER BY ordinal_position");
foreach ($columns as $c) {
    echo "  {$c->column_name} ({$c->data_type})\n";
}

echo "\n=== SAMPLE ROWS OF PAYROLL TABLE ===\n";
$rows = DB::table('payroll')->limit(5)->get();
echo "Total rows: " . DB::table('payroll')->count() . "\n";
foreach ($rows as $r) {
    echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
}
