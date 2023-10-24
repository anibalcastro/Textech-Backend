<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedores extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion',
        'vendedor',
        'telefono',
        'email'
    ];

    public function productos (){
        return $this->hasMany(ProductosProveedor::class,'proveedor_id', 'id');
    }


}
