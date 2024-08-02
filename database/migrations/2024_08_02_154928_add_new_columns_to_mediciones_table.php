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
        Schema::table('mediciones', function (Blueprint $table) {
            //
            $table->decimal('ancho_espalda_superior',10,2)->default(0);
            $table->decimal('largo_total_espalda_superior',10,2)->default(0);
            $table->decimal('separacion_busto_superior',10,2)->default(0);
            $table->decimal('hombros_superior',10,2)->default(0);
            $table->decimal('puno_superior',10,2)->default(0);
            $table->decimal('altura_cadera_inferior',10,2)->default(0);
            $table->decimal('altura_rodilla_inferior',10,2)->default(0);
            $table->decimal('contorno_tiro_inferior',10,2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            //
            $table->dropColumn('ancho_espalda_superior');
            $table->dropColumn('largo_total_espalda_superior');
            $table->dropColumn('separacion_busto_superior');
            $table->dropColumn('hombros_superior');
            $table->dropColumn('puno_superior');
            $table->dropColumn('altura_cadera_inferior');
            $table->dropColumn('altura_rodilla_inferior');
            $table->dropColumn('contorno_tiro_inferior');
        });
    }
};
