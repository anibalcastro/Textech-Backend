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
            'nombre'=>$this->faker->name(),
            'apellido1' => $this->faker->name(),
            'apellido2' => $this->faker->name(),
            'cedula' =>'101110111',
            'email' => 'anibalcastro194@gmail.com',
            'telefono' => '8888 8888',
            'empresa' => $this->faker->name(),
            'departamento' => $this->faker->name(),
            'comentarios' => $this->faker->text()
        ];
    }
}
