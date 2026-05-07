<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpGeolocator
{
    public static function lookup($ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return ['city' => 'Localhost', 'country' => 'Local'];
        }
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=city,country");
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'city' => $data['city'] ?? 'Unknown',
                    'country' => $data['country'] ?? 'Unknown',
                ];
            }
        } catch (\Exception $e) {
            // fallback
        }
        return ['city' => 'Unknown', 'country' => 'Unknown'];
    }
}