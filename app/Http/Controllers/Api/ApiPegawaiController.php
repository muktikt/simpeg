<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AuditLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ApiPegawaiController extends Controller
{
    /**
     * Ambil data pegawai aktif berdasarkan identitas terautentikasi (JWT Token).
     * Mencegah IDOR: Pegawai biasa HANYA dapat mengakses datanya sendiri.
     * Pejabat berwenang (SDM, Keuangan, Direksi, KSPI, Kadiv) dapat melihat pegawai yang diminta.
     */
    protected function getPegawaiByRequest(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return null;
        }

        $requestedNik = $request->query('nik') ?? $request->header('X-NIK');
        $requestedId = $request->query('pegawai_id') ?? $request->query('id') ?? $request->header('X-Pegawai-Id');

        // Jika tidak meminta NIK/ID spesifik atau target sama dengan akun sendiri
        $isSelfNik = empty($requestedNik) || (string) $requestedNik === (string) $authUser->nik;
        $isSelfId = empty($requestedId) || (string) $requestedId === (string) $authUser->id;

        if ($isSelfNik && $isSelfId) {
            return $authUser;
        }

        // Cek hak akses role untuk melihat pegawai lain
        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $isPrivileged = in_array($userLevel, ['1', '2', '7']) || in_array($dbRole, ['sdm', 'admin', 'keuangan', 'direktur', 'kspi', 'kadiv', 'tpdpk']);

        if (! $isPrivileged) {
            // Pegawai biasa dilarang intip data pegawai lain (Return false untuk 403 Forbidden)
            return false;
        }

        // Pejabat berwenang: ambil target yang diminta
        if ($requestedNik && (string) $requestedNik !== (string) $authUser->nik) {
            $target = Cache::remember("pegawai_nik_{$requestedNik}", 180, function () use ($requestedNik) {
                return DB::table('pegawai')->where('nik', $requestedNik)->first();
            });
            if ($target) {
                return $target;
            }
        }

        if ($requestedId && (string) $requestedId !== (string) $authUser->id) {
            $target = Cache::remember("pegawai_id_{$requestedId}", 180, function () use ($requestedId) {
                return DB::table('pegawai')->where('id', $requestedId)->first();
            });
            if ($target) {
                return $target;
            }
        }

        return $authUser;
    }

    /**
     * Helper otorisasi: cegah IDOR pada pemanggilan getPegawaiByRequest
     */
    protected function checkPegawaiAccess($pegawai)
    {
        if ($pegawai === false) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang mengakses data pegawai lain.',
            ], 403);
        }

        if (! $pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        return null;
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

        // Verifikasi Password terhadap kolom password di tabel pegawai lokal PostgreSQL
        $passwordValid = false;
        if (! empty($pegawai->password)) {
            if (password_verify($request->password, $pegawai->password)) {
                $passwordValid = true;
            }
        }
        // Fallback pra-produksi: HANYA aktif pada environment local development
        if (! $passwordValid && app()->environment('local')) {
            if ($request->password === (string) $pegawai->nik || $request->password === 'password') {
                $passwordValid = true;
                try {
                    DB::table('pegawai')->where('id', $pegawai->id)->update([
                        'password' => password_hash($request->password, PASSWORD_BCRYPT),
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        if (! $passwordValid) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi salah atau akun belum dikonfigurasi password.',
            ], 401);
        }

        // Tentukan Role & Userlevel berdasarkan role/jabatan di database
        $roleKode = 'PEGAWAI';
        $userLevel = '5';
        $dbRole = strtolower($pegawai->role ?? '');
        $jabatanLower = strtolower($pegawai->jabatan ?? '');
        $divisiKadiv = $pegawai->divisi_kadiv ?? null;

        if ($dbRole === 'direktur' || str_contains($jabatanLower, 'direktur utama')) {
            $roleKode = 'DIRUT';
            $userLevel = '7';
        } elseif ($dbRole === 'admin' || $dbRole === 'sdm' || str_contains($jabatanLower, 'sdm') || $jabatanLower === 'admin' || $jabatanLower === 'administrator') {
            $roleKode = 'SDM';
            $userLevel = '1';
        } elseif ($dbRole === 'keuangan' || $dbRole === 'keu' || str_contains($jabatanLower, 'keuangan')) {
            $roleKode = 'KEUANGAN';
            $userLevel = '2';
        } elseif ($nik === '1711161' || ($dbRole === 'kspi' && $nik === '1711161')) {
            $roleKode = 'KSPI';
            $userLevel = '5';
        } elseif ($nik === '1711571' || $nik === '1711251' || $dbRole === 'kadiv' || $dbRole === 'kadivkategori') {
            $roleKode = 'KADIV';
            $userLevel = '5';
            if ($nik === '1711571') {
                $divisiKadiv = 'administrasi';
            } elseif ($nik === '1711251') {
                $divisiKadiv = 'teknik';
            }
        } elseif ($dbRole === 'tpdpk') {
            $roleKode = 'TPDPK';
            $userLevel = '5';
        } else {
            $roleKode = 'PEGAWAI';
            $userLevel = '5';
        }

        // Generate token JWT HS256 resmi yang ditandatangani secara kriptografis
        $secret = config('services.simpeg.jwt_secret') ?: env('JWT_SECRET') ?: config('app.key');
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss' => 'simpeg-api',
            'sub' => (string) $pegawai->id,
            'nik' => (string) $pegawai->nik,
            'nama' => (string) $pegawai->name,
            'role' => $roleKode,
            'userlevel' => (string) $userLevel,
            'iat' => time(),
            'exp' => time() + (30 * 86400), // Masa berlaku 30 hari
        ]);

        $b64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $b64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', "$b64Header.$b64Payload", $secret, true);
        $b64Sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $token = "$b64Header.$b64Payload.$b64Sig";

        AuditLogController::record(
            'LOGIN',
            'Autentikasi (Mobile)',
            'Pengguna berhasil login melalui aplikasi Mobile SIMPEG',
            (string) $pegawai->nik,
            (string) $pegawai->name,
            $roleKode,
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $pegawai->id,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->name,
                    'name' => $pegawai->name,
                    'gelar' => $pegawai->gelar ?? '',
                    'jabatan' => $pegawai->jabatan ?? '-',
                    'unit_kerja' => $pegawai->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                    'unit_kerja_singkat' => $pegawai->unit_kerja_singkat ?? '',
                    'golongan' => $pegawai->golongan ?? '',
                    'status' => $pegawai->status ?? 'Pegawai Tetap',
                    'tempat_tanggal_lahir' => $pegawai->tempat_tanggal_lahir ?? '',
                    'status_pernikahan' => $pegawai->status_pernikahan ?? '',
                    'alamat' => $pegawai->alamat ?? '',
                    'no_telp' => $pegawai->no_telp ?? '',
                    'foto_url' => $pegawai->foto_url ?? null,
                    'email' => $pegawai->email ?? '',
                    'userlevel' => (string) $userLevel,
                    'role' => $roleKode,
                    'divisi_kadiv' => $divisiKadiv,
                    'must_change_password' => (bool) ($pegawai->must_change_password ?? false),
                ],
                'token' => $token,
            ]
        ]);
    }

    /**
     * Auth - Ganti Password (Wajib Terautentikasi)
     */
    public function gantiPassword(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $targetNik = (string) trim($request->input('nik', $authUser->nik));
        if ($targetNik !== (string) $authUser->nik) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya berhak mengganti password akun Anda sendiri.',
            ], 403);
        }

        $request->validate([
            'old_password' => 'nullable|string',
            'password_lama' => 'nullable|string',
            'new_password' => 'nullable|string|min:4',
            'password_baru' => 'nullable|string|min:4',
        ]);

        $oldPassword = $request->input('old_password') ?: $request->input('password_lama');
        $newPassword = $request->input('new_password') ?: $request->input('password_baru');

        if (empty($newPassword)) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru wajib diisi.',
            ], 422);
        }

        $pegawai = $authUser;

        // Verifikasi password lama jika disertakan
        if (! empty($oldPassword)) {
            $passwordValid = false;
            if (! empty($pegawai->password)) {
                if (password_verify($oldPassword, $pegawai->password)) {
                    $passwordValid = true;
                }
            }
            if (! $passwordValid && ($oldPassword === (string) $pegawai->nik || $oldPassword === 'password')) {
                $passwordValid = true;
            }

            if (! $passwordValid) {
                return response()->json(['success' => false, 'message' => 'Password lama salah.'], 400);
            }
        }

        // Update password baru langsung di tabel pegawai lokal
        $hashed = bcrypt($newPassword);
        try {
            DB::table('pegawai')->where('id', $pegawai->id)->update([
                'password' => $hashed,
                'must_change_password' => false,
            ]);

            AuditLogController::record(
                'UPDATE',
                'Keamanan (Mobile)',
                'Pegawai memperbarui kata sandi akun melalui aplikasi mobile',
                (string) $pegawai->nik,
                (string) ($pegawai->name ?? $pegawai->nama),
                $pegawai->role ?? 'Pegawai',
                $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
            );
        } catch (\Throwable $e) {
            Log::warning('ApiPegawaiController gantiPassword error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui dan tersimpan di database.',
        ]);
    }

    /**
     * List Semua Data Pegawai (butuh header X-API-KEY)
     * Dioptimalkan dengan Cache in-memory agar respon instan (<5ms)
     */
    public function listPegawai(Request $request)
    {
        $keyword = strtolower(trim((string) $request->query('search', '')));
        $perPage = min((int) $request->query('per_page', 50), 200) ?: 50;
        $page = max((int) $request->query('page', 1), 1);

        // Cache seluruh master data pegawai (554 records) selama 10 menit
        // Mengeliminasi latency round-trip 500ms+ ke database Supabase Cloud di Singapore
        $allPegawai = Cache::remember('pegawai_master_cache', 600, function () {
            return DB::table('pegawai')
                ->select(['id', 'nik', 'name', 'gelar', 'jabatan', 'unit_kerja', 'unit_kerja_singkat', 'golongan', 'status', 'role', 'branch_id'])
                ->orderBy('name')
                ->get();
        });

        if ($keyword !== '') {
            $filtered = $allPegawai->filter(function ($p) use ($keyword) {
                return (isset($p->name) && stripos((string) $p->name, $keyword) !== false)
                    || (isset($p->nik) && stripos((string) $p->nik, $keyword) !== false)
                    || (isset($p->unit_kerja) && stripos((string) $p->unit_kerja, $keyword) !== false);
            })->values();
        } else {
            $filtered = $allPegawai;
        }

        $total = $filtered->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $offset = ($page - 1) * $perPage;
        $items = $filtered->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diambil',
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    /**
     * Profil Pegawai Lengkap (Mengambil dari Supabase)
     */
    public function profile(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
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
                'branch_id' => $pegawai->branch_id ?? null,
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
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $bulanReq = $request->filled('bulan') ? (int) $request->bulan : (int) now()->month;
        $tahunReq = $request->filled('tahun') ? (int) $request->tahun : (int) now()->year;

        $payroll = DB::table('payroll')
            ->where('pegawai_id', $pegawai->id)
            ->where('bulan', $bulanReq)
            ->where('tahun', $tahunReq)
            ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
            ->first();

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $namaBulan = $bulanNames[$bulanReq] ?? ('Bulan ' . $bulanReq);

        if (! $payroll) {
            $pendingPayroll = DB::table('payroll')
                ->where('pegawai_id', $pegawai->id)
                ->where('bulan', $bulanReq)
                ->where('tahun', $tahunReq)
                ->first();

            $msg = $pendingPayroll
                ? "Slip gaji periode $namaBulan $tahunReq masih dalam proses verifikasi & approval berjenjang."
                : "Belum ada slip gaji yang diterbitkan untuk periode $namaBulan $tahunReq.";

            return response()->json([
                'success' => false,
                'message' => $msg,
                'data' => [
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->name,
                    'jabatan' => $pegawai->jabatan,
                    'bulan' => $bulanReq,
                    'tahun' => $tahunReq,
                    'status_proses' => $pendingPayroll ? ($pendingPayroll->status ?? 'draft') : null,
                    'payroll' => null,
                ],
            ]);
        }

        if ($payroll) {
            $prefix = sprintf('%04d-%02d', (int) ($payroll->tahun ?? now()->year), (int) ($payroll->bulan ?? now()->month));
            $monthPadded = str_pad((int) ($payroll->bulan ?? now()->month), 2, '0', STR_PAD_LEFT);
            try {
                $pRow = DB::table('prestasi')
                    ->where('pegawai_id', $pegawai->id)
                    ->where(function ($q) use ($prefix, $payroll, $monthPadded) {
                        $q->where('tanggal', 'like', "$prefix%")
                          ->orWhere('tanggal', 'like', "%{$payroll->tahun}-$monthPadded-%")
                          ->orWhere('tanggal', 'like', "%{$payroll->tahun}-{$payroll->bulan}-%");
                    })
                    ->orderByDesc('id')
                    ->first();
                if ($pRow) {
                    $meta = json_decode($pRow->keterangan ?? '{}', true) ?: [];
                    $jam = (float) ($meta['jam_lembur'] ?? ($pRow->jam_lembur ?? 0));
                    $nominal = (int) ($meta['nominal_lembur'] ?? round($jam * \App\Http\Controllers\PrestasiController::RATE_LEMBUR_PER_JAM));
                    $selisih = $nominal - (int) ($payroll->lembur ?? 0);
                    $payroll->lembur = $nominal;
                    $payroll->total_pendapatan = (int) ($payroll->total_pendapatan ?? 0) + $selisih;
                    $payroll->gaji_bersih = (int) ($payroll->gaji_bersih ?? 0) + $selisih;
                }
            } catch (\Throwable $e) {}
        }

        $payrolls = DB::table('payroll')
            ->where('pegawai_id', $pegawai->id)
            ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data slip gaji berhasil diambil',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan,
                'payroll' => $payroll,
                'history' => $payrolls,
            ],
        ]);
    }

    /**
     * Payroll - Slip THR
     */
    public function slipThr(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $tahunReq = $request->filled('tahun') ? (int) $request->tahun : (int) now()->year;

        $thr = DB::table('thr')
            ->where('pegawai_id', $pegawai->id)
            ->where('tahun', $tahunReq)
            ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
            ->first();

        if (! $thr) {
            return response()->json([
                'success' => false,
                'message' => "Belum ada slip THR yang diterbitkan untuk tahun $tahunReq.",
                'data' => [
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->name,
                    'tahun' => $tahunReq,
                    'thr' => null,
                ],
            ]);
        }

        $thrs = DB::table('thr')
            ->where('pegawai_id', $pegawai->id)
            ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
            ->orderByDesc('tahun')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data slip THR berhasil diambil',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'thr' => $thr,
                'history' => $thrs,
            ],
        ]);
    }

    /**
     * Payroll - Slip Tunjangan Pendidikan (Regulasi 2026 - Format Gambar 1)
     */
    public function tunjanganPendidikan(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $items = DB::table('tunjangan_pendidikan')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('id')
            ->get();

        $formatted = $items->map(function ($it) use ($pegawai) {
            $gapok = (float) ($it->gapok ?? 0);
            $tIstri = (float) ($it->tunjangan_istri ?? 0);
            $tAnak = (float) ($it->tunjangan_anak ?? 0);
            $jmlPendapatan = $gapok + $tIstri + $tAnak;

            $potKoperasi = (float) ($it->potongan_koperasi ?? 0);
            $potKas = (float) ($it->potongan_kas ?? 0);
            $jmlPotongan = $potKoperasi + $potKas;
            $diterima = max(0, $jmlPendapatan - $jmlPotongan);

            return [
                'id' => $it->id,
                'pegawai_id' => $pegawai->id,
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan ?? '-',
                'unit_kerja' => $pegawai->unit_kerja ?? '-',
                'golongan' => $pegawai->golongan_detail ?? ($pegawai->golongan ?? '-'),
                'bulan_label' => $it->bulan_label ?? ('JULI ' . date('Y')),
                'gapok' => $gapok,
                'tunjangan_istri' => $tIstri,
                'tunjangan_anak' => $tAnak,
                'jumlah_pendapatan' => $jmlPendapatan,
                'potongan_koperasi' => $potKoperasi,
                'potongan_kas' => $potKas,
                'jumlah_potongan' => $jmlPotongan,
                'diterima' => $diterima,
                'created_at' => $it->created_at,
            ];
        });

        // Also check gaji_13 for historical records
        $gaji13List = DB::table('gaji_13')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('tahun')
            ->get();

        return response()->json([
            'success' => true,
            'message' => ($formatted->isNotEmpty() || $gaji13List->isNotEmpty()) ? 'Data Tunjangan Pendidikan berhasil diambil' : 'Belum ada data Tunjangan Pendidikan.',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan ?? '-',
                'unit_kerja' => $pegawai->unit_kerja ?? '-',
                'golongan' => $pegawai->golongan_detail ?? ($pegawai->golongan ?? '-'),
                'tunjangan_pendidikan' => $formatted,
                'history_gaji_13' => $gaji13List,
            ],
        ]);
    }

    /**
     * Payroll - Gaji 13 / Tunjangan Pendidikan
     */
    public function slipGaji13(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $tahunReq = $request->filled('tahun') ? (int) $request->tahun : (int) now()->year;

        $gaji13 = DB::table('gaji_13')
            ->where('pegawai_id', $pegawai->id)
            ->where('tahun', $tahunReq)
            ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
            ->first();

        $pemecahan = null;
        if ($gaji13) {
            $pemecahan = \App\Http\Controllers\GajiTigabelasController::hitungPemecahanGaji13((array) $gaji13);
        }

        $latestTP = DB::table('tunjangan_pendidikan')->where('pegawai_id', $pegawai->id)->orderByDesc('id')->first();

        return response()->json([
            'success' => true,
            'message' => ($gaji13 || $latestTP) ? 'Data Gaji 13 / Tunjangan Pendidikan berhasil diambil' : 'Belum ada data Gaji 13 yang diterbitkan.',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan ?? '-',
                'unit_kerja' => $pegawai->unit_kerja ?? '-',
                'golongan' => $pegawai->golongan_detail ?? ($pegawai->golongan ?? '-'),
                'gaji_13' => $gaji13,
                'pemecahan' => $pemecahan,
                'tunjangan_pendidikan_terbaru' => $latestTP,
            ],
        ]);
    }

    /**
     * Payroll - Insentif (Mendukung Insentif Pendidikan dan Insentif Bulanan)
     */
    public function insentif(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $sumber = $request->get('sumber', 'all');
        $items = collect();

        // 1. Jika sumber khusus gaji13
        if ($sumber === 'gaji13') {
            try {
                $fromGaji13 = DB::table('gaji_13')
                    ->where('pegawai_id', $pegawai->id)
                    ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
                    ->orderByDesc('tahun')
                    ->get();
                if ($fromGaji13->isNotEmpty()) {
                    $items = $items->merge($fromGaji13);
                }
            } catch (\Throwable $e) {}
        } else {
            // 2. Ambil dari tabel insentif (Insentif Pendidikan 2026 & Insentif Mandiri dari SDM)
            try {
                $fromInsentif = DB::table('insentif')
                    ->where('pegawai_id', $pegawai->id)
                    ->orderByDesc('created_at')
                    ->get();
                if ($fromInsentif->isNotEmpty()) {
                    $items = $items->merge($fromInsentif);
                }
            } catch (\Throwable $e) {}

            // 3. Ambil dari tabel payroll yang sudah terbit / final (Insentif Bulanan)
            if ($sumber !== 'pendidikan_only') {
                try {
                    $fromPayroll = DB::table('payroll')
                        ->where('pegawai_id', $pegawai->id)
                        ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
                        ->orderByDesc('tahun')
                        ->orderByDesc('bulan')
                        ->get();
                    if ($fromPayroll->isNotEmpty()) {
                        $items = $items->merge($fromPayroll);
                    }
                } catch (\Throwable $e) {}
            }
        }

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $formattedList = $items->map(function ($r) use ($bulanNames, $pegawai) {
            $isGaji13 = isset($r->gaji13_diterima) || isset($r->pembagi_insentif) || (isset($r->kategori) && $r->kategori === 'gaji13');
            if ($isGaji13) {
                $pemecahan = \App\Http\Controllers\GajiTigabelasController::hitungPemecahanGaji13((array) $r);
                $ins = $pemecahan['insentif'];
                $tahun = (int) ($r->tahun ?? date('Y'));
                $periode = "Gaji 13 Tahun $tahun";

                return [
                    'id' => $r->id,
                    'pegawai_id' => $pegawai->id,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->name,
                    'jabatan' => $pegawai->jabatan,
                    'unit_kerja' => $pegawai->unit_kerja,
                    'golongan' => $pegawai->golongan,
                    'periode' => $periode,
                    'bulan' => 0,
                    'tahun' => $tahun,
                    'status' => 'Terbit & Final',
                    'disetujui_oleh' => $r->disetujui_oleh ?? 'Nurpan, S.E., M.Si.',
                    // Penerimaan
                    'insentif_jabatan' => $ins['insentif_jabatan'],
                    'insentif_prestasi' => $ins['insentif_prestasi'],
                    'insentif_transportasi' => $ins['insentif_transportasi'],
                    'insentif_pangan' => $ins['insentif_pangan'],
                    'insentif_bpjs_kesehatan' => $ins['insentif_bpjs_kesehatan'],
                    'insentif_perumahan' => $ins['insentif_perumahan'],
                    'insentif_bpjs_tenaga_kerja' => $ins['insentif_bpjs_tenaga_kerja'],
                    'insentif_perusahaan' => $ins['insentif_perusahaan'],
                    'lembur' => $ins['lembur'],
                    'insentif_pajak' => $ins['insentif_pajak'],
                    'insentif_air_minum' => $ins['insentif_air_minum'],
                    'insentif_komunikasi' => $ins['insentif_komunikasi'],
                    'total_insentif' => $ins['total_insentif'],
                    'insentif_bruto' => $ins['total_insentif'],
                    // Potongan Insentif
                    'potongan_sanksi_perusahaan' => $ins['potongan_sanksi_perusahaan'],
                    'potongan_trandist_pmi_lain' => $ins['potongan_trandist_pmi_lain'],
                    'potongan_dapenma' => $ins['potongan_dapenma'],
                    'potongan_bpjs_tenaga_kerja' => $ins['potongan_bpjs_tenaga_kerja'],
                    'potongan_perumahan' => $ins['potongan_perumahan'],
                    'potongan_tunjangan_perusahaan' => $ins['potongan_tunjangan_perusahaan'],
                    'potongan_korpri' => $ins['potongan_korpri'],
                    'potongan_pajak' => $ins['potongan_pajak'],
                    'potongan_bpjs_kesehatan' => $ins['potongan_bpjs_kesehatan'],
                    // Potongan Non-Insentif
                    'potongan_koperasi' => $ins['potongan_koperasi'],
                    'potongan_darma_wanita' => $ins['potongan_darma_wanita'],
                    'potongan_rekening_air_minum' => $ins['potongan_rekening_air_minum'],
                    'potongan_kas' => $ins['potongan_kas'],
                    'potongan_bank_bjb' => $ins['potongan_bank_bjb'],
                    'potongan_bank_bjbs' => $ins['potongan_bank_bjbs'],
                    'potongan_bank_btn' => $ins['potongan_bank_btn'],
                    'potongan_bank_bpr' => $ins['potongan_bank_bpr'],
                    'potongan_asuransi' => $ins['potongan_asuransi'],
                    'potongan_zakat_profesi' => $ins['potongan_zakat_profesi'],
                    // Totals
                    'total_potongan_insentif' => $ins['total_potongan_insentif'],
                    'total_potongan_non_insentif' => $ins['total_potongan_non_insentif'],
                    'total_potongan' => $ins['total_potongan'],
                    'insentif_diterima' => $ins['insentif_diterima'],
                    'gaji_bersih' => $ins['insentif_diterima'],
                ];
            }

            $bulan = (int) ($r->bulan ?? (isset($r->created_at) ? date('n', strtotime($r->created_at)) : 1));
            $tahun = (int) ($r->tahun ?? (isset($r->created_at) ? date('Y', strtotime($r->created_at)) : (int) date('Y')));
            $bName = $bulanNames[$bulan] ?? ('Bulan ' . $bulan);
            $periode = !empty($r->periode) ? $r->periode : "$bName $tahun";

            // Penerimaan Insentif (Non-Gapok PDAM)
            $insentifJabatan = (int) ($r->tunjangan_jabatan ?? ($r->insentif_jabatan ?? 0));
            $insentifPrestasi = (int) ($r->tunjangan_prestasi ?? ($r->insentif_prestasi ?? 0));
            $insentifTransportasi = (int) ($r->tunjangan_transportasi ?? ($r->insentif_transportasi ?? 0));
            $insentifPangan = (int) ($r->tunjangan_pangan ?? ($r->insentif_pangan ?? 0));
            $insentifBpjsKes = (int) ($r->tunjangan_bpjs_kesehatan ?? ($r->tunjangan_bpjskes ?? ($r->insentif_bpjs_kesehatan ?? 0)));
            $insentifPerumahan = (int) ($r->tunjangan_perumahan ?? ($r->insentif_perumahan ?? 0));
            $insentifBpjstk = (int) ($r->tunjangan_bpjs_tenaga_kerja ?? ($r->tunjangan_bpjstk ?? ($r->insentif_bpjs_tenaga_kerja ?? 0)));
            $insentifPerusahaan = (int) ($r->tunjangan_perusahaan ?? ($r->insentif_perusahaan ?? 0));
            $lembur = (int) ($r->lembur ?? 0);
            $insentifPajak = (int) ($r->tunjangan_pajak ?? ($r->insentif_pajak ?? 0));
            $insentifAirMinum = (int) ($r->tunjangan_air_minum ?? ($r->tunjangan_airminum ?? ($r->insentif_air_minum ?? 0)));
            $insentifKomunikasi = (int) ($r->tunjangan_komunikasi ?? ($r->insentif_komunikasi ?? 0));

            $totalInsentif = $insentifJabatan + $insentifPrestasi + $insentifTransportasi + $insentifPangan +
                $insentifBpjsKes + $insentifPerumahan + $insentifBpjstk + $insentifPerusahaan +
                $lembur + $insentifPajak + $insentifAirMinum + $insentifKomunikasi;

            // Potongan Insentif
            $potSanksi = (int) ($r->potongan_sanksi_perusahaan ?? ($r->potongan_sanksi ?? 0));
            $potPmi = (int) ($r->potongan_trandist_pmi_lain ?? ($r->potongan_pmi_lain ?? ($r->potongan_lain ?? 0)));
            $potDapenma = (int) ($r->potongan_dapenma ?? 0);
            $potBpjstk = (int) ($r->potongan_bpjs_tenaga_kerja ?? ($r->potongan_bpjstk ?? 0));
            $potPerumahan = (int) ($r->potongan_perumahan ?? 0);
            $potTperusahaan = (int) ($r->potongan_tunjangan_perusahaan ?? ($r->potongan_insentif_perusahaan ?? ($r->potongan_tperusahaan ?? 0)));
            $potKorpri = (int) ($r->potongan_korpri ?? 0);
            $potPajak = (int) ($r->potongan_pajak ?? 0);
            $potBpjskes = (int) ($r->potongan_bpjs_kesehatan ?? ($r->potongan_bpjskes ?? 0));

            $totalPotonganInsentif = $potSanksi + $potPmi + $potDapenma + $potBpjstk + $potPerumahan +
                $potTperusahaan + $potKorpri + $potPajak + $potBpjskes;

            // Potongan Non-Insentif
            $potKoperasi = (int) ($r->potongan_koperasi ?? 0);
            $potDarmawanita = (int) ($r->potongan_darma_wanita ?? ($r->potongan_darmawanita ?? 0));
            $potAirMinum = (int) ($r->potongan_rekening_air_minum ?? ($r->potongan_ledeng ?? 0));
            $potKas = (int) ($r->potongan_kas ?? 0);
            $potBjb = (int) ($r->potongan_bank_bjb ?? ($r->potongan_bjb ?? 0));
            $potBjbs = (int) ($r->potongan_bank_bjbs ?? ($r->potongan_bjbs ?? 0));
            $potBtn = (int) ($r->potongan_bank_btn ?? ($r->potongan_btn ?? 0));
            $potBpr = (int) ($r->potongan_bank_bpr ?? ($r->potongan_bpr ?? 0));
            $potAsuransi = (int) ($r->potongan_asuransi ?? 0);
            $potZakat = (int) ($r->potongan_zakat_profesi ?? ($r->potongan_zakat ?? 0));

            $totalPotonganNonInsentif = $potKoperasi + $potDarmawanita + $potAirMinum + $potKas +
                $potBjb + $potBjbs + $potBtn + $potBpr + $potAsuransi + $potZakat;

            $totalPotongan = $totalPotonganInsentif + $totalPotonganNonInsentif;
            $insentifDiterima = max(0, $totalInsentif - $totalPotongan);

            return [
                'id' => $r->id,
                'pegawai_id' => $pegawai->id,
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan,
                'unit_kerja' => $pegawai->unit_kerja,
                'golongan' => $pegawai->golongan,
                'periode' => $periode,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'status' => 'Terbit & Final',
                'disetujui_oleh' => $r->disetujui_oleh ?? 'Nurpan, S.E., M.Si.',
                // Penerimaan
                'insentif_jabatan' => $insentifJabatan,
                'insentif_prestasi' => $insentifPrestasi,
                'insentif_transportasi' => $insentifTransportasi,
                'insentif_pangan' => $insentifPangan,
                'insentif_bpjs_kesehatan' => $insentifBpjsKes,
                'insentif_perumahan' => $insentifPerumahan,
                'insentif_bpjs_tenaga_kerja' => $insentifBpjstk,
                'insentif_perusahaan' => $insentifPerusahaan,
                'lembur' => $lembur,
                'insentif_pajak' => $insentifPajak,
                'insentif_air_minum' => $insentifAirMinum,
                'insentif_komunikasi' => $insentifKomunikasi,
                'total_insentif' => $totalInsentif,
                'insentif_bruto' => $totalInsentif,
                // Potongan Insentif
                'potongan_sanksi_perusahaan' => $potSanksi,
                'potongan_trandist_pmi_lain' => $potPmi,
                'potongan_dapenma' => $potDapenma,
                'potongan_bpjs_tenaga_kerja' => $potBpjstk,
                'potongan_perumahan' => $potPerumahan,
                'potongan_tunjangan_perusahaan' => $potTperusahaan,
                'potongan_korpri' => $potKorpri,
                'potongan_pajak' => $potPajak,
                'potongan_bpjs_kesehatan' => $potBpjskes,
                // Potongan Non-Insentif
                'potongan_koperasi' => $potKoperasi,
                'potongan_darma_wanita' => $potDarmawanita,
                'potongan_rekening_air_minum' => $potAirMinum,
                'potongan_kas' => $potKas,
                'potongan_bank_bjb' => $potBjb,
                'potongan_bank_bjbs' => $potBjbs,
                'potongan_bank_btn' => $potBtn,
                'potongan_bank_bpr' => $potBpr,
                'potongan_asuransi' => $potAsuransi,
                'potongan_zakat_profesi' => $potZakat,
                // Totals
                'total_potongan_insentif' => $totalPotonganInsentif,
                'total_potongan_non_insentif' => $totalPotonganNonInsentif,
                'total_potongan' => $totalPotongan,
                'insentif_diterima' => $insentifDiterima,
                'gaji_bersih' => $insentifDiterima,
            ];
        });

        $latestInsentif = $formattedList->first();

        return response()->json([
            'success' => true,
            'message' => $latestInsentif ? 'Data insentif berhasil diambil' : 'Belum ada data insentif.',
            'data' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'jabatan' => $pegawai->jabatan,
                'unit_kerja' => $pegawai->unit_kerja,
                'insentif' => $latestInsentif,
                'list' => $formattedList->values(),
            ],
        ]);
    }

    /**
     * Absensi Kehadiran
     */
    public function absensi(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $data = DB::table('absensi_harian')
            ->where('pegawai_id', $pegawai->id)
            ->orderByDesc('tanggal')
            ->limit(30)
            ->get();

        $now = now();
        $bulanLabel = $now->translatedFormat('F Y');
        $hadir = $data->where('status', 'HADIR')->count();
        $telat = $data->where('status', 'TELAT')->count();
        $izin = $data->whereIn('status', ['IZIN', 'SAKIT', 'CUTI'])->count();

        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil diambil',
            'summary' => [
                'bulan_label' => $bulanLabel,
                'hadir' => $hadir,
                'telat' => $telat,
                'izin' => $izin,
            ],
            'data' => $data,
        ]);
    }

    /**
     * Clock-In Absensi Mobile
     */
    public function checkinAbsensi(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $request->validate([
            'nik' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'foto_url' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $requestedNik = $request->input('nik');
        if (! empty($requestedNik) && (string) $requestedNik !== (string) $authUser->nik) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat melakukan absensi untuk diri Anda sendiri.',
            ], 403);
        }

        $pegawai = $authUser;

        $now = now();
        $status = $now->hour >= 8 ? 'telat' : 'hadir';
        $inserted = DB::table('absensi_harian')->insertGetId([
            'pegawai_id' => $pegawai->id,
            'tanggal' => $now->toDateString(),
            'jam_masuk' => $now->toDateTimeString(),
            'status' => $status,
            'keterangan' => $request->keterangan ?? 'Absen Mobile App',
            'foto_url' => $request->foto_url,
            'lat' => $request->latitude,
            'lng' => $request->longitude,
            'created_at' => $now,
        ]);

        AuditLogController::record(
            'PRESENSI',
            'Absensi (Mobile)',
            "Pegawai melakukan presensi check-in ({$status}) melalui mobile",
            (string) $pegawai->nik,
            (string) ($pegawai->name ?? $pegawai->nama),
            $pegawai->role ?? 'Pegawai',
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melakukan absensi masuk.',
            'data' => [
                'id' => $inserted,
                'tanggal' => $now->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => $status,
            ]
        ]);
    }

    /**
     * Clock-Out Absensi Mobile
     */
    public function checkoutAbsensi(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $pegawai = $authUser;
        $now = now();
        $today = $now->toDateString();

        $absensi = DB::table('absensi_harian')
            ->where('pegawai_id', $pegawai->id)
            ->where('tanggal', $today)
            ->orderByDesc('id')
            ->first();

        if (! $absensi) {
            return response()->json(['success' => false, 'message' => 'Belum ada data check-in hari ini.'], 400);
        }

        DB::table('absensi_harian')->where('id', $absensi->id)->update([
            'jam_pulang' => $now->toDateTimeString(),
        ]);

        AuditLogController::record(
            'PRESENSI',
            'Absensi (Mobile)',
            'Pegawai melakukan presensi check-out pulang melalui mobile',
            (string) $pegawai->nik,
            (string) ($pegawai->name ?? $pegawai->nama),
            $pegawai->role ?? 'Pegawai',
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melakukan absensi pulang (check-out).',
            'data' => [
                'id' => $absensi->id,
                'jam_pulang' => $now->toTimeString(),
            ]
        ]);
    }

    /**
     * Data Sanksi Pegawai
     */
    public function sanksi(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
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
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
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
        $authUser = $request->attributes->get('auth_user');
        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $isPrivileged = in_array($userLevel, ['1', '7']) || in_array($dbRole, ['sdm', 'admin', 'direktur']);

        // Jika pimpinan/SDM meminta daftar semua cuti bawahan
        if ($isPrivileged && ($request->query('all') === '1' || $request->query('scope') === 'all')) {
            $data = DB::table('pengajuan_cuti')
                ->leftJoin('pegawai', 'pengajuan_cuti.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'pengajuan_cuti.*',
                    'pegawai.nik as pegawai_nik',
                    'pegawai.name as nama_lengkap',
                    'pegawai.unit_kerja'
                )
                ->orderByDesc('pengajuan_cuti.created_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'pegawai_id' => (string) $row->pegawai_id,
                        'nama_pegawai' => $row->nama_lengkap ?? $row->nama_pegawai ?? 'Pegawai',
                        'nik' => $row->pegawai_nik ?? '-',
                        'unit_kerja' => $row->unit_kerja ?? '-',
                        'jenis' => strtolower($row->jenis ?? 'izin'),
                        'tanggal_mulai' => $row->tanggal_mulai,
                        'tanggal_selesai' => $row->tanggal_selesai,
                        'alasan' => $row->alasan ?? '-',
                        'status' => strtolower($row->status ?? 'menunggu'),
                        'created_at' => $row->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Seluruh data pengajuan cuti berhasil diambil',
                'data' => $data,
            ]);
        }

        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
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
     * Update Status Pengajuan Cuti (Approval / Reject oleh SDM atau Direktur)
     */
    public function updateCutiStatus(Request $request, $id)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $isPrivileged = in_array($userLevel, ['1', '7']) || in_array($dbRole, ['sdm', 'admin', 'direktur']);

        if (! $isPrivileged) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki wewenang menyetujui cuti.'], 403);
        }

        $request->validate([
            'status' => 'required|string',
        ]);

        $statusInput = strtoupper(trim($request->status));
        $normalizedStatus = match ($statusInput) {
            'DISETUJUI', 'APPROVED' => 'DISETUJUI',
            'DITOLAK', 'REJECTED' => 'DITOLAK',
            default => 'PENDING',
        };

        $affected = DB::table('pengajuan_cuti')->where('id', $id)->update([
            'status' => $normalizedStatus,
            'updated_at' => now(),
        ]);

        if (! $affected) {
            return response()->json(['success' => false, 'message' => 'Pengajuan cuti tidak ditemukan.'], 404);
        }

        AuditLogController::record(
            'APPROVE',
            'Cuti (Mobile)',
            "Memperbarui status pengajuan cuti ID #{$id} menjadi {$normalizedStatus} melalui mobile",
            (string) $authUser->nik,
            (string) ($authUser->name ?? $authUser->nama),
            $authUser->role ?? 'SDM',
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => "Pengajuan cuti berhasil diubah menjadi {$normalizedStatus}.",
        ]);
    }

    /**
     * Submit Pengajuan Cuti
     */
    public function storeCuti(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $request->validate([
            'nik' => 'required|string',
            'jenis_cuti' => 'required|string',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date',
            'alasan' => 'required|string',
        ]);

        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $isPrivileged = in_array($userLevel, ['1', '2', '7']) || in_array($dbRole, ['sdm', 'admin']);

        if (! $isPrivileged && (string) $request->nik !== (string) $authUser->nik) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang mengajukan cuti untuk pegawai lain.',
            ], 403);
        }

        $pegawai = ($isPrivileged && (string) $request->nik !== (string) $authUser->nik)
            ? DB::table('pegawai')->where('nik', $request->nik)->first()
            : $authUser;

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

        AuditLogController::record(
            'CREATE',
            'Cuti (Mobile)',
            "Pegawai mengajukan cuti {$request->jenis_cuti} ({$request->tgl_mulai} s/d {$request->tgl_selesai}) melalui mobile",
            (string) $pegawai->nik,
            (string) ($pegawai->name ?? $pegawai->nama),
            $pegawai->role ?? 'Pegawai',
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'data' => ['id' => $id],
        ]);
    }

    /**
     * Get Riwayat Lembur Pegawai (dari Prestasi SDM, Tabel Lembur, & Payroll)
     */
    public function getLembur(Request $request)
    {
        $start = microtime(true);
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $tPeg = round((microtime(true) - $start) * 1000, 2);
        $cacheKey = "api_lembur_{$pegawai->id}";
        $isHit = Cache::has($cacheKey);
        $finalData = Cache::remember($cacheKey, 60, function () use ($pegawai) {
            $bulanNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            $allItems = collect();

            // 1. Ambil dari Prestasi (Prioritas tertinggi: input manual SDM)
            try {
                $prestasiRows = DB::table('prestasi')
                    ->where('pegawai_id', $pegawai->id)
                    ->orderByDesc('tanggal')
                    ->orderByDesc('id')
                    ->get();

                foreach ($prestasiRows as $r) {
                    $meta = json_decode($r->keterangan ?? '{}', true) ?: [];
                    $jam = (float) ($meta['jam_lembur'] ?? 0);
                    $nominal = (int) ($meta['nominal_lembur'] ?? round($jam * \App\Http\Controllers\PrestasiController::RATE_LEMBUR_PER_JAM));

                    if ($jam > 0 || $nominal > 0) {
                        $bName = '-';
                        if (!empty($r->tanggal)) {
                            $t = strtotime($r->tanggal);
                            if ($t) {
                                $m = (int) date('n', $t);
                                $y = date('Y', $t);
                                $bName = ($bulanNames[$m] ?? date('F', $t)) . " $y";
                            }
                        }

                        $allItems->push([
                            'id' => $r->id,
                            'bulan' => $bName,
                            'jam_lembur' => $jam,
                            'uang_lembur' => $nominal,
                            'tanggal' => $r->tanggal,
                            'keterangan' => $meta['desc'] ?? ($r->judul ?? 'Lembur Prestasi Kerja'),
                            'source' => 'prestasi',
                            'created_at' => $r->created_at,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Api getLembur prestasi failed: ' . $e->getMessage());
            }

            // 2. Ambil dari tabel lembur
            try {
                $lemburRows = DB::table('lembur')
                    ->where('pegawai_id', $pegawai->id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();

                foreach ($lemburRows as $r) {
                    $allItems->push([
                        'id' => $r->id,
                        'bulan' => $r->bulan ?? '-',
                        'jam_lembur' => (float) ($r->jam_lembur ?? 0),
                        'uang_lembur' => (int) ($r->uang_lembur ?? 0),
                        'tanggal' => $r->created_at ? date('Y-m-d', strtotime($r->created_at)) : null,
                        'keterangan' => 'Uang Lembur Pegawai',
                        'source' => 'lembur',
                        'created_at' => $r->created_at,
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Api getLembur lembur table failed: ' . $e->getMessage());
            }

            // 3. Fallback dari tabel payroll
            try {
                $payrollRows = DB::table('payroll')
                    ->where('pegawai_id', $pegawai->id)
                    ->whereIn(DB::raw('LOWER(status)'), ['terbit', 'diterbitkan'])
                    ->where('lembur', '>', 0)
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->get();

                foreach ($payrollRows as $r) {
                    $bName = $bulanNames[(int) $r->bulan] ?? ('Bulan ' . $r->bulan);
                    $periode = $r->periode ?? "$bName {$r->tahun}";
                    $uang = (int) $r->lembur;
                    $jam = round($uang / \App\Http\Controllers\PrestasiController::RATE_LEMBUR_PER_JAM, 1);

                    $allItems->push([
                        'id' => $r->id,
                        'bulan' => $periode,
                        'jam_lembur' => $jam,
                        'uang_lembur' => $uang,
                        'tanggal' => null,
                        'keterangan' => "Payroll $periode",
                        'source' => 'payroll',
                        'created_at' => $r->created_at ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Api getLembur payroll failed: ' . $e->getMessage());
            }

            $normalizeBulan = function ($str) {
                if (!$str || $str === '-') return '-';
                $en = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                $id = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                return trim(str_ireplace($en, $id, (string) $str));
            };

            // Urutkan & prioritaskan prestasi -> lembur -> payroll per bulan
            return $allItems->map(function ($item) use ($normalizeBulan) {
                $item['bulan'] = $normalizeBulan($item['bulan']);
                return $item;
            })->unique('bulan')->values()->all();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data lembur berhasil diambil.',
            'data' => $finalData,
        ]);
    }

    /**
     * Submit Pengajuan Lembur
     */
    public function storeLembur(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $request->validate([
            'nik' => 'required|string',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|string',
            'jam_selesai' => 'required|string',
            'kegiatan' => 'required|string',
        ]);

        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $isPrivileged = in_array($userLevel, ['1', '2', '7']) || in_array($dbRole, ['sdm', 'admin']);

        if (! $isPrivileged && (string) $request->nik !== (string) $authUser->nik) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang mengajukan lembur untuk pegawai lain.',
            ], 403);
        }

        $pegawai = ($isPrivileged && (string) $request->nik !== (string) $authUser->nik)
            ? DB::table('pegawai')->where('nik', $request->nik)->first()
            : $authUser;

        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $jamMulai = strtotime($request->jam_mulai);
        $jamSelesai = strtotime($request->jam_selesai);
        $hours = ($jamSelesai && $jamMulai && $jamSelesai > $jamMulai) ? round(($jamSelesai - $jamMulai) / 3600, 1) : 3;
        $uangLembur = (int) round($hours * \App\Http\Controllers\PrestasiController::RATE_LEMBUR_PER_JAM);

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $t = strtotime($request->tanggal);
        $m = (int) date('n', $t);
        $y = date('Y', $t);
        $periodeStr = ($bulanNames[$m] ?? date('F', $t)) . " $y";

        $id = DB::table('lembur')->insertGetId([
            'pegawai_id' => $pegawai->id,
            'bulan' => $periodeStr,
            'jam_lembur' => $hours,
            'uang_lembur' => $uangLembur,
            'created_at' => now(),
        ]);

        Cache::forget("api_lembur_{$pegawai->id}");

        AuditLogController::record(
            'CREATE',
            'Lembur (Mobile)',
            "Pegawai mengajukan lembur {$hours} jam pada periode {$periodeStr} melalui mobile",
            (string) $pegawai->nik,
            (string) ($pegawai->name ?? $pegawai->nama),
            $pegawai->role ?? 'Pegawai',
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

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
        $authUser = $request->attributes->get('auth_user');
        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $divisiKadiv = $authUser->divisi_kadiv ?? null;
        $isManager = in_array($userLevel, ['1', '7']) || in_array($dbRole, ['sdm', 'admin', 'direktur', 'kspi', 'kadiv', 'kadivkategori', 'tpdpk']);

        $arrayFields = [
            'foto_bukti', 'dokumen_pendukung', 'video_bukti', 'voice_note',
            'investigasi_foto', 'investigasi_video', 'investigasi_voice', 'investigasi_dokumen',
            'hasil_foto', 'hasil_dokumen', 'hasil_voice', 'hasil_video'
        ];
        $normalizePengaduanList = function ($rows) use ($arrayFields) {
            return collect($rows)->map(function ($row) use ($arrayFields) {
                $arr = (array) $row;
                foreach ($arrayFields as $f) {
                    $val = $arr[$f] ?? null;
                    if (empty($val) || $val === '{}' || $val === '[]' || $val === 'null') {
                        $arr[$f] = [];
                    } elseif (is_string($val)) {
                        $trim = trim($val);
                        if (str_starts_with($trim, '[') && str_ends_with($trim, ']')) {
                            $decoded = json_decode($trim, true);
                            $arr[$f] = is_array($decoded) ? $decoded : [];
                        } elseif (str_starts_with($trim, '{') && str_ends_with($trim, '}')) {
                            $inner = trim(substr($trim, 1, -1));
                            $arr[$f] = empty($inner) ? [] : array_values(array_filter(array_map(function($s) {
                                return trim(trim($s), '"\'');
                            }, explode(',', $inner))));
                        } else {
                            $arr[$f] = [$trim];
                        }
                    } elseif (!is_array($val)) {
                        $arr[$f] = [];
                    }
                }
                return $arr;
            })->all();
        };

        // Jika peran manajerial meminta kotak masuk kasus (scope=role atau tab=masuk)
        if ($isManager && ($request->query('scope') === 'role' || $request->query('tab') === 'masuk')) {
            $query = DB::table('pengaduan_pegawai')
                ->select(
                    'pengaduan_pegawai.*',
                    DB::raw("(SELECT keterangan FROM riwayat_status_pengaduan WHERE pengaduan_id = pengaduan_pegawai.id AND keterangan IS NOT NULL AND TRIM(keterangan) != '' ORDER BY id DESC LIMIT 1) as keterangan_terakhir")
                );

            if ($dbRole === 'kadiv' || $dbRole === 'kadivkategori') {
                $categories = ($divisiKadiv === 'teknik')
                    ? ['Pelanggaran Teknik']
                    : ['Pelanggaran Administrasi', 'Umum'];
                $query->where(function ($q) use ($categories, $divisiKadiv) {
                    $q->where(function ($sub) use ($categories) {
                        $sub->whereIn('status', ['menungguKadiv', 'menungguVerifikasiKadiv', 'DIAJUKAN'])
                            ->whereIn('kategori', $categories);
                    })->orWhere(function ($sub) use ($divisiKadiv) {
                        $sub->where('status', 'investigasiBerjalan')
                            ->where('eksekutor', 'kadiv')
                            ->where('eksekutor_divisi_kadiv', $divisiKadiv);
                    })->orWhere(function ($sub) use ($divisiKadiv) {
                        $sub->where('status', 'tindakLanjutBerjalan')
                            ->where('eksekutor_tindak_lanjut', 'kadiv')
                            ->where('eksekutor_tindak_lanjut_divisi_kadiv', $divisiKadiv);
                    });
                });
            } elseif ($dbRole === 'kspi') {
                $query->whereIn('status', ['reviewKspi', 'telaahAwalKspi', 'menungguPilihEksekutor', 'hasilInvestigasiMasuk']);
            } elseif ($dbRole === 'direktur') {
                $query->whereIn('status', ['disposisiDirutTahap1', 'putusanDirutTahap2']);
            } elseif ($dbRole === 'tpdpk') {
                $query->where('status', 'investigasiBerjalan');
            } elseif ($dbRole === 'sdm' || $userLevel === '1') {
                $query->whereIn('status', ['sdmPemberianSanksi', 'tindakLanjutSdm', 'selesai', 'menungguSdm']);
            }

            $raw = $query->orderByDesc('created_at')->get();
            $data = $normalizePengaduanList($raw);

            return response()->json([
                'success' => true,
                'message' => 'Kotak masuk pengaduan peran berhasil diambil',
                'data' => $data,
            ]);
        }

        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        $raw = DB::table('pengaduan_pegawai')
            ->where('pelapor_id', $pegawai->id)
            ->orWhere('nik', $pegawai->nik)
            ->orderByDesc('created_at')
            ->get();
        $data = $normalizePengaduanList($raw);

        return response()->json([
            'success' => true,
            'message' => 'Data pengaduan berhasil diambil',
            'data' => $data,
        ]);
    }

    public function storePengaduan(Request $request)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $request->validate([
            'nik' => 'required|string',
            'subjek' => 'required|string',
            'pesan' => 'required|string',
        ]);

        $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
        $dbRole = strtolower($authUser->role ?? '');
        $isPrivileged = in_array($userLevel, ['1', '2', '7']) || in_array($dbRole, ['sdm', 'admin']);

        if (! $isPrivileged && (string) $request->nik !== (string) $authUser->nik) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang mengirim pengaduan atas nama pegawai lain.',
            ], 403);
        }

        $pegawai = ($isPrivileged && (string) $request->nik !== (string) $authUser->nik)
            ? DB::table('pegawai')->where('nik', $request->nik)->first()
            : $authUser;

        if (! $pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
        }

        $now = now();
        $nomorPengaduan = 'PGD-' . date('Ymd') . '-' . rand(1000, 9999);
        $kategori = $request->input('kategori') ?: 'Pelanggaran Administrasi';
        $status = 'menungguKadiv';
        $pihakTerlapor = trim((string) ($request->input('pihak_terlapor') ?? ''));
        $nikPelaku = trim((string) ($request->input('nik_pelaku') ?? ''));
        $jabatanPelaku = trim((string) ($request->input('jabatan_pelaku') ?? ''));
        $anonim = $request->boolean('anonim', false);

        $fotoBukti = $request->input('foto_bukti');
        $dokumenPendukung = $request->input('dokumen_pendukung');

        $id = DB::table('pengaduan_pegawai')->insertGetId([
            'nomor_pengaduan' => $nomorPengaduan,
            'pelapor_id' => $pegawai->id,
            'kategori' => $kategori,
            'judul' => $request->subjek,
            'deskripsi' => $request->pesan,
            'tanggal_pengaduan' => $now,
            'nama_pegawai' => $pegawai->name,
            'nik' => $pegawai->nik,
            'cabang' => $pegawai->unit_kerja ?? 'Kantor Pusat',
            'golongan' => $pegawai->golongan ?? '',
            'pihak_terlapor' => !empty($pihakTerlapor) ? $pihakTerlapor : null,
            'nik_pelaku' => !empty($nikPelaku) ? $nikPelaku : null,
            'jabatan_pelaku' => !empty($jabatanPelaku) ? $jabatanPelaku : null,
            'anonim' => $anonim,
            'foto_bukti' => !empty($fotoBukti) ? (is_array($fotoBukti) ? json_encode($fotoBukti) : $fotoBukti) : null,
            'dokumen_pendukung' => !empty($dokumenPendukung) ? (is_array($dokumenPendukung) ? json_encode($dokumenPendukung) : $dokumenPendukung) : null,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Catat di Riwayat Status Pengaduan (Audit trail)
        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => $status,
            'status_lama' => null,
            'oleh' => $anonim ? 'Anonim' : ($pegawai->name ?? $pegawai->nama),
            'role' => 'pegawai',
            'aksi' => 'Pengaduan dibuat',
            'keterangan' => !empty($pihakTerlapor) ? ('Pelaku diadukan: ' . $pihakTerlapor . (!empty($nikPelaku) ? ' · NIK ' . $nikPelaku : '')) : 'Pengaduan berhasil diajukan oleh pelapor',
            'tanggal' => $now,
            'created_at' => $now,
        ]);

        AuditLogController::record(
            'CREATE',
            'Pengaduan (Mobile)',
            "Mengajukan pengaduan baru: \"{$request->subjek}\" (No: {$nomorPengaduan}) melalui mobile",
            (string) $pegawai->nik,
            (string) ($pegawai->name ?? $pegawai->nama),
            $pegawai->role ?? 'Pegawai',
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaduan berhasil dikirim dan menunggu verifikasi Kadiv.',
            'data' => ['id' => $id, 'nomor_pengaduan' => $nomorPengaduan, 'status' => $status],
        ]);
    }

    /**
     * Update Status Pengaduan dari Mobile (Kadiv Aksi, Alihkan Kategori, dll)
     */
    public function updateStatusPengaduan(Request $request, $id)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal.'], 401);
        }

        $pengaduan = DB::table('pengaduan_pegawai')->where('id', $id)->first();
        if (! $pengaduan) {
            return response()->json(['success' => false, 'message' => 'Pengaduan tidak ditemukan.'], 404);
        }

        $now = now();
        $statusBaru = $request->input('status');
        $statusLama = $pengaduan->status;
        $kategoriBaru = $request->input('kategori');
        $catatan = $request->input('catatan');
        $aksi = $request->input('aksi') ?: 'Pembaruan status pengaduan';
        $roleName = $authUser->role ?? 'pegawai';
        $oleh = $authUser->name ?? $authUser->nama ?? 'Sistem';

        $updateData = ['updated_at' => $now];
        if (!empty($statusBaru)) {
            $updateData['status'] = $statusBaru;
        }
        if (!empty($kategoriBaru)) {
            $updateData['kategori'] = $kategoriBaru;
        }
        if ($request->has('keputusan_kadiv')) {
            $updateData['keputusan_kadiv'] = $request->input('keputusan_kadiv');
        }
        if ($request->has('catatan_kadiv')) {
            $updateData['catatan_kadiv'] = $request->input('catatan_kadiv');
        }

        DB::table('pengaduan_pegawai')->where('id', $id)->update($updateData);

        DB::table('riwayat_status_pengaduan')->insert([
            'pengaduan_id' => $id,
            'status' => $statusBaru ?: $statusLama,
            'status_lama' => $statusLama,
            'oleh' => $oleh,
            'role' => $roleName,
            'aksi' => $aksi,
            'keterangan' => $catatan,
            'tanggal' => $now,
            'created_at' => $now,
        ]);

        AuditLogController::record(
            'UPDATE',
            'Pengaduan (Mobile)',
            "{$aksi} pada Pengaduan #{$pengaduan->nomor_pengaduan}: {$catatan}",
            (string) $authUser->nik,
            (string) $oleh,
            $roleName,
            $request->header('X-Device-Info') ?: ($request->header('User-Agent') ?: 'SIMPEG Mobile App')
        );

        return response()->json([
            'success' => true,
            'message' => 'Status pengaduan berhasil diperbarui.',
            'data' => array_merge((array)$pengaduan, $updateData),
        ]);
    }

    /**
     * API Dokumen Resmi Pegawai (SK & Diklat) yang diterbitkan oleh SDM
     */
    public function dokumenResmi(Request $request)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        try {
            $pegawaiUuid = ($pegawai && ! empty($pegawai->id) && \Illuminate\Support\Str::isUuid((string) $pegawai->id))
                ? (string) $pegawai->id
                : null;
            $nik = (string) ($pegawai->nik ?? '');

            $dokumenList = DB::table('dokumen_pegawai')
                ->where(function ($q) use ($pegawaiUuid, $nik) {
                    if ($pegawaiUuid && \Illuminate\Support\Str::isUuid((string) $pegawaiUuid)) {
                        $q->where('pegawai_id', $pegawaiUuid);
                    }
                    if (! empty($nik)) {
                        $q->orWhereRaw('pegawai_id::text = ?', [(string) $nik]);
                    }
                    $q->orWhereNull('pegawai_id');
                })
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($d) use ($request) {
                    $fileUrl = $d->file_url ?? '';
                    if (! empty($fileUrl) && $fileUrl !== '#') {
                        $parsed = parse_url($fileUrl);
                        $path = $parsed['path'] ?? '';
                        if (! empty($path) && str_starts_with($path, '/uploads/')) {
                            $fullPath = public_path(ltrim($path, '/'));
                            if (! File::exists($fullPath)) {
                                $cleanName = preg_replace('/^\d+_/', '', basename($path));
                                $matches = glob(public_path('uploads/dokumen/*') . $cleanName);
                                if (! empty($matches) && File::exists($matches[0])) {
                                    $path = '/uploads/dokumen/' . basename($matches[0]);
                                }
                            }
                            $fileUrl = $request->getSchemeAndHttpHost() . $path;
                        }
                    }

                    // Normalisasi kategori agar terbaca 'SK' atau 'Diklat' di Mobile
                    $rawKategori = strtolower(trim($d->kategori ?? ''));
                    $cleanKategori = 'Umum';
                    if (in_array($rawKategori, ['sk', 'surat_kerja'], true)) {
                        $cleanKategori = 'SK';
                    } elseif (in_array($rawKategori, ['diklat', 'surat_diklat'], true)) {
                        $cleanKategori = 'Diklat';
                    } elseif (! empty($d->kategori)) {
                        $cleanKategori = $d->kategori;
                    }

                    return [
                        'id' => $d->id,
                        'pegawai_id' => $d->pegawai_id,
                        'judul' => $d->judul ?? 'Dokumen Resmi Kepegawaian',
                        'kategori' => $cleanKategori,
                        'nomor' => $d->nomor ?? '-',
                        'file_url' => $fileUrl,
                        'file_nama' => $d->file_nama ?? 'dokumen.pdf',
                        'diunggah_oleh' => $d->diunggah_oleh ?? 'Admin SDM',
                        'created_at' => $d->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $dokumenList,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil dokumen: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Download berkas dokumen resmi fisik langsung via API
     */
    public function downloadDokumen(Request $request, $id)
    {
        $authUser = $request->attributes->get('auth_user');
        if (! $authUser) {
            return response()->json(['success' => false, 'message' => 'Autentikasi gagal: Sesi tidak valid.'], 401);
        }

        $doc = DB::table('dokumen_pegawai')->where('id', $id)->first();
        if (! $doc) {
            return response()->json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }

        // Verifikasi kepemilikan dokumen (IDOR prevention)
        if (! empty($doc->pegawai_id)) {
            $isOwner = ((string) $doc->pegawai_id === (string) $authUser->id) || ((string) $doc->pegawai_id === (string) $authUser->nik);
            $userLevel = (string) ($authUser->userlevel ?? $authUser->role ?? '5');
            $dbRole = strtolower($authUser->role ?? '');
            $isPrivileged = in_array($userLevel, ['1', '2', '7']) || in_array($dbRole, ['sdm', 'admin', 'direktur', 'kspi', 'kadiv']);

            if (! $isOwner && ! $isPrivileged) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda tidak memiliki wewenang mengunduh dokumen pegawai lain.',
                ], 403);
            }
        }

        if (empty($doc->file_url) || $doc->file_url === '#') {
            return response()->json(['success' => false, 'message' => 'Berkas fisik dokumen belum diunggah oleh SDM.'], 404);
        }

        $parsedPath = parse_url($doc->file_url, PHP_URL_PATH);
        $relativePath = ltrim($parsedPath ?? '', '/');
        $fullPath = public_path($relativePath);

        if (! File::exists($fullPath)) {
            $baseName = basename($parsedPath);
            $fullPath = public_path('uploads/dokumen/' . $baseName);
        }

        if (! File::exists($fullPath)) {
            $cleanName = preg_replace('/^\d+_/', '', basename($parsedPath));
            $matches = glob(public_path('uploads/dokumen/*') . $cleanName);
            if (! empty($matches) && File::exists($matches[0])) {
                $fullPath = $matches[0];
            }
        }

        if (File::exists($fullPath)) {
            return response()->download($fullPath, $doc->file_nama ?? 'Dokumen.pdf');
        }

        return response()->json(['success' => false, 'message' => 'Berkas fisik tidak ditemukan di server.'], 404);
    }

    /**
     * API Pengumuman Perusahaan
     */
    public function pengumuman(Request $request)
    {
        $pegawai = $request->attributes->get('auth_user');

        try {
            $list = DB::table('pengumuman')
                ->where('aktif', true)
                ->where(function ($q) {
                    $q->whereNull('kedaluwarsa_pada')
                      ->orWhere('kedaluwarsa_pada', '>=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('terbit_pada')
                      ->orWhere('terbit_pada', '<=', now());
                })
                ->orderByDesc('disematkan')
                ->orderByDesc('terbit_pada')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($p) use ($pegawai) {
                    $isRead = false;
                    if ($pegawai) {
                        $isRead = DB::table('pengumuman_dibaca')
                            ->where('pengumuman_id', $p->id)
                            ->where('pegawai_id', $pegawai->id)
                            ->exists();
                    }

                    return [
                        'id' => $p->id,
                        'judul' => $p->judul,
                        'isi' => $p->isi,
                        'prioritas' => (bool) ($p->prioritas ?? false),
                        'is_pinned' => (bool) ($p->disematkan ?? false),
                        'tgl_publikasi' => !empty($p->terbit_pada) ? substr((string) $p->terbit_pada, 0, 10) : substr((string) $p->created_at, 0, 10),
                        'penulis' => $p->pembuat ?? 'Admin SDM',
                        'lampiran_url' => $p->lampiran_url ?? null,
                        'lampiran_nama' => $p->lampiran_nama ?? null,
                        'is_read' => $isRead,
                        'created_at' => $p->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $list,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil pengumuman: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * API Tandai Pengumuman Telah Dibaca
     */
    public function markPengumumanRead(Request $request, $id)
    {
        $pegawai = $this->getPegawaiByRequest($request);
        if ($resp = $this->checkPegawaiAccess($pegawai)) {
            return $resp;
        }

        try {
            DB::table('pengumuman_dibaca')->updateOrInsert(
                [
                    'pengumuman_id' => $id,
                    'pegawai_id' => $pegawai->id,
                ],
                [
                    'dibaca_pada' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman ditandai sebagai telah dibaca.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
