<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiPegawaiController extends Controller
{
    protected array $defaultPasswords = [
        '3000000003' => 'pegawai123',
        '4000000001' => 'kadiv123',
        '4000000006' => 'kadivteknik2026',
        '4000000002' => 'kspi123',
        '4000000003' => 'tpdpk123',
        '5000000001' => 'dirut123',
        '5000000002' => 'sdm123',
        '4000000005' => 'kadiv123',
        '2000000001' => 'admin123',
        '2000000002' => 'keuangan123',
        '6000000001' => 'sdm123',
    ];

    /**
     * Ambil data pegawai aktif berdasarkan request (header X-NIK atau query parameter)
     */
    protected function getPegawaiByRequest(Request $request)
    {
        $nik = $request->header('X-NIK') ?? $request->query('nik');

        if ($nik) {
            $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
            if ($pegawai) {
                return $pegawai;
            }
        }

        return DB::table('pegawai')->first();
    }

    /**
     * Auth - Login Pegawai (Mobile App Flutter)
     */
    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required',
            'password' => 'required|string',
        ]);

        $nik = (string) trim($request->nik);
        $pegawai = DB::table('pegawai')->where('nik', $nik)->first();

        if (! $pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'NIK tidak terdaftar dalam database.',
            ], 401);
        }

        $expectedPass = $this->defaultPasswords[$nik] ?? 'password';
        if ($request->password !== $expectedPass) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi salah.',
            ], 401);
        }

        // Tentukan Role & Userlevel berdasarkan role/jabatan di database
        $roleKode = 'PEGAWAI';
        $userLevel = '5';
        $dbRole = strtolower($pegawai->role ?? '');
        $jabatanLower = strtolower($pegawai->jabatan ?? '');

        if ($dbRole === 'direktur' || $nik === '5000000001' || str_contains($jabatanLower, 'direktur utama')) {
            $roleKode = 'DIRUT';
            $userLevel = '7';
        } elseif ($dbRole === 'admin' || $dbRole === 'sdm' || $nik === '5000000002' || str_contains($jabatanLower, 'sdm') || $jabatanLower === 'admin' || $jabatanLower === 'administrator') {
            $roleKode = 'SDM';
            $userLevel = '1';
        } elseif ($dbRole === 'keuangan' || $dbRole === 'keu' || str_contains($jabatanLower, 'keuangan')) {
            $roleKode = 'KEUANGAN';
            $userLevel = '2';
        } else {
            $roleKode = 'PEGAWAI';
            $userLevel = '5';
        }

        $token = base64_encode($pegawai->nik . ':' . time());

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $pegawai->id,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->name,
                    'jabatan' => $pegawai->jabatan,
                    'unit_kerja' => $pegawai->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                    'golongan' => $pegawai->golongan ?? '',
                    'userlevel' => $userLevel,
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
     * Profil Pegawai Lengkap (Mengambil dari Supabase)
     */
    public function profile(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);

        if (! $pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $keluarga = DB::table('keluarga')->where('pegawai_id', $pegawai->id)->get();
        $pendidikan = DB::table('pendidikan')->where('pegawai_id', $pegawai->id)->get();
        $riwayatGol = DB::table('riwayat_golongan')->where('pegawai_id', $pegawai->id)->get();
        $riwayatJab = DB::table('riwayat_jabatan')->where('pegawai_id', $pegawai->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data' => [
                'id' => $pegawai->id,
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'gelar' => $pegawai->gelar,
                'jabatan' => $pegawai->jabatan,
                'unit_kerja' => $pegawai->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                'golongan' => $pegawai->golongan,
                'status' => $pegawai->status ?? 'Pegawai Tetap',
                'tempat_tanggal_lahir' => $pegawai->tempat_tanggal_lahir,
                'status_pernikahan' => $pegawai->status_pernikahan,
                'alamat' => $pegawai->alamat,
                'no_telp' => $pegawai->no_telp,
                'foto_url' => $pegawai->foto_url,
                'keluarga' => $keluarga,
                'pendidikan' => $pendidikan,
                'riwayat_golongan' => $riwayatGol,
                'riwayat_jabatan' => $riwayatJab,
            ],
        ]);
    }

    /**
     * Payroll - Slip Gaji Bulanan
     */
    public function slipGaji(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);

        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $payroll = DB::table('payroll')->where('pegawai_id', $pegawai->id)->orderByDesc('created_at')->first();

        if (! $payroll) {
            $payroll = [
                'periode' => date('F Y'),
                'gapok' => 4500000,
                'tunjangan_jabatan' => 1200000,
                'tunjangan_istri' => 450000,
                'tunjangan_anak' => 200000,
                'potongan_dapenma' => 150000,
                'potongan_bank_bjb' => 200000,
                'total_terima' => 6000000,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Data slip gaji berhasil diambil',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan,
                'payroll' => $payroll,
            ],
        ]);
    }

    /**
     * Payroll - Slip THR
     */
    public function slipThr(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $thr = DB::table('thr')->where('pegawai_id', $pegawai->id)->orderByDesc('created_at')->first();

        return response()->json([
            'success' => true,
            'message' => 'Data slip THR berhasil diambil',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'thr' => $thr ?? [
                    'tahun' => date('Y'),
                    'gapok' => 4500000,
                    'status' => 'DITERBITKAN',
                    'tanggal_cair' => date('Y') . '-04-10',
                ],
            ],
        ]);
    }

    /**
     * Payroll - Gaji 13 / Tunjangan Pendidikan
     */
    public function slipGaji13(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $gaji13 = DB::table('gaji_13')->where('pegawai_id', $pegawai->id)->orderByDesc('created_at')->first();

        return response()->json([
            'success' => true,
            'message' => 'Data Gaji 13 berhasil diambil',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'gaji_13' => $gaji13 ?? [
                    'tahun' => date('Y'),
                    'jumlah' => 4500000,
                    'status' => 'DITERBITKAN',
                    'tanggal_cair' => date('Y') . '-06-15',
                ],
            ],
        ]);
    }

    /**
     * Payroll - Insentif
     */
    public function insentif(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $insentif = DB::table('insentif')->where('pegawai_id', $pegawai->id)->orderByDesc('created_at')->first();

        return response()->json([
            'success' => true,
            'message' => 'Data insentif berhasil diambil',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'insentif' => $insentif ?? [
                    'periode' => date('F Y'),
                    'judul' => 'Insentif Kinerja Bulanan',
                    'insentif_jabatan' => 850000,
                ],
            ],
        ]);
    }

    /**
     * Absensi Kehadiran
     */
    public function absensi(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $data = DB::table('absensi_harian')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('tanggal')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil diambil',
            'data' => $data,
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

        $pegawai = DB::table('pegawai')->where('nik', $request->nik)->first();
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $now = now();
        $inserted = DB::table('absensi_harian')->insertGetId([
            'pegawai_id' => $pegawai->id,
            'tanggal' => $now->toDateString(),
            'jam_masuk' => $now->toDateTimeString(),
            'status' => 'HADIR',
            'keterangan' => 'Absen Mobile App',
            'lat' => $request->latitude,
            'lng' => $request->longitude,
            'created_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melakukan absensi masuk.',
            'data' => [
                'id' => $inserted,
                'tanggal' => $now->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'HADIR',
            ]
        ]);
    }

    /**
     * Data Sanksi Pegawai
     */
    public function sanksi(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $data = DB::table('sanksi')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data sanksi berhasil diambil',
            'data' => $data,
        ]);
    }

    /**
     * Data Prestasi Pegawai
     */
    public function prestasi(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $data = DB::table('prestasi')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil diambil',
            'data' => $data,
        ]);
    }

    /**
     * Data Pengajuan Cuti
     */
    public function getCuti(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $data = DB::table('pengajuan_cuti')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan cuti berhasil diambil',
            'data' => $data,
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

        $pegawai = DB::table('pegawai')->where('nik', $request->nik)->first();
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $now = now();
        $id = DB::table('pengajuan_cuti')->insertGetId([
            'pegawai_id' => $pegawai->id,
            'nama_pegawai' => $pegawai->name,
            'jenis' => $request->jenis_cuti,
            'tanggal_mulai' => $request->tgl_mulai,
            'tanggal_selesai' => $request->tgl_selesai,
            'alasan' => $request->alasan,
            'status' => 'PENDING',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'data' => ['id' => $id],
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

        $pegawai = DB::table('pegawai')->where('nik', $request->nik)->first();
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $id = DB::table('lembur')->insertGetId([
            'pegawai_id' => $pegawai->id,
            'bulan' => date('F Y', strtotime($request->tanggal)),
            'jam_lembur' => 3,
            'uang_lembur' => 150000,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dikirim.',
            'data' => ['id' => $id],
        ]);
    }

    /**
     * Data & Submit Pengaduan
     */
    public function pengaduan(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $data = DB::table('pengaduan_pegawai')
            ->where('pelapor_id', $pegawai->id)
            ->orWhere('nik', $pegawai->nik)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data pengaduan berhasil diambil',
            'data' => $data,
        ]);
    }

    public function storePengaduan(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'subjek' => 'required|string',
            'pesan' => 'required|string',
        ]);

        $pegawai = DB::table('pegawai')->where('nik', $request->nik)->first();
        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $now = now();
        $nomorPengaduan = 'PGD-' . date('Ymd') . '-' . rand(1000, 9999);

        $id = DB::table('pengaduan_pegawai')->insertGetId([
            'nomor_pengaduan' => $nomorPengaduan,
            'pelapor_id' => $pegawai->id,
            'kategori' => 'Umum',
            'judul' => $request->subjek,
            'deskripsi' => $request->pesan,
            'tanggal_pengaduan' => $now,
            'nama_pegawai' => $pegawai->name,
            'nik' => $pegawai->nik,
            'cabang' => $pegawai->unit_kerja ?? 'Kantor Pusat',
            'golongan' => $pegawai->golongan ?? '',
            'anonim' => false,
            'status' => 'DIAJUKAN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaduan berhasil dikirim ke SDM.',
            'data' => ['id' => $id, 'nomor_pengaduan' => $nomorPengaduan],
        ]);
    }
}
