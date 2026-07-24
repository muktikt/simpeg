<?php

// Daftar NIK yang ditunjuk sebagai approver di tiap tahap alur persetujuan
// gaji/THR/Gaji 13. Disamakan dengan sistem lama (proses_terbit_gaji_dirum.php
// dkk) yang hardcode NIK '1711254' (Kepegawaian), '1711002' (Dirum), '1711001'
// (Dirut) langsung di kode - di sini dibuat jadi config supaya gampang diubah
// tanpa perlu edit kode program.
//
// Urutan approval: Proses -> Kepegawaian -> Dirum -> Dirut (final/terbit).

return [
    'kepegawaian' => '1711254', // Mukti Kurniawan (Admin/Staf SDM)
    'dirum' => '1800005',        // Direktur Umum (lihat akun dummy baru)
    'dirut' => '1800004',        // Bambang Wijaya (Direktur Utama)
];
