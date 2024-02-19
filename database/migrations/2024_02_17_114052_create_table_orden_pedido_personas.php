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
            Schema::create('orden_pedido_persona', function (Blueprint $table) {
                //
                $table->id();
                $table->unsignedBigInteger('id_orden');
                $table->foreign('id_orden')->references('id')->on('orden_pedido');
                $table->string('prenda');
                $table->string('nombre');
                $table->integer('cantidad');
                $table->boolean('entregado')->default(false);
                $table->timestamps();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_pedido_persona');
    }
};
