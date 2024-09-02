<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mediciones extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_cliente',
        'articulo',
        'largo_inferior',
        'cintura_inferior',
        'cadera_inferior',
        'pierna_inferior',
        'rodilla_inferior',
        'ruedo_inferior',
        'tiro_inferior',
        'espalda_superior',
        'talle_espalda_superior',
        'talle_frente_superior',
        'busto_superior',
        'cintura_superior',
        'cadera_superior',
        'largo_manga_corta_superior',
        'largo_manga_larga_superior',
        'ancho_manga_corta_superior',
        'ancho_manga_larga_superior',
        'largo_total_superior',
        'alto_pinza_superior',
        'talla',
        'fecha',
        'observaciones',
        'sastre',
        'ancho_espalda_superior',
        'largo_total_espalda_superior',
        'separacion_busto_superior',
        'hombros_superior',
        'puno_superior',
        'altura_cadera_inferior',
        'altura_rodilla_inferior',
        'contorno_tiro_inferior',
        'altura_cadera_inferior',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
