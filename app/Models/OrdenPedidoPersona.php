<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenPedidoPersona extends Model
{
    use HasFactory;

    protected $table = 'orden_pedido_persona';

    protected $fillable = [
        'id_orden',
        'prenda',
        'nombre',
        'cantidad',
        'entregado'
    ];

    public function ordenPedido(){
        return $this->belongsTo(OrdenPedido::class, 'id_orden');
    }
}
