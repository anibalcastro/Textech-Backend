<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
         Schema::table('mediciones', function (Blueprint $table) {
        $table->decimal('largo_entrepierna_inferior', 8, 2)->nullable();
        $table->decimal('alto_cadera_superior', 8, 2)->nullable();
        $table->decimal('ancho_pecho_superior', 8, 2)->nullable();
        $table->decimal('boca_manga_superior', 8, 2)->nullable();
        $table->decimal('contorno_cuello_superior', 8, 2)->nullable();
        $table->decimal('escote_superior', 8, 2)->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('mediciones', function (Blueprint $table) {
        $table->dropColumn([
            'largo_entrepierna_inferior',
            'alto_cadera_superior',
            'ancho_pecho_superior',
            'boca_manga_superior',
            'contorno_cuello_superior',
            'escote_superior',
        ]);
    });
    }
};
