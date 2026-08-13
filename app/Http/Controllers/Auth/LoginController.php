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

    protected function getAllowedWebUsers(): array
    {
        $userAksesList = session('dummy_userakses');

        if (! $userAksesList) {
            return $this->dummyUsers;
        }

        $allPegawai = session('dummy_pegawai', []);

        $result = [];
        foreach ($userAksesList as $ua) {
            $nik = $ua['username'];
            $peg = collect($allPegawai)->firstWhere('nik', $nik);
            $result[$nik] = [
                'nik' => $nik,
                'password' => $ua['password'],
                'nama_peg' => $ua['nama'] ?? ($peg['nama'] ?? 'Pegawai'),
                'jabatan' => $peg['jabatan'] ?? 'Pegawai',
                'userlevel' => (string) $ua['userlevel'],
            ];
        }

        return $result ?: $this->dummyUsers;
    }

    /**
     * Pegawai (userlevel 5) tidak diarahkan ke dashboard perusahaan -
     * mengikuti sistem lama (menu_incl_pdam.php) yang landing page-nya
     * langsung profil diri sendiri, bukan dashboard statistik perusahaan.
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
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $allowedUsers = $this->getAllowedWebUsers();
        $user = $allowedUsers[$request->nik] ?? null;

        if ($user && $request->password === $user['password']) {
            $request->session()->regenerate();
            $request->session()->put('simpeg_user', $user);

            return redirect()->route($this->redirectRouteFor($user));
        }

        $allPegawai = session('dummy_pegawai', []);
        $isPegawai = collect($allPegawai)->contains('nik', $request->nik);
        if ($isPegawai && ! $user) {
            return back()
                ->withErrors(['nik' => 'NIK Anda terdaftar sebagai pegawai, tetapi belum diberi hak akses ke Web SIMPEG. Silakan hubungi Administrator.'])
                ->onlyInput('nik');
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