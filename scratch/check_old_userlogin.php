<?php

$sqlFile = 'c:\\Users\\MUKTI\\projek simpeg\\simpeg lama\\backup\\backup-simpegtda-April-2021.sql';

$handle = fopen($sqlFile, 'r');
if (!$handle) {
    die("Cannot open SQL file\n");
}

$recording = false;
$userloginData = "";

while (($line = fgets($handle)) !== false) {
    if (stripos($line, 'INSERT INTO `userlogin`') !== false || stripos($line, 'INSERT INTO userlogin') !== false) {
        $recording = true;
        $userloginData .= $line;
        continue;
    }
    if ($recording) {
        $userloginData .= $line;
        if (strpos($line, ';') !== false) {
            $recording = false;
            echo "--- DUMP BLOCK ---\n";
            echo $userloginData . "\n";
            $userloginData = "";
        }
    }
}
fclose($handle);
