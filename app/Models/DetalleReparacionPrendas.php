<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleReparacionPrendas extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_reparacion',
        'id_producto',
        'precio_unitario',
        'cantidad',
        'descripcion',
        'subtotal'
    ];

    public function productos() {
        return $this->hasMany(Productos::class, 'id_producto', 'id');
    }
}
