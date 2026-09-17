<?php

namespace Tests\Unit;

use App\Http\Middleware\ApiKeyAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    private const OLD_LEAKED_SECRET = '431279a3be9ed0e670f1ef6df48a4b0983299e664906c0c11444c37a468a537f';
    private const CURRENT_SECRET = 'test-current-simpeg-jwt-secret';

    private function craftJwt(string $secret, int $expiresInSeconds = 3600): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['sub' => 'test-nip', 'exp' => time() + $expiresInSeconds])), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true)), '+/', '-_'), '=');

        return "$header.$payload.$signature";
    }

    private function callMiddleware(string $bearer): Response
    {
        $request = Request::create('/api/v1/pegawai', 'GET');
        $request->headers->set('Authorization', "Bearer $bearer");

        $middleware = new ApiKeyAuth();

        return $middleware->handle($request, fn () => new Response('OK', 200));
    }

    public function test_it_rejects_a_token_signed_with_the_old_leaked_secret_once_a_current_secret_is_configured(): void
    {
        Config::set('services.simpeg.jwt_secret', self::CURRENT_SECRET);

        $response = $this->callMiddleware($this->craftJwt(self::OLD_LEAKED_SECRET));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_it_accepts_a_token_signed_with_the_configured_secret(): void
    {
        Config::set('services.simpeg.jwt_secret', self::CURRENT_SECRET);

        $response = $this->callMiddleware($this->craftJwt(self::CURRENT_SECRET));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_rejects_any_jwt_when_no_secret_is_configured(): void
    {
        Config::set('services.simpeg.jwt_secret', null);

        $response = $this->callMiddleware($this->craftJwt(self::OLD_LEAKED_SECRET));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_it_still_accepts_the_x_api_key_header_regardless_of_jwt_secret_config(): void
    {
        Config::set('services.simpeg.api_key', 'test-static-api-key');
        Config::set('services.simpeg.jwt_secret', null);

        $request = Request::create('/api/v1/pegawai', 'GET');
        $request->headers->set('X-API-KEY', 'test-static-api-key');

        $middleware = new ApiKeyAuth();
        $response = $middleware->handle($request, fn () => new Response('OK', 200));

        $this->assertSame(200, $response->getStatusCode());
    }
}
