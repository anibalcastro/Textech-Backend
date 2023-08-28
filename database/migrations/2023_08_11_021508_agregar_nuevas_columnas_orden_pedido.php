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
        Schema::table('orden_pedido', function (Blueprint $table) {
            $table->unsignedBigInteger('id_factura')->nullable();
            $table->longText('comentario')->nullable();

            $table->foreign('id_factura')->references('id')->on('facturas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('id_factura');
            $table->dropColumn('comentario');
        });
    }
};
