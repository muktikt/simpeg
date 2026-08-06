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
        ['label' => 'Beranda', 'icon' => 'home', 'route_name' => 'dashboard', 'roles' => ['1', '2', '7']],
        ['label' => 'Profil Saya', 'icon' => 'user', 'route_name' => 'profile.show'],
        ['label' => 'Payroll', 'icon' => 'report', 'route_name' => 'gaji-laporan.slip-gaji', 'personal' => true],
        ['label' => 'THR', 'icon' => 'calendar', 'route_name' => 'thr.laporan-slip', 'personal' => true],
        ['label' => 'Tunjangan Pendidikan', 'icon' => 'calendar', 'route_name' => 'gaji-tigabelas.laporan-slip', 'personal' => true],
        ['label' => 'Insentif', 'icon' => 'report', 'route_name' => 'insentif.laporan-slip', 'personal' => true],
        ['label' => 'Lembur', 'icon' => 'report', 'route_name' => 'gaji-laporan.lembur', 'personal' => true],
        ['label' => 'Pengaduan', 'icon' => 'report', 'route_name' => 'pengaduan.index', 'roles' => ['5', '1']],
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
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Data Pegawai All', 'route_name' => 'pegawai.index'],
                ['label' => 'Data Per Unit Kerja', 'route_name' => 'pegawai.per-unit-kerja'],
            ],
        ],
        [
            'label' => 'Set Potongan',
            'icon' => 'wrench',
            'roles' => ['2'],
            'items' => [
                ['label' => 'Potongan Gaji', 'route_name' => 'potongan-keu.index', 'params' => ['tipe' => 'gaji']],
                ['label' => 'Potongan THR', 'route_name' => 'potongan-keu.index', 'params' => ['tipe' => 'thr']],
                ['label' => 'Potongan Gaji 13', 'route_name' => 'potongan-keu.index', 'params' => ['tipe' => 'gaji13']],
                ['label' => 'Cek NIK Bulan Lalu', 'route_name' => 'cek-nik.bulan-lalu'],
                ['label' => 'Cek NIK Bulan Ini', 'route_name' => 'cek-nik.bulan-ini'],
                ['label' => 'Hapus Kesalahan NIK', 'route_name' => 'cek-nik.hapus'],
            ],
        ],
        [
            'label' => 'SET Rekening BJB',
            'icon' => 'shield',
            'roles' => ['2'],
            'items' => [
                ['label' => 'SET Rekening BJB', 'route_name' => 'rekening-bjb.index'],
            ],
        ],
        [
            'label' => 'Proses Terbit Potongan',
            'icon' => 'wrench',
            'roles' => ['2'],
            'items' => [
                ['label' => 'Potongan Gaji', 'route_name' => 'potongan-keu.index', 'params' => ['tipe' => 'gaji']],
                ['label' => 'Potongan THR', 'route_name' => 'potongan-keu.index', 'params' => ['tipe' => 'thr']],
                ['label' => 'Potongan Gaji 13', 'route_name' => 'potongan-keu.index', 'params' => ['tipe' => 'gaji13']],
            ],
        ],
        [
            'label' => 'Laporan Penggajian',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Lap. Potongan Keu', 'route_name' => 'laporan-potongan.potongan-keu', 'roles' => ['2']],
                ['label' => 'Lap. Potongan Keu Minus', 'route_name' => 'laporan-potongan.potongan-keu-minus', 'roles' => ['2']],
                ['label' => 'Lap. Potongan Keu Non-Minus', 'route_name' => 'laporan-potongan.potongan-keu-non-minus', 'roles' => ['2']],
                ['label' => 'Lap. Potongan BPJS', 'route_name' => 'laporan-potongan.potongan-bpjs', 'roles' => ['2']],
                ['label' => 'Lap. Absensi', 'route_name' => 'absensi.laporan', 'roles' => ['1', '2']],
                ['label' => 'Lap. Prestasi', 'route_name' => 'prestasi.laporan', 'roles' => ['1', '2']],
                ['label' => 'Lap. Lembur', 'route_name' => 'gaji-laporan.lembur', 'roles' => ['1', '2']],
                ['label' => 'Lap. Slip Gaji', 'route_name' => 'gaji-laporan.slip-gaji'],
                ['label' => 'Lap. Buku Besar Gaji', 'route_name' => 'gaji-laporan.buku-besar'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'gaji-laporan.buku-besar-per-sub'],
                ['label' => 'Lap. Payroll', 'route_name' => 'gaji-laporan.payroll', 'roles' => ['1', '2']],
                ['label' => 'Lap. Pajak', 'route_name' => 'gaji-laporan.pajak', 'roles' => ['1', '2']],
                ['label' => 'Lap. Gapok / Golongan', 'route_name' => 'gaji-pokok.laporan', 'roles' => ['1', '2']],
                ['label' => 'Lap. BPJSTK', 'route_name' => 'gaji-laporan.bpjstk', 'roles' => ['1', '2']],
                ['label' => 'Lap. Tunj. Perumahan', 'route_name' => 'gaji-laporan.tunj-perumahan', 'roles' => ['1', '2']],
            ],
        ],
        [
            'label' => 'Laporan THR',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Lap. Potongan Keu THR', 'route_name' => 'laporan-potongan.potongan-thr', 'roles' => ['2']],
                ['label' => 'Cetak Slip THR', 'route_name' => 'thr.laporan-slip'],
                ['label' => 'Lap. Buku Besar THR', 'route_name' => 'thr.laporan-buku-besar'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'thr.laporan-buku-besar-per-sub'],
                ['label' => 'Lap. Payroll THR', 'route_name' => 'laporan-potongan.payroll-thr', 'roles' => ['2']],
                ['label' => 'Lap. Pajak THR', 'route_name' => 'laporan-potongan.pajak-thr', 'roles' => ['2']],
            ],
        ],
        [
            'label' => 'Laporan Tunj. Pendidikan',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Cetak Slip Tunj. Pendidikan', 'route_name' => 'gaji-tigabelas.laporan-slip'],
                ['label' => 'Lap. Buku Besar Tunj. Pendidikan', 'route_name' => 'gaji-tigabelas.laporan-buku-besar'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'gaji-tigabelas.laporan-buku-besar-per-sub'],
                ['label' => 'Lap. Payroll Gaji 13', 'route_name' => 'laporan-potongan.payroll-gaji13', 'roles' => ['2']],
                ['label' => 'Lap. Pajak Gaji 13', 'route_name' => 'laporan-potongan.pajak-gaji13', 'roles' => ['2']],
            ],
        ],
        [
            'label' => 'Laporan Insentif',
            'icon' => 'report',
            'roles' => ['1', '2'],
            'items' => [
                ['label' => 'Cetak Slip Insentif', 'route_name' => 'insentif.laporan-slip'],
                ['label' => 'Lap. Buku Besar Insentif', 'route_name' => 'insentif.laporan-buku-besar'],
                ['label' => 'Lap. Buku Besar Per Sub', 'route_name' => 'insentif.laporan-buku-besar-per-sub'],
            ],
        ],
        [
            'label' => 'Laporan Kepegawaian',
            'icon' => 'report',
            'roles' => ['1', '2', '7'],
            'items' => [
                ['label' => 'Lap. Anak Diatas 21', 'route_name' => 'pegawai.laporan-anak'],
                ['label' => 'Lap. Cuti Pegawai', 'route_name' => 'cuti.index'],
                ['label' => 'Lap. Sanksi Pegawai', 'route_name' => 'sanksi.laporan', 'roles' => ['1', '2']],
            ],
        ],
    ],

];
