<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabajoSemanal extends Model
{
    use HasFactory;

    protected $table = 'trabajo_semanal';

    protected $fillable = [
        'idOrden',
        'idSemana',
        'estado'
    ];

    public function orden(){
        return $this->belongsTo(OrdenPedido::class, 'idOrden');
    }

    public function semana(){
        return $this->belongsTo(Semana::class, 'idSemana');
    }
}
