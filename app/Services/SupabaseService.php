<?php

namespace App\Services;

class SupabaseService
{
    protected static string $url = 'https://jyywrknlkqwmiqokmcju.supabase.co/rest/v1';
    protected static string $key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imp5eXdya25sa3F3bWlxb2ttY2p1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODMwNzQ3NTEsImV4cCI6MjA5ODY1MDc1MX0.54_KntZXHuOpMj9IQUqUb9rl1a_B4zJQAplNCIbgc9c';

    public static function get(string $table, array $query = []): array
    {
        $queryString = $query ? '?' . http_build_query($query) : '';
        $endpoint = self::$url . '/' . $table . $queryString;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . self::$key,
            'Authorization: Bearer ' . self::$key,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : [];
        }

        return [];
    }

    public static function post(string $table, array $data): ?array
    {
        $endpoint = self::$url . '/' . $table;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . self::$key,
            'Authorization: Bearer ' . self::$key,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            return json_decode($response, true);
        }

        return null;
    }
}
