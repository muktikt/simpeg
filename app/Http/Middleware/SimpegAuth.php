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

        if (! empty($allowedRoles) && ! in_array($user['userlevel'], $allowedRoles, true)) {
            abort(403, 'Kamu tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
