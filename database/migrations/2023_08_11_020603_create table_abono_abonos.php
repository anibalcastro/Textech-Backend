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
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id');
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago');
            $table->string("comentarios");
            $table->string("cajero");
            $table->timestamps();

            // Define las relaciones con otras tablas si es necesario
            $table->foreign('factura_id')->references('id')->on('facturas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists("abonos");
    }
};
