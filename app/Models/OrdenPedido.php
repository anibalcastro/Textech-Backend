<?php

namespace App\Models;


use App\Models\Empresas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenPedido extends Model
{
    use HasFactory;

    protected $table = 'orden_pedido';

    protected $fillable = [
        'id',
        'id_empresa',
        'fecha_orden',
        'precio_total',
        'estado'
    ];

    public function detalles(){
        return $this->hasMany(DetallePedido::class,'id_pedido', 'id');
    }
}
