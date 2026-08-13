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
    /**
     * Data user diambil secara dinamis dari UserAkses & Pegawai yang terhubung ke Supabase.
     */
    protected array $dummyUsers = [];

    protected function getAllowedWebUsers(): array
    {
        $userAksesController = app(\App\Http\Controllers\UserAksesController::class);
        $userAksesList = $userAksesController->index()->getData()['users'] ?? session('dummy_userakses', []);
        $allPegawai = app(\App\Http\Controllers\PegawaiController::class)->index(request())->getData()['pegawai'] ?? session('dummy_pegawai', []);

        $result = [];
        if (is_iterable($userAksesList)) {
            foreach ($userAksesList as $ua) {
                $nik = is_array($ua) ? ($ua['username'] ?? '') : ($ua->username ?? '');
                if (! $nik) continue;

                $pass = is_array($ua) ? ($ua['password'] ?? 'password') : ($ua->password ?? 'password');
                $nama = is_array($ua) ? ($ua['nama'] ?? '') : ($ua->nama ?? '');
                $level = is_array($ua) ? ($ua['userlevel'] ?? '5') : ($ua->userlevel ?? '5');

                $peg = collect($allPegawai)->firstWhere('nik', $nik);
                $pegNama = is_array($peg) ? ($peg['nama'] ?? '') : ($peg->nama ?? '');
                $pegJabatan = is_array($peg) ? ($peg['jabatan'] ?? '') : ($peg->jabatan ?? '');

                $result[$nik] = [
                    'nik' => $nik,
                    'password' => $pass,
                    'nama_peg' => $nama ?: ($pegNama ?: 'Pegawai'),
                    'jabatan' => $pegJabatan ?: 'Pegawai',
                    'userlevel' => (string) $level,
                ];
            }
        }

        return $result;
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