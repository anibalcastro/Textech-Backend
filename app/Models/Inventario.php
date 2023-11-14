<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = "inventario";

    protected $fillable = [
        'nombre_producto',
        'cantidad',
        'color',
        'id_categoria',
        'id_proveedor',
        'comentario'
    ];

    public function categoria(){
        return $this->hasMany(Categorias::class,'id_categoria', 'id');
    }

    public function proveedor(){
        return $this->hasMany(Proveedores::class, 'id_proveedor', 'id');
    }
}
