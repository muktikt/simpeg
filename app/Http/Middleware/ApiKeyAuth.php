<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyAuth
{
    /**
     * Cek header X-API-KEY atau Bearer token (JWT Staf / API Key).
     */
    public function handle(Request $request, Closure $next)
    {
        $expectedKey = config('services.simpeg.api_key', env('SIMPEG_API_KEY'));
        $givenKey = $request->header('X-API-KEY');

        if ($expectedKey && $givenKey && hash_equals($expectedKey, $givenKey)) {
            return $next($request);
        }

        // Cek Bearer token jika ada
        $bearer = $request->bearerToken();
        if ($bearer) {
            // Jika token sama dengan api_key
            if ($expectedKey && hash_equals($expectedKey, $bearer)) {
                return $next($request);
            }

            // Jika token adalah JWT HS256 valid
            if ($this->validateJwt($bearer)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Autentikasi gagal: API key atau Bearer token tidak valid.',
        ], 401);
    }

    private function validateJwt(string $jwt): bool
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $secret = config('services.simpeg.jwt_secret');
        if (! $secret) {
            return false;
        }

        $sig = base64_decode(strtr($sigB64, '-_', '+/'));
        $expectedSig = hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true);

        if (! hash_equals($expectedSig, $sig)) {
            return false;
        }

        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);
        if (! $payload) {
            return false;
        }

        if (isset($payload['exp']) && time() > $payload['exp']) {
            return false;
        }

        return true;
    }
}
