<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;

    protected $fillable =[
        'nombre',
        'apellido1',
        'apellido2',
        'cedula',
        'email',
        'telefono',
        'empresa',
        'departamento',
        'comentarios'
    ];
}
