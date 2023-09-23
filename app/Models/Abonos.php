<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abonos extends Model
{
    use HasFactory;

    protected $fillable = [
        'factura_id',
        'monto',
        'metodo_pago',
        'comentarios',
        'estado',
        'cajero'
    ];

    public function factura(){
        return $this->hasMany(Facturas::class,'factura_id', 'id');
    }
}
