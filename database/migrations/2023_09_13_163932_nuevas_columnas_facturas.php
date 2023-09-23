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
        Schema::table("facturas", function (Blueprint $table) {
            $table->decimal('iva', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->string('estado')->nullable();
            $table->unsignedBigInteger('empresa_id');

            // Relacion con orden de pedidos
            $table->foreign('empresa_id')->references('id')->on('empresas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('iva');
            $table->dropColumn('subtotal');
            $table->dropColumn('estado');
            $table->dropColumn('empresa_id');
        });
    }
};
