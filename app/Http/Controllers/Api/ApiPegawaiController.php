<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\PegawaiController;

class ApiPegawaiController extends Controller
{
    protected array $dummyUsers = [
        '1711254' => [
            'nik' => '1711254',
            'password' => 'password',
            'nama_peg' => 'Mukti Kurniawan',
            'jabatan' => 'Staf SDM',
            'userlevel' => '1',
        ],
        '1800003' => [
            'nik' => '1800003',
            'password' => 'password',
            'nama_peg' => 'Nur Hidayah',
            'jabatan' => 'Pegawai',
            'userlevel' => '5',
        ],
    ];

    /**
     * Auth - Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = $this->dummyUsers[$request->nik] ?? null;

        if (!$user || $request->password !== $user['password']) {
            return response()->json([
                'success' => false,
                'message' => 'NIK atau kata sandi salah.',
            ], 401);
        }

        $token = base64_encode($user['nik'] . ':' . time());

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'nik' => $user['nik'],
                    'nama' => $user['nama_peg'],
                    'jabatan' => $user['jabatan'],
                    'userlevel' => $user['userlevel'],
                ],
                'token' => $token,
            ]
        ]);
    }

    /**
     * Auth - Ganti Password
     */
    public function gantiPassword(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:4',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    /**
     * Profil Pegawai Lengkap
     */
    public function profile(Request $request)
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik', '1800003');

        if (!session()->has('dummy_pegawai')) {
            app(PegawaiController::class)->index(request());
        }

        $allPegawai = session('dummy_pegawai', []);
        $pegawai = collect($allPegawai)->firstWhere('nik', $nik);

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data' => $pegawai,
        ]);
    }

    /**
     * Payroll - Slip Gaji Bulanan
     */
    public function slipGaji(Request $request)
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik', '1800003');

        return response()->json([
            'success' => true,
            'message' => 'Data slip gaji berhasil diambil',
            'data' => [
                'periode' => 'Agustus 2026',
                'nik' => $nik,
                'nama' => 'Nur Hidayah',
                'jabatan' => 'Pegawai',
                'gaji_pokok' => 4500000,
                'tunjangan_jabatan' => 1200000,
                'tunjangan_keluarga' => 450000,
                'insentif' => 850000,
                'potongan_dapenma' => 150000,
                'potongan_bjb' => 200000,
                'total_terima' => 6650000,
            ],
        ]);
    }

    /**
     * Payroll - Slip THR
     */
    public function slipThr(Request $request)
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik', '1800003');

        return response()->json([
            'success' => true,
            'message' => 'Data slip THR berhasil diambil',
            'data' => [
                'tahun' => '2026',
                'nik' => $nik,
                'nama' => 'Nur Hidayah',
                'nominal_thr' => 4500000,
                'tgl_cair' => '2026-04-10',
                'status' => 'DITERBITKAN',
            ],
        ]);
    }

    /**
     * Payroll - Gaji 13 / Tunjangan Pendidikan
     */
    public function slipGaji13(Request $request)
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik', '1800003');

        return response()->json([
            'success' => true,
            'message' => 'Data Gaji 13 berhasil diambil',
            'data' => [
                'tahun' => '2026',
                'nik' => $nik,
                'nama' => 'Nur Hidayah',
                'nominal' => 4500000,
                'tgl_cair' => '2026-06-15',
                'status' => 'DITERBITKAN',
            ],
        ]);
    }

    /**
     * Payroll - Insentif
     */
    public function insentif(Request $request)
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik', '1800003');

        return response()->json([
            'success' => true,
            'message' => 'Data insentif berhasil diambil',
            'data' => [
                'periode' => 'Juli 2026',
                'nik' => $nik,
                'nama' => 'Nur Hidayah',
                'nominal_insentif' => 850000,
                'keterangan' => 'Insentif Kinerja Bulanan',
            ],
        ]);
    }

    /**
     * Absensi Kehadiran
     */
    public function absensi(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil diambil',
            'data' => [
                [
                    'tanggal' => date('Y-m-d'),
                    'jam_masuk' => '07:45:00',
                    'jam_keluar' => '16:30:00',
                    'status' => 'HADIR',
                    'keterangan' => 'Tepat Waktu',
                ],
                [
                    'tanggal' => date('Y-m-d', strtotime('-1 day')),
                    'jam_masuk' => '07:50:00',
                    'jam_keluar' => '16:35:00',
                    'status' => 'HADIR',
                    'keterangan' => 'Tepat Waktu',
                ],
            ],
        ]);
    }

    /**
     * Clock-In Absensi Mobile
     */
    public function checkinAbsensi(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melakukan absensi masuk.',
            'data' => [
                'tanggal' => date('Y-m-d'),
                'jam_masuk' => date('H:i:s'),
                'status' => 'HADIR',
            ]
        ]);
    }

    /**
     * Data Sanksi Pegawai
     */
    public function sanksi(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data sanksi berhasil diambil',
            'data' => [],
        ]);
    }

    /**
     * Data Prestasi Pegawai
     */
    public function prestasi(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil diambil',
            'data' => [
                [
                    'tahun' => '2025',
                    'nama_prestasi' => 'Pegawai Teladan Semester 2',
                    'tingkat' => 'Perusahaan',
                ]
            ],
        ]);
    }

    /**
     * Data Pengajuan Cuti
     */
    public function getCuti(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan cuti berhasil diambil',
            'data' => [
                [
                    'id' => 1,
                    'jenis_cuti' => 'Cuti Tahunan',
                    'tgl_mulai' => '2026-05-10',
                    'tgl_selesai' => '2026-05-12',
                    'alasan' => 'Acara Keluarga',
                    'status' => 'APPROVED',
                ]
            ],
        ]);
    }

    /**
     * Submit Pengajuan Cuti
     */
    public function storeCuti(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'jenis_cuti' => 'required|string',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date',
            'alasan' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dikirim.',
        ]);
    }

    /**
     * Submit Pengajuan Lembur
     */
    public function storeLembur(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|string',
            'jam_selesai' => 'required|string',
            'kegiatan' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dikirim.',
        ]);
    }

    /**
     * Data & Submit Pengaduan
     */
    public function pengaduan(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data pengaduan berhasil diambil',
            'data' => [],
        ]);
    }

    public function storePengaduan(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'subjek' => 'required|string',
            'pesan' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaduan berhasil dikirim ke SDM.',
        ]);
    }
}
