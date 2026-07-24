<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * DUMMY USERS - satu akun per role, buat testing filter menu & akses.
     * Ganti ini kalau sudah siap dihubungkan ke tabel userlogin + tbl_pegawai asli.
     *
     * userlevel (lihat config/simpeg_roles.php): 1=Admin, 2=Keuangan, 5=Pegawai, 7=Direksi
     */
    protected array $dummyUsers = [
        '1711254' => [
            'nik' => '1711254',
            'password' => 'password',
            'nama_peg' => 'Mukti Kurniawan',
            'jabatan' => 'Staf SDM',
            'userlevel' => '1', // Admin
        ],
        '1800001' => [
            'nik' => '1800001',
            'password' => 'password',
            'nama_peg' => 'Dewi Anggraini',
            'jabatan' => 'Staf Keuangan',
            'userlevel' => '2', // Keuangan
        ],
        '1800003' => [
            'nik' => '1800003',
            'password' => 'password',
            'nama_peg' => 'Nur Hidayah',
            'jabatan' => 'Pegawai',
            'userlevel' => '5', // Pegawai
        ],
        '1800004' => [
            'nik' => '1800004',
            'password' => 'password',
            'nama_peg' => 'Bambang Wijaya',
            'jabatan' => 'Direktur Utama',
            'userlevel' => '7', // Direksi
        ],
        '1800005' => [
            'nik' => '1800005',
            'password' => 'password',
            'nama_peg' => 'Hendra Kusuma',
            'jabatan' => 'Direktur Umum',
            'userlevel' => '7', // Direksi
        ],
    ];

    public function showLoginForm()
    {
        if (session('simpeg_user')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = $this->dummyUsers[$request->nik] ?? null;

        if ($user && $request->password === $user['password']) {
            $request->session()->regenerate();
            $request->session()->put('simpeg_user', $user);

            return redirect()->route('dashboard');
        }

        return back()
            ->withErrors(['nik' => 'NIK atau kata sandi salah.'])
            ->onlyInput('nik');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('simpeg_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}