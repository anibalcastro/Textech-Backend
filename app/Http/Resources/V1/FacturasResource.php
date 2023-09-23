<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacturasResource extends JsonResource{



    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orden_id' => $this->order_id,
            'empresa_id' => $this->empresa_id,
            'subtotal' => $this->subtotal,
            'iva' => $this->iva,
            'monto'  => $this->monto,
            'fecha' => $this->fecha,
            'metodo_pago'  => $this->metodo_pago,
            'saldo_restante'  => $this->saldo_restante,
            'comentarios'  => $this->comentarios,
            'estado' => $this->estado,
            'cajero'  => $this->cajero,
        ];
    }
}
