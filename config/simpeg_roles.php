<?php

// Daftar role (userlevel) di SIMPEG.
// Kode angka ini disalin persis dari menu.php lama supaya konsisten
// dengan data userlevel yang sudah ada di database (tabel userlogin).
//
// Catatan: kode 4 dan 6 sengaja tidak ada - sudah tidak dipakai di sistem lama.

return [
    '1' => [
        'label' => 'Admin',
        'description' => 'Akses penuh ke seluruh modul',
    ],
    '2' => [
        'label' => 'Keuangan',
        'description' => 'Akses ke modul gaji, THR, gaji 13, dan laporan keuangan',
    ],
    '5' => [
        'label' => 'Pegawai',
        'description' => 'Akses terbatas untuk pegawai biasa (lihat data diri sendiri)',
    ],
    '7' => [
        'label' => 'Direksi',
        'description' => 'Akses dashboard & laporan versi direksi',
    ],
];
