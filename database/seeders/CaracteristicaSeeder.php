<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CaracteristicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $caracteristicas = [
            ['nombre' => 'Piscina', 'icono' => 'pool'],
            ['nombre' => 'Ascensor', 'icono' => 'elevator'],
            ['nombre' => 'Parqueadero', 'icono' => 'parking'],
            ['nombre' => 'Zona Verde', 'icono' => 'green_area'],
            ['nombre' => 'Seguridad 24/7', 'icono' => 'security'],
            ['nombre' => 'Gimnasio', 'icono' => 'gym'],
            ['nombre' => 'Jacuzzi', 'icono' => 'jacuzzi'],
        ];

        foreach ($caracteristicas as $caracteristica) {
            Caracteristica::create($caracteristica);
        }
    }
}
