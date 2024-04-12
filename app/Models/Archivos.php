<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archivos extends Model
{
    use HasFactory;

    protected $table = 'files';

    protected $fillable = [
        'order_id',
        'file_path'
    ];

    public function order()
    {
        return $this->belongsTo(OrdenPedido::class, 'id');
    }
}
