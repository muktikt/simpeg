<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\PegawaiController;

class ApiPegawaiController extends Controller
{
    protected function getAllPegawai(): array
    {
        if (! session()->has('dummy_pegawai')) {
            app(PegawaiController::class)->index(request());
        }

        return session('dummy_pegawai', []);
    }

    protected function getAllUserAkses(): array
    {
        if (! session()->has('dummy_userakses')) {
            app(\App\Http\Controllers\UserAksesController::class)->index();
        }

        return session('dummy_userakses', []);
    }

    protected function getPegawaiByRequest(Request $request): array
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik');
        $allPegawai = $this->getAllPegawai();

        if ($nik) {
            $found = collect($allPegawai)->firstWhere('nik', $nik);
            if ($found) {
                return $found;
            }
        }

        $allUserAkses = $this->getAllUserAkses();
        if ($nik) {
            $ua = collect($allUserAkses)->firstWhere('username', $nik);
            if ($ua) {
                return [
                    'nik' => $nik,
                    'nama' => $ua['nama'],
                    'jabatan' => 'Staf SIMPEG',
                    'unit_kerja' => 'PDAM Tirta Darma Ayu',
                ];
            }
        }

        return $allPegawai[0] ?? [
            'nik' => '1800001',
            'nama' => 'Dewi Anggraini',
            'jabatan' => 'Staf Keuangan',
            'unit_kerja' => 'Divisi Keuangan',
        ];
    }

    /**
     * Auth - Login (Dapat dilakukan oleh SEMUA Pegawai untuk Mobile App)
     */
    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $nik = trim($request->nik);
        $allPegawai = $this->getAllPegawai();
        $allUserAkses = $this->getAllUserAkses();

        $userAkses = collect($allUserAkses)->firstWhere('username', $nik);
        $pegawai = collect($allPegawai)->firstWhere('nik', $nik);

        $user = null;
        if ($userAkses || $pegawai) {
            $expectedPass = $userAkses['password'] ?? 'password';
            $user = [
                'nik' => $nik,
                'password' => $expectedPass,
                'nama_peg' => $pegawai['nama'] ?? ($userAkses['nama'] ?? 'Pegawai'),
                'jabatan' => $pegawai['jabatan'] ?? 'Pegawai',
                'userlevel' => (string) ($userAkses['userlevel'] ?? '5'), // Pegawai (default)
            ];
        } elseif (isset($this->dummyUsers[$nik])) {
            $user = $this->dummyUsers[$nik];
        }

        if (! $user || $request->password !== $user['password']) {
            return response()->json([
                'success' => false,
                'message' => 'NIK atau kata sandi salah.',
            ], 401);
        }

        $roleKode = 'PEGAWAI';
        $jabatanLower = strtolower($user['jabatan']);
        if ($user['userlevel'] === '1' || str_contains($jabatanLower, 'sdm')) {
            $roleKode = 'SDM';
        } elseif ($user['userlevel'] === '7' || str_contains($jabatanLower, 'direktur') || str_contains($jabatanLower, 'direksi')) {
            $roleKode = 'DIRUT';
        } elseif (str_contains($jabatanLower, 'kadiv') || str_contains($jabatanLower, 'kepala divisi')) {
            $roleKode = 'KADIV';
        } elseif (str_contains($jabatanLower, 'kspi')) {
            $roleKode = 'KSPI';
        } elseif (str_contains($jabatanLower, 'tpdpk')) {
            $roleKode = 'TPDPK';
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
                    'role' => $roleKode,
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
        $pegawai = $this->getPegawaiByRequest($request);

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
        $pegawai = $this->getPegawaiByRequest($request);

        return response()->json([
            'success' => true,
            'message' => 'Data slip gaji berhasil diambil',
            'data' => [
                'periode' => 'Agustus 2026',
                'nik' => $pegawai['nik'],
                'nama' => $pegawai['nama'],
                'jabatan' => $pegawai['jabatan'],
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
        $pegawai = $this->getPegawaiByRequest($request);

        return response()->json([
            'success' => true,
            'message' => 'Data slip THR berhasil diambil',
            'data' => [
                'tahun' => '2026',
                'nik' => $pegawai['nik'],
                'nama' => $pegawai['nama'],
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
        $pegawai = $this->getPegawaiByRequest($request);

        return response()->json([
            'success' => true,
            'message' => 'Data Gaji 13 berhasil diambil',
            'data' => [
                'tahun' => '2026',
                'nik' => $pegawai['nik'],
                'nama' => $pegawai['nama'],
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
        $pegawai = $this->getPegawaiByRequest($request);

        return response()->json([
            'success' => true,
            'message' => 'Data insentif berhasil diambil',
            'data' => [
                'periode' => 'Juli 2026',
                'nik' => $pegawai['nik'],
                'nama' => $pegawai['nama'],
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
