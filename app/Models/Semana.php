<?php

namespace App\Models;

use App\Models\TrabajoSemanal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semana extends Model
{
    use HasFactory;

    protected $table = 'semana';

    protected $fillable = [
        'id',
        'fechaInicio',
        'fechaFinal',
        'nSemana',
    ];

    // Relación con los trabajos semanales
    public function weeklyJobs()
    {
        return $this->hasMany(TrabajoSemanal::class, 'idSemana');
    }
}
