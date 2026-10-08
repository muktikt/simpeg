<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    /**
     * Kirim Push Notification ke seluruh perangkat HP pegawai via OneSignal.
     * Sekaligus otomatis mencatat ke tabel `notifikasi` di database (Lonceng In-App Mobile).
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

        $cleanIsi = mb_substr(strip_tags($isi), 0, 160);

        // 1. Simpan ke tabel `notifikasi` Supabase (agar lonceng notifikasi di HP bertambah)
        try {
            $pegawaiQuery = DB::table('pegawai')->select('id', 'role');
            if (!empty($targetRoles) && count($targetRoles) < 7) {
                $pegawaiQuery->whereIn(DB::raw('LOWER(role)'), array_map('strtolower', $targetRoles));
            }
            $pIds = $pegawaiQuery->pluck('id')->toArray();
            $pengumumanId = isset($customData['pengumuman_id']) ? (int)$customData['pengumuman_id'] : null;
            self::simpanKeTabelNotifikasi($pIds, '📢 ' . $judul, $cleanIsi, $pengumumanId);
        } catch (\Throwable $e) {
            Log::warning('OneSignalService: Gagal sync pengumuman ke DB notifikasi: ' . $e->getMessage());
        }

        // 2. Kirim Push Notification OneSignal Cloud
        if (empty($appId)) {
            Log::warning('OneSignal App ID belum diset.');
            return false;
        }

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

    /**
     * Kirim Push Notification ke satu atau beberapa NIK pegawai tertentu via OneSignal.
     * Sekaligus otomatis mencatat ke tabel `notifikasi` di database (Lonceng In-App Mobile).
     *
     * Digunakan oleh:
     * - Pengajuan Cuti (Disetujui / Ditolak)
     * - Pengaduan Pegawai (Verifikasi / Tindak Lanjut)
     * - Dokumen Resmi (SK / Diklat Baru)
     * - Slip Gaji Bulanan (Terbit)
     * - Slip THR (Terbit)
     * - Slip Gaji ke-13 (Terbit)
     *
     * @param string|array $niks NIK pegawai tunggal atau array NIK
     * @param string $judul Judul Notifikasi
     * @param string $isi Pesan Notifikasi
     * @param array $customData Data tambahan untuk routing halaman saat diklik
     * @return bool
     */
    public static function kirimNotifikasiPegawai(string|array $niks, string $judul, string $isi, array $customData = []): bool
    {
        $appId = config('services.onesignal.app_id') ?? env('ONESIGNAL_APP_ID', 'b7556b90-2f97-44f2-93e2-bd94abe8229e');
        $restApiKey = config('services.onesignal.rest_api_key') ?? env('ONESIGNAL_REST_API_KEY');

        if (empty($niks)) {
            return false;
        }

        $nikList = is_array($niks) ? array_values(array_filter($niks)) : [trim((string)$niks)];
        if (empty($nikList)) {
            return false;
        }

        $nikList = array_map('strval', $nikList);
        $cleanIsi = mb_substr(strip_tags($isi), 0, 160);

        // 1. Simpan ke tabel `notifikasi` Supabase (agar muncul di lonceng notifikasi user terkait)
        try {
            $pIds = DB::table('pegawai')
                ->whereIn('nik', $nikList)
                ->pluck('id')
                ->toArray();
            self::simpanKeTabelNotifikasi($pIds, $judul, $cleanIsi);
        } catch (\Throwable $e) {
            Log::warning('OneSignalService: Gagal sync pegawai ke DB notifikasi: ' . $e->getMessage());
        }

        // 2. Kirim Push Notification OneSignal Cloud
        if (empty($appId)) {
            Log::warning('OneSignal App ID belum diset.');
            return false;
        }

        $payload = [
            'app_id' => $appId,
            'include_aliases' => [
                'external_id' => $nikList,
            ],
            'include_external_user_ids' => $nikList,
            'target_channel' => 'push',
            'headings' => [
                'en' => $judul,
                'id' => $judul,
            ],
            'contents' => [
                'en' => $cleanIsi,
                'id' => $cleanIsi,
            ],
            'data' => array_merge([
                'type' => $customData['type'] ?? 'umum',
                'judul' => $judul,
                'waktu' => now()->toIso8601String(),
            ], $customData),
        ];

        try {
            $request = Http::timeout(10);
            if (!empty($restApiKey)) {
                $request = $request->withHeaders([
                    'Authorization' => 'Basic ' . $restApiKey,
                ]);
            }

            $response = $request->post('https://onesignal.com/api/v1/notifications', $payload);

            if ($response->successful()) {
                Log::info('OneSignal Push Notification ke NIK (' . implode(',', $nikList) . ') berhasil: ' . $judul);
                return true;
            }

            Log::warning('OneSignal Response Error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('OneSignal Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim Push Notification ke seluruh pegawai dengan role tertentu.
     * Sekaligus otomatis mencatat ke tabel `notifikasi` di database (Lonceng In-App Mobile).
     */
    public static function kirimNotifikasiRole(array $roles, string $judul, string $isi, array $customData = []): bool
    {
        $appId = config('services.onesignal.app_id') ?? env('ONESIGNAL_APP_ID', 'b7556b90-2f97-44f2-93e2-bd94abe8229e');
        $restApiKey = config('services.onesignal.rest_api_key') ?? env('ONESIGNAL_REST_API_KEY');

        if (empty($roles)) {
            return false;
        }

        $cleanIsi = mb_substr(strip_tags($isi), 0, 160);

        $expandedRoles = [];
        foreach ($roles as $r) {
            $rLow = strtolower(trim($r));
            $expandedRoles[] = $rLow;
            if ($rLow === 'kadiv') {
                $expandedRoles[] = 'kadivkategori';
            } elseif ($rLow === 'kadivkategori') {
                $expandedRoles[] = 'kadiv';
            }
        }
        $expandedRoles = array_unique($expandedRoles);

        // 1. Simpan ke tabel `notifikasi` Supabase (agar lonceng notifikasi user bertambah)
        try {
            $pIds = DB::table('pegawai')
                ->whereIn(DB::raw('LOWER(role)'), $expandedRoles)
                ->pluck('id')
                ->toArray();
            self::simpanKeTabelNotifikasi($pIds, $judul, $cleanIsi);
        } catch (\Throwable $e) {
            Log::warning('OneSignalService: Gagal sync role ke DB notifikasi: ' . $e->getMessage());
        }

        // 2. Kirim Push Notification OneSignal Cloud
        if (empty($appId)) {
            return false;
        }

        $filters = [];
        foreach (array_values($roles) as $index => $role) {
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

        $payload = [
            'app_id' => $appId,
            'filters' => $filters,
            'headings' => [
                'en' => $judul,
                'id' => $judul,
            ],
            'contents' => [
                'en' => $cleanIsi,
                'id' => $cleanIsi,
            ],
            'data' => array_merge([
                'type' => $customData['type'] ?? 'umum',
                'judul' => $judul,
                'waktu' => now()->toIso8601String(),
            ], $customData),
        ];

        try {
            $request = Http::timeout(10);
            if (!empty($restApiKey)) {
                $request = $request->withHeaders([
                    'Authorization' => 'Basic ' . $restApiKey,
                ]);
            }

            $response = $request->post('https://onesignal.com/api/v1/notifications', $payload);

            if ($response->successful()) {
                Log::info('OneSignal Push Notification ke Role (' . implode(',', $roles) . ') berhasil: ' . $judul);
                return true;
            }

            Log::warning('OneSignal Role Error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('OneSignal Role Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper internal untuk menyimpan riwayat notifikasi ke tabel `notifikasi` di database (Lonceng In-App).
     */
    protected static function simpanKeTabelNotifikasi(array $pegawaiIds, string $judul, string $pesan, ?int $pengumumanId = null): void
    {
        if (empty($pegawaiIds)) {
            return;
        }

        try {
            $now = now();
            $rows = [];
            foreach (array_unique($pegawaiIds) as $pid) {
                if (empty($pid)) continue;
                $row = [
                    'untuk_pegawai_id' => $pid,
                    'judul' => $judul,
                    'pesan' => $pesan,
                    'dibaca' => false,
                    'waktu' => $now,
                ];
                if ($pengumumanId !== null) {
                    $row['pengumuman_id'] = $pengumumanId;
                }
                $rows[] = $row;
            }

            if (!empty($rows)) {
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table('notifikasi')->insert($chunk);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OneSignalService: Gagal simpan ke tabel notifikasi DB: ' . $e->getMessage());
        }
    }
}

