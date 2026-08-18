<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
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
    ];

    /**
     * Pegawai (userlevel 5) diarahkan ke profil diri sendiri,
     * role lain (Admin, Keuangan, Direksi) ke dashboard.
     */
    protected function redirectRouteFor(array $user): string
    {
        return $user['userlevel'] === '5' ? 'profile.show' : 'dashboard';
    }

    public function showLoginForm()
    {
        if ($user = session('simpeg_user')) {
            return redirect()->route($this->redirectRouteFor($user));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required',
            'password' => 'required|string',
        ]);

        $nik = (string) trim($request->nik);

        // 1. Cari pegawai murni di database Supabase
        $pegawai = null;
        try {
            $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
        } catch (\Throwable $e) {
            // DB connection error handling
        }

        if (! $pegawai) {
            return back()
                ->withErrors(['nik' => 'NIK tidak terdaftar dalam database.'])
                ->onlyInput('nik');
        }

        // 2. Verifikasi Password
        $expectedPass = $this->defaultPasswords[$nik] ?? 'password';
        if ($request->password !== $expectedPass) {
            return back()
                ->withErrors(['nik' => 'Kata sandi yang Anda masukkan salah.'])
                ->onlyInput('nik');
        }

        // 3. Tentukan userlevel murni berdasarkan database:
        // Level 1 = Admin / SDM
        // Level 2 = Keuangan
        // Level 7 = Direksi (DIRUT)
        // Level 5 = Pegawai biasa (termasuk Kadiv, KSPI, TPDPK)
        $userLevel = '5';
        $dbRole = strtolower($pegawai->role ?? '');
        $jabatanLower = strtolower($pegawai->jabatan ?? '');

        if ($dbRole === 'direktur' || $nik === '5000000001' || str_contains($jabatanLower, 'direktur utama')) {
            $userLevel = '7'; // DIRUT
        } elseif ($dbRole === 'admin' || $dbRole === 'sdm' || $nik === '5000000002' || str_contains($jabatanLower, 'sdm') || $jabatanLower === 'admin' || $jabatanLower === 'administrator') {
            $userLevel = '1'; // Admin / SDM
        } elseif ($dbRole === 'keuangan' || $dbRole === 'keu' || str_contains($jabatanLower, 'keuangan')) {
            $userLevel = '2'; // Keuangan
        } else {
            $userLevel = '5'; // Pegawai biasa (Kadiv, KSPI, TPDPK, Pelaksana, dll)
        }

        $user = [
            'id' => $pegawai->id,
            'nik' => $pegawai->nik,
            'password' => $expectedPass,
            'nama_peg' => $pegawai->name,
            'jabatan' => $pegawai->jabatan,
            'userlevel' => $userLevel,
        ];

        $request->session()->regenerate();
        $request->session()->put('simpeg_user', $user);

        return redirect()->route($this->redirectRouteFor($user));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('simpeg_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}