<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrdenPedido;
use App\Models\Productos;

class DetallePedido extends Model
{
    use HasFactory;

    protected $table = 'detalle_pedido';

    protected $fillable = [
        'id',
        'id_pedido',
        'id_producto',
        'precio_unitario',
        'cantidad',
        'descripcion',
        'subtotal'
    ];

    public function orden_pedido(){
        return $this->belongsTo(OrdenPedido::class, 'id_pedido', 'id');
    }

    public function productos(){
        return $this->hasMany(Productos::class, 'id_producto', 'id');
    }


}
