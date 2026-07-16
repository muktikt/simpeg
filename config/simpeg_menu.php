<?php

// Struktur menu sidebar SIMPEG.
// Disalin 1:1 dari menu_incl.php (versi lama) supaya tidak ada fitur/menu yang hilang.
// route_name null artinya modul itu belum dimigrasikan -> diarahkan ke halaman placeholder.
//
// 'roles' = daftar userlevel yang boleh lihat menu ini.
// Kosongkan/hapus key 'roles' berarti semua role boleh lihat.
// Kode role (dari menu.php lama): 1=Admin, 2=Keuangan, 3=Umum, 5=Pegawai, 7=Direksi

return [

    'single' => [
        ['label' => 'Beranda', 'icon' => 'home', 'route_name' => 'dashboard'],
    ],

    'groups' => [
        [
            'label' => 'Pengaturan Umum',
            'icon' => 'settings',
            'roles' => ['1'],
            'items' => [
                ['label' => 'SET Aplikasi', 'route_name' => null],
                ['label' => 'Pengaturan Akun Pengguna', 'route_name' => null],
                ['label' => 'Perubahan NIK', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Pengaturan Proses Gaji',
            'icon' => 'wrench',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'SET Gaji Pokok', 'route_name' => null],
                ['label' => 'SET Hari Kerja', 'route_name' => null],
                ['label' => 'SET Absensi', 'route_name' => null],
                ['label' => 'SET Prestasi', 'route_name' => null],
                ['label' => 'SET DRD Tukin', 'route_name' => null],
                ['label' => 'SET Sanksi', 'route_name' => null],
                ['label' => 'Proses Cek Gaji Pegawai', 'route_name' => null],
                ['label' => 'Proses Cek Gaji Dirut', 'route_name' => null],
                ['label' => 'Proses Cek Gaji Dirum', 'route_name' => null],
                ['label' => 'Proses Cek Gaji Dirtek', 'route_name' => null],
                ['label' => 'Proses Penerbitan Gaji', 'route_name' => null, 'restricted' => true],
            ],
        ],
        [
            'label' => 'Pengaturan THR',
            'icon' => 'calendar',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'Proses THR Pegawai', 'route_name' => null],
                ['label' => 'Proses THR Dirut', 'route_name' => null],
                ['label' => 'Proses THR Dirum', 'route_name' => null],
                ['label' => 'Proses THR Dirtek', 'route_name' => null],
                ['label' => 'Proses Penerbitan THR', 'route_name' => null, 'restricted' => true],
            ],
        ],
        [
            'label' => 'Pengaturan Gaji 13',
            'icon' => 'calendar',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'Proses Gaji 13 Pegawai', 'route_name' => null],
                ['label' => 'Proses Gaji 13 Dirut', 'route_name' => null],
                ['label' => 'Proses Gaji 13 Dirum', 'route_name' => null],
                ['label' => 'Proses Gaji 13 Dirtek', 'route_name' => null],
                ['label' => 'Proses Penerbitan Gaji 13', 'route_name' => null, 'restricted' => true],
            ],
        ],
        [
            'label' => 'Pengaturan Asuransi',
            'icon' => 'shield',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'SET PHDP DAPENMA', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Data Pegawai',
            'icon' => 'user',
            'items' => [
                ['label' => 'Data Pegawai All', 'route_name' => null],
                ['label' => 'Data Per Unit Kerja', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Laporan Penggajian',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Lap. Absensi', 'route_name' => null],
                ['label' => 'Lap. Prestasi', 'route_name' => null],
                ['label' => 'Lap. Lembur', 'route_name' => null],
                ['label' => 'Lap. Slip Gaji', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Gaji', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => null],
                ['label' => 'Lap. Payroll', 'route_name' => null],
                ['label' => 'Lap. Pajak', 'route_name' => null],
                ['label' => 'Lap. Gapok / Golongan', 'route_name' => null],
                ['label' => 'Lap. BPJSTK', 'route_name' => null],
                ['label' => 'Lap. Tunj. Perumahan', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Laporan THR',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip THR', 'route_name' => null],
                ['label' => 'Lap. Buku Besar THR', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Laporan Tunj. Pendidikan',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip Tunj. Pendidikan', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Tunj. Pendidikan', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Laporan Insentif',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip Insentif', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Insentif', 'route_name' => null],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Laporan Kepegawaian',
            'icon' => 'report',
            'items' => [
                ['label' => 'Lap. Anak Diatas 21', 'route_name' => null],
                ['label' => 'Lap. Cuti Pegawai', 'route_name' => null],
                ['label' => 'Lap. Sanksi Pegawai', 'route_name' => null],
            ],
        ],
    ],

];
