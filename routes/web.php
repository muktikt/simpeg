<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PegawaiDetailController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\GajiPokokController;
use App\Http\Controllers\DrdTukinController;
use App\Http\Controllers\SanksiController;
use App\Http\Controllers\PrestasiController;
use App\Http\Controllers\GajiProsesController;
use App\Http\Controllers\ThrController;
use App\Http\Controllers\GajiTigabelasController;
use App\Http\Controllers\InsentifController;
use App\Http\Controllers\PerubahanNikController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\UserAksesController;
use App\Http\Controllers\DapenmaController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GajiLaporanController;
use App\Http\Controllers\PotonganKeuController;
use App\Http\Controllers\RekeningBjbController;
use App\Http\Controllers\CekNikController;
use App\Http\Controllers\LaporanPotonganController;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['simpeg.auth'])->group(function () {
    // Beranda (dashboard perusahaan) - bukan untuk role Pegawai, yang
    // landing page-nya adalah profil sendiri (lihat LoginController).
    Route::get('/beranda', [DashboardController::class, 'index'])->middleware(['simpeg.auth:1,2,7'])->name('dashboard');

    // Data Pegawai - list & laporan perusahaan TIDAK untuk role Pegawai
    // (mereka hanya boleh lihat data diri sendiri lewat halaman Profil).
    // show() tetap terbuka tapi dibatasi di controller: role 5 cuma bisa
    // lihat record miliknya sendiri.
    Route::prefix('pegawai')->name('pegawai.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [PegawaiController::class, 'index'])->name('index');
            Route::get('/laporan-anak', [PegawaiController::class, 'laporanAnakDiatas21'])->name('laporan-anak');
            Route::get('/per-unit-kerja', [PegawaiController::class, 'perUnitKerja'])->name('per-unit-kerja');
        });

        Route::get('/{id}', [PegawaiController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [PegawaiController::class, 'create'])->name('create');
            Route::post('/', [PegawaiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PegawaiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [PegawaiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [PegawaiController::class, 'destroy'])->whereNumber('id')->name('destroy');

            Route::post('/{id}/detail/{type}', [PegawaiDetailController::class, 'store'])->whereNumber('id')->name('detail.store');
            Route::put('/{id}/detail/{type}/{itemId}', [PegawaiDetailController::class, 'update'])->whereNumber('id')->whereNumber('itemId')->name('detail.update');
            Route::delete('/{id}/detail/{type}/{itemId}', [PegawaiDetailController::class, 'destroy'])->whereNumber('id')->whereNumber('itemId')->name('detail.destroy');

            // Angkat Calon Pegawai (CP) jadi Pegawai Tetap (PT) + ganti NIK.
            Route::post('/{id}/angkat-tetap', [PegawaiController::class, 'promoteToTetap'])->whereNumber('id')->name('promote-to-tetap');
        });
    });

    // Absensi - Read boleh semua role yang login, Create/Update/Delete cuma Admin.
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/', [AbsensiController::class, 'index'])->name('index');
            Route::get('/laporan', [AbsensiController::class, 'laporan'])->name('laporan');
        });

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [AbsensiController::class, 'create'])->name('create');
            Route::post('/', [AbsensiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AbsensiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [AbsensiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [AbsensiController::class, 'destroy'])->whereNumber('id')->name('destroy');

            // SET Hari Kerja - satu nilai global, bukan daftar.
            Route::get('/hari-kerja', [AbsensiController::class, 'hariKerjaEdit'])->name('hari-kerja');
            Route::put('/hari-kerja', [AbsensiController::class, 'hariKerjaUpdate'])->name('hari-kerja.update');
        });
    });

    // Gaji Pokok - Read boleh Admin & Keuangan. Tambah/Edit cuma Admin.
    // Catatan: sistem lama TIDAK punya fitur hapus untuk gaji pokok, jadi di sini juga tidak ada.
    Route::prefix('gaji-pokok')->name('gaji-pokok.')->group(function () {
        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/', [GajiPokokController::class, 'index'])->name('index');
            Route::get('/laporan', [GajiPokokController::class, 'laporan'])->name('laporan');
        });

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [GajiPokokController::class, 'create'])->name('create');
            Route::post('/', [GajiPokokController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [GajiPokokController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [GajiPokokController::class, 'update'])->whereNumber('id')->name('update');
        });
    });

    // DRD Tukin - full CRUD, Admin & Keuangan (sesuai grup menu Pengaturan Proses Gaji).
    Route::prefix('drd-tukin')->name('drd-tukin.')->middleware(['simpeg.auth:1,2'])->group(function () {
        Route::get('/', [DrdTukinController::class, 'index'])->name('index');
        Route::get('/create', [DrdTukinController::class, 'create'])->name('create');
        Route::post('/', [DrdTukinController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DrdTukinController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [DrdTukinController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [DrdTukinController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Sanksi Pegawai - Admin & Keuangan saja. Direksi TIDAK punya menu
    // Sanksi di sistem lama (dicek dari menu_incl_dirut.php).
    Route::prefix('sanksi')->name('sanksi.')->middleware(['simpeg.auth:1,2'])->group(function () {
        Route::get('/', [SanksiController::class, 'index'])->name('index');
        Route::get('/laporan', [SanksiController::class, 'laporan'])->name('laporan');

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [SanksiController::class, 'create'])->name('create');
            Route::post('/', [SanksiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SanksiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [SanksiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [SanksiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // Prestasi (rekap kerja bulanan untuk gaji) - index bisa dilihat semua role
    // login, tambah/edit/hapus cuma Admin & Keuangan.
    Route::prefix('prestasi')->name('prestasi.')->group(function () {
        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/', [PrestasiController::class, 'index'])->name('index');
            Route::get('/laporan', [PrestasiController::class, 'laporan'])->name('laporan');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [PrestasiController::class, 'create'])->name('create');
            Route::post('/', [PrestasiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PrestasiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [PrestasiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [PrestasiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // Proses Gaji Bulanan - index/show/terbitkan bisa diakses Admin, Keuangan,
    // dan Direksi (karena Dirum & Dirut perlu approve di alur berjenjang).
    // Create/Store/Hapus tetap cuma Admin & Keuangan.
    Route::prefix('gaji-proses')->name('gaji-proses.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [GajiProsesController::class, 'index'])->name('index');
            Route::get('/{id}', [GajiProsesController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/terbitkan', [GajiProsesController::class, 'terbitkan'])->whereNumber('id')->name('terbitkan');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [GajiProsesController::class, 'create'])->name('create');
            Route::post('/', [GajiProsesController::class, 'store'])->name('store');
            Route::delete('/{id}', [GajiProsesController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/ajax/hitung-keluarga/{pegawaiId}', [GajiProsesController::class, 'hitungKeluargaJson'])->whereNumber('pegawaiId')->name('hitung-keluarga');
        });
    });

    // THR - index/show/terbitkan bisa diakses Admin, Keuangan, dan Direksi
    // (karena Dirum & Dirut perlu approve di alur berjenjang).
    // Tambah/Hapus tetap cuma Admin & Keuangan.
    Route::prefix('thr')->name('thr.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,5,7'])->group(function () {
            Route::get('/laporan/slip', [ThrController::class, 'laporanSlip'])->name('laporan-slip');
        });

        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [ThrController::class, 'index'])->name('index');
            Route::get('/laporan/buku-besar', [ThrController::class, 'laporanBukuBesar'])->name('laporan-buku-besar');
            Route::get('/laporan/buku-besar-per-sub', [ThrController::class, 'laporanBukuBesarPerSub'])->name('laporan-buku-besar-per-sub');
            Route::get('/{id}', [ThrController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/terbitkan', [ThrController::class, 'terbitkan'])->whereNumber('id')->name('terbitkan');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [ThrController::class, 'create'])->name('create');
            Route::post('/', [ThrController::class, 'store'])->name('store');
            Route::delete('/{id}', [ThrController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/ajax/hitung-keluarga/{pegawaiId}', [ThrController::class, 'hitungKeluargaJson'])->whereNumber('pegawaiId')->name('hitung-keluarga');
        });
    });

    // Gaji 13 / Tunjangan Pendidikan - satu modul yang sama (dicek dari
    // menu_incl.php asli, "Laporan Tunj. Pendidikan" mengarah ke file yang
    // sama dengan Gaji 13). Index/show bisa dilihat Admin, Keuangan, Direksi.
    Route::prefix('gaji-tigabelas')->name('gaji-tigabelas.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,5,7'])->group(function () {
            Route::get('/laporan/slip', [GajiTigabelasController::class, 'laporanSlip'])->name('laporan-slip');
        });

        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [GajiTigabelasController::class, 'index'])->name('index');
            Route::get('/laporan/buku-besar', [GajiTigabelasController::class, 'laporanBukuBesar'])->name('laporan-buku-besar');
            Route::get('/laporan/buku-besar-per-sub', [GajiTigabelasController::class, 'laporanBukuBesarPerSub'])->name('laporan-buku-besar-per-sub');
            Route::get('/{id}', [GajiTigabelasController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/terbitkan', [GajiTigabelasController::class, 'terbitkan'])->whereNumber('id')->name('terbitkan');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [GajiTigabelasController::class, 'create'])->name('create');
            Route::post('/', [GajiTigabelasController::class, 'store'])->name('store');
            Route::delete('/{id}', [GajiTigabelasController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/ajax/hitung-keluarga/{pegawaiId}', [GajiTigabelasController::class, 'hitungKeluargaJson'])->whereNumber('pegawaiId')->name('hitung-keluarga');
        });
    });

    // Insentif
    Route::prefix('insentif')->name('insentif.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,5'])->group(function () {
            Route::get('/laporan/slip', [InsentifController::class, 'laporanSlip'])->name('laporan-slip');
        });
        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/laporan/buku-besar', [InsentifController::class, 'laporanBukuBesar'])->name('laporan-buku-besar');
            Route::get('/laporan/buku-besar-per-sub', [InsentifController::class, 'laporanBukuBesarPerSub'])->name('laporan-buku-besar-per-sub');
        });
    });

    // Perubahan NIK - Admin only.
    Route::prefix('perubahan-nik')->name('perubahan-nik.')->middleware(['simpeg.auth:1'])->group(function () {
        Route::get('/', [PerubahanNikController::class, 'index'])->name('index');
        Route::post('/', [PerubahanNikController::class, 'update'])->name('update');
    });

    // Cuti - READ ONLY, tidak punya data sendiri (lihat catatan di
    // CutiController). Admin, Keuangan, Direksi (bukan role Pegawai).
    Route::get('/cuti', [CutiController::class, 'index'])->middleware(['simpeg.auth:1,2,7'])->name('cuti.index');

    // Pengaturan Akun Pengguna (Hak Akses User) - Admin only.
    Route::prefix('user-akses')->name('user-akses.')->middleware(['simpeg.auth:1'])->group(function () {
        Route::get('/', [UserAksesController::class, 'index'])->name('index');
        Route::get('/create', [UserAksesController::class, 'create'])->name('create');
        Route::post('/', [UserAksesController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserAksesController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [UserAksesController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [UserAksesController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Asuransi (Dapenma) - Admin & Keuangan.
    Route::prefix('dapenma')->name('dapenma.')->middleware(['simpeg.auth:1,2'])->group(function () {
        Route::get('/', [DapenmaController::class, 'index'])->name('index');
        Route::get('/create', [DapenmaController::class, 'create'])->name('create');
        Route::post('/', [DapenmaController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DapenmaController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [DapenmaController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [DapenmaController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Laporan Penggajian - Direksi di sistem lama HANYA punya akses ke
    // Slip Gaji, Buku Besar Gaji, dan Buku Besar Per Sub (dicek dari
    // menu_incl_dirut.php). Lembur/Payroll/Pajak/BPJSTK/Tunj. Perumahan
    // cuma untuk Admin & Keuangan.
    Route::prefix('gaji-laporan')->name('gaji-laporan.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,5,7'])->group(function () {
            Route::get('/slip-gaji', [GajiLaporanController::class, 'slipGaji'])->name('slip-gaji');
        });

        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/buku-besar', [GajiLaporanController::class, 'bukuBesar'])->name('buku-besar');
            Route::get('/buku-besar-per-sub', [GajiLaporanController::class, 'bukuBesarPerSub'])->name('buku-besar-per-sub');
        });

        Route::middleware(['simpeg.auth:1,2,5'])->group(function () {
            Route::get('/lembur', [GajiLaporanController::class, 'lembur'])->name('lembur');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/payroll', [GajiLaporanController::class, 'payroll'])->name('payroll');
            Route::get('/pajak', [GajiLaporanController::class, 'pajak'])->name('pajak');
            Route::get('/bpjstk', [GajiLaporanController::class, 'bpjstk'])->name('bpjstk');
            Route::get('/tunj-perumahan', [GajiLaporanController::class, 'tunjPerumahan'])->name('tunj-perumahan');
        });
    });

    // ══════════════════════════════════════════════════════════════
    // MODUL KEUANGAN — Potongan, Rekening BJB, Cek NIK, Laporan
    // Hak akses: Role 2 (Keuangan) — sesuai menu_incl_keu.php lama
    // ══════════════════════════════════════════════════════════════

    // Potongan Keuangan (Gaji / THR / Gaji 13) - CRUD + terbitkan + belum-masuk
    Route::prefix('potongan-keu')->name('potongan-keu.')->middleware(['simpeg.auth:2'])->group(function () {
        Route::get('/{tipe}', [PotonganKeuController::class, 'index'])->name('index')->where('tipe', 'gaji|thr|gaji13');
        Route::get('/{tipe}/create', [PotonganKeuController::class, 'create'])->name('create')->where('tipe', 'gaji|thr|gaji13');
        Route::post('/{tipe}', [PotonganKeuController::class, 'store'])->name('store')->where('tipe', 'gaji|thr|gaji13');
        Route::get('/{tipe}/{id}/edit', [PotonganKeuController::class, 'edit'])->name('edit')->where('tipe', 'gaji|thr|gaji13')->whereNumber('id');
        Route::put('/{tipe}/{id}', [PotonganKeuController::class, 'update'])->name('update')->where('tipe', 'gaji|thr|gaji13')->whereNumber('id');
        Route::delete('/{tipe}/{id}', [PotonganKeuController::class, 'destroy'])->name('destroy')->where('tipe', 'gaji|thr|gaji13')->whereNumber('id');
        Route::post('/{tipe}/terbitkan', [PotonganKeuController::class, 'terbitkan'])->name('terbitkan')->where('tipe', 'gaji|thr|gaji13');
        Route::get('/{tipe}/belum-masuk', [PotonganKeuController::class, 'belumMasuk'])->name('belum-masuk')->where('tipe', 'gaji|thr|gaji13');
    });

    // Rekening BJB - CRUD
    Route::prefix('rekening-bjb')->name('rekening-bjb.')->middleware(['simpeg.auth:2'])->group(function () {
        Route::get('/', [RekeningBjbController::class, 'index'])->name('index');
        Route::get('/create', [RekeningBjbController::class, 'create'])->name('create');
        Route::post('/', [RekeningBjbController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [RekeningBjbController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [RekeningBjbController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [RekeningBjbController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/belum-masuk', [RekeningBjbController::class, 'belumMasuk'])->name('belum-masuk');
    });

    // Cek NIK & Hapus Kesalahan NIK
    Route::prefix('cek-nik')->name('cek-nik.')->middleware(['simpeg.auth:2'])->group(function () {
        Route::get('/bulan-lalu', [CekNikController::class, 'bulanLalu'])->name('bulan-lalu');
        Route::get('/bulan-ini', [CekNikController::class, 'bulanIni'])->name('bulan-ini');
        Route::get('/hapus', [CekNikController::class, 'hapusForm'])->name('hapus');
        Route::post('/hapus', [CekNikController::class, 'hapusProses'])->name('hapus.proses');
    });

    // Laporan Potongan Keuangan
    Route::prefix('laporan-potongan')->name('laporan-potongan.')->middleware(['simpeg.auth:2'])->group(function () {
        Route::get('/potongan-keu', [LaporanPotonganController::class, 'potonganKeu'])->name('potongan-keu');
        Route::get('/potongan-keu-minus', [LaporanPotonganController::class, 'potonganKeuMinus'])->name('potongan-keu-minus');
        Route::get('/potongan-keu-non-minus', [LaporanPotonganController::class, 'potonganKeuNonMinus'])->name('potongan-keu-non-minus');
        Route::get('/potongan-bpjs', [LaporanPotonganController::class, 'potonganBpjs'])->name('potongan-bpjs');
        Route::get('/potongan-thr', [LaporanPotonganController::class, 'potonganThr'])->name('potongan-thr');
        Route::get('/payroll-thr', [LaporanPotonganController::class, 'payrollThr'])->name('payroll-thr');
        Route::get('/pajak-thr', [LaporanPotonganController::class, 'pajakThr'])->name('pajak-thr');
        Route::get('/payroll-gaji13', [LaporanPotonganController::class, 'payrollGaji13'])->name('payroll-gaji13');
        Route::get('/pajak-gaji13', [LaporanPotonganController::class, 'pajakGaji13'])->name('pajak-gaji13');
    });

    // ══════════════════════════════════════════════════════════════

    // Approval - dashboard kotak masuk, menggabungkan item pending dari
    // Gaji Proses/THR/Gaji13 yang menunggu approval user yang login.
    Route::get('/approval', [ApprovalController::class, 'index'])->middleware(['simpeg.auth:1,2,7'])->name('approval.index');

    // Pengaduan Pegawai - Pegawai (5) & Admin (1)
    Route::get('/pengaduan', function () {
        $pengaduan = session('dummy_pengaduan', []);
        $myRole = session('simpeg_user.userlevel');
        $myNik = session('simpeg_user.nik');
        if ($myRole === '5') {
            $pengaduan = collect($pengaduan)->where('nik', $myNik)->values()->all();
        }
        return view('pengaduan.index', compact('pengaduan'));
    })->name('pengaduan.index');

    Route::post('/pengaduan', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'subjek' => 'required|string|max:150',
            'pesan' => 'required|string',
        ]);
        $userLogin = session('simpeg_user');
        $items = session('dummy_pengaduan', []);
        $newId = $items ? max(array_column($items, 'id')) + 1 : 1;
        $items[] = [
            'id' => $newId,
            'nik' => $userLogin['nik'],
            'nama' => $userLogin['nama_peg'],
            'subjek' => $validated['subjek'],
            'pesan' => $validated['pesan'],
            'status' => 'Pending',
            'tanggal' => date('Y-m-d H:i'),
        ];
        session()->put('dummy_pengaduan', $items);
        return back()->with('success', 'Pengaduan berhasil dikirim.');
    })->name('pengaduan.store');

    // Profile - Khusus role Pegawai (role 5) di web. Admin & Keuangan mengelola data via Data Pegawai.
    Route::prefix('profile')->name('profile.')->middleware(['simpeg.auth:5'])->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update-password');
        Route::post('/dokumen', [ProfileController::class, 'uploadDokumen'])->name('upload-dokumen');
    });

    // Semua modul lama yang belum dimigrasikan -> halaman placeholder.
    Route::get('/modul/{slug}', [PlaceholderController::class, 'show'])->name('placeholder');
});
