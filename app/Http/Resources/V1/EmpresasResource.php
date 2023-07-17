<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpresasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_empresa' => $this->nombre_empresa,
            'cedula' => $this->cedula,
            'email' => $this->email,
            'nombre_encargado' => $this->nombre_encargado,
            'telefono_encargado' => $this->telefono_encargado,
            'direccion' => $this->direccion,
            'comentarios' => $this->comentarios
        ];
    }
}
