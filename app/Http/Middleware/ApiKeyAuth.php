<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyAuth
{
    /**
     * Cek header X-API-KEY terhadap SIMPEG_API_KEY di .env.
     */
    public function handle(Request $request, Closure $next)
    {
        $expected = config('services.simpeg.api_key');
        $given = $request->header('X-API-KEY');

        if (! $expected || ! $given || ! hash_equals($expected, $given)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
