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
        Schema::create('detalle_reparacion_prendas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_reparacion');
            $table->unsignedBigInteger('id_producto');
            $table->decimal('precio_unitario');
            $table->integer('cantidad');
            $table->longText('descripcion');
            $table->decimal('subtotal');
            $table->timestamps();

            $table->foreign('id_reparacion')->references('id')->on('reparacion_prendas');
            $table->foreign('id_producto')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_reparacion_prendas');
    }
};
