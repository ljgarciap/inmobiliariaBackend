<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Propiedad;
use App\Models\Ciudad;
use App\Models\Caracteristica;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropiedadSeeder extends Seeder
{
    private $googleMapsApiKey;

    public function __construct()
    {
        $this->googleMapsApiKey = env('GOOGLE_MAPS_API_KEY');
    }

    public function run(): void
    {
        $ciudades = Ciudad::all();
        $caracteristicas = Caracteristica::all();

        $propiedades = [
            [
                'detalle' => 'Hermoso apartamento en zona cabecera de Bucaramanga',
                'descripcion' => 'Apartamento amplio con vista panorámica, acabados de lujo, cerca de centros comerciales y parques.',
                'ciudad_id' => $ciudades->where('nombre', 'Bucaramanga')->first()->id,
                'direccion_completa' => 'Calle 45 # 25-35, Bucaramanga, Santander',
                'habitaciones' => 3,
                'banios' => 2,
                'tipo_transaccion' => 'venta',
                'precio_venta' => 850000000,
                'precio_arriendo' => null,
            ],
            [
                'detalle' => 'Casa campestre en Floridablanca',
                'descripcion' => 'Casa con amplio jardín, zona de parrilla, perfecta para familia, sector tranquilo y seguro.',
                'ciudad_id' => $ciudades->where('nombre', 'Floridablanca')->first()->id,
                'direccion_completa' => 'Carrera 12 # 15-20, Floridablanca, Santander',
                'habitaciones' => 4,
                'banios' => 3,
                'tipo_transaccion' => 'arriendo',
                'precio_venta' => null,
                'precio_arriendo' => 2500000,
            ],
            [
                'detalle' => 'Apartamento en conjunto cerrado en Girón',
                'descripcion' => 'Apartamento moderno con piscina, gimnasio, seguridad 24/7.',
                'ciudad_id' => $ciudades->where('nombre', 'Girón')->first()->id,
                'direccion_completa' => 'Avenida Central # 30-40, Girón, Santander',
                'habitaciones' => 2,
                'banios' => 2,
                'tipo_transaccion' => 'venta',
                'precio_venta' => 450000000,
                'precio_arriendo' => null,
            ]
        ];

        foreach ($propiedades as $propiedadData) {
            // Obtener coordenadas con Geocoding
            $coordinates = $this->geocodeAddress($propiedadData['direccion_completa']);

            $propiedad = Propiedad::create([
                ...$propiedadData,
                'latitud' => $coordinates['lat'],
                'longitud' => $coordinates['lng'],
                'user_id' => 1
            ]);

            // Asignar características aleatorias
            $randomFeatures = $caracteristicas->random(rand(2, 4))->pluck('id');
            $propiedad->caracteristicas()->attach($randomFeatures);
        }
    }

    private function geocodeAddress(string $address): array
    {
        if (!$this->googleMapsApiKey) {
            Log::warning('Google Maps API key no configurada. Usando coordenadas por defecto.');
            return ['lat' => 7.1193, 'lng' => -73.1227]; // Bucaramanga por defecto
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $this->googleMapsApiKey,
                'region' => 'co'
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng']
                ];
            }

            Log::warning('Geocoding falló para: ' . $address . ' - ' . ($data['status'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('Error en Geocoding: ' . $e->getMessage());
        }

        // Fallback a coordenadas por ciudad
        return $this->getFallbackCoordinates($address);
    }

    private function getFallbackCoordinates(string $address): array
    {
        $cityCoordinates = [
            'Bucaramanga' => ['lat' => 7.1193, 'lng' => -73.1227],
            'Floridablanca' => ['lat' => 7.0622, 'lng' => -73.0864],
            'Girón' => ['lat' => 7.0734, 'lng' => -73.1688],
            'Piedecuesta' => ['lat' => 7.0794, 'lng' => -73.0494],
            'San Gil' => ['lat' => 6.5557, 'lng' => -73.1331]
        ];

        foreach ($cityCoordinates as $city => $coords) {
            if (stripos($address, $city) !== false) {
                return $coords;
            }
        }

        return ['lat' => 4.135, 'lng' => -73.635]; // Colombia por defecto
    }
}
