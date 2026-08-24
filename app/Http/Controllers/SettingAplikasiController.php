<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingAplikasiController extends Controller
{
    /**
     * Tampilkan halaman pengaturan aplikasi umum.
     */
    public function index()
    {
        $settings = [
            'nama_instansi' => 'PERUMDAM Tirta Darma Ayu',
            'singkatan_instansi' => 'TDA',
            'alamat_instansi' => 'Jl. MT. Haryono No. 1, Indramayu',
            'email_instansi' => 'info@tirtadarmaayu.co.id',
            'telepon_instansi' => '(0234) 272023',
            'website' => 'https://tirtadarmaayu.co.id',
            'periode_aktif_bulan' => now()->month,
            'periode_aktif_tahun' => now()->year,
            'jam_masuk_kantor' => '07:30',
            'jam_pulang_kantor' => '16:00',
            'toleransi_terlambat_menit' => 15,
            'versi_aplikasi' => '2.4.0 (Build 2026)',
        ];

        // Cek jika ada pengaturan tersimpan di session atau database
        if (session()->has('app_settings')) {
            $settings = array_merge($settings, session('app_settings'));
        }

        return view('setting-aplikasi.index', compact('settings'));
    }

    /**
     * Simpan perubahan pengaturan aplikasi.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_instansi' => 'required|string|max:150',
            'singkatan_instansi' => 'required|string|max:50',
            'alamat_instansi' => 'nullable|string',
            'email_instansi' => 'nullable|email',
            'telepon_instansi' => 'nullable|string|max:50',
            'website' => 'nullable|string',
            'periode_aktif_bulan' => 'required|numeric|between:1,12',
            'periode_aktif_tahun' => 'required|numeric',
            'jam_masuk_kantor' => 'required|string',
            'jam_pulang_kantor' => 'required|string',
            'toleransi_terlambat_menit' => 'required|numeric|min:0',
        ]);

        $settings = array_merge(session('app_settings', []), $validated);
        session()->put('app_settings', $settings);

        return redirect()->route('setting-aplikasi.index')->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
