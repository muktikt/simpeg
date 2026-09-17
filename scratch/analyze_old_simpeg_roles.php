<?php

$sqlFile = 'c:\\Users\\MUKTI\\projek simpeg\\simpeg lama\\backup\\backup-simpegtda-April-2021.sql';
$handle = fopen($sqlFile, 'r');
if (!$handle) die("Cannot open file");

$users = [];
$recording = false;

while (($line = fgets($handle)) !== false) {
    if (stripos($line, 'INSERT INTO `userlogin`') !== false) {
        $recording = true;
        continue;
    }
    if ($recording) {
        // match rows like (iduser, 'username', 'password', 'nama', 'userlevel', 'foto')
        // e.g. (1, 'admin', '...', 'Administrator', '1', '...')
        if (preg_match_all("/\(\s*(\d+)\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/", $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $users[] = [
                    'id' => $m[1],
                    'username' => $m[2],
                    'nama' => trim($m[4]),
                    'userlevel' => $m[5],
                    'foto' => $m[6]
                ];
            }
        }
        if (strpos($line, ';') !== false) {
            $recording = false;
        }
    }
}
fclose($handle);

echo "Total users in old userlogin: " . count($users) . "\n\n";

$levels = [];
foreach ($users as $u) {
    $lvl = $u['userlevel'];
    if (!isset($levels[$lvl])) $levels[$lvl] = [];
    $levels[$lvl][] = $u;
}

foreach ($levels as $lvl => $list) {
    echo "=== USERLEVEL {$lvl} (Total: " . count($list) . ") ===\n";
    if (in_array($lvl, ['1', '2', '3', '4', '6', '7'])) {
        foreach ($list as $item) {
            echo "  ID: {$item['id']} | Username: {$item['username']} | Nama: {$item['nama']}\n";
        }
    } else {
        echo "  [Role {$lvl} adalah Pegawai biasa - menampilkan 5 contoh]:\n";
        for ($i = 0; $i < min(5, count($list)); $i++) {
            echo "  ID: {$list[$i]['id']} | Username: {$list[$i]['username']} | Nama: {$list[$i]['nama']}\n";
        }
    }
    echo "\n";
}
