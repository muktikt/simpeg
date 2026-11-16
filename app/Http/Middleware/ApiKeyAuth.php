<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiKeyAuth
{
    /**
     * Validasi autentikasi API SIMPEG:
     * - Memprioritaskan Bearer token (JWT HS256 yang ditandatangani secara kriptografis per pegawai).
     * - Menolak token kosong, secret kosong, token kedaluwarsa, atau signature tidak valid.
     * - Menempelkan objek pegawai terverifikasi ke $request->attributes ('auth_user').
     */
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();

        if ($bearer) {
            $jwtResult = $this->verifyAndExtractJwt($bearer);
            if ($jwtResult['valid']) {
                $payload = $jwtResult['payload'];
                $sub = $payload['sub'] ?? null;
                $nik = $payload['nik'] ?? null;

                $pegawai = null;
                if ($sub && \Illuminate\Support\Str::isUuid((string) $sub)) {
                    $pegawai = DB::table('pegawai')->where('id', $sub)->first();
                }
                if (! $pegawai && $nik) {
                    $pegawai = DB::table('pegawai')->where('nik', (string) $nik)->first();
                }

                if (! $pegawai) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Autentikasi gagal: Identitas pengguna dalam token tidak ditemukan dalam sistem.',
                    ], 401);
                }

                // Tempelkan data identitas pegawai terverifikasi ke request
                $request->attributes->set('auth_user', $pegawai);
                $request->attributes->set('jwt_payload', $payload);
                $request->attributes->set('user_nik', (string) $pegawai->nik);
                $request->attributes->set('user_id', (string) $pegawai->id);

                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => $jwtResult['message'] ?? 'Token autentikasi tidak valid atau telah kedaluwarsa.',
            ], 401);
        }

        // Pengecekan alternatif server-to-server via header X-API-KEY (hanya jika disetel dan tidak kosong)
        $expectedKey = config('services.simpeg.api_key');
        $givenKey = $request->header('X-API-KEY');

        if (! empty($expectedKey) && ! empty($givenKey) && hash_equals($expectedKey, $givenKey)) {
            // Ambil akun admin sistem sebagai fallback context server-to-server
            $systemAdmin = DB::table('pegawai')->whereIn('role', ['admin', 'sdm'])->first();
            if ($systemAdmin) {
                $request->attributes->set('auth_user', $systemAdmin);
            }
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Autentikasi gagal: Bearer token wajib disertakan untuk mengakses endpoint ini.',
        ], 401);
    }

    private function verifyAndExtractJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return ['valid' => false, 'message' => 'Format token tidak valid.'];
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $secret = config('services.simpeg.jwt_secret') ?: env('JWT_SECRET') ?: config('app.key');

        if (empty($secret)) {
            return ['valid' => false, 'message' => 'Konfigurasi secret token belum disetel di server.'];
        }

        $sig = base64_decode(strtr($sigB64, '-_', '+/'));
        $expectedSig = hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true);

        if (! hash_equals($expectedSig, $sig)) {
            return ['valid' => false, 'message' => 'Tanda tangan token tidak valid.'];
        }

        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);
        if (! is_array($payload)) {
            return ['valid' => false, 'message' => 'Isi token tidak dapat diurai.'];
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            return ['valid' => false, 'message' => 'Token telah kedaluwarsa. Silakan login kembali.'];
        }

        if (empty($payload['sub']) && empty($payload['nik'])) {
            return ['valid' => false, 'message' => 'Token tidak memiliki identitas pengguna.'];
        }

        return ['valid' => true, 'payload' => $payload];
    }
}

