<?php

namespace App\Http\Request;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReparacionPrendasResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "titulo" => $this->titulo,
            "id_empresa" => $this->id_empresa,
            "id_factura" => $this->id_factura,
            "fecha" => $this->fecha,
            "precio" => $this->precio,
            "estado" =>  $this->estado,
            "comentario" => $this->comentario,
            "telefono" => $this->telefono
        ];
    }
}
