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
        ['label' => 'Approval', 'icon' => 'report', 'route_name' => 'approval.index', 'roles' => ['1', '7']],
    ],

    'groups' => [
        [
            'label' => 'Pengaturan Umum',
            'icon' => 'settings',
            'roles' => ['1'],
            'items' => [
                ['label' => 'SET Aplikasi', 'route_name' => null],
                ['label' => 'Pengaturan Akun Pengguna', 'route_name' => 'user-akses.index'],
                ['label' => 'Perubahan NIK', 'route_name' => 'perubahan-nik.index'],
            ],
        ],
        [
            'label' => 'Pengaturan Proses Gaji',
            'icon' => 'wrench',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'SET Gaji Pokok', 'route_name' => 'gaji-pokok.index'],
                ['label' => 'SET Hari Kerja', 'route_name' => 'absensi.hari-kerja'],
                ['label' => 'SET Absensi', 'route_name' => 'absensi.index'],
                ['label' => 'SET Prestasi', 'route_name' => 'prestasi.index'],
                ['label' => 'SET DRD Tukin', 'route_name' => 'drd-tukin.index'],
                ['label' => 'SET Sanksi', 'route_name' => 'sanksi.index'],
                ['label' => 'Proses Cek Gaji Pegawai', 'route_name' => 'gaji-proses.index'],
                ['label' => 'Proses Cek Gaji Dirut', 'route_name' => 'gaji-proses.index'],
                ['label' => 'Proses Cek Gaji Dirum', 'route_name' => 'gaji-proses.index'],
                ['label' => 'Proses Cek Gaji Dirtek', 'route_name' => 'gaji-proses.index'],
                ['label' => 'Proses Penerbitan Gaji', 'route_name' => 'gaji-proses.index', 'restricted' => true],
            ],
        ],
        [
            'label' => 'Pengaturan THR',
            'icon' => 'calendar',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'Proses THR Pegawai', 'route_name' => 'thr.index'],
                ['label' => 'Proses THR Dirut', 'route_name' => 'thr.index'],
                ['label' => 'Proses THR Dirum', 'route_name' => 'thr.index'],
                ['label' => 'Proses THR Dirtek', 'route_name' => 'thr.index'],
                ['label' => 'Proses Penerbitan THR', 'route_name' => 'thr.index', 'restricted' => true],
            ],
        ],
        [
            'label' => 'Pengaturan Gaji 13',
            'icon' => 'calendar',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'Proses Gaji 13 Pegawai', 'route_name' => 'gaji-tigabelas.index'],
                ['label' => 'Proses Gaji 13 Dirut', 'route_name' => 'gaji-tigabelas.index'],
                ['label' => 'Proses Gaji 13 Dirum', 'route_name' => 'gaji-tigabelas.index'],
                ['label' => 'Proses Gaji 13 Dirtek', 'route_name' => 'gaji-tigabelas.index'],
                ['label' => 'Proses Penerbitan Gaji 13', 'route_name' => 'gaji-tigabelas.index', 'restricted' => true],
            ],
        ],
        [
            'label' => 'Pengaturan Asuransi',
            'icon' => 'shield',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'SET PHDP DAPENMA', 'route_name' => 'dapenma.index'],
            ],
        ],
        [
            'label' => 'Data Pegawai',
            'icon' => 'user',
            'items' => [
                ['label' => 'Data Pegawai All', 'route_name' => 'pegawai.index'],
                ['label' => 'Data Per Unit Kerja', 'route_name' => null],
            ],
        ],
        [
            'label' => 'Laporan Penggajian',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Lap. Absensi', 'route_name' => 'absensi.laporan'],
                ['label' => 'Lap. Prestasi', 'route_name' => 'prestasi.index'],
                ['label' => 'Lap. Lembur', 'route_name' => 'gaji-laporan.lembur'],
                ['label' => 'Lap. Slip Gaji', 'route_name' => 'gaji-laporan.slip-gaji'],
                ['label' => 'Lap. Buku Besar Gaji', 'route_name' => 'gaji-laporan.buku-besar'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'gaji-laporan.buku-besar-per-sub'],
                ['label' => 'Lap. Payroll', 'route_name' => 'gaji-laporan.payroll'],
                ['label' => 'Lap. Pajak', 'route_name' => 'gaji-laporan.pajak'],
                ['label' => 'Lap. Gapok / Golongan', 'route_name' => 'gaji-pokok.index'],
                ['label' => 'Lap. BPJSTK', 'route_name' => 'gaji-laporan.bpjstk'],
                ['label' => 'Lap. Tunj. Perumahan', 'route_name' => 'gaji-laporan.tunj-perumahan'],
            ],
        ],
        [
            'label' => 'Laporan THR',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip THR', 'route_name' => 'thr.index'],
                ['label' => 'Lap. Buku Besar THR', 'route_name' => 'thr.index'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'thr.index'],
            ],
        ],
        [
            'label' => 'Laporan Tunj. Pendidikan',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip Tunj. Pendidikan', 'route_name' => 'gaji-tigabelas.index'],
                ['label' => 'Lap. Buku Besar Tunj. Pendidikan', 'route_name' => 'gaji-tigabelas.index'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'gaji-tigabelas.index'],
            ],
        ],
        [
            'label' => 'Laporan Insentif',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip Insentif', 'route_name' => 'insentif.index'],
                ['label' => 'Lap. Buku Besar Insentif', 'route_name' => 'insentif.index'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'insentif.index'],
            ],
        ],
        [
            'label' => 'Laporan Kepegawaian',
            'icon' => 'report',
            'items' => [
                ['label' => 'Lap. Anak Diatas 21', 'route_name' => null],
                ['label' => 'Lap. Cuti Pegawai', 'route_name' => 'cuti.index'],
                ['label' => 'Lap. Sanksi Pegawai', 'route_name' => 'sanksi.index'],
            ],
        ],
    ],

];
