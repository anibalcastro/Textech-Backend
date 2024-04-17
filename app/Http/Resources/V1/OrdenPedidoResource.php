<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenPedidoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            "id" => $this->id,
            "proforma" => $this->proforma,
            "titulo" => $this->titulo,
            "id_empresa" => $this->id_empresa,
            "fecha_orden" => $this->fecha_orden,
            "precio_total" => $this->precio_total,
            "estado" =>  $this->estado
        ];
    }
}
