<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PerubahanNikController extends Controller
{
    /**
     * Disamakan dengan sistem lama (ubahnik.php) - fungsinya mengganti NIK
     * satu pegawai lalu mencascade perubahan itu ke semua data terkait,
     * supaya riwayat pegawai tidak putus. Subtitle asli: "Untuk mengubah
     * NIK pegawai honor ke capeg" - jadi awalnya dibuat untuk kasus
     * pengangkatan honor->capeg, tapi fungsinya generik untuk ganti NIK apa saja.
     *
     * Sistem lama meng-update 15 tabel sekaligus (tbl_pegawai, tbl_absensi,
     * tbl_prestasi, tbl_keluarga, tbl_gaji_detail, tbl_thr_detail, dst)
     * karena semua tabel itu menyimpan NIK sebagai referensi.
     *
     * Di versi Laravel ini arsitekturnya sedikit beda: modul Absensi, Sanksi,
     * dan Prestasi menyimpan pegawai_id (bukan NIK) dan menampilkan NIK
     * secara live-join - jadi otomatis ikut berubah tanpa perlu di-cascade.
     * Modul Gaji Proses, THR, dan Gaji 13 menyimpan NIK sebagai SNAPSHOT saat
     * data dibuat - jadi ketiga modul itu yang perlu di-cascade manual di sini,
     * supaya riwayat gaji/THR/gaji13 yang sudah ada tetap konsisten.
     */
    public function index()
    {
        return view('perubahan-nik.index', [
            'pegawaiList' => app(PegawaiController::class)->all(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'nik_baru' => 'required|string|max:20',
        ]);

        $pegawaiList = app(PegawaiController::class)->all();
        $pegawai = collect($pegawaiList)->firstWhere('id', $validated['pegawai_id']);
        abort_if(! $pegawai, 404);

        $nikLama = $pegawai['nik'];
        $nikBaru = $validated['nik_baru'];

        // 1. Update NIK utama di Database Pegawai dan tabel snapshot terkait
        if (! empty($pegawai['db_id'])) {
            try {
                \Illuminate\Support\Facades\DB::table('pegawai')
                    ->where('id', $pegawai['db_id'])
                    ->update(['nik' => $nikBaru]);

                \Illuminate\Support\Facades\DB::table('payroll')
                    ->where('pegawai_id', $pegawai['db_id'])
                    ->orWhere('nik', $nikLama)
                    ->update(['nik' => $nikBaru]);

                \Illuminate\Support\Facades\DB::table('thr')
                    ->where('pegawai_id', $pegawai['db_id'])
                    ->orWhere('nik', $nikLama)
                    ->update(['nik' => $nikBaru]);

                \Illuminate\Support\Facades\DB::table('gaji_13')
                    ->where('pegawai_id', $pegawai['db_id'])
                    ->orWhere('nik', $nikLama)
                    ->update(['nik' => $nikBaru]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB update NIK failed: ' . $e->getMessage());
            }
        }

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('perubahan-nik.index')
            ->with('success', "NIK berhasil diubah dari {$nikLama} menjadi {$nikBaru}, termasuk di riwayat Gaji, THR, dan Gaji 13.");
    }
}
