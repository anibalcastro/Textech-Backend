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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_id');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->string('metodo_pago');
            $table->decimal('saldo_restante', 10, 2);
            $table->string('comentarios');
            $table->string('cajero');
            $table->timestamps();

            // Relacion con orden de pedidos
            $table->foreign('orden_id')->references('id')->on('orden_pedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists("factura");
    }
};
