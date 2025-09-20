<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Caracteristica;

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
            ['nombre' => 'Gimnasio', 'icono' => 'gym'],
            ['nombre' => 'Jacuzzi', 'icono' => 'jacuzzi'],
        ];

        foreach ($caracteristicas as $caracteristica) {
            Caracteristica::create($caracteristica);
        }
    }
}
