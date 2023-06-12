<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mediciones>
 */
class MedicionesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_cliente' => rand(1,5),
            'articulo' => 'Gabacha',
            'espalda_superior' => 40.0,
            'talle_espalda_superior'=> 38.0,
            'talle_frente_superior' => 41.0,
            'busto_superior' => 96.0,
            'cintura_superior' => 86.0,
            'cadera_superior' => 106,
            'largo_manga_superior' => 38,
            'ancho_manga_superior' => 28,
            'largo_total_superior' => 53,
            'alto_pinza_superior' => 28,
            'fecha' => $this->faker->date(),
            'observaciones' => $this->faker->text()


        ];
    }
}
