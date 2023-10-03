<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbonosResource extends JsonResource{

    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'factura_id' => $this->factura_id,
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'comentarios' => $this->comentarios,
            'estado' => $this->estado,
            'cajero' => $this->cajero,
            'created_at' => $this->created_at
        ];
    }

}
