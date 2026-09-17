<?php
require 'vendor/autoload.php';

$dir = 'c:\\Users\\MUKTI\\projek simpeg\\simpeg baru\\storage\\framework\\sessions';
$files = scandir($dir);

foreach ($files as $f) {
    if ($f === '.' || $f === '..' || $f === '.gitignore') continue;
    $path = $dir . DIRECTORY_SEPARATOR . $f;
    $content = file_get_contents($path);
    $data = @unserialize($content);
    if (is_array($data)) {
        echo "=== SESSION FILE: {$f} ===\n";
        echo "Keys: " . implode(', ', array_keys($data)) . "\n";
        if (isset($data['simpeg_user'])) {
            echo "User: " . ($data['simpeg_user']['nik'] ?? '') . " - " . ($data['simpeg_user']['nama_peg'] ?? '') . " (Role: " . ($data['simpeg_user']['userlevel'] ?? '') . ")\n";
        }
        if (isset($data['dummy_gaji_proses'])) {
            echo "dummy_gaji_proses items: " . count($data['dummy_gaji_proses']) . "\n";
            foreach ($data['dummy_gaji_proses'] as $item) {
                echo "  - ID: {$item['id']} | NIK: {$item['nik']} | Nama: {$item['nama']} | Bulan: {$item['bulan']} | Tahun: {$item['tahun']} | Status: {$item['status']}\n";
            }
        }
        echo "\n";
    }
}
