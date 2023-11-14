<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventarioResource extends JsonResource
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
            'cantidad' => $this->cantidad,
            'color' => $this->color,
            'id_categoria' => $this->id_categoria,
            'id_proveedor' => $this->id_proveedor,
            'comentario' => $this->comentario
        ];
    }
}
