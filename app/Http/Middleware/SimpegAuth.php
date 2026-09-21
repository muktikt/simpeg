<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SimpegAuth
{
    /**
     * Middleware berbasis session + role.
     *
     * Pemakaian di routes/web.php:
     *   Route::middleware(['simpeg.auth'])->group(...)                 -> wajib login, semua role boleh
     *   Route::middleware(['simpeg.auth:1'])->group(...)                -> wajib login, cuma role 1 (Admin)
     *   Route::middleware(['simpeg.auth:1,2'])->group(...)              -> wajib login, role 1 atau 2
     *
     * Kode role (lihat config/simpeg_roles.php): 1=Admin, 2=Keuangan, 5=Pegawai, 7=Direksi
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles)
    {
        $user = $request->session()->get('simpeg_user');

        if (! $user) {
            return redirect()->route('login');
        }

        // Self-healing: jika akun master seperti NIK 1711254 tersimpan di session dengan userlevel salah (misal sisa session lama),
        // otomatis perbaiki ke level yang berhak agar tidak terblokir 403.
        $masterRoles = [
            '1711254' => '1', // Heddy Kelana (SDM)
            '1711157' => '1', // Cahrudin (SDM)
            '1711444' => '1', // Suwanto (SDM)
            '1711590' => '1', // Riko Prahtama (SDM)
            '1711567' => '1', // Asep Kurnadi (SDM)
            '1711001' => '7', // Nurpan (Dirut)
            '1711002' => '7', // Dr. Sunaryo (Dirum)
            '1711003' => '7', // Jojo Sutarjo (Dirtek)
            '1711296' => '2', // Yayah Khaeriyah (Keuangan)
            '1711145' => '2', // Ari Hendrayati (Keuangan)
        ];

        $nik = (string) ($user['nik'] ?? '');
        if (isset($masterRoles[$nik]) && (string) ($user['userlevel'] ?? '') !== $masterRoles[$nik]) {
            $user['userlevel'] = $masterRoles[$nik];
            if ($masterRoles[$nik] === '1') {
                $user['role_pengaduan'] = 'sdm';
            } elseif ($masterRoles[$nik] === '2') {
                $user['role_pengaduan'] = 'keuangan';
            } elseif ($masterRoles[$nik] === '7') {
                $user['role_pengaduan'] = 'dirut';
            }
            $request->session()->put('simpeg_user', $user);
        }

        if (! empty($allowedRoles)) {
            $userRole = (string) ($user['userlevel'] ?? '');
            $allowed = array_map('strval', $allowedRoles);

            if (! in_array($userRole, $allowed, true)) {
                abort(403, 'Kamu tidak punya akses ke halaman ini.');
            }
        }

        return $next($request);
    }
}
