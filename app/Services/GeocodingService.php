<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Ciudad;

class GeocodingService
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_MAPS_API_KEY');
    }

    public function geocodeAddress(string $address): ?array
    {
        if (!$this->apiKey) {
            Log::warning('Google Maps API key no configurada');
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $this->apiKey,
                'region' => 'co',
                'components' => 'country:CO'
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                return [
                    'lat' => $data['results'][0]['geometry']['location']['lat'],
                    'lng' => $data['results'][0]['geometry']['location']['lng'],
                    'formatted_address' => $data['results'][0]['formatted_address']
                ];
            }

            Log::warning('Geocoding failed for: ' . $address . ' - Status: ' . $data['status']);

        } catch (\Exception $e) {
            Log::error('Geocoding error: ' . $e->getMessage());
        }

        return null;
    }

    public function getCoordinatesByCity(int $ciudadId): array
    {
        $ciudad = Ciudad::find($ciudadId);

        if (!$ciudad) {
            return ['lat' => 4.135, 'lng' => -73.635]; // Colombia por defecto
        }

        $cityCoordinates = [
            'Bucaramanga' => ['lat' => 7.1193, 'lng' => -73.1227],
            'Floridablanca' => ['lat' => 7.0622, 'lng' => -73.0864],
            'Girón' => ['lat' => 7.0734, 'lng' => -73.1688],
            'Piedecuesta' => ['lat' => 7.0794, 'lng' => -73.0494],
            'San Gil' => ['lat' => 6.5557, 'lng' => -73.1331]
        ];

        return $cityCoordinates[$ciudad->nombre] ?? ['lat' => 4.135, 'lng' => -73.635];
    }
}
