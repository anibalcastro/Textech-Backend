<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresas extends Model
{
    use HasFactory;

    protected $fillable =[
        'id',
        'nombre_empresa',
        'cedula',
        'email',
        'nombre_encargado',
        'telefono_encargado',
        'direccion',
        'comentarios'
    ];
}
