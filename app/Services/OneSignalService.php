<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    /**
     * Kirim Push Notification ke seluruh perangkat HP pegawai via OneSignal.
     *
     * @param string $judul Judul Pengumuman
     * @param string $isi Ringkasan isi pengumuman
     * @param array $targetRoles Array target role (misal: ['pegawai', 'sdm', ...])
     * @param array $customData Data tambahan (opsional)
     * @return bool
     */
    public static function kirimPengumuman(string $judul, string $isi, array $targetRoles = [], array $customData = []): bool
    {
        $appId = config('services.onesignal.app_id') ?? env('ONESIGNAL_APP_ID', 'b7556b90-2f97-44f2-93e2-bd94abe8229e');
        $restApiKey = config('services.onesignal.rest_api_key') ?? env('ONESIGNAL_REST_API_KEY');

        if (empty($appId)) {
            Log::warning('OneSignal App ID belum diset.');
            return false;
        }

        $cleanIsi = mb_substr(strip_tags($isi), 0, 160);

        $payload = [
            'app_id' => $appId,
            'headings' => [
                'en' => '📢 ' . $judul,
                'id' => '📢 ' . $judul,
            ],
            'contents' => [
                'en' => $cleanIsi,
                'id' => $cleanIsi,
            ],
            'data' => array_merge([
                'type' => 'pengumuman',
                'judul' => $judul,
                'waktu' => now()->toIso8601String(),
            ], $customData),
        ];

        // Jika ada filter role tertentu (dan bukan semua role)
        if (!empty($targetRoles) && count($targetRoles) < 7) {
            $filters = [];
            foreach (array_values($targetRoles) as $index => $role) {
                if ($index > 0) {
                    $filters[] = ['operator' => 'OR'];
                }
                $filters[] = [
                    'field' => 'tag',
                    'key' => 'role',
                    'relation' => '=',
                    'value' => strtolower(trim($role)),
                ];
            }
            $payload['filters'] = $filters;
        } else {
            // Kirim ke seluruh pelanggan / device yang terinstal
            $payload['included_segments'] = ['Total Subscriptions'];
        }

        try {
            $request = Http::timeout(10);
            if (!empty($restApiKey)) {
                $request = $request->withHeaders([
                    'Authorization' => 'Basic ' . $restApiKey,
                ]);
            }

            $response = $request->post('https://onesignal.com/api/v1/notifications', $payload);

            if ($response->successful()) {
                Log::info('OneSignal Push Notification berhasil dikirim: ' . $judul);
                return true;
            }

            Log::warning('OneSignal Response Error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('OneSignal Exception: ' . $e->getMessage());
            return false;
        }
    }
}
