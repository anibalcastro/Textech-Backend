<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReparacionPrendas extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'titulo',
        'id_empresa',
        'id_factura',
        'fecha',
        'precio',
        'estado',
        'comentario',
        'telefono'
    ];

    public function detalleReparacion(){
        return $this->hasMany(DetalleReparacionPrendas::class, 'id_reparacion', 'id');
    }


}
