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
        Schema::create('reparacion_prendas', function (Blueprint $table){
            $table->id();
            $table->string('titulo')->nullable();
            $table->unsignedBigInteger('id_empresa');
            $table->dateTime('fecha');
            $table->decimal('precio');
            $table->string('estado');
            $table->unsignedBigInteger('id_factura')->nullable();
            $table->longText('comentario')->nullable();
            $table->timestamps();

            $table->foreign('id_factura')->references('id')->on('facturas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reparacion_prendas');
    }
};
