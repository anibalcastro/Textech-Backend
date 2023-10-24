<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductosProveedor extends Model
{
    use HasFactory;

    protected $table = 'productos_proveedor';

    protected $fillable = [
        'proveedor_id',
        'nombre_producto',
        'descripcion',
        'precio'
    ];

    public function proveedor(){
        return $this->belongsTo(Proveedores::class, 'proveedor_id', 'id');
    }
}
