<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
      // 1. Actualizar valores nulos a 0 usando COALESCE (para PostgreSQL)
        DB::statement('
            UPDATE mediciones
            SET
                largo_entrepierna_inferior = COALESCE(largo_entrepierna_inferior, 0),
                alto_cadera_superior = COALESCE(alto_cadera_superior, 0),
                ancho_pecho_superior = COALESCE(ancho_pecho_superior, 0),
                boca_manga_superior = COALESCE(boca_manga_superior, 0),
                contorno_cuello_superior = COALESCE(contorno_cuello_superior, 0),
                escote_superior = COALESCE(escote_superior, 0)
        ');

        // 2. Cambiar columnas para que tengan default(0)
        Schema::table('mediciones', function (Blueprint $table) {
            $table->decimal('largo_entrepierna_inferior', 8, 2)->default(0)->change();
            $table->decimal('alto_cadera_superior', 8, 2)->default(0)->change();
            $table->decimal('ancho_pecho_superior', 8, 2)->default(0)->change();
            $table->decimal('boca_manga_superior', 8, 2)->default(0)->change();
            $table->decimal('contorno_cuello_superior', 8, 2)->default(0)->change();
            $table->decimal('escote_superior', 8, 2)->default(0)->change();
        });
    }


};

