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
        Schema::create('trabajo_semanal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idOrden');
            $table->unsignedBigInteger('idSemana');
            $table->boolean('estado')->default(false);
            $table->timestamps();

             // Agregar claves foráneas
             $table->foreign('idOrden')->references('id')->on('orden_pedido');
             $table->foreign('idSemana')->references('id')->on('semana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajo_semanal');
    }
};
