<?php

namespace App\Service;

class GeoLocationService
{
    /**
     * Recuperer les infos de geolocalisation a partir d'une adresse IP
     */
    public function getLocationFromIp(string $ip): array
    {
        $default = [
            'country' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        // Les IPs locales ne peuvent pas etre geolocalisees
        if ($this->isLocalIp($ip)) {
            return array_merge($default, [
                'country' => 'Local',
                'city' => 'Localhost',
            ]);
        }

        try {
            $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,city,lat,lon&lang=fr';

            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return $default;
            }

            $data = json_decode($response, true);

            if (!$data || ($data['status'] ?? '') !== 'success') {
                return $default;
            }

            return [
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'latitude' => $data['lat'] ?? null,
                'longitude' => $data['lon'] ?? null,
            ];
        } catch (\Exception $e) {
            return $default;
        }
    }

    private function isLocalIp(string $ip): bool
    {
        $localIps = ['127.0.0.1', '::1', '0.0.0.0', 'localhost'];

        if (in_array($ip, $localIps, true)) {
            return true;
        }

        if (
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.16.') ||
            str_starts_with($ip, '172.17.') ||
            str_starts_with($ip, '172.18.') ||
            str_starts_with($ip, '172.19.') ||
            str_starts_with($ip, '172.2') ||
            str_starts_with($ip, '172.30.') ||
            str_starts_with($ip, '172.31.')
        ) {
            return true;
        }

        return false;
    }
}