<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenPedido extends Model
{
    use HasFactory;

    protected $table = 'orden_pedido';

    protected $fillable = [
        'id',
        'proforma',
        'proforma2',
        'proforma3',
        'titulo',
        'id_empresa',
        'telefono',
        'fecha_orden',
        'precio_total',
        'estado',
        'comentario',
        'pizarra',
        'tela'
    ];

    public function detalles(){
        return $this->hasMany(DetallePedido::class,'id_pedido', 'id');
    }

    public function archivos()
{
    return $this->hasMany(Archivos::class, 'order_id', 'id');
}
}
