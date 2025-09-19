<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ciudad;

class CiudadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ciudades = [
            ['nombre' => 'Bucaramanga'],
            ['nombre' => 'Floridablanca'],
            ['nombre' => 'Girón'],
            ['nombre' => 'Piedecuesta'],
            ['nombre' => 'San Gil'],
        ];

        foreach ($ciudades as $ciudad) {
            Ciudad::create($ciudad);
        }
    }
}
