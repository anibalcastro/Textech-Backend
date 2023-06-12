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
        Schema::create('mediciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->string('articulo',60);
            $table->decimal('cintura_inferior',10,2)->default(0);
            $table->decimal('cadera_inferior',10,2)->default(0);
            $table->decimal('pierna_inferior',10,2)->default(0);
            $table->decimal('rodilla_inferior',10,2)->default(0);
            $table->decimal('ruedo_inferior',10,2)->default(0);
            $table->decimal('tiro_inferior',10,2)->default(0);
            $table->decimal('espalda_superior',10,2)->default(0);
            $table->decimal('talle_espalda_superior',10,2)->default(0);
            $table->decimal('talle_frente_superior',10,2)->default(0);
            $table->decimal('busto_superior',10,2)->default(0);
            $table->decimal('cintura_superior',10,2)->default(0);
            $table->decimal('cadera_superior',10,2)->default(0);
            $table->decimal('largo_manga_superior',10,2)->default(0);
            $table->decimal('ancho_manga_superior',10,2)->default(0);
            $table->decimal('largo_total_superior',10,2)->default(0);
            $table->decimal('alto_pinza_superior',10,2)->default(0);
            $table->date('fecha');
            $table->longText('observaciones');

            $table->foreign('id_cliente')->references('id')->on('clientes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediciones');
    }
};
