<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clientes>
 */
class ClientesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre'=>'Anibal Jafeth',
            'apellido1' => 'Castro',
            'apellido2' => 'Ponce',
            'cedula' =>'208110305',
            'email' => 'anibalcastro194@gmail.com',
            'telefono' => '85424471',
            'empresa' => 'Independiente',
            'departamento' => 'TI',
            'comentarios' => $this->faker->text()
        ];
    }
}
