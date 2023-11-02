<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facturas extends Model
{
    use HasFactory;

    protected $fillable = [
        'orden_id',
        'reparacion_id',
        'empresa_id',
        'subtotal',
        'iva',
        'monto',
        'fecha',
        'metodo_pago',
        'saldo_restante',
        'comentarios',
        'estado',
        'cajero'
    ];

    public function ordenPedido(){
        return $this->hasMany(OrdenPedido::class,'order_id', 'id');
    }
}
