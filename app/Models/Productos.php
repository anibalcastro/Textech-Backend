<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    use HasFactory;

    protected $fillable =[
        'nombre_producto',
        'descripcion',
        'precio_unitario',
        'categoria'
    ];
}
