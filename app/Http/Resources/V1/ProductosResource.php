<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductosResource extends JsonResource
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
            'nombre_producto' => $this->nombre_producto,
            'descripcion' => $this->descripcion,
            'precio_unitario' => $this->precio_unitario,
            'categoria' => $this->precio_unitario
        ];
    }
}
