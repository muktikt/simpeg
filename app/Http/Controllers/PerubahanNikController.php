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
            'pegawaiList' => session('dummy_pegawai', []),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'nik_baru' => 'required|string|max:20',
        ]);

        $pegawaiList = session('dummy_pegawai', []);
        $pegawai = collect($pegawaiList)->firstWhere('id', $validated['pegawai_id']);
        abort_if(! $pegawai, 404);

        $nikLama = $pegawai['nik'];
        $nikBaru = $validated['nik_baru'];

        // 1. Update NIK utama di Data Pegawai.
        $pegawaiList = collect($pegawaiList)->map(function ($p) use ($validated, $nikBaru) {
            if ($p['id'] === $validated['pegawai_id']) {
                $p['nik'] = $nikBaru;
            }

            return $p;
        })->all();
        session()->put('dummy_pegawai', $pegawaiList);

        // 2. Cascade update NIK snapshot di Gaji Proses, THR, dan Gaji 13.
        foreach (['dummy_gaji_proses', 'dummy_thr', 'dummy_gaji13'] as $sessionKey) {
            $rows = session($sessionKey, []);
            $rows = collect($rows)->map(function ($row) use ($validated, $nikBaru) {
                if (($row['pegawai_id'] ?? null) === $validated['pegawai_id']) {
                    $row['nik'] = $nikBaru;
                }

                return $row;
            })->all();
            session()->put($sessionKey, $rows);
        }

        return redirect()->route('perubahan-nik.index')
            ->with('success', "NIK berhasil diubah dari {$nikLama} menjadi {$nikBaru}, termasuk di riwayat Gaji, THR, dan Gaji 13.");
    }
}
