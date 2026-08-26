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
        '4000000006' => 'kadivteknik2025',
        '4000000002' => 'kspi123',
        '4000000003' => 'tpdpk123',
        '5000000001' => 'dirut123',
        '5000000002' => 'sdm123',
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

        // 2. Verifikasi Password langsung ke hash auth.users Supabase atau defaultPasswords
        $passwordValid = false;
        try {
            $authUser = DB::table('auth.users')->where('email', "{$nik}@gmail.com")->first();
            if ($authUser && ! empty($authUser->encrypted_password)) {
                if (password_verify($request->password, $authUser->encrypted_password)) {
                    $passwordValid = true;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static verify
        }

        $expectedPass = $this->defaultPasswords[$nik] ?? 'password';
        if (! $passwordValid && $request->password === $expectedPass) {
            $passwordValid = true;
        }

        if (! $passwordValid) {
            return back()
                ->withErrors(['nik' => 'Kata sandi yang Anda masukkan salah.'])
                ->onlyInput('nik');
        }

        // 3. Tentukan userlevel & role pengaduan murni dari kolom database (pegawai.role & pegawai.divisi_kadiv):
        $dbRole = strtolower(trim($pegawai->role ?? 'pegawai'));
        $divisiKadiv = $pegawai->divisi_kadiv ?? null;

        if ($dbRole === 'direktur') {
            $userLevel = '7'; // DIRUT
            $rolePengaduan = 'dirut';
        } elseif ($dbRole === 'kspi') {
            $userLevel = '5';
            $rolePengaduan = 'kspi';
        } elseif ($dbRole === 'tpdpk') {
            $userLevel = '5';
            $rolePengaduan = 'tpdpk';
        } elseif ($dbRole === 'kadiv' || $dbRole === 'kadivkategori') {
            $userLevel = '5';
            $rolePengaduan = 'kadiv';
        } elseif ($dbRole === 'admin' || $dbRole === 'sdm') {
            $userLevel = '1'; // Admin / SDM
            $rolePengaduan = 'sdm';
        } elseif ($dbRole === 'keuangan' || $dbRole === 'keu') {
            $userLevel = '2'; // Keuangan
            $rolePengaduan = 'keuangan';
        } else {
            $userLevel = '5'; // Pegawai biasa
            $rolePengaduan = 'pegawai';
        }

        $user = [
            'id' => $pegawai->id,
            'nik' => $pegawai->nik,
            'password' => $expectedPass,
            'nama_peg' => $pegawai->name,
            'jabatan' => $pegawai->jabatan,
            'userlevel' => $userLevel,
            'role_pengaduan' => $rolePengaduan,
            'divisi_kadiv' => $divisiKadiv,
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