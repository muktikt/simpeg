<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PUBLIC TABLES IN DATABASE ===\n";
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
foreach ($tables as $t) {
    echo " - {$t->table_name}\n";
}
