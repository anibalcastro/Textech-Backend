<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleReparacionPrendas extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            "id_reparacion" => $this->id_reparacion,
            "id_producto" => $this->id_producto,
            "precio_unitario" => $this->precio_unitario,
            "cantidad" => $this->cantidad,
            "descripcion" => $this->descripcion,
            "subtotal" => $this->subtotal
        ];
    }
}
